<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\ServiceCategory;
use App\Models\ServiceType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServiceType>
 */
class ServiceTypeFactory extends Factory
{
    protected $model = ServiceType::class;

    public function definition(): array
    {
        return [
            'category_id' => ServiceCategory::factory(),
            'responsible_department_id' => Department::factory(),
            'name' => fake()->sentence(3),
            'code' => fake()->unique()->bothify('SRV-####'),
            'description' => fake()->paragraph(),
            'requirements' => fake()->sentence(),
            'form_schema' => [
                [
                    'name' => 'full_name',
                    'label' => 'Full Name',
                    'type' => 'string',
                    'required' => true,
                ],
                [
                    'name' => 'note',
                    'label' => 'Note',
                    'type' => 'string',
                    'required' => false,
                ],
            ],
            'document_requirements' => [
                ['code' => 'citizen_id_copy', 'label' => 'Citizen ID Copy', 'required' => true],
            ],
            'processing_time_days' => fake()->numberBetween(1, 30),
            'fee' => fake()->numberBetween(0, 500000),
            'is_active' => true,
        ];
    }
}
