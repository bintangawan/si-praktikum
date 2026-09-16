<?php

namespace Database\Seeders;

use App\Models\Semester;
use Illuminate\Database\Seeder;

class SemesterSeeder extends Seeder
{
    public function run(): void
    {
        Semester::query()->firstOrCreate(['name' => 'Genap 2025/2026'], [
            'name' => 'Genap 2025/2026',
            'is_active' => true, // Menandakan semester ini yang sedang berjalan
        ]);
    }
}
