<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class CourseManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_laboran_can_create_a_course_and_immediately_see_it_in_their_course_list(): void
    {
        [$laboran, $dosen, $aslab, $semester] = $this->staffFixture();

        $response = $this->actingAs($laboran)->post(route('courses.store'), [
            'course_name' => '  Pemrograman   Web  ',
            'class_group' => ' ti-3a ',
            'target_semester' => 3,
            'dosen_id' => $dosen->id,
            'aslab_id' => $aslab->id,
        ]);

        $response->assertRedirect(route('courses.index'))
            ->assertSessionHas('success', 'Kelas berhasil dibuat.');

        $course = Course::query()->sole();
        $this->assertSame('Pemrograman Web', $course->course_name);
        $this->assertSame('TI-3A', $course->class_group);
        $this->assertSame($semester->id, $course->semester_id);
        $this->assertSame($laboran->id, $course->laboran_id);
        $this->assertNotEmpty($course->enrollment_code);

        $this->actingAs($laboran)->get(route('courses.index'))
            ->assertOk()
            ->assertSee('Pemrograman Web')
            ->assertSee('TI-3A')
            ->assertSee($course->enrollment_code)
            ->assertSee('Kelola Mahasiswa');
    }

    public function test_course_creation_rejects_users_that_do_not_have_the_required_roles(): void
    {
        [$laboran, $dosen, $aslab] = $this->staffFixture();
        $student = User::factory()->create(['role' => 'Mahasiswa']);

        $this->actingAs($laboran)->from(route('courses.create'))->post(route('courses.store'), [
            'course_name' => 'Basis Data',
            'class_group' => 'A',
            'target_semester' => 3,
            'dosen_id' => $student->id,
            'aslab_id' => $aslab->id,
        ])->assertRedirect(route('courses.create'))->assertSessionHasErrors('dosen_id');

        $this->assertDatabaseCount('courses', 0);

        $this->actingAs($student)->post(route('courses.store'), [
            'course_name' => 'Basis Data',
            'class_group' => 'A',
            'target_semester' => 3,
            'dosen_id' => $dosen->id,
            'aslab_id' => $aslab->id,
        ])->assertForbidden();
    }

    public function test_student_can_enroll_and_then_open_the_course(): void
    {
        [$laboran, $dosen, $aslab, $semester] = $this->staffFixture();
        $student = User::factory()->create(['role' => 'Mahasiswa']);
        $course = Course::query()->create([
            'semester_id' => $semester->id,
            'course_name' => 'Algoritma',
            'class_group' => 'A',
            'target_semester' => 1,
            'dosen_id' => $dosen->id,
            'laboran_id' => $laboran->id,
            'aslab_id' => $aslab->id,
            'enrollment_code' => 'JOIN1234',
        ]);

        $this->actingAs($student)->post(route('courses.enroll'), [
            'enrollment_code' => ' join1234 ',
        ])->assertRedirect(route('courses.index'));

        $this->assertDatabaseHas('course_user', [
            'course_id' => $course->id,
            'user_id' => $student->id,
        ]);

        $this->actingAs($student)->get(route('courses.index'))
            ->assertOk()
            ->assertSee('Algoritma')
            ->assertSee('Gabung ke Kelas');

        $this->actingAs($student)->get(route('courses.show', $course))
            ->assertOk()
            ->assertSee('Algoritma');
    }

    public function test_only_laboran_can_add_and_remove_students_from_a_course_roster(): void
    {
        [$laboran, $dosen, $aslab, $semester] = $this->staffFixture();
        $student = User::factory()->create(['role' => 'Mahasiswa']);
        $course = Course::query()->create([
            'semester_id' => $semester->id,
            'course_name' => 'Struktur Data',
            'class_group' => 'B',
            'target_semester' => 2,
            'dosen_id' => $dosen->id,
            'laboran_id' => $laboran->id,
            'aslab_id' => $aslab->id,
            'enrollment_code' => 'ROSTER12',
        ]);

        $this->actingAs($laboran)->get(route('courses.students', $course))
            ->assertOk()
            ->assertSee($student->name)
            ->assertSee('Tambahkan ke Kelas');

        $this->actingAs($laboran)->post(route('courses.add-student', $course), [
            'student_id' => $student->id,
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseHas('course_user', [
            'course_id' => $course->id,
            'user_id' => $student->id,
        ]);

        $this->actingAs($aslab)->post(route('courses.add-student', $course), [
            'student_id' => $student->id,
        ])->assertForbidden();

        $this->actingAs($laboran)->delete(route('courses.remove-student', [$course, $student]))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('course_user', [
            'course_id' => $course->id,
            'user_id' => $student->id,
        ]);
    }

    public function test_aslab_role_is_fixed_and_cannot_be_switched_into_student_access(): void
    {
        [$laboran, $dosen, $aslab, $semester] = $this->staffFixture();
        $course = Course::query()->create([
            'semester_id' => $semester->id,
            'course_name' => 'Keamanan Sistem',
            'class_group' => 'A',
            'target_semester' => 5,
            'dosen_id' => $dosen->id,
            'laboran_id' => $laboran->id,
            'aslab_id' => $aslab->id,
            'enrollment_code' => 'SECURE12',
        ]);

        $this->assertFalse(Route::has('role.switch'));

        $this->actingAs($aslab)
            ->withSession(['active_role' => 'Mahasiswa'])
            ->get(route('courses.index'))
            ->assertOk()
            ->assertSee($course->course_name)
            ->assertDontSee('Gabung ke Kelas')
            ->assertDontSee('Ganti Mode');

        $this->actingAs($aslab)
            ->withSession(['active_role' => 'Mahasiswa'])
            ->post(route('courses.enroll'), ['enrollment_code' => $course->enrollment_code])
            ->assertForbidden();

        $this->actingAs($aslab)->get(route('courses.create'))->assertForbidden();
        $this->actingAs($aslab)->post(route('courses.store'), [])->assertForbidden();
    }

    /** @return array{User, User, User, Semester} */
    private function staffFixture(): array
    {
        $laboran = User::factory()->create(['role' => 'Laboran']);
        $dosen = User::factory()->create(['role' => 'Dosen']);
        $aslab = User::factory()->create(['role' => 'Aslab']);
        $semester = Semester::query()->create(['name' => 'Ganjil 2026/2027', 'is_active' => true]);

        return [$laboran, $dosen, $aslab, $semester];
    }
}
