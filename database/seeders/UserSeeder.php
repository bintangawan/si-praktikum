<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Jalankan database seeds.
     */
    public function run(): void // <--- Pastikan namanya "run"
    {
        $account = config('si_praktikum.initial_laboran');

        if (! $account['id'] || ! $account['email'] || ! $account['password']) {
            $this->command?->warn('Akun laboran awal dilewati. Isi SEED_LABORAN_ID, SEED_LABORAN_EMAIL, dan SEED_LABORAN_PASSWORD bila diperlukan.');

            return;
        }

        User::query()->firstOrCreate(['id' => $account['id']], [
            'name' => $account['name'],
            'email' => $account['email'],
            'password' => Hash::make($account['password']),
            'role' => 'Laboran',
            'email_verified_at' => now(),
            'is_first_login' => true,
        ]);

    }
}
