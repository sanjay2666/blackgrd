# Destructive Database Command Safety

## Why this guard exists

A destructive development command once used a cached Laravel connection instead of the intended disposable override. The database was recovered from backup. This guard prevents a similar command from running unless the effective runtime connection is independently verified as disposable.

The guard never treats `APP_ENV=testing` or a single opt-in flag as sufficient proof.

## Protected operations

The following Artisan commands are intercepted before execution:

- `migrate:fresh`
- `migrate:reset`
- `migrate:refresh`
- `migrate:rollback`
- `db:wipe`
- `schema:load`

Laravel database connections also receive a pre-execution hook that rejects direct `DROP DATABASE`, `DROP SCHEMA`, `DROP TABLE`, `DROP VIEW`, `DROP INDEX`, `ALTER TABLE ... DROP`, `TRUNCATE`, and `CREATE DATABASE` statements unless the current process has already passed the destructive command preflight.

Do not run destructive SQL through PDO or another client to bypass this application-level control. Database permissions remain the final enforcement layer.

## Disposable naming rules

Only database names ending in one of these suffixes are eligible:

```text
_test
_testing
_tmp
_temp
_recovery
_disposable
```

Names such as `blackgrd`, `blackgrd_erp`, `production`, and `live` are always blocked. Production Laravel environments are blocked even when a database has a disposable suffix.

SQLite `:memory:` is allowed only for the `testing` environment. MySQL integration tests must use a separate allow-listed database such as `blackgrd_testing`.

## Preflight

Clear configuration cache and inspect the effective connection:

```bash
php artisan config:clear
php artisan db:safety-check
```

The command reports environment, connection, driver, host, port, environment-declared database, configured database, actual connected database, and configuration-cache state. It does not print credentials.

Exit code `0` means the database identity is eligible. It does not mean destructive execution is armed. A blocked, mismatched, unknown, unavailable, live, or production connection returns a non-zero exit code.

## Multi-step confirmation for destructive Artisan commands

For a verified disposable database, destructive commands additionally require both process-level values:

```text
DB_DESTRUCTIVE_OPERATIONS_ALLOWED=true
DB_DESTRUCTIVE_CONFIRM_DATABASE=blackgrd_testing
```

The confirmation database must exactly equal the actual connected database. Run the safety check again in the same effective environment, then run the intended command. Remove the opt-in values immediately afterward.

Never add local passwords or production credentials to source control. With cached configuration, prefer clearing the cache; otherwise the confirmation variables must be supplied by the process environment because `.env` is not loaded from cached configuration.

## PHPUnit separation

The default `phpunit.xml` uses SQLite `:memory:` and never points to `blackgrd`. The project test base verifies the real connection during application creation, before `RefreshDatabase`, `DatabaseMigrations`, or similar traits can run.

For MySQL integration tests:

1. Prepare an isolated database named `blackgrd_testing` or another allow-listed name.
2. Supply `APP_ENV=testing`, `DB_CONNECTION=mysql`, `DB_DATABASE=blackgrd_testing`, and credentials through an uncommitted local environment or CI secret store.
3. Run `php artisan config:clear`.
4. Run `php artisan db:safety-check` and require `ALLOWED`.
5. Run only the intended integration suite.

Do not mix SQLite characterization tests and MySQL integration tests in the same configuration.

## Disposable database utility

Interactive create-if-missing:

```bash
php artisan db:prepare-disposable blackgrd_testing
```

Interactive recreate:

```bash
php artisan db:prepare-disposable blackgrd_testing --recreate
```

Non-interactive use requires `--force` plus both confirmation environment values. The command rejects unsafe names, production, a target equal to the current database, connection mismatches, and unverified servers.

## Migration test procedure

1. Confirm the working tree and intended migration files.
2. Create a fresh verified backup of any database whose data matters.
3. Clear Laravel configuration cache.
4. Run `db:safety-check`; stop unless it reports the exact disposable database.
5. Arm the process with both confirmation values.
6. Run the migration command against only that disposable database.
7. Inspect schema and application tests.
8. Remove confirmation values and re-run `db:safety-check` before returning to normal work.

## Forbidden against live databases

Never run these against `blackgrd`, `blackgrd_erp`, production, or live data:

- `migrate:fresh`, `migrate:reset`, or `migrate:refresh`
- `db:wipe`
- destructive test database traits
- `DROP DATABASE`, `DROP TABLE`, `TRUNCATE`, or schema-reset scripts
- disposable preparation or recreation

## Backup-before-migration checklist

- Verify the effective database using the application connection.
- Record database name, host, port, charset, collation, and migration count.
- Create a timestamped dump outside the repository.
- Record size, modification time, and SHA-256.
- Import the dump into an isolated disposable database.
- Compare critical table counts and run integrity checks.
- Confirm binary logging or another point-in-time recovery mechanism.

## Emergency recovery checklist

1. Stop application writes and enter maintenance mode.
2. Preserve the original backup and create a checksum-verified copy.
3. Snapshot the incident-state database before overwriting it.
4. Verify the backup in an isolated recovery database.
5. Restore only after table counts and integrity checks pass.
6. Clear Laravel caches and verify the connected database.
7. Run read-only application smoke checks.
8. Preserve code diffs and incident evidence outside Git.
9. Investigate logs, uploads, notifications, and backup directories without manually reconstructing data until approved.
