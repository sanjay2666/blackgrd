<?php

namespace App\DatabaseSafety;

use Illuminate\Database\DatabaseManager;
use PDO;

class DisposableDatabaseManager
{
    public function __construct(
        private readonly DatabaseSafetyGuard $guard,
        private readonly DatabaseManager $databases,
    ) {}

    /**
     * @return array{created: bool, recreated: bool, already_existed: bool}
     */
    public function prepare(string $database, bool $recreate = false): array
    {
        $this->guard->assertDisposableTargetAuthorized($database);
        $snapshot = $this->guard->inspect();
        $this->guard->assertDisposableTargetAllowed($database, $snapshot);

        if (! in_array($snapshot->driver, ['mysql', 'mariadb'], true)) {
            throw new UnsafeDatabaseOperation('Disposable database preparation currently supports MySQL/MariaDB only.');
        }

        $pdo = $this->databases->connection($snapshot->connectionName)->getPdo();
        $alreadyExisted = $this->databaseExists($pdo, $database);
        $identifier = '`'.str_replace('`', '``', $database).'`';

        if ($alreadyExisted && ! $recreate) {
            return ['created' => false, 'recreated' => false, 'already_existed' => true];
        }

        if ($alreadyExisted) {
            $pdo->exec("DROP DATABASE {$identifier}");
        }

        $pdo->exec("CREATE DATABASE {$identifier} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

        return [
            'created' => ! $alreadyExisted,
            'recreated' => $alreadyExisted,
            'already_existed' => $alreadyExisted,
        ];
    }

    private function databaseExists(PDO $pdo, string $database): bool
    {
        $statement = $pdo->prepare(
            'SELECT 1 FROM information_schema.schemata WHERE schema_name = :database LIMIT 1'
        );
        $statement->execute(['database' => $database]);

        return (bool) $statement->fetchColumn();
    }
}
