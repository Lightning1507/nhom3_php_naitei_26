<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\ActivityLog;
use App\Models\Application;
use App\Models\Department;
use App\Models\ServiceType;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CsvExportService
{
    /**
     * Export specified resource data as a streamed CSV response.
     *
     * @param  string  $resource  Resource name ('citizens', 'staff', 'applications', 'services', 'departments')
     * @param  array<string, mixed>  $filters  Query filters (search, status, department_id, date_from, date_to, etc.)
     */
    public function export(string $resource, array $filters = []): StreamedResponse
    {
        $filename = "{$resource}-export-".date('YmdHis').'.csv';

        // Log audit activity
        $this->logActivity($resource, $filters);

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ];

        $callback = function () use ($resource, $filters): void {
            $output = fopen('php://output', 'w');

            // Write UTF-8 BOM for Excel compatibility with Vietnamese characters
            fwrite($output, "\xEF\xBB\xBF");

            match ($resource) {
                'citizens' => $this->exportCitizens($output, $filters),
                'staff' => $this->exportStaff($output, $filters),
                'applications' => $this->exportApplications($output, $filters),
                'services' => $this->exportServices($output, $filters),
                'departments' => $this->exportDepartments($output, $filters),
                default => throw new \InvalidArgumentException("Tài nguyên xuất dữ liệu không hợp lệ: {$resource}"),
            };

            fclose($output);
        };

        return new StreamedResponse($callback, 200, $headers);
    }

    /**
     * Export Citizen users.
     *
     * @param  resource  $output
     * @param  array<string, mixed>  $filters
     */
    private function exportCitizens($output, array $filters): void
    {
        fputcsv($output, [
            'Họ và Tên',
            'Email',
            'Số CCCD',
            'Số điện thoại',
            'Địa chỉ',
            'Ngày sinh',
            'Trạng thái',
            'Ngày tạo',
        ]);

        $query = User::query()->where('role', UserRole::Citizen);

        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function (Builder $q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('citizen_id', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if (isset($filters['status']) && $filters['status'] !== '') {
            $isActive = in_array((string) $filters['status'], ['active', '1', 'true'], true);
            $query->where('is_active', $isActive);
        }

        $query->orderBy('id', 'desc')->chunk(200, function ($users) use ($output): void {
            /** @var User $user */
            foreach ($users as $user) {
                fputcsv($output, [
                    $user->name,
                    $user->email,
                    $user->citizen_id ?? '',
                    $user->phone ?? '',
                    $user->address ?? '',
                    $user->date_of_birth ? $user->date_of_birth->format('Y-m-d') : '',
                    $user->is_active ? 'Hoạt động' : 'Tạm khóa',
                    $user->created_at ? $user->created_at->format('Y-m-d H:i:s') : '',
                ]);
            }
        });
    }

    /**
     * Export Staff/Manager/Admin users.
     *
     * @param  resource  $output
     * @param  array<string, mixed>  $filters
     */
    private function exportStaff($output, array $filters): void
    {
        fputcsv($output, [
            'Họ và Tên',
            'Email',
            'Vai trò',
            'Phòng ban',
            'Số điện thoại',
            'Trạng thái',
            'Ngày tạo',
        ]);

        $query = User::query()
            ->whereIn('role', [UserRole::Staff, UserRole::Manager, UserRole::SuperAdmin])
            ->with('departments');

        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function (Builder $q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['role'])) {
            $query->where('role', $filters['role']);
        }

        if (! empty($filters['department_id'])) {
            $deptId = (int) $filters['department_id'];
            $query->whereHas('departments', function (Builder $dq) use ($deptId): void {
                $dq->where('departments.id', $deptId);
            });
        }

        if (isset($filters['status']) && $filters['status'] !== '') {
            $isActive = in_array((string) $filters['status'], ['active', '1', 'true'], true);
            $query->where('is_active', $isActive);
        }

        $query->orderBy('id', 'desc')->chunk(200, function ($staffList) use ($output): void {
            /** @var User $staff */
            foreach ($staffList as $staff) {
                $departments = $staff->departments->pluck('name')->implode(', ');

                fputcsv($output, [
                    $staff->name,
                    $staff->email,
                    strtoupper($staff->role->value ?? (string) $staff->role),
                    $departments !== '' ? $departments : 'Chưa phân công',
                    $staff->phone ?? '',
                    $staff->is_active ? 'Hoạt động' : 'Tạm khóa',
                    $staff->created_at ? $staff->created_at->format('Y-m-d H:i:s') : '',
                ]);
            }
        });
    }

    /**
     * Export Applications.
     *
     * @param  resource  $output
     * @param  array<string, mixed>  $filters
     */
    private function exportApplications($output, array $filters): void
    {
        fputcsv($output, [
            'Mã hồ sơ',
            'Tên công dân',
            'Mã định danh/CCCD',
            'Số điện thoại',
            'Tên dịch vụ',
            'Phòng ban xử lý',
            'Trạng thái',
            'Ngày nộp',
            'Ngày hoàn thành',
        ]);

        $query = Application::query()
            ->with(['citizen', 'serviceType.responsibleDepartment']);

        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function (Builder $q) use ($search): void {
                $q->where('application_code', 'like', "%{$search}%")
                    ->orWhereHas('citizen', function (Builder $cq) use ($search): void {
                        $cq->where('name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%")
                            ->orWhere('citizen_id', 'like', "%{$search}%");
                    });
            });
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['department_id'])) {
            $deptId = (int) $filters['department_id'];
            $query->whereHas('serviceType', function (Builder $sq) use ($deptId): void {
                $sq->where('responsible_department_id', $deptId);
            });
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('submitted_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('submitted_at', '<=', $filters['date_to']);
        }

        $query->orderBy('id', 'desc')->chunk(200, function ($applications) use ($output): void {
            /** @var Application $app */
            foreach ($applications as $app) {
                $citizen = $app->citizen;
                $service = $app->serviceType;
                $department = $service?->responsibleDepartment;

                $statusValue = is_object($app->status) && property_exists($app->status, 'value')
                    ? $app->status->value
                    : (string) $app->status;

                fputcsv($output, [
                    $app->application_code,
                    $citizen?->name ?? 'N/A',
                    $citizen?->citizen_id ?? '',
                    $citizen?->phone ?? '',
                    $service?->name ?? 'N/A',
                    $department?->name ?? 'N/A',
                    $statusValue,
                    $app->submitted_at ? $app->submitted_at->format('Y-m-d H:i:s') : '',
                    $app->completed_at ? $app->completed_at->format('Y-m-d H:i:s') : '',
                ]);
            }
        });
    }

    /**
     * Export Services (ServiceTypes).
     *
     * @param  resource  $output
     * @param  array<string, mixed>  $filters
     */
    private function exportServices($output, array $filters): void
    {
        fputcsv($output, [
            'Mã thủ tục',
            'Tên thủ tục',
            'Danh mục/Lĩnh vực',
            'Phòng ban phụ trách',
            'Thời hạn xử lý (ngày)',
            'Lệ phí (VNĐ)',
            'Trạng thái',
        ]);

        $query = ServiceType::query()->with(['category', 'responsibleDepartment']);

        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function (Builder $q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['department_id'])) {
            $query->where('responsible_department_id', (int) $filters['department_id']);
        }

        if (isset($filters['status']) && $filters['status'] !== '') {
            $isActive = in_array((string) $filters['status'], ['active', '1', 'true'], true);
            $query->where('is_active', $isActive);
        }

        $query->orderBy('id', 'desc')->chunk(200, function ($services) use ($output): void {
            /** @var ServiceType $service */
            foreach ($services as $service) {
                fputcsv($output, [
                    $service->code,
                    $service->name,
                    $service->category?->name ?? 'N/A',
                    $service->responsibleDepartment?->name ?? 'N/A',
                    $service->processing_time_days ?? 0,
                    number_format((float) ($service->fee ?? 0), 0, ',', '.'),
                    $service->is_active ? 'Hoạt động' : 'Tạm dừng',
                ]);
            }
        });
    }

    /**
     * Export Departments.
     *
     * @param  resource  $output
     * @param  array<string, mixed>  $filters
     */
    private function exportDepartments($output, array $filters): void
    {
        fputcsv($output, [
            'Mã phòng ban',
            'Tên phòng ban',
            'Địa chỉ',
            'Trưởng phòng',
            'Số lượng nhân sự',
            'Trạng thái',
        ]);

        $query = Department::query()->with('leader')->withCount('users');

        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function (Builder $q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $query->orderBy('id', 'desc')->chunk(200, function ($departments) use ($output): void {
            /** @var Department $dept */
            foreach ($departments as $dept) {
                fputcsv($output, [
                    $dept->code,
                    $dept->name,
                    $dept->address ?? '',
                    $dept->leader?->name ?? 'Chưa phân công',
                    $dept->users_count ?? 0,
                    $dept->trashed() ? 'Đã lưu trữ' : 'Hoạt động',
                ]);
            }
        });
    }

    /**
     * Audit log for CSV export action.
     *
     * @param  array<string, mixed>  $filters
     */
    private function logActivity(string $resource, array $filters): void
    {
        ActivityLog::query()->create([
            'actor_id' => Auth::id(),
            'action' => "user.export.{$resource}",
            'subject_type' => User::class,
            'description' => "Đã xuất danh sách {$resource} ra tệp CSV.",
            'metadata' => array_filter($filters),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
