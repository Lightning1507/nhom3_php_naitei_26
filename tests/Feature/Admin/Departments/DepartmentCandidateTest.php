<?php

namespace Tests\Feature\Admin\Departments;

use App\Enums\UserRole;
use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepartmentCandidateTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_candidate_search_is_bounded_and_filters_role_and_status(): void
    {
        $actor = $this->superAdmin();
        User::factory()->count(22)->manager()->sequence(
            fn ($sequence) => ['name' => sprintf('Candidate Manager %02d', $sequence->index)],
        )->create();
        User::factory()->staff()->create(['name' => 'Candidate Staff']);
        User::factory()->manager()->inactive()->create(['name' => 'Candidate Inactive']);
        User::factory()->manager()->create(['name' => 'Candidate Deleted', 'deleted_at' => now()]);

        $this->actingAs($actor)
            ->getJson(route('admin.departments.manager-candidates', ['search' => 'Candidate']))
            ->assertOk()
            ->assertJsonCount(20, 'data')
            ->assertJsonPath('meta.has_more', true)
            ->assertJsonMissing(['name' => 'Candidate Staff'])
            ->assertJsonMissing(['name' => 'Candidate Inactive'])
            ->assertJsonMissing(['name' => 'Candidate Deleted']);

        $this->actingAs($actor)
            ->getJson(route('admin.departments.manager-candidates', ['search' => 'C']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('search');
    }

    public function test_member_candidates_exclude_existing_members_and_respect_actor_role(): void
    {
        $superAdmin = $this->superAdmin();
        $manager = User::factory()->manager()->create(['name' => 'Scope Manager']);
        $department = Department::factory()->ledBy($manager)->create();
        $staff = User::factory()->staff()->create(['name' => 'Scope Staff']);
        $managerCandidate = User::factory()->manager()->create(['name' => 'Scope Candidate Manager']);
        $existingStaff = User::factory()->staff()->create(['name' => 'Scope Existing Staff']);
        $inactiveStaff = User::factory()->staff()->inactive()->create(['name' => 'Scope Inactive Staff']);
        $department->users()->attach($existingStaff);

        $this->actingAs($superAdmin)
            ->getJson(route('admin.departments.member-candidates', [$department, 'search' => 'Scope']))
            ->assertOk()
            ->assertJsonFragment(['id' => $staff->id])
            ->assertJsonFragment(['id' => $managerCandidate->id])
            ->assertJsonMissing(['id' => $existingStaff->id])
            ->assertJsonMissing(['id' => $inactiveStaff->id]);

        $this->actingAs($manager)
            ->getJson(route('admin.departments.member-candidates', [$department, 'search' => 'Scope']))
            ->assertOk()
            ->assertJsonFragment(['id' => $staff->id])
            ->assertJsonMissing(['id' => $managerCandidate->id]);
    }

    public function test_candidate_routes_do_not_enumerate_out_of_scope_departments(): void
    {
        $manager = User::factory()->manager()->create();
        $otherDepartment = Department::factory()->create();

        $this->actingAs($manager)
            ->getJson(route('admin.departments.member-candidates', [
                $otherDepartment,
                'search' => 'Staff',
            ]))
            ->assertNotFound();

        $this->actingAs(User::factory()->withRole(UserRole::Staff)->create())
            ->getJson(route('admin.departments.manager-candidates', ['search' => 'Manager']))
            ->assertForbidden();
    }

    private function superAdmin(): User
    {
        return User::factory()->withRole(UserRole::SuperAdmin)->create();
    }
}
