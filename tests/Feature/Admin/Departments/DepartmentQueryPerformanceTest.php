<?php

namespace Tests\Feature\Admin\Departments;

use App\Enums\UserRole;
use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[Group('department-performance')]
class DepartmentQueryPerformanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_sc_004_list_search_and_detail_queries_at_reference_scale(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            $this->markTestSkipped('SC-004 benchmark requires PostgreSQL.');
        }

        $actor = User::factory()->withRole(UserRole::SuperAdmin)->create();
        $staffIds = User::factory()->staff()->count(10)->create()->modelKeys();
        $now = now();

        $departmentRows = collect(range(1, 1000))->map(fn (int $number): array => [
            'name' => sprintf('Benchmark Department %04d', $number),
            'code' => sprintf('BENCH-%04d', $number),
            'address' => 'Benchmark campus',
            'leader_id' => null,
            'lock_version' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        foreach ($departmentRows->chunk(250) as $chunk) {
            Department::query()->insert($chunk->all());
        }

        $departmentIds = Department::query()->orderBy('id')->pluck('id');
        $membershipRows = $departmentIds->flatMap(fn (int $departmentId) => collect($staffIds)
            ->map(fn (int $staffId): array => [
                'department_id' => $departmentId,
                'user_id' => $staffId,
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        foreach ($membershipRows->chunk(1000) as $chunk) {
            DB::table('department_user')->insert($chunk->all());
        }

        $queryCount = 0;
        DB::listen(static function () use (&$queryCount): void {
            $queryCount++;
        });

        $listStartedAt = hrtime(true);
        $this->actingAs($actor)
            ->get(route('admin.departments.index', ['search' => 'Benchmark', 'status' => 'all']))
            ->assertOk();
        $listMilliseconds = (hrtime(true) - $listStartedAt) / 1_000_000;
        $listQueryCount = $queryCount;

        $queryCount = 0;
        $detailStartedAt = hrtime(true);
        $this->actingAs($actor)
            ->get(route('admin.departments.show', $departmentIds->first()))
            ->assertOk();
        $detailMilliseconds = (hrtime(true) - $detailStartedAt) / 1_000_000;

        $this->assertLessThanOrEqual(10, $listQueryCount, 'Department list must not introduce N+1 queries.');
        $this->assertLessThanOrEqual(8, $queryCount, 'Department detail must use bounded eager-loaded queries.');

        fwrite(STDERR, sprintf(
            "\nSC-004: 1,000 departments / 10,000 memberships; list %.2f ms (%d queries); detail %.2f ms (%d queries).\n",
            $listMilliseconds,
            $listQueryCount,
            $detailMilliseconds,
            $queryCount,
        ));
    }
}
