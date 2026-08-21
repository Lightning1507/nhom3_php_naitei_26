<?php

namespace App\Policies;

use App\Enums\ApplicationStatus;
use App\Enums\UserRole;
use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ApplicationDocumentPolicy
{
    public function download(User $user, ApplicationDocument $document): Response|bool
    {
        if (! $user->canAccessProtectedResources()) {
            return false;
        }

        $application = $document->application;

        if ($application === null) {
            return false;
        }

        if (in_array($user->role, [UserRole::Staff, UserRole::Manager, UserRole::SuperAdmin], true)) {
            return Application::query()
                ->visibleTo($user)
                ->whereKey($application->getKey())
                ->exists()
                    ? Response::allow()
                    : Response::denyAsNotFound();
        }

        return $user->id === $application->citizen_id;
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
