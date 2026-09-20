<?php

namespace App\Services;

use App\Models\Course;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class SubmissionFileStorage
{
    /** @return array{file_path: string, original_filename: string, file_size: int} */
    public function store(UploadedFile $file, Course $course, User $student): array
    {
        $directory = "submissions/{$course->id}/{$student->id}";
        $path = $file->storeAs($directory, Str::uuid().'.pdf', 'local');

        if ($path === false) {
            throw new RuntimeException('PDF laporan gagal disimpan.');
        }

        return [
            'file_path' => $path,
            'original_filename' => Str::limit($file->getClientOriginalName(), 255, ''),
            'file_size' => (int) $file->getSize(),
        ];
    }

    public function delete(?string $path): void
    {
        if ($path) {
            Storage::disk('local')->delete($path);
        }
    }
}
