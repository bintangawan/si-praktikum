<?php

namespace App\Http\Controllers;

use App\Models\Submission;
use App\Models\SubmissionHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SubmissionFileController extends Controller
{
    public function submission(Request $request, Submission $submission): StreamedResponse
    {
        $submission->loadMissing(['meeting.course', 'finalTask.course']);
        $this->authorizeFileAccess($request, $submission);

        return $this->pdfResponse(
            $submission->file_path,
            $submission->original_filename,
        );
    }

    public function history(Request $request, SubmissionHistory $history): StreamedResponse
    {
        $history->loadMissing(['submission.meeting.course', 'submission.finalTask.course']);
        $this->authorizeFileAccess($request, $history->submission);

        return $this->pdfResponse(
            $history->file_path,
            $history->original_filename,
        );
    }

    private function authorizeFileAccess(Request $request, Submission $submission): void
    {
        $course = $submission->meeting?->course ?? $submission->finalTask?->course;
        $ownsSubmission = $course
            && (string) $submission->student_id === (string) $request->user()->id
            && $request->user()->can('participate', $course);

        abort_unless($ownsSubmission || $request->user()->can('review', $submission), 403);
    }

    private function pdfResponse(?string $path, ?string $originalFilename): StreamedResponse
    {
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        $filename = preg_replace('/[^A-Za-z0-9._ -]/u', '_', $originalFilename ?? '') ?: 'laporan-praktikum.pdf';

        return Storage::disk('local')->response($path, $filename, [
            'Content-Type' => 'application/pdf',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
