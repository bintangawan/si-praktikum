<?php

namespace App\Http\Controllers;

use App\Enums\SubmissionStatus;
use App\Enums\UserRole;
use App\Models\FinalTask;
use App\Models\Meeting;
use App\Models\Submission;
use App\Models\SubmissionHistory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SubmissionController extends Controller
{
    public function index(Request $request, Meeting $meeting): View
    {
        $this->authorize('manage', $meeting->course);
        $meeting->load('course.students');

        return view('submissions.index', [
            'meeting' => $meeting,
            'submissions' => $meeting->submissions()->where('is_final', false)->with('histories')->get()->keyBy('student_id'),
        ]);
    }

    public function handler(Request $request, Submission $submission): View
    {
        abort_if($submission->is_final, 404);
        $submission->loadMissing('meeting.course');
        $this->authorize('review', $submission);
        $submission->load(['student', 'histories.reviewer']);

        return view('submissions.handler', compact('submission'));
    }

    public function manage(Request $request, Meeting $meeting): View
    {
        $this->authorize('participate', $meeting->course);
        $submission = $meeting->submissions()
            ->where('student_id', $request->user()->id)
            ->where('is_final', false)
            ->with('histories.reviewer')
            ->first();

        return view('mahasiswa.submissions.handler', compact('meeting', 'submission'));
    }

    public function store(Request $request, Meeting $meeting): RedirectResponse
    {
        $this->authorize('participate', $meeting->course);
        $validated = $this->validateSubmission($request);
        $existing = $meeting->submissions()
            ->where('student_id', $request->user()->id)
            ->where('is_final', false)
            ->first();

        if ($existing) {
            return $this->update($request, $existing);
        }

        if ($meeting->deadline && now()->isAfter($meeting->deadline)) {
            return back()->withInput()->with('error', 'Waktu pengumpulan sudah ditutup.');
        }

        DB::transaction(function () use ($meeting, $request, $validated): void {
            $submission = $meeting->submissions()->create([
                'student_id' => $request->user()->id,
                'submission_link' => $validated['submission_link'],
                'notes' => $validated['notes'] ?? null,
                'first_upload_at' => now(),
                'last_upload_at' => now(),
                'aslab_status' => SubmissionStatus::PENDING->value,
                'laboran_status' => SubmissionStatus::PENDING->value,
                'dosen_status' => SubmissionStatus::NOT_APPLICABLE->value,
                'is_completed' => false,
                'is_final' => false,
            ]);
            $this->recordHistory($submission, 'Upload');
        });

        return redirect()->route('courses.show', $meeting->course_id)->with('success', 'Tugas berhasil dikirim.');
    }

    public function update(Request $request, Submission $submission): RedirectResponse
    {
        abort_if($submission->is_final, 404);
        $submission->loadMissing('meeting.course');
        $this->authorize('update', $submission);
        $validated = $this->validateSubmission($request);

        $isRevision = in_array(SubmissionStatus::REVISION->value, [$submission->aslab_status, $submission->laboran_status], true);
        if (! $isRevision && $submission->meeting->deadline && now()->isAfter($submission->meeting->deadline)) {
            return back()->withInput()->with('error', 'Waktu pengumpulan sudah ditutup.');
        }

        DB::transaction(function () use ($submission, $validated): void {
            $locked = Submission::query()->whereKey($submission->id)->lockForUpdate()->firstOrFail();
            $locked->update([
                'submission_link' => $validated['submission_link'],
                'notes' => $validated['notes'] ?? null,
                'aslab_status' => $locked->aslab_status === SubmissionStatus::APPROVED->value
                    ? SubmissionStatus::APPROVED->value : SubmissionStatus::PENDING->value,
                'laboran_status' => SubmissionStatus::PENDING->value,
                'laboran_acc_at' => null,
                'is_completed' => false,
                'last_upload_at' => now(),
            ]);
            $this->recordHistory($locked, 'Revision');
        });

        return redirect()->route('courses.show', $submission->meeting->course_id)->with('success', 'Tugas perbaikan berhasil dikirim.');
    }

    public function approve(Request $request, Submission $submission): RedirectResponse
    {
        abort_if($submission->is_final, 404);
        $submission->loadMissing('meeting.course');
        $this->authorize('review', $submission);
        $validated = $request->validate([
            'status' => ['required', Rule::in(['ACC', 'REVISI'])],
            'feedback' => ['nullable', 'string', 'max:5000', 'required_if:status,REVISI'],
        ]);
        $status = $validated['status'] === 'ACC' ? SubmissionStatus::APPROVED : SubmissionStatus::REVISION;
        $role = UserRole::normalize((string) $request->user()->role);

        DB::transaction(function () use ($submission, $validated, $status, $role): void {
            $locked = Submission::query()->whereKey($submission->id)->lockForUpdate()->firstOrFail();

            if ($role === UserRole::ASLAB) {
                $locked->aslab_status = $status->value;
                $locked->aslab_acc_at = $status === SubmissionStatus::APPROVED ? now() : null;
                if ($status === SubmissionStatus::REVISION) {
                    $locked->laboran_status = SubmissionStatus::PENDING->value;
                    $locked->laboran_acc_at = null;
                }
            } elseif ($role === UserRole::LABORAN) {
                abort_unless($locked->aslab_status === SubmissionStatus::APPROVED->value, 422, 'Menunggu verifikasi Asisten Laboratorium.');
                $locked->laboran_status = $status->value;
                $locked->laboran_acc_at = $status === SubmissionStatus::APPROVED ? now() : null;
            } else {
                abort(403);
            }

            $locked->is_completed = $locked->aslab_status === SubmissionStatus::APPROVED->value
                && $locked->laboran_status === SubmissionStatus::APPROVED->value;
            $locked->save();
            $this->recordHistory($locked, $status === SubmissionStatus::APPROVED ? 'ACC' : 'Revision', $validated['feedback'] ?? null);
        });

        return redirect()->route('submissions.index', $submission->meeting_id)->with('success', "Status {$validated['status']} berhasil disimpan.");
    }

    public function mySubmissions(Request $request): View
    {
        $userId = $request->user()->id;
        $meetings = Meeting::query()->whereHas('course.students', fn ($query) => $query->whereKey($userId))
            ->with(['course', 'submissions' => fn ($query) => $query->where('student_id', $userId)->where('is_final', false)])
            ->get();
        $finalTasks = FinalTask::query()->whereHas('course.students', fn ($query) => $query->whereKey($userId))
            ->with(['course', 'submissions' => fn ($query) => $query->where('student_id', $userId)->where('is_final', true)])
            ->get();

        $allTasks = $meetings->map(fn ($meeting) => (object) [
            'is_final' => false, 'course' => $meeting->course, 'title' => $meeting->title,
            'number' => 'Pertemuan '.$meeting->meeting_number, 'deadline' => $meeting->deadline,
            'submission' => $meeting->submissions->first(),
        ])->concat($finalTasks->map(fn ($task) => (object) [
            'is_final' => true, 'course' => $task->course, 'title' => 'Laporan Final',
            'number' => 'TUGAS FINAL', 'deadline' => $task->deadline,
            'submission' => $task->submissions->first(),
        ]))->sortByDesc('deadline')->values();

        return view('mahasiswa.submissions.index', compact('allTasks'));
    }

    public function updateDeadline(Request $request, Meeting $meeting): RedirectResponse
    {
        $this->authorize('manage', $meeting->course);
        $validated = $request->validate(['deadline' => ['required', 'date']]);
        $meeting->update($validated);

        return back()->with('success', 'Batas waktu pertemuan berhasil diperbarui.');
    }

    public function pending(Request $request): View
    {
        $user = $request->user();
        $role = UserRole::normalize((string) $user->role);
        $query = Submission::query()->with(['student', 'meeting.course', 'finalTask.course']);

        match ($role) {
            UserRole::ASLAB => $query->where('aslab_status', SubmissionStatus::PENDING->value)
                ->where(fn ($q) => $q->whereHas('meeting.course', fn ($c) => $c->where('aslab_id', $user->id))
                    ->orWhereHas('finalTask.course', fn ($c) => $c->where('aslab_id', $user->id))),
            UserRole::LABORAN => $query->where('aslab_status', SubmissionStatus::APPROVED->value)
                ->where('laboran_status', SubmissionStatus::PENDING->value),
            UserRole::DOSEN => $query->where('aslab_status', SubmissionStatus::APPROVED->value)
                ->where('laboran_status', SubmissionStatus::APPROVED->value)
                ->where('dosen_status', SubmissionStatus::PENDING->value)
                ->where('is_final', true)
                ->whereHas('finalTask.course', fn ($c) => $c->where('dosen_id', $user->id)),
            default => abort(403),
        };

        return view('submissions.pending', ['submissions' => $query->latest()->get()]);
    }

    private function validateSubmission(Request $request): array
    {
        return $request->validate([
            'submission_link' => ['required', 'url', 'max:2048'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);
    }

    private function recordHistory(Submission $submission, string $action, ?string $feedback = null): SubmissionHistory
    {
        return $submission->histories()->create([
            'drive_link' => $submission->submission_link,
            'iteration' => $submission->histories()->max('iteration') + 1,
            'feedback' => $feedback,
            'action_type' => $action,
            'reviewed_by' => $action === 'Upload' || $action === 'Revision' && $feedback === null ? null : auth()->id(),
        ]);
    }
}
