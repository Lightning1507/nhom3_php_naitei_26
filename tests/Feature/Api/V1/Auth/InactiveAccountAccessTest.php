<?php

namespace Tests\Feature\Api\V1\Auth;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class InactiveAccountAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_inactive_citizen_cannot_access_protected_citizen_routes(): void
    {
        $citizen = User::factory()->withRole(UserRole::Citizen)->create([
            'is_active' => false,
        ]);

        $this->actingAs($citizen)
            ->postJson('/api/v1/auth/logout')
            ->assertForbidden()
            ->assertJsonPath('message', 'Tài khoản này không thể truy cập tài nguyên được bảo vệ.');
    }

    public function test_inactive_internal_user_cannot_access_admin_routes(): void
    {
        $staff = User::factory()->withRole(UserRole::Staff)->create([
            'is_active' => false,
        ]);

        $this->actingAs($staff)
            ->get('/admin')
            ->assertForbidden()
            ->assertSee('Tài khoản này không thể truy cập khu vực nội bộ.');
    }

    public function test_inactive_citizen_is_denied_self_access_policy_permissions(): void
    {
        $citizen = User::factory()->withRole(UserRole::Citizen)->create([
            'is_active' => false,
        ]);

        $this->assertFalse(Gate::forUser($citizen)->allows('view', $citizen));
        $this->assertFalse(Gate::forUser($citizen)->allows('update', $citizen));
    }
}
