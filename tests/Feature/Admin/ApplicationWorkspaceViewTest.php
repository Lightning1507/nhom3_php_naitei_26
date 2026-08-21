<?php

namespace Tests\Feature\Admin;

use App\Enums\ApplicationStatus;
use App\Enums\DocumentKind;
use App\Models\Application;
use App\Models\Department;
use App\Models\ServiceType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ApplicationWorkspaceViewTest extends TestCase
{
    use RefreshDatabase;

    private Department $department;

    private User $manager;

    private User $staff;

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

        $this->service = ServiceType::factory()->create([
            'responsible_department_id' => $this->department->id,
            'processing_time_days' => 5,
        ]);

        $this->application = Application::factory()->create([
            'service_type_id' => $this->service->id,
            'assigned_staff_id' => $this->staff->id,
        ]);
    }

    public function test_staff_index_renders_my_applications_and_claimable_section(): void
    {
        $claimable = Application::factory()->create([
            'service_type_id' => $this->service->id,
        ]);

        $this->actingAs($this->staff)
            ->get(route('admin.applications.index'))
            ->assertOk()
            ->assertSee('Hồ sơ của tôi')
            ->assertSee('Hồ sơ có thể nhận')
            ->assertSee($this->application->application_code)
            ->assertSee($claimable->application_code);
    }

    public function test_manager_index_renders_board_stats_and_filters(): void
    {
        $this->actingAs($this->manager)
            ->get(route('admin.applications.index'))
            ->assertOk()
            ->assertSee('Đang chờ xử lý')
            ->assertSee('Quá hạn')
            ->assertSee($this->application->application_code);
    }

    public function test_show_renders_supplement_banner_with_note_and_missing_documents(): void
    {
        $this->application->update([
            'status' => ApplicationStatus::SupplementRequired,
            'processing_started_at' => now(),
        ]);

        $this->application->statusHistories()->create([
            'from_status' => ApplicationStatus::Processing,
            'to_status' => ApplicationStatus::SupplementRequired,
            'changed_by' => $this->staff->id,
            'note' => 'Thiếu giấy tờ tùy thân',
        ]);

        $this->actingAs($this->staff)
            ->get(route('admin.applications.show', $this->application))
            ->assertOk()
            ->assertSee('Đang chờ bổ sung tài liệu')
            ->assertSee('Thiếu giấy tờ tùy thân')
            ->assertSee('Citizen ID Copy');
    }

    public function test_show_renders_next_step_guidance_per_status(): void
    {
        $this->actingAs($this->staff)
            ->get(route('admin.applications.show', $this->application))
            ->assertOk()
            ->assertSee('Bước tiếp theo')
            ->assertSee('Bắt đầu xử lý');
    }

    public function test_show_renders_inline_preview_for_pdf_document(): void
    {
        Storage::fake('local');

        $document = $this->application->documents()->create([
            'uploaded_by' => $this->application->citizen_id,
            'document_kind' => DocumentKind::Submission,
            'requirement_code' => 'citizen_id_copy',
            'original_name' => 'cmnd.pdf',
            'path' => 'documents/seed.pdf',
            'disk' => 'local',
            'mime_type' => 'application/pdf',
            'file_size' => 1024,
        ]);
        Storage::disk('local')->put('documents/seed.pdf', 'pdf-bytes');

        $this->actingAs($this->staff)
            ->get(route('admin.applications.show', $this->application))
            ->assertOk()
            ->assertSee('Xem', false)
            ->assertSee('preview-document-'.$this->application->id.'-'.$document->id)
            ->assertSee('?inline=1', false);
    }

    public function test_inline_preview_returns_document_with_original_content_type(): void
    {
        Storage::fake('local');

        $document = $this->application->documents()->create([
            'uploaded_by' => $this->application->citizen_id,
            'document_kind' => DocumentKind::Submission,
            'requirement_code' => 'citizen_id_copy',
            'original_name' => 'cmnd.pdf',
            'path' => 'documents/seed.pdf',
            'disk' => 'local',
            'mime_type' => 'application/pdf',
            'file_size' => 1024,
        ]);
        Storage::disk('local')->put('documents/seed.pdf', 'pdf-bytes');

        $this->actingAs($this->staff)
            ->get(route('admin.applications.documents.download', [$this->application, $document]).'?inline=1')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_assigned_received_application_hides_claim_button_and_shows_start_processing(): void
    {
        $this->application->update(['assigned_staff_id' => $this->staff->id]);

        $this->actingAs($this->staff)
            ->get(route('admin.applications.show', $this->application))
            ->assertOk()
            ->assertSee('admin/applications/'.$this->application->id.'/start-processing')
            ->assertDontSee('admin/applications/'.$this->application->id.'/claim')
            ->assertDontSee('request-supplement-'.$this->application->id)
            ->assertDontSee('approve-application-'.$this->application->id)
            ->assertDontSee('reject-application-'.$this->application->id);
    }

    public function test_processing_application_shows_only_processing_actions(): void
    {
        $this->application->update([
            'assigned_staff_id' => $this->staff->id,
            'status' => ApplicationStatus::Processing,
            'processing_started_at' => now(),
        ]);

        $this->actingAs($this->staff)
            ->get(route('admin.applications.show', $this->application))
            ->assertOk()
            ->assertSee('request-supplement-'.$this->application->id)
            ->assertSee('approve-application-'.$this->application->id)
            ->assertSee('reject-application-'.$this->application->id)
            ->assertSee('result-document-'.$this->application->id)
            ->assertDontSee('admin/applications/'.$this->application->id.'/claim')
            ->assertDontSee('admin/applications/'.$this->application->id.'/start-processing')
            ->assertDontSee('admin/applications/'.$this->application->id.'/resume');
    }

    public function test_unassigned_received_application_shows_claim_button(): void
    {
        $this->application->update(['assigned_staff_id' => null]);

        $this->actingAs($this->staff)
            ->get(route('admin.applications.show', $this->application))
            ->assertOk()
            ->assertSee('admin/applications/'.$this->application->id.'/claim')
            ->assertDontSee('admin/applications/'.$this->application->id.'/start-processing')
            ->assertDontSee('request-supplement-'.$this->application->id);
    }

    public function test_dashboard_links_to_applications_index(): void
    {
        $this->actingAs($this->manager)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Quản lý hồ sơ dịch vụ công');
    }
}
