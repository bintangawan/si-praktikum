<?php

namespace App\Http\Controllers;

use App\Models\Tutorial;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TutorialController extends Controller
{
    public function index(Request $request): View
    {
        $query = Tutorial::with('author');

        // Pencarian berdasarkan Judul atau Deskripsi
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filter berdasarkan Tipe Konten (youtube / gdrive_pdf)
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $tutorials = $query->latest()->get();

        return view('tutorials.index', compact('tutorials'));
    }

    public function store(Request $request): RedirectResponse
    {
        // Pengecekan akses: Hanya Laboran yang bisa mengunggah
        if (! auth()->user()->hasRole('Laboran')) {
            return redirect()->back()->with('error', 'Hanya Laboran yang diizinkan mengunggah tutorial.');
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:youtube,gdrive_pdf',
            'url' => 'required|url',
        ]);

        Tutorial::create([
            'title' => $request->title,
            'description' => $request->description,
            'type' => $request->type,
            'url' => $request->url,
            'created_by' => auth()->id(),
        ]);

        return redirect()->back()->with('success', 'Tutorial berhasil ditambahkan!');
    }

    public function destroy(Tutorial $tutorial): RedirectResponse
    {
        // Hanya Laboran yang bisa menghapus
        if (! auth()->user()->hasRole('Laboran')) {
            return redirect()->back()->with('error', 'Akses ditolak.');
        }

        $tutorial->delete();

        return redirect()->back()->with('success', 'Tutorial berhasil dihapus.');
    }
}
