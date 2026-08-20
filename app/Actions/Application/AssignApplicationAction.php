<?php

namespace App\Actions\Application;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\ApplicationAssignment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final readonly class AssignApplicationAction
{
    public function handle(Application $application, User $staff, User $actor, ?string $note = null): Application
    {
        return DB::transaction(function () use ($application, $staff, $actor, $note): Application {
            $locked = Application::query()->lockForUpdate()->findOrFail($application->getKey());

            Gate::forUser($actor)->authorize('assign', $locked);

            if (in_array($locked->status, [
                ApplicationStatus::Approved,
                ApplicationStatus::Rejected,
            ], true)) {
                throw ValidationException::withMessages([
                    'staff_id' => 'Không thể phân công hồ sơ đã hoàn tất.',
                ]);
            }

            $serviceType = $locked->serviceType;

            if ($serviceType === null) {
                throw ValidationException::withMessages([
                    'staff_id' => 'Hồ sơ không hợp lệ.',
                ]);
            }

            $isEligible = $staff->is_active
                && ! $staff->trashed()
                && $staff->departments()->whereKey($serviceType->responsible_department_id)->exists();

            if (! $isEligible) {
                throw ValidationException::withMessages([
                    'staff_id' => 'Staff phải đang hoạt động và thuộc phòng ban phụ trách dịch vụ của hồ sơ.',
                ]);
            }

            $this->closeActiveAssignments($locked);

            ApplicationAssignment::query()->create([
                'application_id' => $locked->getKey(),
                'staff_id' => $staff->getKey(),
                'department_id' => $serviceType->responsible_department_id,
                'assigned_by' => $actor->getKey(),
                'assigned_at' => now(),
                'note' => $note,
            ]);

            $locked->assigned_staff_id = $staff->getKey();
            $locked->save();

            return $locked->refresh();
        });
    }

    private function closeActiveAssignments(Application $application): void
    {
        ApplicationAssignment::query()
            ->where('application_id', $application->getKey())
            ->whereNull('ended_at')
            ->update(['ended_at' => now()]);
    }
}
