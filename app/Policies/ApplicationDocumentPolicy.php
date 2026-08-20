<?php

namespace App\Policies;

use App\Enums\ApplicationStatus;
use App\Enums\UserRole;
use App\Models\ApplicationDocument;
use App\Models\User;

class ApplicationDocumentPolicy
{
    public function download(User $user, ApplicationDocument $document): bool
    {
        if (! $user->canAccessProtectedResources()) {
            return false;
        }

        if (in_array($user->role, [UserRole::Staff, UserRole::Manager, UserRole::SuperAdmin], true)) {
            return true;
        }

        return $user->id === $document->application->citizen_id;
    }

    public function delete(User $user, ApplicationDocument $document): bool
    {
        if (! $user->canAccessProtectedResources()) {
            return false;
        }

        if ($user->id !== $document->application->citizen_id) {
            return false;
        }

        return $document->application->status === ApplicationStatus::Received
            && $document->application->assigned_staff_id === null;
    }
}
