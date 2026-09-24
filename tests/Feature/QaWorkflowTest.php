<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\FinalTask;
use App\Models\Meeting;
use App\Models\Semester;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class QaWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function fixture(): array
    {
        $laboran = User::factory()->create(['role' => 'Laboran']);
        $aslab = User::factory()->create(['role' => 'Aslab']);
        $dosen = User::factory()->create(['role' => 'Dosen']);
        $student = User::factory()->create();
        $semester = Semester::create(['name' => 'Aktif', 'is_active' => true]);
        $course = Course::create(['semester_id' => $semester->id, 'course_name' => 'Pemrograman', 'class_group' => 'A', 'target_semester' => 1, 'dosen_id' => $dosen->id, 'aslab_id' => $aslab->id, 'laboran_id' => $laboran->id, 'enrollment_code' => 'JOIN1234']);
        $course->students()->attach($student);

        return [$course, $laboran, $aslab, $dosen, $student];
    }

    public function test_rendered_form_actions_and_report_links_use_slugs(): void
    {
        [$course, $laboran] = $this->fixture();
        $response = $this->actingAs($laboran)->get(route('courses.show', $course))->assertOk();
        $response->assertSee(route('courses.modules.edit', $course), false);
        $response->assertSee(route('attendance.report', $course), false);
        $response->assertDontSee('Buat Laprak Final');
        $this->assertFalse(Route::has('final-tasks.store'));
        $this->assertFalse(Route::has('meetings.store'));
        $this->get(route('attendance.report', $course))->assertOk()->assertSee(route('attendance.report.pdf', $course), false)->assertSee(route('attendance.report.excel', $course), false);
    }

    public function test_eight_modules_are_atomic_and_have_independent_student_submissions(): void
    {
        [$course, $laboran, $aslab, $dosen, $student] = $this->fixture();
        $course->meetings()->createMany(collect(range(1, 8))->map(fn ($number) => [
            'meeting_number' => $number,
            'title' => 'Modul '.$number,
        ])->all());
        $modules = $course->meetings()->get()->map(fn ($meeting) => [
            'id' => $meeting->id,
            'title' => 'Materi '.$meeting->meeting_number,
            'module_drive_link' => "https://drive.google.com/file/d/material-{$meeting->meeting_number}/view",
            'deadline' => now()->addDay()->toDateTimeString(),
            'is_published' => 1,
        ])->all();
        $invalid = $modules;
        $invalid[7]['module_drive_link'] = 'https://example.test/not-drive';
        $this->actingAs($aslab)->put(route('courses.modules.update', $course), ['modules' => $invalid])->assertSessionHasErrors('modules.7.module_drive_link');
        $this->assertSame(0, $course->meetings()->whereNotNull('published_at')->count());
        $this->put(route('courses.modules.update', $course), ['modules' => $modules])->assertRedirect(route('courses.show', $course));
        $this->assertDatabaseCount('meetings', 8);
        $page = $this->actingAs($student)->get(route('courses.show', $course))->assertOk();
        for ($i = 1; $i <= 8; $i++) {
            $page->assertSee('data-module="'.$i.'"', false);
        }
        $meeting = $course->meetings()->where('meeting_number', 3)->firstOrFail();
        $this->post(route('submissions.store', $meeting), ['submission_link' => 'https://drive.google.com/file/d/report-3/view'])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('submissions', 1);
        $this->assertDatabaseHas('submissions', ['meeting_id' => $meeting->id, 'student_id' => $student->id]);
        $this->get(route('submissions.my-index'))->assertSee(route('mahasiswa.submissions.manage', $meeting), false);
    }

    public function test_course_creation_requires_a_module_count_between_one_and_sixteen(): void
    {
        [$existingCourse, $laboran, $aslab, $dosen] = $this->fixture();
        $payload = [
            'course_name' => 'Basis Data',
            'class_group' => 'B',
            'target_semester' => 2,
            'laboran_id' => $laboran->id,
            'dosen_id' => $dosen->id,
            'aslab_id' => $aslab->id,
        ];

        $this->actingAs($laboran)->post(route('courses.store'), $payload)
            ->assertSessionHasErrors('module_count');
        $this->assertDatabaseMissing('courses', ['course_name' => 'Basis Data']);

        $payload['module_count'] = 17;
        $this->post(route('courses.store'), $payload)
            ->assertSessionHasErrors('module_count');
        $this->assertDatabaseMissing('courses', ['course_name' => 'Basis Data']);

        $payload['module_count'] = 7;
        $response = $this->post(route('courses.store'), $payload)->assertSessionHasNoErrors();
        $course = Course::where('course_name', 'Basis Data')->firstOrFail();
        $response->assertRedirect(route('courses.show', $course));
        $this->assertSame(7, $course->meetings()->count());
        $this->assertSame(0, $course->meetings()->whereNotNull('published_at')->count());
        $this->actingAs($laboran)->get(route('courses.show', $course))->assertSee('Coming Soon');
        $this->assertDatabaseCount('courses', 2);
    }

    public function test_modules_can_be_added_and_opened_without_material_while_identity_stays_fixed(): void
    {
        [$course, $laboran, $aslab, $dosen, $student] = $this->fixture();
        $first = $course->meetings()->create(['meeting_number' => 1, 'title' => 'Modul 1']);
        $second = $course->meetings()->create(['meeting_number' => 2, 'title' => 'Modul 2']);

        $this->actingAs($student)->get(route('mahasiswa.submissions.manage', $first))->assertNotFound();
        $this->get(route('courses.show', $course))->assertOk()->assertSee('data-module-status="coming-soon"', false);

        $outsider = User::factory()->create(['role' => 'Aslab']);
        $this->actingAs($outsider)->get(route('courses.modules.edit', $course))->assertForbidden();

        $this->actingAs($aslab)->put(route('courses.modules.update', $course), ['modules' => [
            ['id' => $first->id, 'title' => 'Dasar Pemrograman', 'module_drive_link' => '', 'is_published' => 1],
            ['id' => $second->id, 'title' => 'Modul 2', 'module_drive_link' => 'https://drive.google.com/file/d/material-two/view', 'is_published' => 0],
            ['title' => 'Praktik Controller', 'module_drive_link' => '', 'is_published' => 1],
        ]])->assertRedirect(route('courses.show', $course));

        $this->assertDatabaseHas('meetings', ['id' => $first->id, 'meeting_number' => 1, 'title' => 'Dasar Pemrograman']);
        $this->assertDatabaseHas('meetings', ['id' => $second->id, 'meeting_number' => 2, 'title' => 'Modul 2', 'module_drive_link' => 'https://drive.google.com/file/d/material-two/view']);
        $this->assertDatabaseHas('meetings', ['course_id' => $course->id, 'meeting_number' => 3, 'title' => 'Praktik Controller', 'module_drive_link' => null]);
        $third = $course->meetings()->where('meeting_number', 3)->firstOrFail();
        $this->assertNotNull($first->fresh()->published_at);
        $this->assertNull($second->fresh()->published_at);
        $this->assertNotNull($third->published_at);

        $this->actingAs($student)->post(route('submissions.store', $first), [
            'submission_link' => 'https://drive.google.com/file/d/report-one/view',
        ])->assertRedirect(route('courses.show', $course));
        $this->post(route('submissions.store', $second), [
            'submission_link' => 'https://drive.google.com/file/d/report-two/view',
        ])->assertNotFound();

        $this->assertDatabaseHas('submissions', ['meeting_id' => $first->id, 'student_id' => $student->id]);
        $this->assertDatabaseMissing('submissions', ['meeting_id' => $second->id, 'student_id' => $student->id]);

        $this->actingAs($student)->post(route('submissions.store', $third), [
            'submission_link' => 'https://drive.google.com/file/d/report-three/view',
        ])->assertRedirect(route('courses.show', $course));
        $this->actingAs($aslab)->get(route('submissions.index', $third))
            ->assertOk()
            ->assertSee('https://drive.google.com/file/d/report-three/view', false)
            ->assertSee('Preview')
            ->assertSee('Buka Drive');
    }

    public function test_rejected_submission_is_visible_and_can_be_replaced_after_deadline(): void
    {
        [$course, $laboran, $aslab, $dosen, $student] = $this->fixture();
        $meeting = Meeting::create([
            'course_id' => $course->id,
            'meeting_number' => 1,
            'title' => 'Modul Jaringan',
            'module_drive_link' => 'https://drive.google.com/file/d/material/view',
            'deadline' => now()->addDay(),
            'published_at' => now(),
        ]);

        $this->actingAs($student)->post(route('submissions.store', $meeting), [
            'submission_link' => 'https://drive.google.com/file/d/report-v1/view',
        ])->assertSessionHasNoErrors();
        $submission = Submission::sole();

        $this->actingAs($aslab)->post(route('submissions.approve', $submission), [
            'status' => 'DITOLAK',
            'document_version' => 1,
        ])->assertSessionHasErrors('feedback');

        $this->post(route('submissions.approve', $submission), [
            'status' => 'DITOLAK',
            'document_version' => 1,
            'feedback' => 'File bukan laporan modul yang diminta.',
        ])->assertRedirect();

        $this->assertSame('Ditolak', $submission->fresh()->aslab_status);
        $this->assertSame('Ditolak', $submission->fresh()->studentStatus());
        $this->assertDatabaseHas('submission_histories', [
            'submission_id' => $submission->id,
            'action_type' => 'Rejected',
            'feedback' => 'File bukan laporan modul yang diminta.',
        ]);
        $this->actingAs($student)->get(route('courses.show', $course))
            ->assertOk()
            ->assertSee('Ditolak')
            ->assertSee('Upload perbaikan');

        $meeting->update(['deadline' => now()->subDay()]);
        $this->put(route('submissions.update', $submission), [
            'submission_link' => 'https://drive.google.com/file/d/report-v2/view',
            'document_version' => 1,
        ])->assertSessionHasNoErrors();

        $submission->refresh();
        $this->assertSame(2, $submission->document_version);
        $this->assertSame('Pending', $submission->aslab_status);
        $this->assertSame('Menunggu pemeriksaan', $submission->studentStatus());
    }

    public function test_new_versions_reset_all_approvals_and_stale_reviews_are_rejected(): void
    {
        [$course, $laboran, $aslab, $dosen, $student] = $this->fixture();
        foreach ([false, true] as $final) {
            $task = $final ? FinalTask::create(['course_id' => $course->id, 'description' => 'Final', 'deadline' => now()->addDay()]) : Meeting::create(['course_id' => $course->id, 'meeting_number' => 1, 'title' => 'Modul', 'module_drive_link' => 'https://drive.google.com/file/d/module/view', 'deadline' => now()->addDay(), 'published_at' => now()]);
            $this->actingAs($student)->post(route($final ? 'final-tasks.submit' : 'submissions.store', $task), ['submission_link' => 'https://drive.google.com/file/d/v1/view'])->assertSessionHasNoErrors();
            $submission = Submission::where('is_final', $final)->sole();
            $review = route($final ? 'final-tasks.approve' : 'submissions.approve', $submission);
            $method = $final ? 'patch' : 'post';
            $this->actingAs($aslab)->{$method}($review, ['status' => 'ACC', 'document_version' => 1, 'score' => 80])->assertRedirect();
            if ($final) {
                $this->actingAs($laboran)->{$method}($review, ['status' => 'ACC', 'document_version' => 1, 'score' => 80])->assertRedirect();
            }
            $this->actingAs($student)->put(route($final ? 'final-tasks.update' : 'submissions.update', $submission), ['submission_link' => 'https://drive.google.com/file/d/v2/view', 'document_version' => 1])->assertSessionHasNoErrors();
            $submission->refresh();
            $this->assertSame(2, $submission->document_version);
            $this->assertSame('Pending', $submission->aslab_status);
            $this->assertSame('Pending', $submission->laboran_status);
            $this->assertNull($submission->aslab_acc_at);
            $this->assertNull($submission->laboran_acc_at);
            $this->actingAs($aslab)->{$method}($review, ['status' => 'ACC', 'document_version' => 1, 'score' => 80])->assertStatus(409);
            $this->actingAs($laboran)->{$method}($review, ['status' => 'ACC', 'document_version' => 2, 'score' => 80])->assertStatus(422);
            $this->actingAs($aslab)->{$method}($review, ['status' => 'REVISI', 'document_version' => 2, $final ? 'notes' : 'feedback' => 'Perbaiki'])->assertRedirect();
            $task->update(['deadline' => now()->subDay()]);
            $this->actingAs($student)->put(route($final ? 'final-tasks.update' : 'submissions.update', $submission), ['submission_link' => 'https://drive.google.com/file/d/v3/view', 'document_version' => 2])->assertSessionHasNoErrors()->assertRedirect(route('courses.show', $course));
            $this->assertSame(3, $submission->fresh()->document_version);
            $this->assertSame(1, $submission->histories()->where('action_type', 'ACC')->first()->document_version);
        }
    }

    public function test_search_only_approved_students_and_exact_nim_is_first(): void
    {
        [$course, $laboran] = $this->fixture();
        $exact = User::factory()->create(['id' => '000123', 'name' => 'Zul']);
        User::factory()->create(['id' => '0001234', 'name' => 'Amin']);
        User::factory()->create(['id' => '0001235', 'approved_at' => null]);
        $this->actingAs($laboran)->getJson(route('courses.search-students', $course).'?q=000123')->assertOk()->assertJsonPath('data.0.id', $exact->id)->assertJsonCount(2, 'data');
        $this->getJson(route('courses.search-students', $course).'?q=%25%25%25')->assertOk()->assertJsonCount(0, 'data');
        $this->post(route('courses.add-student', $course), ['student_id' => '0001235'])->assertSessionHasErrors('student_id');
    }

    public function test_duplicate_create_stale_edit_and_completed_reports_cannot_overwrite_documents(): void
    {
        [$course, $laboran, $aslab, $dosen, $student] = $this->fixture();
        $meeting = Meeting::create(['course_id' => $course->id, 'meeting_number' => 1, 'title' => 'Modul', 'module_drive_link' => 'https://drive.google.com/file/d/module/view', 'deadline' => now()->addDay(), 'published_at' => now()]);
        $link = 'https://drive.google.com/file/d/report-1/view';
        $this->actingAs($student)->post(route('submissions.store', $meeting), ['submission_link' => $link])->assertRedirect();
        $submission = Submission::sole();
        $this->post(route('submissions.store', $meeting), ['submission_link' => $link])->assertStatus(409);
        $this->assertDatabaseCount('submissions', 1);
        $this->assertDatabaseCount('submission_histories', 1);
        $this->put(route('submissions.update', $submission), ['submission_link' => 'https://drive.google.com/file/d/report-2/view', 'document_version' => 1])->assertSessionHasNoErrors();
        $this->put(route('submissions.update', $submission), ['submission_link' => $link, 'document_version' => 1])->assertStatus(409);
        $this->actingAs($aslab)->post(route('submissions.approve', $submission), ['status' => 'ACC', 'document_version' => 2, 'score' => 80])->assertRedirect();
        $this->actingAs($laboran)->post(route('submissions.approve', $submission), ['status' => 'ACC', 'document_version' => 2, 'score' => 90])->assertRedirect();
        $this->assertTrue($submission->fresh()->is_completed);
        $this->actingAs($student)->put(route('submissions.update', $submission), ['submission_link' => $link, 'document_version' => 2])->assertForbidden();
        $this->actingAs($aslab)->post(route('submissions.approve', $submission), ['status' => 'REVISI', 'document_version' => 2, 'feedback' => 'Ubah lagi'])->assertStatus(409);
        $this->assertSame('https://drive.google.com/file/d/report-2/view', $submission->fresh()->submission_link);
    }

    public function test_new_staff_pages_render_and_invalid_drive_links_cannot_be_submitted(): void
    {
        [$course, $laboran, $aslab, $dosen, $student] = $this->fixture();
        $course->meetings()->create(['meeting_number' => 1, 'title' => 'Modul 1']);
        $this->actingAs($laboran)->get(route('courses.modules.edit', $course))->assertOk()->assertSee('Simpan semua modul');
        $this->get(route('courses.staff.edit', $course))->assertOk();
        $meeting = $course->meetings()->firstOrFail();
        $meeting->update([
            'module_drive_link' => 'https://drive.google.com/file/d/material/view',
            'published_at' => now(),
        ]);
        foreach (['https://evil.test/file/d/id/view', 'https://drive.google.com/drive/folders/id'] as $link) {
            $this->actingAs($student)->post(route('submissions.store', $meeting), ['submission_link' => $link])->assertSessionHasErrors('submission_link');
        }
        $this->assertDatabaseCount('submissions', 0);
    }

    public function test_archives_are_read_only_and_staff_assignment_is_audited(): void
    {
        [$course, $laboran, $aslab, $dosen, $student] = $this->fixture();
        $replacement = User::factory()->create(['role' => 'Aslab']);
        $this->actingAs($laboran)->put(route('courses.staff.update', $course), ['aslab_id' => $replacement->id, 'dosen_id' => $dosen->id])->assertRedirect();
        $this->assertDatabaseHas('course_staff_histories', ['course_id' => $course->id, 'previous_aslab_id' => $aslab->id, 'aslab_id' => $replacement->id]);
        $meeting = Meeting::create(['course_id' => $course->id, 'meeting_number' => 1, 'title' => 'Modul', 'module_drive_link' => 'https://drive.google.com/file/d/module/view', 'published_at' => now()]);
        $this->post(route('attendance.store', $meeting), ['attendances' => [$student->id => 'TK']])->assertRedirect();
        $this->actingAs($student)->get(route('courses.show', $course))->assertSee('Tanpa Keterangan');
        $course->semester->update(['is_active' => false]);
        $this->post(route('submissions.store', $meeting), ['submission_link' => 'https://drive.google.com/file/d/v1/view'])->assertForbidden();
        $this->get(route('courses.show', $course))->assertOk();
        $this->assertFalse(Route::has('meetings.store'));
    }
}
