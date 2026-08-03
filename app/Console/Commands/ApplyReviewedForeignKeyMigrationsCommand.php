<?php

namespace App\Console\Commands;

use App\DatabaseSafety\DatabaseSafetyGuard;
use Illuminate\Console\Command;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use RuntimeException;
use Throwable;

class ApplyReviewedForeignKeyMigrationsCommand extends Command
{
    protected $signature = 'db:apply-reviewed-foreign-keys
        {--execute : Apply the reviewed migrations after all checks pass}
        {--confirm-database= : Must exactly match the reviewed live database}';

    protected $description = 'Apply only the hash-pinned first critical foreign-key migrations';

    private const REVIEWED_DATABASE = 'blackgrd';

    private const REVIEWED_MIGRATIONS = [
        '2026_08_03_000001_add_parent_foreign_key_to_work_orders_table' => [
            'file' => '2026_08_03_000001_add_parent_foreign_key_to_work_orders_table.php',
            'sha256' => '13e5b7390afa6107ae53f998ab4145a9f3bc1b56270632666367751fca9a3602',
        ],
        '2026_08_03_000002_add_critical_foreign_keys_to_warehouse_tables' => [
            'file' => '2026_08_03_000002_add_critical_foreign_keys_to_warehouse_tables.php',
            'sha256' => '339366d0831484edb9e2f16c9ea1a9aeb86fd9f7ef16a26d4a93ee28df2b3adc',
        ],
    ];

    public function handle(DatabaseSafetyGuard $guard, Migrator $migrator): int
    {
        try {
            $snapshot = $guard->inspect();
            $this->assertReviewedConnection($snapshot);
            $this->assertApplicationIsDown();

            $paths = $this->verifyReviewedFiles();
            $this->assertOnlyReviewedMigrationsArePending();
            $this->assertLivePreconditions();
        } catch (Throwable $exception) {
            $this->error('BLOCKED: '.$exception->getMessage());

            return self::FAILURE;
        }

        $this->table(['Check', 'Effective value'], [
            ['Environment', $snapshot->environment],
            ['Connection', $snapshot->connectionName],
            ['Configured database', $snapshot->configuredDatabase],
            ['Connected database', $snapshot->connectedDatabase],
            ['Maintenance mode', 'ACTIVE'],
            ['Reviewed migrations', implode(', ', array_keys(self::REVIEWED_MIGRATIONS))],
        ]);

        if (! $this->option('execute')) {
            $this->info('READY: reviewed live-migration preflight passed; no migration was executed.');

            return self::SUCCESS;
        }

        if (! hash_equals(self::REVIEWED_DATABASE, (string) $this->option('confirm-database'))) {
            $this->error('BLOCKED: --execute requires --confirm-database='.self::REVIEWED_DATABASE.'.');

            return self::FAILURE;
        }

        $auditPath = storage_path('logs/reviewed-foreign-key-migration-'.now()->format('Ymd_His').'.json');
        $audit = [
            'command' => $this->getName(),
            'database' => $snapshot->connectedDatabase,
            'connection' => $snapshot->connectionName,
            'environment' => $snapshot->environment,
            'migrations' => self::REVIEWED_MIGRATIONS,
            'authorized_at' => now()->toIso8601String(),
            'authorization_scope' => 'process-local exact-path execution',
            'status' => 'authorized',
        ];

        try {
            $this->writeAudit($auditPath, $audit);
            $migrator->setOutput($this->output);
            $migrator->run($paths, ['step' => true]);
            $this->assertReviewedMigrationsRan();

            $audit['status'] = 'succeeded';
            $audit['completed_at'] = now()->toIso8601String();
            $this->info('Reviewed foreign-key migrations completed successfully.');
        } catch (Throwable $exception) {
            $audit['status'] = 'failed';
            $audit['failed_at'] = now()->toIso8601String();
            $audit['error'] = [
                'class' => $exception::class,
                'message' => $exception->getMessage(),
            ];
            $this->error('FAILED: '.$exception->getMessage());
        } finally {
            $audit['authorization_revoked_at'] = now()->toIso8601String();

            try {
                $this->writeAudit($auditPath, $audit);
                $this->line('Audit log: '.$auditPath);
            } catch (Throwable $auditException) {
                $this->error('Audit log finalization failed: '.$auditException->getMessage());

                return self::FAILURE;
            }
        }

        return $audit['status'] === 'succeeded' ? self::SUCCESS : self::FAILURE;
    }

