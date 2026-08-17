<?php

namespace App\Actions\Auth;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RegisterCitizen
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data): User
    {
        return DB::transaction(fn (): User => User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => UserRole::Citizen,
            'citizen_id' => $data['citizen_id'],
            'date_of_birth' => $data['date_of_birth'],
            'phone' => $data['phone'],
            'address' => $data['address'],
            'is_active' => true,
        ]));
    }
}
