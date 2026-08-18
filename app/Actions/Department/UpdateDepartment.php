<?php

namespace App\Actions\Department;

use App\Exceptions\StaleDepartmentVersion;
use App\Models\Department;
use App\Models\User;
use App\Support\Department\DepartmentActivityLogger;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class UpdateDepartment
{
    public function __construct(private DepartmentActivityLogger $activityLogger) {}

    /**
     * @param  array{name: string, code: string, address: ?string, version: int}  $attributes
     */
    public function handle(
        Department $department,
        array $attributes,
        User $actor,
        ?Request $request = null,
    ): Department {
        try {
            return DB::transaction(function () use ($department, $attributes, $actor, $request): Department {
                $lockedDepartment = Department::withTrashed()
                    ->lockForUpdate()
                    ->findOrFail($department->getKey());

                if ($lockedDepartment->isArchived()) {
                    throw ValidationException::withMessages([
                        'department' => 'Phòng ban đã lưu trữ chỉ có thể được tra cứu.',
                    ]);
                }

                $expectedVersion = (int) $attributes['version'];
                $actualVersion = (int) $lockedDepartment->lock_version;

                if ($actualVersion !== $expectedVersion) {
                    throw new StaleDepartmentVersion(
                        (int) $lockedDepartment->getKey(),
                        $expectedVersion,
                        $actualVersion,
                    );
                }

                $before = $this->activityLogger->departmentSnapshot($lockedDepartment);

                $lockedDepartment->fill([
                    'name' => $attributes['name'],
                    'code' => $attributes['code'],
                    'address' => $attributes['address'],
                ]);
                $lockedDepartment->lock_version = $actualVersion + 1;
                $lockedDepartment->save();

                $after = $this->activityLogger->departmentSnapshot($lockedDepartment);
                $this->activityLogger->record(
                    DepartmentActivityLogger::UPDATED,
                    $lockedDepartment,
                    $actor,
                    $request,
                    before: $before,
                    after: $after,
                );

                return $lockedDepartment;
            });
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages([
                'code' => 'Mã phòng ban đã tồn tại, kể cả trong dữ liệu đã lưu trữ.',
            ]);
        }
    }
}
