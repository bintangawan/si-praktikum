<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Course;
use App\Models\Semester;
use App\Models\Submission;
use App\Models\User;
use App\Services\DriveLink;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class CourseController extends Controller
{
    public function index(Request $request): View
    {
        $activeSemester = Semester::query()->active()->first();

        if (! $activeSemester) {
            return view('courses.index', ['courses' => collect(), 'activeSemester' => null]);
        }

        $user = $request->user();
        $query = $activeSemester->courses()
            ->with(['dosen:id,name', 'aslab:id,name', 'laboran:id,name'])
            ->withCount('students')
            ->latest();

        if ($user->hasRole(UserRole::MAHASISWA)) {
            $query->whereHas('students', fn ($builder) => $builder->whereKey($user->id));
        } elseif ($user->hasRole(UserRole::DOSEN)) {
            $query->where('dosen_id', $user->id);
        } elseif ($user->hasRole(UserRole::ASLAB)) {
            $query->where('aslab_id', $user->id);
        } elseif ($request->query('view', 'my_classes') !== 'all') {
            $query->where('laboran_id', $user->id);
        }

        return view('courses.index', [
            'courses' => $query->paginate(12)->withQueryString(),
            'activeSemester' => $activeSemester,
        ]);
    }

    public function create(): View
    {
        return view('courses.create', [
            'dosens' => User::query()->where('role', UserRole::DOSEN->value)->whereNotNull('approved_at')->orderBy('name')->get(['id', 'name']),
            'aslabs' => User::query()->where('role', UserRole::ASLAB->value)->whereNotNull('approved_at')->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $activeSemester = Semester::query()->active()->first();

        if (! $activeSemester) {
            return back()->withInput()->with('error', 'Aktifkan semester sebelum membuat kelas.');
        }

        $request->merge([
            'course_name' => preg_replace('/\s+/', ' ', trim((string) $request->input('course_name'))),
            'class_group' => Str::upper(trim((string) $request->input('class_group'))),
        ]);

        $validated = $request->validate([
            'course_name' => ['required', 'string', 'max:255'],
            'class_group' => ['required', 'string', 'max:50'],
            'target_semester' => ['required', 'integer', 'min:1', 'max:14'],
            'dosen_id' => ['required', Rule::exists('users', 'id')->where('role', UserRole::DOSEN->value)->whereNotNull('approved_at')],
            'aslab_id' => ['required', Rule::exists('users', 'id')->where('role', UserRole::ASLAB->value)->whereNotNull('approved_at')],
            'modules' => ['required', 'array', 'min:1', 'max:50'],
            'modules.*.meeting_number' => ['required', 'integer', 'min:1', 'max:50', 'distinct'],
            'modules.*.title' => ['required', 'string', 'max:255'],
            'modules.*.description' => ['nullable', 'string', 'max:10000'],
            'modules.*.module_drive_link' => ['required', 'string', 'max:2048', DriveLink::rule()],
            'modules.*.deadline' => ['nullable', 'date'],
        ]);

        $duplicate = Course::query()
            ->where('semester_id', $activeSemester->id)
            ->where('course_name', $validated['course_name'])
            ->where('class_group', $validated['class_group'])
            ->exists();

        if ($duplicate) {
            return back()->withInput()->with('error', 'Kelas tersebut sudah ada pada semester aktif.');
        }

        $modules = $validated['modules'];
        unset($validated['modules']);

        $course = DB::transaction(function () use ($validated, $modules, $activeSemester, $request): Course {
            $course = Course::query()->create([
                ...$validated,
                'semester_id' => $activeSemester->id,
                'laboran_id' => $request->user()->id,
                'enrollment_code' => $this->uniqueEnrollmentCode(),
            ]);

            $course->meetings()->createMany($modules);

            return $course;
        });

        return redirect()->route('courses.show', $course)
            ->with('success', 'Kelas dan '.count($modules).' modul berhasil dibuat.');
    }

    public function enroll(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'enrollment_code' => ['required', 'string', 'max:20'],
        ]);

        $course = Course::query()
            ->where('enrollment_code', strtoupper(trim($validated['enrollment_code'])))
            ->whereHas('semester', fn ($query) => $query->where('is_active', true))
            ->first();

        if (! $course) {
            return back()->withInput()->withErrors(['enrollment_code' => 'Kode kelas tidak valid atau semester sudah berakhir.']);
        }

        $attached = $request->user()->courses()->syncWithoutDetaching([$course->id]);

        if (empty($attached['attached'])) {
            return back()->with('info', 'Anda sudah bergabung di kelas ini.');
        }

        return redirect()->route('courses.index')->with('success', "Berhasil bergabung ke kelas {$course->course_name}.");
    }

    public function edit(Course $course): View
    {
        $this->authorize('manage', $course);

        return view('courses.edit', [
            'course' => $course,
            'dosens' => User::query()->where('role', UserRole::DOSEN->value)->whereNotNull('approved_at')->orderBy('name')->get(['id', 'name']),
            'aslabs' => User::query()->where('role', UserRole::ASLAB->value)->whereNotNull('approved_at')->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, Course $course): RedirectResponse
    {
        $this->authorize('manage', $course);
        $request->merge([
            'course_name' => preg_replace('/\s+/', ' ', trim((string) $request->input('course_name'))),
            'class_group' => Str::upper(trim((string) $request->input('class_group'))),
        ]);
        $validated = $request->validate([
            'course_name' => ['required', 'string', 'max:255'],
            'class_group' => ['required', 'string', 'max:50'],
            'target_semester' => ['required', 'integer', 'min:1', 'max:14'],
            'dosen_id' => ['required', Rule::exists('users', 'id')->where('role', UserRole::DOSEN->value)->whereNotNull('approved_at')],
            'aslab_id' => ['required', Rule::exists('users', 'id')->where('role', UserRole::ASLAB->value)->whereNotNull('approved_at')],
        ]);

        $duplicate = Course::query()
            ->where('semester_id', $course->semester_id)
            ->where('course_name', $validated['course_name'])
            ->where('class_group', $validated['class_group'])
            ->whereKeyNot($course->id)
            ->exists();

        if ($duplicate) {
            return back()->withInput()->with('error', 'Kelas dengan nama dan kelompok tersebut sudah ada pada semester ini.');
        }

        DB::transaction(function () use ($course, $validated, $request): void {
            $locked = Course::query()->whereKey($course->id)->lockForUpdate()->firstOrFail();

            if ($locked->dosen_id !== $validated['dosen_id'] || $locked->aslab_id !== $validated['aslab_id']) {
                DB::table('course_staff_histories')->insert([
                    'course_id' => $locked->id,
                    'changed_by' => $request->user()->id,
                    'previous_dosen_id' => $locked->dosen_id,
                    'previous_aslab_id' => $locked->aslab_id,
                    'dosen_id' => $validated['dosen_id'],
                    'aslab_id' => $validated['aslab_id'],
                    'created_at' => now(),
                ]);
            }

            $locked->update($validated);
        });

        $course->refresh();

        return redirect()->route('courses.show', $course)->with('success', 'Data kelas berhasil diperbarui.');
    }

    public function destroy(Course $course): RedirectResponse
    {
        $this->authorize('manage', $course);

        $hasAcademicRecords = Submission::query()
            ->where(fn ($query) => $query
                ->whereHas('meeting', fn ($meeting) => $meeting->where('course_id', $course->id))
                ->orWhereHas('finalTask', fn ($task) => $task->where('course_id', $course->id)))
            ->exists()
            || $course->meetings()->whereHas('attendances')->exists();

        if ($hasAcademicRecords) {
            return back()->with('error', 'Kelas tidak dapat dihapus karena sudah memiliki presensi atau laprak. Arsipkan kelas melalui semester agar data akademik tetap aman.');
        }

        $name = $course->course_name.' '.$course->class_group;
        DB::transaction(fn () => $course->delete());

        return redirect()->route('courses.index')->with('success', "Kelas {$name} berhasil dihapus.");
    }

    public function students(Request $request, Course $course): View
    {
        $this->authorize('manage', $course);

        return view('attendance.students', [
            'course' => $course,
            'students' => $course->students()->orderBy('users.id')->get(),
        ]);
    }

    public function searchStudents(Request $request, Course $course): JsonResponse
    {
        $this->authorize('manage', $course);

        $request->merge(['q' => trim((string) $request->input('q'))]);
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:3', 'max:100'],
        ]);

        $term = trim($validated['q']);
        $escaped = str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $term);
        $students = User::query()
            ->where('role', UserRole::MAHASISWA->value)
            ->whereNotNull('approved_at')
            ->whereDoesntHave('courses', fn ($query) => $query->whereKey($course->id))
            ->where(fn ($query) => $query
                ->whereRaw("id LIKE ? ESCAPE '!'", ["%{$escaped}%"])
                ->orWhereRaw("name LIKE ? ESCAPE '!'", ["%{$escaped}%"]))
            ->orderByRaw("CASE WHEN id = ? THEN 0 WHEN id LIKE ? ESCAPE '!' THEN 1 WHEN name LIKE ? ESCAPE '!' THEN 2 ELSE 3 END", [$term, $escaped.'%', $escaped.'%'])
            ->orderBy('name')
            ->orderBy('id')
            ->limit(10)
            ->get(['id', 'name', 'email']);

        return response()->json(['data' => $students]);
    }

    public function addStudent(Request $request, Course $course): RedirectResponse
    {
        $this->authorize('manage', $course);
        $validated = $request->validate([
            'student_id' => [
                'required',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('role', UserRole::MAHASISWA->value)->whereNotNull('approved_at')),
            ],
        ]);

        $student = User::query()->findOrFail($validated['student_id']);
        $attached = $course->students()->syncWithoutDetaching([$student->id]);

        if (empty($attached['attached'])) {
            return back()->with('info', "{$student->name} sudah terdaftar di kelas ini.");
        }

        return back()->with('success', "{$student->name} berhasil ditambahkan ke kelas.");
    }

    public function removeStudent(Request $request, Course $course, User $student): RedirectResponse
    {
        $this->authorize('manage', $course);

        $hasRecords = $student->submissions()
            ->where(fn ($query) => $query
                ->whereHas('meeting', fn ($meeting) => $meeting->where('course_id', $course->id))
                ->orWhereHas('finalTask', fn ($task) => $task->where('course_id', $course->id)))
            ->exists()
            || $student->attendances()->whereHas('meeting', fn ($meeting) => $meeting->where('course_id', $course->id))->exists();

        if ($hasRecords) {
            return back()->with('error', 'Mahasiswa yang sudah memiliki presensi atau pengumpulan tidak dapat dikeluarkan.');
        }

        $course->students()->detach($student->id);

        return back()->with('success', "Mahasiswa {$student->name} berhasil dikeluarkan dari kelas.");
    }

    public function printCard(Request $request, Course $course): Response|RedirectResponse
    {
        $this->authorize('participate', $course);

        $student = $request->user();
        $course->load(['dosen', 'laboran', 'aslab', 'semester']);
        $meetings = $course->meetings()
            ->with([
                'attendances' => fn ($query) => $query->where('student_id', $student->id),
                'submissions' => fn ($query) => $query->where('student_id', $student->id),
            ])->get();

        return Pdf::loadView('courses.practicum_card_pdf', compact('course', 'student', 'meetings'))
            ->setPaper('a4')
            ->stream('Kartu_Praktikum_'.Str::slug($student->name).'.pdf');
    }

    public function editStaff(Course $course): View
    {
        $this->authorize('manage', $course);

        return view('courses.staff', [
            'course' => $course,
            'dosens' => User::where('role', 'Dosen')->whereNotNull('approved_at')->orderBy('name')->get(['id', 'name']),
            'aslabs' => User::where('role', 'Aslab')->whereNotNull('approved_at')->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function updateStaff(Request $request, Course $course): RedirectResponse
    {
        $this->authorize('manage', $course);
        $data = $request->validate([
            'dosen_id' => ['required', Rule::exists('users', 'id')->where('role', 'Dosen')->whereNotNull('approved_at')],
            'aslab_id' => ['required', Rule::exists('users', 'id')->where('role', 'Aslab')->whereNotNull('approved_at')],
        ]);
        DB::transaction(function () use ($course, $data, $request) {
            $locked = Course::whereKey($course->id)->lockForUpdate()->firstOrFail();
            DB::table('course_staff_histories')->insert([
                'course_id' => $course->id, 'changed_by' => $request->user()->id,
                'previous_dosen_id' => $locked->dosen_id, 'previous_aslab_id' => $locked->aslab_id,
                'dosen_id' => $data['dosen_id'], 'aslab_id' => $data['aslab_id'], 'created_at' => now(),
            ]);
            $locked->update($data);
        });

        return redirect()->route('courses.show', $course)->with('success', 'Penugasan kelas diperbarui. Riwayat pemeriksaan sebelumnya tetap tersimpan.');
    }

    private function uniqueEnrollmentCode(): string
    {
        do {
            $code = Str::upper(Str::random(8));
        } while (Course::query()->where('enrollment_code', $code)->exists());

        return $code;
    }
}
