<?php

namespace Tests\Feature\Admin;

use App\Enums\ApplicationStatus;
use App\Enums\DocumentKind;
use App\Enums\UserRole;
use App\Models\Application;
use App\Models\Department;
use App\Models\ServiceType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ApplicationProcessingTest extends TestCase
{
    use RefreshDatabase;

    private Department $department;

    private User $manager;

    private User $staff;

    private User $staffTwo;

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
        $this->staffTwo = User::factory()->staff()->create();
        $this->department->users()->syncWithoutDetaching([$this->staff->id, $this->staffTwo->id]);

        $this->service = ServiceType::factory()->create([
            'responsible_department_id' => $this->department->id,
            'processing_time_days' => 5,
        ]);

        $this->application = Application::factory()->create([
            'service_type_id' => $this->service->id,
        ]);
    }

    public function test_manager_can_assign_application_to_staff_of_department(): void
    {
        $response = $this->actingAs($this->manager)->post(
            route('admin.applications.assign', $this->application),
            ['staff_id' => $this->staff->id, 'note' => 'Phân công'],
        );

        $response->assertRedirect(route('admin.applications.show', $this->application));

        $this->assertDatabaseHas('applications', [
            'id' => $this->application->id,
            'assigned_staff_id' => $this->staff->id,
            'status' => ApplicationStatus::Received->value,
        ]);

        $this->assertDatabaseHas('application_assignments', [
            'application_id' => $this->application->id,
            'staff_id' => $this->staff->id,
            'assigned_by' => $this->manager->id,
            'department_id' => $this->department->id,
            'note' => 'Phân công',
            'ended_at' => null,
        ]);
    }

    public function test_manager_can_reassign_application_closing_previous_assignment(): void
    {
        Application::query()->whereKey($this->application)->update(['assigned_staff_id' => $this->staff->id]);
        $this->application->assignments()->create([
            'staff_id' => $this->staff->id,
            'department_id' => $this->department->id,
            'assigned_by' => $this->manager->id,
            'assigned_at' => now(),
        ]);

        $this->actingAs($this->manager)->post(
            route('admin.applications.assign', $this->application),
            ['staff_id' => $this->staffTwo->id],
        )->assertRedirect(route('admin.applications.show', $this->application));

        $this->assertDatabaseHas('applications', [
            'id' => $this->application->id,
            'assigned_staff_id' => $this->staffTwo->id,
        ]);

        $this->assertSame(1, $this->application->assignments()
            ->where('staff_id', $this->staff->id)
            ->whereNotNull('ended_at')
            ->count());

        $this->assertDatabaseHas('application_assignments', [
            'application_id' => $this->application->id,
            'staff_id' => $this->staffTwo->id,
            'ended_at' => null,
        ]);
    }

    public function test_manager_cannot_assign_terminal_application(): void
    {
        Application::query()->whereKey($this->application)->update([
            'status' => ApplicationStatus::Approved,
            'completed_at' => now(),
        ]);

        $response = $this->actingAs($this->manager)->post(
            route('admin.applications.assign', $this->application),
            ['staff_id' => $this->staff->id],
        );

        $response->assertSessionHasErrors('staff_id');

        $this->assertDatabaseHas('applications', [
            'id' => $this->application->id,
            'assigned_staff_id' => null,
        ]);
    }

    public function test_staff_cannot_assign_application(): void
    {
        $response = $this->actingAs($this->staff)->post(
            route('admin.applications.assign', $this->application),
            ['staff_id' => $this->staffTwo->id],
        );

        $response->assertForbidden();

        $this->assertDatabaseHas('applications', [
            'id' => $this->application->id,
            'assigned_staff_id' => null,
        ]);
    }

    public function test_manager_cannot_assign_staff_outside_responsible_department(): void
    {
        $otherDepartment = Department::factory()->create();
        $otherStaff = User::factory()->staff()->create();
        $otherDepartment->users()->syncWithoutDetaching([$otherStaff->id]);

        $response = $this->actingAs($this->manager)->post(
            route('admin.applications.assign', $this->application),
            ['staff_id' => $otherStaff->id],
        );

        $response->assertSessionHasErrors('staff_id');

        $this->assertDatabaseHas('applications', [
            'id' => $this->application->id,
            'assigned_staff_id' => null,
        ]);
    }

    public function test_manager_cannot_assign_inactive_staff(): void
    {
        $inactiveStaff = User::factory()->staff()->inactive()->create();
        $this->department->users()->syncWithoutDetaching([$inactiveStaff->id]);

        $response = $this->actingAs($this->manager)->post(
            route('admin.applications.assign', $this->application),
            ['staff_id' => $inactiveStaff->id],
        );

        $response->assertSessionHasErrors('staff_id');
    }

    public function test_manager_of_other_department_does_not_see_application(): void
    {
        $otherManager = User::factory()->manager()->create();
        $otherDepartment = Department::factory()->create();
        $otherDepartment->update(['leader_id' => $otherManager->id]);
        $otherDepartment->users()->syncWithoutDetaching([$otherManager->id]);

        $response = $this->actingAs($otherManager)->get(route('admin.applications.index'));

        $response->assertOk();
        $response->assertDontSee($this->application->application_code);
    }

    public function test_staff_can_claim_unassigned_application_in_scope(): void
    {
        $response = $this->actingAs($this->staff)->post(
            route('admin.applications.claim', $this->application),
        );

        $response->assertRedirect(route('admin.applications.show', $this->application));

        $this->assertDatabaseHas('applications', [
            'id' => $this->application->id,
            'assigned_staff_id' => $this->staff->id,
            'status' => ApplicationStatus::Received->value,
        ]);

        $this->assertDatabaseHas('application_assignments', [
            'application_id' => $this->application->id,
            'staff_id' => $this->staff->id,
            'assigned_by' => $this->staff->id,
            'ended_at' => null,
        ]);
    }

    public function test_staff_cannot_claim_already_assigned_application(): void
    {
        Application::query()->whereKey($this->application)->update(['assigned_staff_id' => $this->staffTwo->id]);

        $response = $this->actingAs($this->staff)->post(
            route('admin.applications.claim', $this->application),
        );

        $response->assertSessionHasErrors('application');
    }

    public function test_staff_of_other_department_cannot_claim_application(): void
    {
        $otherDepartment = Department::factory()->create();
        $otherStaff = User::factory()->staff()->create();
        $otherDepartment->users()->syncWithoutDetaching([$otherStaff->id]);

        $response = $this->actingAs($otherStaff)->post(
            route('admin.applications.claim', $this->application),
        );

        $response->assertForbidden();
    }

    public function test_assigned_staff_can_start_processing(): void
    {
        $this->application->update(['assigned_staff_id' => $this->staff->id]);

        $response = $this->actingAs($this->staff)->post(
            route('admin.applications.start-processing', $this->application),
        );

        $response->assertRedirect(route('admin.applications.show', $this->application));

        $this->assertDatabaseHas('applications', [
            'id' => $this->application->id,
            'status' => ApplicationStatus::Processing->value,
        ]);

        $this->assertNotNull($this->application->fresh()->processing_started_at);

        $this->assertDatabaseHas('application_status_histories', [
            'application_id' => $this->application->id,
            'from_status' => ApplicationStatus::Received->value,
            'to_status' => ApplicationStatus::Processing->value,
            'changed_by' => $this->staff->id,
        ]);
    }

    public function test_unassigned_staff_cannot_start_processing(): void
    {
        $response = $this->actingAs($this->staff)->post(
            route('admin.applications.start-processing', $this->application),
        );

        $response->assertForbidden();

        $this->assertDatabaseHas('applications', [
            'id' => $this->application->id,
            'status' => ApplicationStatus::Received->value,
        ]);
    }

    public function test_assigned_staff_can_approve_application(): void
    {
        $this->application->update([
            'assigned_staff_id' => $this->staff->id,
            'status' => ApplicationStatus::Processing,
            'processing_started_at' => now(),
        ]);

        $response = $this->actingAs($this->staff)->post(
            route('admin.applications.approve', $this->application),
            ['result_note' => 'Đã hoàn tất'],
        );

        $response->assertRedirect(route('admin.applications.show', $this->application));

        $this->assertDatabaseHas('applications', [
            'id' => $this->application->id,
            'status' => ApplicationStatus::Approved->value,
            'result_note' => 'Đã hoàn tất',
        ]);

        $this->assertNotNull($this->application->fresh()->completed_at);
    }

    public function test_assigned_staff_can_reject_application_with_reason(): void
    {
        $this->application->update([
            'assigned_staff_id' => $this->staff->id,
            'status' => ApplicationStatus::Processing,
            'processing_started_at' => now(),
        ]);

        $response = $this->actingAs($this->staff)->post(
            route('admin.applications.reject', $this->application),
            ['rejection_reason' => 'Thiếu giấy tờ'],
        );

        $response->assertRedirect(route('admin.applications.show', $this->application));

        $this->assertDatabaseHas('applications', [
            'id' => $this->application->id,
            'status' => ApplicationStatus::Rejected->value,
            'rejection_reason' => 'Thiếu giấy tờ',
        ]);
    }

    public function test_reject_without_reason_is_rejected(): void
    {
        $this->application->update([
            'assigned_staff_id' => $this->staff->id,
            'status' => ApplicationStatus::Processing,
            'processing_started_at' => now(),
        ]);

        $response = $this->actingAs($this->staff)->post(
            route('admin.applications.reject', $this->application),
            [],
        );

        $response->assertSessionHasErrors('rejection_reason');
    }

    public function test_invalid_transitions_are_rejected(): void
    {
        foreach ([
            [ApplicationStatus::Received, ApplicationStatus::Approved],
            [ApplicationStatus::Approved, ApplicationStatus::Processing],
            [ApplicationStatus::SupplementRequired, ApplicationStatus::Rejected],
            [ApplicationStatus::Processing, ApplicationStatus::Processing],
        ] as [$from, $to]) {
            $application = Application::factory()->create([
                'service_type_id' => $this->service->id,
                'status' => $from,
                'assigned_staff_id' => $this->staff->id,
                'processing_started_at' => in_array($from, [
                    ApplicationStatus::Processing,
                    ApplicationStatus::SupplementRequired,
                    ApplicationStatus::Approved,
                    ApplicationStatus::Rejected,
                ], true) ? now() : null,
                'completed_at' => in_array($from, [
                    ApplicationStatus::Approved,
                    ApplicationStatus::Rejected,
                ], true) ? now() : null,
            ]);

            $response = match ($to) {
                ApplicationStatus::Approved => $this->actingAs($this->staff)->post(
                    route('admin.applications.approve', $application),
                ),
                ApplicationStatus::Rejected => $this->actingAs($this->staff)->post(
                    route('admin.applications.reject', $application),
                    ['rejection_reason' => 'lý do'],
                ),
                ApplicationStatus::Processing => $this->actingAs($this->staff)->post(
                    route('admin.applications.start-processing', $application),
                ),
                default => throw new \LogicException('Unexpected transition target'),
            };

            $response->assertSessionHasErrors('status');
        }
    }

    public function test_staff_can_request_supplement_with_note(): void
    {
        $this->application->update([
            'assigned_staff_id' => $this->staff->id,
            'status' => ApplicationStatus::Processing,
            'processing_started_at' => now(),
        ]);

        $response = $this->actingAs($this->staff)->post(
            route('admin.applications.request-supplement', $this->application),
            ['note' => 'Cần bổ sung sổ hộ khẩu'],
        );

        $response->assertRedirect(route('admin.applications.show', $this->application));

        $this->assertDatabaseHas('applications', [
            'id' => $this->application->id,
            'status' => ApplicationStatus::SupplementRequired->value,
        ]);

        $this->assertDatabaseHas('application_status_histories', [
            'application_id' => $this->application->id,
            'from_status' => ApplicationStatus::Processing->value,
            'to_status' => ApplicationStatus::SupplementRequired->value,
            'note' => 'Cần bổ sung sổ hộ khẩu',
        ]);
    }

    public function test_request_supplement_without_note_is_rejected(): void
    {
        $this->application->update([
            'assigned_staff_id' => $this->staff->id,
            'status' => ApplicationStatus::Processing,
            'processing_started_at' => now(),
        ]);

        $this->actingAs($this->staff)->post(
            route('admin.applications.request-supplement', $this->application),
            [],
        )->assertSessionHasErrors('note');
    }

    public function test_request_supplement_from_received_is_rejected(): void
    {
        $this->application->update(['assigned_staff_id' => $this->staff->id]);

        $this->actingAs($this->staff)->post(
            route('admin.applications.request-supplement', $this->application),
            ['note' => 'lý do'],
        )->assertSessionHasErrors('status');
    }

    public function test_staff_can_resume_processing_after_supplement(): void
    {
        $this->application->update([
            'assigned_staff_id' => $this->staff->id,
            'status' => ApplicationStatus::SupplementRequired,
            'processing_started_at' => now(),
        ]);

        $this->application->statusHistories()->create([
            'from_status' => ApplicationStatus::Processing,
            'to_status' => ApplicationStatus::SupplementRequired,
            'changed_by' => $this->staff->id,
            'note' => 'Cần bổ sung',
        ]);

        $response = $this->actingAs($this->staff)->post(
            route('admin.applications.resume', $this->application),
        );

        $response->assertRedirect(route('admin.applications.show', $this->application));

        $this->assertDatabaseHas('applications', [
            'id' => $this->application->id,
            'status' => ApplicationStatus::Processing->value,
        ]);

        $this->assertDatabaseHas('application_status_histories', [
            'application_id' => $this->application->id,
            'from_status' => ApplicationStatus::SupplementRequired->value,
            'to_status' => ApplicationStatus::Processing->value,
            'changed_by' => $this->staff->id,
        ]);
    }

    public function test_assigned_staff_can_store_result_document_while_processing(): void
    {
        Storage::fake('local');

        $this->application->update([
            'assigned_staff_id' => $this->staff->id,
            'status' => ApplicationStatus::Processing,
            'processing_started_at' => now(),
        ]);

        $response = $this->actingAs($this->staff)->post(
            route('admin.applications.result-documents.store', $this->application),
            ['document' => UploadedFile::fake()->create('result.pdf', 100, 'application/pdf')],
        );

        $response->assertRedirect(route('admin.applications.show', $this->application));

        $this->assertDatabaseHas('application_documents', [
            'application_id' => $this->application->id,
            'uploaded_by' => $this->staff->id,
            'document_kind' => 'result',
            'original_name' => 'result.pdf',
        ]);
    }

    public function test_result_document_cannot_be_stored_when_rejected(): void
    {
        Storage::fake('local');

        $this->application->update([
            'assigned_staff_id' => $this->staff->id,
            'status' => ApplicationStatus::Rejected,
            'processing_started_at' => now(),
            'completed_at' => now(),
            'rejection_reason' => 'lý do',
        ]);

        $this->actingAs($this->staff)->post(
            route('admin.applications.result-documents.store', $this->application),
            ['document' => UploadedFile::fake()->create('result.pdf', 100, 'application/pdf')],
        )->assertSessionHasErrors('document');

        $this->assertDatabaseMissing('application_documents', [
            'application_id' => $this->application->id,
        ]);
    }

    public function test_show_returns_workflow_detail_view(): void
    {
        $this->application->update(['assigned_staff_id' => $this->staff->id]);

        $this->actingAs($this->staff)
            ->get(route('admin.applications.show', $this->application))
            ->assertOk()
            ->assertSee($this->application->application_code);
    }

    public function test_detail_keeps_f05_actions_guarded_by_the_existing_application_policy(): void
    {
        $this->application->update(['assigned_staff_id' => $this->staff->id]);

        $staffResponse = $this->actingAs($this->staff)
            ->get(route('admin.applications.show', $this->application));

        $staffResponse->assertOk()
            ->assertDontSee(route('admin.applications.assign', $this->application), false)
            ->assertSee(route('admin.applications.start-processing', $this->application), false)
            ->assertSee(route('admin.applications.request-supplement', $this->application), false)
            ->assertSee(route('admin.applications.approve', $this->application), false)
            ->assertSee(route('admin.applications.reject', $this->application), false);

        $managerResponse = $this->actingAs($this->manager)
            ->get(route('admin.applications.show', $this->application));

        $managerResponse->assertOk()
            ->assertSee(route('admin.applications.assign', $this->application), false)
            ->assertDontSee(route('admin.applications.start-processing', $this->application), false)
            ->assertDontSee(route('admin.applications.approve', $this->application), false)
            ->assertDontSee(route('admin.applications.reject', $this->application), false);
    }

    public function test_staff_can_download_citizen_document_from_admin_ui(): void
    {
        Storage::fake('local');

        $this->application->update(['assigned_staff_id' => $this->staff->id]);

        $document = $this->application->documents()->create([
            'uploaded_by' => $this->application->citizen_id,
            'document_kind' => DocumentKind::Submission,
            'requirement_code' => 'citizen_id_copy',
            'original_name' => 'cmnd.pdf',
            'path' => 'documents/seed.pdf',
            'disk' => 'local',
            'mime_type' => 'application/pdf',
            'size' => 1024,
        ]);
        Storage::disk('local')->put('documents/seed.pdf', 'pdf-bytes');

        $this->actingAs($this->staff)
            ->get(route('admin.applications.documents.download', [$this->application, $document]))
            ->assertOk()
            ->assertDownload('cmnd.pdf');

        $this->assertSame('pdf-bytes', Storage::disk('local')->get('documents/seed.pdf'));
    }

    public function test_staff_cannot_download_document_of_other_application(): void
    {
        Storage::fake('local');

        $otherApplication = Application::factory()->create();

        $document = $otherApplication->documents()->create([
            'uploaded_by' => $otherApplication->citizen_id,
            'document_kind' => DocumentKind::Submission,
            'original_name' => 'cmnd.pdf',
            'path' => 'documents/seed.pdf',
            'disk' => 'local',
            'mime_type' => 'application/pdf',
            'size' => 1024,
        ]);

        $this->actingAs($this->staff)
            ->get(route('admin.applications.documents.download', [$this->application, $document]))
            ->assertNotFound();
    }

    public function test_citizen_cannot_access_admin_processing_routes(): void
    {
        $citizen = User::factory()->withRole(UserRole::Citizen)->create();

        $this->actingAs($citizen)->get(route('admin.applications.index'))->assertForbidden();
        $this->actingAs($citizen)->post(route('admin.applications.claim', $this->application))->assertForbidden();
    }
}
