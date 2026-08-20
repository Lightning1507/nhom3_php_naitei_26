<?php

namespace App\Http\Requests\Admin\Applications;

use Illuminate\Foundation\Http\FormRequest;

class RequestSupplementRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'note' => ['required', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'note.required' => 'Vui lòng nhập lý do yêu cầu bổ sung tài liệu.',
        ];
    }
}
