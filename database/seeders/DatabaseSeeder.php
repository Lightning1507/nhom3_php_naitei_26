<?php

namespace Database\Seeders;

use App\Enums\ApplicationStatus;
use App\Enums\UserRole;
use App\Models\Application;
use App\Models\Department;
use App\Models\ServiceCategory;
use App\Models\ServiceType;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        User::factory()->withRole(UserRole::SuperAdmin)->create([
            'name' => 'Development Super Admin',
            'email' => 'admin@example.test',
            'password' => 'password',
        ]);

        $manager = User::factory()->withRole(UserRole::Manager)->create([
            'name' => 'Development Manager',
            'email' => 'manager@example.test',
            'password' => 'password',
        ]);

        $staffOne = User::factory()->withRole(UserRole::Staff)->create([
            'name' => 'Development Staff One',
            'email' => 'staff1@example.test',
            'password' => 'password',
        ]);

        $staffTwo = User::factory()->withRole(UserRole::Staff)->create([
            'name' => 'Development Staff Two',
            'email' => 'staff2@example.test',
            'password' => 'password',
        ]);

        User::factory()->withRole(UserRole::Citizen)->create([
            'name' => 'Development Citizen One',
            'email' => 'citizen1@example.test',
            'password' => 'password',
            'citizen_id' => 'CITIZEN-0001',
        ]);

        User::factory()->withRole(UserRole::Citizen)->create([
            'name' => 'Development Citizen Two',
            'email' => 'citizen2@example.test',
            'password' => 'password',
            'citizen_id' => 'CITIZEN-0002',
        ]);

        $citizenServices = Department::query()->create([
            'name' => 'Citizen Services Department',
            'code' => 'CSD',
            'address' => 'Public Service Center',
            'leader_id' => $manager->id,
        ]);

        $technicalServices = Department::query()->create([
            'name' => 'Technical and Infrastructure Department',
            'code' => 'TID',
            'address' => 'Technical Administration Building',
            'leader_id' => $manager->id,
        ]);

        $socialServices = Department::query()->create([
            'name' => 'Education and Health Department',
            'code' => 'EHD',
            'address' => 'Community Services Building',
            'leader_id' => $manager->id,
        ]);

        $citizenServices->users()->attach([$manager->id, $staffOne->id]);
        $technicalServices->users()->attach([$manager->id, $staffOne->id]);
        $socialServices->users()->attach([$manager->id, $staffTwo->id]);

        $categories = collect([
            ['name' => 'Administration', 'code' => 'ADMINISTRATION'],
            ['name' => 'Education', 'code' => 'EDUCATION'],
            ['name' => 'Healthcare', 'code' => 'HEALTHCARE'],
            ['name' => 'Construction', 'code' => 'CONSTRUCTION'],
            ['name' => 'Natural Resources', 'code' => 'NATURAL_RESOURCES'],
        ])->mapWithKeys(function (array $category): array {
            $model = ServiceCategory::query()->create($category);

            return [$category['code'] => $model];
        });

        $civilStatus = ServiceType::query()->create([
            'category_id' => $categories['ADMINISTRATION']->id,
            'responsible_department_id' => $citizenServices->id,
            'name' => 'Civil Status Certificate',
            'code' => 'CIVIL_STATUS_CERTIFICATE',
            'requirements' => 'Valid citizen identification is required.',
            'document_requirements' => [
                ['code' => 'citizen_id_copy', 'label' => 'Citizen ID Copy', 'required' => true],
            ],
            'processing_time_days' => 3,
        ]);

        $constructionPermit = ServiceType::query()->create([
            'category_id' => $categories['CONSTRUCTION']->id,
            'responsible_department_id' => $technicalServices->id,
            'name' => 'Construction Permit',
            'code' => 'CONSTRUCTION_PERMIT',
            'form_schema' => [
                [
                    'name' => 'construction_area',
                    'label' => 'Construction Area',
                    'type' => 'number',
                    'required' => true,
                ],
            ],
            'document_requirements' => [
                ['code' => 'design_drawing', 'label' => 'Design Drawing', 'required' => true],
            ],
            'processing_time_days' => 15,
            'fee' => 100000,
        ]);

        $schoolEnrollment = ServiceType::query()->create([
            'category_id' => $categories['EDUCATION']->id,
            'responsible_department_id' => $socialServices->id,
            'name' => 'Public School Enrollment',
            'code' => 'PUBLIC_SCHOOL_ENROLLMENT',
            'processing_time_days' => 5,
        ]);

        $healthcareSupport = ServiceType::query()->create([
            'category_id' => $categories['HEALTHCARE']->id,
            'responsible_department_id' => $socialServices->id,
            'name' => 'Healthcare Support Registration',
            'code' => 'HEALTHCARE_SUPPORT_REGISTRATION',
            'processing_time_days' => 7,
        ]);

        $landRecord = ServiceType::query()->create([
            'category_id' => $categories['NATURAL_RESOURCES']->id,
            'responsible_department_id' => $technicalServices->id,
            'name' => 'Land Record Information Request',
            'code' => 'LAND_RECORD_INFORMATION',
            'processing_time_days' => 10,
            'fee' => 50000,
        ]);

        $staffOne->serviceTypes()->attach([
            $civilStatus->id,
            $constructionPermit->id,
            $landRecord->id,
        ]);

        $staffTwo->serviceTypes()->attach([
            $schoolEnrollment->id,
            $healthcareSupport->id,
        ]);

        $citizenOne = User::query()->where('email', 'citizen1@example.test')->firstOrFail();
        $citizenTwo = User::query()->where('email', 'citizen2@example.test')->firstOrFail();

        $seedApplication = static function (
            string $code,
            User $citizen,
            ServiceType $service,
            ApplicationStatus $status,
            int $daysAgo,
            ?User $assignedStaff = null,
        ): void {
            $submittedAt = now()->subDays($daysAgo);
            $attributes = [
                'application_code' => $code,
                'citizen_id' => $citizen->id,
                'service_type_id' => $service->id,
                'status' => $status,
                'form_data' => ['full_name' => $citizen->name],
                'submitted_at' => $submittedAt,
            ];

            if ($assignedStaff !== null) {
                $attributes['assigned_staff_id'] = $assignedStaff->id;
            }

            if (in_array($status, [ApplicationStatus::Processing, ApplicationStatus::SupplementRequired, ApplicationStatus::Approved, ApplicationStatus::Rejected], true)) {
                $attributes['processing_started_at'] = now()->subDays(max(1, $daysAgo - 1));
            }

            if ($status === ApplicationStatus::Approved) {
                $attributes['completed_at'] = now()->subDay();
                $attributes['result_note'] = 'Đã cấp chứng nhận theo quy định.';
            }

            if ($status === ApplicationStatus::Rejected) {
                $attributes['completed_at'] = now()->subDay();
                $attributes['rejection_reason'] = 'Hồ sơ thiếu giấy tờ bắt buộc.';
            }

            $application = Application::query()->create($attributes);

            $application->statusHistories()->create([
                'from_status' => null,
                'to_status' => ApplicationStatus::Received,
                'changed_by' => $citizen->id,
                'note' => 'Hồ sơ được nộp trực tuyến.',
            ]);

            if (in_array($status, [ApplicationStatus::Processing, ApplicationStatus::SupplementRequired, ApplicationStatus::Approved, ApplicationStatus::Rejected], true)) {
                $application->statusHistories()->create([
                    'from_status' => ApplicationStatus::Received,
                    'to_status' => ApplicationStatus::Processing,
                    'changed_by' => $assignedStaff?->id ?? $citizen->id,
                    'note' => 'Cán bộ bắt đầu xử lý.',
                ]);
            }

            if ($status === ApplicationStatus::SupplementRequired) {
                $application->statusHistories()->create([
                    'from_status' => ApplicationStatus::Processing,
                    'to_status' => ApplicationStatus::SupplementRequired,
                    'changed_by' => $assignedStaff?->id ?? $citizen->id,
                    'note' => 'Cần bổ sung sổ hộ khẩu.',
                ]);
            }

            if ($status === ApplicationStatus::Approved) {
                $application->statusHistories()->create([
                    'from_status' => ApplicationStatus::Processing,
                    'to_status' => ApplicationStatus::Approved,
                    'changed_by' => $assignedStaff?->id ?? $citizen->id,
                    'note' => 'Đã kiểm tra đầy đủ.',
                ]);
            }

            if ($status === ApplicationStatus::Rejected) {
                $application->statusHistories()->create([
                    'from_status' => ApplicationStatus::Processing,
                    'to_status' => ApplicationStatus::Rejected,
                    'changed_by' => $assignedStaff?->id ?? $citizen->id,
                    'note' => $attributes['rejection_reason'],
                ]);
            }

            if ($assignedStaff !== null) {
                $application->assignments()->create([
                    'staff_id' => $assignedStaff->id,
                    'department_id' => $service->responsible_department_id,
                    'assigned_by' => $service->responsibleDepartment->leader_id,
                    'assigned_at' => now()->subDays(max(1, $daysAgo - 1)),
                    'ended_at' => null,
                ]);
            }
        };

        $seedApplication('HS-20260820-000001', $citizenOne, $civilStatus, ApplicationStatus::Received, 2);
        $seedApplication('HS-20260820-000002', $citizenOne, $constructionPermit, ApplicationStatus::Processing, 3, $staffOne);
        $seedApplication('HS-20260820-000003', $citizenTwo, $schoolEnrollment, ApplicationStatus::SupplementRequired, 4, $staffTwo);
        $seedApplication('HS-20260820-000004', $citizenTwo, $healthcareSupport, ApplicationStatus::Approved, 6, $staffTwo);
        $seedApplication('HS-20260820-000005', $citizenOne, $landRecord, ApplicationStatus::Rejected, 5, $staffOne);
    }
}
