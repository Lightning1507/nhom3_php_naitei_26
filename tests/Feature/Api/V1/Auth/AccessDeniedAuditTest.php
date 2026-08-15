<?php

namespace Tests\Feature\Api\V1\Auth;

use App\Enums\UserRole;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccessDeniedAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_wrong_role_citizen_route_denial_is_audited(): void
    {
        $staff = User::factory()->withRole(UserRole::Staff)->create();

        $this->actingAs($staff)
            ->postJson('/api/v1/auth/logout')
            ->assertForbidden();

        $log = ActivityLog::query()->where('action', 'access.denied')->firstOrFail();

        $this->assertTrue($log->actor->is($staff));
        $this->assertSame('wrong_role', $log->metadata['reason']);
        $this->assertSame('api/v1/auth/logout', $log->metadata['path']);
        $this->assertSame('POST', $log->metadata['method']);
        $this->assertSame('citizen', $log->metadata['required_role']);
        $this->assertSame(UserRole::Staff->value, $log->metadata['actual_role']);
    }

    public function test_inactive_citizen_route_denial_is_audited(): void
    {
        $citizen = User::factory()->withRole(UserRole::Citizen)->create([
            'is_active' => false,
        ]);

        $this->actingAs($citizen)
            ->postJson('/api/v1/auth/logout')
            ->assertForbidden();

        $log = ActivityLog::query()->where('action', 'access.denied')->firstOrFail();

        $this->assertTrue($log->actor->is($citizen));
        $this->assertSame('inactive_account', $log->metadata['reason']);
        $this->assertSame('api/v1/auth/logout', $log->metadata['path']);
    }

    public function test_internal_route_denial_is_audited(): void
    {
        $citizen = User::factory()->withRole(UserRole::Citizen)->create();

        $this->actingAs($citizen)
            ->get('/admin')
            ->assertForbidden();

        $log = ActivityLog::query()->where('action', 'access.denied')->firstOrFail();

        $this->assertTrue($log->actor->is($citizen));
        $this->assertSame('wrong_role', $log->metadata['reason']);
        $this->assertSame('admin', $log->metadata['path']);
        $this->assertSame('internal', $log->metadata['required_role']);
        $this->assertSame(UserRole::Citizen->value, $log->metadata['actual_role']);
    }
}
