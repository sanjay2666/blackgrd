<?php

namespace App\DatabaseSafety;

use Illuminate\Database\Connection;
use Illuminate\Database\DatabaseManager;
use Illuminate\Foundation\Application;
use Illuminate\Support\Env;
use PDO;
use Throwable;

class DatabaseSafetyGuard
{
    private bool $destructiveExecutionAuthorized = false;

    private ?string $authorizedDisposableTarget = null;

    public function __construct(
        private readonly Application $app,
        private readonly DatabaseManager $databases,
    ) {}

    public function inspect(?string $connectionName = null): DatabaseSafetySnapshot
    {
        $connectionName ??= (string) config('database.default');
        $configuration = (array) config("database.connections.{$connectionName}", []);
        $driver = (string) ($configuration['driver'] ?? '');
        $configuredDatabase = $this->normalizeDatabaseName($configuration['database'] ?? null);
        $declaredDatabase = $this->normalizeDatabaseName(Env::get('DB_DATABASE'));
        $connectedDatabase = null;
        $connectionError = null;

        try {
            $connection = $this->databases->connection($connectionName);
            $connectedDatabase = $this->readConnectedDatabase($connection, $driver, $configuredDatabase);
        } catch (Throwable $exception) {
            $connectionError = $exception->getMessage();
        }

        return new DatabaseSafetySnapshot(
            environment: $this->app->environment(),
            connectionName: $connectionName,
            driver: $driver,
            host: $this->normalizeDatabaseName($configuration['host'] ?? null),
            port: $this->normalizeDatabaseName($configuration['port'] ?? null),
            declaredDatabase: $declaredDatabase,
            configuredDatabase: $configuredDatabase,
            connectedDatabase: $connectedDatabase,
            configurationCached: $this->app->configurationIsCached(),
            connectionError: $connectionError,
        );
    }

    public function evaluate(DatabaseSafetySnapshot $snapshot): DatabaseSafetyResult
    {
        $reasons = [];

        if ($snapshot->environment === 'production') {
            $reasons[] = 'The production Laravel environment never permits destructive database operations.';
        }

        if ($snapshot->connectionError !== null) {
            $reasons[] = 'The effective database connection is unavailable.';
        }

        if ($snapshot->configuredDatabase === null) {
            $reasons[] = 'The configured database name is empty or unknown.';
        }

        if ($snapshot->connectedDatabase === null) {
            $reasons[] = 'The actual connected database name is empty or unknown.';
        }

        if (
            $snapshot->configuredDatabase !== null
            && $snapshot->connectedDatabase !== null
            && $snapshot->configuredDatabase !== $snapshot->connectedDatabase
        ) {
            $reasons[] = 'Configured and connected database names do not match.';
        }

        if (
            $snapshot->declaredDatabase !== null
            && $snapshot->connectedDatabase !== null
            && $snapshot->declaredDatabase !== $snapshot->connectedDatabase
        ) {
            $reasons[] = 'Environment-declared and connected database names do not match.';
        }

        if ($snapshot->connectedDatabase !== null && ! $this->isAllowedDatabase($snapshot)) {
            $reasons[] = "Database [{$snapshot->connectedDatabase}] is not an allow-listed disposable database.";
        }

        return new DatabaseSafetyResult(
            snapshot: $snapshot,
            allowed: $reasons === [],
            reasons: $reasons,
            executionArmed: $this->executionConfirmationMatches($snapshot->connectedDatabase),
        );
    }

    public function check(?string $connectionName = null): DatabaseSafetyResult
    {
        return $this->evaluate($this->inspect($connectionName));
    }

    public function assertTestEnvironmentSafe(?string $connectionName = null): void
    {
        $result = $this->check($connectionName);

        if ($result->snapshot->environment !== 'testing') {
            throw new UnsafeDatabaseOperation('PHPUnit bootstrap requires APP_ENV=testing.');
        }

        if (! $result->allowed) {
            throw new UnsafeDatabaseOperation($this->failureMessage('PHPUnit database preflight', $result));
        }
    }

    public function authorizeDestructiveCommand(string $command): DatabaseSafetyResult
    {
        $result = $this->check();

        if (! $result->allowed) {
            throw new UnsafeDatabaseOperation($this->failureMessage("Destructive command [{$command}]", $result));
        }

        if (! $result->executionArmed) {
            throw new UnsafeDatabaseOperation(
                "Destructive command [{$command}] is not armed. Set DB_DESTRUCTIVE_OPERATIONS_ALLOWED=true "
                ."and DB_DESTRUCTIVE_CONFIRM_DATABASE={$result->snapshot->connectedDatabase} for this process."
            );
        }

        $this->destructiveExecutionAuthorized = true;

        return $result;
    }

    public function assertDestructiveSqlAllowed(string $query): void
    {
        if (! $this->isDestructiveSql($query)) {
            return;
        }

        if (! $this->destructiveExecutionAuthorized) {
            throw new UnsafeDatabaseOperation(
                'Direct destructive SQL was blocked because no verified destructive Artisan preflight authorized this process.'
            );
        }
    }

    public function revokeDestructiveAuthorization(): void
    {
        $this->destructiveExecutionAuthorized = false;
    }

