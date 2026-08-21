<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_active_super_admin_can_open_user_management_endpoints(): void
    {
        $target = User::factory()->create();

        $this->get(route('admin.users.index'))->assertRedirect(route('admin.login'));

        foreach ([
            User::factory()->create(),
            User::factory()->staff()->create(),
            User::factory()->manager()->create(),
            User::factory()->withRole(UserRole::SuperAdmin)->inactive()->create(),
        ] as $actor) {
            $this->actingAs($actor)->get(route('admin.users.index'))->assertForbidden();
            $this->actingAs($actor)->get(route('admin.users.show', $target))->assertForbidden();
        }

        $admin = $this->superAdmin();
        $this->actingAs($admin)->get(route('admin.users.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.users.show', $target))->assertOk();
        $this->assertTrue(Gate::forUser($admin)->allows('viewAny', User::class));
        $this->assertTrue(Gate::forUser($admin)->allows('changeStatus', $target));
        $this->assertFalse(Gate::forUser($admin)->allows('update', $target));

        $staff = User::factory()->staff()->create();
        $this->actingAs($staff)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertDontSee('href="'.route('admin.users.index').'"', false);
        $this->actingAs($admin)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('href="'.route('admin.users.index').'"', false);
    }

    public function test_search_is_literal_case_insensitive_and_matches_name_email_or_citizen_id(): void
    {
        $admin = $this->superAdmin();
        $byName = User::factory()->create(['name' => 'Nguyễn % An', 'email' => 'name@example.test']);
        $byEmail = User::factory()->create(['name' => 'Email Match', 'email' => 'literal_under_score@example.test']);
        $byCitizenId = User::factory()->create(['name' => 'Citizen Match', 'citizen_id' => 'CCCD_100%']);
        $wildcardLookalike = User::factory()->create(['name' => 'Nguyễn X An']);

        $this->actingAs($admin)
            ->get(route('admin.users.index', ['search' => '%']))
            ->assertOk()
            ->assertViewHas('users', fn ($users): bool => $users->pluck('id')->contains($byName->id)
                && $users->pluck('id')->contains($byCitizenId->id)
                && ! $users->pluck('id')->contains($wildcardLookalike->id));

        $this->actingAs($admin)
            ->get(route('admin.users.index', ['search' => 'LITERAL_UNDER_SCORE']))
            ->assertOk()
            ->assertViewHas('users', fn ($users): bool => $users->modelKeys() === [$byEmail->id]);

        $this->actingAs($admin)
            ->get(route('admin.users.index', ['search' => 'CCCD_100%']))
            ->assertOk()
            ->assertViewHas('users', fn ($users): bool => $users->modelKeys() === [$byCitizenId->id]);
    }

    public function test_role_and_status_filters_intersect(): void
    {
        $admin = $this->superAdmin();
        $match = User::factory()->staff()->inactive()->create(['name' => 'Match Staff']);
        User::factory()->staff()->active()->create(['name' => 'Active Staff']);
        User::factory()->manager()->inactive()->create(['name' => 'Inactive Manager']);

        $this->actingAs($admin)
            ->get(route('admin.users.index', [
                'search' => 'Staff',
                'role' => UserRole::Staff->value,
                'status' => 'inactive',
            ]))
            ->assertOk()
            ->assertViewHas('users', fn ($users): bool => $users->modelKeys() === [$match->id]);
    }

    public function test_pagination_is_stable_twenty_per_page_and_preserves_filters(): void
    {
        $admin = $this->superAdmin();
        $createdAt = '2026-08-20 08:00:00';

        foreach (range(1, 21) as $number) {
            User::factory()->staff()->create([
                'name' => sprintf('Page User %02d', $number),
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);
        }

        $response = $this->actingAs($admin)->get(route('admin.users.index', [
            'search' => 'Page User',
            'role' => UserRole::Staff->value,
            'status' => 'active',
            'page' => 1,
        ]));

        $response->assertOk()->assertViewHas('users', function ($users): bool {
            parse_str((string) parse_url($users->url(2), PHP_URL_QUERY), $query);

            return $users->count() === 20
                && $users->total() === 21
                && $users->first()->name === 'Page User 21'
                && $users->last()->name === 'Page User 02'
                && $query['search'] === 'Page User'
                && $query['role'] === UserRole::Staff->value
                && $query['status'] === 'active';
        });

        $this->actingAs($admin)->get(route('admin.users.index', [
            'search' => 'Page User',
            'role' => UserRole::Staff->value,
            'status' => 'active',
            'page' => 2,
        ]))->assertViewHas('users', fn ($users): bool => $users->count() === 1
            && $users->first()->name === 'Page User 01');
    }

    public function test_safe_detail_labels_archived_organization_and_omits_authentication_secrets(): void
    {
        $admin = $this->superAdmin();
        $target = User::factory()->manager()->create([
            'remember_token' => 'remember-token-sentinel',
        ]);
        User::query()->whereKey($target)->update(['password' => 'password-hash-sentinel']);
        DB::table('sessions')->insert([
            'id' => 'session-id-sentinel',
            'user_id' => $target->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'secret-session-agent',
            'payload' => 'secret-session-payload',
            'last_activity' => now()->timestamp,
        ]);
        DB::table('password_reset_tokens')->insert([
            'email' => $target->email,
            'token' => 'reset-token-sentinel',
            'created_at' => now(),
        ]);
        $department = Department::factory()->ledBy($target)->archived()->create([
            'name' => 'Phòng ban lịch sử',
            'code' => 'ARCHIVED-ORG',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.users.show', $target));

        $response->assertOk()
            ->assertSee($target->name)
            ->assertSee($department->name)
            ->assertSee('Đã lưu trữ')
            ->assertDontSee('password-hash-sentinel')
            ->assertDontSee('remember-token-sentinel')
            ->assertDontSee('session-id-sentinel')
            ->assertDontSee('reset-token-sentinel')
            ->assertViewHas('user', fn (User $safeUser): bool => ! array_key_exists('password', $safeUser->getAttributes())
                && ! array_key_exists('remember_token', $safeUser->getAttributes()));
    }

    public function test_no_result_state_offers_filter_reset(): void
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin)
            ->get(route('admin.users.index', ['search' => 'NO-SUCH-USER']))
            ->assertOk()
            ->assertSee('Không tìm thấy người dùng phù hợp')
            ->assertSee('Xóa bộ lọc');
    }

    private function superAdmin(): User
    {
        return User::factory()->withRole(UserRole::SuperAdmin)->create();
    }
}
