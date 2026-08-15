<?php

namespace App\Http\Requests\Api\V1;

use App\Models\ServiceType;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

class StoreApplicationRequest extends FormRequest
{
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
            $key = 'form_data.'.$field['name'];

            $fieldRules = [];

            if (! empty($field['required'])) {
                $fieldRules[] = 'required';
            }

            $fieldRules[] = $this->typeRule($field['type'] ?? 'string');

            $rules[$key] = $fieldRules;
        }

        return $rules;
    }

    private function serviceTypeOrNull(): ?ServiceType
    {
        $id = $this->input('service_type_id');

        if ($id === null) {
            return null;
        }

        return ServiceType::query()->find($id);
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
            default => 'string',
        };
    }
}