    private function assertReviewedConnection(object $snapshot): void
    {
        if ($snapshot->driver !== 'mysql') {
            throw new RuntimeException('Reviewed execution requires the MySQL driver.');
        }

        foreach (['declaredDatabase', 'configuredDatabase', 'connectedDatabase'] as $field) {
            if ($snapshot->{$field} !== self::REVIEWED_DATABASE) {
                throw new RuntimeException("{$field} must exactly equal [".self::REVIEWED_DATABASE.'].');
            }
        }

        if ($snapshot->connectionError !== null) {
            throw new RuntimeException('The reviewed live connection is unavailable.');
        }
    }

    private function assertApplicationIsDown(): void
    {
        if (! app()->isDownForMaintenance()) {
            throw new RuntimeException('Application maintenance mode must be active.');
        }
    }

    /**
     * @return list<string>
     */
    private function verifyReviewedFiles(): array
    {
        $paths = [];

        foreach (self::REVIEWED_MIGRATIONS as $migration) {
            $path = database_path('migrations/'.$migration['file']);

            if (! File::isFile($path)) {
                throw new RuntimeException("Reviewed migration file is missing: {$path}");
            }

            $actualHash = hash_file('sha256', $path);

            if ($actualHash === false || ! hash_equals($migration['sha256'], strtolower($actualHash))) {
                throw new RuntimeException("Reviewed migration hash mismatch: {$migration['file']}");
            }

            $paths[] = $path;
        }

        return $paths;
    }

    private function assertOnlyReviewedMigrationsArePending(): void
    {
        $ran = DB::table('migrations')->pluck('migration')->all();
        $repositoryMigrations = collect(File::glob(database_path('migrations/*.php')))
            ->map(fn (string $path): string => pathinfo($path, PATHINFO_FILENAME))
            ->all();
        $pending = array_values(array_diff($repositoryMigrations, $ran));
        $expected = array_keys(self::REVIEWED_MIGRATIONS);
        sort($pending);
        sort($expected);

        if ($pending !== $expected) {
            throw new RuntimeException(
                'Pending migration set does not exactly match the reviewed set. Found: '.implode(', ', $pending)
            );
        }
    }

