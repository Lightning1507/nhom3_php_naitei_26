<?php

namespace App\Http\Requests\Admin\Departments;

use App\Enums\UserRole;
use App\Models\Department;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListDepartmentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Department::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $search = $this->query('search');
        $managerId = $this->query('manager_id');
        $status = $this->query('status');

        $this->merge([
            'search' => is_string($search)
                ? (trim($search) !== '' ? trim($search) : null)
                : $search,
            'manager_id' => is_string($managerId) && trim($managerId) === '' ? null : $managerId,
            'status' => is_string($status) && $status !== '' ? $status : 'active',
            'page' => $this->query('page', 1),
        ]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'manager_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(
                    fn (Builder $query): Builder => $query->where('role', UserRole::Manager->value),
                ),
            ],
            'status' => ['required', Rule::in(['active', 'archived', 'all'])],
            'page' => ['required', 'integer', 'min:1'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'search.string' => 'Từ khóa tìm kiếm không hợp lệ.',
            'search.max' => 'Từ khóa tìm kiếm không được vượt quá 100 ký tự.',
            'manager_id.integer' => 'Lãnh đạo được chọn không hợp lệ.',
            'manager_id.exists' => 'Lãnh đạo được chọn không hợp lệ.',
            'status.required' => 'Trạng thái phòng ban không hợp lệ.',
            'status.in' => 'Trạng thái phòng ban không hợp lệ.',
            'page.required' => 'Trang danh sách không hợp lệ.',
            'page.integer' => 'Trang danh sách không hợp lệ.',
            'page.min' => 'Trang danh sách không hợp lệ.',
        ];
    }
}
