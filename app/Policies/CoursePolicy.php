<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Course;
use App\Models\User;

class CoursePolicy
{
    public function view(User $user, Course $course): bool
    {
        if ($user->hasActiveRole(UserRole::LABORAN)) {
            return true;
        }

        if ($user->hasActiveRole(UserRole::DOSEN)) {
            return (string) $course->dosen_id === (string) $user->id;
        }

        if ($user->hasActiveRole(UserRole::ASLAB)) {
            return (string) $course->aslab_id === (string) $user->id;
        }

        return $course->students()->whereKey($user->id)->exists();
    }

    public function manage(User $user, Course $course): bool
    {
        return $user->hasActiveRole(UserRole::LABORAN)
            || ($user->hasActiveRole(UserRole::DOSEN) && (string) $course->dosen_id === (string) $user->id)
            || ($user->hasActiveRole(UserRole::ASLAB) && (string) $course->aslab_id === (string) $user->id);
    }

    public function participate(User $user, Course $course): bool
    {
        return $user->hasActiveRole(UserRole::MAHASISWA)
            && $course->students()->whereKey($user->id)->exists();
    }
}
