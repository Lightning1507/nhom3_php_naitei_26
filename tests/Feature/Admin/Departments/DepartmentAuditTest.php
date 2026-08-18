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

    private function superAdmin(): User
    {
        return User::factory()->withRole(UserRole::SuperAdmin)->create();
    }
}
