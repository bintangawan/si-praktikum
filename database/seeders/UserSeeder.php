<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'id' => '123',
                'name' => 'Admin Laboratorium',
                'email' => 'laboran@uinsu.ac.id',
                'role' => 'Laboran',
            ],
            [
                'id' => '0702220001',
                'name' => 'Muhammad Fadli Ramadhan',
                'email' => 'aslab@uinsu.ac.id',
                'role' => 'Aslab',
            ],
            [
                'id' => '198705152019031004',
                'name' => 'Dr. Rahmat Hidayat, M.Kom.',
                'email' => 'dosen@uinsu.ac.id',
                'role' => 'Dosen',
            ],
            ['id' => '0701231001', 'name' => 'Ahmad Fauzan', 'email' => '0701231001@student.uinsu.ac.id', 'role' => 'Mahasiswa'],
            ['id' => '0701231002', 'name' => 'Aisyah Putri Ramadhani', 'email' => '0701231002@student.uinsu.ac.id', 'role' => 'Mahasiswa'],
            ['id' => '0701231003', 'name' => 'Bima Pratama', 'email' => '0701231003@student.uinsu.ac.id', 'role' => 'Mahasiswa'],
            ['id' => '0701231004', 'name' => 'Citra Ananda', 'email' => '0701231004@student.uinsu.ac.id', 'role' => 'Mahasiswa'],
            ['id' => '0701231005', 'name' => 'Dimas Saputra', 'email' => '0701231005@student.uinsu.ac.id', 'role' => 'Mahasiswa'],
            ['id' => '0701231006', 'name' => 'Fahira Nabila', 'email' => '0701231006@student.uinsu.ac.id', 'role' => 'Mahasiswa'],
            ['id' => '0701231007', 'name' => 'Gilang Maulana', 'email' => '0701231007@student.uinsu.ac.id', 'role' => 'Mahasiswa'],
            ['id' => '0701231008', 'name' => 'Nadya Khairunnisa', 'email' => '0701231008@student.uinsu.ac.id', 'role' => 'Mahasiswa'],
            ['id' => '0701231009', 'name' => 'Rizky Akbar', 'email' => '0701231009@student.uinsu.ac.id', 'role' => 'Mahasiswa'],
            ['id' => '0701231010', 'name' => 'Salsabila Zahra', 'email' => '0701231010@student.uinsu.ac.id', 'role' => 'Mahasiswa'],
        ];

        foreach ($users as $user) {
            User::query()->updateOrCreate(
                ['id' => $user['id']],
                [
                    ...$user,
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                    'is_first_login' => false,
                ],
            );
        }
    }
}
