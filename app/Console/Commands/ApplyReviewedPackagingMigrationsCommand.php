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

final class ApplyReviewedPackagingMigrationsCommand extends Command
{
    private const DATABASE = 'blackgrd';

    /** @var array<string, string> */
    private const MIGRATIONS = [
        '2026_08_14_000001_create_packaging_allocation_tables' => '636c0f28cb1ee2d99766ea6415edb297ce683a09018870a32cd14cf6ed4ea2ba',
        '2026_08_14_000002_extend_packaging_orders_for_bulk_and_sample_carts' => '42bb29dbe909e9faf84b62c859cd6852a7853c67ab8889f3d287df74e99a0a13',
    ];

    protected $signature = 'db:apply-reviewed-packaging
        {--execute : Apply only the reviewed Packaging migrations}
        {--confirm-database= : Must exactly equal blackgrd}
        {--backup-manifest= : JSON manifest for verified backups}
        {--writes-stopped : Confirm queue and scheduler writes were independently verified stopped}';

    protected $description = 'Apply the hash-pinned Packaging migrations to blackgrd';

    public function handle(DatabaseSafetyGuard $guard, Migrator $migrator): int
    {
        try {
            $snapshot = $guard->inspect();
            if ($snapshot->driver !== 'mysql') {
                throw new RuntimeException('Reviewed Packaging execution requires the MySQL driver.');
            }
            foreach (['configuredDatabase', 'declaredDatabase', 'connectedDatabase'] as $field) {
                if ($snapshot->{$field} !== self::DATABASE) {
                    throw new RuntimeException("{$field} must exactly equal [".self::DATABASE.'].');
                }
            }
            if (! app()->isDownForMaintenance() || ! $this->option('writes-stopped')) {
                throw new RuntimeException('Maintenance mode and --writes-stopped are required.');
            }
            $paths = $this->verifiedPaths();
            if (DB::table('migrations')->whereIn('migration', array_keys(self::MIGRATIONS))->exists()) {
                throw new RuntimeException('One or more reviewed Packaging migrations are already recorded as applied.');
            }
            foreach (['packaging_orders', 'packaging_order_items', 'packaging_roll_allocations'] as $table) {
                if (Schema::hasTable($table)) {
                    throw new RuntimeException("Packaging table [{$table}] already exists outside the reviewed migration ledger.");
                }
            }
            $this->verifyBackupManifest((string) $this->option('backup-manifest'));
            $before = $this->preservationSnapshot();
            if (! $this->option('execute')) {
                $this->info('READY: reviewed Packaging migration preflight passed; no migration was executed.');

                return self::SUCCESS;
            }
            if ((string) $this->option('confirm-database') !== self::DATABASE) {
                throw new RuntimeException('--execute requires --confirm-database=blackgrd.');
            }

            $guard->authorizeReviewedLiveMigration(self::DATABASE);
            try {
                $migrator->setOutput($this->output);
                $migrator->run($paths, ['step' => true]);
                $this->verifyAppliedSchema($before);
            } finally {
                $guard->revokeDestructiveAuthorization();
            }
            $this->info('PASS: reviewed Packaging migrations applied with stock and sales data preservation verification.');

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error('BLOCKED/FAILED: '.$exception->getMessage());

            return self::FAILURE;
        }
    }

    /** @return list<string> */
    private function verifiedPaths(): array
    {
        $paths = [];
        foreach (self::MIGRATIONS as $migration => $hash) {
            $path = database_path("migrations/{$migration}.php");
            if ($hash === 'TO_BE_PINNED' || ! File::isFile($path) || ! hash_equals($hash, (string) hash_file('sha256', $path))) {
                throw new RuntimeException("Reviewed Packaging migration hash mismatch for [{$migration}].");
            }
            $paths[] = $path;
        }

        return $paths;
    }

    private function verifyBackupManifest(string $path): void
    {
        if ($path === '' || ! File::isFile($path)) {
            throw new RuntimeException('A readable backup manifest is required.');
        }
        $manifest = json_decode((string) File::get($path), true, flags: JSON_THROW_ON_ERROR);
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
        foreach (['companies', 'sale_orders', 'sale_order_items', 'warehouse_in_items', 'warehouse_item_stocks', 'warehouse_out_items', 'warehouse_balance_items', 'production_genealogy_links'] as $table) {
            $ids = DB::table($table)->orderBy('id')->pluck('id')->map(fn ($id): string => (string) $id)->all();
            $snapshot[$table] = ['count' => count($ids), 'ids' => hash('sha256', implode(',', $ids))];
        }

        return $snapshot;
    }

    /** @param array<string, array{count: int, ids: string}> $before */
    private function verifyAppliedSchema(array $before): void
    {
        foreach (['packaging_orders', 'packaging_order_items', 'packaging_roll_allocations'] as $table) {
            if (! Schema::hasTable($table) || DB::table($table)->exists()) {
                throw new RuntimeException("Packaging table [{$table}] is missing or was not created empty.");
            }
        }
        foreach ([
            'packaging_orders' => ['company_id', 'customer_id', 'packaging_mode', 'allocated_quantity', 'packed_quantity', 'dispatched_quantity', 'cancelled_quantity', 'returned_quantity', 'remaining_quantity'],
            'packaging_order_items' => ['packaging_order_id', 'sale_order_id', 'sale_order_item_id', 'dyeing_color', 'coating_type', 'final_dispatch_width', 'tube_width'],
            'packaging_roll_allocations' => ['packaging_order_item_id', 'warehouse_item_stock_id', 'warehouse_out_item_id', 'dyeing_lot_number', 'source_available_quantity'],
        ] as $table => $columns) {
            if (! Schema::hasColumns($table, $columns)) {
                throw new RuntimeException("Packaging table [{$table}] is missing a required reviewed column.");
            }
        }
        if (DB::table('migrations')->whereIn('migration', array_keys(self::MIGRATIONS))->count() !== count(self::MIGRATIONS) || $before !== $this->preservationSnapshot()) {
            throw new RuntimeException('Packaging migration ledger or business-data preservation check failed.');
        }
    }
}
