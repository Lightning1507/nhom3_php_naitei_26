<?php

namespace App\Http\Resources\Api\V1;

use App\Enums\ApplicationStatus;
use App\Models\ApplicationDocument;
use App\Support\ServiceSchema;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApplicationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if ($this->relationLoaded('documents')) {
            $this->documents->each(
                fn (ApplicationDocument $document) => $document->setRelation('application', $this->resource)
            );
        }

        return [
            'id' => $this->id,
            'application_code' => $this->application_code,
            'service_type' => [
                'id' => $this->service_type_id,
                'name' => $this->whenLoaded('serviceType', fn () => $this->serviceType->name),
                'code' => $this->whenLoaded('serviceType', fn () => $this->serviceType->code),
                'document_requirements' => $this->whenLoaded('serviceType', fn () => ServiceSchema::normalizeDocumentRequirements($this->serviceType->document_requirements)),
            ],
            'status' => $this->status->value,
            'form_data' => $this->form_data,
            'submitted_at' => $this->submitted_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'missing_required_documents' => $this->missingRequiredDocuments(),
            'documents' => ApplicationDocumentResource::collection($this->whenLoaded('documents')),
        ];
    }

    /**
     * @return array<int, array{code: string, label: string}>
     */
    private function missingRequiredDocuments(): array
    {
        if (! in_array($this->status, [ApplicationStatus::Received, ApplicationStatus::SupplementRequired], true)) {
            return [];
        }

        if (! $this->relationLoaded('serviceType') || ! $this->relationLoaded('documents')) {
            return [];
        }

        $requirements = ServiceSchema::normalizeDocumentRequirements($this->serviceType->document_requirements);

        $uploadedCodes = $this->documents
            ->pluck('requirement_code')
            ->filter()
            ->flip();

        $missing = [];

        foreach ($requirements as $requirement) {
            if ($requirement['required'] && ! $uploadedCodes->has($requirement['code'])) {
                $missing[] = ['code' => $requirement['code'], 'label' => $requirement['label']];
            }
        }

        return $missing;
    }
}
