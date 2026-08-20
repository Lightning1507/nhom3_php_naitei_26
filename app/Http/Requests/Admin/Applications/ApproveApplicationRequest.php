<?php

namespace App\Http\Requests\Admin\Applications;

use Illuminate\Foundation\Http\FormRequest;

class ApproveApplicationRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'result_note' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
