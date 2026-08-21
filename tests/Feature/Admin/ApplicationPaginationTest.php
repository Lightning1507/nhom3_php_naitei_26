<?php

namespace Tests\Feature\Admin;

use App\Enums\ApplicationStatus;
use App\Enums\UserRole;
use App\Models\Application;
use App\Models\ServiceType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ApplicationPaginationTest extends TestCase
{
    use RefreshDatabase;

    public function test_pagination_is_stable_twenty_per_page_and_preserves_filters(): void
    {
        $actor = User::factory()->withRole(UserRole::SuperAdmin)->create();
        $service = ServiceType::factory()->create();
        $submittedAt = '2026-08-20 08:00:00';

        foreach (range(1, 21) as $number) {
            Application::factory()->create([
                'application_code' => sprintf('HS-PAGE-%03d', $number),
                'service_type_id' => $service->id,
                'status' => ApplicationStatus::Received,
                'submitted_at' => $submittedAt,
            ]);
        }

        $response = $this->actingAs($actor)->get(route('admin.applications.index', [
            'q' => 'HS-PAGE',
            'status' => ApplicationStatus::Received->value,
            'service_type_id' => $service->id,
            'sort' => 'newest',
            'page' => 1,
        ]));

        $response->assertOk()->assertViewHas('applications', function ($applications) use ($service): bool {
            parse_str((string) parse_url($applications->url(2), PHP_URL_QUERY), $pageQuery);

            return $applications->count() === 20
                && $applications->total() === 21
                && $applications->first()->application_code === 'HS-PAGE-021'
                && $applications->last()->application_code === 'HS-PAGE-002'
                && $pageQuery['q'] === 'HS-PAGE'
                && $pageQuery['status'] === ApplicationStatus::Received->value
                && (int) $pageQuery['service_type_id'] === $service->id
                && $pageQuery['sort'] === 'newest';
        });

        $this->actingAs($actor)
            ->get(route('admin.applications.index', [
                'q' => 'HS-PAGE',
                'status' => ApplicationStatus::Received->value,
                'service_type_id' => $service->id,
                'sort' => 'newest',
                'page' => 2,
            ]))
            ->assertOk()
            ->assertViewHas('applications', fn ($applications): bool => $applications->count() === 1
                && $applications->first()->application_code === 'HS-PAGE-001');
    }

    public function test_out_of_range_page_redirects_to_last_valid_page_with_filters(): void
    {
        $actor = User::factory()->withRole(UserRole::SuperAdmin)->create();
        Application::factory()->create(['application_code' => 'HS-VALID-PAGE']);

        $this->actingAs($actor)
            ->get(route('admin.applications.index', [
                'q' => 'VALID-PAGE',
                'sort' => 'code_asc',
                'page' => 99,
            ]))
            ->assertRedirect(route('admin.applications.index', [
                'q' => 'VALID-PAGE',
                'sort' => 'code_asc',
                'page' => 1,
            ]));
    }

    public function test_empty_scope_and_filtered_no_result_have_distinct_states(): void
    {
        $staff = User::factory()->staff()->create();

        $this->actingAs($staff)
            ->get(route('admin.applications.index'))
            ->assertOk()
            ->assertSee('Chưa có hồ sơ trong phạm vi của bạn')
            ->assertDontSee('Không tìm thấy hồ sơ phù hợp');

        Application::factory()->assignedTo($staff)->create(['application_code' => 'HS-AVAILABLE']);

        $this->actingAs($staff)
            ->get(route('admin.applications.index', ['q' => 'NO-MATCH']))
            ->assertOk()
            ->assertSee('Không tìm thấy hồ sơ phù hợp')
            ->assertSee('Xóa bộ lọc')
            ->assertDontSee('Chưa có hồ sơ trong phạm vi của bạn');
    }

    public function test_list_queries_are_bounded_and_get_request_does_not_mutate_applications(): void
    {
        $actor = User::factory()->withRole(UserRole::SuperAdmin)->create();
        $application = Application::factory()->create(['application_code' => 'HS-READ-ONLY']);
        $before = $application->fresh()->getAttributes();
        $queryCount = 0;
        DB::listen(static function () use (&$queryCount): void {
            $queryCount++;
        });

        $this->actingAs($actor)
            ->get(route('admin.applications.index', ['q' => 'READ-ONLY']))
            ->assertOk();

        $this->assertLessThanOrEqual(16, $queryCount, 'Application worklist must use bounded eager-loaded queries.');
        $this->assertSame($before, $application->fresh()->getAttributes());
    }
}
