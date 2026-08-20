<?php

namespace Database\Factories;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\ApplicationStatusHistory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ApplicationStatusHistory>
 */
class ApplicationStatusHistoryFactory extends Factory
{
    protected $model = ApplicationStatusHistory::class;

    public function definition(): array
    {
        return [
            'application_id' => Application::factory(),
            'from_status' => null,
            'to_status' => ApplicationStatus::Received,
            'changed_by' => User::factory(),
            'note' => null,
        ];
    }
}
