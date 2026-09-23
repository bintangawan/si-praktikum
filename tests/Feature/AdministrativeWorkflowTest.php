<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\FinalTask;
use App\Models\Meeting;
use App\Models\Semester;
use App\Models\Tutorial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdministrativeWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_laboran_can_manage_semesters_safely(): void
    {
        $laboran = User::factory()->create(['role' => 'Laboran']);

        $this->actingAs($laboran)->post(route('semesters.store'), [
            'name' => 'Ganjil 2026/2027',
        ])->assertRedirect()->assertSessionHas('success');

        $semester = Semester::query()->sole();
        $this->assertFalse($semester->is_active);

        $this->actingAs($laboran)->patch(route('semesters.set-active', $semester))
            ->assertRedirect()
            ->assertSessionHas('success');
        $this->assertTrue($semester->fresh()->is_active);

        $this->actingAs($laboran)->delete(route('semesters.destroy', $semester))
            ->assertRedirect()
            ->assertSessionHas('error');
        $this->assertDatabaseHas('semesters', ['id' => $semester->id]);
    }

    public function test_assigned_staff_can_manage_course_content(): void
    {
        [$laboran, $aslab, $dosen, $course] = $this->courseFixture();

        $meeting = Meeting::query()->create([
            'course_id' => $course->id,
            'meeting_number' => 1,
            'title' => 'Modul 1',
        ]);

        $this->actingAs($aslab)->put(route('courses.modules.update', $course), ['modules' => [[
            'id' => $meeting->id,
            'title' => 'Pengenalan Sistem',
            'description' => 'Materi diperbarui',
            'module_drive_link' => 'https://drive.google.com/file/d/module-updated/view',
            'deadline' => now()->addDay()->format('Y-m-d H:i:s'),
            'is_published' => 1,
        ]]])->assertRedirect()->assertSessionHas('success');
        $this->assertDatabaseHas('meetings', ['id' => $meeting->id, 'title' => 'Pengenalan Sistem']);
        $this->assertNotNull($meeting->fresh()->published_at);

        $this->actingAs($aslab)->put(route('meetings.update', $meeting), [
            'title' => 'Pengenalan Sistem Informasi',
            'description' => 'Materi akhir',
            'module_drive_link' => 'https://drive.google.com/file/d/module-final/view',
        ])->assertRedirect()->assertSessionHas('success');
        $this->assertDatabaseHas('meetings', ['id' => $meeting->id, 'title' => 'Pengenalan Sistem Informasi']);

        $this->actingAs($dosen)->get(route('courses.modules.edit', $course))->assertForbidden();
        $this->actingAs($dosen)->put(route('meetings.update', $meeting), [
            'title' => 'Tidak boleh',
            'module_drive_link' => 'https://drive.google.com/file/d/blocked/view',
        ])->assertForbidden();

        $finalTask = FinalTask::query()->create([
            'course_id' => $course->id,
            'description' => 'Buat laporan final.',
            'deadline' => now()->addWeek(),
        ]);

        $this->actingAs($aslab)->put(route('final-tasks.update-description', $finalTask), [
            'description' => 'Buat laporan final yang telah direvisi.',
        ])->assertRedirect()->assertSessionHas('success');
        $this->assertDatabaseHas('final_tasks', [
            'id' => $finalTask->id,
            'description' => 'Buat laporan final yang telah direvisi.',
        ]);
    }

    public function test_laboran_can_manage_tutorials_and_user_roles(): void
    {
        $laboran = User::factory()->create(['role' => 'Laboran']);
        $student = User::factory()->create(['role' => 'Mahasiswa']);

        $this->actingAs($laboran)->post(route('tutorials.store'), [
            'title' => 'Panduan Upload',
            'description' => 'Cara mengunggah laporan.',
            'type' => 'youtube',
            'url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
        ])->assertRedirect()->assertSessionHas('success');

        $tutorial = Tutorial::query()->sole();
        $this->assertSame($laboran->id, $tutorial->created_by);

        $this->actingAs($laboran)->post(route('users.make-aslab', $student))
            ->assertRedirect()
            ->assertSessionHas('success');
        $this->assertSame('Aslab', $student->fresh()->role);

        $this->actingAs($laboran)->post(route('users.revoke-aslab', $student))
            ->assertRedirect()
            ->assertSessionHas('success');
        $this->assertSame('Mahasiswa', $student->fresh()->role);

        $this->actingAs($laboran)->post(route('users.update-role', $student), ['role' => 'Laboran'])
            ->assertRedirect()
            ->assertSessionHas('success', "{$student->name} berhasil diangkat menjadi Laboran.");
        $this->assertSame('Laboran', $student->fresh()->role);

        $this->actingAs($laboran)->patch(route('users.reset-password', $student))
            ->assertRedirect()
            ->assertSessionHas('success');
        $student->refresh();
        $this->assertTrue($student->is_first_login);
        $this->assertTrue(Hash::check($student->id, $student->password));

        $this->actingAs($laboran)->delete(route('tutorials.destroy', $tutorial))
            ->assertRedirect()
            ->assertSessionHas('success');
        $this->assertDatabaseMissing('tutorials', ['id' => $tutorial->id]);
    }

    public function test_language_choice_persists_between_pages(): void
    {
        $laboran = User::factory()->create(['role' => 'Laboran']);

        $this->actingAs($laboran)
            ->from(route('users.index'))
            ->post(route('locale.update'), ['locale' => 'en'])
            ->assertRedirect(route('users.index'));

        $this->get(route('users.index'))
            ->assertOk()
            ->assertSee('<html lang="en">', false);

        $this->assertSame('Lab Assistant', __('Aslab'));
    }

    /** @return array{User, User, User, Course} */
    private function courseFixture(): array
    {
        $laboran = User::factory()->create(['role' => 'Laboran']);
        $aslab = User::factory()->create(['role' => 'Aslab']);
        $dosen = User::factory()->create(['role' => 'Dosen']);
        $semester = Semester::query()->create(['name' => 'Ganjil 2026/2027', 'is_active' => true]);
        $course = Course::query()->create([
            'semester_id' => $semester->id,
            'course_name' => 'Pemrograman Web',
            'class_group' => 'A',
            'target_semester' => 3,
            'dosen_id' => $dosen->id,
            'laboran_id' => $laboran->id,
            'aslab_id' => $aslab->id,
            'enrollment_code' => 'ADMIN123',
        ]);

        return [$laboran, $aslab, $dosen, $course];
    }
}
