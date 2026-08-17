<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const CODE_CONSTRAINT = 'departments_code_canonical_check';

    public function up(): void
    {
        $this->ensurePostgreSql();
        $this->ensureCodesCanBeCanonicalized();

        DB::statement('UPDATE departments SET code = UPPER(BTRIM(code))');

        Schema::table('departments', function (Blueprint $table): void {
            $table->unsignedInteger('lock_version')->default(0);
            $table->index('leader_id', 'departments_leader_id_index');
        });

        Schema::table('department_user', function (Blueprint $table): void {
            $table->index('user_id', 'department_user_user_id_index');
        });

        DB::statement(<<<'SQL'
            ALTER TABLE departments
            ADD CONSTRAINT departments_code_canonical_check
            CHECK (
                code = UPPER(BTRIM(code))
                AND CHAR_LENGTH(code) BETWEEN 2 AND 50
                AND code ~ '^[A-Z0-9]+([-_][A-Z0-9]+)*$'
            )
        SQL);
    }

    public function down(): void
    {
        $this->ensurePostgreSql();

        DB::statement(sprintf(
            'ALTER TABLE departments DROP CONSTRAINT IF EXISTS %s',
            self::CODE_CONSTRAINT,
        ));

        Schema::table('department_user', function (Blueprint $table): void {
            $table->dropIndex('department_user_user_id_index');
        });

        Schema::table('departments', function (Blueprint $table): void {
            $table->dropIndex('departments_leader_id_index');
            $table->dropColumn('lock_version');
        });
    }

    private function ensurePostgreSql(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            throw new RuntimeException('F03 Department migration requires PostgreSQL.');
        }
    }

    private function ensureCodesCanBeCanonicalized(): void
    {
        $collisions = DB::table('departments')
            ->selectRaw('UPPER(BTRIM(code)) AS canonical_code, COUNT(*) AS occurrences')
            ->groupByRaw('UPPER(BTRIM(code))')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('canonical_code');

        if ($collisions->isNotEmpty()) {
            throw new RuntimeException(sprintf(
                'Department code canonicalization has collisions: %s. Resolve them before migrating.',
                $collisions->implode(', '),
            ));
        }

        $invalidCodes = DB::table('departments')
            ->whereRaw("CHAR_LENGTH(UPPER(BTRIM(code))) NOT BETWEEN 2 AND 50 OR UPPER(BTRIM(code)) !~ '^[A-Z0-9]+([-_][A-Z0-9]+)*$'")
            ->limit(5)
            ->pluck('code');

        if ($invalidCodes->isNotEmpty()) {
            throw new RuntimeException(sprintf(
                'Invalid Department codes must be corrected before migrating: %s.',
                $invalidCodes->implode(', '),
            ));
        }
    }
};
