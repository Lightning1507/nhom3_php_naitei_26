<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function view(User $user, User $target): bool
    {
        return $this->canAccessSelf($user, $target);
    }

    public function update(User $user, User $target): bool
    {
        return $this->canAccessSelf($user, $target);
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
