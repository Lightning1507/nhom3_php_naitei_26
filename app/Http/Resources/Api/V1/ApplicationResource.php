<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApplicationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'application_code' => $this->application_code,
            'service_type' => [
                'id' => $this->service_type_id,
                'name' => $this->whenLoaded('serviceType', fn () => $this->serviceType->name),
                'code' => $this->whenLoaded('serviceType', fn () => $this->serviceType->code),
            ],
            'status' => $this->status->value,
            'form_data' => $this->form_data,
            'submitted_at' => $this->submitted_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
