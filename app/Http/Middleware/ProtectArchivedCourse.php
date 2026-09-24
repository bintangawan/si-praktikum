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
                $courseSettingsRoute = $request->routeIs('courses.update', 'courses.destroy', 'courses.archive');
                abort_if($course && $course->isArchived() && ! $courseSettingsRoute, 403, 'Kelas arsip hanya dapat dibaca.');
            }
        }

        return $next($request);
    }
}
