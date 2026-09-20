<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Services\DriveLink;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ModuleEditorController extends Controller
{
    public function edit(Course $course)
    {
        $this->authorize('manage', $course);

        return view('courses.modules', ['course' => $course->load('meetings')]);
    }

    public function store(Request $request, Course $course)
    {
        $this->authorize('manage', $course);
        $data = $request->validate([
            'modules' => ['required', 'array', 'min:1', 'max:50'],
            'modules.*.meeting_number' => ['required', 'integer', 'min:1', 'max:50', 'distinct'],
            'modules.*.title' => ['required', 'string', 'max:255'],
            'modules.*.description' => ['nullable', 'string', 'max:10000'],
            'modules.*.module_drive_link' => ['required', 'string', 'max:2048', DriveLink::rule()],
            'modules.*.deadline' => ['nullable', 'date'],
        ]);
        DB::transaction(function () use ($course, $data) {
            Course::whereKey($course->id)->lockForUpdate()->firstOrFail();
            foreach ($data['modules'] as $module) {
                $course->meetings()->updateOrCreate(['meeting_number' => $module['meeting_number']], $module);
            }
        });

        return redirect()->route('courses.show', $course)->with('success', 'Modul berhasil disimpan dan tersedia untuk mahasiswa.');
    }
}
