<?php

namespace App\Policies;

use App\Models\Submission;
use App\Models\User;

class SubmissionPolicy
{
    public function update(User $user, Submission $submission): bool
    {
        $course = $submission->meeting?->course ?? $submission->finalTask?->course;

        return $course !== null
            && (string) $submission->student_id === (string) $user->id
            && $user->can('participate', $course)
            && ! $submission->is_completed;
    }

    public function review(User $user, Submission $submission): bool
    {
        $course = $submission->meeting?->course ?? $submission->finalTask?->course;

        return $course !== null && $user->can('manage', $course);
    }
}
