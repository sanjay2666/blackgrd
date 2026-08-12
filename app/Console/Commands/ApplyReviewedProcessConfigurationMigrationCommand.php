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

final class ApplyReviewedProcessConfigurationMigrationCommand extends Command
{
    private const DATABASE = 'blackgrd';

    private const MIGRATION = '2026_08_12_000014_create_process_configuration_tables';

    private const HASH = 'ae3872b37067fac9700d707a1b402bdf4625b5e5816e2b8689590b3831675357';

    protected $signature = 'db:apply-reviewed-process-configuration
        {--execute : Apply only the reviewed Task 2.6 Process Configuration migration}
        {--confirm-database= : Must exactly equal blackgrd}
        {--backup-manifest= : JSON manifest for verified backups}
        {--writes-stopped : Confirm queue and scheduler writes were independently verified stopped}';

    protected $description = 'Apply the reviewed, hash-pinned Task 2.6 Process Configuration migration to blackgrd';

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

            $path = database_path('migrations/'.self::MIGRATION.'.php');
            if (! File::isFile($path) || ! hash_equals(self::HASH, (string) hash_file('sha256', $path))) {
                throw new RuntimeException('Reviewed Process Configuration migration hash mismatch.');
            }
            if (DB::table('migrations')->where('migration', self::MIGRATION)->exists()) {
                throw new RuntimeException('Reviewed Process Configuration migration is already recorded as applied.');
            }
            foreach (['process_item_configurations', 'process_item_material_configurations', 'process_item_allowed_next'] as $table) {
                if (Schema::hasTable($table)) {
                    throw new RuntimeException("Process Configuration table [{$table}] already exists outside the migration ledger.");
                }
            }

            $this->verifyBackupManifest((string) $this->option('backup-manifest'));
            $before = $this->preservationSnapshot();
            if (! $this->option('execute')) {
                $this->info('READY: reviewed Task 2.6 Process Configuration migration preflight passed; no migration executed.');

                return self::SUCCESS;
            }
            if ((string) $this->option('confirm-database') !== self::DATABASE) {
                throw new RuntimeException('--execute requires --confirm-database=blackgrd.');
            }

            $migrator->setOutput($this->output);
            $migrator->run([$path], ['step' => true]);
            $this->verifyAppliedSchema($before);
            $this->info('PASS: reviewed Process Configuration migration applied with business-data preservation verification.');

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

    /** @return array<string, array{count:int,ids:string}> */
    private function preservationSnapshot(): array
    {
        $snapshot = [];
        foreach (['companies', 'process_items', 'item_type', 'sale_orders', 'sale_order_items', 'work_orders', 'work_order_items'] as $table) {
            $key = $table === 'item_type' ? 'item_type_id' : 'id';
            $ids = DB::table($table)->orderBy($key)->pluck($key)->map(fn ($id): string => (string) $id)->all();
            $snapshot[$table] = ['count' => count($ids), 'ids' => hash('sha256', implode(',', $ids))];
        }

        return $snapshot;
    }

    /** @param array<string, array{count:int,ids:string}> $before */
    private function verifyAppliedSchema(array $before): void
    {
        foreach (['process_item_configurations', 'process_item_material_configurations', 'process_item_allowed_next'] as $table) {
            if (! Schema::hasTable($table) || DB::table($table)->exists()) {
                throw new RuntimeException("Process Configuration table [{$table}] is missing or was not created empty.");
            }
        }
        if (! DB::table('migrations')->where('migration', self::MIGRATION)->exists() || $before !== $this->preservationSnapshot()) {
            throw new RuntimeException('Migration ledger or business-data preservation check failed.');
        }
    }
}
