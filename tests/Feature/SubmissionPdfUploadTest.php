<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\FinalTask;
use App\Models\Meeting;
use App\Models\Semester;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SubmissionPdfUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_weekly_report_accepts_drive_link_without_writing_pdf(): void
    {
        Storage::fake('local');
        [$course, $student, $aslab] = $this->courseFixture();
        $meeting = Meeting::create(['course_id' => $course->id, 'meeting_number' => 1, 'title' => 'Modul 1', 'module_drive_link' => 'https://drive.google.com/file/d/module-1/view', 'deadline' => now()->addDay(), 'published_at' => now()]);
        $this->actingAs($student)->get(route('mahasiswa.submissions.manage', $meeting))
            ->assertOk()->assertSee('name="submission_link"', false)->assertDontSee('name="submission_file"', false);
        $this->post(route('submissions.store', $meeting), ['submission_link' => 'https://drive.google.com/file/d/report-v1/view'])
            ->assertRedirect(route('courses.show', $course));
        $submission = Submission::sole();
        $this->assertNull($submission->file_path);
        $this->assertSame([], Storage::disk('local')->allFiles());
        $this->actingAs($aslab)->get(route('submissions.handler', $submission))->assertOk()->assertSee('report-v1/preview');
    }

    public function test_both_endpoints_reject_file_upload_even_with_valid_link(): void
    {
        Storage::fake('local');
        [$course, $student] = $this->courseFixture();
        $meeting = Meeting::create(['course_id' => $course->id, 'meeting_number' => 1, 'title' => 'Modul 1', 'module_drive_link' => 'https://drive.google.com/file/d/module-1/view', 'published_at' => now()]);
        $finalTask = FinalTask::create(['course_id' => $course->id, 'description' => 'Final']);
        foreach ([route('submissions.store', $meeting), route('final-tasks.submit', $finalTask)] as $url) {
            $this->actingAs($student)->post($url, ['submission_file' => $this->pdf('laprak.pdf'), 'submission_link' => 'https://drive.google.com/file/d/report-v1/view'])
                ->assertSessionHasErrors('submission_file');
        }
        $this->assertDatabaseCount('submissions', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_existing_local_pdf_and_history_remain_readable_only_by_authorized_users(): void
    {
        Storage::fake('local');
        [$course, $student, $aslab] = $this->courseFixture();
        $meeting = Meeting::create(['course_id' => $course->id, 'meeting_number' => 1, 'title' => 'Modul 1']);
        Storage::disk('local')->put('submissions/legacy.pdf', '%PDF-1.4 legacy');
        $submission = Submission::create(['meeting_id' => $meeting->id, 'student_id' => $student->id, 'file_path' => 'submissions/legacy.pdf', 'original_filename' => 'legacy.pdf']);
        $history = $submission->histories()->create(['drive_link' => '', 'file_path' => $submission->file_path, 'iteration' => 1, 'action_type' => 'Upload']);
        foreach ([$student, $aslab] as $user) {
            $this->actingAs($user)->get(route('submissions.file', $submission))->assertOk()->assertHeader('content-type', 'application/pdf');
            $this->get(route('submission-histories.file', $history))->assertOk();
        }
        $outsider = User::factory()->create(['role' => 'Aslab']);
        $this->actingAs($outsider)->get(route('submissions.file', $submission))->assertForbidden();
        $this->get(route('submission-histories.file', $history))->assertForbidden();
    }

    private function pdf(string $name): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            $name,
            "%PDF-1.4\n1 0 obj<</Type/Catalog>>endobj\n%%EOF",
        );
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
