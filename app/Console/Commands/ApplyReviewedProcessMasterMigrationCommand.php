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

final class ApplyReviewedProcessMasterMigrationCommand extends Command
{
    private const DATABASE = 'blackgrd';

    private const MIGRATION = '2026_08_11_000003_harden_process_master';

    private const HASH = 'e648d8f0b8592a152a34169626d9b7f4116f71da84a5b0ce8c1dafc53a77f225';

    private const CORE_IDENTITIES = [1 => 'Warping', 2 => 'Weaving', 3 => 'Dyeing', 4 => 'Coating'];

    protected $signature = 'db:apply-reviewed-process-master
        {--execute : Apply only the reviewed additive Process Master migration}
        {--confirm-database= : Must exactly equal blackgrd}
        {--backup-manifest= : JSON manifest for verified backups}
        {--writes-stopped : Confirm queue and scheduler writes were independently verified stopped}';

    protected $description = 'Apply the reviewed, hash-pinned Process Master migration to blackgrd';

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
                throw new RuntimeException('Reviewed Process Master migration hash mismatch.');
            }
            if (DB::table('migrations')->where('migration', self::MIGRATION)->exists()) {
                throw new RuntimeException('Reviewed Process Master migration is already recorded as applied.');
            }
            $this->verifyBackupManifest((string) $this->option('backup-manifest'));
            $before = $this->preservationSnapshot();

            if (! $this->option('execute')) {
                $this->info('READY: reviewed Process Master migration preflight passed; no migration executed.');

                return self::SUCCESS;
            }
            if ((string) $this->option('confirm-database') !== self::DATABASE) {
                throw new RuntimeException('--execute requires --confirm-database=blackgrd.');
            }

            $migrator->setOutput($this->output);
            $migrator->run([$path], ['step' => true]);
            $this->verifyAppliedSchema($before);
            $this->info('PASS: reviewed Process Master migration applied with identity and row-count preservation verification.');

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

    /** @return array{process_count:int,core_identities:array<int,string>} */
    private function preservationSnapshot(): array
    {
        return [
            'process_count' => DB::table('process_items')->count(),
            'core_identities' => DB::table('process_items')->whereIn('id', array_keys(self::CORE_IDENTITIES))
                ->orderBy('id')->pluck('process_name', 'id')->mapWithKeys(fn ($name, $id) => [(int) $id => (string) $name])->all(),
        ];
    }

    /** @param array{process_count:int,core_identities:array<int,string>} $before */
    private function verifyAppliedSchema(array $before): void
    {
        foreach (['company_id', 'short_code', 'description', 'department_id', 'display_order'] as $column) {
            if (! Schema::hasColumn('process_items', $column)) {
                throw new RuntimeException("Required Process Master column [{$column}] was not created.");
            }
        }
        if (! DB::table('migrations')->where('migration', self::MIGRATION)->exists()
            || DB::table('process_items')->whereNull('company_id')->exists()
            || $before !== $this->preservationSnapshot()) {
            throw new RuntimeException('Migration ledger, company ownership, identity, or row-count preservation check failed.');
        }
    }
}
