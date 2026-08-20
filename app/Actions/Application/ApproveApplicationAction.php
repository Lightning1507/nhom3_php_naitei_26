<?php

namespace App\Actions\Application;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\User;

class ApproveApplicationAction extends TransitionsApplication
{
    protected function targetStatus(): ApplicationStatus
    {
        return ApplicationStatus::Approved;
    }

    protected function ability(): string
    {
        return 'approve';
    }

    protected function apply(Application $application, User $actor, ?string $note): void
    {
        $application->completed_at = now();
        $application->result_note = $note;
    }
}
