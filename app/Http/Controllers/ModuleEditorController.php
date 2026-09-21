<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Services\DriveLink;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ModuleEditorController extends Controller
{
    public function edit(Course $course): View
    {
        $this->authorize('manageModules', $course);

        return view('courses.modules', ['course' => $course->load('meetings')]);
    }

    public function update(Request $request, Course $course): RedirectResponse
    {
        $this->authorize('manageModules', $course);
        $data = $request->validate([
            'modules' => ['required', 'array', 'min:1', 'max:16'],
            'modules.*.id' => ['nullable', 'integer', 'distinct', Rule::exists('meetings', 'id')->where('course_id', $course->id)],
            'modules.*.title' => ['required', 'string', 'max:255'],
            'modules.*.description' => ['nullable', 'string', 'max:10000'],
            'modules.*.module_drive_link' => ['nullable', 'string', 'max:2048', DriveLink::rule()],
            'modules.*.deadline' => ['nullable', 'date'],
            'modules.*.is_published' => ['nullable', 'boolean'],
        ], [
            'modules.required' => 'Tambahkan minimal satu modul.',
            'modules.min' => 'Tambahkan minimal satu modul.',
            'modules.max' => 'Jumlah modul maksimal 16.',
            'modules.*.id.exists' => 'Data modul tidak sesuai dengan kelas. Muat ulang halaman dan coba lagi.',
            'modules.*.id.distinct' => 'Data modul terduplikasi. Muat ulang halaman dan coba lagi.',
            'modules.*.title.required' => 'Judul setiap modul wajib diisi.',
            'modules.*.title.max' => 'Judul modul maksimal 255 karakter.',
            'modules.*.description.max' => 'Instruksi modul maksimal 10.000 karakter.',
            'modules.*.deadline.date' => 'Format batas pengumpulan tidak valid.',
            'modules.*.is_published.boolean' => 'Status pengumpulan modul tidak valid.',
        ]);

        DB::transaction(function () use ($course, $data): void {
            Course::whereKey($course->id)->lockForUpdate()->firstOrFail();
            $existing = $course->meetings()->lockForUpdate()->get()->keyBy('id');
            $submittedIds = collect($data['modules'])
                ->pluck('id')
                ->filter(fn ($id) => filled($id))
                ->map(fn ($id) => (int) $id)
                ->sort()
                ->values();
            $existingIds = $existing->keys()->map(fn ($id) => (int) $id)->sort()->values();

            if ($submittedIds->all() !== $existingIds->all()) {
                throw ValidationException::withMessages([
                    'modules' => 'Daftar modul berubah. Muat ulang halaman agar data laprak tetap berada pada modul yang benar.',
                ]);
            }

            if (count($data['modules']) > 16) {
                throw ValidationException::withMessages(['modules' => 'Jumlah modul maksimal 16.']);
            }

            $nextNumber = ((int) $existing->max('meeting_number')) + 1;

            foreach ($data['modules'] as $module) {
                $id = filled($module['id'] ?? null) ? (int) $module['id'] : null;
                $isPublished = array_key_exists('is_published', $module)
                    ? (bool) $module['is_published']
                    : ($id ? $existing->get($id)->isPublished() : true);
                unset($module['id'], $module['is_published']);
                $module['module_drive_link'] = filled($module['module_drive_link'] ?? null)
                    ? $module['module_drive_link']
                    : null;
                $module['published_at'] = $isPublished ? now() : null;

                if ($id) {
                    $existing->get($id)->update($module);

                    continue;
                }

                $course->meetings()->create([
                    ...$module,
                    'meeting_number' => $nextNumber++,
                ]);
            }
        });

        $published = $course->meetings()->whereNotNull('published_at')->count();

        return redirect()->route('courses.show', $course)
            ->with('success', "Modul berhasil disimpan. Pengumpulan dibuka pada {$published} dari {$course->meetings()->count()} modul.");
    }
}
