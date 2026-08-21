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

class ApplicationAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private Department $department;

    private User $manager;

    private User $staff;

    private User $superAdmin;

    private ServiceType $service;

    private Application $application;

    protected function setUp(): void
    {
        parent::setUp();

        $this->department = Department::factory()->create();
        $this->manager = User::factory()->manager()->create();
        $this->department->update(['leader_id' => $this->manager->id]);
        $this->department->users()->syncWithoutDetaching([$this->manager->id]);

        $this->staff = User::factory()->staff()->create();
        $this->department->users()->syncWithoutDetaching([$this->staff->id]);

        $this->superAdmin = User::factory()->withRole(UserRole::SuperAdmin)->create();

        $this->service = ServiceType::factory()->create([
            'responsible_department_id' => $this->department->id,
            'processing_time_days' => 5,
        ]);

        $this->application = Application::factory()->create([
            'service_type_id' => $this->service->id,
        ]);
    }

    public function test_staff_from_other_department_cannot_process_application(): void
    {
        $otherDepartment = Department::factory()->create();
        $otherStaff = User::factory()->staff()->create();
        $otherDepartment->users()->syncWithoutDetaching([$otherStaff->id]);

        $this->application->update(['assigned_staff_id' => $this->staff->id]);

        $this->actingAs($otherStaff)
            ->post(route('admin.applications.start-processing', $this->application))
            ->assertForbidden();
    }

    public function test_manager_who_is_not_assigned_cannot_process_application(): void
    {
        $this->application->update(['assigned_staff_id' => $this->staff->id]);

        $this->actingAs($this->manager)
            ->post(route('admin.applications.start-processing', $this->application))
            ->assertForbidden();
    }

    public function test_inactive_staff_cannot_process_application(): void
    {
        $inactiveStaff = User::factory()->staff()->inactive()->create();
        $this->department->users()->syncWithoutDetaching([$inactiveStaff->id]);

        $this->application->update(['assigned_staff_id' => $inactiveStaff->id]);

        $this->actingAs($inactiveStaff)
            ->post(route('admin.applications.start-processing', $this->application))
            ->assertForbidden();
    }

    public function test_trashed_staff_cannot_process_application(): void
    {
        $trashedStaff = User::factory()->staff()->create();
        $this->department->users()->syncWithoutDetaching([$trashedStaff->id]);
        $trashedStaff->delete();

        $this->application->update(['assigned_staff_id' => $trashedStaff->id]);

        $this->actingAs($trashedStaff)
            ->post(route('admin.applications.start-processing', $this->application))
            ->assertForbidden();
    }

    public function test_soft_deleted_application_blocks_processing(): void
    {
        $this->application->update(['assigned_staff_id' => $this->staff->id]);
        $this->application->delete();

        $this->actingAs($this->staff)
            ->post(route('admin.applications.start-processing', $this->application))
            ->assertNotFound();
    }

    public function test_super_admin_can_assign_application(): void
    {
        $this->actingAs($this->superAdmin)
            ->post(route('admin.applications.assign', $this->application), [
                'staff_id' => $this->staff->id,
            ])
            ->assertRedirect(route('admin.applications.show', $this->application));

        $this->assertDatabaseHas('applications', [
            'id' => $this->application->id,
            'assigned_staff_id' => $this->staff->id,
        ]);
    }

    public function test_super_admin_can_process_application_as_override(): void
    {
        $this->application->update([
            'assigned_staff_id' => $this->staff->id,
            'status' => ApplicationStatus::Processing,
            'processing_started_at' => now(),
        ]);

        $this->actingAs($this->superAdmin)
            ->post(route('admin.applications.approve', $this->application), [
                'result_note' => 'duyệt bởi admin',
            ])
            ->assertRedirect(route('admin.applications.show', $this->application));

        $this->assertDatabaseHas('applications', [
            'id' => $this->application->id,
            'status' => ApplicationStatus::Approved->value,
        ]);
    }

    public function test_super_admin_sees_all_applications_in_index(): void
    {
        $this->actingAs($this->superAdmin)
            ->get(route('admin.applications.index'))
            ->assertOk()
            ->assertSee($this->application->application_code);
    }

    public function test_staff_sees_own_assigned_and_department_claimable_in_index(): void
    {
        $this->actingAs($this->staff)
            ->get(route('admin.applications.index'))
            ->assertOk()
            ->assertSee($this->application->application_code);

        $this->application->update(['assigned_staff_id' => $this->staff->id]);

        $this->actingAs($this->staff)
            ->get(route('admin.applications.index'))
            ->assertOk()
            ->assertSee($this->application->application_code);
    }

    public function test_staff_does_not_see_application_of_other_department_in_index(): void
    {
        $otherDepartment = Department::factory()->create();
        $otherService = ServiceType::factory()->create([
            'responsible_department_id' => $otherDepartment->id,
        ]);
        $otherApplication = Application::factory()->create([
            'service_type_id' => $otherService->id,
        ]);

        $this->actingAs($this->staff)
            ->get(route('admin.applications.index'))
            ->assertOk()
            ->assertDontSee($otherApplication->application_code);
    }

    public function test_manager_sees_applications_of_led_department_in_index(): void
    {
        $this->actingAs($this->manager)
            ->get(route('admin.applications.index'))
            ->assertOk()
            ->assertSee($this->application->application_code);
    }
}
