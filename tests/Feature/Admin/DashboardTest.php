<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_renders(): void
    {
        $user = User::factory()->withRole(UserRole::SuperAdmin)->create();

        $response = $this->actingAs($user)->get('/admin');

        $response
            ->assertOk()
            ->assertViewIs('admin.dashboard')
            ->assertSee('Admin Dashboard');
    }
}
