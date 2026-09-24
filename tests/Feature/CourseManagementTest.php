<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Course;
use App\Models\CourseGrade;
use App\Models\Meeting;
use App\Models\Semester;
use App\Models\Submission;
use App\Models\SubmissionHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class CourseManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_course_page_uses_consistent_empty_states_for_every_role(): void
    {
        $roles = ['Mahasiswa', 'Dosen', 'Aslab', 'Laboran'];
        $noActiveSemesterMessage = 'Belum ada kelas praktikum untuk ditampilkan saat ini.';
        $activeSemesterMessage = 'Belum ada kelas praktikum yang tersedia untuk akun Anda pada semester aktif ini.';

        foreach ($roles as $role) {
            $user = User::factory()->create(['role' => $role]);

            $response = $this->actingAs($user)->get(route('courses.index'));
            $response->assertOk()
                ->assertSee('Belum Ada Kelas')
                ->assertSee($noActiveSemesterMessage)
                ->assertDontSee('Sistem Sedang Ditangguhkan');

            if ($role === 'Mahasiswa') {
                $response->assertDontSee('id="enrollment_code"', false);
            }
        }

        Semester::query()->create(['name' => 'Semester Kosong', 'is_active' => true]);

        foreach ($roles as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->actingAs($user)->get(route('courses.index'))
                ->assertOk()
                ->assertSee('Belum Ada Kelas')
                ->assertSee($activeSemesterMessage)
                ->assertDontSee('Sistem Sedang Ditangguhkan');
        }
    }

    public function test_dosen_dashboard_lists_all_and_only_courses_assigned_to_that_lecturer(): void
    {
        [$laboran, $dosen, $aslab, $activeSemester] = $this->staffFixture();
        $archivedSemester = Semester::query()->create(['name' => 'Genap 2025/2026', 'is_active' => false]);

        $activeCourse = Course::query()->create([
            'semester_id' => $activeSemester->id,
            'course_name' => 'Algoritma Aktif',
            'class_group' => 'A',
            'target_semester' => 3,
            'dosen_id' => $dosen->id,
            'laboran_id' => $laboran->id,
            'aslab_id' => $aslab->id,
            'enrollment_code' => 'DOSENACT01',
        ]);
        $archivedCourse = Course::query()->create([
            'semester_id' => $archivedSemester->id,
            'course_name' => 'Basis Data Arsip',
            'class_group' => 'B',
            'target_semester' => 3,
            'dosen_id' => $dosen->id,
            'laboran_id' => $laboran->id,
            'aslab_id' => $aslab->id,
            'enrollment_code' => 'DOSENARC01',
        ]);

        $otherDosen = User::factory()->create(['role' => 'Dosen']);
        $otherCourse = Course::query()->create([
            'semester_id' => $activeSemester->id,
            'course_name' => 'Kelas Dosen Lain',
            'class_group' => 'C',
            'target_semester' => 3,
            'dosen_id' => $otherDosen->id,
            'laboran_id' => $laboran->id,
            'aslab_id' => $aslab->id,
            'enrollment_code' => 'DOSENOTHER01',
        ]);

        $this->actingAs($dosen)->get(route('dashboard'))
            ->assertOk()
            ->assertSee($activeCourse->course_name)
            ->assertSee($archivedCourse->course_name)
            ->assertDontSee($otherCourse->course_name)
            ->assertSee(route('courses.show', $activeCourse), false)
            ->assertSee(route('courses.students', $activeCourse), false)
            ->assertSee(route('attendance.report', $activeCourse), false);
    }

    public function test_laboran_can_create_a_course_and_immediately_see_it_in_their_course_list(): void
    {
        [$laboran, $dosen, $aslab, $semester] = $this->staffFixture();
        $assignedLaboran = User::factory()->create(['role' => 'Laboran']);

        $response = $this->actingAs($laboran)->post(route('courses.store'), [
            'course_name' => '  Pemrograman   Web  ',
            'class_group' => ' ti-3a ',
            'target_semester' => 3,
            'laboran_id' => $assignedLaboran->id,
            'dosen_id' => $dosen->id,
            'aslab_id' => $aslab->id,
            'module_count' => 2,
        ]);

        $course = Course::query()->sole();
        $response->assertRedirect(route('courses.show', $course))
            ->assertSessionHas('success');

        $this->assertSame('Pemrograman Web', $course->course_name);
        $this->assertSame('TI-3A', $course->class_group);
        $this->assertSame($semester->id, $course->semester_id);
        $this->assertSame($assignedLaboran->id, $course->laboran_id);
        $this->assertSame('pemrograman-web-ti-3a', $course->slug);
        $this->assertNotEmpty($course->enrollment_code);
        $this->assertSame(2, $course->meetings()->count());
        $this->assertDatabaseHas('meetings', [
            'course_id' => $course->id,
            'meeting_number' => 1,
            'title' => 'Modul 1',
            'published_at' => null,
        ]);

        $this->assertStringContainsString('/courses/pemrograman-web-ti-3a', route('courses.show', $course));
        $this->actingAs($laboran)->get("/courses/{$course->id}/students")->assertNotFound();

        $this->actingAs($assignedLaboran)->get(route('courses.index'))
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
            'laboran_id' => $student->id,
            'dosen_id' => $student->id,
            'aslab_id' => $aslab->id,
            'module_count' => 8,
        ])->assertRedirect(route('courses.create'))->assertSessionHasErrors(['laboran_id', 'dosen_id']);

        $this->assertDatabaseCount('courses', 0);

        $this->actingAs($student)->post(route('courses.store'), [
            'course_name' => 'Basis Data',
            'class_group' => 'A',
            'target_semester' => 3,
            'laboran_id' => $laboran->id,
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

    public function test_laboran_and_assigned_aslab_can_search_and_add_students_but_only_laboran_can_remove_them(): void
    {
        [$laboran, $dosen, $aslab, $semester] = $this->staffFixture();
        $student = User::factory()->create([
            'id' => '0701231001',
            'name' => 'Ahmad Fauzan',
            'role' => 'Mahasiswa',
        ]);
        $secondStudent = User::factory()->create([
            'id' => '0701231002',
            'name' => 'Siti Aminah',
            'role' => 'Mahasiswa',
        ]);
        $unassignedAslab = User::factory()->create(['role' => 'Aslab']);
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
            ->assertSee('Ketik nama atau NIM mahasiswa')
            ->assertSee('Saran muncul setelah 3 karakter')
            ->assertSee('Tambahkan ke Kelas');

        $this->actingAs($laboran)
            ->getJson(route('courses.search-students', $course).'?q=100')
            ->assertOk()
            ->assertJsonFragment(['id' => $student->id, 'name' => $student->name])
            ->assertJsonFragment(['id' => $secondStudent->id, 'name' => $secondStudent->name]);

        $this->actingAs($laboran)
            ->getJson(route('courses.search-students', $course).'?q=10')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('q');

        $this->actingAs($laboran)->post(route('courses.add-student', $course), [
            'student_id' => $student->id,
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseHas('course_user', [
            'course_id' => $course->id,
            'user_id' => $student->id,
        ]);

        $this->actingAs($laboran)
            ->getJson(route('courses.search-students', $course).'?q=Ahm')
            ->assertOk()
            ->assertJsonMissing(['id' => $student->id]);

        $this->actingAs($aslab)->post(route('courses.add-student', $course), [
            'student_id' => $secondStudent->id,
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseHas('course_user', [
            'course_id' => $course->id,
            'user_id' => $secondStudent->id,
        ]);

        $this->actingAs($aslab)->get(route('courses.students', $course))
            ->assertOk()
            ->assertSee('Tambahkan ke Kelas');

        $this->actingAs($unassignedAslab)
            ->getJson(route('courses.search-students', $course).'?q=100')
            ->assertForbidden();

        $this->actingAs($unassignedAslab)->post(route('courses.add-student', $course), [
            'student_id' => $student->id,
        ])->assertForbidden();

        $this->actingAs($aslab)->delete(route('courses.remove-student', [$course, $student]))
            ->assertForbidden();

        $this->actingAs($laboran)->delete(route('courses.remove-student', [$course, $student]))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->actingAs($laboran)->delete(route('courses.remove-student', [$course, $secondStudent]))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('course_user', [
            'course_id' => $course->id,
            'user_id' => $student->id,
        ]);
        $this->assertDatabaseMissing('course_user', [
            'course_id' => $course->id,
            'user_id' => $secondStudent->id,
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

    public function test_laboran_can_edit_course_information_and_staff_assignment(): void
    {
        [$laboran, $dosen, $aslab, $semester] = $this->staffFixture();
        $replacementDosen = User::factory()->create(['role' => 'Dosen']);
        $replacementAslab = User::factory()->create(['role' => 'Aslab']);
        $course = Course::query()->create([
            'semester_id' => $semester->id,
            'course_name' => 'Kelas Salah',
            'class_group' => 'a',
            'target_semester' => 2,
            'dosen_id' => $dosen->id,
            'laboran_id' => $laboran->id,
            'aslab_id' => $aslab->id,
            'enrollment_code' => 'EDIT1234',
        ]);

        $this->actingAs($laboran)->put(route('courses.update', $course), [
            'course_name' => '  Rekayasa   Perangkat Lunak ',
            'class_group' => ' ti-4a ',
            'target_semester' => 4,
            'dosen_id' => $replacementDosen->id,
            'aslab_id' => $replacementAslab->id,
        ])->assertRedirect();

        $course->refresh();
        $this->assertSame('Rekayasa Perangkat Lunak', $course->course_name);
        $this->assertSame('TI-4A', $course->class_group);
        $this->assertSame('rekayasa-perangkat-lunak-ti-4a', $course->slug);
        $this->assertSame($replacementDosen->id, $course->dosen_id);
        $this->assertDatabaseHas('course_staff_histories', [
            'course_id' => $course->id,
            'previous_dosen_id' => $dosen->id,
            'dosen_id' => $replacementDosen->id,
            'changed_by' => $laboran->id,
        ]);

        $this->actingAs($aslab)->get(route('courses.edit', $course))->assertForbidden();
        $this->actingAs($aslab)->put(route('courses.update', $course), [])->assertForbidden();
    }

    public function test_laboran_can_delete_course_with_academic_records_after_confirmation(): void
    {
        [$laboran, $dosen, $aslab, $semester] = $this->staffFixture();
        $unused = Course::query()->create([
            'semester_id' => $semester->id,
            'course_name' => 'Kelas Duplikat',
            'class_group' => 'A',
            'target_semester' => 1,
            'dosen_id' => $dosen->id,
            'laboran_id' => $laboran->id,
            'aslab_id' => $aslab->id,
            'enrollment_code' => 'DELETE01',
        ]);
        $unusedMeeting = $unused->meetings()->create([
            'meeting_number' => 1,
            'title' => 'Modul kosong',
            'module_drive_link' => 'https://drive.google.com/file/d/unused/view',
        ]);
        $student = User::factory()->create(['role' => 'Mahasiswa']);
        $unused->students()->attach($student);

        $this->actingAs($laboran)->delete(route('courses.destroy', $unused))
            ->assertRedirect(route('courses.index'))
            ->assertSessionHas('success');
        $this->assertDatabaseMissing('courses', ['id' => $unused->id]);
        $this->assertDatabaseMissing('meetings', ['id' => $unusedMeeting->id]);
        $this->assertDatabaseMissing('course_user', ['course_id' => $unused->id]);

        $protected = Course::query()->create([
            'semester_id' => $semester->id,
            'course_name' => 'Kelas Berjalan',
            'class_group' => 'B',
            'target_semester' => 1,
            'dosen_id' => $dosen->id,
            'laboran_id' => $laboran->id,
            'aslab_id' => $aslab->id,
            'enrollment_code' => 'DELETE02',
        ]);
        $meeting = Meeting::query()->create([
            'course_id' => $protected->id,
            'meeting_number' => 1,
            'title' => 'Modul aktif',
            'module_drive_link' => 'https://drive.google.com/file/d/active/view',
        ]);
        Attendance::query()->create([
            'meeting_id' => $meeting->id,
            'student_id' => $student->id,
            'status' => 'Hadir',
            'attendance_date' => now()->toDateString(),
        ]);
        $submission = Submission::query()->create([
            'student_id' => $student->id,
            'meeting_id' => $meeting->id,
            'submission_link' => 'https://drive.google.com/file/d/approved/view',
            'aslab_status' => 'ACC',
            'laboran_status' => 'ACC',
            'is_completed' => true,
            'aslab_score' => 90,
            'laboran_score' => 88,
        ]);
        $history = SubmissionHistory::query()->create([
            'submission_id' => $submission->id,
            'drive_link' => 'https://drive.google.com/file/d/approved/view',
            'iteration' => 1,
            'action_type' => 'ACC',
            'reviewed_by' => $laboran->id,
        ]);
        CourseGrade::query()->create([
            'course_id' => $protected->id,
            'student_id' => $student->id,
            'uts_score' => 87,
            'uas_score' => 91,
        ]);

        $this->actingAs($laboran)->delete(route('courses.destroy', $protected))
            ->assertRedirect(route('courses.index'))
            ->assertSessionHas('success');
        $this->assertDatabaseMissing('courses', ['id' => $protected->id]);
        $this->assertDatabaseMissing('meetings', ['id' => $meeting->id]);
        $this->assertDatabaseMissing('attendances', ['meeting_id' => $meeting->id]);
        $this->assertDatabaseMissing('submissions', ['id' => $submission->id]);
        $this->assertDatabaseMissing('submission_histories', ['id' => $history->id]);
        $this->assertDatabaseMissing('course_grades', ['course_id' => $protected->id]);
    }

    public function test_laboran_can_archive_and_restore_a_course_without_deleting_records(): void
    {
        [$laboran, $dosen, $aslab, $semester] = $this->staffFixture();
        $course = Course::query()->create([
            'semester_id' => $semester->id,
            'course_name' => 'Kelas Arsip Individual',
            'class_group' => 'C',
            'target_semester' => 1,
            'dosen_id' => $dosen->id,
            'laboran_id' => $laboran->id,
            'aslab_id' => $aslab->id,
            'enrollment_code' => 'ARCHIVE01',
        ]);
        $meeting = $course->meetings()->create([
            'meeting_number' => 1,
            'title' => 'Modul tersimpan',
        ]);

        $this->actingAs($laboran)->get(route('courses.index'))
            ->assertOk()
            ->assertSee('Kelas Arsip Individual')
            ->assertSee('Arsipkan kelas')
            ->assertSee('Hapus kelas');

        $this->actingAs($laboran)->patch(route('courses.archive', $course), ['archived' => 1])
            ->assertRedirect(route('archives.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('courses', ['id' => $course->id, 'is_archived' => true]);
        $this->assertDatabaseHas('meetings', ['id' => $meeting->id]);
        $this->actingAs($laboran)->get(route('courses.index'))->assertDontSee('Kelas Arsip Individual');
        $this->actingAs($laboran)->get(route('archives.index'))->assertOk()->assertSee('Kelas Arsip Individual');
        $this->actingAs($laboran)->put(route('meetings.update', $meeting), [
            'title' => 'Tidak boleh diedit dari kelas arsip',
        ])->assertForbidden();

        $this->actingAs($laboran)->patch(route('courses.archive', $course), ['archived' => 0])
            ->assertRedirect(route('courses.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('courses', ['id' => $course->id, 'is_archived' => false]);
        $this->actingAs($laboran)->get(route('courses.index'))->assertSee('Kelas Arsip Individual');
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
