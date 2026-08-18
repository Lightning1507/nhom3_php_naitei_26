<?php

namespace App\Actions\ServiceCategory;

use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Http\Request;

class UpdateServiceCategory
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(ServiceCategory $serviceCategory, array $data, User $actor, ?Request $request = null): ServiceCategory
    {
        $serviceCategory->update([
            'name' => $data['name'],
            'code' => $data['code'],
            'description' => $data['description'] ?? null,
        ]);

        return $serviceCategory;
    }
}
