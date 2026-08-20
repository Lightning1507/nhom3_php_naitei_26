<?php

namespace App\Http\Requests\Admin\Applications;

use App\Support\ServiceSchema;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreResultDocumentRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $application = $this->route('application');
        $service = $application?->serviceType;

        $codes = array_column(
            ServiceSchema::normalizeDocumentRequirements($service?->document_requirements),
            'code'
        );

        return [
            'document' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'requirement_code' => count($codes) > 0
                ? ['nullable', 'string', Rule::in($codes)]
                : ['nullable', 'string'],
        ];
    }
}
