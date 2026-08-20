<?php

namespace App\Actions\Application;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\User;

class RejectApplicationAction extends TransitionsApplication
{
    protected function targetStatus(): ApplicationStatus
    {
        return ApplicationStatus::Rejected;
    }

    protected function ability(): string
    {
        return 'reject';
    }

    protected function apply(Application $application, User $actor, ?string $note): void
    {
        $application->completed_at = now();
        $application->rejection_reason = $note;
    }
}
