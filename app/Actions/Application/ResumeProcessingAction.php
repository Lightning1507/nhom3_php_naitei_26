<?php

namespace App\Actions\Application;

use App\Enums\ApplicationStatus;

class ResumeProcessingAction extends TransitionsApplication
{
    protected function targetStatus(): ApplicationStatus
    {
        return ApplicationStatus::Processing;
    }

    protected function ability(): string
    {
        return 'resume';
    }
}
