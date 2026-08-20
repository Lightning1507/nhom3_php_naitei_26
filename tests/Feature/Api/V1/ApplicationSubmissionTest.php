<?php

namespace Tests\Feature\Api\V1;

use App\Enums\ApplicationStatus;
use App\Enums\DocumentKind;
use App\Enums\UserRole;
use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Models\ServiceType;
use App\Models\User;
use App\Services\ApplicationCodeService;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ApplicationSubmissionTest extends TestCase
{
    use RefreshDatabase;

    private function makeCitizen(): User
    {
        return User::factory()->withRole(UserRole::Citizen)->create([
            'citizen_id' => fake()->unique()->numerify('#############'),
        ]);
    }

    private function makeActiveService(array $overrides = []): ServiceType
    {
        return ServiceType::factory()->create($overrides);
    }

    public function test_citizen_can_submit_an_application(): void
    {
        $citizen = $this->makeCitizen();
        $service = $this->makeActiveService();

        $response = $this->actingAs($citizen, 'sanctum')->postJson('/api/v1/applications', [
            'service_type_id' => $service->id,
            'form_data' => [
                'full_name' => 'Nguyen Van A',
            ],
        ]);

        $response->assertCreated()->assertJsonPath('success', true);

        $code = $response->json('data.application_code');
        $this->assertMatchesRegularExpression('/^HS-\d{8}-\d{5}$/', $code);
        $this->assertSame('received', $response->json('data.status'));

        $this->assertDatabaseHas('applications', [
            'application_code' => $code,
            'citizen_id' => $citizen->id,
            'service_type_id' => $service->id,
            'status' => ApplicationStatus::Received->value,
        ]);

        $application = Application::query()->where('application_code', $code)->firstOrFail();
        $this->assertDatabaseHas('application_status_histories', [
            'application_id' => $application->id,
            'from_status' => null,
            'to_status' => ApplicationStatus::Received->value,
            'changed_by' => $citizen->id,
        ]);
    }

    public function test_missing_required_form_field_is_rejected(): void
    {
        $citizen = $this->makeCitizen();
        $service = $this->makeActiveService();

        $response = $this->actingAs($citizen, 'sanctum')->postJson('/api/v1/applications', [
            'service_type_id' => $service->id,
            'form_data' => [],
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['form_data.full_name']);

        $this->assertDatabaseCount('applications', 0);
    }

    public function test_optional_form_field_may_be_omitted(): void
    {
        $citizen = $this->makeCitizen();
        $service = $this->makeActiveService();

        $response = $this->actingAs($citizen, 'sanctum')->postJson('/api/v1/applications', [
            'service_type_id' => $service->id,
            'form_data' => ['full_name' => 'Nguyen Van B'],
        ]);

        $response->assertCreated();
    }

    public function test_inactive_service_is_rejected(): void
    {
        $citizen = $this->makeCitizen();
        $service = $this->makeActiveService(['is_active' => false]);

        $response = $this->actingAs($citizen, 'sanctum')->postJson('/api/v1/applications', [
            'service_type_id' => $service->id,
            'form_data' => ['full_name' => 'Nguyen Van C'],
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors(['service_type_id']);
    }

    public function test_missing_service_type_is_rejected(): void
    {
        $citizen = $this->makeCitizen();

        $response = $this->actingAs($citizen, 'sanctum')->postJson('/api/v1/applications', [
            'service_type_id' => 999999,
            'form_data' => [],
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors(['service_type_id']);
    }

    public function test_application_codes_are_unique_within_a_day(): void
    {
        $citizen = $this->makeCitizen();
        $service = $this->makeActiveService();

        $first = $this->actingAs($citizen, 'sanctum')->postJson('/api/v1/applications', [
            'service_type_id' => $service->id,
            'form_data' => ['full_name' => 'A'],
        ]);

        $second = $this->actingAs($citizen, 'sanctum')->postJson('/api/v1/applications', [
            'service_type_id' => $service->id,
            'form_data' => ['full_name' => 'B'],
        ]);

        $first->assertCreated();
        $second->assertCreated();

        $this->assertNotSame(
            $first->json('data.application_code'),
            $second->json('data.application_code'),
        );
    }

    public function test_duplicate_application_code_is_blocked_by_database(): void
    {
        $citizen = $this->makeCitizen();
        $service = $this->makeActiveService();

        Application::query()->create([
            'application_code' => 'HS-20260815-00001',
            'citizen_id' => $citizen->id,
            'service_type_id' => $service->id,
            'status' => ApplicationStatus::Received,
            'submitted_at' => now(),
        ]);

        $this->expectException(QueryException::class);

        DB::table('applications')->insert([
            'application_code' => 'HS-20260815-00001',
            'citizen_id' => $citizen->id,
            'service_type_id' => $service->id,
            'status' => ApplicationStatus::Received->value,
            'submitted_at' => now(),
        ]);
    }

    public function test_index_returns_only_the_calling_citizen_applications(): void
    {
        $citizenA = $this->makeCitizen();
        $citizenB = $this->makeCitizen();
        $service = $this->makeActiveService();

        $appA = Application::query()->create([
            'application_code' => 'HS-20260815-00011',
            'citizen_id' => $citizenA->id,
            'service_type_id' => $service->id,
            'status' => ApplicationStatus::Received,
            'submitted_at' => now(),
        ]);

        Application::query()->create([
            'application_code' => 'HS-20260815-00012',
            'citizen_id' => $citizenB->id,
            'service_type_id' => $service->id,
            'status' => ApplicationStatus::Received,
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($citizenA, 'sanctum')->getJson('/api/v1/applications');

        $response->assertOk()->assertJsonPath('success', true);

        $codes = collect($response->json('data.data'))->pluck('application_code')->all();
        $this->assertSame(['HS-20260815-00011'], $codes);
        $this->assertSame(1, $response->json('data.meta.total'));
    }

    public function test_citizen_cannot_view_another_citizens_application(): void
    {
        $citizenA = $this->makeCitizen();
        $citizenB = $this->makeCitizen();
        $service = $this->makeActiveService();

        $application = Application::query()->create([
            'application_code' => 'HS-20260815-00031',
            'citizen_id' => $citizenA->id,
            'service_type_id' => $service->id,
            'status' => ApplicationStatus::Received,
            'form_data' => ['full_name' => 'Nguyen Van A'],
            'submitted_at' => now(),
        ]);

        $this->actingAs($citizenB, 'sanctum')
            ->getJson("/api/v1/applications/{$application->id}")
            ->assertForbidden();
    }

    public function test_staff_cannot_submit_an_application(): void
    {
        $staff = User::factory()->withRole(UserRole::Staff)->create();
        $service = $this->makeActiveService();

        $this->actingAs($staff, 'sanctum')
            ->postJson('/api/v1/applications', [
                'service_type_id' => $service->id,
                'form_data' => ['full_name' => 'Nguyen Van E'],
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('applications', 0);
    }

    public function test_show_returns_the_requested_application(): void
    {
        $citizen = $this->makeCitizen();
        $service = $this->makeActiveService();

        $application = Application::query()->create([
            'application_code' => 'HS-20260815-00021',
            'citizen_id' => $citizen->id,
            'service_type_id' => $service->id,
            'status' => ApplicationStatus::Received,
            'form_data' => ['full_name' => 'Nguyen Van D'],
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($citizen, 'sanctum')->getJson("/api/v1/applications/{$application->id}");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.application_code', 'HS-20260815-00021')
            ->assertJsonPath('data.service_type.id', $service->id);
    }

    public function test_submission_honors_admin_form_schema_shape(): void
    {
        $citizen = $this->makeCitizen();
        $service = $this->makeActiveService([
            'form_schema' => [
                ['name' => 'full_name', 'type' => 'text', 'is_required' => true],
                ['name' => 'note', 'type' => 'text', 'is_required' => false],
                ['name' => 'attachment', 'type' => 'file', 'is_required' => true],
            ],
        ]);

        $missing = $this->actingAs($citizen, 'sanctum')->postJson('/api/v1/applications', [
            'service_type_id' => $service->id,
            'form_data' => ['note' => 'ghi chú'],
        ]);

        $missing->assertUnprocessable()->assertJsonValidationErrors(['form_data.full_name']);
        $this->assertDatabaseCount('applications', 0);

        $ok = $this->actingAs($citizen, 'sanctum')->postJson('/api/v1/applications', [
            'service_type_id' => $service->id,
            'form_data' => ['full_name' => 'Nguyen Van F'],
        ]);

        $ok->assertCreated();
    }

    public function test_show_includes_the_application_documents(): void
    {
        $citizen = $this->makeCitizen();
        $service = $this->makeActiveService();

        $application = Application::query()->create([
            'application_code' => 'HS-20260815-00022',
            'citizen_id' => $citizen->id,
            'service_type_id' => $service->id,
            'status' => ApplicationStatus::Received,
            'submitted_at' => now(),
        ]);

        ApplicationDocument::query()->create([
            'application_id' => $application->id,
            'uploaded_by' => $citizen->id,
            'document_kind' => DocumentKind::Submission,
            'original_name' => 'cmnd.pdf',
            'disk' => 'local',
            'path' => 'applications/'.$application->id.'/cmnd.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 1024,
        ]);

        $response = $this->actingAs($citizen, 'sanctum')->getJson("/api/v1/applications/{$application->id}");

        $response->assertOk()
            ->assertJsonPath('data.documents.0.original_name', 'cmnd.pdf')
            ->assertJsonCount(1, 'data.documents');
    }

    public function test_store_succeeds_with_missing_required_documents_and_reports_them(): void
    {
        $citizen = $this->makeCitizen();
        $service = $this->makeActiveService([
            'document_requirements' => [
                ['code' => 'citizen_id_copy', 'label' => 'Bản sao CCCD', 'required' => true, 'type' => 'mixed'],
                ['code' => 'household_copy', 'label' => 'Sổ hộ khẩu', 'required' => false, 'type' => 'image'],
            ],
        ]);

        $response = $this->actingAs($citizen, 'sanctum')->postJson('/api/v1/applications', [
            'service_type_id' => $service->id,
            'form_data' => ['full_name' => 'Nguyen Van A'],
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data.missing_required_documents')
            ->assertJsonPath('data.missing_required_documents.0.code', 'citizen_id_copy')
            ->assertJsonPath('data.missing_required_documents.0.label', 'Bản sao CCCD');
    }

    public function test_show_reports_only_still_missing_required_documents(): void
    {
        $citizen = $this->makeCitizen();
        $service = $this->makeActiveService([
            'document_requirements' => [
                ['code' => 'citizen_id_copy', 'label' => 'Bản sao CCCD', 'required' => true, 'type' => 'mixed'],
                ['code' => 'household_copy', 'label' => 'Sổ hộ khẩu', 'required' => true, 'type' => 'image'],
            ],
        ]);

        $application = Application::query()->create([
            'application_code' => 'HS-20260815-00023',
            'citizen_id' => $citizen->id,
            'service_type_id' => $service->id,
            'status' => ApplicationStatus::Received,
            'submitted_at' => now(),
        ]);

        ApplicationDocument::query()->create([
            'application_id' => $application->id,
            'uploaded_by' => $citizen->id,
            'document_kind' => DocumentKind::Submission,
            'original_name' => 'cccd.pdf',
            'requirement_code' => 'citizen_id_copy',
            'disk' => 'local',
            'path' => 'applications/'.$application->id.'/cccd.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 1024,
        ]);

        $response = $this->actingAs($citizen, 'sanctum')->getJson("/api/v1/applications/{$application->id}");

        $response->assertOk()
            ->assertJsonCount(1, 'data.missing_required_documents')
            ->assertJsonPath('data.missing_required_documents.0.code', 'household_copy')
            ->assertJsonPath('data.missing_required_documents.0.label', 'Sổ hộ khẩu');
    }

    public function test_show_does_not_report_missing_documents_once_processing_starts(): void
    {
        $citizen = $this->makeCitizen();
        $service = $this->makeActiveService([
            'document_requirements' => [
                ['code' => 'citizen_id_copy', 'label' => 'Bản sao CCCD', 'required' => true, 'type' => 'mixed'],
            ],
        ]);

        $application = Application::query()->create([
            'application_code' => 'HS-20260815-00024',
            'citizen_id' => $citizen->id,
            'service_type_id' => $service->id,
            'status' => ApplicationStatus::Processing,
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($citizen, 'sanctum')->getJson("/api/v1/applications/{$application->id}");

        $response->assertOk()
            ->assertJsonCount(0, 'data.missing_required_documents');
    }

    public function test_submission_codes_are_distinct_and_monotonic(): void
    {
        $service = app(ApplicationCodeService::class);
        $date = CarbonImmutable::parse('2026-08-15');

        $codes = collect(range(1, 20))
            ->map(fn (): string => $service->generateForDate($date))
            ->all();

        $this->assertCount(20, $codes);
        $this->assertSame(20, collect($codes)->unique()->count());
        $this->assertSame($codes, collect($codes)->sort()->values()->all());
        $this->assertSame('HS-20260815-00001', $codes[0]);
        $this->assertSame('HS-20260815-00020', $codes[19]);
    }

    public function test_index_does_not_perform_n_plus_one_queries(): void
    {
        $citizen = $this->makeCitizen();
        $service = $this->makeActiveService();

        foreach (range(1, 30) as $i) {
            Application::query()->create([
                'application_code' => 'HS-20260815-'.str_pad((string) $i, 5, '0', STR_PAD_LEFT),
                'citizen_id' => $citizen->id,
                'service_type_id' => $service->id,
                'status' => ApplicationStatus::Received,
                'submitted_at' => now(),
            ]);
        }

        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->actingAs($citizen, 'sanctum')->getJson('/api/v1/applications')->assertOk();

        $queryCount = count(DB::getQueryLog());

        $this->assertLessThanOrEqual(6, $queryCount, "List endpoint executed {$queryCount} queries.");
    }
}
