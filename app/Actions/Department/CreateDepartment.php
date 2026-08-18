<?php

namespace App\Actions\Department;

use App\Models\Department;
use App\Models\User;
use App\Support\Department\DepartmentActivityLogger;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class CreateDepartment
{
    public function __construct(private DepartmentActivityLogger $activityLogger) {}

    /**
     * @param  array{name: string, code: string, address: ?string}  $attributes
     */
    public function handle(array $attributes, User $actor, ?Request $request = null): Department
    {
        try {
            return DB::transaction(function () use ($attributes, $actor, $request): Department {
                $department = new Department;
                $department->fill($attributes);
                $department->lock_version = 0;
                $department->save();

                $snapshot = $this->activityLogger->departmentSnapshot($department);
                $this->activityLogger->record(
                    DepartmentActivityLogger::CREATED,
                    $department,
                    $actor,
                    $request,
                    after: $snapshot,
                );

                return $department;
            });
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages([
                'code' => 'Mã phòng ban đã tồn tại, kể cả trong dữ liệu đã lưu trữ.',
            ]);
        }
    }
}
