<?php

namespace App\Console\Commands;

use App\DatabaseSafety\DatabaseSafetyGuard;
use Illuminate\Console\Command;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use RuntimeException;
use Throwable;

final class ApplyReviewedDepartmentProcessMappingMigrationCommand extends Command
{
    private const DATABASE = 'blackgrd';

    private const MIGRATION = '2026_08_13_000001_complete_department_process_mappings';

    private const HASH = '27f86f73a97ed64c8204ba78c228d755a2ba1e1026f7a4017c7bd37262904028';

    protected $signature = 'db:apply-reviewed-department-process-mappings
        {--execute : Apply only the reviewed Department/Process data migration}
        {--confirm-database= : Must exactly equal blackgrd}
        {--backup-manifest= : JSON manifest for verified backups}
        {--writes-stopped : Confirm queue and scheduler writes were independently verified stopped}';

    protected $description = 'Apply the hash-pinned Department/Process mapping migration to blackgrd';

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
                throw new RuntimeException('Reviewed Department/Process migration hash mismatch.');
            }
            if (DB::table('migrations')->where('migration', self::MIGRATION)->exists()) {
                throw new RuntimeException('Reviewed Department/Process migration is already recorded as applied.');
            }
            $this->verifyBackupManifest((string) $this->option('backup-manifest'));

            if (! $this->option('execute')) {
                $this->table(['Process', 'Target Department'], $this->expectedMappings());
                $this->info('READY: reviewed Department/Process mapping preflight passed; no migration executed.');

                return self::SUCCESS;
            }
            if ((string) $this->option('confirm-database') !== self::DATABASE) {
                throw new RuntimeException('--execute requires --confirm-database=blackgrd.');
            }

            $guard->authorizeReviewedLiveMigration(self::DATABASE);
            $migrator->setOutput($this->output);
            $migrator->run([$path], ['step' => true]);
            $this->verifyAppliedMappings();
            $this->info('PASS: reviewed Department/Process mapping migration applied and verified.');

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

    /** @return list<array{0:string,1:string}> */
    private function expectedMappings(): array
    {
        return [
            ['Warping', 'Weaving'], ['Weaving', 'Weaving'], ['Dyeing', 'Dyeing'],
            ['Printing / D-Printing / C-Printing', 'Printing'], ['Coating', 'Coating'],
            ['Packaging', 'Packaging'], ['Warehouse', 'Warehouse'],
        ];
    }

    private function verifyAppliedMappings(): void
    {
        $expected = collect($this->expectedMappings())->flatMap(function (array $mapping): array {
            $processes = explode(' / ', $mapping[0]);

            return array_fill_keys($processes, $mapping[1]);
        });
        $unmapped = DB::table('process_items')->join('departments', 'departments.id', '=', 'process_items.department_id')
            ->where('process_items.status', 'Active')->whereIn('process_items.process_name', $expected->keys())
            ->whereColumn('process_items.company_id', 'departments.company_id')
            ->get(['process_items.process_name', 'departments.department_name'])
            ->contains(fn ($row): bool => $expected->get($row->process_name) !== $row->department_name);

        if ($unmapped) {
            throw new RuntimeException('One or more canonical active Processes do not have the required Department mapping.');
        }
    }
}
