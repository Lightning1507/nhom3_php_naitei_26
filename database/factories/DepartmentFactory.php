<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Department>
 */
class DepartmentFactory extends Factory
{
    protected $model = Department::class;

    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'code' => fake()->unique()->bothify('DEPT-####'),
            'address' => fake()->address(),
            'leader_id' => null,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'deleted_at' => null,
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'deleted_at' => now(),
        ]);
    }

    public function ledBy(?User $leader = null): static
    {
        return $this
            ->state(fn (array $attributes) => [
                'leader_id' => $leader?->getKey() ?? User::factory()->manager(),
            ])
            ->afterCreating(function (Department $department): void {
                $department->users()->syncWithoutDetaching([$department->leader_id]);
            });
    }
}
