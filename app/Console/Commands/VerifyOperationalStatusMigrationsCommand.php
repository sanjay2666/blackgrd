<?php

namespace App\Console\Commands;

use App\DatabaseSafety\DatabaseSafetyGuard;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use RuntimeException;
use Throwable;

class VerifyOperationalStatusMigrationsCommand extends Command
{
    protected $signature = 'db:verify-operational-status-migrations
        {--confirm-database= : Exact allow-listed disposable database name}';

    protected $description = 'Run fresh, rollback, and re-migration on one verified disposable database';

    public function handle(DatabaseSafetyGuard $guard): int
    {
        $database = (string) $this->option('confirm-database');

        try {
            $snapshot = $guard->inspect();

            if ($snapshot->environment !== 'testing') {
                throw new RuntimeException('Disposable migration verification requires APP_ENV=testing.');
            }

            if (! $guard->isAllowedDisposableName($database)) {
                throw new RuntimeException('The confirmed database is not allow-listed as disposable.');
            }

            foreach (['declaredDatabase', 'configuredDatabase', 'connectedDatabase'] as $field) {
                if ($snapshot->{$field} !== $database) {
                    throw new RuntimeException("{$field} must exactly match [{$database}].");
                }
            }

            if (! $guard->executionConfirmationMatches($database)) {
                throw new RuntimeException('Process-level destructive confirmation is not armed for the exact database.');
            }

            $this->runProtected($guard, 'migrate:fresh', $database);
            $this->runProtected($guard, 'migrate:rollback', $database);
            $this->runProtected($guard, 'migrate', $database);
        } catch (Throwable $exception) {
            $this->error('BLOCKED/FAILED: '.$exception->getMessage());

            return self::FAILURE;
        }

        $this->info("PASS: fresh, full rollback, and re-migration completed on [{$database}].");

        return self::SUCCESS;
    }

    private function runProtected(DatabaseSafetyGuard $guard, string $command, string $database): void
    {
        $snapshot = $guard->inspect();

        if ($snapshot->connectedDatabase !== $database) {
            throw new RuntimeException("[{$command}] connected database is not exactly [{$database}].");
        }

        $guard->authorizeDestructiveCommand($command);

        try {
            $exitCode = Artisan::call($command, ['--force' => true]);
            $this->output->write(Artisan::output());

            if ($exitCode !== self::SUCCESS) {
                throw new RuntimeException("[{$command}] returned exit code {$exitCode}.");
            }
        } finally {
            $guard->revokeDestructiveAuthorization();
        }
    }
}
