<?php

namespace Tests\Feature\DatabaseSafety;

use App\DatabaseSafety\UnsafeDatabaseOperation;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Support\Facades\Event;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Tests\TestCase;

class DatabaseSafetyCommandTest extends TestCase
{
    public function test_safety_check_returns_success_for_phpunit_sqlite_memory_database(): void
    {
        $this->artisan('db:safety-check')
            ->expectsOutputToContain('ALLOWED')
            ->assertExitCode(0);
    }

    public function test_safety_check_returns_failure_for_unknown_connection(): void
    {
        $this->artisan('db:safety-check', ['--connection' => 'missing-safety-connection'])
            ->expectsOutputToContain('BLOCKED')
            ->assertExitCode(1);
    }

    public function test_disposable_preparation_rejects_live_name_before_any_database_action(): void
    {
        $this->artisan('db:prepare-disposable', [
            'database' => 'blackgrd',
            '--force' => true,
        ])
            ->expectsOutputToContain('BLOCKED')
            ->assertExitCode(1);
    }

    public function test_destructive_command_event_is_blocked_before_command_execution_when_not_armed(): void
    {
        $this->expectException(UnsafeDatabaseOperation::class);

        Event::dispatch(new CommandStarting(
            'migrate:fresh',
            new ArrayInput([]),
            new BufferedOutput,
        ));
    }

    public function test_migrate_command_is_blocked_when_not_armed(): void
    {
        $this->expectException(UnsafeDatabaseOperation::class);

        Event::dispatch(new CommandStarting(
            'migrate',
            new ArrayInput([]),
            new BufferedOutput,
        ));
    }

    public function test_reviewed_live_migration_command_rejects_non_live_database(): void
    {
        $this->artisan('db:apply-reviewed-foreign-keys', [
            '--execute' => true,
            '--confirm-database' => 'blackgrd',
        ])
            ->expectsOutputToContain('BLOCKED')
            ->assertExitCode(1);
    }
}
