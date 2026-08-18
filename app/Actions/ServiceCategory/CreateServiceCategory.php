<?php

namespace App\Actions\ServiceCategory;

use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Http\Request;

class CreateServiceCategory
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data, User $actor, ?Request $request = null): ServiceCategory
    {
        return ServiceCategory::create([
            'name' => $data['name'],
            'code' => $data['code'],
            'description' => $data['description'] ?? null,
        ]);
    }
}
