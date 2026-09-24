<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseGrade;
use App\Models\User;
use App\Support\GradeScale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CourseGradeController extends Controller
{
    public function index(Request $request, Course $course): View
    {
        $this->authorize('manage', $course);
        abort_unless($request->user()->hasRole('Dosen'), 403);

        $course->load('semester');
        $meetings = $course->meetings()
            ->with(['submissions' => fn ($query) => $query->where('is_final', false)])
            ->get();
        $grades = $course->grades()->get()->keyBy('student_id');
        $students = $course->students()->orderBy('name')->get();

        $rows = $students->map(function (User $student) use ($meetings, $grades) {
            $modules = $meetings->map(function ($meeting) use ($student) {
                $submission = $meeting->submissions->firstWhere('student_id', (string) $student->id);
                $moduleScore = $submission && $submission->aslab_score !== null && $submission->laboran_score !== null
                    ? ((float) $submission->aslab_score + (float) $submission->laboran_score) / 2
                    : null;

                return [
                    'meeting_number' => $meeting->meeting_number,
                    'meeting_title' => $meeting->title,
                    'has_submission' => $submission !== null,
                    'is_completed' => (bool) ($submission?->is_completed ?? false),
                    'has_aslab_score' => $submission?->aslab_score !== null,
                    'has_laboran_score' => $submission?->laboran_score !== null,
                    'aslab_score' => $submission?->aslab_score,
                    'laboran_score' => $submission?->laboran_score,
                    'score' => $moduleScore === null ? null : round($moduleScore, 2),
                ];
            });

            $ready = $meetings->isNotEmpty()
                && $meetings->every(fn ($meeting) => $meeting->published_at !== null)
                && $modules->every(fn ($module) => $module['is_completed']
                    && $module['has_aslab_score']
                    && $module['has_laboran_score']);
            $laprak = $ready ? round((float) $modules->avg('score'), 2) : null;
            $grade = $grades->get((string) $student->id);
            $uts = $grade?->uts_score === null ? null : (float) $grade->uts_score;
            $uas = $grade?->uas_score === null ? null : (float) $grade->uas_score;
            $final = $ready && $uts !== null && $uas !== null ? round(($laprak + $uts + $uas) / 3, 2) : null;

            return [
                'student_name' => $student->name,
                'student_id' => $student->id,
                'modules' => $modules,
                'ready' => $ready,
                'laprak' => $laprak,
                'laprak_letter' => GradeScale::letter($laprak),
                'grade' => $grade,
                'uts' => $uts,
                'uts_letter' => GradeScale::letter($uts),
                'uas' => $uas,
                'uas_letter' => GradeScale::letter($uas),
                'final' => $final,
                'final_letter' => GradeScale::letter($final),
            ];
        });

        return view('courses.grades', compact('course', 'meetings', 'rows'));
    }

    public function update(Request $request, Course $course, User $student): RedirectResponse
    {
        $this->authorize('manage', $course);
        abort_unless($request->user()->hasRole('Dosen'), 403);
        abort_unless(! $course->isArchived(), 403, 'Kelas arsip hanya dapat dibaca.');
        abort_unless($student->hasRole('Mahasiswa') && $course->students()->whereKey($student->id)->exists(), 404);

        $validated = $request->validate([
            'uts_score' => ['required', 'numeric', 'between:0,100'],
            'uas_score' => ['required', 'numeric', 'between:0,100'],
        ]);

        abort_unless($this->studentHasCompletedAllModules($course, $student), 422, 'Nilai UTS dan UAS dapat diisi setelah seluruh modul mahasiswa ini selesai dinilai.');

        DB::transaction(fn () => CourseGrade::query()->updateOrCreate(
            ['course_id' => $course->id, 'student_id' => $student->id],
            $validated,
        ));

        return redirect()->route('courses.grades.index', $course)->with('success', "Nilai {$student->name} berhasil disimpan.");
    }

    private function studentHasCompletedAllModules(Course $course, User $student): bool
    {
        $meetings = $course->meetings();

        if (! $meetings->exists() || $meetings->whereNull('published_at')->exists()) {
            return false;
        }

        return ! $course->meetings()
            ->whereDoesntHave('submissions', fn ($query) => $query
                ->where('student_id', $student->id)
                ->where('is_final', false)
                ->where('is_completed', true)
                ->whereNotNull('aslab_score')
                ->whereNotNull('laboran_score'))
            ->exists();
    }
}
