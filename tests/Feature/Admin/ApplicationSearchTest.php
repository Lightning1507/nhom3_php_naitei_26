<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\Application;
use App\Models\Department;
use App\Models\ServiceType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplicationSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_list_and_filter_options_are_limited_to_current_assignments(): void
    {
        [$departmentA, $serviceA] = $this->serviceContext('STAFF-A');
        [$departmentB, $serviceB] = $this->serviceContext('STAFF-B');
        $staffA = User::factory()->staff()->create(['name' => 'Staff A']);
        $staffB = User::factory()->staff()->create(['name' => 'Staff B']);
        $departmentA->users()->attach($staffA);
        $departmentB->users()->attach($staffB);

        $visible = Application::factory()->assignedTo($staffA)->create([
            'application_code' => 'HS-STAFF-A',
            'service_type_id' => $serviceA->id,
        ]);
        Application::factory()->assignedTo($staffB)->create([
            'application_code' => 'HS-STAFF-B',
            'service_type_id' => $serviceB->id,
        ]);
        Application::factory()->create([
            'application_code' => 'HS-UNASSIGNED',
            'service_type_id' => $serviceA->id,
            'assigned_staff_id' => null,
        ]);

        $this->actingAs($staffA)
            ->get(route('admin.applications.index'))
            ->assertOk()
            ->assertViewHas('applications', fn ($applications): bool => $applications->modelKeys() === [$visible->id])
            ->assertViewHas('serviceOptions', fn ($options): bool => $options->modelKeys() === [$serviceA->id])
            ->assertViewHas('departmentOptions', fn ($options): bool => $options->modelKeys() === [$departmentA->id])
            ->assertViewHas('staffOptions', fn ($options): bool => $options->modelKeys() === [$staffA->id]);
    }

    public function test_manager_sees_union_of_led_departments_without_duplicates(): void
    {
        $manager = User::factory()->manager()->create();
        [$departmentA, $serviceA] = $this->serviceContext('MANAGER-A', $manager);
        [$departmentB, $serviceB] = $this->serviceContext('MANAGER-B', $manager);
        [, $outsideService] = $this->serviceContext('OUTSIDE');

        $applicationA = Application::factory()->create([
            'application_code' => 'HS-MANAGER-A',
            'service_type_id' => $serviceA->id,
        ]);
        $applicationB = Application::factory()->create([
            'application_code' => 'HS-MANAGER-B',
            'service_type_id' => $serviceB->id,
        ]);
        Application::factory()->create([
            'application_code' => 'HS-OUTSIDE',
            'service_type_id' => $outsideService->id,
        ]);

        $this->actingAs($manager)
            ->get(route('admin.applications.index', ['sort' => 'code_asc']))
            ->assertOk()
            ->assertViewHas('applications', function ($applications) use ($applicationA, $applicationB): bool {
                return $applications->modelKeys() === [$applicationA->id, $applicationB->id]
                    && $applications->unique('id')->count() === 2;
            })
            ->assertViewHas('departmentOptions', function ($options) use ($departmentA, $departmentB): bool {
                return $options->modelKeys() === [$departmentA->id, $departmentB->id];
            });
    }

    public function test_super_admin_sees_all_non_archived_applications(): void
    {
        $superAdmin = User::factory()->withRole(UserRole::SuperAdmin)->create();
        $visible = Application::factory()->count(2)->create();
        $archived = Application::factory()->create();
        $archived->delete();

        $this->actingAs($superAdmin)
            ->get(route('admin.applications.index'))
            ->assertOk()
            ->assertViewHas('applications', function ($applications) use ($visible, $archived): bool {
                return $applications->total() === 2
                    && $applications->pluck('id')->sort()->values()->all() === $visible->modelKeys()
                    && ! $applications->pluck('id')->contains($archived->id);
            });
    }

    public function test_guest_citizen_and_inactive_internal_user_cannot_open_worklist(): void
    {
        $citizen = User::factory()->create();
        $inactiveStaff = User::factory()->staff()->inactive()->create();

        $this->get(route('admin.applications.index'))
            ->assertRedirect(route('admin.login'));

        $this->actingAs($citizen)
            ->get(route('admin.applications.index'))
            ->assertForbidden();

        $this->actingAs($inactiveStaff)
            ->get(route('admin.applications.index'))
            ->assertForbidden();
    }

    /** @return array{Department, ServiceType} */
    private function serviceContext(string $suffix, ?User $manager = null): array
    {
        $department = Department::factory()->create([
            'name' => "Department {$suffix}",
            'code' => "DEP-{$suffix}",
            'leader_id' => $manager?->id,
        ]);

        if ($manager !== null) {
            $department->users()->attach($manager);
        }

        $service = ServiceType::factory()->create([
            'name' => "Service {$suffix}",
            'code' => "SRV-{$suffix}",
            'responsible_department_id' => $department->id,
        ]);

        return [$department, $service];
    }
}
