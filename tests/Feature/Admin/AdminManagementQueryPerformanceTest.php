<?php

namespace Tests\Feature\Admin;

use App\Enums\ApplicationStatus;
use App\Enums\UserRole;
use App\Models\Department;
use App\Models\ServiceCategory;
use App\Models\ServiceType;
use App\Models\User;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[Group('admin-management-performance')]
class AdminManagementQueryPerformanceTest extends TestCase
{
    use RefreshDatabase;

    private const SAMPLE_COUNT = 20;

    public function test_sc_002_and_sc_006_admin_paths_at_reference_scale(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            $this->markTestSkipped('F07 benchmark requires PostgreSQL.');
        }

        [$actor, $serviceIds] = $this->seedReferenceDataset();
        $this->assertGreaterThanOrEqual(10_000, DB::table('users')->count());
        $this->assertSame(10_000, DB::table('applications')->count());

        $scenarios = [
            'application_list' => [
                'url' => route('admin.applications.index'),
                'max_queries' => 16,
            ],
            'application_search' => [
                'url' => route('admin.applications.index', ['q' => 'PERF-APP-09999']),
                'max_queries' => 16,
            ],
            'application_filter' => [
                'url' => route('admin.applications.index', [
                    'status' => ApplicationStatus::Processing->value,
                    'service_type_id' => $serviceIds[0],
                ]),
                'max_queries' => 16,
            ],
            'application_page' => [
                'url' => route('admin.applications.index', ['page' => 400]),
                'max_queries' => 16,
            ],
            'dashboard' => [
                'url' => route('admin.dashboard'),
                'max_queries' => 3,
            ],
            'user_list' => [
                'url' => route('admin.users.index', [
                    'search' => 'Performance User',
                    'role' => UserRole::Citizen->value,
                    'status' => 'active',
                    'page' => 250,
                ]),
                'max_queries' => 4,
            ],
        ];

        $activeScenario = null;
        $capturedQueries = [];
        DB::listen(static function (QueryExecuted $query) use (&$activeScenario, &$capturedQueries): void {
            if ($activeScenario === null) {
                return;
            }

            $capturedQueries[$activeScenario][] = [
                'bindings' => $query->bindings,
                'milliseconds' => $query->time,
                'sql' => $query->sql,
            ];
        });

        $this->actingAs($actor);
        $results = [];

        foreach ($scenarios as $name => $scenario) {
            $samples = [];
            $queryCounts = [];

            for ($iteration = 0; $iteration < self::SAMPLE_COUNT; $iteration++) {
                $activeScenario = $name;
                $capturedQueries[$name] = [];
                $startedAt = hrtime(true);

                $this->get($scenario['url'])->assertOk();

                $samples[] = (hrtime(true) - $startedAt) / 1_000_000;
                $queryCounts[] = count($capturedQueries[$name]);
                $activeScenario = null;
            }

            $slowestSelect = collect($capturedQueries[$name])
                ->filter(fn (array $query): bool => str_starts_with(strtolower(ltrim($query['sql'])), 'select'))
                ->sortByDesc('milliseconds')
                ->first();

            $results[$name] = [
                'p95_ms' => round($this->percentile95($samples), 2),
                'max_queries' => max($queryCounts),
                'slowest_query_ms' => round((float) ($slowestSelect['milliseconds'] ?? 0), 2),
                'plan' => isset($slowestSelect['sql'])
                    ? $this->explainSummary($slowestSelect['sql'], $slowestSelect['bindings'])
                    : null,
            ];

            $this->assertLessThan(
                2_000,
                $results[$name]['p95_ms'],
                "SC-002 failed for {$name}: p95 must stay below two seconds.",
            );
            $this->assertLessThanOrEqual(
                $scenario['max_queries'],
                $results[$name]['max_queries'],
                "SC-006 failed for {$name}: query count is not bounded.",
            );
        }

