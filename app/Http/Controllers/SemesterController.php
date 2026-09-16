<?php

namespace App\Http\Controllers;

use App\Models\Semester;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SemesterController extends Controller
{
    // Menampilkan daftar semester
    public function index()
    {
        $semesters = Semester::orderBy('created_at', 'desc')->get();

        return view('semesters.index', compact('semesters'));
    }

    // Menyimpan semester baru
    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:semesters,name'],
        ]);

        Semester::create([
            'name' => $request->name,
            'is_active' => false, // Default selalu false saat baru dibuat
        ]);

        return back()->with('success', 'Semester baru berhasil ditambahkan.');
    }

    // Mengupdate nama semester
    public function update(Request $request, Semester $semester)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('semesters', 'name')->ignore($semester)],
        ]);

        $semester->update([
            'name' => $request->name,
        ]);

        return back()->with('success', 'Nama semester berhasil diperbarui.');
    }

    // Menghapus semester
    public function destroy(Semester $semester)
    {
        if ($semester->is_active) {
            return back()->with('error', 'Tidak dapat menghapus semester yang sedang aktif!');
        }

        if ($semester->courses()->exists()) {
            return back()->with('error', 'Semester yang sudah memiliki kelas tidak dapat dihapus.');
        }

        $semester->delete();

        return back()->with('success', 'Semester berhasil dihapus.');
    }

    // MENGAKTIFKAN SEMESTER (Ini fungsi paling penting)
    public function setActive(Semester $semester)
    {
        DB::transaction(function () use ($semester): void {
            Semester::query()->where('is_active', true)->update(['is_active' => false]);
            $semester->update(['is_active' => true]);
        });

        return back()->with('success', 'Semester '.$semester->name.' sekarang aktif!');
    }
}
