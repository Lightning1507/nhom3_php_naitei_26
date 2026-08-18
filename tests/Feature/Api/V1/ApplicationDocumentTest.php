<?php

namespace Tests\Feature\Api\V1;

use App\Enums\ApplicationStatus;
use App\Enums\UserRole;
use App\Models\Application;
use App\Models\ServiceType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ApplicationDocumentTest extends TestCase
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

    public function test_citizen_can_upload_a_pdf_document(): void
    {
        Storage::fake('local');

        $citizen = $this->makeCitizen();
        $application = $this->makeApplication($citizen);
        $file = UploadedFile::fake()->create('cmnd.pdf', 100, 'application/pdf');

        $response = $this->actingAs($citizen, 'sanctum')
            ->postJson("/api/v1/applications/{$application->id}/documents", [
                'document' => $file,
            ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.document_kind', 'submission')
            ->assertJsonPath('data.original_name', 'cmnd.pdf')
            ->assertJsonPath('data.mime_type', 'application/pdf');

        $this->assertDatabaseHas('application_documents', [
            'application_id' => $application->id,
            'uploaded_by' => $citizen->id,
            'document_kind' => 'submission',
            'original_name' => 'cmnd.pdf',
            'disk' => 'local',
            'mime_type' => 'application/pdf',
            'file_size' => $file->getSize(),
        ]);
    }

    public function test_citizen_can_upload_an_image_document(): void
    {
        Storage::fake('local');

        $citizen = $this->makeCitizen();
        $application = $this->makeApplication($citizen);

        $response = $this->actingAs($citizen, 'sanctum')
            ->postJson("/api/v1/applications/{$application->id}/documents", [
                'document' => UploadedFile::fake()->image('hopdong.jpg', 200, 100),
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.original_name', 'hopdong.jpg')
            ->assertJsonPath('data.mime_type', 'image/jpeg');

        $this->assertDatabaseHas('application_documents', [
            'application_id' => $application->id,
            'original_name' => 'hopdong.jpg',
            'document_kind' => 'submission',
        ]);
    }

    public function test_non_pdf_or_image_file_is_rejected(): void
    {
        Storage::fake('local');

        $citizen = $this->makeCitizen();
        $application = $this->makeApplication($citizen);

        $response = $this->actingAs($citizen, 'sanctum')
            ->postJson("/api/v1/applications/{$application->id}/documents", [
                'document' => UploadedFile::fake()->create('malware.exe', 100, 'application/octet-stream'),
            ]);

        $response->assertUnprocessable()->assertJsonValidationErrors(['document']);

        $this->assertDatabaseCount('application_documents', 0);
        Storage::disk('local')->assertDirectoryEmpty('applications');
    }

    public function test_oversized_file_is_rejected(): void
    {
        Storage::fake('local');

        $citizen = $this->makeCitizen();
        $application = $this->makeApplication($citizen);

        $response = $this->actingAs($citizen, 'sanctum')
            ->postJson("/api/v1/applications/{$application->id}/documents", [
                'document' => UploadedFile::fake()->create('big.pdf', 10241, 'application/pdf'),
            ]);

        $response->assertUnprocessable()->assertJsonValidationErrors(['document']);

        $this->assertDatabaseCount('application_documents', 0);
        Storage::disk('local')->assertDirectoryEmpty('applications');
    }

    public function test_owner_can_download_a_document(): void
    {
        Storage::fake('local');

        $citizen = $this->makeCitizen();
        $application = $this->makeApplication($citizen);

        $upload = $this->actingAs($citizen, 'sanctum')
            ->postJson("/api/v1/applications/{$application->id}/documents", [
                'document' => UploadedFile::fake()->create('baocao.pdf', 100, 'application/pdf'),
            ]);

        $documentId = $upload->json('data.id');

        $this->actingAs($citizen, 'sanctum')
            ->get("/api/v1/applications/{$application->id}/documents/{$documentId}")
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertDownload('baocao.pdf');
    }

    public function test_other_citizen_cannot_download_a_document(): void
    {
        Storage::fake('local');

        $owner = $this->makeCitizen();
        $intruder = $this->makeCitizen();
        $application = $this->makeApplication($owner);

        $upload = $this->actingAs($owner, 'sanctum')
            ->postJson("/api/v1/applications/{$application->id}/documents", [
                'document' => UploadedFile::fake()->create('baocao.pdf', 100, 'application/pdf'),
            ]);

        $documentId = $upload->json('data.id');

        $this->actingAs($intruder, 'sanctum')
            ->get("/api/v1/applications/{$application->id}/documents/{$documentId}")
            ->assertForbidden();
    }

    public function test_unauthenticated_download_is_rejected(): void
    {
        Storage::fake('local');

        $citizen = $this->makeCitizen();
        $application = $this->makeApplication($citizen);

        $this->get("/api/v1/applications/{$application->id}/documents/1")
            ->assertStatus(401);
    }

    public function test_owner_can_soft_delete_a_document_before_processing(): void
    {
        Storage::fake('local');

        $citizen = $this->makeCitizen();
        $application = $this->makeApplication($citizen);

        $upload = $this->actingAs($citizen, 'sanctum')
            ->postJson("/api/v1/applications/{$application->id}/documents", [
                'document' => UploadedFile::fake()->create('baocao.pdf', 100, 'application/pdf'),
            ]);

        $documentId = $upload->json('data.id');

        $this->actingAs($citizen, 'sanctum')
            ->deleteJson("/api/v1/applications/{$application->id}/documents/{$documentId}")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted('application_documents', ['id' => $documentId]);
    }

    public function test_document_cannot_be_deleted_after_processing_starts(): void
    {
        Storage::fake('local');

        $citizen = $this->makeCitizen();
        $application = $this->makeApplication($citizen, [
            'status' => ApplicationStatus::Processing,
        ]);

        $upload = $this->actingAs($citizen, 'sanctum')
            ->postJson("/api/v1/applications/{$application->id}/documents", [
                'document' => UploadedFile::fake()->create('baocao.pdf', 100, 'application/pdf'),
            ]);

        $documentId = $upload->json('data.id');

        $this->actingAs($citizen, 'sanctum')
            ->deleteJson("/api/v1/applications/{$application->id}/documents/{$documentId}")
            ->assertForbidden();

        $this->assertDatabaseHas('application_documents', [
            'id' => $documentId,
            'deleted_at' => null,
        ]);
    }

    public function test_citizen_cannot_upload_to_another_citizens_application(): void
    {
        Storage::fake('local');

        $owner = $this->makeCitizen();
        $other = $this->makeCitizen();
        $application = $this->makeApplication($owner);

        $this->actingAs($other, 'sanctum')
            ->postJson("/api/v1/applications/{$application->id}/documents", [
                'document' => UploadedFile::fake()->create('baocao.pdf', 100, 'application/pdf'),
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('application_documents', 0);
    }

    public function test_deleted_document_is_not_downloadable(): void
    {
        Storage::fake('local');

        $citizen = $this->makeCitizen();
        $application = $this->makeApplication($citizen);

        $upload = $this->actingAs($citizen, 'sanctum')
            ->postJson("/api/v1/applications/{$application->id}/documents", [
                'document' => UploadedFile::fake()->create('baocao.pdf', 100, 'application/pdf'),
            ]);

        $documentId = $upload->json('data.id');

        $this->actingAs($citizen, 'sanctum')
            ->deleteJson("/api/v1/applications/{$application->id}/documents/{$documentId}");

        $this->actingAs($citizen, 'sanctum')
            ->get("/api/v1/applications/{$application->id}/documents/{$documentId}")
            ->assertNotFound();
    }

    public function test_document_of_another_application_is_not_accessible(): void
    {
        Storage::fake('local');

        $citizen = $this->makeCitizen();
        $applicationA = $this->makeApplication($citizen);
        $applicationB = $this->makeApplication($citizen);

        $upload = $this->actingAs($citizen, 'sanctum')
            ->postJson("/api/v1/applications/{$applicationA->id}/documents", [
                'document' => UploadedFile::fake()->create('baocao.pdf', 100, 'application/pdf'),
            ]);

        $documentId = $upload->json('data.id');

        $this->actingAs($citizen, 'sanctum')
            ->get("/api/v1/applications/{$applicationB->id}/documents/{$documentId}")
            ->assertNotFound();
    }

    public function test_staff_cannot_upload_to_a_citizens_application(): void
    {
        Storage::fake('local');

        $citizen = $this->makeCitizen();
        $staff = User::factory()->withRole(UserRole::Staff)->create();
        $application = $this->makeApplication($citizen);

        $this->actingAs($staff, 'sanctum')
            ->postJson("/api/v1/applications/{$application->id}/documents", [
                'document' => UploadedFile::fake()->create('baocao.pdf', 100, 'application/pdf'),
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('application_documents', 0);
    }

    public function test_inactive_citizen_cannot_upload_a_document(): void
    {
        Storage::fake('local');

        $citizen = $this->makeCitizen();
        $application = $this->makeApplication($citizen);

        $citizen->forceFill(['is_active' => false])->save();

        $this->actingAs($citizen, 'sanctum')
            ->postJson("/api/v1/applications/{$application->id}/documents", [
                'document' => UploadedFile::fake()->create('baocao.pdf', 100, 'application/pdf'),
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('application_documents', 0);
    }

    public function test_inactive_citizen_cannot_download_a_document(): void
    {
        Storage::fake('local');

        $citizen = $this->makeCitizen();
        $application = $this->makeApplication($citizen);

        $upload = $this->actingAs($citizen, 'sanctum')
            ->postJson("/api/v1/applications/{$application->id}/documents", [
                'document' => UploadedFile::fake()->create('baocao.pdf', 100, 'application/pdf'),
            ]);

        $documentId = $upload->json('data.id');

        $citizen->forceFill(['is_active' => false])->save();

        $this->actingAs($citizen, 'sanctum')
            ->get("/api/v1/applications/{$application->id}/documents/{$documentId}")
            ->assertForbidden();
    }
}