        fwrite(STDERR, "\nADMIN_MANAGEMENT_PERFORMANCE=".json_encode($results, JSON_PRETTY_PRINT)."\n");
    }

    /** @return array{User, list<int>} */
    private function seedReferenceDataset(): array
    {
        $actor = User::factory()->withRole(UserRole::SuperAdmin)->create([
            'email' => 'performance-admin@example.test',
        ]);
        $staff = User::factory()->staff()->count(10)->create();
        $now = now()->startOfSecond();
        $password = Hash::make('password');

        for ($offset = 0; $offset < 10_000; $offset += 500) {
            $userRows = [];

            for ($number = $offset + 1; $number <= $offset + 500; $number++) {
                $userRows[] = [
                    'name' => sprintf('Performance User %05d', $number),
                    'email' => sprintf('performance-user-%05d@example.test', $number),
                    'email_verified_at' => $now,
                    'password' => $password,
                    'role' => UserRole::Citizen->value,
                    'citizen_id' => sprintf('PERF-CIT-%05d', $number),
                    'date_of_birth' => null,
                    'gender' => null,
                    'phone' => null,
                    'address' => null,
                    'email_notifications_enabled' => true,
                    'is_active' => $number % 10 !== 0,
                    'remember_token' => null,
                    'created_at' => $now->copy()->subSeconds($number),
                    'updated_at' => $now,
                    'deleted_at' => null,
                ];
            }

            DB::table('users')->insert($userRows);
        }

        $citizenIds = DB::table('users')
            ->where('email', 'like', 'performance-user-%@example.test')
            ->orderBy('id')
            ->pluck('id')
            ->all();

        $category = ServiceCategory::factory()->create([
            'name' => 'Performance Category',
            'code' => 'PERF-CATEGORY',
        ]);
        $departments = collect(range(1, 4))->map(fn (int $number): Department => Department::factory()->create([
            'name' => "Performance Department {$number}",
            'code' => "PERF-DEPT-{$number}",
        ]));
        $services = $departments->map(fn (Department $department, int $index): ServiceType => ServiceType::factory()->create([
            'category_id' => $category->id,
            'responsible_department_id' => $department->id,
            'name' => 'Performance Service '.($index + 1),
            'code' => 'PERF-SERVICE-'.($index + 1),
            'processing_time_days' => $index + 1,
        ]));
        $serviceIds = $services->pluck('id')->all();
        $staffIds = $staff->modelKeys();
        $statuses = array_column(ApplicationStatus::cases(), 'value');

        for ($offset = 0; $offset < 10_000; $offset += 500) {
            $applicationRows = [];

            for ($number = $offset + 1; $number <= $offset + 500; $number++) {
                $status = $statuses[($number - 1) % count($statuses)];
                $terminal = in_array($status, ApplicationStatus::completedValues(), true);
                $applicationRows[] = [
                    'application_code' => sprintf('PERF-APP-%05d', $number),
                    'citizen_id' => $citizenIds[$number - 1],
                    'service_type_id' => $serviceIds[($number - 1) % count($serviceIds)],
                    'assigned_staff_id' => $staffIds[($number - 1) % count($staffIds)],
                    'status' => $status,
                    'form_data' => json_encode(['reference' => $number], JSON_THROW_ON_ERROR),
                    'submitted_at' => $now->copy()->subDays($number % 45),
                    'processing_started_at' => $status === ApplicationStatus::Received->value ? null : $now,
                    'completed_at' => $terminal ? $now : null,
                    'result_note' => $status === ApplicationStatus::Approved->value ? 'Approved benchmark result' : null,
                    'rejection_reason' => $status === ApplicationStatus::Rejected->value ? 'Rejected benchmark result' : null,
                    'created_at' => $now->copy()->subSeconds($number),
                    'updated_at' => $now,
                    'deleted_at' => null,
                ];
            }

            DB::table('applications')->insert($applicationRows);
        }

        return [$actor, $serviceIds];
    }

    /** @param list<float> $samples */
    private function percentile95(array $samples): float
    {
        sort($samples);

        return $samples[(int) ceil(count($samples) * 0.95) - 1];
    }

    /**
     * @param  list<mixed>  $bindings
     * @return array<string, mixed>
     */
    private function explainSummary(string $sql, array $bindings): array
    {
        $rows = DB::select('EXPLAIN (ANALYZE, BUFFERS, FORMAT JSON) '.$sql, $bindings);
        $payload = json_decode($rows[0]->{'QUERY PLAN'}, true, flags: JSON_THROW_ON_ERROR);
        $plan = $payload[0]['Plan'];

        return [
            'node' => $plan['Node Type'],
            'actual_total_ms' => round((float) $plan['Actual Total Time'], 2),
            'actual_rows' => $plan['Actual Rows'],
            'planned_rows' => $plan['Plan Rows'],
            'shared_hit_blocks' => $plan['Shared Hit Blocks'] ?? 0,
            'shared_read_blocks' => $plan['Shared Read Blocks'] ?? 0,
        ];
    }
}
