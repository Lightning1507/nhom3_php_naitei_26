<?php

namespace Tests\Feature\Api\V1\Auth;

use App\Enums\UserRole;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CitizenRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_citizen_can_register_with_valid_identity_information(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Nguyen Van A',
            'email' => ' Citizen@Example.Test ',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'citizen_id' => ' 012345678901 ',
            'date_of_birth' => '1995-01-15',
            'phone' => '0901234567',
            'address' => 'Ha Noi',
            'role' => UserRole::SuperAdmin->value,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.email', 'citizen@example.test')
            ->assertJsonPath('data.role', UserRole::Citizen->value)
            ->assertJsonPath('data.citizen_id', '012345678901')
            ->assertJsonMissingPath('data.password');

        $user = User::query()->where('email', 'citizen@example.test')->firstOrFail();

        $this->assertTrue(Hash::check('password123', $user->password));
        $this->assertTrue($user->isCitizen());
        $this->assertDatabaseHas(ActivityLog::class, [
            'actor_id' => $user->id,
            'action' => 'citizen.registered',
        ]);
    }

    public function test_registration_returns_standard_validation_envelope(): void
    {
        $response = $this->postJson('/api/v1/auth/register', []);

        $response
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Dữ liệu không hợp lệ.')
            ->assertJsonPath('errors.name.0', 'Vui lòng nhập họ và tên.')
            ->assertJsonPath('errors.email.0', 'Vui lòng nhập email.')
            ->assertJsonPath('errors.password.0', 'Vui lòng nhập mật khẩu.')
            ->assertJsonPath('errors.citizen_id.0', 'Vui lòng nhập số CCCD.')
            ->assertJsonStructure([
                'success',
                'message',
                'errors' => [
                    'name',
                    'email',
                    'password',
                    'citizen_id',
                    'date_of_birth',
                    'phone',
                    'address',
                ],
            ]);
    }

    public function test_registration_rejects_invalid_citizen_id_format(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Nguyen Van A',
            'email' => 'citizen@example.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'citizen_id' => 'abc-123',
            'date_of_birth' => '1995-01-15',
            'phone' => '0901234567',
            'address' => 'Ha Noi',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('errors.citizen_id.0', 'Số CCCD phải gồm đúng 12 chữ số.')
            ->assertJsonValidationErrors(['citizen_id']);
    }
}
