<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Application;
use App\Models\User;

class ApplicationPolicy
{
    public function create(User $user): bool
    {
        return $user->role === UserRole::Citizen;
    }

    public function view(User $user, Application $application): bool
    {
        if (in_array($user->role, [UserRole::Staff, UserRole::Manager, UserRole::SuperAdmin], true)) {
            return true;
        }

        return $user->id === $application->citizen_id;
    }

    public function uploadDocument(User $user, Application $application): bool
    {
        return $user->canAccessProtectedResources() && $user->id === $application->citizen_id;
    }
}
