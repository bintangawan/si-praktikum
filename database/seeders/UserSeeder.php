<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'id' => '0701223160',
                'name' => 'Muhammad Fathir Aulia',
                'email' => 'laboran@uinsu.ac.id',
                'role' => UserRole::LABORAN->value,
            ],
            [
                'id' => '0701222090',
                'name' => 'Bintang Kurniawan Herman',
                'email' => 'aslab@uinsu.ac.id',
                'role' => UserRole::ASLAB->value,
            ],
            [
                'id' => '0701225090',
                'name' => 'Bintangin',
                'email' => '0701225090@student.uinsu.ac.id',
                'role' => UserRole::MAHASISWA->value,
            ],
        ];

        foreach ($users as $user) {
            User::query()->updateOrCreate(
                ['id' => $user['id']],
                [
                    ...$user,
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                    'approved_at' => now(),
                    'is_first_login' => false,
                ],
            );
        }
    }
}
