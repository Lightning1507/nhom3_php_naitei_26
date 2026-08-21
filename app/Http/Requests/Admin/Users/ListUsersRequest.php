<?php

namespace App\Http\Requests\Admin\Users;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListUsersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', User::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'search' => $this->normalizedString('search'),
            'role' => $this->normalizedString('role'),
            'status' => $this->normalizedString('status'),
            'page' => $this->query('page', 1),
        ]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'role' => ['nullable', Rule::enum(UserRole::class)],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
            'page' => ['required', 'integer', 'min:1'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'search.string' => 'Từ khóa tìm kiếm không hợp lệ.',
            'search.max' => 'Từ khóa tìm kiếm không được vượt quá 100 ký tự.',
            'role.enum' => 'Vai trò người dùng không hợp lệ.',
            'status.in' => 'Trạng thái tài khoản không hợp lệ.',
            'page.required' => 'Trang danh sách không hợp lệ.',
            'page.integer' => 'Trang danh sách không hợp lệ.',
            'page.min' => 'Trang danh sách không hợp lệ.',
        ];
    }

    private function normalizedString(string $key): mixed
    {
        $value = $this->query($key);

        if (! is_string($value)) {
            return $value;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
