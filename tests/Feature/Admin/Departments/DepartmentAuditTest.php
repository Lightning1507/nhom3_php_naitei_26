<?php

namespace Tests\Feature\Admin\Departments;

use App\Enums\UserRole;
use App\Models\ActivityLog;
use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class DepartmentAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_and_update_audits_store_actor_and_snapshots(): void
    {
        $actor = $this->superAdmin();

        $this->actingAs($actor)->post(route('admin.departments.store'), [
            'name' => 'Phòng Kiểm soát',
            'code' => 'KS-01',
            'address' => 'Tầng 3',
        ])->assertRedirect();

        $department = Department::query()->where('code', 'KS-01')->firstOrFail();
        $created = ActivityLog::query()->where('action', 'department.created')->firstOrFail();

        $this->assertSame($actor->id, $created->actor_id);
        $this->assertSame($department->id, $created->subject_id);
        $this->assertSame('KS-01', data_get($created->metadata, 'after.code'));
        $this->assertSame(0, data_get($created->metadata, 'after.lock_version'));

        $this->actingAs($actor)->patch(route('admin.departments.update', $department), [
            'name' => 'Phòng Kiểm soát nội bộ',
            'code' => 'KS-02',
            'address' => 'Tầng 4',
            'version' => 0,
        ])->assertRedirect();

        $updated = ActivityLog::query()->where('action', 'department.updated')->firstOrFail();
        $this->assertSame($actor->id, $updated->actor_id);
        $this->assertSame('KS-01', data_get($updated->metadata, 'before.code'));
        $this->assertSame('KS-02', data_get($updated->metadata, 'after.code'));
        $this->assertSame(1, data_get($updated->metadata, 'after.lock_version'));
    }

    public function test_create_rolls_back_when_audit_insert_fails(): void
    {
        ActivityLog::creating(static function (): void {
            throw new RuntimeException('Forced audit failure.');
        });

        try {
            $this->actingAs($this->superAdmin())->post(route('admin.departments.store'), [
                'name' => 'Không được lưu',
                'code' => 'ROLLBACK-01',
                'address' => null,
            ]);
        } catch (RuntimeException $exception) {
            $this->assertSame('Forced audit failure.', $exception->getMessage());
        } finally {
            ActivityLog::flushEventListeners();
        }

        $this->assertDatabaseMissing('departments', ['code' => 'ROLLBACK-01']);
        $this->assertDatabaseMissing('activity_logs', ['action' => 'department.created']);
    }

    public function test_update_rolls_back_when_audit_insert_fails(): void
    {
        $department = Department::factory()->create([
            'name' => 'Tên trước cập nhật',
            'code' => 'ROLLBACK-02',
        ]);

        ActivityLog::creating(static function (): void {
            throw new RuntimeException('Forced audit failure.');
        });

        try {
            $this->actingAs($this->superAdmin())->patch(route('admin.departments.update', $department), [
                'name' => 'Tên không được lưu',
                'code' => 'ROLLBACK-03',
                'address' => null,
                'version' => 0,
            ]);
        } catch (RuntimeException $exception) {
            $this->assertSame('Forced audit failure.', $exception->getMessage());
        } finally {
            ActivityLog::flushEventListeners();
        }

        $department->refresh();
        $this->assertSame('Tên trước cập nhật', $department->name);
        $this->assertSame('ROLLBACK-02', $department->code);
        $this->assertSame(0, $department->lock_version);
        $this->assertDatabaseMissing('activity_logs', ['action' => 'department.updated']);
    }

    public function test_leader_add_and_remove_events_store_actor_member_and_version_metadata(): void
    {
        $actor = $this->superAdmin();
        $leader = User::factory()->manager()->create();
        $staff = User::factory()->staff()->create();
        $department = Department::factory()->create();

        $this->actingAs($actor)->patch(route('admin.departments.leader.update', $department), [
            'leader_id' => $leader->id,
            'version' => 0,
        ])->assertRedirect();
        $this->actingAs($actor)->post(route('admin.departments.members.store', $department), [
            'user_id' => $staff->id,
            'version' => 1,
        ])->assertRedirect();
        $this->actingAs($actor)->delete(route('admin.departments.members.destroy', [$department, $staff]), [
            'version' => 2,
        ])->assertRedirect();

        $leaderEvent = ActivityLog::query()->where('action', 'department.leader_changed')->firstOrFail();
        $addedEvent = ActivityLog::query()->where('action', 'department.member_added')->firstOrFail();
        $removedEvent = ActivityLog::query()->where('action', 'department.member_removed')->firstOrFail();

        $this->assertSame($actor->id, $leaderEvent->actor_id);
        $this->assertSame($leader->id, data_get($leaderEvent->metadata, 'new_leader.id'));
        $this->assertTrue(data_get($leaderEvent->metadata, 'auto_membership'));
        $this->assertSame($staff->id, data_get($addedEvent->metadata, 'member.id'));
        $this->assertSame(2, data_get($addedEvent->metadata, 'after.lock_version'));
        $this->assertSame($staff->id, data_get($removedEvent->metadata, 'member.id'));
        $this->assertSame(3, data_get($removedEvent->metadata, 'after.lock_version'));
    }

    public function test_membership_mutation_rolls_back_when_audit_insert_fails(): void
    {
        $actor = $this->superAdmin();
        $staff = User::factory()->staff()->create();
        $department = Department::factory()->create();

        ActivityLog::creating(static function (): void {
            throw new RuntimeException('Forced audit failure.');
        });

        try {
            $this->actingAs($actor)->post(route('admin.departments.members.store', $department), [
                'user_id' => $staff->id,
                'version' => 0,
            ]);
        } catch (RuntimeException $exception) {
            $this->assertSame('Forced audit failure.', $exception->getMessage());
        } finally {
            ActivityLog::flushEventListeners();
        }

        $department->refresh();
        $this->assertFalse($department->users()->whereKey($staff->id)->exists());
        $this->assertSame(0, $department->lock_version);
        $this->assertDatabaseMissing('activity_logs', ['action' => 'department.member_added']);
    }

    public function test_leader_change_rolls_back_when_audit_insert_fails(): void
    {
        $actor = $this->superAdmin();
        $leader = User::factory()->manager()->create();
        $department = Department::factory()->create();

        ActivityLog::creating(static function (): void {
            throw new RuntimeException('Forced audit failure.');
        });

        try {
            $this->actingAs($actor)->patch(route('admin.departments.leader.update', $department), [
                'leader_id' => $leader->id,
                'version' => 0,
            ]);
        } catch (RuntimeException $exception) {
            $this->assertSame('Forced audit failure.', $exception->getMessage());
        } finally {
            ActivityLog::flushEventListeners();
        }

        $department->refresh();
        $this->assertNull($department->leader_id);
        $this->assertSame(0, $department->lock_version);
        $this->assertFalse($department->users()->whereKey($leader->id)->exists());
        $this->assertDatabaseMissing('activity_logs', ['action' => 'department.leader_changed']);
    }

    public function test_member_removal_rolls_back_when_audit_insert_fails(): void
    {
        $actor = $this->superAdmin();
        $staff = User::factory()->staff()->create();
        $department = Department::factory()->create();
        $department->users()->attach($staff);

        ActivityLog::creating(static function (): void {
            throw new RuntimeException('Forced audit failure.');
        });

        try {
            $this->actingAs($actor)->delete(route('admin.departments.members.destroy', [$department, $staff]), [
                'version' => 0,
            ]);
        } catch (RuntimeException $exception) {
            $this->assertSame('Forced audit failure.', $exception->getMessage());
        } finally {
            ActivityLog::flushEventListeners();
        }

        $department->refresh();
        $this->assertSame(0, $department->lock_version);
        $this->assertTrue($department->users()->whereKey($staff->id)->exists());
        $this->assertDatabaseMissing('activity_logs', ['action' => 'department.member_removed']);
    }

    private function superAdmin(): User
    {
        return User::factory()->withRole(UserRole::SuperAdmin)->create();
    }
}
