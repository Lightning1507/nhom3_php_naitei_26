<?php

namespace Tests\Feature\Admin;

use App\Enums\ApplicationStatus;
use App\Enums\DocumentKind;
use App\Enums\UserRole;
use App\Models\Application;
use App\Models\ApplicationAssignment;
use App\Models\ApplicationDocument;
use App\Models\ApplicationStatusHistory;
use App\Models\Department;
use App\Models\ServiceType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ApplicationDetailTest extends TestCase
{
    use RefreshDatabase;

    private Department $department;

    private User $manager;

    private User $staff;

    private User $otherStaff;

    private User $superAdmin;

    private User $citizen;

    private ServiceType $service;

    private Application $application;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = User::factory()->manager()->create(['name' => 'Manager In Scope']);
        $this->department = Department::factory()->ledBy($this->manager)->create([
            'name' => 'Department In Scope',
            'code' => 'DEP-SCOPE',
        ]);
        $this->staff = User::factory()->staff()->create(['name' => 'Assigned Staff']);
        $this->otherStaff = User::factory()->staff()->create(['name' => 'Other Staff']);
        $this->department->users()->syncWithoutDetaching([$this->staff->id, $this->otherStaff->id]);
        $this->superAdmin = User::factory()->withRole(UserRole::SuperAdmin)->create();
        $this->citizen = User::factory()->withRole(UserRole::Citizen)->create([
            'name' => 'Citizen Detail Owner',
            'citizen_id' => '012345678901',
        ]);
        $this->service = ServiceType::factory()->create([
            'name' => 'Detailed Public Service',
            'code' => 'SVC-DETAIL',
            'responsible_department_id' => $this->department->id,
            'processing_time_days' => 5,
        ]);
        $this->application = Application::factory()->create([
            'application_code' => 'HS-DETAIL-001',
            'citizen_id' => $this->citizen->id,
            'service_type_id' => $this->service->id,
            'assigned_staff_id' => $this->staff->id,
        ]);
    }

    public function test_detail_visibility_matches_the_canonical_list_scope_and_masks_denials(): void
    {
        $this->actingAs($this->staff)
            ->get(route('admin.applications.show', $this->application))
            ->assertOk();

        $this->actingAs($this->manager)
            ->get(route('admin.applications.show', $this->application))
            ->assertOk();

        $this->actingAs($this->superAdmin)
            ->get(route('admin.applications.show', $this->application))
            ->assertOk();

        $this->actingAs($this->otherStaff)
            ->get(route('admin.applications.show', $this->application))
            ->assertNotFound();

        $outsideManager = User::factory()->manager()->create();
        Department::factory()->ledBy($outsideManager)->create();

        $this->actingAs($outsideManager)
            ->get(route('admin.applications.show', $this->application))
            ->assertNotFound();

        $this->actingAs($this->staff)
            ->get(route('admin.applications.show', 999999))
            ->assertNotFound();
    }

    public function test_staff_visibility_is_rechecked_after_reassignment(): void
    {
        $this->actingAs($this->staff)
            ->get(route('admin.applications.show', $this->application))
            ->assertOk();

        $this->application->update(['assigned_staff_id' => $this->otherStaff->id]);

        $this->actingAs($this->staff)
            ->get(route('admin.applications.show', $this->application))
            ->assertNotFound();

        $this->actingAs($this->otherStaff)
            ->get(route('admin.applications.show', $this->application))
            ->assertOk();
    }

    public function test_detail_renders_the_complete_read_model_in_deterministic_order(): void
    {
        $submittedAt = Carbon::parse('2026-08-20 08:00:00');
        $processingAt = Carbon::parse('2026-08-20 09:00:00');
        $completedAt = Carbon::parse('2026-08-21 10:00:00');

        $this->application->update([
            'status' => ApplicationStatus::Approved,
            'form_data' => [
                'full_name' => 'Submitted Citizen Name',
                'address' => 'Submitted Address',
                'nested' => ['value' => 'Nested Value'],
            ],
            'submitted_at' => $submittedAt,
            'processing_started_at' => $processingAt,
            'completed_at' => $completedAt,
            'result_note' => 'Approved terminal result',
        ]);

        $timelineAt = Carbon::parse('2026-08-20 09:30:00');
        ApplicationAssignment::factory()->create([
            'application_id' => $this->application->id,
            'staff_id' => $this->staff->id,
            'department_id' => $this->department->id,
            'assigned_by' => $this->manager->id,
            'assigned_at' => $timelineAt,
            'ended_at' => $timelineAt->copy()->addHour(),
            'note' => 'Assignment first',
        ]);
        ApplicationAssignment::factory()->create([
            'application_id' => $this->application->id,
            'staff_id' => $this->otherStaff->id,
            'department_id' => $this->department->id,
            'assigned_by' => $this->manager->id,
            'assigned_at' => $timelineAt,
            'note' => 'Assignment second',
        ]);

        ApplicationStatusHistory::factory()->create([
            'application_id' => $this->application->id,
            'from_status' => ApplicationStatus::Received,
            'to_status' => ApplicationStatus::Processing,
            'changed_by' => $this->staff->id,
            'note' => 'History first',
            'created_at' => $timelineAt,
        ]);
        ApplicationStatusHistory::factory()->create([
            'application_id' => $this->application->id,
            'from_status' => ApplicationStatus::Processing,
            'to_status' => ApplicationStatus::Approved,
            'changed_by' => $this->manager->id,
            'note' => 'History second',
            'created_at' => $timelineAt,
        ]);

        ApplicationDocument::query()->create([
            'application_id' => $this->application->id,
            'uploaded_by' => $this->citizen->id,
            'document_kind' => DocumentKind::Submission,
            'original_name' => 'citizen-document.pdf',
            'requirement_code' => 'citizen_id_copy',
            'disk' => 'local',
            'path' => 'applications/detail/citizen-document.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 2048,
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->get(route('admin.applications.show', $this->application));

        $response->assertOk()
            ->assertSeeText('HS-DETAIL-001')
            ->assertSeeText('Citizen Detail Owner')
            ->assertSeeText('012345678901')
            ->assertSeeText('Detailed Public Service')
            ->assertSeeText('SVC-DETAIL')
            ->assertSeeText('Department In Scope')
            ->assertSeeText('DEP-SCOPE')
            ->assertSeeText('Assigned Staff')
            ->assertSeeText('Submitted Citizen Name')
            ->assertSeeText('Submitted Address')
            ->assertSeeText('Nested Value')
            ->assertSeeText('citizen-document.pdf')
            ->assertSeeText('Approved terminal result')
            ->assertSeeInOrder(['Assignment first', 'Assignment second'])
            ->assertSeeInOrder(['History first', 'History second'])
            ->assertSeeText($submittedAt->format('d/m/Y H:i'))
            ->assertSeeText($processingAt->format('d/m/Y H:i'))
            ->assertSeeText($completedAt->format('d/m/Y H:i'));
    }

    public function test_archived_and_inactive_historical_relations_keep_their_labels(): void
    {
        $archivedUploader = User::factory()->withRole(UserRole::Citizen)->create(['name' => 'Archived Uploader']);
        $archivedActor = User::factory()->staff()->create(['name' => 'Archived History Actor']);

        ApplicationAssignment::factory()->create([
            'application_id' => $this->application->id,
            'staff_id' => $archivedActor->id,
            'department_id' => $this->department->id,
            'assigned_by' => $archivedActor->id,
            'note' => 'Archived assignment relation',
        ]);
        ApplicationStatusHistory::factory()->create([
            'application_id' => $this->application->id,
            'to_status' => ApplicationStatus::Received,
            'changed_by' => $archivedActor->id,
            'note' => 'Archived history relation',
        ]);
        ApplicationDocument::query()->create([
            'application_id' => $this->application->id,
            'uploaded_by' => $archivedUploader->id,
            'document_kind' => DocumentKind::Submission,
            'original_name' => 'archived-uploader.pdf',
            'disk' => 'local',
            'path' => 'applications/detail/archived-uploader.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 1024,
        ]);

        $this->staff->update(['is_active' => false]);
        $this->citizen->delete();
        $archivedUploader->delete();
        $archivedActor->delete();
        $this->service->update(['is_active' => false]);
        $this->service->delete();
        $this->department->delete();

        $response = $this->actingAs($this->superAdmin)
            ->get(route('admin.applications.show', $this->application));

        $response->assertOk()
            ->assertSeeText('Citizen Detail Owner')
            ->assertSeeText('Assigned Staff')
            ->assertSeeText('Detailed Public Service')
            ->assertSeeText('Department In Scope')
            ->assertSeeText('Archived Uploader')
            ->assertSeeText('Archived History Actor')
            ->assertSeeText('Đã lưu trữ')
            ->assertSeeText('Đã vô hiệu hóa');
    }

    public function test_terminal_result_and_rejection_are_rendered_without_mutation(): void
    {
        $approved = $this->application;
        $approved->update([
            'status' => ApplicationStatus::Approved,
            'result_note' => 'Approval result read only',
            'completed_at' => now(),
        ]);
        $rejected = Application::factory()->create([
            'citizen_id' => $this->citizen->id,
            'service_type_id' => $this->service->id,
            'status' => ApplicationStatus::Rejected,
            'rejection_reason' => 'Rejection reason read only',
            'completed_at' => now(),
        ]);

        $this->actingAs($this->superAdmin)
            ->get(route('admin.applications.show', $approved))
            ->assertOk()
            ->assertSeeText('Approval result read only');

        $this->actingAs($this->superAdmin)
            ->get(route('admin.applications.show', $rejected))
            ->assertOk()
            ->assertSeeText('Rejection reason read only');

        $this->assertSame(ApplicationStatus::Approved, $approved->fresh()->status);
        $this->assertSame(ApplicationStatus::Rejected, $rejected->fresh()->status);
    }

    public function test_private_document_download_rechecks_parent_scope_and_association(): void
    {
        Storage::fake('local');
        $document = ApplicationDocument::query()->create([
            'application_id' => $this->application->id,
            'uploaded_by' => $this->citizen->id,
            'document_kind' => DocumentKind::Submission,
            'original_name' => 'private.pdf',
            'disk' => 'local',
            'path' => 'applications/detail/private.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 11,
        ]);
        Storage::disk('local')->put($document->path, 'private-pdf');

        $this->actingAs($this->staff)
            ->get(route('admin.applications.documents.download', [$this->application, $document]))
            ->assertOk()
            ->assertDownload('private.pdf');

        $this->actingAs($this->otherStaff)
            ->get(route('admin.applications.documents.download', [$this->application, $document]))
            ->assertNotFound();

        $otherApplication = Application::factory()->create([
            'assigned_staff_id' => $this->staff->id,
        ]);

        $this->actingAs($this->staff)
            ->get(route('admin.applications.documents.download', [$otherApplication, $document]))
            ->assertNotFound();
    }

    public function test_detail_and_download_get_requests_do_not_mutate_domain_or_history_data(): void
    {
        Storage::fake('local');
        $document = ApplicationDocument::query()->create([
            'application_id' => $this->application->id,
            'uploaded_by' => $this->citizen->id,
            'document_kind' => DocumentKind::Submission,
            'original_name' => 'immutable.pdf',
            'disk' => 'local',
            'path' => 'applications/detail/immutable.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 13,
        ]);
        Storage::disk('local')->put($document->path, 'immutable-pdf');

        $before = [
            'application' => $this->application->fresh()->getAttributes(),
            'assignments' => ApplicationAssignment::query()->count(),
            'documents' => ApplicationDocument::withTrashed()->count(),
            'histories' => ApplicationStatusHistory::query()->count(),
        ];

        $this->actingAs($this->staff)
            ->get(route('admin.applications.show', $this->application))
            ->assertOk();
        $this->actingAs($this->staff)
            ->get(route('admin.applications.documents.download', [$this->application, $document]))
            ->assertOk();

        $this->assertSame($before['application'], $this->application->fresh()->getAttributes());
        $this->assertSame($before['assignments'], ApplicationAssignment::query()->count());
        $this->assertSame($before['documents'], ApplicationDocument::withTrashed()->count());
        $this->assertSame($before['histories'], ApplicationStatusHistory::query()->count());
    }
}
