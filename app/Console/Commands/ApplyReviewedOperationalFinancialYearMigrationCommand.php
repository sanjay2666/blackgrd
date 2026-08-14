<?php

namespace App\Console\Commands;

use App\DatabaseSafety\DatabaseSafetyGuard;
use Illuminate\Console\Command;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Throwable;

final class ApplyReviewedOperationalFinancialYearMigrationCommand extends Command
{
    private const DATABASE = 'blackgrd';

    private const MIGRATION = '2026_08_14_000005_add_financial_year_to_new_operational_transactions';

    private const HASH = 'c1e566bdb2302f1ed3971395e21114efc25e72da98f9050ac585d8087925305b';

    private const TABLES = [
        'packaging_orders',
        'packaging_order_items',
        'packaging_roll_allocations',
        'sales_challan_items',
        'sales_challan_roll_allocations',
        'production_genealogy_links',
    ];

    protected $signature = 'db:apply-reviewed-operational-financial-year
        {--execute : Apply only the reviewed operational Financial Year migration}
        {--confirm-database= : Must exactly equal blackgrd}
        {--backup-manifest= : JSON manifest for verified backups}
        {--writes-stopped : Confirm queue and scheduler writes were independently verified stopped}';

    protected $description = 'Apply the hash-pinned operational Financial Year migration to blackgrd';

    public function handle(DatabaseSafetyGuard $guard, Migrator $migrator): int
    {
        try {
            $snapshot = $guard->inspect();
            if ($snapshot->driver !== 'mysql') {
                throw new RuntimeException('Reviewed operational Financial Year execution requires the MySQL driver.');
            }
            foreach (['configuredDatabase', 'declaredDatabase', 'connectedDatabase'] as $field) {
                if ($snapshot->{$field} !== self::DATABASE) {
                    throw new RuntimeException("{$field} must exactly equal [".self::DATABASE.'].');
                }
            }
            if (! app()->isDownForMaintenance() || ! $this->option('writes-stopped')) {
                throw new RuntimeException('Maintenance mode and --writes-stopped are required.');
            }

            $path = database_path('migrations/'.self::MIGRATION.'.php');
            if (! File::isFile($path) || ! hash_equals(self::HASH, (string) hash_file('sha256', $path))) {
                throw new RuntimeException('Reviewed operational Financial Year migration hash mismatch.');
            }
            if (DB::table('migrations')->where('migration', self::MIGRATION)->exists()) {
                throw new RuntimeException('The reviewed operational Financial Year migration is already recorded as applied.');
            }
            foreach (self::TABLES as $table) {
                if (! Schema::hasTable($table) || Schema::hasColumn($table, 'financial_year_id')) {
                    throw new RuntimeException("Operational Financial Year target [{$table}] is missing or already changed outside the reviewed ledger.");
                }
            }
            $this->verifyBackupManifest((string) $this->option('backup-manifest'));

            $files = collect(File::glob(database_path('migrations/*.php')))->map(fn (string $file): string => pathinfo($file, PATHINFO_FILENAME))->all();
            $pending = array_values(array_diff($files, DB::table('migrations')->pluck('migration')->all()));
            if (! in_array(self::MIGRATION, $pending, true)) {
                throw new RuntimeException('The reviewed operational Financial Year migration is not pending.');
            }
            if (array_filter($pending, fn (string $migration): bool => $migration > self::MIGRATION) !== []) {
                throw new RuntimeException('A newer pending migration exists; reviewed operational Financial Year execution is blocked.');
            }
            $before = $this->preservationSnapshot();
            if (! $this->option('execute')) {
                $this->info('READY: reviewed operational Financial Year migration preflight passed; no migration was executed.');

                return self::SUCCESS;
            }
            if ((string) $this->option('confirm-database') !== self::DATABASE) {
                throw new RuntimeException('--execute requires --confirm-database=blackgrd.');
            }

            $guard->authorizeReviewedLiveMigration(self::DATABASE);
            try {
                $migrator->setOutput($this->output);
                $migrator->run([$path], ['step' => true]);
                $this->verifyAppliedSchema($before);
            } finally {
                $guard->revokeDestructiveAuthorization();
            }
            $this->info('PASS: reviewed operational Financial Year migration applied with preservation verification.');

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
        $contents = (string) File::get($path);
        $manifest = json_decode(str_starts_with($contents, "\xEF\xBB\xBF") ? substr($contents, 3) : $contents, true, flags: JSON_THROW_ON_ERROR);
        if (($manifest['database'] ?? null) !== self::DATABASE || empty($manifest['pitr_coordinates']) || empty($manifest['recovery_verified_at'])) {
            throw new RuntimeException('Backup manifest must identify blackgrd and include verified PITR coordinates and recovery verification.');
        }
        $kinds = [];
        foreach (($manifest['backups'] ?? []) as $backup) {
            $file = (string) ($backup['path'] ?? '');
            if (! in_array($backup['kind'] ?? null, ['full', 'affected_tables', 'migrations_table'], true)
                || ! File::isFile($file)
                || File::size($file) !== (int) ($backup['size'] ?? -1)
                || ! hash_equals(strtolower((string) ($backup['sha256'] ?? '')), strtolower((string) hash_file('sha256', $file)))) {
                throw new RuntimeException('Backup checksum, size, or manifest entry is invalid.');
            }
            $kinds[] = $backup['kind'];
        }
        sort($kinds);
        if ($kinds !== ['affected_tables', 'full', 'migrations_table']) {
            throw new RuntimeException('Backup manifest must contain full, affected_tables, and migrations_table backups.');
        }
    }

    /** @return array<string, array{count: int, ids: string}> */
    private function preservationSnapshot(): array
    {
        $snapshot = [];
        foreach (array_merge(['companies', 'sale_orders', 'sale_order_items', 'warehouse_item_stocks', 'warehouse_out_items', 'warehouse_balance_items', 'sales_challans'], self::TABLES) as $table) {
            $ids = DB::table($table)->orderBy('id')->pluck('id')->map(fn ($id): string => (string) $id)->all();
            $snapshot[$table] = ['count' => count($ids), 'ids' => hash('sha256', implode(',', $ids))];
        }

        return $snapshot;
    }

    /** @param array<string, array{count: int, ids: string}> $before */
    private function verifyAppliedSchema(array $before): void
    {
        foreach (self::TABLES as $table) {
            if (! Schema::hasColumn($table, 'financial_year_id')) {
                throw new RuntimeException("Operational Financial Year target [{$table}] is missing financial_year_id.");
            }
        }
        if (! DB::table('migrations')->where('migration', self::MIGRATION)->exists() || $before !== $this->preservationSnapshot()) {
            throw new RuntimeException('Operational Financial Year migration ledger or business-data preservation check failed.');
        }
    }
}
