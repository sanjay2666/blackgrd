<?php

namespace App\Console\Commands;

use App\DatabaseSafety\DatabaseSafetyGuard;
use Illuminate\Console\Command;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use RuntimeException;
use Throwable;

final class ApplyReviewedWarpingDepartmentMappingMigrationCommand extends Command
{
    private const DATABASE = 'blackgrd';

    private const MIGRATION = '2026_08_13_000002_correct_warping_department_mapping';

    private const HASH = '868f2523d97c89d0552660387762b0a12bc7b83c2f73d0aa8a08dbdd3223ac5e';

    protected $signature = 'db:apply-reviewed-warping-department-mapping
        {--execute : Apply only the reviewed Warping Department mapping data migration}
        {--confirm-database= : Must exactly equal blackgrd}
        {--backup-manifest= : JSON manifest for verified backups}
        {--writes-stopped : Confirm queue and scheduler writes were independently verified stopped}';

    protected $description = 'Apply the hash-pinned Warping Department mapping migration to blackgrd';

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
                throw new RuntimeException('Reviewed Warping Department migration hash mismatch.');
            }
            if (DB::table('migrations')->where('migration', self::MIGRATION)->exists()) {
                throw new RuntimeException('Reviewed Warping Department migration is already recorded as applied.');
            }
            $this->verifyBackupManifest((string) $this->option('backup-manifest'));
            $accessBefore = DB::table('user_department_access')->orderBy('id')->get()->map(fn ($row): array => (array) $row)->all();

            if (! $this->option('execute')) {
                $this->info('READY: reviewed Warping Department mapping preflight passed; no migration executed.');

                return self::SUCCESS;
            }
            if ((string) $this->option('confirm-database') !== self::DATABASE) {
                throw new RuntimeException('--execute requires --confirm-database=blackgrd.');
            }

            $guard->authorizeReviewedLiveMigration(self::DATABASE);
            $migrator->setOutput($this->output);
            $migrator->run([$path], ['step' => true]);
            $this->verifyAppliedMapping($accessBefore);
            $this->info('PASS: reviewed Warping Department mapping migration applied and verified.');

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error('BLOCKED/FAILED: '.$exception->getMessage());

            return self::FAILURE;
        } finally {
            $guard->revokeDestructiveAuthorization();
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

    /** @param list<array<string, mixed>> $accessBefore */
    private function verifyAppliedMapping(array $accessBefore): void
    {
        $invalidWarping = DB::table('process_items')->leftJoin('departments', 'departments.id', '=', 'process_items.department_id')
            ->where('process_items.status', 'Active')->where('process_items.process_name', 'Warping')
            ->where(function ($query): void {
                $query->whereNull('departments.id')->orWhereColumn('process_items.company_id', '!=', 'departments.company_id')
                    ->orWhere('departments.department_name', '!=', 'Warping')->orWhere('departments.status', '!=', 'Active');
            })->exists();
        $accessAfter = DB::table('user_department_access')->orderBy('id')->get()->map(fn ($row): array => (array) $row)->all();

        if ($invalidWarping || $accessBefore !== $accessAfter) {
            throw new RuntimeException('Warping mapping or User Department Access preservation verification failed.');
        }
    }
}