    public function authorizeDisposableTarget(string $database, DatabaseSafetySnapshot $server): void
    {
        $this->assertDisposableTargetAllowed($database, $server);
        $this->authorizedDisposableTarget = $database;
    }

    public function assertDisposableTargetAuthorized(string $database): void
    {
        if ($this->authorizedDisposableTarget === null || ! hash_equals($database, $this->authorizedDisposableTarget)) {
            throw new UnsafeDatabaseOperation(
                'Disposable database operation was blocked because interactive or environment confirmation is missing.'
            );
        }
    }

    public function revokeDisposableTargetAuthorization(): void
    {
        $this->authorizedDisposableTarget = null;
    }

    public function isDestructiveCommand(string $command): bool
    {
        return in_array($command, (array) config('database-safety.destructive_commands', []), true);
    }

    public function isDestructiveSql(string $query): bool
    {
        return preg_match(
            '/\b(?:DROP\s+(?:DATABASE|SCHEMA|TABLE|VIEW|INDEX)|TRUNCATE\s+(?:TABLE\s+)?|CREATE\s+DATABASE|ALTER\s+TABLE\b[^;]*\bDROP\b)/i',
            $query
        ) === 1;
    }

    public function isAllowedDisposableName(?string $database): bool
    {
        if ($database === null || trim($database) === '') {
            return false;
        }

        $normalized = strtolower(trim($database));

        if (in_array($normalized, (array) config('database-safety.blocked_names', []), true)) {
            return false;
        }

        foreach ((array) config('database-safety.allowed_suffixes', []) as $suffix) {
            if (str_ends_with($normalized, strtolower((string) $suffix))) {
                return true;
            }
        }

        return false;
    }

    public function executionConfirmationMatches(?string $database): bool
    {
        if ($database === null) {
            return false;
        }

        $enabled = filter_var(
            Env::get('DB_DESTRUCTIVE_OPERATIONS_ALLOWED', false),
            FILTER_VALIDATE_BOOL
        );
        $confirmation = $this->normalizeDatabaseName(Env::get('DB_DESTRUCTIVE_CONFIRM_DATABASE'));

        return $enabled && hash_equals($database, (string) $confirmation);
    }

    public function assertDisposableTargetAllowed(string $database, DatabaseSafetySnapshot $server): void
    {
        if (! preg_match('/\A[A-Za-z0-9_]+\z/', $database)) {
            throw new UnsafeDatabaseOperation('Disposable database names may contain only letters, numbers, and underscores.');
        }

        if (! $this->isAllowedDisposableName($database)) {
            throw new UnsafeDatabaseOperation("Requested database [{$database}] is not allow-listed as disposable.");
        }

        if ($server->environment === 'production') {
            throw new UnsafeDatabaseOperation('Disposable database preparation is blocked in production.');
        }

        if ($server->connectionError !== null || $server->connectedDatabase === null) {
            throw new UnsafeDatabaseOperation('The connected database server could not be verified.');
        }

        if ($server->configuredDatabase !== $server->connectedDatabase) {
            throw new UnsafeDatabaseOperation('Configured and connected database names do not match.');
        }

        if ($server->declaredDatabase !== null && $server->declaredDatabase !== $server->connectedDatabase) {
            throw new UnsafeDatabaseOperation('Environment-declared and connected database names do not match.');
        }

        if (strcasecmp($database, $server->connectedDatabase) === 0) {
            throw new UnsafeDatabaseOperation('The currently connected database cannot be prepared or recreated.');
        }
    }

    public function failureMessage(string $operation, DatabaseSafetyResult $result): string
    {
        return $operation.' BLOCKED: '.implode(' ', $result->reasons);
    }

    private function isAllowedDatabase(DatabaseSafetySnapshot $snapshot): bool
    {
        if ($snapshot->driver === 'sqlite') {
            return $snapshot->environment === 'testing' && $snapshot->connectedDatabase === ':memory:';
        }

        return $this->isAllowedDisposableName($snapshot->connectedDatabase);
    }

    private function readConnectedDatabase(
        Connection $connection,
        string $driver,
        ?string $configuredDatabase,
    ): ?string {
        $pdo = $connection->getPdo();

        return match ($driver) {
            'mysql', 'mariadb' => $this->normalizeDatabaseName($pdo->query('SELECT DATABASE()')->fetchColumn()),
            'pgsql' => $this->normalizeDatabaseName($pdo->query('SELECT current_database()')->fetchColumn()),
            'sqlsrv' => $this->normalizeDatabaseName($pdo->query('SELECT DB_NAME()')->fetchColumn()),
            'sqlite' => $this->readSqliteDatabase($pdo, $configuredDatabase),
            default => null,
        };
    }

    private function readSqliteDatabase(PDO $pdo, ?string $configuredDatabase): ?string
    {
        $rows = $pdo->query('PRAGMA database_list')->fetchAll(PDO::FETCH_ASSOC);
        $main = collect($rows)->firstWhere('name', 'main');
        $file = $this->normalizeDatabaseName($main['file'] ?? null);

        if ($file === null && $configuredDatabase === ':memory:') {
            return ':memory:';
        }

        return $file;
    }

    private function normalizeDatabaseName(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $normalized = trim((string) $value);

        return $normalized === '' ? null : $normalized;
    }
}
