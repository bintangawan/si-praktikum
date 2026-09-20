<?php

namespace App\Http\Controllers;

use App\Enums\SubmissionStatus;
use App\Enums\UserRole;
use App\Models\Course;
use App\Models\FinalTask;
use App\Models\Submission;
use App\Models\SubmissionHistory;
use App\Services\DriveLink;
use App\Services\SubmissionFileStorage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class FinalTaskController extends Controller
{
    public function __construct(private readonly SubmissionFileStorage $fileStorage) {}

    public function index(Request $request, FinalTask $finalTask): View
    {
        $this->authorize('manage', $finalTask->course);
        $finalTask->load('course.students');

        return view('final-tasks.index', [
            'finalTask' => $finalTask,
            'submissions' => $finalTask->submissions()->where('is_final', true)->withCount('histories')->get()->keyBy('student_id'),
        ]);
    }

    public function submit(Request $request, FinalTask $finalTask): RedirectResponse
    {
        $this->authorize('participate', $finalTask->course);

        return DB::transaction(function () use ($request, $finalTask) {
            $locked = FinalTask::query()->whereKey($finalTask->id)->lockForUpdate()->firstOrFail();

            return $this->createSubmission($request, $locked);
        });
    }

    private function createSubmission(Request $request, FinalTask $finalTask): RedirectResponse
    {
        $this->authorize('participate', $finalTask->course);
        $validated = $this->validateSubmission($request);
        $existing = $finalTask->submissions()
            ->where('student_id', $request->user()->id)
            ->where('is_final', true)
            ->first();

        if ($existing) {
            abort(409, 'Laporan sudah dikirim. Buka ulang halaman untuk mengirim versi baru.');
        }

        if ($finalTask->deadline && now()->isAfter($finalTask->deadline)) {
            return back()->withInput()->with('error', 'Waktu pengumpulan sudah ditutup.');
        }

        $document = $this->storeSubmissionDocument($request, $finalTask->course, $validated);

        try {
            DB::transaction(function () use ($finalTask, $request, $validated, $document): void {
                $submission = $finalTask->submissions()->create([
                    'student_id' => $request->user()->id,
                    ...$document,
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
        } catch (Throwable $exception) {
            $this->fileStorage->delete($document['file_path']);

            throw $exception;
        }

        return redirect()->route('courses.show', $finalTask->course)->with('success', 'Laporan final berhasil dikumpulkan.');
    }

    public function update(Request $request, Submission $submission): RedirectResponse
    {
        abort_unless($submission->is_final, 404);
        $submission->loadMissing('finalTask.course');
        $this->authorize('update', $submission);
        $validated = [...$this->validateSubmission($request), ...$request->validate(['document_version' => ['required', 'integer', 'min:1']])];
        $isRevision = $submission->canResubmit();

        if (! $isRevision && $submission->finalTask->deadline && now()->isAfter($submission->finalTask->deadline)) {
            return back()->withInput()->with('error', 'Waktu pengumpulan sudah ditutup.');
        }

        $document = $this->storeSubmissionDocument($request, $submission->finalTask->course, $validated);

        try {
            DB::transaction(function () use ($submission, $validated, $document): void {
                $locked = Submission::query()->whereKey($submission->id)->lockForUpdate()->firstOrFail();
                $this->authorize('update', $locked);
                abort_unless((int) $validated['document_version'] === (int) $locked->document_version, 409, 'Versi dokumen berubah. Buka ulang halaman sebelum mengirim.');
                abort_if(! $locked->canResubmit() && $locked->finalTask->deadline && now()->isAfter($locked->finalTask->deadline), 422, 'Waktu pengumpulan sudah ditutup.');
                $locked->update([
                    ...$document,
                    'notes' => $validated['notes'] ?? null,
                    'aslab_status' => SubmissionStatus::PENDING->value,
                    'aslab_acc_at' => null,
                    'document_version' => $locked->document_version + 1,
                    'laboran_status' => SubmissionStatus::PENDING->value,
                    'laboran_acc_at' => null,
                    'dosen_status' => SubmissionStatus::PENDING->value,
                    'dosen_acc_at' => null,
                    'is_completed' => false,
                    'last_upload_at' => now(),
                ]);
                $this->recordHistory($locked, 'Revision');
            });
        } catch (Throwable $exception) {
            $this->fileStorage->delete($document['file_path']);

            throw $exception;
        }

        return redirect()->route('courses.show', $submission->finalTask->course)->with('success', 'Perbaikan laporan final berhasil dikirim.');
    }

    public function approve(Request $request, Submission $submission): RedirectResponse
    {
        abort_unless($submission->is_final, 404);
        $submission->loadMissing('finalTask.course');
        $this->authorize('review', $submission);
        $validated = $request->validate([
            'status' => ['required', Rule::in(['ACC', 'REVISI', 'DITOLAK'])],
            'document_version' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:5000', Rule::requiredIf(fn () => in_array($request->input('status'), ['REVISI', 'DITOLAK'], true))],
        ]);
        $status = match ($validated['status']) {
            'ACC' => SubmissionStatus::APPROVED,
            'DITOLAK' => SubmissionStatus::REJECTED,
            default => SubmissionStatus::REVISION,
        };
        $role = UserRole::normalize((string) $request->user()->role);

        DB::transaction(function () use ($submission, $validated, $status, $role): void {
            $locked = Submission::query()->whereKey($submission->id)->lockForUpdate()->firstOrFail();
            abort_if($locked->is_completed, 409, 'Laporan telah selesai diverifikasi.');
            abort_unless((int) $validated['document_version'] === (int) $locked->document_version, 409, 'Versi dokumen berubah. Buka ulang laporan sebelum memeriksa.');

            if ($role === UserRole::ASLAB) {
                $locked->aslab_status = $status->value;
                $locked->aslab_acc_at = $status === SubmissionStatus::APPROVED ? now() : null;
                if (in_array($status, [SubmissionStatus::REVISION, SubmissionStatus::REJECTED], true)) {
                    $locked->laboran_status = SubmissionStatus::PENDING->value;
                    $locked->laboran_acc_at = null;
                    $locked->dosen_status = SubmissionStatus::PENDING->value;
                    $locked->dosen_acc_at = null;
                }
            } elseif ($role === UserRole::LABORAN) {
                abort_unless($locked->aslab_status === SubmissionStatus::APPROVED->value, 422, 'Menunggu verifikasi Aslab.');
                $locked->laboran_status = $status->value;
                $locked->laboran_acc_at = $status === SubmissionStatus::APPROVED ? now() : null;
                if (in_array($status, [SubmissionStatus::REVISION, SubmissionStatus::REJECTED], true)) {
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
            $action = match ($status) {
                SubmissionStatus::APPROVED => 'ACC',
                SubmissionStatus::REJECTED => 'Rejected',
                default => 'Revision',
            };
            $this->recordHistory($locked, $action, $validated['notes'] ?? null);
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
            'submission_file' => ['prohibited'],
            'submission_link' => ['required', 'string', 'max:2048', DriveLink::rule()],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array{submission_link: ?string, file_path: ?string, original_filename: ?string, file_size: ?int}
     */
    private function storeSubmissionDocument(Request $request, Course $course, array $validated): array
    {
        return [
            'submission_link' => $validated['submission_link'],
            'file_path' => null,
            'original_filename' => null,
            'file_size' => null,
        ];
    }

    private function recordHistory(Submission $submission, string $action, ?string $feedback = null): SubmissionHistory
    {
        return $submission->histories()->create([
            'document_version' => $submission->document_version ?? 1,
            'drive_link' => $submission->submission_link ?? '',
            'file_path' => $submission->file_path,
            'original_filename' => $submission->original_filename,
            'file_size' => $submission->file_size,
            'iteration' => $submission->histories()->max('iteration') + 1,
            'feedback' => $feedback,
            'action_type' => $action,
            'reviewed_by' => in_array($action, ['Upload', 'Revision'], true) && $feedback === null ? null : auth()->id(),
        ]);
    }
}
