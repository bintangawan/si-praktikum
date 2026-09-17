<?php

namespace App\Http\Controllers;

use App\Enums\SubmissionStatus;
use App\Enums\UserRole;
use App\Models\Course;
use App\Models\FinalTask;
use App\Models\Submission;
use App\Models\SubmissionHistory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class FinalTaskController extends Controller
{
    public function index(Request $request, FinalTask $finalTask): View
    {
        $this->authorize('manage', $finalTask->course);
        $finalTask->load('course.students');

        return view('final-tasks.index', [
            'finalTask' => $finalTask,
            'submissions' => $finalTask->submissions()->where('is_final', true)->with('histories')->get()->keyBy('student_id'),
        ]);
    }

    public function store(Request $request, Course $course): RedirectResponse
    {
        $this->authorize('manage', $course);
        $validated = $request->validate([
            'description' => ['required', 'string', 'max:10000'],
            'deadline' => ['nullable', 'date'],
        ]);

        $course->finalTask()->updateOrCreate([], $validated);

        return back()->with('success', 'Tugas final berhasil diperbarui.');
    }

    public function submit(Request $request, FinalTask $finalTask): RedirectResponse
    {
        $this->authorize('participate', $finalTask->course);
        $validated = $this->validateSubmission($request);
        $existing = $finalTask->submissions()
            ->where('student_id', $request->user()->id)
            ->where('is_final', true)
            ->first();

        if ($existing) {
            return $this->update($request, $existing);
        }

        if ($finalTask->deadline && now()->isAfter($finalTask->deadline)) {
            return back()->withInput()->with('error', 'Waktu pengumpulan sudah ditutup.');
        }

        DB::transaction(function () use ($finalTask, $request, $validated): void {
            $submission = $finalTask->submissions()->create([
                'student_id' => $request->user()->id,
                'submission_link' => $validated['submission_link'],
                'notes' => $validated['notes'] ?? null,
                'is_final' => true,
                'first_upload_at' => now(),
                'last_upload_at' => now(),
                'aslab_status' => SubmissionStatus::PENDING->value,
                'laboran_status' => SubmissionStatus::PENDING->value,
                'dosen_status' => SubmissionStatus::PENDING->value,
                'is_completed' => false,
            ]);
            $this->recordHistory($submission, 'Upload');
        });

        return redirect()->route('courses.show', $finalTask->course_id)->with('success', 'Laporan final berhasil dikumpulkan.');
    }

    public function update(Request $request, Submission $submission): RedirectResponse
    {
        abort_unless($submission->is_final, 404);
        $submission->loadMissing('finalTask.course');
        $this->authorize('update', $submission);
        $validated = $this->validateSubmission($request);
        $isRevision = collect([$submission->aslab_status, $submission->laboran_status, $submission->dosen_status])
            ->contains(SubmissionStatus::REVISION->value);

        if (! $isRevision && $submission->finalTask->deadline && now()->isAfter($submission->finalTask->deadline)) {
            return back()->withInput()->with('error', 'Waktu pengumpulan sudah ditutup.');
        }

        DB::transaction(function () use ($submission, $validated): void {
            $locked = Submission::query()->whereKey($submission->id)->lockForUpdate()->firstOrFail();
            $locked->update([
                'submission_link' => $validated['submission_link'],
                'notes' => $validated['notes'] ?? null,
                'aslab_status' => $locked->aslab_status === SubmissionStatus::APPROVED->value ? SubmissionStatus::APPROVED->value : SubmissionStatus::PENDING->value,
                'laboran_status' => $locked->laboran_status === SubmissionStatus::APPROVED->value ? SubmissionStatus::APPROVED->value : SubmissionStatus::PENDING->value,
                'dosen_status' => SubmissionStatus::PENDING->value,
                'dosen_acc_at' => null,
                'is_completed' => false,
                'last_upload_at' => now(),
            ]);
            $this->recordHistory($locked, 'Revision');
        });

        return redirect()->route('courses.show', $submission->finalTask->course_id)->with('success', 'Perbaikan laporan final berhasil dikirim.');
    }

    public function approve(Request $request, Submission $submission): RedirectResponse
    {
        abort_unless($submission->is_final, 404);
        $submission->loadMissing('finalTask.course');
        $this->authorize('review', $submission);
        $validated = $request->validate([
            'status' => ['required', Rule::in(['ACC', 'REVISI'])],
            'notes' => ['nullable', 'string', 'max:5000', 'required_if:status,REVISI'],
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
                    $locked->dosen_status = SubmissionStatus::PENDING->value;
                    $locked->dosen_acc_at = null;
                }
            } elseif ($role === UserRole::LABORAN) {
                abort_unless($locked->aslab_status === SubmissionStatus::APPROVED->value, 422, 'Menunggu verifikasi Aslab.');
                $locked->laboran_status = $status->value;
                $locked->laboran_acc_at = $status === SubmissionStatus::APPROVED ? now() : null;
                if ($status === SubmissionStatus::REVISION) {
                    $locked->dosen_status = SubmissionStatus::PENDING->value;
                    $locked->dosen_acc_at = null;
                }
            } elseif ($role === UserRole::DOSEN) {
                abort_unless(
                    $locked->aslab_status === SubmissionStatus::APPROVED->value
                    && $locked->laboran_status === SubmissionStatus::APPROVED->value,
                    422,
                    'Menunggu verifikasi Aslab dan Laboran.'
                );
                $locked->dosen_status = $status->value;
                $locked->dosen_acc_at = $status === SubmissionStatus::APPROVED ? now() : null;
            } else {
                abort(403);
            }

            $locked->is_completed = $locked->aslab_status === SubmissionStatus::APPROVED->value
                && $locked->laboran_status === SubmissionStatus::APPROVED->value
                && $locked->dosen_status === SubmissionStatus::APPROVED->value;
            $locked->save();
            $this->recordHistory($locked, $status === SubmissionStatus::APPROVED ? 'ACC' : 'Revision', $validated['notes'] ?? null);
        });

        return back()->with('success', "Status {$validated['status']} berhasil disimpan.");
    }

    public function handler(Request $request, Submission $submission): View
    {
        abort_unless($submission->is_final, 404);
        $submission->loadMissing('finalTask.course');
        $this->authorize('review', $submission);
        $submission->load(['student', 'histories.reviewer']);

        return view('final-tasks.handler', ['submission' => $submission, 'finalTask' => $submission->finalTask]);
    }

    public function manage(Request $request, FinalTask $finalTask): View
    {
        $this->authorize('participate', $finalTask->course);
        $submission = $finalTask->submissions()
            ->where('student_id', $request->user()->id)
            ->where('is_final', true)
            ->with('histories.reviewer')
            ->first();

        return view('mahasiswa.final-tasks.handler', [
            'finalTask' => $finalTask,
            'submission' => $submission,
            'histories' => $submission?->histories ?? collect(),
        ]);
    }

    public function updateDeadline(Request $request, FinalTask $finalTask): RedirectResponse
    {
        $this->authorize('manage', $finalTask->course);
        $finalTask->update($request->validate(['deadline' => ['required', 'date']]));

        return back()->with('success', 'Deadline diperbarui.');
    }

    public function updateDescription(Request $request, FinalTask $finalTask): RedirectResponse
    {
        $this->authorize('manage', $finalTask->course);
        $finalTask->update($request->validate(['description' => ['required', 'string', 'max:10000']]));

        return back()->with('success', 'Deskripsi laporan final berhasil diperbarui.');
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
            'reviewed_by' => in_array($action, ['Upload', 'Revision'], true) && $feedback === null ? null : auth()->id(),
        ]);
    }
}
