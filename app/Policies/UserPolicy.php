<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() && $user->canAccessProtectedResources();
    }

    public function view(User $user, User $target): bool
    {
        return $this->canAdministerUsers($user) || $this->canAccessSelf($user, $target);
    }

    public function update(User $user, User $target): bool
    {
        return $this->canAccessSelf($user, $target);
    }

    public function changeStatus(User $user, User $target): bool
    {
        return $this->canAdministerUsers($user);
    }

    private function canAdministerUsers(User $user): bool
    {
        return $user->isSuperAdmin() && $user->canAccessProtectedResources();
    }

    private function canAccessSelf(User $user, User $target): bool
    {
        return $user->canAccessProtectedResources()
            && $target->canAccessProtectedResources()
            && $user->isCitizen()
            && $target->isCitizen()
            && $user->is($target);
    }
}
