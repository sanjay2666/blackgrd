<?php

namespace App\Providers;

use App\Console\Commands\DatabaseSafetyCheckCommand;
use App\Console\Commands\PrepareDisposableDatabaseCommand;
use App\DatabaseSafety\DatabaseSafetyGuard;
use App\DatabaseSafety\DisposableDatabaseManager;
use Illuminate\Console\Events\CommandFinished;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Database\Events\ConnectionEstablished;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class DatabaseSafetyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(DatabaseSafetyGuard::class);
        $this->app->singleton(DisposableDatabaseManager::class);
    }

    public function boot(DatabaseSafetyGuard $guard): void
    {
        Event::listen(CommandStarting::class, function (CommandStarting $event) use ($guard): void {
            if (! $guard->isDestructiveCommand($event->command)) {
                return;
            }

            try {
                $result = $guard->authorizeDestructiveCommand($event->command);
                $event->output->writeln(
                    "<info>Database safety preflight passed for [{$result->snapshot->connectedDatabase}].</info>"
                );
            } catch (\Throwable $exception) {
                $event->output->writeln('<error>'.$exception->getMessage().'</error>');
                throw $exception;
            }
        });

        Event::listen(CommandFinished::class, function (CommandFinished $event) use ($guard): void {
            if ($guard->isDestructiveCommand($event->command)) {
                $guard->revokeDestructiveAuthorization();
            }
        });

        Event::listen(ConnectionEstablished::class, function (ConnectionEstablished $event) use ($guard): void {
            $event->connection->beforeExecuting(
                function (string $query) use ($guard): void {
                    $guard->assertDestructiveSqlAllowed($query);
                }
            );
        });

        if ($this->app->runningInConsole()) {
            $this->commands([
                DatabaseSafetyCheckCommand::class,
                PrepareDisposableDatabaseCommand::class,
            ]);
        }
    }
}
