<?php

namespace Database\Factories;

use App\Models\Application;
use App\Models\ApplicationAssignment;
use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ApplicationAssignment>
 */
class ApplicationAssignmentFactory extends Factory
{
    protected $model = ApplicationAssignment::class;

    public function definition(): array
    {
        return [
            'application_id' => Application::factory(),
            'staff_id' => User::factory(),
            'department_id' => Department::factory(),
            'assigned_by' => User::factory(),
            'assigned_at' => now(),
            'ended_at' => null,
            'note' => null,
        ];
    }

    public function ended(): static
    {
        return $this->state(fn (array $attributes) => [
            'ended_at' => now(),
        ]);
    }
}