    private function assertLivePreconditions(): void
    {
        $engines = DB::table('information_schema.TABLES')
            ->where('TABLE_SCHEMA', self::REVIEWED_DATABASE)
            ->whereIn('TABLE_NAME', [
                'work_orders',
                'warehouses',
                'warehouse_compartments',
                'warehouse_in_items',
                'warehouse_item_stocks',
            ])
            ->pluck('ENGINE', 'TABLE_NAME');

        if ($engines->count() !== 5 || $engines->contains(fn (string $engine): bool => $engine !== 'InnoDB')) {
            throw new RuntimeException('Every reviewed parent and child table must use InnoDB.');
        }

        $expectedColumns = [
            'work_orders.id' => ['int(10) unsigned', 'NO'],
            'work_orders.parent_work_order_id' => ['int(10) unsigned', 'YES'],
            'warehouses.id' => ['bigint(20) unsigned', 'NO'],
            'warehouse_compartments.warehouse_id' => ['bigint(20) unsigned', 'NO'],
            'warehouse_in_items.id' => ['bigint(20)', 'NO'],
            'warehouse_item_stocks.warehouse_item_id' => ['bigint(20)', 'YES'],
        ];
        $columns = DB::table('information_schema.COLUMNS')
            ->where('TABLE_SCHEMA', self::REVIEWED_DATABASE)
            ->whereIn('TABLE_NAME', [
                'work_orders',
                'warehouses',
                'warehouse_compartments',
                'warehouse_in_items',
                'warehouse_item_stocks',
            ])
            ->get(['TABLE_NAME', 'COLUMN_NAME', 'COLUMN_TYPE', 'IS_NULLABLE'])
            ->keyBy(fn (object $column): string => $column->TABLE_NAME.'.'.$column->COLUMN_NAME);

        foreach ($expectedColumns as $key => [$type, $nullable]) {
            $column = $columns->get($key);

            if ($column === null || $column->COLUMN_TYPE !== $type || $column->IS_NULLABLE !== $nullable) {
                throw new RuntimeException("Reviewed column contract mismatch: {$key}");
            }
        }

        $existingConstraints = DB::table('information_schema.REFERENTIAL_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', self::REVIEWED_DATABASE)
            ->whereIn('CONSTRAINT_NAME', ['fk_wo_parent', 'fk_wc_warehouse', 'fk_wis_inward'])
            ->count();

        if ($existingConstraints !== 0) {
            throw new RuntimeException('One or more reviewed constraint names already exist.');
        }

        $prechecks = [
            'work_orders.parent_work_order_id' => <<<'SQL'
                SELECT
                    COALESCE(SUM(c.parent_work_order_id = 0), 0) AS zeros,
                    COALESCE(SUM(c.parent_work_order_id IS NOT NULL AND p.id IS NULL), 0) AS orphans
                FROM work_orders c
                LEFT JOIN work_orders p ON p.id = c.parent_work_order_id
                SQL,
            'warehouse_compartments.warehouse_id' => <<<'SQL'
                SELECT
                    COALESCE(SUM(c.warehouse_id = 0), 0) AS zeros,
                    COALESCE(SUM(p.id IS NULL), 0) AS orphans
                FROM warehouse_compartments c
                LEFT JOIN warehouses p ON p.id = c.warehouse_id
                SQL,
            'warehouse_item_stocks.warehouse_item_id' => <<<'SQL'
                SELECT
                    COALESCE(SUM(c.warehouse_item_id = 0), 0) AS zeros,
                    COALESCE(SUM(c.warehouse_item_id IS NOT NULL AND p.id IS NULL), 0) AS orphans
                FROM warehouse_item_stocks c
                LEFT JOIN warehouse_in_items p ON p.id = c.warehouse_item_id
                SQL,
        ];

        foreach ($prechecks as $relationship => $query) {
            $result = DB::selectOne($query);

            if ((int) $result->zeros !== 0 || (int) $result->orphans !== 0) {
                throw new RuntimeException("Orphan/sentinel precheck failed: {$relationship}");
            }
        }

        $parentIndexes = [
            ['work_orders', 'id'],
            ['warehouses', 'id'],
            ['warehouse_in_items', 'id'],
        ];

        foreach ($parentIndexes as [$table, $column]) {
            $indexed = DB::table('information_schema.STATISTICS')
                ->where('TABLE_SCHEMA', self::REVIEWED_DATABASE)
                ->where('TABLE_NAME', $table)
                ->where('COLUMN_NAME', $column)
                ->where('SEQ_IN_INDEX', 1)
                ->where('NON_UNIQUE', 0)
                ->exists();

            if (! $indexed) {
                throw new RuntimeException("Reviewed parent key is not uniquely indexed: {$table}.{$column}");
            }
        }
    }

    private function assertReviewedMigrationsRan(): void
    {
        $ran = DB::table('migrations')
            ->whereIn('migration', array_keys(self::REVIEWED_MIGRATIONS))
            ->pluck('migration')
            ->sort()
            ->values()
            ->all();
        $expected = array_keys(self::REVIEWED_MIGRATIONS);
        sort($expected);

        if ($ran !== $expected) {
            throw new RuntimeException('Reviewed migration records were not written exactly as expected.');
        }
    }

    private function writeAudit(string $path, array $audit): void
    {
        $json = json_encode($audit, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        if (File::put($path, $json.PHP_EOL, true) === false) {
            throw new RuntimeException("Unable to write reviewed migration audit log: {$path}");
        }
    }
}
