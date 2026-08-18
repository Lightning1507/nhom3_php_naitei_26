<?php

namespace App\Http\Requests\Admin\ServiceTypes;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateServiceTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'category_id' => ['required', 'exists:service_categories,id'],
            'responsible_department_id' => ['nullable', 'exists:departments,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('service_types', 'code')->ignore($this->route('service_type')),
            ],
            'description' => ['nullable', 'string'],
            'requirements' => ['nullable', 'string'],
            'processing_time_days' => ['required', 'integer', 'min:1'],
            'fee' => ['required', 'numeric', 'min:0'],
            'is_active' => ['boolean'],

            'document_requirements' => ['nullable', 'array'],
            'document_requirements.*.name' => ['required', 'string', 'max:255'],
            'document_requirements.*.is_required' => ['boolean'],

            'form_schema' => ['nullable', 'array'],
            'form_schema.*.name' => ['required', 'string', 'max:255'],
            'form_schema.*.type' => ['required', 'string', 'in:text,number,date,file'],
            'form_schema.*.is_required' => ['boolean'],
        ];
    }
}
