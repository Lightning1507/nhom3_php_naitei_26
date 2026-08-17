<?php

namespace Tests\Feature\Api\V1\Auth;

use App\Enums\UserRole;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CitizenLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_citizen_can_login_and_logout(): void
    {
        $user = User::factory()->withRole(UserRole::Citizen)->create([
            'email' => 'citizen@example.test',
            'password' => 'password123',
            'citizen_id' => '012345678901',
        ]);

        $loginResponse = $this->postJson(
            '/api/v1/auth/login',
            [
                'email' => ' CITIZEN@EXAMPLE.TEST ',
                'password' => 'password123',
            ],
            $this->spaHeaders(),
        );

        $loginResponse
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.role', UserRole::Citizen->value);

        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas(ActivityLog::class, [
            'actor_id' => $user->id,
            'action' => 'citizen.login_succeeded',
        ]);

        $logoutResponse = $this->postJson('/api/v1/auth/logout', [], $this->spaHeaders());

        $logoutResponse
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->postJson('/api/v1/auth/logout', [], $this->spaHeaders())
            ->assertUnauthorized()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Chưa đăng nhập.');

        $this->assertDatabaseHas(ActivityLog::class, [
            'actor_id' => $user->id,
            'action' => 'citizen.logout',
        ]);
    }

    public function test_login_failure_does_not_reveal_account_existence(): void
    {
        User::factory()->withRole(UserRole::Citizen)->create([
            'email' => 'citizen@example.test',
            'password' => 'password123',
            'citizen_id' => '012345678901',
        ]);

        $wrongPasswordResponse = $this->postJson('/api/v1/auth/login', [
            'email' => 'citizen@example.test',
            'password' => 'wrong-password',
        ]);

        $missingAccountResponse = $this->postJson('/api/v1/auth/login', [
            'email' => 'missing@example.test',
            'password' => 'wrong-password',
        ]);

        $wrongPasswordResponse
            ->assertUnauthorized()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Thông tin đăng nhập không chính xác.');

        $missingAccountResponse
            ->assertUnauthorized()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Thông tin đăng nhập không chính xác.');

        $this->assertDatabaseHas(ActivityLog::class, [
            'actor_id' => null,
            'action' => 'citizen.login_failed',
        ]);
    }

    public function test_non_citizen_cannot_use_citizen_login_flow(): void
    {
        User::factory()->withRole(UserRole::Staff)->create([
            'email' => 'staff@example.test',
            'password' => 'password123',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'staff@example.test',
            'password' => 'password123',
        ]);

        $response
            ->assertUnauthorized()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Thông tin đăng nhập không chính xác.');

        $this->assertGuest();
    }

    public function test_inactive_citizen_cannot_login(): void
    {
        User::factory()->withRole(UserRole::Citizen)->create([
            'email' => 'citizen@example.test',
            'password' => 'password123',
            'citizen_id' => '012345678901',
            'is_active' => false,
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'citizen@example.test',
            'password' => 'password123',
        ]);

        $response
            ->assertUnauthorized()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Thông tin đăng nhập không chính xác.');

        $this->assertGuest();
    }

    public function test_guest_cannot_logout(): void
    {
        $response = $this->postJson('/api/v1/auth/logout', [], $this->spaHeaders());

        $response
            ->assertUnauthorized()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Chưa đăng nhập.');
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
