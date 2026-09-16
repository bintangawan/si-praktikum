<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Course;
use App\Models\FinalTask;
use App\Models\Meeting;
use App\Models\Semester;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkflowAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_login_user_is_forced_to_change_password(): void
    {
        $user = User::factory()->create([
            'id' => '10001',
            'email' => 'first@example.test',
            'password' => 'initial-password',
            'is_first_login' => true,
        ]);

        $this->post('/login', ['email' => $user->email, 'password' => 'initial-password'])
            ->assertRedirect(route('first.login.form'));

        $this->actingAs($user)->get(route('courses.index'))
            ->assertRedirect(route('first.login.form'));
    }

    public function test_student_cannot_open_laboran_administration(): void
    {
        $student = User::factory()->create(['role' => 'Mahasiswa']);

        $this->actingAs($student)->get(route('users.index'))->assertForbidden();
        $this->actingAs($student)->post(route('semesters.store'), ['name' => 'Ganjil 2026/2027'])->assertForbidden();
    }

    public function test_course_can_only_be_opened_by_a_participant_or_assigned_staff(): void
    {
        [$course, $student] = $this->courseFixture();
        $outsider = User::factory()->create(['role' => 'Mahasiswa']);

        $this->actingAs($student)->get(route('courses.show', $course))->assertOk();
        $this->actingAs($outsider)->get(route('courses.show', $course))->assertForbidden();
    }

    public function test_weekly_submission_and_approval_keep_canonical_status_and_history(): void
    {
        [$course, $student, $aslab] = $this->courseFixture();
        $meeting = Meeting::query()->create([
            'course_id' => $course->id,
            'meeting_number' => 1,
            'title' => 'Pengenalan',
            'deadline' => now()->addDay(),
        ]);

        $this->actingAs($student)->post(route('submissions.store', $meeting), [
            'submission_link' => 'https://example.test/laporan/1',
            'notes' => 'Versi awal',
        ])->assertRedirect(route('courses.show', $course));

        $submission = Submission::query()->sole();
        $this->assertSame('Pending', $submission->aslab_status);
        $this->assertDatabaseHas('submission_histories', [
            'submission_id' => $submission->id,
            'iteration' => 1,
            'action_type' => 'Upload',
        ]);

        $this->actingAs($aslab)->post(route('submissions.approve', $submission), ['status' => 'ACC'])
            ->assertRedirect(route('submissions.index', $meeting));

        $this->assertDatabaseHas('submissions', ['id' => $submission->id, 'aslab_status' => 'ACC']);
        $this->assertDatabaseHas('submission_histories', [
            'submission_id' => $submission->id,
            'iteration' => 2,
            'action_type' => 'ACC',
            'reviewed_by' => $aslab->id,
        ]);
    }

    public function test_final_submission_follows_aslab_laboran_dosen_waterfall(): void
    {
        [$course, $student, $aslab, $dosen, $laboran] = $this->courseFixture();
        $finalTask = FinalTask::query()->create([
            'course_id' => $course->id,
            'description' => 'Laporan akhir praktikum',
            'deadline' => now()->addDay(),
        ]);

        $this->actingAs($student)->post(route('final-tasks.submit', $finalTask), [
            'submission_link' => 'https://example.test/laporan/final',
        ])->assertRedirect(route('courses.show', $course));

        $submission = Submission::query()->sole();
        $this->actingAs($laboran)->patch(route('final-tasks.approve', $submission), ['status' => 'ACC'])
            ->assertStatus(422);
        $this->actingAs($aslab)->patch(route('final-tasks.approve', $submission), ['status' => 'ACC'])
            ->assertRedirect();
        $this->actingAs($laboran)->patch(route('final-tasks.approve', $submission), ['status' => 'ACC'])
            ->assertRedirect();
        $this->actingAs($dosen)->patch(route('final-tasks.approve', $submission), ['status' => 'ACC'])
            ->assertRedirect();

        $submission->refresh();
        $this->assertSame('ACC', $submission->aslab_status);
        $this->assertSame('ACC', $submission->laboran_status);
        $this->assertSame('ACC', $submission->dosen_status);
        $this->assertTrue($submission->is_completed);
        $this->assertCount(4, $submission->histories);
    }

    public function test_pending_page_renders_final_submission_with_the_correct_handler(): void
    {
        [$course, $student, $aslab, $dosen] = $this->courseFixture();
        $finalTask = FinalTask::query()->create([
            'course_id' => $course->id,
            'description' => 'Laporan akhir praktikum',
            'deadline' => now()->addDay(),
        ]);
        $submission = Submission::query()->create([
            'student_id' => $student->id,
            'final_task_id' => $finalTask->id,
            'submission_link' => 'https://example.test/laporan/final',
            'is_final' => true,
            'aslab_status' => 'ACC',
            'laboran_status' => 'ACC',
            'dosen_status' => 'Pending',
            'last_upload_at' => now(),
        ]);

        $this->actingAs($dosen)->get(route('submissions.pending'))
            ->assertOk()
            ->assertSee('Laporan Final')
            ->assertSee(route('final-tasks.handler', $submission), false);
    }

    public function test_dosen_weekly_review_is_read_only(): void
    {
        [$course, $student, $aslab, $dosen] = $this->courseFixture();
        $meeting = Meeting::query()->create([
            'course_id' => $course->id,
            'meeting_number' => 1,
            'title' => 'Pengenalan',
        ]);
        $submission = Submission::query()->create([
            'student_id' => $student->id,
            'meeting_id' => $meeting->id,
            'submission_link' => 'https://example.test/laporan/1',
            'is_final' => false,
        ]);

        $this->actingAs($dosen)->get(route('submissions.handler', $submission))
            ->assertOk()
            ->assertSee('akses baca')
            ->assertDontSee('Berikan ACC');
    }

    public function test_student_course_page_does_not_offer_staff_only_participant_link(): void
    {
        [$course, $student] = $this->courseFixture();

        $this->actingAs($student)->get(route('courses.show', $course))
            ->assertOk()
            ->assertDontSee(route('courses.students', $course), false);
    }

    public function test_attendance_percentage_only_counts_conducted_meetings(): void
    {
        [$course, $student, $aslab, $dosen, $laboran] = $this->courseFixture();
        $conducted = Meeting::query()->create([
            'course_id' => $course->id,
            'meeting_number' => 1,
            'title' => 'Sudah Dilaksanakan',
        ]);
        Meeting::query()->create([
            'course_id' => $course->id,
            'meeting_number' => 2,
            'title' => 'Belum Dilaksanakan',
        ]);
        Attendance::query()->create([
            'meeting_id' => $conducted->id,
            'student_id' => $student->id,
            'status' => 'Hadir',
            'attendance_date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($laboran)->get(route('attendance.report', $course));
        $response->assertOk();
        $row = $response->viewData('report')->first();

        $this->assertSame(1, $row->total_pertemuan);
        $this->assertSame(100, $row->percentage);
    }

    public function test_attendance_submission_must_cover_every_enrolled_student(): void
    {
        [$course, $student, $aslab, $dosen, $laboran] = $this->courseFixture();
        $secondStudent = User::factory()->create(['role' => 'Mahasiswa']);
        $course->students()->attach($secondStudent);
        $meeting = Meeting::query()->create([
            'course_id' => $course->id,
            'meeting_number' => 1,
            'title' => 'Pengenalan',
        ]);

        $this->actingAs($laboran)->post(route('attendance.store', $meeting), [
            'attendances' => [$student->id => 'Hadir'],
        ])->assertStatus(422);

        $this->assertDatabaseCount('attendances', 0);
    }

    /** @return array{Course, User, User, User, User} */
    private function courseFixture(): array
    {
        $semester = Semester::query()->create(['name' => 'Ganjil 2026/2027', 'is_active' => true]);
        $student = User::factory()->create(['role' => 'Mahasiswa']);
        $aslab = User::factory()->create(['role' => 'Aslab']);
        $dosen = User::factory()->create(['role' => 'Dosen']);
        $laboran = User::factory()->create(['role' => 'Laboran']);
        $course = Course::query()->create([
            'semester_id' => $semester->id,
            'course_name' => 'Sistem Informasi',
            'class_group' => 'A',
            'target_semester' => 1,
            'dosen_id' => $dosen->id,
            'laboran_id' => $laboran->id,
            'aslab_id' => $aslab->id,
            'enrollment_code' => 'TESTCODE',
        ]);
        $course->students()->attach($student);

        return [$course, $student, $aslab, $dosen, $laboran];
    }
}
