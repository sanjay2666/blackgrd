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

final class ApplyReviewedSalesChallanMigrationsCommand extends Command
{
    private const DATABASE = 'blackgrd';

    /** @var array<string, string> */
    private const MIGRATIONS = [
        '2026_08_14_000003_create_sales_challan_dispatch_tables' => 'b911fd5716cb2fd83b42cb4bfa21744da04f184fa056448d7ac3a3b7c6032a8e',
        '2026_08_14_000004_add_sales_challan_number_series_and_permissions' => '18fb193835e21b8f6b7921eb6bf7bd3be784af52ba345801b5acb63e82751269',
    ];

    protected $signature = 'db:apply-reviewed-sales-challan
        {--execute : Apply only the reviewed Sales Challan migrations}
        {--confirm-database= : Must exactly equal blackgrd}
        {--backup-manifest= : JSON manifest for verified backups}
        {--writes-stopped : Confirm queue and scheduler writes were independently verified stopped}';

    protected $description = 'Apply hash-pinned Sales Challan migrations to blackgrd';

    public function handle(DatabaseSafetyGuard $guard, Migrator $migrator): int
    {
        try {
            $snapshot = $guard->inspect();
            if ($snapshot->driver !== 'mysql') {
                throw new RuntimeException('Reviewed Sales Challan execution requires the MySQL driver.');
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
                throw new RuntimeException('One or more reviewed Sales Challan migrations are already recorded as applied.');
            }
            foreach (['sales_challans', 'sales_challan_items', 'sales_challan_roll_allocations'] as $table) {
                if (Schema::hasTable($table)) {
                    throw new RuntimeException("Sales Challan table [{$table}] already exists outside the reviewed migration ledger.");
                }
            }
            $this->verifyBackupManifest((string) $this->option('backup-manifest'));
            $before = $this->preservationSnapshot();
            if (! $this->option('execute')) {
                $this->info('READY: reviewed Sales Challan migration preflight passed; no migration was executed.');

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
            $this->info('PASS: reviewed Sales Challan migrations applied with preservation verification.');

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
                throw new RuntimeException("Reviewed Sales Challan migration hash mismatch for [{$migration}].");
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
        foreach (['companies', 'departments', 'individuals', 'sale_orders', 'sale_order_items', 'warehouse_item_stocks', 'warehouse_out_items', 'packaging_orders', 'packaging_order_items', 'packaging_roll_allocations'] as $table) {
            $ids = DB::table($table)->orderBy('id')->pluck('id')->map(fn ($id): string => (string) $id)->all();
            $snapshot[$table] = ['count' => count($ids), 'ids' => hash('sha256', implode(',', $ids))];
        }

        return $snapshot;
    }

    /** @param array<string, array{count: int, ids: string}> $before */
    private function verifyAppliedSchema(array $before): void
    {
        foreach ([
            'sales_challans' => ['company_id', 'department_id', 'customer_id', 'financial_year_id', 'challan_number', 'status', 'customer_name', 'billing_address', 'shipping_address', 'transporter_id', 'lr_number', 'vehicle_number', 'total_meter', 'submission_key'],
            'sales_challan_items' => ['company_id', 'sales_challan_id', 'packaging_order_id', 'packaging_order_item_id', 'sale_order_id', 'sale_order_item_id', 'item_name', 'final_dispatch_width', 'tube_width'],
            'sales_challan_roll_allocations' => ['company_id', 'sales_challan_id', 'sales_challan_item_id', 'packaging_roll_allocation_id', 'warehouse_item_stock_id', 'dyeing_lot_number', 'packet_number', 'insp_taka_number', 'dispatched_quantity'],
        ] as $table => $columns) {
            if (! Schema::hasTable($table) || DB::table($table)->exists() || ! Schema::hasColumns($table, $columns)) {
                throw new RuntimeException("Sales Challan table [{$table}] is missing, non-empty, or incomplete.");
            }
        }
        if (DB::table('migrations')->whereIn('migration', array_keys(self::MIGRATIONS))->count() !== count(self::MIGRATIONS) || $before !== $this->preservationSnapshot()) {
            throw new RuntimeException('Sales Challan migration ledger or business-data preservation check failed.');
        }
        if (! DB::table('number_series')->where('series_key', 'sales-challan')->whereNull('financial_year_id')->where('status', 'Active')->exists()) {
            throw new RuntimeException('Sales Challan number series was not created.');
        }
        if (DB::table('permissions')->whereIn('permission_key', ['sales-challans.view', 'sales-challans.create', 'sales-challans.dispatch', 'sales-challans.cancel', 'sales-challans.print'])->count() !== 5) {
            throw new RuntimeException('Sales Challan permissions were not created.');
        }
    }
}
