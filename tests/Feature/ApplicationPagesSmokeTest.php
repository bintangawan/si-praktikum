<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\FinalTask;
use App\Models\Meeting;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplicationPagesSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_pages_render_without_server_errors(): void
    {
        foreach (['/', '/login', '/register', '/forgot-password'] as $url) {
            $response = $this->get($url);

            $this->assertSame(200, $response->getStatusCode(), "Halaman publik gagal: {$url}");
        }
    }

    public function test_all_laboran_pages_render_without_server_errors(): void
    {
        $fixture = $this->academicFixture();

        $this->assertPagesAreOk($fixture['laboran'], [
            'dashboard' => route('dashboard'),
            'profile' => route('profile.edit'),
            'courses' => route('courses.index'),
            'create course' => route('courses.create'),
            'edit course' => route('courses.edit', $fixture['course']),
            'course detail' => route('courses.show', $fixture['course']),
            'archives' => route('archives.index'),
            'tutorials' => route('tutorials.index'),
            'users' => route('users.index'),
            'import users' => route('user.import.form'),
            'semesters' => route('semesters.index'),
            'pending submissions' => route('submissions.pending'),
            'attendance input' => route('attendance.index', $fixture['meeting']),
            'attendance report' => route('attendance.report', $fixture['course']),
            'students' => route('courses.students', $fixture['course']),
            'meeting submissions' => route('submissions.index', $fixture['meeting']),
            'final submissions' => route('final-tasks.index', $fixture['finalTask']),
        ]);
    }

    public function test_all_aslab_and_dosen_pages_render_without_server_errors(): void
    {
        $fixture = $this->academicFixture();
        $staffPages = [
            'dashboard' => route('dashboard'),
            'profile' => route('profile.edit'),
            'courses' => route('courses.index'),
            'course detail' => route('courses.show', $fixture['course']),
            'archives' => route('archives.index'),
            'tutorials' => route('tutorials.index'),
            'pending submissions' => route('submissions.pending'),
            'attendance input' => route('attendance.index', $fixture['meeting']),
            'attendance report' => route('attendance.report', $fixture['course']),
            'students' => route('courses.students', $fixture['course']),
            'meeting submissions' => route('submissions.index', $fixture['meeting']),
            'final submissions' => route('final-tasks.index', $fixture['finalTask']),
        ];

        $this->assertPagesAreOk($fixture['aslab'], $staffPages);
        $this->assertPagesAreOk($fixture['dosen'], $staffPages);
    }

    public function test_all_student_pages_render_without_server_errors(): void
    {
        $fixture = $this->academicFixture();

        $this->assertPagesAreOk($fixture['student'], [
            'dashboard' => route('dashboard'),
            'profile' => route('profile.edit'),
            'courses' => route('courses.index'),
            'course detail' => route('courses.show', $fixture['course']),
            'archives' => route('archives.index'),
            'tutorials' => route('tutorials.index'),
            'my submissions' => route('submissions.my-index'),
            'meeting submission' => route('mahasiswa.submissions.manage', $fixture['meeting']),
            'final submission' => route('mahasiswa.final-tasks.manage', $fixture['finalTask']),
        ]);
    }

    public function test_report_exports_and_practicum_card_can_be_downloaded(): void
    {
        $fixture = $this->academicFixture();

        $this->actingAs($fixture['laboran'])
            ->get(route('attendance.report.pdf', $fixture['course']))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->actingAs($fixture['laboran'])
            ->get(route('attendance.report.excel', $fixture['course']))
            ->assertOk();

        $this->actingAs($fixture['student'])
            ->get(route('courses.print-card', $fixture['course']))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    /** @param array<string, string> $pages */
    private function assertPagesAreOk(User $user, array $pages): void
    {
        foreach ($pages as $name => $url) {
            $response = $this->actingAs($user)->get($url);

            $this->assertSame(200, $response->getStatusCode(), "Halaman {$name} gagal untuk role {$user->role}: {$url}");
        }
    }

    /** @return array{laboran: User, aslab: User, dosen: User, student: User, course: Course, meeting: Meeting, finalTask: FinalTask} */
    private function academicFixture(): array
    {
        $laboran = User::factory()->create(['role' => 'Laboran']);
        $aslab = User::factory()->create(['role' => 'Aslab']);
        $dosen = User::factory()->create(['role' => 'Dosen']);
        $student = User::factory()->create(['role' => 'Mahasiswa']);
        $semester = Semester::query()->create(['name' => 'Ganjil 2026/2027', 'is_active' => true]);
        $course = Course::query()->create([
            'semester_id' => $semester->id,
            'course_name' => 'Pengujian Sistem',
            'class_group' => 'A',
            'target_semester' => 5,
            'dosen_id' => $dosen->id,
            'laboran_id' => $laboran->id,
            'aslab_id' => $aslab->id,
            'enrollment_code' => 'SMOKE123',
        ]);
        $course->students()->attach($student);
        $meeting = Meeting::query()->create([
            'course_id' => $course->id,
            'meeting_number' => 1,
            'title' => 'Pengenalan Praktikum',
            'description' => 'Pertemuan pengenalan.',
            'module_drive_link' => 'https://drive.google.com/file/d/smoke-module/view',
            'deadline' => now()->addDay(),
            'published_at' => now(),
        ]);
        $finalTask = FinalTask::query()->create([
            'course_id' => $course->id,
            'description' => 'Laporan akhir praktikum.',
            'deadline' => now()->addWeek(),
        ]);

        return compact('laboran', 'aslab', 'dosen', 'student', 'course', 'meeting', 'finalTask');
    }
}
