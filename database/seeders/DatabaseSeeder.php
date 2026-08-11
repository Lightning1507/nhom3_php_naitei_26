<?php

namespace Database\Seeders;

use App\Enums\UserRole;
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
    }
}
