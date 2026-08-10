<?php

namespace App\Console\Commands;

use App\DatabaseSafety\DatabaseSafetyGuard;
use App\Domain\OperationalStatus\OperationalStatusMigrationVerifier;
use App\Domain\OperationalStatus\ReviewedOperationalStatusMigrations;
use Illuminate\Console\Command;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use RuntimeException;
use Throwable;

class ApplyReviewedOperationalStatusMigrationsCommand extends Command
{
    protected $signature = 'db:apply-reviewed-operational-statuses
        {--execute : Apply only the reviewed Task 1.3C migrations}
        {--confirm-database= : Must exactly equal blackgrd}
        {--backup-manifest= : JSON manifest for verified full, affected-table, and migrations-table backups}
        {--writes-stopped : Confirm queue and scheduler writes were independently verified stopped}';

    protected $description = 'Apply only hash-pinned Task 1.3C operational status migrations to reviewed live database';

    public function handle(
        DatabaseSafetyGuard $guard,
        ReviewedOperationalStatusMigrations $reviewed,
        OperationalStatusMigrationVerifier $verifier,
        Migrator $migrator,
    ): int {
        $auditPath = storage_path('logs/task-1.3c-live-apply-'.now()->format('Ymd_His').'.json');
        $audit = ['command' => $this->getName(), 'status' => 'blocked'];

        try {
            $snapshot = $guard->inspect();
            $this->assertLiveConnection($snapshot);
            $this->assertApplicationIsDown();
            $paths = $reviewed->verifiedPaths();
            $this->assertOnlyReviewedPending();
            $backups = $this->verifyBackupManifest((string) $this->option('backup-manifest'));
            if (! $this->option('writes-stopped')) {
                throw new RuntimeException('--writes-stopped is required after independent queue/scheduler process verification.');
            }

            $before = $verifier->preservationSnapshot();
            $exclusions = $verifier->legacyExclusions();
            $audit = [
                'command' => $this->getName(),
                'database' => $snapshot->connectedDatabase,
                'environment' => $snapshot->environment,
                'migrations' => ReviewedOperationalStatusMigrations::MIGRATIONS,
                'backups' => $backups,
                'writes_stopped' => true,
                'legacy_exclusions' => $exclusions,
                'before' => $before,
                'authorized_at' => now()->toIso8601String(),
                'status' => 'ready',
            ];
            $this->writeAudit($auditPath, $audit);

            $this->table(['Check', 'Effective value'], [
                ['Connected database', $snapshot->connectedDatabase],
                ['Maintenance mode', 'ACTIVE'],
                ['Pending migrations', (string) count(ReviewedOperationalStatusMigrations::MIGRATIONS).' exact reviewed files'],
                ['Migration hashes', 'MATCH'],
                ['Verified backups', (string) count($backups)],
                ['Explicit legacy exclusions', (string) count($exclusions)],
            ]);

            if (! $this->option('execute')) {
                $this->info('READY: live Task 1.3C preflight passed; no migration was executed.');
                $this->line('Audit log: '.$auditPath);

                return self::SUCCESS;
            }

            if (! hash_equals(ReviewedOperationalStatusMigrations::DATABASE, (string) $this->option('confirm-database'))) {
                throw new RuntimeException('--execute requires --confirm-database=blackgrd.');
            }
            if ($guard->inspect()->connectedDatabase !== ReviewedOperationalStatusMigrations::DATABASE) {
                throw new RuntimeException('Connected database changed immediately before execution.');
            }

            $migrator->setOutput($this->output);
            $migrator->run($paths, ['step' => true]);
            $this->assertReviewedRan();
            $after = $verifier->preservationSnapshot();
            $verifier->assertPreserved($before, $after);
            $verification = $verifier->verifyCanonicalBackfill();

            $audit['after'] = $after;
            $audit['verification'] = $verification;
            $audit['completed_at'] = now()->toIso8601String();
            $audit['status'] = 'succeeded';
            $this->info('PASS: reviewed Task 1.3C migrations and read-only post-apply verification succeeded.');
        } catch (Throwable $exception) {
            $audit['failed_at'] = now()->toIso8601String();
            $audit['error'] = ['class' => $exception::class, 'message' => $exception->getMessage()];
            $audit['status'] = 'failed';
            $this->error('BLOCKED/FAILED: '.$exception->getMessage());
        } finally {
            try {
                $this->writeAudit($auditPath, $audit);
                $this->line('Audit log: '.$auditPath);
            } catch (Throwable $auditException) {
                $this->error('Audit finalization failed: '.$auditException->getMessage());

                return self::FAILURE;
            }
        }

        return $audit['status'] === 'succeeded' || ($audit['status'] === 'ready' && ! $this->option('execute'))
            ? self::SUCCESS
            : self::FAILURE;
    }

