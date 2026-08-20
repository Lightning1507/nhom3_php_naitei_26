<?php

namespace Tests\Feature\Api\V1;

use App\Enums\ApplicationStatus;
use App\Enums\UserRole;
use App\Models\Application;
use App\Models\ServiceType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplicationWorkflowViewTest extends TestCase
{
    use RefreshDatabase;

    private function makeCitizen(): User
    {
        return User::factory()->withRole(UserRole::Citizen)->create([
            'citizen_id' => fake()->unique()->numerify('#############'),
        ]);
    }

    private function makeService(array $overrides = []): ServiceType
    {
        return ServiceType::factory()->create($overrides);
    }

    private function makeApplication(User $citizen, array $overrides = []): Application
    {
        return Application::query()->create(array_merge([
            'application_code' => fake()->unique()->numerify('HS-20260820-#####'),
            'citizen_id' => $citizen->id,
            'service_type_id' => $this->makeService()->id,
            'status' => ApplicationStatus::Received,
            'form_data' => ['full_name' => 'Nguyen Van A'],
            'submitted_at' => now(),
        ], $overrides));
    }

    public function test_show_exposes_timeline_and_result_for_owner(): void
    {
        $citizen = $this->makeCitizen();
        $application = $this->makeApplication($citizen, [
            'status' => ApplicationStatus::Approved,
            'processing_started_at' => now()->subDay(),
            'completed_at' => now(),
            'result_note' => 'Đã cấp kết quả',
        ]);

        $application->statusHistories()->create([
            'from_status' => null,
            'to_status' => ApplicationStatus::Received,
            'changed_by' => $citizen->id,
        ]);
        $application->statusHistories()->create([
            'from_status' => ApplicationStatus::Received,
            'to_status' => ApplicationStatus::Processing,
            'changed_by' => $citizen->id,
        ]);
        $application->statusHistories()->create([
            'from_status' => ApplicationStatus::Processing,
            'to_status' => ApplicationStatus::Approved,
            'changed_by' => $citizen->id,
        ]);

        $response = $this->actingAs($citizen, 'sanctum')->getJson("/api/v1/applications/{$application->id}");

        $response->assertOk()
            ->assertJsonPath('data.status', 'approved')
            ->assertJsonPath('data.result_note', 'Đã cấp kết quả')
            ->assertJsonPath('data.completed_at', $application->completed_at->toISOString())
            ->assertJsonCount(3, 'data.timeline');
    }

    public function test_show_exposes_rejection_reason_for_owner(): void
    {
        $citizen = $this->makeCitizen();
        $application = $this->makeApplication($citizen, [
            'status' => ApplicationStatus::Rejected,
            'processing_started_at' => now()->subDay(),
            'completed_at' => now(),
            'rejection_reason' => 'Thiếu giấy tờ bắt buộc',
        ]);

        $application->statusHistories()->create([
            'from_status' => ApplicationStatus::Processing,
            'to_status' => ApplicationStatus::Rejected,
            'changed_by' => $citizen->id,
        ]);

        $this->actingAs($citizen, 'sanctum')
            ->getJson("/api/v1/applications/{$application->id}")
            ->assertOk()
            ->assertJsonPath('data.rejection_reason', 'Thiếu giấy tờ bắt buộc');
    }

    public function test_show_exposes_supplement_note_and_missing_documents(): void
    {
        $citizen = $this->makeCitizen();
        $service = $this->makeService([
            'document_requirements' => [
                ['code' => 'citizen_id_copy', 'label' => 'Bản sao CCCD', 'required' => true, 'type' => 'mixed'],
            ],
        ]);

        $application = Application::query()->create([
            'application_code' => fake()->unique()->numerify('HS-20260820-#####'),
            'citizen_id' => $citizen->id,
            'service_type_id' => $service->id,
            'status' => ApplicationStatus::SupplementRequired,
            'form_data' => ['full_name' => 'Nguyen Van A'],
            'submitted_at' => now(),
            'processing_started_at' => now(),
        ]);

        $application->statusHistories()->create([
            'from_status' => ApplicationStatus::Processing,
            'to_status' => ApplicationStatus::SupplementRequired,
            'changed_by' => $citizen->id,
            'note' => 'Cần bổ sung sổ hộ khẩu',
        ]);

        $this->actingAs($citizen, 'sanctum')
            ->getJson("/api/v1/applications/{$application->id}")
            ->assertOk()
            ->assertJsonPath('data.supplement_note', 'Cần bổ sung sổ hộ khẩu')
            ->assertJsonPath('data.missing_required_documents.0.code', 'citizen_id_copy');
    }

    public function test_other_citizen_cannot_view_application(): void
    {
        $owner = $this->makeCitizen();
        $other = $this->makeCitizen();
        $application = $this->makeApplication($owner);

        $this->actingAs($other, 'sanctum')
            ->getJson("/api/v1/applications/{$application->id}")
            ->assertForbidden();
    }
}
