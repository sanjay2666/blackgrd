<?php

namespace App\Console\Commands;

use App\DatabaseSafety\DatabaseSafetyGuard;
use App\DatabaseSafety\DisposableDatabaseManager;
use App\DatabaseSafety\UnsafeDatabaseOperation;
use Illuminate\Console\Command;

class PrepareDisposableDatabaseCommand extends Command
{
    protected $signature = 'db:prepare-disposable
        {database : Allow-listed disposable database name}
        {--recreate : Drop and recreate the database when it already exists}
        {--force : Use environment-based confirmation instead of interactive prompts}';

    protected $description = 'Safely create or recreate an explicitly allow-listed disposable database';

    public function handle(
        DatabaseSafetyGuard $guard,
        DisposableDatabaseManager $manager,
    ): int {
        $database = trim((string) $this->argument('database'));

        if (! $guard->isAllowedDisposableName($database)) {
            $this->error("BLOCKED: requested database [{$database}] is not allow-listed as disposable.");

            return self::FAILURE;
        }

        try {
            $snapshot = $guard->inspect();
            $guard->assertDisposableTargetAllowed($database, $snapshot);
        } catch (UnsafeDatabaseOperation $exception) {
            $this->error('BLOCKED: '.$exception->getMessage());

            return self::FAILURE;
        }

        $this->table(['Check', 'Effective value'], [
            ['Laravel environment', $snapshot->environment],
            ['Connection', $snapshot->connectionName],
            ['Host', $snapshot->host ?? '(not configured)'],
            ['Port', $snapshot->port ?? '(not configured)'],
            ['Current connected database', $snapshot->connectedDatabase ?? '(unknown)'],
            ['Requested disposable database', $database],
            ['Requested operation', $this->option('recreate') ? 'create or recreate' : 'create if missing'],
        ]);

        if (! $this->confirmOperation($guard, $database)) {
            return self::FAILURE;
        }

        $guard->authorizeDisposableTarget($database, $snapshot);

        try {
            $result = $manager->prepare($database, (bool) $this->option('recreate'));
        } catch (UnsafeDatabaseOperation $exception) {
            $this->error('BLOCKED: '.$exception->getMessage());

            return self::FAILURE;
        } finally {
            $guard->revokeDisposableTargetAuthorization();
        }

        if ($result['recreated']) {
            $this->info("Disposable database [{$database}] was recreated.");
        } elseif ($result['created']) {
            $this->info("Disposable database [{$database}] was created.");
        } else {
            $this->info("Disposable database [{$database}] already exists; no destructive action was taken.");
        }

        return self::SUCCESS;
    }

    private function confirmOperation(DatabaseSafetyGuard $guard, string $database): bool
    {
        if ($this->option('force')) {
            if (! $guard->executionConfirmationMatches($database)) {
                $this->error(
                    'BLOCKED: --force requires DB_DESTRUCTIVE_OPERATIONS_ALLOWED=true and '
                    ."DB_DESTRUCTIVE_CONFIRM_DATABASE={$database}."
                );

                return false;
            }

            return true;
        }

        if (! $this->input->isInteractive()) {
            $this->error('BLOCKED: non-interactive execution requires --force and both confirmation variables.');

            return false;
        }

        if (! $this->confirm("Prepare disposable database [{$database}] on the verified server?", false)) {
            $this->warn('Cancelled; no database action was taken.');

            return false;
        }

        $typed = (string) $this->ask('Type the exact disposable database name to confirm');

        if (! hash_equals($database, $typed)) {
            $this->error('BLOCKED: typed database name did not match exactly.');

            return false;
        }

        return true;
    }
}
