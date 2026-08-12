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

final class ApplyReviewedCotingMasterMigrationCommand extends Command
{
    private const DATABASE = 'blackgrd';

    private const MIGRATION = '2026_08_12_000004_harden_coting_master';

    private const HASH = 'eddf682375e9d83630959c524757166a296a73acbf73b42903ad8cced5fcd6bc';

    protected $signature = 'db:apply-reviewed-coting-master
        {--execute : Apply only the reviewed additive Coting Master migration}
        {--confirm-database= : Must exactly equal blackgrd}
        {--backup-manifest= : JSON manifest for verified backups}
        {--writes-stopped : Confirm queue and scheduler writes were independently verified stopped}';

    protected $description = 'Apply the reviewed, hash-pinned Coting Master migration to blackgrd';

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
                throw new RuntimeException('Reviewed Coting Master migration hash mismatch.');
            }
            if (DB::table('migrations')->where('migration', self::MIGRATION)->exists()) {
                throw new RuntimeException('Reviewed Coting Master migration is already recorded as applied.');
            }
            if (! Schema::hasTable('cotings')) {
                throw new RuntimeException('cotings table must exist before applying the reviewed hardening migration.');
            }

            $this->verifyBackupManifest((string) $this->option('backup-manifest'));
            $before = $this->preservationSnapshot();

            if (! $this->option('execute')) {
                $this->info('READY: reviewed Coting Master migration preflight passed; no migration executed.');

                return self::SUCCESS;
            }
            if ((string) $this->option('confirm-database') !== self::DATABASE) {
                throw new RuntimeException('--execute requires --confirm-database=blackgrd.');
            }

            $migrator->setOutput($this->output);
            $migrator->run([$path], ['step' => true]);
            $this->verifyAppliedSchema($before);
            $this->info('PASS: reviewed Coting Master migration applied with identity and row-count preservation verification.');

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

    /** @return array{count:int,rows:list<array<string,mixed>>} */
    private function preservationSnapshot(): array
    {
        $columns = ['id', 'name', 'code', 'status'];
        if (Schema::hasColumn('cotings', 'company_id')) {
            $columns[] = 'company_id';
        }

        return [
            'count' => DB::table('cotings')->count(),
            'rows' => DB::table('cotings')->orderBy('id')->get($columns)->map(fn ($row): array => (array) $row)->all(),
        ];
    }

    /** @param array{count:int,rows:list<array<string,mixed>>} $before */
    private function verifyAppliedSchema(array $before): void
    {
        foreach (['description', 'display_order'] as $column) {
            if (! Schema::hasColumn('cotings', $column)) {
                throw new RuntimeException("Required Coting Master column [{$column}] was not created.");
            }
        }
        if (! DB::table('migrations')->where('migration', self::MIGRATION)->exists()) {
            throw new RuntimeException('Reviewed Coting Master migration was not recorded in the migration ledger.');
        }
        if ($before !== $this->preservationSnapshot()) {
            throw new RuntimeException('Coting identity, status, company ownership, or row-count preservation check failed.');
        }
    }
}
