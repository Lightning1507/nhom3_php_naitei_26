<?php

namespace Tests\Feature\Api\V1;

use App\Enums\ApplicationStatus;
use App\Enums\DocumentKind;
use App\Enums\UserRole;
use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Models\ServiceType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ApplicationAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private function makeCitizen(): User
    {
        return User::factory()->withRole(UserRole::Citizen)->create([
            'citizen_id' => fake()->unique()->numerify('#############'),
        ]);
    }

    private function makeApplication(User $citizen, array $overrides = []): Application
    {
        return Application::query()->create(array_merge([
            'application_code' => fake()->unique()->numerify('HS-20260818-#####'),
            'citizen_id' => $citizen->id,
            'service_type_id' => ServiceType::factory()->create()->id,
            'status' => ApplicationStatus::Received,
            'form_data' => ['full_name' => 'Nguyen Van A'],
            'submitted_at' => now(),
        ], $overrides));
    }

    private function makeDocument(Application $application, array $overrides = []): ApplicationDocument
    {
        return ApplicationDocument::query()->create(array_merge([
            'application_id' => $application->id,
            'uploaded_by' => $application->citizen_id,
            'document_kind' => DocumentKind::Submission,
            'original_name' => 'baocao.pdf',
            'disk' => 'local',
            'path' => 'applications/'.$application->id.'/baocao.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 1024,
        ], $overrides));
    }

    public function test_guest_cannot_submit_an_application(): void
    {
        $service = ServiceType::factory()->create();

        $this->postJson('/api/v1/applications', ['service_type_id' => $service->id])
            ->assertUnauthorized();

        $this->assertDatabaseCount('applications', 0);
    }

    public function test_guest_cannot_list_applications(): void
    {
        $this->getJson('/api/v1/applications')->assertUnauthorized();
    }

    public function test_guest_cannot_view_an_application(): void
    {
        $citizen = $this->makeCitizen();
        $application = $this->makeApplication($citizen);

        $this->getJson("/api/v1/applications/{$application->id}")->assertUnauthorized();
    }

    public function test_guest_cannot_upload_a_document(): void
    {
        $citizen = $this->makeCitizen();
        $application = $this->makeApplication($citizen);

        $this->postJson("/api/v1/applications/{$application->id}/documents", [
            'document' => UploadedFile::fake()->create('baocao.pdf', 100, 'application/pdf'),
        ])->assertUnauthorized();
    }

    public function test_guest_cannot_download_a_document(): void
    {
        $citizen = $this->makeCitizen();
        $application = $this->makeApplication($citizen);
        $document = $this->makeDocument($application);

        $this->get("/api/v1/applications/{$application->id}/documents/{$document->id}")
            ->assertUnauthorized();
    }

    public function test_guest_cannot_delete_a_document(): void
    {
        $citizen = $this->makeCitizen();
        $application = $this->makeApplication($citizen);
        $document = $this->makeDocument($application);

        $this->deleteJson("/api/v1/applications/{$application->id}/documents/{$document->id}")
            ->assertUnauthorized();
    }

    public function test_citizen_cannot_view_another_citizens_application(): void
    {
        $owner = $this->makeCitizen();
        $intruder = $this->makeCitizen();
        $application = $this->makeApplication($owner);

        $this->actingAs($intruder, 'sanctum')
            ->getJson("/api/v1/applications/{$application->id}")
            ->assertForbidden();
    }

    public function test_citizen_cannot_upload_to_another_citizens_application(): void
    {
        $owner = $this->makeCitizen();
        $intruder = $this->makeCitizen();
        $application = $this->makeApplication($owner);

        $this->actingAs($intruder, 'sanctum')
            ->postJson("/api/v1/applications/{$application->id}/documents", [
                'document' => UploadedFile::fake()->create('baocao.pdf', 100, 'application/pdf'),
                'requirement_code' => 'citizen_id_copy',
            ])
            ->assertForbidden();
    }

    public function test_citizen_cannot_download_another_citizens_document(): void
    {
        $owner = $this->makeCitizen();
        $intruder = $this->makeCitizen();
        $application = $this->makeApplication($owner);
        $document = $this->makeDocument($application);

        $this->actingAs($intruder, 'sanctum')
            ->get("/api/v1/applications/{$application->id}/documents/{$document->id}")
            ->assertForbidden();
    }

    public function test_citizen_cannot_delete_another_citizens_document(): void
    {
        $owner = $this->makeCitizen();
        $intruder = $this->makeCitizen();
        $application = $this->makeApplication($owner);
        $document = $this->makeDocument($application);

        $this->actingAs($intruder, 'sanctum')
            ->deleteJson("/api/v1/applications/{$application->id}/documents/{$document->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('application_documents', [
            'id' => $document->id,
            'deleted_at' => null,
        ]);
    }

    public function test_citizen_cannot_read_another_citizens_application_via_list(): void
    {
        $owner = $this->makeCitizen();
        $intruder = $this->makeCitizen();
        $this->makeApplication($owner);

        $this->actingAs($intruder, 'sanctum')
            ->getJson('/api/v1/applications')
            ->assertOk()
            ->assertJsonCount(0, 'data.data');
    }

    public function test_owner_can_view_own_application(): void
    {
        $citizen = $this->makeCitizen();
        $application = $this->makeApplication($citizen);

        $this->actingAs($citizen, 'sanctum')
            ->getJson("/api/v1/applications/{$application->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $application->id);
    }

    public function test_staff_cannot_submit_an_application(): void
    {
        $staff = User::factory()->staff()->create();
        $service = ServiceType::factory()->create();

        $this->actingAs($staff, 'sanctum')
            ->postJson('/api/v1/applications', [
                'service_type_id' => $service->id,
                'form_data' => ['full_name' => 'Nguyen Van A'],
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('applications', 0);
    }

    public function test_staff_can_download_any_citizens_document(): void
    {
        Storage::fake('local');

        $staff = User::factory()->staff()->create();
        $citizen = $this->makeCitizen();
        $application = $this->makeApplication($citizen);
        $document = $this->makeDocument($application);

        Storage::disk('local')->put($document->path, 'fake-content');

        $this->actingAs($staff, 'sanctum')
            ->get("/api/v1/applications/{$application->id}/documents/{$document->id}")
            ->assertOk()
            ->assertDownload('baocao.pdf');
    }

    public function test_inactive_citizen_cannot_submit_an_application(): void
    {
        $citizen = User::factory()->withRole(UserRole::Citizen)->inactive()->create([
            'citizen_id' => fake()->unique()->numerify('#############'),
        ]);
        $service = ServiceType::factory()->create();

        $this->actingAs($citizen, 'sanctum')
            ->postJson('/api/v1/applications', [
                'service_type_id' => $service->id,
                'form_data' => ['full_name' => 'Nguyen Van A'],
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('applications', 0);
    }

    public function test_submission_rejects_a_nonexistent_service(): void
    {
        $citizen = $this->makeCitizen();

        $this->actingAs($citizen, 'sanctum')
            ->postJson('/api/v1/applications', [
                'service_type_id' => 999999,
                'form_data' => ['full_name' => 'Nguyen Van A'],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['service_type_id']);

        $this->assertDatabaseCount('applications', 0);
    }

    public function test_submission_rejects_an_inactive_service(): void
    {
        $citizen = $this->makeCitizen();
        $service = ServiceType::factory()->create(['is_active' => false]);

        $this->actingAs($citizen, 'sanctum')
            ->postJson('/api/v1/applications', [
                'service_type_id' => $service->id,
                'form_data' => ['full_name' => 'Nguyen Van A'],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['service_type_id']);

        $this->assertDatabaseCount('applications', 0);
    }
}
