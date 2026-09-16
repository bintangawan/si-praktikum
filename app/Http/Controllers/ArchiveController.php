<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Semester;
use Illuminate\Support\Facades\Auth;

class ArchiveController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $role = strtoupper($user->active_role);

        // Ambil semua semester yang TIDAK aktif (Arsip)
        $archivedSemesters = Semester::where('is_active', false)->pluck('id');

        // Jika tidak ada semester arsip, kembalikan koleksi kosong
        if ($archivedSemesters->isEmpty()) {
            $courses = collect();
        } else {
            // Query dasar: Hanya ambil course dari semester arsip
            $query = Course::with(['dosen', 'aslab', 'laboran', 'semester'])
                ->whereIn('semester_id', $archivedSemesters)
                ->latest();

            // Filter berdasarkan Role (Sama seperti di Dashboard)
            if ($role === 'MAHASISWA') {
                // Mahasiswa hanya melihat kelas yang mereka ikuti di masa lalu
                $query->whereHas('students', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                });
            } elseif ($role === 'DOSEN') {
                $query->where('dosen_id', $user->id);
            } elseif ($role === 'ASLAB') {
                $query->where('aslab_id', $user->id);
            } elseif ($role === 'LABORAN') {
                // Laboran melihat kelas yang di-assign ke mereka, ATAU semua kelas (tergantung aturan kampusmu)
                // Jika laboran bisa lihat semua, jangan tambah kondisi ini.
                $query->where('laboran_id', $user->id);
            }

            // Dapatkan hasil dan kelompokkan berdasarkan nama semester agar rapi di UI
            $courses = $query->get()->groupBy(function ($data) {
                return $data->semester->name;
            });
        }

        return view('archives.index', compact('courses'));
    }
}
