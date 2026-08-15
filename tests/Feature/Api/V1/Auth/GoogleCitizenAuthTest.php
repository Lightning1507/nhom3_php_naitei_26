<?php

namespace Tests\Feature\Api\V1\Auth;

use App\Enums\UserRole;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Tests\TestCase;

class GoogleCitizenAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_google_redirect_sends_user_to_google_provider(): void
    {
        $provider = Mockery::mock();
        $provider->shouldReceive('scopes')
            ->once()
            ->with(['openid', 'profile', 'email'])
            ->andReturnSelf();
        $provider->shouldReceive('redirect')
            ->once()
            ->andReturn(redirect()->away('https://accounts.google.com/o/oauth2/auth'));

        Socialite::shouldReceive('driver')->once()->with('google')->andReturn($provider);

        $this->get('/api/v1/auth/google/redirect')
            ->assertRedirect('https://accounts.google.com/o/oauth2/auth');
    }

    public function test_existing_active_citizen_can_login_with_google(): void
    {
        $citizen = User::factory()->withRole(UserRole::Citizen)->create([
            'email' => 'citizen@example.test',
        ]);

        $this->fakeGoogleUser(email: ' Citizen@Example.Test ', name: 'Citizen User');

        $this->get('/api/v1/auth/google/callback')
            ->assertRedirect('/profile');

        $this->assertAuthenticatedAs($citizen);
        $this->assertDatabaseHas(ActivityLog::class, [
            'actor_id' => $citizen->id,
            'subject_id' => $citizen->id,
            'action' => 'citizen.google_login_succeeded',
        ]);
    }

    public function test_internal_user_email_cannot_login_with_google_citizen_flow(): void
    {
        $staff = User::factory()->withRole(UserRole::Staff)->create([
            'email' => 'staff@example.test',
        ]);

        $this->fakeGoogleUser(email: 'staff@example.test', name: 'Staff User');

        $this->get('/api/v1/auth/google/callback')
            ->assertRedirect('/login?auth_error=google_login_denied');

        $this->assertGuest();
        $this->assertDatabaseHas(ActivityLog::class, [
            'actor_id' => null,
            'subject_id' => $staff->id,
            'action' => 'citizen.google_login_failed',
        ]);
    }

    public function test_unknown_google_email_redirects_to_completion_flow(): void
    {
        $this->fakeGoogleUser(email: 'new-citizen@example.test', name: 'New Citizen');

        $this->get('/api/v1/auth/google/callback')
            ->assertRedirect('/auth/google/complete');

        $this->getJson('/api/v1/auth/google/pending')
            ->assertOk()
            ->assertJsonPath('data.email', 'new-citizen@example.test')
            ->assertJsonPath('data.name', 'New Citizen');
    }

    public function test_google_completion_creates_and_logs_in_citizen(): void
    {
        $this->withSession([
            'auth.google_citizen_registration' => [
                'email' => 'new-citizen@example.test',
                'name' => 'New Citizen',
                'google_id' => 'google-123',
                'avatar' => null,
            ],
        ]);

        $response = $this->postJson('/api/v1/auth/google/complete', [
            'name' => 'New Citizen',
            'citizen_id' => '012345678901',
            'date_of_birth' => '1995-05-20',
            'phone' => '0901234567',
            'address' => 'Ha Noi',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('message', 'Hoàn tất đăng ký Google thành công.')
            ->assertJsonPath('data.email', 'new-citizen@example.test')
            ->assertJsonPath('data.role', UserRole::Citizen->value)
            ->assertJsonPath('data.citizen_id', '012345678901');

        $user = User::query()->where('email', 'new-citizen@example.test')->firstOrFail();

        $this->assertAuthenticatedAs($user);
        $this->assertTrue($user->isCitizen());
        $this->assertDatabaseHas(ActivityLog::class, [
            'actor_id' => $user->id,
            'subject_id' => $user->id,
            'action' => 'citizen.google_registration_completed',
        ]);
    }

    public function test_google_completion_rejects_identity_and_security_fields(): void
    {
        $this->withSession([
            'auth.google_citizen_registration' => [
                'email' => 'new-citizen@example.test',
                'name' => 'New Citizen',
                'google_id' => 'google-123',
                'avatar' => null,
            ],
        ]);

        $this->postJson('/api/v1/auth/google/complete', [
            'name' => 'New Citizen',
            'citizen_id' => '012345678901',
            'date_of_birth' => '1995-05-20',
            'phone' => '0901234567',
            'address' => 'Ha Noi',
            'email' => 'changed@example.test',
            'role' => UserRole::SuperAdmin->value,
            'password' => 'password123',
            'is_active' => false,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'role', 'password', 'is_active']);

        $this->assertDatabaseMissing(User::class, [
            'email' => 'new-citizen@example.test',
        ]);
    }

    private function fakeGoogleUser(string $email, string $name): void
    {
        $googleUser = Mockery::mock(SocialiteUser::class);
        $googleUser->shouldReceive('getId')->andReturn('google-123');
        $googleUser->shouldReceive('getName')->andReturn($name);
        $googleUser->shouldReceive('getEmail')->andReturn($email);
        $googleUser->shouldReceive('getAvatar')->andReturn(null);

        $provider = Mockery::mock();
        $provider->shouldReceive('user')->once()->andReturn($googleUser);

        Socialite::shouldReceive('driver')->once()->with('google')->andReturn($provider);
    }
}
