<?php

namespace App\Http\Requests\Api\V1;

use App\Support\ServiceSchema;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreApplicationDocumentRequest extends FormRequest
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
                ? ['required', 'string', Rule::in($codes)]
                : ['nullable', 'string'],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $code = $this->input('requirement_code');

                if ($code === null || ! $this->hasFile('document')) {
                    return;
                }

                $service = $this->route('application')?->serviceType;
                $requirements = ServiceSchema::normalizeDocumentRequirements($service?->document_requirements);
                $requirement = collect($requirements)->firstWhere('code', $code);

                if ($requirement === null) {
                    return;
                }

                $allowed = match ($requirement['type']) {
                    'pdf' => ['application/pdf'],
                    'image' => ['image/jpeg', 'image/png'],
                    default => ['application/pdf', 'image/jpeg', 'image/png'],
                };

                if (! in_array($this->file('document')->getMimeType(), $allowed, true)) {
                    $validator->errors()->add(
                        'document',
                        'File tải lên không đúng loại yêu cầu của giấy tờ này.'
                    );
                }
            },
        ];
    }
}
