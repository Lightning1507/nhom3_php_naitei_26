<?php

namespace Tests\Feature\Admin;

use App\Enums\ApplicationStatus;
use App\Enums\UserRole;
use App\Models\Application;
use App\Models\Department;
use App\Models\ServiceType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ApplicationProcessingAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private Department $department;

    private Department $otherDepartment;

    private User $manager;

    private User $otherManager;

    private User $staff;

    private User $otherStaff;

    private User $superAdmin;

    private User $citizen;

    private ServiceType $service;

    private Application $application;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->department = Department::factory()->create();
        $this->otherDepartment = Department::factory()->create();

        $this->manager = User::factory()->manager()->create();
        $this->department->update(['leader_id' => $this->manager->id]);
        $this->department->users()->syncWithoutDetaching([$this->manager->id]);

        $this->otherManager = User::factory()->manager()->create();
        $this->otherDepartment->update(['leader_id' => $this->otherManager->id]);
        $this->otherDepartment->users()->syncWithoutDetaching([$this->otherManager->id]);

        $this->staff = User::factory()->staff()->create();
        $this->department->users()->syncWithoutDetaching([$this->staff->id]);

        $this->otherStaff = User::factory()->staff()->create();
        $this->otherDepartment->users()->syncWithoutDetaching([$this->otherStaff->id]);

        $this->superAdmin = User::factory()->withRole(UserRole::SuperAdmin)->create();

        $this->citizen = User::factory()->withRole(UserRole::Citizen)->create();

        $this->service = ServiceType::factory()->create([
            'responsible_department_id' => $this->department->id,
            'processing_time_days' => 5,
        ]);

        $this->application = Application::factory()->create([
            'service_type_id' => $this->service->id,
            'citizen_id' => $this->citizen->id,
            'status' => ApplicationStatus::Received,
        ]);
    }

    // -----------------------------------------------------------------
    // Guest / non-internal (401/403) — via EnsureInternalUser
    // -----------------------------------------------------------------

    public function test_guest_cannot_access_admin_application_index(): void
    {
        $this->get(route('admin.applications.index'))->assertRedirect(route('admin.login'));
    }

    public function test_guest_cannot_mutate_application(): void
    {
        $this->post(route('admin.applications.assign', $this->application), ['staff_id' => $this->staff->id])
            ->assertRedirect(route('admin.login'));
    }

    public function test_citizen_cannot_access_admin_processing_routes(): void
    {
        $this->actingAs($this->citizen)
            ->post(route('admin.applications.assign', $this->application), ['staff_id' => $this->staff->id])
            ->assertForbidden();

        $this->actingAs($this->citizen)
            ->post(route('admin.applications.start-processing', $this->application))
            ->assertForbidden();

        $this->actingAs($this->citizen)
            ->get(route('admin.applications.show', $this->application))
            ->assertForbidden();
    }

    public function test_inactive_user_blocked_via_can_access_protected_resources(): void
    {
        $inactiveManager = User::factory()->manager()->inactive()->create();
        $this->department->update(['leader_id' => $inactiveManager->id]);

        $this->actingAs($inactiveManager)
            ->get(route('admin.applications.index'))
            ->assertForbidden();

        $this->actingAs($inactiveManager)
            ->post(route('admin.applications.assign', $this->application), ['staff_id' => $this->staff->id])
            ->assertForbidden();
    }

    public function test_soft_deleted_user_blocked(): void
    {
        $trashedStaff = User::factory()->staff()->create();
        $this->department->users()->syncWithoutDetaching([$trashedStaff->id]);
        $this->application->update(['assigned_staff_id' => $trashedStaff->id]);
        $trashedStaff->delete();

        $this->actingAs($trashedStaff)
            ->post(route('admin.applications.start-processing', $this->application))
            ->assertForbidden();
    }

    // -----------------------------------------------------------------
    // AC: Manager gán Staff khác department → 403
    // -----------------------------------------------------------------

    public function test_manager_cannot_assign_staff_outside_responsible_department(): void
    {
        // Validation layer returns 422 (session errors) — policy also would block
        $response = $this->actingAs($this->manager)->post(
            route('admin.applications.assign', $this->application),
            ['staff_id' => $this->otherStaff->id]
        );

        $response->assertSessionHasErrors('staff_id');
        $this->assertDatabaseHas('applications', ['id' => $this->application->id, 'assigned_staff_id' => null]);
    }

    public function test_manager_from_other_department_cannot_assign_application(): void
    {
        // Manager who does not lead responsible department → policy 403
        $this->actingAs($this->otherManager)
            ->post(route('admin.applications.assign', $this->application), ['staff_id' => $this->staff->id])
            ->assertForbidden();
    }

    public function test_staff_cannot_assign_application(): void
    {
        $this->actingAs($this->staff)
            ->post(route('admin.applications.assign', $this->application), ['staff_id' => $this->staff->id])
            ->assertForbidden();
    }

    public function test_inactive_staff_cannot_be_assigned(): void
    {
        $inactiveStaff = User::factory()->staff()->inactive()->create();
        $this->department->users()->syncWithoutDetaching([$inactiveStaff->id]);

        $this->actingAs($this->manager)
            ->post(route('admin.applications.assign', $this->application), ['staff_id' => $inactiveStaff->id])
            ->assertSessionHasErrors('staff_id');
    }

    // -----------------------------------------------------------------
    // AC: Staff thao tác hồ sơ không phải của mình → 403
    // -----------------------------------------------------------------

    public function test_unassigned_staff_cannot_start_processing(): void
    {
        $this->application->update(['assigned_staff_id' => $this->staff->id]);

        $this->actingAs($this->otherStaff)
            ->post(route('admin.applications.start-processing', $this->application))
            ->assertForbidden();
    }

    public function test_unassigned_staff_cannot_request_supplement(): void
    {
        $this->application->update([
            'assigned_staff_id' => $this->staff->id,
            'status' => ApplicationStatus::Processing,
            'processing_started_at' => now(),
        ]);

        $this->actingAs($this->otherStaff)
            ->post(route('admin.applications.request-supplement', $this->application), ['note' => 'thiếu giấy tờ'])
            ->assertForbidden();
    }

    public function test_unassigned_staff_cannot_resume_processing(): void
    {
        $this->application->update([
            'assigned_staff_id' => $this->staff->id,
            'status' => ApplicationStatus::SupplementRequired,
            'processing_started_at' => now(),
        ]);

        $this->actingAs($this->otherStaff)
            ->post(route('admin.applications.resume', $this->application))
            ->assertForbidden();
    }

    public function test_unassigned_staff_cannot_approve(): void
    {
        $this->application->update([
            'assigned_staff_id' => $this->staff->id,
            'status' => ApplicationStatus::Processing,
            'processing_started_at' => now(),
        ]);

        $this->actingAs($this->otherStaff)
            ->post(route('admin.applications.approve', $this->application), ['result_note' => 'ok'])
            ->assertForbidden();
    }

    public function test_unassigned_staff_cannot_reject(): void
    {
        $this->application->update([
            'assigned_staff_id' => $this->staff->id,
            'status' => ApplicationStatus::Processing,
            'processing_started_at' => now(),
        ]);

        $this->actingAs($this->otherStaff)
            ->post(route('admin.applications.reject', $this->application), ['rejection_reason' => 'không hợp lệ'])
            ->assertForbidden();
    }

    public function test_unassigned_staff_cannot_upload_result_document(): void
    {
        $this->application->update([
            'assigned_staff_id' => $this->staff->id,
            'status' => ApplicationStatus::Processing,
            'processing_started_at' => now(),
        ]);

        $this->actingAs($this->otherStaff)
            ->post(route('admin.applications.result-documents.store', $this->application), [
                'document' => UploadedFile::fake()->create('result.pdf', 100, 'application/pdf'),
            ])
            ->assertForbidden();
    }

    public function test_manager_who_is_not_assigned_cannot_process(): void
    {
        $this->application->update([
            'assigned_staff_id' => $this->staff->id,
            'status' => ApplicationStatus::Processing,
            'processing_started_at' => now(),
        ]);

        $this->actingAs($this->manager)
            ->post(route('admin.applications.approve', $this->application), ['result_note' => 'duyệt'])
            ->assertForbidden();
    }

    public function test_staff_from_other_department_cannot_claim(): void
    {
        $this->actingAs($this->otherStaff)
            ->post(route('admin.applications.claim', $this->application))
            ->assertForbidden();
    }

    // -----------------------------------------------------------------
    // Hồ sơ người khác bị chặn server-side (view)
    // -----------------------------------------------------------------

    public function test_staff_cannot_view_application_of_other_department(): void
    {
        $otherService = ServiceType::factory()->create(['responsible_department_id' => $this->otherDepartment->id]);
        $otherApp = Application::factory()->create(['service_type_id' => $otherService->id]);

        $this->actingAs($this->staff)
            ->get(route('admin.applications.show', $otherApp))
            ->assertNotFound();
    }

    public function test_manager_cannot_view_application_of_other_department(): void
    {
        $otherService = ServiceType::factory()->create(['responsible_department_id' => $this->otherDepartment->id]);
        $otherApp = Application::factory()->create(['service_type_id' => $otherService->id]);

        $this->actingAs($this->manager)
            ->get(route('admin.applications.show', $otherApp))
            ->assertNotFound();
    }

    public function test_staff_can_view_own_assigned_application(): void
    {
        $this->application->update(['assigned_staff_id' => $this->staff->id]);

        $this->actingAs($this->staff)
            ->get(route('admin.applications.show', $this->application))
            ->assertOk();
    }

    public function test_staff_can_view_claimable_application(): void
    {
        // unassigned + Received + same department → claimable → view allowed
        $this->actingAs($this->staff)
            ->get(route('admin.applications.show', $this->application))
            ->assertOk();
    }

    // -----------------------------------------------------------------
    // Chặn tài khoản inactive / soft-deleted qua canAccessProtectedResources (FR-017)
    // -----------------------------------------------------------------

    public function test_inactive_assigned_staff_blocked_on_processing(): void
    {
        $inactiveStaff = User::factory()->staff()->inactive()->create();
        $this->department->users()->syncWithoutDetaching([$inactiveStaff->id]);
        $this->application->update(['assigned_staff_id' => $inactiveStaff->id]);

        $this->actingAs($inactiveStaff)
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

    public function test_soft_deleted_application_blocks_view(): void
    {
        $this->application->delete();

        $this->actingAs($this->manager)
            ->get(route('admin.applications.show', $this->application))
            ->assertNotFound();
    }

    // -----------------------------------------------------------------
    // SuperAdmin toàn quyền (override)
    // -----------------------------------------------------------------

    public function test_super_admin_can_assign_any_application(): void
    {
        $this->actingAs($this->superAdmin)
            ->post(route('admin.applications.assign', $this->application), ['staff_id' => $this->staff->id])
            ->assertRedirect(route('admin.applications.show', $this->application));

        $this->assertDatabaseHas('applications', ['id' => $this->application->id, 'assigned_staff_id' => $this->staff->id]);
    }

    public function test_super_admin_can_claim_unassigned_application(): void
    {
        $this->actingAs($this->superAdmin)
            ->post(route('admin.applications.claim', $this->application))
            ->assertRedirect(route('admin.applications.show', $this->application));

        $this->assertDatabaseHas('applications', ['id' => $this->application->id, 'assigned_staff_id' => $this->superAdmin->id]);
    }

    public function test_super_admin_can_process_as_override(): void
    {
        $this->application->update([
            'assigned_staff_id' => $this->staff->id,
            'status' => ApplicationStatus::Processing,
            'processing_started_at' => now(),
        ]);

        $this->actingAs($this->superAdmin)
            ->post(route('admin.applications.approve', $this->application), ['result_note' => 'duyệt bởi super admin'])
            ->assertRedirect(route('admin.applications.show', $this->application));

        $this->assertDatabaseHas('applications', ['id' => $this->application->id, 'status' => ApplicationStatus::Approved->value]);
    }

    public function test_super_admin_can_perform_all_transitions(): void
    {
        // startProcessing
        $this->application->update(['assigned_staff_id' => $this->staff->id, 'status' => ApplicationStatus::Received]);
        $this->actingAs($this->superAdmin)->post(route('admin.applications.start-processing', $this->application))->assertRedirect();
        $this->assertDatabaseHas('applications', ['id' => $this->application->id, 'status' => ApplicationStatus::Processing->value]);

        // requestSupplement
        $this->actingAs($this->superAdmin)->post(route('admin.applications.request-supplement', $this->application), ['note' => 'bổ sung'])->assertRedirect();

        // resume
        $this->actingAs($this->superAdmin)->post(route('admin.applications.resume', $this->application))->assertRedirect();

        // reject (new app)
        $app2 = Application::factory()->create(['service_type_id' => $this->service->id, 'status' => ApplicationStatus::Processing, 'processing_started_at' => now(), 'assigned_staff_id' => $this->staff->id]);
        $this->actingAs($this->superAdmin)->post(route('admin.applications.reject', $app2), ['rejection_reason' => 'lý do'])->assertRedirect();
        $this->assertDatabaseHas('applications', ['id' => $app2->id, 'status' => ApplicationStatus::Rejected->value]);
    }

    public function test_super_admin_can_upload_result_document_as_override(): void
    {
        $this->application->update([
            'assigned_staff_id' => $this->staff->id,
            'status' => ApplicationStatus::Processing,
            'processing_started_at' => now(),
        ]);

        $this->actingAs($this->superAdmin)
            ->post(route('admin.applications.result-documents.store', $this->application), [
                'document' => UploadedFile::fake()->create('result.pdf', 100, 'application/pdf'),
            ])
            ->assertRedirect(route('admin.applications.show', $this->application));
    }

    // -----------------------------------------------------------------
    // Scopes: toàn bộ mutation đi qua policy (S005) + scope queries
    // -----------------------------------------------------------------

    public function test_scope_visible_to_staff_only_own_assigned(): void
    {
        $otherApp = Application::factory()->create(['service_type_id' => $this->service->id]);
        $this->application->update(['assigned_staff_id' => $this->staff->id]);

        $visibleIds = Application::visibleTo($this->staff)->pluck('id')->toArray();

        $this->assertContains($this->application->id, $visibleIds);
        $this->assertNotContains($otherApp->id, $visibleIds);
    }

    public function test_scope_visible_to_manager_only_led_department(): void
    {
        $otherService = ServiceType::factory()->create(['responsible_department_id' => $this->otherDepartment->id]);
        $otherApp = Application::factory()->create(['service_type_id' => $otherService->id]);

        $managerIds = Application::visibleTo($this->manager)->pluck('id')->toArray();
        $this->assertContains($this->application->id, $managerIds);
        $this->assertNotContains($otherApp->id, $managerIds);

        $superIds = Application::visibleTo($this->superAdmin)->pluck('id')->toArray();
        $this->assertContains($this->application->id, $superIds);
        $this->assertContains($otherApp->id, $superIds);
    }

    public function test_scope_claimable_by_returns_only_unassigned_received_in_department(): void
    {
        $assignedApp = Application::factory()->create(['service_type_id' => $this->service->id, 'assigned_staff_id' => $this->staff->id]);
        $processingApp = Application::factory()->create(['service_type_id' => $this->service->id, 'status' => ApplicationStatus::Processing]);

        $claimableIds = Application::claimableBy($this->staff)->pluck('id')->toArray();

        $this->assertContains($this->application->id, $claimableIds);
        $this->assertNotContains($assignedApp->id, $claimableIds);
        $this->assertNotContains($processingApp->id, $claimableIds);
    }

    public function test_scope_assignable_to_manager_only_non_terminal_in_led_department(): void
    {
        $terminalApp = Application::factory()->create([
            'service_type_id' => $this->service->id,
            'status' => ApplicationStatus::Approved,
            'completed_at' => now(),
        ]);

        $assignableIds = Application::assignableTo($this->manager)->pluck('id')->toArray();
        $this->assertContains($this->application->id, $assignableIds);
        $this->assertNotContains($terminalApp->id, $assignableIds);

        $staffAssignable = Application::assignableTo($this->staff)->pluck('id')->toArray();
        $this->assertEmpty($staffAssignable);

        $superAssignable = Application::assignableTo($this->superAdmin)->pluck('id')->toArray();
        $this->assertContains($this->application->id, $superAssignable);
        $this->assertNotContains($terminalApp->id, $superAssignable);
    }

    public function test_staff_index_shows_only_visible_applications(): void
    {
        $otherService = ServiceType::factory()->create(['responsible_department_id' => $this->otherDepartment->id]);
        $otherApp = Application::factory()->create(['service_type_id' => $otherService->id]);

        $this->application->update(['assigned_staff_id' => $this->staff->id]);

        $this->actingAs($this->staff)
            ->get(route('admin.applications.index'))
            ->assertOk()
            ->assertSee($this->application->application_code)
            ->assertDontSee($otherApp->application_code);
    }

    public function test_manager_index_does_not_show_other_department_application(): void
    {
        $otherService = ServiceType::factory()->create(['responsible_department_id' => $this->otherDepartment->id]);
        $otherApp = Application::factory()->create(['service_type_id' => $otherService->id]);

        $this->actingAs($this->manager)
            ->get(route('admin.applications.index'))
            ->assertOk()
            ->assertSee($this->application->application_code)
            ->assertDontSee($otherApp->application_code);
    }
}
