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

class VerifyOperationalStatusBackfillCommand extends Command
{
    protected $signature = 'db:verify-operational-status-backfill
        {--confirm-database= : Exact disposable database name}
        {--execute : Run the reviewed apply, rollback, and re-apply cycle}';

    protected $description = 'Verify hash-pinned Task 1.3C backfill and preservation on a populated disposable database';

    public function handle(
        DatabaseSafetyGuard $guard,
        ReviewedOperationalStatusMigrations $reviewed,
        OperationalStatusMigrationVerifier $verifier,
        Migrator $migrator,
    ): int {
        $database = (string) $this->option('confirm-database');

        try {
            $snapshot = $guard->inspect();
            $this->assertConnection($guard, $snapshot, $database);
            $paths = $reviewed->verifiedPaths();
            $this->assertOnlyReviewedPending();
            $before = $verifier->preservationSnapshot();

            if (! $this->option('execute')) {
                $this->info('READY: populated disposable preflight passed; no migration was executed.');

                return self::SUCCESS;
            }

            $migrator->setOutput($this->output);
            $migrator->run($paths, ['step' => true]);
            $afterFirstApply = $verifier->preservationSnapshot();
            $verifier->assertPreserved($before, $afterFirstApply);
            $firstVerification = $verifier->verifyCanonicalBackfill();

            $this->assertConnectedDatabase($guard, $database, 'rollback');
            $guard->authorizeDestructiveCommand('migrate:rollback');
            try {
                $rolledBack = $migrator->rollback($paths, ['step' => count(ReviewedOperationalStatusMigrations::MIGRATIONS)]);
            } finally {
                $guard->revokeDestructiveAuthorization();
            }
            if (count($rolledBack) !== count(ReviewedOperationalStatusMigrations::MIGRATIONS)) {
                throw new RuntimeException('Rollback did not execute exactly eight reviewed migrations.');
            }
            $this->assertNoneReviewedRan();
            $afterRollback = $verifier->preservationSnapshot();
            $verifier->assertPreserved($before, $afterRollback);

            $this->assertConnectedDatabase($guard, $database, 're-migration');
            $migrator->run($paths, ['step' => true]);
            $afterSecondApply = $verifier->preservationSnapshot();
            $verifier->assertPreserved($before, $afterSecondApply);
            $secondVerification = $verifier->verifyCanonicalBackfill();

            if ($firstVerification !== $secondVerification) {
                throw new RuntimeException('Backfill results differ after rollback and re-migration.');
            }

            $auditPath = storage_path('logs/task-1.3c-disposable-verification-'.now()->format('Ymd_His').'.json');
            $this->writeAudit($auditPath, [
                'database' => $database,
                'migrations' => ReviewedOperationalStatusMigrations::MIGRATIONS,
                'before' => $before,
                'after_first_apply' => $afterFirstApply,
                'after_rollback' => $afterRollback,
                'after_second_apply' => $afterSecondApply,
                'verification' => $secondVerification,
                'completed_at' => now()->toIso8601String(),
                'result' => 'passed',
            ]);
            $this->info('PASS: populated backfill, preservation, rollback, and re-migration verified.');
            $this->line('Audit log: '.$auditPath);
        } catch (Throwable $exception) {
            $this->error('BLOCKED/FAILED: '.$exception->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function assertConnection(DatabaseSafetyGuard $guard, object $snapshot, string $database): void
    {
        if ($database !== ReviewedOperationalStatusMigrations::DISPOSABLE_DATABASE || ! $guard->isAllowedDisposableName($database)) {
            throw new RuntimeException('Only blackgrd_schema_testing is allowed for populated disposable verification.');
        }
        if ($snapshot->environment !== 'testing' || $snapshot->driver !== 'mysql') {
            throw new RuntimeException('Populated disposable verification requires APP_ENV=testing and MySQL.');
        }
        foreach (['declaredDatabase', 'configuredDatabase', 'connectedDatabase'] as $field) {
            if ($snapshot->{$field} !== $database) {
                throw new RuntimeException("{$field} must exactly match [{$database}].");
            }
        }
        if (! $guard->executionConfirmationMatches($database)) {
            throw new RuntimeException('Exact process-level destructive confirmation is not armed.');
        }
    }

    private function assertConnectedDatabase(DatabaseSafetyGuard $guard, string $database, string $operation): void
    {
        if ($guard->inspect()->connectedDatabase !== $database) {
            throw new RuntimeException("Connected database changed before {$operation}.");
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
            throw new RuntimeException('Pending set does not exactly match Task 1.3C: '.implode(', ', $pending));
        }
    }

    private function assertNoneReviewedRan(): void
    {
        if (DB::table('migrations')->whereIn('migration', array_keys(ReviewedOperationalStatusMigrations::MIGRATIONS))->exists()) {
            throw new RuntimeException('One or more reviewed migrations remained recorded after rollback.');
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
