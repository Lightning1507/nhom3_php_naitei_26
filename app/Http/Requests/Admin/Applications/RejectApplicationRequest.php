<?php

namespace App\Http\Requests\Admin\Applications;

use Illuminate\Foundation\Http\FormRequest;

class RejectApplicationRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'rejection_reason' => ['required', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'rejection_reason.required' => 'Vui lòng nhập lý do từ chối hồ sơ.',
        ];
    }
}
