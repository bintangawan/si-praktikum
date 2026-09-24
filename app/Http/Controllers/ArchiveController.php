<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\Support\Facades\Auth;

class ArchiveController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $role = strtoupper($user->role);

        $query = Course::query()
            ->with(['dosen:id,name', 'aslab:id,name', 'laboran:id,name', 'semester:id,name,is_active'])
            ->withCount('students')
            ->where(fn ($query) => $query
                ->where('is_archived', true)
                ->orWhereHas('semester', fn ($semester) => $semester->where('is_active', false)))
            ->latest();

        if ($role === 'MAHASISWA') {
            $query->whereHas('students', fn ($students) => $students->where('user_id', $user->id));
        } elseif ($role === 'DOSEN') {
            $query->where('dosen_id', $user->id);
        } elseif ($role === 'ASLAB') {
            $query->where('aslab_id', $user->id);
        }

        $coursePaginator = $query->paginate(24)->withQueryString();
        $courses = $coursePaginator->getCollection()->groupBy(fn ($course) => $course->semester->name);

        return view('archives.index', compact('courses', 'coursePaginator'));
    }
}
