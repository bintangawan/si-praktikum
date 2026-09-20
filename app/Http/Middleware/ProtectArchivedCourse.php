<?php

namespace App\Http\Middleware;

use App\Models\Course;
use App\Models\FinalTask;
use App\Models\Meeting;
use App\Models\Submission;
use Closure;
use Illuminate\Http\Request;

class ProtectArchivedCourse
{
    public function handle(Request $request, Closure $next)
    {
        if (! $request->isMethodSafe()) {
            foreach ($request->route()->parameters() as $model) {
                $course = match (true) {
                    $model instanceof Course => $model,
                    $model instanceof Meeting, $model instanceof FinalTask => $model->course,
                    $model instanceof Submission => $model->meeting?->course ?? $model->finalTask?->course,
                    default => null,
                };
                abort_if($course && ! $course->semester->is_active, 403, 'Kelas arsip hanya dapat dibaca. Aktifkan semester untuk melakukan perubahan.');
            }
        }

        return $next($request);
    }
}
