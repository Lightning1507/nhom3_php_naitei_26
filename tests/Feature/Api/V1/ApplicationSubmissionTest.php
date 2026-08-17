<?php

namespace Tests\Feature\Api\V1;

use App\Enums\ApplicationStatus;
use App\Enums\UserRole;
use App\Models\Application;
use App\Models\ServiceType;
use App\Models\User;
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
}
