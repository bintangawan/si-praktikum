<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Course;
use App\Models\User;

class CoursePolicy
{
    public function view(User $user, Course $course): bool
    {
        if ($user->hasRole(UserRole::LABORAN)) {
            return true;
        }

        if ($user->hasRole(UserRole::DOSEN)) {
            return (string) $course->dosen_id === (string) $user->id;
        }

        if ($user->hasRole(UserRole::ASLAB)) {
            return (string) $course->aslab_id === (string) $user->id;
        }

        return $course->students()->whereKey($user->id)->exists();
    }

    public function manage(User $user, Course $course): bool
    {
        return $user->hasRole(UserRole::LABORAN)
            || ($user->hasRole(UserRole::DOSEN) && (string) $course->dosen_id === (string) $user->id)
            || ($user->hasRole(UserRole::ASLAB) && (string) $course->aslab_id === (string) $user->id);
    }

    public function manageModules(User $user, Course $course): bool
    {
        return $user->hasRole(UserRole::LABORAN)
            || ($user->hasRole(UserRole::ASLAB) && (string) $course->aslab_id === (string) $user->id);
    }

    public function participate(User $user, Course $course): bool
    {
        return $user->hasRole(UserRole::MAHASISWA)
            && $course->students()->whereKey($user->id)->exists();
    }
}
