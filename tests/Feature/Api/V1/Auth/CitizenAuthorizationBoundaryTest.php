<?php

namespace Tests\Feature\Api\V1\Auth;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class CitizenAuthorizationBoundaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_protected_citizen_routes(): void
    {
        $this->postJson('/api/v1/auth/logout', [], $this->spaHeaders())
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Chưa đăng nhập.');
    }

    public function test_active_citizen_can_access_protected_citizen_routes(): void
    {
        User::factory()->withRole(UserRole::Citizen)->create([
            'email' => 'citizen@example.test',
            'password' => 'password123',
            'citizen_id' => '012345678901',
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'citizen@example.test',
            'password' => 'password123',
        ], $this->spaHeaders())->assertOk();

        $this->postJson('/api/v1/auth/logout', [], $this->spaHeaders())
            ->assertOk()
            ->assertJsonPath('message', 'Đăng xuất thành công.');
    }

    public function test_internal_user_cannot_access_protected_citizen_routes(): void
    {
        $staff = User::factory()->withRole(UserRole::Staff)->create();

        $this->actingAs($staff)
            ->postJson('/api/v1/auth/logout')
            ->assertForbidden()
            ->assertJsonPath('message', 'Bạn không có quyền truy cập tài nguyên này.');
    }

    public function test_citizen_user_policy_allows_self_access_only(): void
    {
        $citizen = User::factory()->withRole(UserRole::Citizen)->create();
        $otherCitizen = User::factory()->withRole(UserRole::Citizen)->create();

        $this->assertTrue(Gate::forUser($citizen)->allows('view', $citizen));
        $this->assertTrue(Gate::forUser($citizen)->allows('update', $citizen));
        $this->assertFalse(Gate::forUser($citizen)->allows('view', $otherCitizen));
        $this->assertFalse(Gate::forUser($citizen)->allows('update', $otherCitizen));
    }

    public function test_internal_users_do_not_receive_citizen_self_access_policy_permissions(): void
    {
        $manager = User::factory()->withRole(UserRole::Manager)->create();

        $this->assertFalse(Gate::forUser($manager)->allows('view', $manager));
        $this->assertFalse(Gate::forUser($manager)->allows('update', $manager));
    }

    /**
     * @return array<string, string>
     */
    private function spaHeaders(): array
    {
        return [
            'Origin' => 'http://localhost',
        ];
    }
}
