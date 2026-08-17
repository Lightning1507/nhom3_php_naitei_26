<?php

namespace Tests\Feature\Api\V1\Auth;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CitizenRegistrationUniquenessTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_rejects_duplicate_normalized_email(): void
    {
        User::factory()->withRole(UserRole::Citizen)->create([
            'email' => 'citizen@example.test',
            'citizen_id' => '012345678901',
        ]);

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Nguyen Van B',
            'email' => ' CITIZEN@EXAMPLE.TEST ',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'citizen_id' => '012345678902',
            'date_of_birth' => '1996-02-20',
            'phone' => '0901234568',
            'address' => 'Da Nang',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonValidationErrors(['email']);

        $this->assertSame(1, User::query()->where('email', 'citizen@example.test')->count());
    }

    public function test_registration_rejects_duplicate_normalized_citizen_id(): void
    {
        User::factory()->withRole(UserRole::Citizen)->create([
            'email' => 'citizen-a@example.test',
            'citizen_id' => '012345678901',
        ]);

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Nguyen Van B',
            'email' => 'citizen-b@example.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'citizen_id' => ' 012345678901 ',
            'date_of_birth' => '1996-02-20',
            'phone' => '0901234568',
            'address' => 'Da Nang',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonValidationErrors(['citizen_id']);

        $this->assertSame(1, User::query()->where('citizen_id', '012345678901')->count());
    }
}
