<?php

namespace App\Http\Requests\Admin\Departments;

use App\Models\Department;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UpdateDepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $department = $this->route('department');
        $actor = $this->user();

        if (! $department instanceof Department || ! $actor) {
            return false;
        }

        Gate::forUser($actor)->authorize('update', $department);

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Department $department */
        $department = $this->route('department');

        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required',
                'string',
                'min:2',
                'max:50',
                'regex:/^[A-Z0-9]+(?:[-_][A-Z0-9]+)*$/',
                Rule::unique('departments', 'code')->ignore($department->getKey()),
            ],
            'address' => ['nullable', 'string', 'max:1000'],
            'version' => ['required', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Vui lòng nhập tên phòng ban.',
            'name.max' => 'Tên phòng ban không được vượt quá 255 ký tự.',
            'code.required' => 'Vui lòng nhập mã phòng ban.',
            'code.min' => 'Mã phòng ban phải có ít nhất 2 ký tự.',
            'code.max' => 'Mã phòng ban không được vượt quá 50 ký tự.',
            'code.regex' => 'Mã chỉ được gồm chữ cái, số và dấu gạch nối hoặc gạch dưới.',
            'code.unique' => 'Mã phòng ban đã tồn tại, kể cả trong dữ liệu đã lưu trữ.',
            'address.max' => 'Địa chỉ không được vượt quá 1.000 ký tự.',
            'version.required' => 'Phiên bản dữ liệu không hợp lệ. Vui lòng tải lại trang.',
            'version.integer' => 'Phiên bản dữ liệu không hợp lệ. Vui lòng tải lại trang.',
            'version.min' => 'Phiên bản dữ liệu không hợp lệ. Vui lòng tải lại trang.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => preg_replace('/\s+/u', ' ', trim((string) $this->input('name'))) ?? '',
            'code' => mb_strtoupper(trim((string) $this->input('code'))),
            'address' => $this->normalizeAddress($this->input('address')),
        ]);
    }

    private function normalizeAddress(mixed $value): ?string
    {
        $address = trim((string) $value);

        return $address === '' ? null : $address;
    }
}
