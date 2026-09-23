<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Semester;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PerformanceRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_course_page_uses_counts_without_loading_submission_histories(): void
    {
        [$laboran, $dosen, $aslab, $semester] = $this->fixture();
        $course = $this->course($semester, $laboran, $dosen, $aslab, 'PERF0001');
        $meeting = $course->meetings()->create([
            'meeting_number' => 1,
            'title' => 'Modul performa',
            'module_drive_link' => 'https://drive.google.com/file/d/performance/view',
            'published_at' => now(),
        ]);
        $student = User::factory()->create(['role' => 'Mahasiswa']);
        $submission = Submission::query()->create([
            'student_id' => $student->id,
            'meeting_id' => $meeting->id,
            'submission_link' => 'https://drive.google.com/file/d/report/view',
            'is_final' => false,
            'first_upload_at' => now(),
            'last_upload_at' => now(),
        ]);
        $submission->histories()->create([
            'drive_link' => $submission->submission_link,
            'iteration' => 1,
            'action_type' => 'Upload',
        ]);

        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->actingAs($laboran)->get(route('courses.show', $course))->assertOk()->assertSee('Laporan (1)');
        $queries = collect(DB::getQueryLog())->pluck('query');

        $this->assertLessThanOrEqual(8, $queries->count(), $queries->implode(PHP_EOL));
        $this->assertFalse($queries->contains(fn (string $query) => str_contains(strtolower($query), 'from "submission_histories"')));
        $this->assertFalse(
            $queries->contains(fn (string $query) => str_starts_with(strtolower($query), 'select * from "attendances"')),
            $queries->implode(PHP_EOL)
        );
    }

    public function test_course_list_is_paginated_to_keep_the_page_lightweight(): void
    {
        [$laboran, $dosen, $aslab, $semester] = $this->fixture();
        foreach (range(1, 13) as $number) {
            $this->course($semester, $laboran, $dosen, $aslab, 'PAGE'.str_pad((string) $number, 4, '0', STR_PAD_LEFT), "Kelas {$number}");
        }

        $response = $this->actingAs($laboran)->get(route('courses.index'));
        $response->assertOk();
        $courses = $response->viewData('courses');

        $this->assertInstanceOf(LengthAwarePaginator::class, $courses);
        $this->assertSame(12, $courses->perPage());
        $this->assertSame(13, $courses->total());
    }

    public function test_user_management_is_paginated_and_keeps_filters_available_on_other_pages(): void
    {
        $laboran = User::factory()->create(['role' => 'Laboran']);
        User::factory()->count(12)->create([
            'role' => 'Mahasiswa',
            'name' => 'Paged User',
        ]);

        $response = $this->actingAs($laboran)->get(route('users.index', [
            'roles' => ['Mahasiswa'],
            'search' => 'Paged User',
            'limit' => '10',
        ]));
        $response->assertOk()->assertSee('aria-label="Navigasi halaman"', false);

        $users = $response->viewData('users');
        $this->assertInstanceOf(LengthAwarePaginator::class, $users);
        $this->assertSame(10, $users->perPage());
        $this->assertSame(12, $users->total());

        parse_str((string) parse_url($users->url(2), PHP_URL_QUERY), $pageTwoQuery);
        $this->assertSame(['Mahasiswa'], $pageTwoQuery['roles']);
        $this->assertSame('Paged User', $pageTwoQuery['search']);
        $this->assertSame('10', $pageTwoQuery['limit']);

        $hundredPerPage = $this->actingAs($laboran)->get(route('users.index', [
            'roles' => ['Mahasiswa'],
            'search' => 'Paged User',
            'limit' => '100',
        ]));
        $hundredPerPage->assertOk()->assertDontSee('aria-label="Navigasi halaman"', false);
        $largerPage = $hundredPerPage->viewData('users');
        $this->assertInstanceOf(LengthAwarePaginator::class, $largerPage);
        $this->assertSame(100, $largerPage->perPage());
        $this->assertSame(12, $largerPage->total());

        $this->actingAs($laboran)->get(route('users.index', ['limit' => 'all']))
            ->assertSessionHasErrors('limit');
    }

    private function fixture(): array
    {
        return [
            User::factory()->create(['role' => 'Laboran']),
            User::factory()->create(['role' => 'Dosen']),
            User::factory()->create(['role' => 'Aslab']),
            Semester::query()->create(['name' => 'Ganjil 2026/2027', 'is_active' => true]),
        ];
    }

    private function course(Semester $semester, User $laboran, User $dosen, User $aslab, string $code, string $name = 'Kelas Performa'): Course
    {
        return Course::query()->create([
            'semester_id' => $semester->id,
            'course_name' => $name,
            'class_group' => $code,
            'target_semester' => 1,
            'dosen_id' => $dosen->id,
            'laboran_id' => $laboran->id,
            'aslab_id' => $aslab->id,
            'enrollment_code' => $code,
        ]);
    }
}
