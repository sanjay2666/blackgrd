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

final class ApplyReviewedProductionGenealogyMigrationCommand extends Command
{
    private const DATABASE = 'blackgrd';

    private const MIGRATION = '2026_08_12_000016_create_production_genealogy_links_table';

    private const HASH = '8944b068a9cf5bd62e241908cb0e99b0d06ecba63307c6f2de97a9f63de02c65';

    protected $signature = 'db:apply-reviewed-production-genealogy
        {--execute : Apply only the reviewed Task 5.5 production genealogy migration}
        {--confirm-database= : Must exactly equal blackgrd}
        {--backup-manifest= : JSON manifest for verified backups}
        {--writes-stopped : Confirm queue and scheduler writes were independently verified stopped}';

    protected $description = 'Apply the hash-pinned Task 5.5 production genealogy migration to blackgrd';

    public function handle(DatabaseSafetyGuard $guard, Migrator $migrator): int
    {
        try {
            $snapshot = $guard->inspect();
            if ($snapshot->driver !== 'mysql') {
                throw new RuntimeException('Reviewed Task 5.5 execution requires the MySQL driver.');
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
                throw new RuntimeException('Reviewed Task 5.5 migration hash mismatch.');
            }
            if (DB::table('migrations')->where('migration', self::MIGRATION)->exists()) {
                throw new RuntimeException('Reviewed Task 5.5 migration is already recorded as applied.');
            }
            if (Schema::hasTable('production_genealogy_links')) {
                throw new RuntimeException('Production genealogy table already exists outside the migration ledger.');
            }

            $this->verifyBackupManifest((string) $this->option('backup-manifest'));
            $before = $this->preservationSnapshot();
            if (! $this->option('execute')) {
                $this->info('READY: reviewed Task 5.5 production genealogy migration preflight passed; no migration executed.');

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

            $this->info('PASS: reviewed Task 5.5 migration applied with genealogy-table and business-data preservation verification.');

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

    /** @return array<string, array{count: int, ids: string}> */
    private function preservationSnapshot(): array
    {
        $snapshot = [];
        foreach (['companies', 'sale_orders', 'sale_order_items', 'work_orders', 'work_order_items', 'work_process_requirements', 'work_inspections', 'work_inspection_details', 'warehouse_item_stocks'] as $table) {
            $ids = DB::table($table)->orderBy('id')->pluck('id')->map(fn ($id): string => (string) $id)->all();
            $snapshot[$table] = ['count' => count($ids), 'ids' => hash('sha256', implode(',', $ids))];
        }

        return $snapshot;
    }

    /** @param array<string, array{count: int, ids: string}> $before */
    private function verifyAppliedSchema(array $before): void
    {
        if (! Schema::hasTable('production_genealogy_links') || DB::table('production_genealogy_links')->exists()) {
            throw new RuntimeException('Production genealogy table is missing or was not created empty.');
        }
        foreach (['production_genealogy_operation_unique', 'production_genealogy_source_lookup', 'production_genealogy_result_lookup'] as $index) {
            if (! collect(Schema::getIndexes('production_genealogy_links'))->contains(fn (array $definition): bool => ($definition['name'] ?? '') === $index)) {
                throw new RuntimeException("Production genealogy index [{$index}] is missing.");
            }
        }
        if (! DB::table('migrations')->where('migration', self::MIGRATION)->exists() || $before !== $this->preservationSnapshot()) {
            throw new RuntimeException('Migration ledger or business-data preservation check failed.');
        }
    }
}
