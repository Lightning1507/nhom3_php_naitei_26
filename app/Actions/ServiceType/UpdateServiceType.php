<?php

namespace App\Actions\ServiceType;

use App\Models\ServiceType;
use App\Models\User;
use Illuminate\Http\Request;

class UpdateServiceType
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(ServiceType $serviceType, array $data, User $actor, ?Request $request = null): ServiceType
    {
        $serviceType->fill([
            'category_id' => $data['category_id'],
            'responsible_department_id' => $data['responsible_department_id'] ?? null,
            'name' => $data['name'],
            'code' => $data['code'],
            'description' => $data['description'] ?? null,
            'requirements' => $data['requirements'] ?? null,
            'form_schema' => $data['form_schema'] ?? [],
            'document_requirements' => $data['document_requirements'] ?? [],
            'processing_time_days' => $data['processing_time_days'],
            'fee' => $data['fee'],
            'is_active' => $data['is_active'] ?? false,
        ]);
        $serviceType->save();

        return $serviceType;
    }
}
