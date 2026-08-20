<?php

namespace App\Actions\Application;

use App\Enums\ApplicationStatus;

class RequestSupplementAction extends TransitionsApplication
{
    protected function targetStatus(): ApplicationStatus
    {
        return ApplicationStatus::SupplementRequired;
    }

    protected function ability(): string
    {
        return 'requestSupplement';
    }
}
