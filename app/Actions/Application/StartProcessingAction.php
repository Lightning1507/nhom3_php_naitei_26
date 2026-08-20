<?php

namespace App\Actions\Application;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\User;

class StartProcessingAction extends TransitionsApplication
{
    protected function targetStatus(): ApplicationStatus
    {
        return ApplicationStatus::Processing;
    }

    protected function ability(): string
    {
        return 'startProcessing';
    }

    protected function apply(Application $application, User $actor, ?string $note): void
    {
        if ($application->processing_started_at === null) {
            $application->processing_started_at = now();
        }
    }
}
