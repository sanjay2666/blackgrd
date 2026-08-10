<?php

namespace App\Console\Commands;

use App\DatabaseSafety\DatabaseSafetyGuard;
use Illuminate\Console\Command;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use RuntimeException;
use Throwable;

class ApplyReviewedRbacMigrationCommand extends Command
{
    private const DATABASE = 'blackgrd';

    private const MIGRATION = '2026_08_10_000003_create_rbac_tables';

    private const HASH = '91c6aa0849d68dafd58033ffef4a4e2a6c408ee0166a226225672b1310c4a9ad';

    protected $signature = 'db:apply-reviewed-rbac
        {--execute : Apply only the reviewed Task 1.7B migration}
        {--confirm-database= : Must exactly equal blackgrd}
        {--backup-manifest= : JSON manifest for verified backups}
        {--writes-stopped : Confirm queue and scheduler writes were independently verified stopped}';

    protected $description = 'Apply the hash-pinned Task 1.7B RBAC migration to blackgrd';

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
            $migrationPath = database_path('migrations/'.self::MIGRATION.'.php');
            if (! File::isFile($migrationPath) || ! hash_equals(self::HASH, (string) hash_file('sha256', $migrationPath))) {
                throw new RuntimeException('Reviewed RBAC migration hash mismatch.');
            }
            $this->verifyBackupManifest((string) $this->option('backup-manifest'));
            $files = collect(File::glob(database_path('migrations/*.php')))->map(fn (string $path) => pathinfo($path, PATHINFO_FILENAME))->all();
            $pending = array_values(array_diff($files, DB::table('migrations')->pluck('migration')->all()));
            if ($pending !== [self::MIGRATION]) {
                throw new RuntimeException('Pending migration set does not contain exactly the reviewed RBAC migration.');
            }
            $before = $this->preservationSnapshot();
            if (! $this->option('execute')) {
                $this->info('READY: reviewed RBAC live preflight passed; no migration was executed.');

                return self::SUCCESS;
            }
            if ((string) $this->option('confirm-database') !== self::DATABASE) {
                throw new RuntimeException('--execute requires --confirm-database=blackgrd.');
            }
            $migrator->setOutput($this->output);
            $migrator->run([$migrationPath], ['step' => true]);
            if (! DB::table('migrations')->where('migration', self::MIGRATION)->exists() || $before !== $this->preservationSnapshot()) {
                throw new RuntimeException('RBAC migration ledger or preservation check failed.');
            }
            $this->info('PASS: reviewed Task 1.7B RBAC migration and preservation verification succeeded.');

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
        return collect(['users', 'companies', 'user_organization_access', 'departments', 'warehouses', 'sale_orders', 'work_orders', 'work_process_requirements', 'work_inspections', 'gate_passes', 'warehouse_in_items', 'warehouse_out_items', 'warehouse_balance_items', 'warehouse_item_stocks', 'purchase_orders', 'purchases'])->mapWithKeys(fn (string $table) => [$table => DB::table($table)->count()])->all();
    }
}
