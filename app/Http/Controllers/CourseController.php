<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Course;
use App\Models\Semester;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
            ->with(['dosen', 'aslab', 'laboran'])
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
            'courses' => $query->get(),
            'activeSemester' => $activeSemester,
        ]);
    }

    public function create(): View
    {
        return view('courses.create', [
            'dosens' => User::query()->where('role', UserRole::DOSEN->value)->orderBy('name')->get(),
            'aslabs' => User::query()->where('role', UserRole::ASLAB->value)->orderBy('name')->get(),
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
            'dosen_id' => ['required', Rule::exists('users', 'id')->where('role', UserRole::DOSEN->value)],
            'aslab_id' => ['required', Rule::exists('users', 'id')->where('role', UserRole::ASLAB->value)],
        ]);

        $duplicate = Course::query()
            ->where('semester_id', $activeSemester->id)
            ->where('course_name', $validated['course_name'])
            ->where('class_group', $validated['class_group'])
            ->exists();

        if ($duplicate) {
            return back()->withInput()->with('error', 'Kelas tersebut sudah ada pada semester aktif.');
        }

        Course::query()->create([
            ...$validated,
            'semester_id' => $activeSemester->id,
            'laboran_id' => $request->user()->id,
            'enrollment_code' => $this->uniqueEnrollmentCode(),
        ]);

        return redirect()->route('courses.index')->with('success', 'Kelas berhasil dibuat.');
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

    public function students(Request $request, Course $course): View
    {
        $this->authorize('manage', $course);

        return view('attendance.students', [
            'course' => $course,
            'students' => $course->students()->orderBy('users.id')->get(),
            'availableStudents' => User::query()
                ->where('role', UserRole::MAHASISWA->value)
                ->whereDoesntHave('courses', fn ($query) => $query->whereKey($course->id))
                ->orderBy('name')
                ->get(['id', 'name', 'email']),
        ]);
    }

    public function addStudent(Request $request, Course $course): RedirectResponse
    {
        $this->authorize('manage', $course);
        $validated = $request->validate([
            'student_id' => [
                'required',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('role', UserRole::MAHASISWA->value)),
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

    private function uniqueEnrollmentCode(): string
    {
        do {
            $code = Str::upper(Str::random(8));
        } while (Course::query()->where('enrollment_code', $code)->exists());

        return $code;
    }
}
