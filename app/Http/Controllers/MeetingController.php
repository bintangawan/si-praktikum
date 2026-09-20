<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Course;
use App\Models\Meeting;
use App\Services\DriveLink;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MeetingController extends Controller
{
    public function show(Request $request, Course $course): View
    {
        $this->authorize('view', $course);
        $course->load([
            'laboran:id,name,avatar',
            'dosen:id,name,avatar',
            'aslab:id,name,avatar',
            'semester:id,name,is_active',
            'finalTask',
        ]);
        $studentId = $request->user()->hasRole(UserRole::MAHASISWA) ? $request->user()->id : null;

        $course->load(['meetings' => function ($query) use ($studentId) {
            $query->withCount(['attendances', 'submissions']);

            if ($studentId) {
                $query->with([
                    'attendances' => fn ($relation) => $relation->where('student_id', $studentId),
                    'submissions' => fn ($relation) => $relation
                        ->where('student_id', $studentId)
                        ->with(['histories' => fn ($history) => $history->whereNotNull('feedback')->latest()]),
                ]);
            }
        }]);

        if ($course->finalTask) {
            if ($studentId) {
                $course->finalTask->load(['submissions' => fn ($query) => $query->where('student_id', $studentId)]);
            }
        }

        return view('courses.show', compact('course'));
    }

    public function store(Request $request, Course $course): RedirectResponse
    {
        $this->authorize('manage', $course);
        $validated = $request->validate([
            'meeting_number' => ['required', 'integer', 'min:1', 'max:50', Rule::unique('meetings')->where('course_id', $course->id)],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'module_drive_link' => ['required', 'string', 'max:2048', DriveLink::rule()],
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
            'module_drive_link' => ['required', 'string', 'max:2048', DriveLink::rule()],
        ]);

        $meeting->update($validated);

        return back()->with('success', 'Data pertemuan berhasil diperbarui.');
    }
}
