<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seeder_creates_initial_users_including_the_lecturer_account(): void
    {
        $this->seed();

        $this->assertDatabaseCount('users', 4);
        $this->assertDatabaseCount('semesters', 0);
        $this->assertDatabaseCount('courses', 0);

        $expectedUsers = [
            '0701223160' => ['Muhammad Fathir Aulia', 'laboran@uinsu.ac.id', 'Laboran'],
            '0701222090' => ['Bintang Kurniawan Herman', 'aslab@uinsu.ac.id', 'Aslab'],
            '0701225090' => ['Bintangin', '0701225090@student.uinsu.ac.id', 'Mahasiswa'],
            '9999999999' => ['Dosen Demo', 'dosen@uinsu.ac.id', 'Dosen'],
        ];

        foreach ($expectedUsers as $id => [$name, $email, $role]) {
            $user = User::query()->findOrFail($id);

            $this->assertSame($name, $user->name);
            $this->assertSame($email, $user->email);
            $this->assertSame($role, $user->role);
            $this->assertTrue(Hash::check('password', $user->password));
            $this->assertNotNull($user->email_verified_at);
            $this->assertNotNull($user->approved_at);
            $this->assertFalse($user->is_first_login);
        }

        $this->actingAs(User::query()->findOrFail('9999999999'))
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Dosen Demo')
            ->assertSee('Belum ada kelas yang ditugaskan.');
    }
}
