<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Meeting;
use App\Models\Semester;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GradeWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function fixture(): array
    {
        $laboran = User::factory()->create(['role' => 'Laboran']);
        $aslab = User::factory()->create(['role' => 'Aslab']);
        $dosen = User::factory()->create(['role' => 'Dosen']);
        $student = User::factory()->create();
        $semester = Semester::create(['name' => 'Aktif', 'is_active' => true]);
        $course = Course::create([
            'semester_id' => $semester->id,
            'course_name' => 'Pemrograman',
            'class_group' => 'A',
            'target_semester' => 1,
            'dosen_id' => $dosen->id,
            'aslab_id' => $aslab->id,
            'laboran_id' => $laboran->id,
            'enrollment_code' => 'GRADE123',
        ]);
        $course->students()->attach($student);

        return [$course, $dosen, $aslab, $laboran, $student];
    }

    public function test_dosen_can_record_exam_grades_and_see_the_aggregated_practicum_grade(): void
    {
        [$course, $dosen, $aslab, $laboran, $student] = $this->fixture();
        foreach ([[80, 90], [100, 90]] as $index => [$aslabScore, $laboranScore]) {
            $meeting = $course->meetings()->create([
                'meeting_number' => $index + 1,
                'title' => 'Modul '.($index + 1),
                'published_at' => now(),
            ]);
            Submission::create([
                'student_id' => $student->id,
                'meeting_id' => $meeting->id,
                'submission_link' => 'https://drive.google.com/file/d/report-'.$index.'/view',
                'aslab_status' => 'ACC',
                'laboran_status' => 'ACC',
                'is_completed' => true,
                'is_final' => false,
                'aslab_score' => $aslabScore,
                'laboran_score' => $laboranScore,
            ]);
        }

        $this->actingAs($dosen)->get(route('courses.grades.index', $course))
            ->assertOk()
            ->assertSee('Laporan Praktikum')
            ->assertSee('Rincian nilai modul');

        $this->put(route('courses.grades.update', [$course, $student]), ['uts_score' => 87, 'uas_score' => 75])
            ->assertRedirect(route('courses.grades.index', $course));

        $this->assertDatabaseHas('course_grades', [
            'course_id' => $course->id,
            'student_id' => $student->id,
            'uts_score' => 87,
            'uas_score' => 75,
        ]);
        $this->get(route('courses.grades.index', $course))
            ->assertOk()
            ->assertSee('90,00')
            ->assertSee('84,00')
            ->assertSee('B');

        $outsider = User::factory()->create(['role' => 'Dosen']);
        $this->actingAs($outsider)->get(route('courses.grades.index', $course))->assertForbidden();
    }

    public function test_aslab_and_laboran_can_enter_module_scores_after_both_approvals(): void
    {
        [$course, $dosen, $aslab, $laboran, $student] = $this->fixture();
        $meeting = $course->meetings()->create(['meeting_number' => 1, 'title' => 'Modul 1', 'published_at' => now()]);
        $submission = Submission::create([
            'student_id' => $student->id,
            'meeting_id' => $meeting->id,
            'submission_link' => 'https://drive.google.com/file/d/report/view',
            'aslab_status' => 'ACC',
            'laboran_status' => 'ACC',
            'is_completed' => false,
            'is_final' => false,
        ]);

        $this->actingAs($aslab)->put(route('submissions.score', $submission), ['score' => 88])->assertStatus(422);
        $submission->update(['is_completed' => true]);
        $this->put(route('submissions.score', $submission), ['score' => 88])->assertRedirect();
        $this->actingAs($laboran)->put(route('submissions.score', $submission), ['score' => 92])->assertRedirect();
        $this->assertDatabaseHas('submissions', ['id' => $submission->id, 'aslab_score' => 88, 'laboran_score' => 92]);

        $outsider = User::factory()->create(['role' => 'Aslab']);
        $this->actingAs($outsider)->put(route('submissions.score', $submission), ['score' => 60])->assertForbidden();
    }

    public function test_module_acc_saves_the_score_and_disables_review_for_that_role(): void
    {
        [$course, $dosen, $aslab, $laboran, $student] = $this->fixture();
        $first = $course->meetings()->create(['meeting_number' => 1, 'title' => 'Modul 1', 'published_at' => now()]);
        $course->meetings()->create(['meeting_number' => 2, 'title' => 'Modul 2', 'published_at' => now()]);
        $submission = Submission::create([
            'student_id' => $student->id,
            'meeting_id' => $first->id,
            'submission_link' => 'https://drive.google.com/file/d/report/view',
            'aslab_status' => 'Pending',
            'laboran_status' => 'Pending',
            'is_completed' => false,
            'is_final' => false,
        ]);

        $this->actingAs($aslab)->get(route('submissions.index', $first))
            ->assertOk()
            ->assertSee('Status Modul 1')
            ->assertSee('Status Modul 2')
            ->assertSee(route('submissions.handler', $submission), false);
        $this->get(route('submissions.handler', $submission))
            ->assertOk()
            ->assertSee('name="score"', false)
            ->assertSee(':disabled="!scoreEnabled"', false);

        $this->post(route('submissions.approve', $submission), ['status' => 'ACC', 'document_version' => 1])
            ->assertSessionHasErrors('score');
        $this->post(route('submissions.approve', $submission), ['status' => 'ACC', 'document_version' => 1, 'score' => 88])
            ->assertRedirect();
        $this->assertDatabaseHas('submissions', ['id' => $submission->id, 'aslab_status' => 'ACC', 'aslab_score' => 88]);
        $this->post(route('submissions.approve', $submission), ['status' => 'ACC', 'document_version' => 1, 'score' => 90])
            ->assertStatus(409);
        $this->get(route('submissions.index', $first))
            ->assertOk()
            ->assertSee('Sudah ACC')
            ->assertDontSee(route('submissions.handler', $submission), false)
            ->assertSee('ring-1 ring-emerald-200', false);

        $this->actingAs($laboran)->get(route('submissions.index', $first))
            ->assertOk()
            ->assertSee(route('submissions.handler', $submission), false);
        $this->post(route('submissions.approve', $submission), ['status' => 'ACC', 'document_version' => 1, 'score' => 92])
            ->assertRedirect();
        $this->assertDatabaseHas('submissions', [
            'id' => $submission->id,
            'laboran_status' => 'ACC',
            'laboran_score' => 92,
            'is_completed' => true,
        ]);
    }

    public function test_dosen_cannot_record_exam_grades_before_all_module_scores_are_ready(): void
    {
        [$course, $dosen, $aslab, $laboran, $student] = $this->fixture();
        $course->meetings()->create(['meeting_number' => 1, 'title' => 'Modul 1', 'published_at' => now()]);

        $this->actingAs($dosen)
            ->put(route('courses.grades.update', [$course, $student]), ['uts_score' => 80, 'uas_score' => 90])
            ->assertStatus(422);
        $this->assertDatabaseCount('course_grades', 0);
    }
}
