<?php

namespace App\Actions\Application;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\ApplicationAssignment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final readonly class ClaimApplicationAction
{
    public function handle(Application $application, User $actor): Application
    {
        return DB::transaction(function () use ($application, $actor): Application {
            $locked = Application::query()->lockForUpdate()->findOrFail($application->getKey());

            Gate::forUser($actor)->authorize('claim', $locked);

            if ($locked->assigned_staff_id !== null || $locked->status !== ApplicationStatus::Received) {
                throw ValidationException::withMessages([
                    'application' => 'Hồ sơ này đã có người phụ trách hoặc không thể nhận.',
                ]);
            }

            $serviceType = $locked->serviceType;

            if (! $actor->isSuperAdmin() && ($serviceType === null || ! $actor->departments()->whereKey($serviceType->responsible_department_id)->exists())) {
                throw ValidationException::withMessages([
                    'application' => 'Bạn không thuộc phòng ban phụ trách dịch vụ của hồ sơ này.',
                ]);
            }

            ApplicationAssignment::query()->create([
                'application_id' => $locked->getKey(),
                'staff_id' => $actor->getKey(),
                'department_id' => $serviceType->responsible_department_id,
                'assigned_by' => $actor->getKey(),
                'assigned_at' => now(),
            ]);

            $locked->assigned_staff_id = $actor->getKey();
            $locked->save();

            return $locked->refresh();
        });
    }
}
