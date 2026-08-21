<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\Application;
use App\Models\Department;
use App\Models\ServiceType;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class UserStatusConcurrencyTest extends TestCase
{
    use DatabaseMigrations;

    public function test_simultaneous_super_admin_deactivations_cannot_remove_the_last_active_admin(): void
    {
        $adminA = $this->superAdmin();
        $adminB = $this->superAdmin();

        $processA = $this->childProcess(sprintf(
            <<<'PHP'
try {
    $target = App\Models\User::query()->findOrFail(%d);
    $actor = App\Models\User::query()->findOrFail(%d);
    app(App\Actions\User\SetUserActiveStatus::class)->handle($target, false, $actor);
    echo 'changed';
} catch (Throwable $exception) {
    echo 'blocked:'.get_class($exception);
}
PHP,
            $adminB->id,
            $adminA->id,
        ));
        $processB = $this->childProcess(sprintf(
            <<<'PHP'
try {
    $target = App\Models\User::query()->findOrFail(%d);
    $actor = App\Models\User::query()->findOrFail(%d);
    app(App\Actions\User\SetUserActiveStatus::class)->handle($target, false, $actor);
    echo 'changed';
} catch (Throwable $exception) {
    echo 'blocked:'.get_class($exception);
}
PHP,
            $adminA->id,
            $adminB->id,
        ));

        $processA->start();
        $processB->start();
        $processA->wait();
        $processB->wait();

        $this->assertTrue($processA->isSuccessful(), $processA->getErrorOutput());
        $this->assertTrue($processB->isSuccessful(), $processB->getErrorOutput());
        $this->assertSame(1, User::query()
            ->where('role', UserRole::SuperAdmin->value)
            ->where('is_active', true)
            ->count());
        $this->assertDatabaseCount('activity_logs', 1);
    }

    public function test_assign_and_deactivation_serialize_on_fresh_staff_row(): void
    {
        $admin = $this->superAdmin();
        [$department, $service] = $this->serviceContext();
        $staff = User::factory()->staff()->create();
        $department->users()->attach($staff);
        $application = Application::factory()->create(['service_type_id' => $service->id]);

        [$statusProcess, $assignmentProcess] = $this->competingProcesses(
            $admin,
            $staff,
            sprintf(
                <<<'PHP'
try {
    $application = App\Models\Application::query()->findOrFail(%d);
    $staff = App\Models\User::query()->findOrFail(%d);
    $actor = App\Models\User::query()->findOrFail(%d);
    app(App\Actions\Application\AssignApplicationAction::class)->handle($application, $staff, $actor);
    echo 'assigned';
} catch (Throwable $exception) {
    echo 'blocked:'.get_class($exception);
}
PHP,
                $application->id,
                $staff->id,
                $admin->id,
            ),
        );

        $this->assertCompetingProcessesSucceeded($statusProcess, $assignmentProcess);
        $this->assertSerializedAssignmentOutcome($staff, $application);
    }

    public function test_claim_and_deactivation_serialize_on_fresh_staff_row(): void
    {
        $admin = $this->superAdmin();
        [$department, $service] = $this->serviceContext();
        $staff = User::factory()->staff()->create();
        $department->users()->attach($staff);
        $application = Application::factory()->create(['service_type_id' => $service->id]);

        [$statusProcess, $claimProcess] = $this->competingProcesses(
            $admin,
            $staff,
            sprintf(
                <<<'PHP'
try {
    $application = App\Models\Application::query()->findOrFail(%d);
    $actor = App\Models\User::query()->findOrFail(%d);
    app(App\Actions\Application\ClaimApplicationAction::class)->handle($application, $actor);
    echo 'claimed';
} catch (Throwable $exception) {
    echo 'blocked:'.get_class($exception);
}
PHP,
                $application->id,
                $staff->id,
            ),
        );

        $this->assertCompetingProcessesSucceeded($statusProcess, $claimProcess);
        $this->assertSerializedAssignmentOutcome($staff, $application);
    }

    /** @return array{Process, Process} */
    private function competingProcesses(User $admin, User $staff, string $applicationBody): array
    {
        $statusProcess = $this->childProcess(sprintf(
            <<<'PHP'
try {
    $target = App\Models\User::query()->findOrFail(%d);
    $actor = App\Models\User::query()->findOrFail(%d);
    app(App\Actions\User\SetUserActiveStatus::class)->handle($target, false, $actor);
    echo 'deactivated';
} catch (Throwable $exception) {
    echo 'blocked:'.get_class($exception);
}
PHP,
            $staff->id,
            $admin->id,
        ));
        $applicationProcess = $this->childProcess($applicationBody);

        $statusProcess->start();
        $applicationProcess->start();
        $statusProcess->wait();
        $applicationProcess->wait();

        return [$statusProcess, $applicationProcess];
    }

    private function assertCompetingProcessesSucceeded(Process $first, Process $second): void
    {
        $this->assertTrue($first->isSuccessful(), $first->getErrorOutput());
        $this->assertTrue($second->isSuccessful(), $second->getErrorOutput());
    }

    private function assertSerializedAssignmentOutcome(User $staff, Application $application): void
    {
        $staff = $staff->fresh();
        $application = $application->fresh();

        if ($staff->is_active) {
            $this->assertSame($staff->id, $application->assigned_staff_id);
            $this->assertDatabaseHas('application_assignments', [
                'application_id' => $application->id,
                'staff_id' => $staff->id,
            ]);
            $this->assertDatabaseMissing('activity_logs', [
                'action' => 'user.deactivated',
                'subject_id' => $staff->id,
            ]);

            return;
        }

        $this->assertNull($application->assigned_staff_id);
        $this->assertDatabaseMissing('application_assignments', [
            'application_id' => $application->id,
            'staff_id' => $staff->id,
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'user.deactivated',
            'subject_id' => $staff->id,
        ]);
    }

    private function childProcess(string $body): Process
    {
        $bootstrap = <<<'PHP'
require getcwd().'/vendor/autoload.php';
$app = require getcwd().'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
PHP;

        $connection = (string) config('database.default');
        $database = config("database.connections.{$connection}");
        $process = new Process(
            [PHP_BINARY, '-r', $bootstrap.$body],
            base_path(),
            [
                'APP_ENV' => 'testing',
                'APP_KEY' => (string) config('app.key'),
                'CACHE_STORE' => 'array',
                'SESSION_DRIVER' => 'array',
                'DB_CONNECTION' => $connection,
                'DB_HOST' => (string) $database['host'],
                'DB_PORT' => (string) $database['port'],
                'DB_DATABASE' => (string) $database['database'],
                'DB_USERNAME' => (string) $database['username'],
                'DB_PASSWORD' => (string) $database['password'],
                'DB_SSLMODE' => (string) ($database['sslmode'] ?? 'prefer'),
            ],
        );
        $process->setTimeout(20);

        return $process;
    }

    /** @return array{Department, ServiceType} */
    private function serviceContext(): array
    {
        $department = Department::factory()->create();
        $service = ServiceType::factory()->create([
            'responsible_department_id' => $department->id,
        ]);

        return [$department, $service];
    }

    private function superAdmin(): User
    {
        return User::factory()->withRole(UserRole::SuperAdmin)->create();
    }
}
