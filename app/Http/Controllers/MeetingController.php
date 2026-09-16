<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Course;
use App\Models\Meeting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MeetingController extends Controller
{
    public function show(Request $request, Course $course): View
    {
        $this->authorize('view', $course);
        $course->load(['laboran', 'dosen', 'aslab', 'semester', 'finalTask']);
        $studentId = $request->user()->hasActiveRole(UserRole::MAHASISWA) ? $request->user()->id : null;

        $course->load(['meetings' => function ($query) use ($studentId) {
            $query->withCount('attendances')
                ->with([
                    'attendances' => fn ($relation) => $studentId ? $relation->where('student_id', $studentId) : $relation,
                    'submissions' => fn ($relation) => $studentId ? $relation->where('student_id', $studentId) : $relation,
                ]);
        }]);

        if ($course->finalTask) {
            $course->finalTask->load(['submissions' => fn ($query) => $studentId ? $query->where('student_id', $studentId) : $query]);
        }

        return view('courses.show', compact('course'));
    }

    public function store(Request $request, Course $course): RedirectResponse
    {
        $this->authorize('manage', $course);
        $validated = $request->validate([
            'meeting_number' => ['required', 'integer', 'min:1', 'max:16', Rule::unique('meetings')->where('course_id', $course->id)],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'module_drive_link' => ['nullable', 'url', 'max:2048'],
            'deadline' => ['nullable', 'date'],
        ]);

        $course->meetings()->create($validated);

        return back()->with('success', 'Pertemuan berhasil ditambahkan.');
    }

    public function update(Request $request, Meeting $meeting): RedirectResponse
    {
        $this->authorize('manage', $meeting->course);
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'module_drive_link' => ['nullable', 'url', 'max:2048'],
        ]);

        $meeting->update($validated);

        return back()->with('success', 'Data pertemuan berhasil diperbarui.');
    }
}
