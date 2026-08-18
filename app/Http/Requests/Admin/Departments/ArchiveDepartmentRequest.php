<?php

namespace App\Http\Requests\Admin\Departments;

use App\Models\Department;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class ArchiveDepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $department = $this->route('department');
        $actor = $this->user();

        if (! $department instanceof Department || ! $actor) {
            return false;
        }

        Gate::forUser($actor)->authorize('archive', $department);

        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'confirmation' => ['required', Rule::in(['archive'])],
            'version' => ['required', 'integer', 'min:0'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'confirmation.required' => 'Vui lòng xác nhận lưu trữ phòng ban.',
            'confirmation.in' => 'Xác nhận lưu trữ phòng ban không hợp lệ.',
            'version.required' => 'Phiên bản dữ liệu không hợp lệ. Vui lòng tải lại trang.',
            'version.integer' => 'Phiên bản dữ liệu không hợp lệ. Vui lòng tải lại trang.',
            'version.min' => 'Phiên bản dữ liệu không hợp lệ. Vui lòng tải lại trang.',
        ];
    }
}
