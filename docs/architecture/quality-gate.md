# ERP Quality Gate

The authoritative developer check is:

```text
php artisan quality:check
```

The command is read-only with respect to live business data. It never calls a destructive authorization method, runs migrations, allocates numbers, writes RBAC assignments, changes financial years, creates audit records, truncates data, or enters maintenance mode.

Checks run in a deterministic order and print one `PASS`, `FAIL`, or `SKIP` result: database safety, RBAC route coverage, permission registry consistency, audit/foundation integrity, number series, financial year, migration safety, the complete test suite, routes, Blade compilation, changed-file PHP lint, task-scoped Pint, and `git diff --check`.

`BLOCKED / NOT ARMED` is the expected safe result for a protected live database. Tests must use the existing testing configuration or the approved disposable database policy; the gate does not fall back to live `blackgrd`. A critical child check failure returns a non-zero exit code. `--quick` only skips the full test suite and Blade cache and is not the authoritative pre-commit check.

RBAC coverage requires every authenticated route to resolve through the explicit route/action registry or an intentional exclusion. Named mappings that no longer resolve to a route fail the check. Permission templates and frontend overrides must reference canonical keys, and admin-only permissions remain outside the frontend catalog.

Changed migrations are inspected against `HEAD`. Destructive patterns such as drops, truncation, delete-all SQL, and reset/load behavior require explicit review and therefore fail the gate until handled deliberately. Historical migrations are not re-litigated by this check.

## CI usage

CI can run the same command from a checked-out repository:

```yaml
- run: php artisan quality:check
```

Before committing or pushing, run the full command, investigate every failure, and confirm the final Git diff is intentional. Use `-v` when child-command detail is needed.

## Troubleshooting

- A safety failure means the effective connection could not be verified or is unexpectedly armed; inspect `.env` and keep destructive confirmation variables unset.
- A test failure should be reproduced with `php artisan test`; database-backed tests must use `APP_ENV=testing` and the approved disposable workflow.
- RBAC failures require updating the explicit route/action registry and, for removed routes, deleting stale mappings.
- Pint checks only changed PHP files so legacy formatting debt is not silently rewritten. Fix the reported changed files rather than running a broad formatter.
- Migration failures require a reviewed, hash-pinned safety path before any database apply operation.
