<?php

namespace App\Policies;

use App\Enums\ApplicationStatus;
use App\Enums\UserRole;
use App\Models\Application;
use App\Models\User;

class ApplicationPolicy
{
    public function create(User $user): bool
    {
        return $user->role === UserRole::Citizen && $user->canAccessProtectedResources();
    }

    public function viewAny(User $user): bool
    {
        return in_array($user->role, [UserRole::Staff, UserRole::Manager, UserRole::SuperAdmin], true)
            && $user->canAccessProtectedResources();
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
        return $user->canAccessProtectedResources()
            && $user->id === $application->citizen_id
            && in_array($application->status, [ApplicationStatus::Received, ApplicationStatus::SupplementRequired], true);
    }

    public function assign(User $user, Application $application): bool
    {
        if (! $this->isActiveInternalUser($user) || $this->isTrashed($application)) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        if (! $user->isManager()) {
            return false;
        }

        $departmentId = $application->serviceType?->responsible_department_id;

        return $departmentId !== null
            && $user->ledDepartments()->whereKey($departmentId)->exists();
    }

    public function claim(User $user, Application $application): bool
    {
        if (! $this->isActiveInternalUser($user) || $this->isTrashed($application)) {
            return false;
        }

        if (! $user->isStaff()) {
            return false;
        }

        $departmentId = $application->serviceType?->responsible_department_id;

        return $departmentId !== null
            && $user->departments()->whereKey($departmentId)->exists();
    }

    public function startProcessing(User $user, Application $application): bool
    {
        return $this->canProcess($user, $application);
    }

    public function requestSupplement(User $user, Application $application): bool
    {
        return $this->canProcess($user, $application);
    }

    public function resume(User $user, Application $application): bool
    {
        return $this->canProcess($user, $application);
    }

    public function approve(User $user, Application $application): bool
    {
        return $this->canProcess($user, $application);
    }

    public function reject(User $user, Application $application): bool
    {
        return $this->canProcess($user, $application);
    }

    public function uploadResultDocument(User $user, Application $application): bool
    {
        return $this->canProcess($user, $application);
    }

    private function canProcess(User $user, Application $application): bool
    {
        if (! $this->isActiveInternalUser($user) || $this->isTrashed($application)) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        return $application->assigned_staff_id === $user->id;
    }

    private function isActiveInternalUser(User $user): bool
    {
        return in_array($user->role, [UserRole::Staff, UserRole::Manager, UserRole::SuperAdmin], true)
            && $user->canAccessProtectedResources();
    }

    private function isTrashed(Application $application): bool
    {
        return $application->trashed();
    }
}
