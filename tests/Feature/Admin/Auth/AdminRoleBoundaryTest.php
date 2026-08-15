<?php

namespace Tests\Feature\Admin\Auth;

use App\Enums\UserRole;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminRoleBoundaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_admin_login(): void
    {
        $this->get('/admin')
            ->assertRedirect('/admin/login');
    }

    public function test_internal_roles_can_access_admin_dashboard(): void
    {
        foreach ([UserRole::Staff, UserRole::Manager, UserRole::SuperAdmin] as $role) {
            $user = User::factory()->withRole($role)->create();

            $this->actingAs($user)
                ->get('/admin')
                ->assertOk()
                ->assertViewIs('admin.dashboard');
        }
    }

    public function test_citizen_cannot_login_to_admin_area(): void
    {
        User::factory()->withRole(UserRole::Citizen)->create([
            'email' => 'citizen@example.test',
            'password' => 'password123',
            'citizen_id' => '012345678901',
        ]);

        $this->from('/admin/login')->post('/admin/login', [
            'email' => 'citizen@example.test',
            'password' => 'password123',
        ])->assertRedirect('/admin/login')
            ->assertSessionHasErrors(['email' => 'Thông tin đăng nhập không chính xác.']);

        $this->assertGuest();
    }

    public function test_authenticated_citizen_is_denied_admin_dashboard_access(): void
    {
        $user = User::factory()->withRole(UserRole::Citizen)->create([
            'citizen_id' => '012345678901',
        ]);

        $this->actingAs($user)
            ->get('/admin')
            ->assertForbidden()
            ->assertSee('Bạn không có quyền truy cập khu vực nội bộ.');

        $this->assertDatabaseHas(ActivityLog::class, [
            'actor_id' => $user->id,
            'action' => 'access.denied',
        ]);
    }

    public function test_inactive_internal_user_is_denied_admin_dashboard_access(): void
    {
        $user = User::factory()->withRole(UserRole::Staff)->create([
            'is_active' => false,
        ]);

        $this->actingAs($user)
            ->get('/admin')
            ->assertForbidden()
            ->assertSee('Tài khoản này không thể truy cập khu vực nội bộ.');
    }
}
