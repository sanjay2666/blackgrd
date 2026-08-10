<?php

namespace App\Console\Commands;

use App\DatabaseSafety\DatabaseSafetyGuard;
use Illuminate\Console\Command;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use RuntimeException;
use Throwable;

final class ApplyReviewedNumberSeriesMigrationCommand extends Command
{
    private const DATABASE = 'blackgrd';

    private const MIGRATIONS = [
        '2026_08_12_000001_create_number_series_table',
        '2026_08_12_000002_seed_number_series',
    ];

    private const HASHES = [
        '2026_08_12_000001_create_number_series_table' => 'dec4e03cf8294a85970d9eed05bef0b3de4d30df31341834497fe9c088ccae2b',
        '2026_08_12_000002_seed_number_series' => 'ce3c13f9335e1257cb1f55e1efa534d517f77ea7596855f276cbd4da651ec8a5',
    ];

    protected $signature = 'db:apply-reviewed-number-series
        {--execute : Apply only the reviewed Task 1.9 migrations}
        {--confirm-database= : Must exactly equal blackgrd}
        {--backup-manifest= : JSON manifest for verified backups}
        {--writes-stopped : Confirm queue and scheduler writes were independently verified stopped}';

    protected $description = 'Apply hash-pinned Task 1.9 number-series migrations to blackgrd';

    public function handle(DatabaseSafetyGuard $guard, Migrator $migrator): int
    {
        try {
            $snapshot = $guard->inspect();
            foreach (['configuredDatabase', 'declaredDatabase', 'connectedDatabase'] as $field) {
                if ($snapshot->{$field} !== self::DATABASE) {
                    throw new RuntimeException("{$field} must exactly equal [".self::DATABASE.'].');
                }
            }
            if (! app()->isDownForMaintenance() || ! $this->option('writes-stopped')) {
                throw new RuntimeException('Maintenance mode and --writes-stopped are required.');
            }

            $paths = [];
            foreach (self::MIGRATIONS as $migration) {
                $path = database_path('migrations/'.$migration.'.php');
                if (! File::isFile($path) || ! hash_equals(self::HASHES[$migration], (string) hash_file('sha256', $path))) {
                    throw new RuntimeException("Reviewed migration hash mismatch: {$migration}.");
                }
                $paths[] = $path;
            }
            $this->verifyBackupManifest((string) $this->option('backup-manifest'));

            $pending = array_values(array_diff(
                collect(File::glob(database_path('migrations/*.php')))->map(fn (string $file): string => pathinfo($file, PATHINFO_FILENAME))->all(),
                DB::table('migrations')->pluck('migration')->all(),
            ));
            if ($pending !== self::MIGRATIONS) {
                throw new RuntimeException('Pending migration set does not contain exactly the reviewed Task 1.9 migrations.');
            }

            $before = $this->preservationSnapshot();
            $expected = $this->expectedNextCounters();
            if (! $this->option('execute')) {
                $this->table(['Series', 'Expected next'], $expected);
                $this->info('READY: reviewed Task 1.9 live preflight passed; no migration executed.');

                return self::SUCCESS;
            }
            if ((string) $this->option('confirm-database') !== self::DATABASE) {
                throw new RuntimeException('--execute requires --confirm-database=blackgrd.');
            }

            $migrator->setOutput($this->output);
            $migrator->run($paths, ['step' => true]);
            if ($before !== $this->preservationSnapshot()) {
                throw new RuntimeException('Historical-number or protected-data preservation check failed.');
            }
            $this->verifyCounters($expected);
            if (DB::table('migrations')->whereIn('migration', self::MIGRATIONS)->count() !== count(self::MIGRATIONS)) {
                throw new RuntimeException('Task 1.9 migrations were not recorded in the migration ledger.');
            }
            $this->info('PASS: reviewed Task 1.9 migrations, bootstrap, and preservation verification succeeded.');

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error('BLOCKED/FAILED: '.$exception->getMessage());

            return self::FAILURE;
        }
    }

