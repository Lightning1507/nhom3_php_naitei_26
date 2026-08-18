<?php

namespace Tests\Feature\Admin\Departments;

use App\Actions\Department\CreateDepartment;
use App\Enums\UserRole;
use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DepartmentManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_create_read_and_update_department_identity(): void
    {
        $actor = $this->superAdmin();

        $response = $this->actingAs($actor)->post(route('admin.departments.store'), [
            'name' => '  Phòng   Hành chính  ',
            'code' => '  hc_ns-01 ',
            'address' => '  Tầng 2, Tòa nhà A  ',
        ]);

        $department = Department::query()->where('code', 'HC_NS-01')->firstOrFail();

        $response->assertRedirect(route('admin.departments.show', $department));
        $this->assertSame('Phòng Hành chính', $department->name);
        $this->assertSame('Tầng 2, Tòa nhà A', $department->address);
        $this->assertSame(0, $department->lock_version);

        $this->actingAs($actor)->get(route('admin.departments.index'))
            ->assertOk()
            ->assertSee('HC_NS-01');
        $this->actingAs($actor)->get(route('admin.departments.show', $department))
            ->assertOk()
            ->assertSee('Phòng Hành chính');
        $this->actingAs($actor)->get(route('admin.departments.edit', $department))->assertOk();

        $this->actingAs($actor)->patch(route('admin.departments.update', $department), [
            'name' => '  Phòng   Hành chính mới ',
            'code' => 'hc_ns-02',
            'address' => '   ',
            'version' => 0,
        ])->assertRedirect(route('admin.departments.show', $department));

        $department->refresh();
        $this->assertSame('Phòng Hành chính mới', $department->name);
        $this->assertSame('HC_NS-02', $department->code);
        $this->assertNull($department->address);
        $this->assertSame(1, $department->lock_version);
    }

    public function test_archived_department_code_is_still_reserved_case_insensitively(): void
    {
        Department::factory()->archived()->create(['code' => 'ARCHIVE-01']);

        $this->actingAs($this->superAdmin())
            ->from(route('admin.departments.create'))
            ->post(route('admin.departments.store'), [
                'name' => 'Phòng mới',
                'code' => ' archive-01 ',
                'address' => null,
            ])
            ->assertRedirect(route('admin.departments.create'))
            ->assertSessionHasErrors('code');

        $this->assertSame(1, Department::withTrashed()->where('code', 'ARCHIVE-01')->count());
    }

    public function test_invalid_fields_are_rejected_without_partial_write(): void
    {
        $this->actingAs($this->superAdmin())
            ->post(route('admin.departments.store'), [
                'name' => '   ',
                'code' => 'invalid code!',
                'address' => str_repeat('a', 1001),
            ])
            ->assertSessionHasErrors(['name', 'code', 'address']);

        $this->assertDatabaseCount('departments', 0);
    }

    public function test_database_duplicate_race_is_mapped_to_code_validation_error(): void
    {
        $actor = $this->superAdmin();
        Department::factory()->create(['code' => 'RACE-01']);

        try {
            app(CreateDepartment::class)->handle([
                'name' => 'Phòng trùng',
                'code' => 'RACE-01',
                'address' => null,
            ], $actor, Request::create('/admin/departments', 'POST'));
            $this->fail('Expected duplicate code validation exception.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('code', $exception->errors());
        }

        $this->assertSame(1, Department::withTrashed()->where('code', 'RACE-01')->count());
    }

    public function test_stale_version_returns_conflict_without_overwriting_current_data(): void
    {
        $actor = $this->superAdmin();
        $department = Department::factory()->create([
            'name' => 'Tên hiện tại',
            'lock_version' => 2,
        ]);

        $this->actingAs($actor)->patch(route('admin.departments.update', $department), [
            'name' => 'Tên từ form cũ',
            'code' => $department->code,
            'address' => $department->address,
            'version' => 1,
        ])->assertStatus(409)
            ->assertViewIs('errors.department-version-conflict');

        $department->refresh();
        $this->assertSame('Tên hiện tại', $department->name);
        $this->assertSame(2, $department->lock_version);
    }

    private function superAdmin(): User
    {
        return User::factory()->withRole(UserRole::SuperAdmin)->create();
    }
}
