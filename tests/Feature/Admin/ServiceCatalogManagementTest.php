<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\Department;
use App\Models\ServiceCategory;
use App\Models\ServiceType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceCatalogManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => UserRole::Manager,
        ]);
    }

    public function test_admin_can_list_service_categories(): void
    {
        $category = ServiceCategory::factory()->create();

        $response = $this->actingAs($this->admin)->get(route('admin.service-categories.index'));

        $response->assertOk()
            ->assertSee($category->name);
    }

    public function test_admin_can_create_service_category(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.service-categories.store'), [
            'name' => 'New Category',
            'code' => 'NC01',
            'description' => 'Test description',
        ]);

        $response->assertRedirect(route('admin.service-categories.index'));
        $this->assertDatabaseHas('service_categories', [
            'name' => 'New Category',
            'code' => 'NC01',
        ]);
    }

    public function test_admin_can_update_service_category(): void
    {
        $category = ServiceCategory::factory()->create();

        $response = $this->actingAs($this->admin)->put(route('admin.service-categories.update', $category), [
            'name' => 'Updated Category',
            'code' => $category->code,
            'description' => 'Updated description',
        ]);

        $response->assertRedirect(route('admin.service-categories.index'));
        $this->assertDatabaseHas('service_categories', [
            'id' => $category->id,
            'name' => 'Updated Category',
        ]);
    }

    public function test_admin_can_list_service_types(): void
    {
        $type = ServiceType::factory()->create();

        $response = $this->actingAs($this->admin)->get(route('admin.service-types.index'));

        $response->assertOk()
            ->assertSee($type->name);
    }

    public function test_admin_can_create_service_type(): void
    {
        $category = ServiceCategory::factory()->create();
        $department = Department::factory()->create();

        $response = $this->actingAs($this->admin)->post(route('admin.service-types.store'), [
            'category_id' => $category->id,
            'responsible_department_id' => $department->id,
            'name' => 'New Service Type',
            'code' => 'NST01',
            'description' => 'A new service',
            'processing_time_days' => 5,
            'fee' => 100,
            'is_active' => true,
        ]);

        $response->assertRedirect(route('admin.service-types.index'));
        $this->assertDatabaseHas('service_types', [
            'name' => 'New Service Type',
            'code' => 'NST01',
            'processing_time_days' => 5,
        ]);
    }

    public function test_admin_can_update_service_type(): void
    {
        $type = ServiceType::factory()->create();

        $response = $this->actingAs($this->admin)->put(route('admin.service-types.update', $type), [
            'category_id' => $type->category_id,
            'responsible_department_id' => $type->responsible_department_id,
            'name' => 'Updated Service Type',
            'code' => $type->code,
            'description' => $type->description,
            'processing_time_days' => 10,
            'fee' => $type->fee,
            'is_active' => true,
        ]);

        $response->assertRedirect(route('admin.service-types.index'));
        $this->assertDatabaseHas('service_types', [
            'id' => $type->id,
            'name' => 'Updated Service Type',
            'processing_time_days' => 10,
        ]);
    }
}