    private function verifyBackupManifest(string $path): void
    {
        if ($path === '' || ! File::isFile($path)) {
            throw new RuntimeException('A readable backup manifest is required.');
        }
        $manifest = json_decode((string) File::get($path), true, flags: JSON_THROW_ON_ERROR);
        if (($manifest['database'] ?? null) !== self::DATABASE) {
            throw new RuntimeException('Backup manifest database must equal blackgrd.');
        }
        $kinds = [];
        foreach (($manifest['backups'] ?? []) as $backup) {
            $file = (string) ($backup['path'] ?? '');
            if (! in_array($backup['kind'] ?? null, ['full', 'affected_tables', 'migrations_table'], true) || ! File::isFile($file) || File::size($file) !== (int) ($backup['size'] ?? -1) || ! hash_equals(strtolower((string) ($backup['sha256'] ?? '')), strtolower((string) hash_file('sha256', $file)))) {
                throw new RuntimeException('Backup checksum, size, or manifest entry is invalid.');
            }
            $kinds[] = $backup['kind'];
        }
        sort($kinds);
        if ($kinds !== ['affected_tables', 'full', 'migrations_table']) {
            throw new RuntimeException('Backup manifest must contain full, affected_tables, and migrations_table backups.');
        }
    }

    private function preservationSnapshot(): array
    {
        $tables = ['users', 'companies', 'roles', 'user_role_assignments', 'audit_logs', 'financial_years', 'sale_orders', 'purchase_orders', 'work_orders', 'work_process_requirements', 'stock_mill_dispatches'];

        return collect($tables)->mapWithKeys(function (string $table): array {
            $query = DB::table($table);
            $columns = match ($table) {
                'work_orders' => ['id', 'process_type_id', 'process_sl_no'],
                'work_process_requirements' => ['id', 'req_lot_no'],
                'stock_mill_dispatches' => ['id', 'voucher_number', 'chalan_no'],
                default => ['id'],
            };

            return [$table => ['count' => $query->count(), 'rows' => $query->orderBy('id')->get($columns)->map(fn ($row): array => (array) $row)->all()]];
        })->all();
    }

    /** @return list<array{0:string,1:int}> */
    private function expectedNextCounters(): array
    {
        $rows = [];
        foreach (range(1, 4) as $processId) {
            $workOrderMax = (int) (DB::table('work_orders')->where('process_type_id', $processId)->whereNotNull('process_sl_no')->max('process_sl_no') ?? 0);
            $processMax = (int) (DB::table('process_items')->where('id', $processId)->value('process_sl_no_last') ?? 0);
            $rows[] = ['work-order-'.$processId, max($workOrderMax, $processMax) + 1];
        }
        $wpr = DB::table('work_process_requirements')->whereNotNull('req_lot_no')->pluck('req_lot_no');
        $jobWork = DB::table('stock_mill_dispatches')->select('voucher_number', 'chalan_no')->get();
        $rows[] = ['wpr-lot', $this->maxNumeric($wpr) + 1];
        $rows[] = ['job-work-voucher', $this->maxNumeric($jobWork->pluck('voucher_number')) + 1];
        $rows[] = ['job-work-challan', $this->maxNumeric($jobWork->pluck('chalan_no')) + 1];

        return $rows;
    }

    private function verifyCounters(array $expected): void
    {
        foreach ($expected as [$key, $next]) {
            $actual = (int) DB::table('number_series')->where('series_key', $key)->whereNull('financial_year_id')->value('next_number');
            if ($actual !== $next) {
                throw new RuntimeException("Bootstrapped counter mismatch for {$key}: expected {$next}, found {$actual}.");
            }
        }
    }

    private function maxNumeric($values): int
    {
        $nonNumeric = $values->filter(fn ($value): bool => ! preg_match('/^[0-9]+$/', (string) $value));
        if ($nonNumeric->isNotEmpty()) {
            throw new RuntimeException('A migrated number series contains an unparseable legacy value.');
        }

        return (int) ($values->map(fn ($value): int => (int) $value)->max() ?? 0);
    }
}
