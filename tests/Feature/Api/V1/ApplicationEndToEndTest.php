<?php

namespace Tests\Feature\Api\V1;

use App\Enums\ApplicationStatus;
use App\Enums\UserRole;
use App\Models\ServiceType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ApplicationEndToEndTest extends TestCase
{
    use RefreshDatabase;

    private function makeCitizen(): User
    {
        return User::factory()->withRole(UserRole::Citizen)->create([
            'citizen_id' => fake()->unique()->numerify('#############'),
        ]);
    }

    private function makeActiveService(): ServiceType
    {
        return ServiceType::factory()->create([
            'document_requirements' => [
                ['code' => 'citizen_id_copy', 'label' => 'Bản sao CCCD', 'required' => true, 'type' => 'pdf'],
            ],
        ]);
    }

    public function test_full_citizen_flow_via_routes(): void
    {
        Storage::fake('local');

        $citizen = $this->makeCitizen();
        $service = $this->makeActiveService();

        $this->actingAs($citizen, 'sanctum')
            ->getJson("/api/v1/services/{$service->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $service->id);

        $create = $this->actingAs($citizen, 'sanctum')
            ->postJson('/api/v1/applications', [
                'service_type_id' => $service->id,
                'form_data' => ['full_name' => 'Nguyen Van A'],
            ]);

        $create->assertCreated();
        $applicationId = $create->json('data.id');
        $applicationCode = $create->json('data.application_code');

        $this->assertDatabaseHas('applications', ['id' => $applicationId]);
        $this->assertDatabaseHas('application_status_histories', [
            'application_id' => $applicationId,
            'from_status' => null,
            'to_status' => ApplicationStatus::Received->value,
        ]);

        $upload = $this->actingAs($citizen, 'sanctum')
            ->postJson("/api/v1/applications/{$applicationId}/documents", [
                'document' => UploadedFile::fake()->create('cccd.pdf', 100, 'application/pdf'),
                'requirement_code' => 'citizen_id_copy',
            ]);

        $upload->assertCreated()
            ->assertJsonPath('data.original_name', 'cccd.pdf');

        $this->actingAs($citizen, 'sanctum')
            ->getJson('/api/v1/applications')
            ->assertOk()
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.application_code', $applicationCode);

        $show = $this->actingAs($citizen, 'sanctum')
            ->getJson("/api/v1/applications/{$applicationId}")
            ->assertOk()
            ->assertJsonPath('data.application_code', $applicationCode)
            ->assertJsonCount(1, 'data.documents');

        $document = $show->json('data.documents.0');
        $this->assertSame('cccd.pdf', $document['original_name']);
        $this->assertArrayNotHasKey('path', $document);
        $this->assertArrayNotHasKey('disk', $document);
    }
}
