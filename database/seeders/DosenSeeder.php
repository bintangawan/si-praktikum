<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DosenSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'dosen@uinsu.ac.id'],
            [
                'id' => '9999999999',
                'name' => 'Dosen Demo',
                'password' => Hash::make('password'),
                'role' => UserRole::DOSEN->value,
                'email_verified_at' => now(),
                'approved_at' => now(),
                'is_first_login' => false,
            ],
        );
    }
}
