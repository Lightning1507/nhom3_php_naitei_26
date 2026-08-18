<?php

namespace Tests\Feature\Admin\Departments;

use App\Enums\UserRole;
use App\Models\Department;
use App\Models\ServiceType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepartmentQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_is_case_insensitive_and_treats_wildcards_as_literals(): void
    {
        $actor = $this->superAdmin();
        Department::factory()->create(['name' => 'Phòng Một_Phần', 'code' => 'SEARCH-01']);
        Department::factory()->create(['name' => 'Phòng MộtXPhần', 'code' => 'SEARCH-02']);
        Department::factory()->create(['name' => 'Phòng khác', 'code' => 'PERCENT-01', 'address' => 'Khu 10%']);

        $this->actingAs($actor)
            ->get(route('admin.departments.index', ['search' => 'một_phần']))
            ->assertOk()
            ->assertSee('SEARCH-01')
            ->assertDontSee('SEARCH-02');

        $this->actingAs($actor)
            ->get(route('admin.departments.index', ['search' => '10%']))
            ->assertOk()
            ->assertSee('PERCENT-01')
            ->assertDontSee('SEARCH-01');
    }

    public function test_manager_and_status_filters_never_expand_actor_scope(): void
    {
        $actor = $this->superAdmin();
        $managerA = User::factory()->manager()->create(['name' => 'Manager A']);
        $managerB = User::factory()->manager()->create(['name' => 'Manager B']);
        $activeA = Department::factory()->ledBy($managerA)->create(['code' => 'ACTIVE-A']);
        $archivedA = Department::factory()->ledBy($managerA)->archived()->create(['code' => 'ARCHIVED-A']);
        $activeB = Department::factory()->ledBy($managerB)->create(['code' => 'ACTIVE-B']);

        $this->actingAs($actor)
            ->get(route('admin.departments.index'))
            ->assertOk()
            ->assertSee($activeA->code)
            ->assertSee($activeB->code)
            ->assertDontSee($archivedA->code);

        $this->actingAs($actor)
            ->get(route('admin.departments.index', ['status' => 'archived']))
            ->assertOk()
            ->assertSee($archivedA->code)
            ->assertDontSee($activeA->code)
            ->assertDontSee($activeB->code);

        $this->actingAs($actor)
            ->get(route('admin.departments.index', ['manager_id' => $managerA->id, 'status' => 'all']))
            ->assertOk()
            ->assertSee($activeA->code)
            ->assertSee($archivedA->code)
            ->assertDontSee($activeB->code);

        $this->actingAs($managerA)
            ->get(route('admin.departments.index', ['manager_id' => $managerB->id, 'status' => 'all']))
            ->assertOk()
            ->assertDontSee($activeA->code)
            ->assertDontSee($activeB->code);
    }

    public function test_pagination_is_stable_and_preserves_all_filters(): void
    {
        $actor = $this->superAdmin();
        $manager = User::factory()->manager()->create();

        foreach (range(1, 17) as $number) {
            Department::factory()->ledBy($manager)->create([
                'name' => "Phòng phân trang {$number}",
                'code' => sprintf('PAGE-%03d', $number),
            ]);
        }

        $response = $this->actingAs($actor)->get(route('admin.departments.index', [
            'search' => 'phân trang',
            'manager_id' => $manager->id,
            'status' => 'all',
            'page' => 1,
        ]));

        $response->assertOk()->assertViewHas('departments', function ($departments) use ($manager): bool {
            parse_str((string) parse_url($departments->url(2), PHP_URL_QUERY), $pageQuery);

            return $departments->count() === 15
                && $departments->first()->code === 'PAGE-001'
                && $departments->last()->code === 'PAGE-015'
                && $pageQuery['search'] === 'phân trang'
                && (int) $pageQuery['manager_id'] === $manager->id
                && $pageQuery['status'] === 'all';
        });
    }

    public function test_scoped_summary_counts_and_read_only_services_are_rendered(): void
    {
        $manager = User::factory()->manager()->create();
        $staff = User::factory()->staff()->create();
        $active = Department::factory()->ledBy($manager)->create(['code' => 'SCOPED-ACTIVE']);
        Department::factory()->ledBy($manager)->archived()->create(['code' => 'SCOPED-ARCHIVED']);
        Department::factory()->create(['code' => 'OUTSIDE-SCOPE']);
        $active->users()->attach($staff);
        $service = ServiceType::factory()->create([
            'responsible_department_id' => $active->id,
            'name' => 'Dịch vụ chỉ đọc',
            'code' => 'READ-ONLY-01',
        ]);
        $archivedService = ServiceType::factory()->create([
            'responsible_department_id' => $active->id,
            'name' => 'Dịch vụ đã lưu trữ',
            'code' => 'READ-ONLY-02',
        ]);
        $archivedService->delete();

        $index = $this->actingAs($manager)->get(route('admin.departments.index', ['status' => 'all']));
        $index->assertOk()
            ->assertViewHas('stats', [
                'total' => 2,
                'active' => 1,
                'missing_leader' => 0,
                'staff_memberships' => 1,
            ])
            ->assertViewHas('departments', fn ($departments): bool => $departments->firstWhere('id', $active->id)->members_count === 2
                && $departments->firstWhere('id', $active->id)->service_types_count === 2)
            ->assertSee('SCOPED-ACTIVE')
            ->assertSee('SCOPED-ARCHIVED')
            ->assertDontSee('OUTSIDE-SCOPE');

        $this->actingAs($manager)
            ->get(route('admin.departments.show', $active))
            ->assertOk()
            ->assertSee($service->name)
            ->assertSee($service->code)
            ->assertSee($archivedService->name)
            ->assertSee('Đã lưu trữ')
            ->assertSee('Chỉ đọc')
            ->assertDontSee('Sửa dịch vụ')
            ->assertDontSee('Xóa dịch vụ');
    }

    public function test_invalid_query_inputs_are_rejected(): void
    {
        $actor = $this->superAdmin();

        $this->actingAs($actor)
            ->from(route('admin.departments.index'))
            ->get(route('admin.departments.index', [
                'search' => str_repeat('a', 101),
                'manager_id' => 'invalid',
                'status' => 'deleted',
                'page' => 0,
            ]))
            ->assertRedirect(route('admin.departments.index'))
            ->assertSessionHasErrors(['search', 'manager_id', 'status', 'page']);
    }

    private function superAdmin(): User
    {
        return User::factory()->withRole(UserRole::SuperAdmin)->create();
    }
}
