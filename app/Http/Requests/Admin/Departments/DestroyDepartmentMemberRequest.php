<?php

namespace App\Http\Requests\Admin\Departments;

use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class DestroyDepartmentMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        $department = $this->route('department');
        $member = $this->route('member');
        $actor = $this->user();

        if (! $department instanceof Department
            || ! $member instanceof User
            || ! $actor?->can('removeMember', $department)) {
            return false;
        }

        return $actor->isSuperAdmin() || $member->isStaff();
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'version' => ['required', 'integer', 'min:0'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'version.required' => 'Phiên bản dữ liệu không hợp lệ. Vui lòng tải lại trang.',
            'version.integer' => 'Phiên bản dữ liệu không hợp lệ. Vui lòng tải lại trang.',
            'version.min' => 'Phiên bản dữ liệu không hợp lệ. Vui lòng tải lại trang.',
        ];
    }
}
