<?php

namespace App\Http\Requests\Admin\Applications;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ListApplicationsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Application::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'q' => $this->normalizedString('q'),
            'status' => $this->normalizedString('status'),
            'service_type_id' => $this->normalizedNullableValue('service_type_id'),
            'department_id' => $this->normalizedNullableValue('department_id'),
            'assigned_staff_id' => $this->normalizedNullableValue('assigned_staff_id'),
            'submitted_from' => $this->normalizedString('submitted_from'),
            'submitted_to' => $this->normalizedString('submitted_to'),
            'overdue' => $this->normalizedNullableValue('overdue'),
            'sort' => $this->normalizedString('sort') ?? ApplicationStatus::defaultSort(),
            'page' => $this->query('page', 1),
        ]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(ApplicationStatus::filterValues())],
            'service_type_id' => ['nullable', 'integer', 'min:1'],
            'department_id' => ['nullable', 'integer', 'min:1'],
            'assigned_staff_id' => ['nullable', 'integer', 'min:1'],
            'submitted_from' => ['nullable', 'date_format:Y-m-d'],
            'submitted_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:submitted_from'],
            'overdue' => ['nullable', 'boolean'],
            'sort' => ['required', Rule::in(ApplicationStatus::sortValues())],
            'page' => ['required', 'integer', 'min:1'],
        ];
    }

    /** @return array<int, callable> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->hasAny([
                'service_type_id',
                'department_id',
                'assigned_staff_id',
            ])) {
                return;
            }

            foreach (['service_type_id', 'department_id', 'assigned_staff_id'] as $field) {
                $value = $this->input($field);

                if ($value !== null && ! $this->isVisibleFilterValue($field, (int) $value)) {
                    $validator->errors()->add($field, 'Giá trị bộ lọc không hợp lệ.');
                }
            }
        }];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'q.string' => 'Từ khóa tìm kiếm không hợp lệ.',
            'q.max' => 'Từ khóa tìm kiếm không được vượt quá 100 ký tự.',
            'status.in' => 'Trạng thái hồ sơ không hợp lệ.',
            'service_type_id.integer' => 'Giá trị bộ lọc không hợp lệ.',
            'service_type_id.min' => 'Giá trị bộ lọc không hợp lệ.',
            'department_id.integer' => 'Giá trị bộ lọc không hợp lệ.',
            'department_id.min' => 'Giá trị bộ lọc không hợp lệ.',
            'assigned_staff_id.integer' => 'Giá trị bộ lọc không hợp lệ.',
            'assigned_staff_id.min' => 'Giá trị bộ lọc không hợp lệ.',
            'submitted_from.date_format' => 'Ngày nộp từ ngày không hợp lệ.',
            'submitted_to.date_format' => 'Ngày nộp đến ngày không hợp lệ.',
            'submitted_to.after_or_equal' => 'Ngày nộp đến ngày phải bằng hoặc sau ngày nộp từ ngày.',
            'overdue.boolean' => 'Giá trị lọc quá hạn không hợp lệ.',
            'sort.required' => 'Thứ tự danh sách không hợp lệ.',
            'sort.in' => 'Thứ tự danh sách không hợp lệ.',
            'page.required' => 'Trang danh sách không hợp lệ.',
            'page.integer' => 'Trang danh sách không hợp lệ.',
            'page.min' => 'Trang danh sách không hợp lệ.',
        ];
    }

    private function isVisibleFilterValue(string $field, int $value): bool
    {
        $actor = $this->user();

        if ($actor === null) {
            return false;
        }

        $query = Application::query()->visibleTo($actor);

        return match ($field) {
            'service_type_id' => $query->where('service_type_id', $value)->exists(),
            'department_id' => $query->whereHas(
                'serviceType',
                fn (Builder $serviceQuery): Builder => $serviceQuery
                    ->withTrashed()
                    ->where('responsible_department_id', $value),
            )->exists(),
            'assigned_staff_id' => $query->where('assigned_staff_id', $value)->exists(),
            default => false,
        };
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

    private function normalizedNullableValue(string $key): mixed
    {
        $value = $this->query($key);

        return is_string($value) && trim($value) === '' ? null : $value;
    }
}
