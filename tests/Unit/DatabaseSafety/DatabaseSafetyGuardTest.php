<?php

namespace Tests\Unit\DatabaseSafety;

use App\DatabaseSafety\DatabaseSafetyGuard;
use App\DatabaseSafety\DatabaseSafetySnapshot;
use App\DatabaseSafety\UnsafeDatabaseOperation;
use Tests\TestCase;

class DatabaseSafetyGuardTest extends TestCase
{
    public function test_live_blackgrd_is_blocked_in_local_environment(): void
    {
        $result = $this->guard()->evaluate($this->snapshot(connected: 'blackgrd'));

        $this->assertFalse($result->allowed);
        $this->assertStringContainsString('not an allow-listed disposable database', implode(' ', $result->reasons));
    }

    public function test_allow_listed_testing_database_is_allowed(): void
    {
        $result = $this->guard()->evaluate($this->snapshot(
            environment: 'testing',
            declared: 'blackgrd_testing',
            configured: 'blackgrd_testing',
            connected: 'blackgrd_testing',
        ));

        $this->assertTrue($result->allowed);
        $this->assertFalse($result->executionArmed, 'A safe name alone must not arm destructive execution.');
    }

    public function test_configured_and_connected_database_mismatch_is_blocked(): void
    {
        $result = $this->guard()->evaluate($this->snapshot(
            declared: 'blackgrd_testing',
            configured: 'blackgrd_testing',
            connected: 'blackgrd',
        ));

        $this->assertFalse($result->allowed);
        $this->assertStringContainsString('Configured and connected database names do not match', implode(' ', $result->reasons));
    }

    public function test_config_cache_is_safe_only_when_effective_names_match(): void
    {
        $safe = $this->guard()->evaluate($this->snapshot(
            environment: 'testing',
            declared: 'blackgrd_testing',
            configured: 'blackgrd_testing',
            connected: 'blackgrd_testing',
            cached: true,
        ));
        $unsafe = $this->guard()->evaluate($this->snapshot(
            environment: 'testing',
            declared: 'blackgrd_testing',
            configured: 'blackgrd',
            connected: 'blackgrd',
            cached: true,
        ));

        $this->assertTrue($safe->allowed);
        $this->assertFalse($unsafe->allowed);
    }

    public function test_unknown_and_empty_database_names_are_blocked(): void
    {
        $unknown = $this->guard()->evaluate($this->snapshot(
            declared: null,
            configured: null,
            connected: null,
            error: 'unavailable',
        ));
        $unexpected = $this->guard()->evaluate($this->snapshot(connected: 'customer_archive'));

        $this->assertFalse($unknown->allowed);
        $this->assertFalse($unexpected->allowed);
    }

    public function test_production_environment_is_blocked_even_for_disposable_name(): void
    {
        $result = $this->guard()->evaluate($this->snapshot(
            environment: 'production',
            declared: 'blackgrd_testing',
            configured: 'blackgrd_testing',
            connected: 'blackgrd_testing',
        ));

        $this->assertFalse($result->allowed);
        $this->assertStringContainsString('production Laravel environment', implode(' ', $result->reasons));
    }

    public function test_disposable_name_validation_rejects_unsafe_names(): void
    {
        $guard = $this->guard();

        $this->assertFalse($guard->isAllowedDisposableName('blackgrd'));
        $this->assertFalse($guard->isAllowedDisposableName('blackgrd_erp'));
        $this->assertFalse($guard->isAllowedDisposableName('production'));
        $this->assertFalse($guard->isAllowedDisposableName(''));
        $this->assertTrue($guard->isAllowedDisposableName('blackgrd_testing'));
        $this->assertTrue($guard->isAllowedDisposableName('feature_tmp'));
        $this->assertTrue($guard->isAllowedDisposableName('restore_recovery'));
    }

    public function test_direct_destructive_sql_is_blocked_without_authorized_preflight(): void
    {
        $this->expectException(UnsafeDatabaseOperation::class);

        $this->guard()->assertDestructiveSqlAllowed('DROP TABLE users');
    }

    public function test_alter_table_drop_is_recognized_as_destructive_sql(): void
    {
        $this->assertTrue(
            $this->guard()->isDestructiveSql('ALTER TABLE users DROP COLUMN legacy_flag')
        );
    }

    public function test_non_destructive_sql_is_not_blocked(): void
    {
        $this->guard()->assertDestructiveSqlAllowed('SELECT * FROM users');

        $this->addToAssertionCount(1);
    }

    public function test_disposable_service_requires_confirmation_context(): void
    {
        $this->expectException(UnsafeDatabaseOperation::class);

        $this->guard()->assertDisposableTargetAuthorized('blackgrd_testing');
    }

    private function guard(): DatabaseSafetyGuard
    {
        return $this->app->make(DatabaseSafetyGuard::class);
    }

    private function snapshot(
        string $environment = 'local',
        ?string $declared = 'blackgrd',
        ?string $configured = 'blackgrd',
        ?string $connected = 'blackgrd',
        bool $cached = false,
        ?string $error = null,
    ): DatabaseSafetySnapshot {
        return new DatabaseSafetySnapshot(
            environment: $environment,
            connectionName: 'mysql',
            driver: 'mysql',
            host: '127.0.0.1',
            port: '3306',
            declaredDatabase: $declared,
            configuredDatabase: $configured,
            connectedDatabase: $connected,
            configurationCached: $cached,
            connectionError: $error,
        );
    }
}
