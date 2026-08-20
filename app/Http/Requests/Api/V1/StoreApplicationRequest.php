<?php

namespace App\Http\Requests\Api\V1;

use App\Models\ServiceType;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

class StoreApplicationRequest extends FormRequest
{
    private ?ServiceType $resolvedServiceType = null;

    private bool $serviceTypeResolved = false;

    /**
     * @return array<string, list<Closure|string>>
     */
    public function rules(): array
    {
        $rules = [
            'service_type_id' => ['required', 'integer', $this->activeServiceTypeRule()],
            'form_data' => ['sometimes', 'array'],
        ];

        $serviceType = $this->serviceTypeOrNull();

        if ($serviceType === null) {
            return $rules;
        }

        foreach ($serviceType->form_schema ?? [] as $field) {
            $name = $field['name'] ?? null;

            if (! is_string($name) || $name === '' || ($field['type'] ?? '') === 'file') {
                continue;
            }

            $key = 'form_data.'.$name;

            $fieldRules = [];

            if (! empty($field['required']) || ! empty($field['is_required'])) {
                $fieldRules[] = 'required';
            }

            $fieldRules[] = $this->typeRule($field['type'] ?? 'string');

            $rules[$key] = $fieldRules;
        }

        return $rules;
    }

    public function serviceType(): ServiceType
    {
        $serviceType = $this->serviceTypeOrNull();

        abort_unless($serviceType !== null, 422, 'The selected service type is invalid.');

        return $serviceType;
    }

    private function serviceTypeOrNull(): ?ServiceType
    {
        if (! $this->serviceTypeResolved) {
            $id = $this->input('service_type_id');

            $this->resolvedServiceType = $id === null
                ? null
                : ServiceType::query()->find($id);

            $this->serviceTypeResolved = true;
        }

        return $this->resolvedServiceType;
    }

    private function activeServiceTypeRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            $serviceType = $this->serviceTypeOrNull();

            if ($serviceType === null || ! $serviceType->is_active || $serviceType->trashed()) {
                $fail('The selected service type is not available.');
            }
        };
    }

    private function typeRule(string $type): string
    {
        return match ($type) {
            'number' => 'numeric',
            'date' => 'date',
            'boolean' => 'boolean',
            'text', 'string' => 'string',
            default => 'string',
        };
    }
}
