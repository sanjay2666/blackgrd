<?php

namespace App\Console\Commands;

use App\DatabaseSafety\DatabaseSafetyGuard;
use Illuminate\Console\Command;

class DatabaseSafetyCheckCommand extends Command
{
    protected $signature = 'db:safety-check {--connection= : Laravel database connection to inspect}';

    protected $description = 'Verify the effective runtime database before any destructive operation';

    public function handle(DatabaseSafetyGuard $guard): int
    {
        $connection = $this->option('connection');
        $result = $guard->check(is_string($connection) && $connection !== '' ? $connection : null);
        $snapshot = $result->snapshot;

        $this->table(['Check', 'Effective value'], [
            ['Laravel environment', $snapshot->environment],
            ['Connection', $snapshot->connectionName],
            ['Driver', $snapshot->driver !== '' ? $snapshot->driver : '(unknown)'],
            ['Host', $snapshot->host ?? '(not configured)'],
            ['Port', $snapshot->port ?? '(not configured)'],
            ['Environment-declared database', $snapshot->declaredDatabase ?? '(unknown)'],
            ['Configured database', $snapshot->configuredDatabase ?? '(unknown)'],
            ['Connected database', $snapshot->connectedDatabase ?? '(unknown)'],
            ['Configuration cache', $snapshot->configurationCached ? 'PRESENT' : 'NOT PRESENT'],
            ['Disposable allow-list', $result->allowed ? 'MATCH' : 'NO MATCH'],
            ['Execution confirmation', $result->executionArmed ? 'ARMED' : 'NOT ARMED'],
        ]);

        if ($result->allowed) {
            $this->info('ALLOWED: the effective database is eligible for destructive operations.');

            if (! $result->executionArmed) {
                $this->warn('Destructive execution remains unarmed until both confirmation variables match.');
            }

            return self::SUCCESS;
        }

        $this->error('BLOCKED: destructive database operations are not allowed.');

        foreach ($result->reasons as $reason) {
            $this->line(" - {$reason}");
        }

        return self::FAILURE;
    }
}
