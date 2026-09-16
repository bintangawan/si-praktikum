<?php

namespace App\Http\Controllers;

use App\Exports\AttendanceReportExport;
use App\Models\Attendance;
use App\Models\Course;
use App\Models\Meeting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AttendanceController extends Controller
{
    public function index(Request $request, Meeting $meeting): View
    {
        $this->authorize('manage', $meeting->course);
        $meeting->load('course.students');

        return view('attendance.index', [
            'meeting' => $meeting,
            'existingAttendances' => $meeting->attendances()->get()->keyBy('student_id'),
        ]);
    }

    public function store(Request $request, Meeting $meeting): RedirectResponse
    {
        $this->authorize('manage', $meeting->course);
        $validated = $request->validate([
            'attendances' => ['required', 'array'],
            'attendances.*' => ['required', Rule::in(['H', 'S', 'I', 'TK', 'Hadir', 'Sakit', 'Izin', 'Tanpa Keterangan', 'Alpha'])],
        ]);

        $enrolledIds = $meeting->course->students()->pluck('users.id')->map(fn ($id) => (string) $id);
        $submittedIds = collect(array_keys($validated['attendances']))->map(fn ($id) => (string) $id);

        if ($submittedIds->diff($enrolledIds)->isNotEmpty()) {
            abort(422, 'Data presensi memuat mahasiswa yang tidak terdaftar di kelas.');
        }

        if ($enrolledIds->diff($submittedIds)->isNotEmpty()) {
            abort(422, 'Status presensi wajib diisi untuk seluruh mahasiswa di kelas.');
        }

        DB::transaction(function () use ($meeting, $validated): void {
            foreach ($validated['attendances'] as $studentId => $status) {
                Attendance::query()->updateOrCreate(
                    ['meeting_id' => $meeting->id, 'student_id' => $studentId],
                    ['status' => $this->normalizeStatus($status), 'attendance_date' => now()->toDateString()]
                );
            }
        });

        return back()->with('success', 'Presensi berhasil diperbarui.');
    }

    public function report(Request $request, Course $course): View
    {
        $this->authorize('manage', $course);
        $data = $this->calculateAttendanceReport($course);

        return view('attendance.report', ['course' => $course, ...$data]);
    }

    public function exportPdf(Request $request, Course $course)
    {
        $this->authorize('manage', $course);
        $data = $this->calculateAttendanceReport($course);

        return Pdf::loadView('attendance.report_pdf', ['course' => $course, ...$data])
            ->setPaper('a4', 'landscape')
            ->download('Rekap_Presensi_'.Str::slug($course->course_name).'.pdf');
    }

    public function exportExcel(Request $request, Course $course): BinaryFileResponse
    {
        $this->authorize('manage', $course);
        $data = $this->calculateAttendanceReport($course);

        return Excel::download(
            new AttendanceReportExport($course, $data['report'], $data['meetings']),
            'Rekap_Presensi_'.Str::slug($course->course_name).'.xlsx'
        );
    }

    /** @return array{meetings: Collection, report: Collection} */
    private function calculateAttendanceReport(Course $course): array
    {
        $course->load(['students', 'meetings.attendances']);
        $meetings = $course->meetings;
        $conductedMeetings = $meetings->filter(fn ($meeting) => $meeting->attendances->isNotEmpty());
        $attendanceByStudent = $meetings->flatMap->attendances->groupBy('student_id');

        $report = $course->students->map(function ($student) use ($meetings, $conductedMeetings, $attendanceByStudent) {
            $attendances = $attendanceByStudent->get((string) $student->id, collect())->keyBy('meeting_id');
            $perMeeting = $meetings->mapWithKeys(fn ($meeting) => [
                $meeting->id => $attendances->has($meeting->id)
                    ? $this->statusCode($attendances->get($meeting->id)->status)
                    : ($meeting->attendances->isNotEmpty() ? 'TK' : '-'),
            ])->all();
            $codes = $conductedMeetings->map(fn ($meeting) => $attendances->has($meeting->id)
                ? $this->statusCode($attendances->get($meeting->id)->status)
                : 'TK');
            $hadir = $codes->filter(fn ($status) => $status === 'H')->count();
            $total = $conductedMeetings->count();

            return (object) [
                'id' => $student->id,
                'name' => $student->name,
                'per_meeting_status' => $perMeeting,
                'hadir' => $hadir,
                'sakit' => $codes->filter(fn ($status) => $status === 'S')->count(),
                'izin' => $codes->filter(fn ($status) => $status === 'I')->count(),
                'alpha' => $codes->filter(fn ($status) => $status === 'TK')->count(),
                'total_pertemuan' => $total,
                'percentage' => $total > 0 ? (int) round($hadir / $total * 100) : 0,
            ];
        });

        return compact('meetings', 'report');
    }

    private function normalizeStatus(string $status): string
    {
        return match (strtoupper(trim($status))) {
            'H', 'HADIR' => 'Hadir',
            'S', 'SAKIT' => 'Sakit',
            'I', 'IZIN' => 'Izin',
            default => 'Tanpa Keterangan',
        };
    }

    private function statusCode(string $status): string
    {
        return match (strtoupper(trim($status))) {
            'H', 'HADIR' => 'H',
            'S', 'SAKIT' => 'S',
            'I', 'IZIN' => 'I',
            default => 'TK',
        };
    }
}
