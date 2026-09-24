<?php

namespace App\Http\Controllers;

use App\Enums\SubmissionStatus;
use App\Enums\UserRole;
use App\Models\Course;
use App\Models\FinalTask;
use App\Models\Meeting;
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

class SubmissionController extends Controller
{
    public function __construct(private readonly SubmissionFileStorage $fileStorage) {}

    public function index(Request $request, Meeting $meeting): View
    {
        $this->authorize('manage', $meeting->course);
        $meeting->load(['course.semester', 'course.students']);
        $courseMeetings = $meeting->course->meetings()
            ->with(['submissions' => fn ($query) => $query->where('is_final', false)->withCount('histories')])
            ->get();

        return view('submissions.index', [
            'meeting' => $meeting,
            'courseMeetings' => $courseMeetings,
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
        abort_unless($meeting->isPublished(), 404);
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
        abort_unless($meeting->isPublished(), 404);
        $this->authorize('participate', $meeting->course);

        return DB::transaction(function () use ($request, $meeting) {
            $locked = Meeting::query()->whereKey($meeting->id)->lockForUpdate()->firstOrFail();

            return $this->createSubmission($request, $locked);
        });
    }

    private function createSubmission(Request $request, Meeting $meeting): RedirectResponse
    {
        $this->authorize('participate', $meeting->course);
        $validated = $this->validateSubmission($request);
        $existing = $meeting->submissions()
            ->where('student_id', $request->user()->id)
            ->where('is_final', false)
            ->first();

        if ($existing) {
            abort(409, 'Laporan sudah dikirim. Buka ulang halaman untuk mengirim versi baru.');
        }

        if ($meeting->deadline && now()->isAfter($meeting->deadline)) {
            return back()->withInput()->with('error', 'Waktu pengumpulan sudah ditutup.');
        }

        $document = $this->storeSubmissionDocument($request, $meeting->course, $validated);

        try {
            DB::transaction(function () use ($meeting, $request, $validated, $document): void {
                $submission = $meeting->submissions()->create([
                    'student_id' => $request->user()->id,
                    ...$document,
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
        } catch (Throwable $exception) {
            $this->fileStorage->delete($document['file_path']);

            throw $exception;
        }

        return redirect()->route('courses.show', $meeting->course)->with('success', 'Tugas berhasil dikirim.');
    }

    public function update(Request $request, Submission $submission): RedirectResponse
    {
        abort_if($submission->is_final, 404);
        $submission->loadMissing('meeting.course');
        $this->authorize('update', $submission);
        $validated = [...$this->validateSubmission($request), ...$request->validate(['document_version' => ['required', 'integer', 'min:1']])];

        $canResubmit = $submission->canResubmit();
        if (! $canResubmit && $submission->meeting->deadline && now()->isAfter($submission->meeting->deadline)) {
            return back()->withInput()->with('error', 'Waktu pengumpulan sudah ditutup.');
        }

        $document = $this->storeSubmissionDocument($request, $submission->meeting->course, $validated);

        try {
            DB::transaction(function () use ($submission, $validated, $document): void {
                $locked = Submission::query()->whereKey($submission->id)->lockForUpdate()->firstOrFail();
                $this->authorize('update', $locked);
                abort_unless((int) $validated['document_version'] === (int) $locked->document_version, 409, 'Versi dokumen berubah. Buka ulang halaman sebelum mengirim.');
                abort_if(! $locked->canResubmit() && $locked->meeting->deadline && now()->isAfter($locked->meeting->deadline), 422, 'Waktu pengumpulan sudah ditutup.');
                $locked->update([
                    ...$document,
                    'notes' => $validated['notes'] ?? null,
                    'aslab_status' => SubmissionStatus::PENDING->value,
                    'aslab_acc_at' => null,
                    'document_version' => $locked->document_version + 1,
                    'laboran_status' => SubmissionStatus::PENDING->value,
                    'laboran_acc_at' => null,
                    'aslab_score' => null,
                    'laboran_score' => null,
                    'is_completed' => false,
                    'last_upload_at' => now(),
                ]);
                $this->recordHistory($locked, 'Revision');
            });
        } catch (Throwable $exception) {
            $this->fileStorage->delete($document['file_path']);

            throw $exception;
        }

        return redirect()->route('courses.show', $submission->meeting->course)->with('success', 'Tugas perbaikan berhasil dikirim.');
    }

    public function approve(Request $request, Submission $submission): RedirectResponse
    {
        abort_if($submission->is_final, 404);
        $submission->loadMissing('meeting.course');
        $this->authorize('review', $submission);
        $validated = $request->validate([
            'status' => ['required', Rule::in(['ACC', 'REVISI', 'DITOLAK'])],
            'document_version' => ['required', 'integer', 'min:1'],
            'feedback' => ['nullable', 'string', 'max:5000', Rule::requiredIf(fn () => in_array($request->input('status'), ['REVISI', 'DITOLAK'], true))],
            'score' => ['nullable', 'numeric', 'between:0,100', Rule::requiredIf(fn () => $request->input('status') === 'ACC')],
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
                abort_unless($locked->aslab_status !== SubmissionStatus::APPROVED->value, 409, 'Laporan sudah mendapat ACC Aslab.');
                $locked->aslab_status = $status->value;
                $locked->aslab_acc_at = $status === SubmissionStatus::APPROVED ? now() : null;
                $locked->aslab_score = $status === SubmissionStatus::APPROVED ? $validated['score'] : null;
                if (in_array($status, [SubmissionStatus::REVISION, SubmissionStatus::REJECTED], true)) {
                    $locked->laboran_status = SubmissionStatus::PENDING->value;
                    $locked->laboran_acc_at = null;
                    $locked->laboran_score = null;
                }
            } elseif ($role === UserRole::LABORAN) {
                abort_unless($locked->aslab_status === SubmissionStatus::APPROVED->value, 422, 'Menunggu verifikasi Asisten Laboratorium.');
                abort_unless($locked->laboran_status !== SubmissionStatus::APPROVED->value, 409, 'Laporan sudah mendapat ACC Laboran.');
                $locked->laboran_status = $status->value;
                $locked->laboran_acc_at = $status === SubmissionStatus::APPROVED ? now() : null;
                $locked->laboran_score = $status === SubmissionStatus::APPROVED ? $validated['score'] : null;
            } else {
                abort(403);
            }

            $locked->is_completed = $locked->aslab_status === SubmissionStatus::APPROVED->value
                && $locked->laboran_status === SubmissionStatus::APPROVED->value;
            $locked->save();
            $action = match ($status) {
                SubmissionStatus::APPROVED => 'ACC',
                SubmissionStatus::REJECTED => 'Rejected',
                default => 'Revision',
            };
            $this->recordHistory($locked, $action, $validated['feedback'] ?? null);
        });

        return redirect()->route('submissions.index', $submission->meeting_id)->with('success', "Status {$validated['status']} berhasil disimpan.");
    }

    public function score(Request $request, Submission $submission): RedirectResponse
    {
        abort_if($submission->is_final, 404);
        $submission->loadMissing('meeting.course.semester');
        $this->authorize('review', $submission);
        abort_unless(! $submission->meeting->course->isArchived(), 403, 'Kelas arsip hanya dapat dibaca.');
        abort_unless($submission->is_completed, 422, 'Nilai dapat diberikan setelah laporan mendapat ACC dari Aslab dan Laboran.');

        $validated = $request->validate(['score' => ['required', 'numeric', 'between:0,100']]);
        $role = UserRole::normalize((string) $request->user()->role);
        abort_unless(in_array($role, [UserRole::ASLAB, UserRole::LABORAN], true), 403);

        DB::transaction(function () use ($submission, $validated, $role): void {
            $locked = Submission::query()->whereKey($submission->id)->lockForUpdate()->firstOrFail();
            abort_unless($locked->is_completed && ! $locked->is_final, 422, 'Laporan belum selesai diverifikasi.');
            $locked->update([$role === UserRole::ASLAB ? 'aslab_score' : 'laboran_score' => $validated['score']]);
        });

        return back()->with('success', 'Nilai modul berhasil disimpan.');
    }

    public function mySubmissions(Request $request): View
    {
        $userId = $request->user()->id;
        $archived = $request->boolean('archive');
        $courseArchiveFilter = fn ($query) => $query->where(fn ($state) => $archived
            ? $state->where('is_archived', true)->orWhereHas('semester', fn ($semester) => $semester->where('is_active', false))
            : $state->where('is_archived', false)->whereHas('semester', fn ($semester) => $semester->where('is_active', true)));
        $meetings = Meeting::query()->whereNotNull('published_at')->whereHas('course', $courseArchiveFilter)->whereHas('course.students', fn ($query) => $query->whereKey($userId))
            ->with(['course', 'submissions' => fn ($query) => $query->where('student_id', $userId)->where('is_final', false)])
            ->get();
        $finalTasks = FinalTask::query()->whereHas('course', $courseArchiveFilter)->whereHas('course.students', fn ($query) => $query->whereKey($userId))
            ->with(['course', 'submissions' => fn ($query) => $query->where('student_id', $userId)->where('is_final', true)])
            ->get();

        $allTasks = $meetings->map(fn ($meeting) => (object) [
            'manage_url' => route('mahasiswa.submissions.manage', $meeting),
            'is_final' => false, 'course' => $meeting->course, 'title' => $meeting->title,
            'number' => 'Pertemuan '.$meeting->meeting_number, 'deadline' => $meeting->deadline,
            'submission' => $meeting->submissions->first(),
        ])->concat($finalTasks->map(fn ($task) => (object) [
            'manage_url' => route('mahasiswa.final-tasks.manage', $task),
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
        $activeCourseSubmission = fn ($course) => $course->where('is_archived', false)->whereHas('semester', fn ($semester) => $semester->where('is_active', true));
        $query = Submission::query()->with(['student', 'meeting.course', 'finalTask.course'])->where(fn ($q) => $q
            ->whereHas('meeting.course', $activeCourseSubmission)
            ->orWhereHas('finalTask.course', $activeCourseSubmission));

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

        return view('submissions.pending', ['submissions' => $query->latest()->paginate(25)->withQueryString()]);
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
            'reviewed_by' => $action === 'Upload' || $action === 'Revision' && $feedback === null ? null : auth()->id(),
        ]);
    }
}
