<?php

namespace App\Http\Resources\Api\V1;

use App\Support\ServiceSchema;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApplicationDocumentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'application_id' => $this->application_id,
            'document_kind' => $this->document_kind->value,
            'original_name' => $this->original_name,
            'requirement_code' => $this->requirement_code,
            'requirement_label' => $this->requirementLabel(),
            'mime_type' => $this->mime_type,
            'file_size' => $this->file_size,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }

    private function requirementLabel(): ?string
    {
        if ($this->requirement_code === null) {
            return null;
        }

        $service = $this->application?->serviceType;

        if ($service === null) {
            return null;
        }

        $label = collect(ServiceSchema::normalizeDocumentRequirements($service->document_requirements))
            ->firstWhere('code', $this->requirement_code)['label'] ?? null;

        return $label !== null ? (string) $label : null;
    }
}
