# Task 1.10 — Automated Quality Gate

## Outcome

Foundation work is protected by `php artisan quality:check`, with a reliable zero/non-zero exit status and compact per-check output suitable for local development and future CI.

## Covered foundations

The gate reuses the complete Laravel test suite and existing contract tests for database safety, authentication, RBAC, audit logging, number series, financial years, statuses, organization scope, and business regression. It additionally checks authenticated route coverage, stale RBAC mappings, permission-template consistency, audit-route read-only behavior, changed migration safety, routes, Blade compilation, changed-file PHP syntax, Pint, and Git whitespace.

Database safety is read-only. A protected live database is expected to report `BLOCKED / NOT ARMED`, which is a safe pass. A testing SQLite memory database or approved disposable database may be allowed while remaining unarmed. The gate never arms `DatabaseSafetyGuard` and never runs migrations or live data mutations.

## Result semantics

`PASS` means the check completed successfully. `FAIL` means the gate returns non-zero and prints an actionable reason. `SKIP` is limited to checks explicitly omitted by `--quick`; quick mode is not a substitute for the full gate.

## Verification record

The final verification record belongs with the commit that introduces this command: full tests, safety check, routes, Blade, lint, Pint, whitespace, maintenance-mode state, commit, push, and final `HEAD == origin/main` status must be recorded in the handoff. No live migration is required for this task.
