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

final class ApplyReviewedWorkflowOptionalStepsMigrationCommand extends Command
{
    private const DATABASE = 'blackgrd';

    private const MIGRATION = '2026_08_12_000015_add_optional_steps_and_repeat_support_to_workflow_version_steps';

    private const HASH = '555aa780606575b1e17dcc4071e1f95ea20e4423e03b5d261272d0f32f8bc968';

    private const SEQUENCE_UNIQUE_INDEX = 'workflow_version_steps_workflow_version_id_sequence_unique';

    private const PROCESS_UNIQUE_INDEX = 'workflow_version_steps_workflow_version_id_process_id_unique';

    protected $signature = 'db:apply-reviewed-workflow-optional-steps
        {--execute : Apply only the reviewed Task 5.3 Workflow Version Steps migration}
        {--confirm-database= : Must exactly equal blackgrd}
        {--backup-manifest= : JSON manifest for verified backups}
        {--writes-stopped : Confirm queue and scheduler writes were independently verified stopped}';

    protected $description = 'Apply the hash-pinned Task 5.3 Workflow Version Steps migration to blackgrd';

    public function handle(DatabaseSafetyGuard $guard, Migrator $migrator): int
    {
        try {
            $snapshot = $guard->inspect();
            if ($snapshot->driver !== 'mysql') {
                throw new RuntimeException('Reviewed Task 5.3 execution requires the MySQL driver.');
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
                throw new RuntimeException('Reviewed Task 5.3 migration hash mismatch.');
            }
            if (! Schema::hasTable('workflow_version_steps') || Schema::hasColumn('workflow_version_steps', 'is_required')) {
                throw new RuntimeException('Workflow Version Steps pre-migration column state is not as reviewed.');
            }
            if (! $this->hasUniqueIndex(self::SEQUENCE_UNIQUE_INDEX) || ! $this->hasUniqueIndex(self::PROCESS_UNIQUE_INDEX)) {
                throw new RuntimeException('Workflow Version Steps pre-migration unique-index state is not as reviewed.');
            }
            $this->verifyBackupManifest((string) $this->option('backup-manifest'));
            $this->assertReviewedMigrationIsPending();
            $before = $this->preservationSnapshot();

            if (! $this->option('execute')) {
                $this->info('READY: reviewed Task 5.3 Workflow Version Steps migration preflight passed; no migration executed.');

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

            $this->info('PASS: reviewed Task 5.3 migration applied with schema and workflow-data preservation verification.');

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

    private function assertReviewedMigrationIsPending(): void
    {
        if (DB::table('migrations')->where('migration', self::MIGRATION)->exists()) {
            throw new RuntimeException('Reviewed Task 5.3 migration is already recorded as applied.');
        }
    }

    /** @return array<string, array{count: int, ids: string}> */
    private function preservationSnapshot(): array
    {
        $snapshot = [];
        foreach (['companies', 'process_items', 'sale_orders', 'sale_order_items', 'work_orders', 'work_order_items', 'workflow_definitions', 'workflow_versions', 'workflow_version_steps'] as $table) {
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
        $column = collect(Schema::getColumns('workflow_version_steps'))->firstWhere('name', 'is_required');
        if ($column === null || ! in_array((string) ($column['default'] ?? ''), ['1', 'true'], true)) {
            throw new RuntimeException('Workflow Version Steps is_required column/default is not as reviewed.');
        }
        if (! $this->hasUniqueIndex(self::SEQUENCE_UNIQUE_INDEX) || $this->hasUniqueIndex(self::PROCESS_UNIQUE_INDEX)) {
            throw new RuntimeException('Workflow Version Steps post-migration unique-index state is not as reviewed.');
        }
        if (DB::table('workflow_version_steps')->where('is_required', '!=', true)->exists()) {
            throw new RuntimeException('Existing Workflow Version Steps were not preserved as required.');
        }
        if (! DB::table('migrations')->where('migration', self::MIGRATION)->exists()) {
            throw new RuntimeException('Reviewed Task 5.3 migration was not recorded in the migration ledger.');
        }
        if ($before !== $this->preservationSnapshot()) {
            throw new RuntimeException('Business or Workflow Definition/Version/Step rows changed during Task 5.3 migration.');
        }
    }

    private function hasUniqueIndex(string $name): bool
    {
        return collect(Schema::getIndexes('workflow_version_steps'))->contains(
            fn (array $index): bool => ($index['name'] ?? '') === $name && (bool) ($index['unique'] ?? false),
        );
    }
}
