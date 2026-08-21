<?php

namespace Tests\Feature\Admin;

use App\Enums\ApplicationStatus;
use App\Enums\UserRole;
use App\Models\Application;
use App\Models\Department;
use App\Models\ServiceType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplicationFilterValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_keyword_search_matches_each_supported_field_case_insensitively(): void
    {
        $actor = User::factory()->withRole(UserRole::SuperAdmin)->create();
        $citizen = User::factory()->create([
            'name' => 'Nguyễn Văn Minh',
            'citizen_id' => 'CCCD-F07-0001',
        ]);
        $service = ServiceType::factory()->create(['name' => 'Cấp phép xây dựng đặc biệt']);
        $target = Application::factory()->create([
            'application_code' => 'HS-F07-TARGET-001',
            'citizen_id' => $citizen->id,
            'service_type_id' => $service->id,
        ]);
        $other = Application::factory()->create(['application_code' => 'HS-F07-OTHER-001']);

        foreach ([' target-001 ', 'nguyễn văn', 'cccd-f07', 'XÂY DỰNG ĐẶC BIỆT'] as $keyword) {
            $this->actingAs($actor)
                ->get(route('admin.applications.index', ['q' => $keyword]))
                ->assertOk()
                ->assertViewHas('applications', fn ($applications): bool => $applications->modelKeys() === [$target->id])
                ->assertDontSee($other->application_code);
        }
    }

    public function test_keyword_percent_underscore_and_backslash_are_literal_characters(): void
    {
        $actor = User::factory()->withRole(UserRole::SuperAdmin)->create();
        $literal = Application::factory()->create(['application_code' => 'HS-LITERAL-%_\\-01']);
        $wildcardLookalike = Application::factory()->create(['application_code' => 'HS-LITERAL-XXZ-01']);

        $this->actingAs($actor)
            ->get(route('admin.applications.index', ['q' => '%_\\']))
            ->assertOk()
            ->assertViewHas('applications', fn ($applications): bool => $applications->modelKeys() === [$literal->id])
            ->assertDontSee($wildcardLookalike->application_code);
    }

    public function test_all_application_filters_use_intersection_and_inclusive_dates(): void
    {
        $this->travelTo('2026-08-21 12:00:00');

        $actor = User::factory()->withRole(UserRole::SuperAdmin)->create();
        $department = Department::factory()->create();
        $staff = User::factory()->staff()->create();
        $department->users()->attach($staff);
        $service = ServiceType::factory()->create([
            'responsible_department_id' => $department->id,
            'processing_time_days' => 2,
        ]);
        $target = Application::factory()->assignedTo($staff)->withStatus(ApplicationStatus::Processing)->create([
            'application_code' => 'HS-INTERSECTION-TARGET',
            'service_type_id' => $service->id,
            'submitted_at' => '2026-08-10 23:59:59',
        ]);
        Application::factory()->assignedTo($staff)->withStatus(ApplicationStatus::Received)->create([
            'application_code' => 'HS-WRONG-STATUS',
            'service_type_id' => $service->id,
            'submitted_at' => '2026-08-10 12:00:00',
        ]);
        Application::factory()->assignedTo($staff)->withStatus(ApplicationStatus::Processing)->create([
            'application_code' => 'HS-WRONG-DATE',
            'service_type_id' => $service->id,
            'submitted_at' => '2026-08-11 00:00:00',
        ]);

        $this->actingAs($actor)
            ->get(route('admin.applications.index', [
                'q' => 'INTERSECTION',
                'status' => ApplicationStatus::Processing->value,
                'service_type_id' => $service->id,
                'department_id' => $department->id,
                'assigned_staff_id' => $staff->id,
                'submitted_from' => '2026-08-10',
                'submitted_to' => '2026-08-10',
                'overdue' => '1',
            ]))
            ->assertOk()
            ->assertViewHas('applications', fn ($applications): bool => $applications->modelKeys() === [$target->id]);
    }

    public function test_completed_filter_groups_only_approved_and_rejected(): void
    {
        $actor = User::factory()->withRole(UserRole::SuperAdmin)->create();
        $approved = Application::factory()->withStatus(ApplicationStatus::Approved)->create();
        $rejected = Application::factory()->withStatus(ApplicationStatus::Rejected)->create();
        $processing = Application::factory()->withStatus(ApplicationStatus::Processing)->create();

        $this->actingAs($actor)
            ->get(route('admin.applications.index', ['status' => 'completed', 'sort' => 'oldest']))
            ->assertOk()
            ->assertViewHas('applications', function ($applications) use ($approved, $rejected, $processing): bool {
                return $applications->pluck('id')->sort()->values()->all() === collect([$approved->id, $rejected->id])->sort()->values()->all()
                    && ! $applications->pluck('id')->contains($processing->id);
            });
    }

    public function test_invalid_and_out_of_scope_filters_are_rejected_without_leaking_labels(): void
    {
        $manager = User::factory()->manager()->create();
        $managedDepartment = Department::factory()->create(['leader_id' => $manager->id]);
        $managedDepartment->users()->attach($manager);
        ServiceType::factory()->create(['responsible_department_id' => $managedDepartment->id]);

        $outsideDepartment = Department::factory()->create(['name' => 'Protected Department Label']);
        $outsideStaff = User::factory()->staff()->create(['name' => 'Protected Staff Label']);
        $outsideDepartment->users()->attach($outsideStaff);
        $outsideService = ServiceType::factory()->create([
            'name' => 'Protected Service Label',
            'responsible_department_id' => $outsideDepartment->id,
        ]);
        Application::factory()->assignedTo($outsideStaff)->create(['service_type_id' => $outsideService->id]);

        $this->actingAs($manager)
            ->from(route('admin.applications.index'))
            ->get(route('admin.applications.index', [
                'q' => str_repeat('a', 101),
                'status' => 'unknown',
                'service_type_id' => $outsideService->id,
                'department_id' => $outsideDepartment->id,
                'assigned_staff_id' => $outsideStaff->id,
                'submitted_from' => '2026-08-20',
                'submitted_to' => '2026-08-10',
                'overdue' => 'sometimes',
                'sort' => 'raw_sql',
                'page' => 0,
            ]))
            ->assertRedirect(route('admin.applications.index'))
            ->assertSessionHasErrors([
                'q',
                'status',
                'service_type_id',
                'department_id',
                'assigned_staff_id',
                'submitted_to',
                'overdue',
                'sort',
                'page',
            ])
            ->assertDontSee('Protected Department Label')
            ->assertDontSee('Protected Staff Label')
            ->assertDontSee('Protected Service Label');
    }
}
