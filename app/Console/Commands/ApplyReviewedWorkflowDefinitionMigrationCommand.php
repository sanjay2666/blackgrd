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

final class ApplyReviewedWorkflowDefinitionMigrationCommand extends Command
{
    private const DATABASE = 'blackgrd';

    private const MIGRATION = '2026_08_11_000001_create_workflow_definition_tables';

    private const HASH = '8eb036d0ef536ddff307b1d9fbaac88124e2a77a2f08f5d8d18a6d77a4e5bed9';

    protected $signature = 'db:apply-reviewed-workflow-definition
        {--execute : Apply only the reviewed Task 5.1 Workflow migration}
        {--confirm-database= : Must exactly equal blackgrd}
        {--backup-manifest= : JSON manifest for verified backups}
        {--writes-stopped : Confirm queue and scheduler writes were independently verified stopped}';

    protected $description = 'Apply the hash-pinned Task 5.1 Workflow Definition migration to blackgrd';

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
                throw new RuntimeException('Reviewed Workflow Definition migration hash mismatch.');
            }
            if (DB::table('migrations')->where('migration', self::MIGRATION)->exists()) {
                throw new RuntimeException('Reviewed Workflow Definition migration is already recorded as applied.');
            }
            foreach (['workflow_definitions', 'workflow_versions', 'workflow_version_steps'] as $table) {
                if (Schema::hasTable($table)) {
                    throw new RuntimeException("Workflow table [{$table}] already exists outside the migration ledger.");
                }
            }
            foreach (['workflow_definition_id', 'workflow_version_id'] as $column) {
                if (Schema::hasColumn('sale_order_items', $column)) {
                    throw new RuntimeException("Sale Order Item column [{$column}] already exists outside the migration ledger.");
                }
            }

            $this->verifyBackupManifest((string) $this->option('backup-manifest'));
            $before = $this->preservationSnapshot();

            if (! $this->option('execute')) {
                $this->info('READY: reviewed Task 5.1 Workflow migration preflight passed; no migration executed.');

                return self::SUCCESS;
            }
            if ((string) $this->option('confirm-database') !== self::DATABASE) {
                throw new RuntimeException('--execute requires --confirm-database=blackgrd.');
            }

            $migrator->setOutput($this->output);
            $migrator->run([$path], ['step' => true]);
            $this->verifyAppliedSchema($before);
            $this->info('PASS: reviewed Task 5.1 Workflow migration applied with business-data preservation verification.');

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
                || ! hash_equals(
                    strtolower((string) ($backup['sha256'] ?? '')),
                    strtolower((string) hash_file('sha256', $file)),
                )) {
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
        foreach (['companies', 'process_items', 'sale_orders', 'sale_order_items', 'work_orders', 'work_order_items'] as $table) {
            $ids = DB::table($table)->orderBy('id')->pluck('id')->map(fn ($id): string => (string) $id)->all();
            $snapshot[$table] = [
                'count' => count($ids),
                'ids' => hash('sha256', implode(',', $ids)),
            ];
        }

        return $snapshot;
    }

    /** @param array<string, array{count: int, ids: string}> $before */
    private function verifyAppliedSchema(array $before): void
    {
        foreach (['workflow_definitions', 'workflow_versions', 'workflow_version_steps'] as $table) {
            if (! Schema::hasTable($table) || DB::table($table)->exists()) {
                throw new RuntimeException("Workflow table [{$table}] is missing or was not created empty.");
            }
        }
        foreach (['workflow_definition_id', 'workflow_version_id'] as $column) {
            if (! Schema::hasColumn('sale_order_items', $column)) {
                throw new RuntimeException("Required Sale Order Item column [{$column}] was not created.");
            }
        }
        if (! DB::table('migrations')->where('migration', self::MIGRATION)->exists()) {
            throw new RuntimeException('Reviewed Workflow Definition migration was not recorded in the migration ledger.');
        }
        if ($before !== $this->preservationSnapshot()) {
            throw new RuntimeException('Business table row counts or ordered identity hashes changed during Task 5.1 migration.');
        }
    }
}
