<?php

namespace Tests\Feature\Api\V1\Profile;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CitizenProfileForbiddenFieldsTest extends TestCase
{
    use RefreshDatabase;

    public function test_citizen_cannot_update_identity_or_security_fields(): void
    {
        $citizen = User::factory()->withRole(UserRole::Citizen)->create([
            'email' => 'citizen@example.test',
            'password' => 'password123',
            'citizen_id' => '012345678901',
            'is_active' => true,
        ]);

        $this->actingAs($citizen)
            ->patchJson('/api/v1/me', [
                'name' => 'Allowed Name',
                'email' => 'changed@example.test',
                'citizen_id' => '999999999999',
                'role' => UserRole::SuperAdmin->value,
                'password' => 'new-password',
                'is_active' => false,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'citizen_id', 'role', 'password', 'is_active']);

        $citizen->refresh();

        $this->assertSame('citizen@example.test', $citizen->email);
        $this->assertSame('012345678901', $citizen->citizen_id);
        $this->assertTrue($citizen->isCitizen());
        $this->assertTrue($citizen->is_active);
    }

    public function test_citizen_cannot_update_profile_when_account_becomes_inactive(): void
    {
        $citizen = User::factory()->withRole(UserRole::Citizen)->create([
            'is_active' => false,
        ]);

        $this->actingAs($citizen)
            ->patchJson('/api/v1/me', [
                'name' => 'Nguyen Van B',
            ])
            ->assertForbidden()
            ->assertJsonPath('message', 'Tài khoản này không thể truy cập tài nguyên được bảo vệ.');
    }
}
