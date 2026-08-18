<?php

namespace App\Actions\Department;

use App\Exceptions\StaleDepartmentVersion;
use App\Models\Department;
use App\Models\User;
use App\Support\Department\DepartmentActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final readonly class ArchiveDepartment
{
    public function __construct(private DepartmentActivityLogger $activityLogger) {}

    public function handle(
        Department $department,
        int $version,
        User $actor,
        ?Request $request = null,
    ): Department {
        return DB::transaction(function () use ($department, $version, $actor, $request): Department {
            $lockedDepartment = Department::withTrashed()
                ->lockForUpdate()
                ->findOrFail($department->getKey());

            Gate::forUser($actor)->authorize('archive', $lockedDepartment);

            $actualVersion = (int) $lockedDepartment->lock_version;
            if ($actualVersion !== $version) {
                throw new StaleDepartmentVersion(
                    (int) $lockedDepartment->getKey(),
                    $version,
                    $actualVersion,
                );
            }

            $before = $this->activityLogger->departmentSnapshot($lockedDepartment);
            $lockedDepartment->lock_version = $actualVersion + 1;
            $lockedDepartment->save();
            $lockedDepartment->delete();
            $after = $this->activityLogger->departmentSnapshot($lockedDepartment);

            $this->activityLogger->record(
                DepartmentActivityLogger::ARCHIVED,
                $lockedDepartment,
                $actor,
                $request,
                before: $before,
                after: $after,
            );

            return $lockedDepartment;
        });
    }
}
