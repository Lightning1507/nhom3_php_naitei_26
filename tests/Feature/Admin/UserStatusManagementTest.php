<?php

namespace Tests\Feature\Admin;

use App\Actions\User\SetUserActiveStatus;
use App\Enums\ApplicationStatus;
use App\Enums\UserRole;
use App\Models\ActivityLog;
use App\Models\Application;
use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class UserStatusManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_deactivate_and_reactivate_with_atomic_audit_and_next_request_effect(): void
    {
        $actor = $this->superAdmin();
        $target = User::factory()->staff()->create(['phone' => '0900000000']);
        $before = $target->only(['name', 'email', 'role', 'citizen_id', 'phone']);

        $this->actingAs($actor)
            ->withHeader('User-Agent', 'F07-status-test')
            ->patch(route('admin.users.status.update', $target), [
                'is_active' => false,
                'role' => UserRole::Manager->value,
                'password' => 'must-not-change',
            ])->assertRedirect(route('admin.users.show', $target));

        $this->assertFalse($target->fresh()->is_active);
        $this->assertSame($before, $target->fresh()->only(array_keys($before)));
        $deactivated = ActivityLog::query()->where('action', 'user.deactivated')->sole();
        $this->assertSame($actor->id, $deactivated->actor_id);
        $this->assertSame($target->id, $deactivated->subject_id);
        $this->assertSame('F07-status-test', $deactivated->user_agent);
        $this->assertTrue($deactivated->metadata['before']['is_active']);
        $this->assertFalse($deactivated->metadata['after']['is_active']);

        $this->actingAs($target->fresh())->get(route('admin.dashboard'))->assertForbidden();

        $this->actingAs($actor)->patch(route('admin.users.status.update', $target), [
            'is_active' => true,
        ])->assertRedirect(route('admin.users.show', $target));

        $this->assertTrue($target->fresh()->is_active);
        $this->assertSame(2, ActivityLog::query()
            ->whereIn('action', ['user.activated', 'user.deactivated'])
            ->count());
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'user.activated',
            'subject_id' => $target->id,
        ]);
    }

    public function test_non_super_admin_cannot_change_user_status(): void
    {
        $target = User::factory()->create();

        foreach ([
            User::factory()->create(),
            User::factory()->staff()->create(),
            User::factory()->manager()->create(),
        ] as $actor) {
            $this->actingAs($actor)->patch(route('admin.users.status.update', $target), [
                'is_active' => false,
            ])->assertForbidden();
        }

        $this->assertTrue($target->fresh()->is_active);
        $this->assertSame(0, ActivityLog::query()
            ->whereIn('action', ['user.activated', 'user.deactivated'])
            ->count());
    }

    public function test_self_and_last_active_super_admin_deactivation_are_blocked(): void
    {
        $onlyAdmin = $this->superAdmin();
        $this->actingAs($onlyAdmin)->patch(route('admin.users.status.update', $onlyAdmin), [
            'is_active' => false,
        ])->assertSessionHasErrors('is_active');

        $otherAdmin = $this->superAdmin();
        $this->actingAs($onlyAdmin)->patch(route('admin.users.status.update', $onlyAdmin), [
            'is_active' => false,
        ])->assertSessionHasErrors('is_active');

        $this->assertTrue($onlyAdmin->fresh()->is_active);
        $this->assertTrue($otherAdmin->fresh()->is_active);
        $this->assertDatabaseCount('activity_logs', 0);
    }

    public function test_active_department_leader_is_blocked_but_archived_leadership_is_not(): void
    {
        $actor = $this->superAdmin();
        $activeLeader = User::factory()->manager()->create();
        Department::factory()->ledBy($activeLeader)->active()->create();

        $this->actingAs($actor)->patch(route('admin.users.status.update', $activeLeader), [
            'is_active' => false,
        ])->assertSessionHasErrors('is_active');
        $this->assertTrue($activeLeader->fresh()->is_active);

        $historicalLeader = User::factory()->manager()->create();
        Department::factory()->ledBy($historicalLeader)->archived()->create();

        $this->actingAs($actor)->patch(route('admin.users.status.update', $historicalLeader), [
            'is_active' => false,
        ])->assertRedirect(route('admin.users.show', $historicalLeader));
        $this->assertFalse($historicalLeader->fresh()->is_active);
    }

    public function test_unfinished_assignment_blocks_deactivation_but_terminal_or_archived_application_does_not(): void
    {
        $actor = $this->superAdmin();
        $busyStaff = User::factory()->staff()->create();
        Application::factory()->assignedTo($busyStaff)->withStatus(ApplicationStatus::Processing)->create();

        $this->actingAs($actor)->patch(route('admin.users.status.update', $busyStaff), [
            'is_active' => false,
        ])->assertSessionHasErrors('is_active');
        $this->assertTrue($busyStaff->fresh()->is_active);

        $terminalStaff = User::factory()->staff()->create();
        $department = Department::factory()->create();
        $department->users()->attach($terminalStaff);
        $terminalApplication = Application::factory()->assignedTo($terminalStaff)->withStatus(ApplicationStatus::Approved)->create();
        $this->actingAs($actor)->patch(route('admin.users.status.update', $terminalStaff), [
            'is_active' => false,
        ])->assertRedirect(route('admin.users.show', $terminalStaff));

        $historicalStaff = User::factory()->staff()->create();
        $archived = Application::factory()->assignedTo($historicalStaff)->withStatus(ApplicationStatus::Processing)->create();
        $archived->delete();
        $this->actingAs($actor)->patch(route('admin.users.status.update', $historicalStaff), [
            'is_active' => false,
        ])->assertRedirect(route('admin.users.show', $historicalStaff));

        $this->assertFalse($terminalStaff->fresh()->is_active);
        $this->assertFalse($historicalStaff->fresh()->is_active);
        $this->assertDatabaseHas('department_user', [
            'department_id' => $department->id,
            'user_id' => $terminalStaff->id,
        ]);
        $this->assertDatabaseHas('applications', [
            'id' => $terminalApplication->id,
            'assigned_staff_id' => $terminalStaff->id,
            'status' => ApplicationStatus::Approved->value,
        ]);
    }

    public function test_repeated_desired_state_is_an_idempotent_no_op_without_audit(): void
    {
        $actor = $this->superAdmin();
        $target = User::factory()->inactive()->create();
        $updatedAt = $target->updated_at;

        $this->actingAs($actor)->patch(route('admin.users.status.update', $target), [
            'is_active' => false,
        ])->assertRedirect(route('admin.users.show', $target));

        $this->assertFalse($target->fresh()->is_active);
        $this->assertTrue($updatedAt->equalTo($target->fresh()->updated_at));
        $this->assertDatabaseCount('activity_logs', 0);
    }

    public function test_audit_failure_rolls_back_status_change(): void
    {
        $actor = $this->superAdmin();
        $target = User::factory()->create();

        ActivityLog::creating(static function (): never {
            throw new RuntimeException('simulated audit failure');
        });

        try {
            app(SetUserActiveStatus::class)->handle($target, false, $actor);
            $this->fail('The simulated audit failure was not raised.');
        } catch (RuntimeException $exception) {
            $this->assertSame('simulated audit failure', $exception->getMessage());
        } finally {
            ActivityLog::flushEventListeners();
        }

        $this->assertTrue($target->fresh()->is_active);
        $this->assertDatabaseCount('activity_logs', 0);
    }

    private function superAdmin(): User
    {
        return User::factory()->withRole(UserRole::SuperAdmin)->create();
    }
}