    private function assertLiveConnection(object $snapshot): void
    {
        if ($snapshot->driver !== 'mysql' || $snapshot->connectionError !== null) {
            throw new RuntimeException('Reviewed live execution requires an available MySQL connection.');
        }
        foreach (['declaredDatabase', 'configuredDatabase', 'connectedDatabase'] as $field) {
            if ($snapshot->{$field} !== ReviewedOperationalStatusMigrations::DATABASE) {
                throw new RuntimeException("{$field} must exactly equal [blackgrd].");
            }
        }
    }

    private function assertApplicationIsDown(): void
    {
        if (! app()->isDownForMaintenance()) {
            throw new RuntimeException('Application maintenance mode must be active.');
        }
    }

    private function assertOnlyReviewedPending(): void
    {
        $ran = DB::table('migrations')->pluck('migration')->all();
        $repository = collect(File::glob(database_path('migrations/*.php')))->map(fn (string $path): string => pathinfo($path, PATHINFO_FILENAME))->all();
        $pending = array_values(array_diff($repository, $ran));
        $expected = array_keys(ReviewedOperationalStatusMigrations::MIGRATIONS);
        sort($pending);
        sort($expected);
        if ($pending !== $expected) {
            throw new RuntimeException('Pending migration set mismatch: '.implode(', ', $pending));
        }
    }

    /** @return list<array{kind: string, path: string, size: int, sha256: string}> */
    private function verifyBackupManifest(string $manifestPath): array
    {
        if ($manifestPath === '' || ! File::isFile($manifestPath)) {
            throw new RuntimeException('A readable --backup-manifest is required.');
        }
        $manifest = json_decode((string) File::get($manifestPath), true, flags: JSON_THROW_ON_ERROR);
        if (($manifest['database'] ?? null) !== ReviewedOperationalStatusMigrations::DATABASE) {
            throw new RuntimeException('Backup manifest database must exactly equal blackgrd.');
        }
        $backups = $manifest['backups'] ?? [];
        $kinds = [];
        foreach ($backups as $backup) {
            $path = (string) ($backup['path'] ?? '');
            $kind = (string) ($backup['kind'] ?? '');
            if (! in_array($kind, ['full', 'affected_tables', 'migrations_table'], true) || ! File::isFile($path)) {
                throw new RuntimeException("Invalid backup manifest entry: {$kind}");
            }
            $size = File::size($path);
            $hash = hash_file('sha256', $path);
            if ($size <= 0 || $size !== (int) ($backup['size'] ?? -1) || $hash === false || ! hash_equals(strtolower((string) ($backup['sha256'] ?? '')), strtolower($hash))) {
                throw new RuntimeException("Backup verification mismatch: {$path}");
            }
            $kinds[] = $kind;
        }
        sort($kinds);
        if ($kinds !== ['affected_tables', 'full', 'migrations_table']) {
            throw new RuntimeException('Backup manifest must contain exactly the three required backup kinds.');
        }

        return $backups;
    }

    private function assertReviewedRan(): void
    {
        $ran = DB::table('migrations')->whereIn('migration', array_keys(ReviewedOperationalStatusMigrations::MIGRATIONS))->pluck('migration')->sort()->values()->all();
        $expected = array_keys(ReviewedOperationalStatusMigrations::MIGRATIONS);
        sort($expected);
        if ($ran !== $expected) {
            throw new RuntimeException('Migration ledger does not contain exactly the reviewed Task 1.3C migrations.');
        }
    }

    /** @param array<string, mixed> $data */
    private function writeAudit(string $path, array $data): void
    {
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        if (File::put($path, $json.PHP_EOL, true) === false) {
            throw new RuntimeException("Unable to write audit log: {$path}");
        }
    }
}
