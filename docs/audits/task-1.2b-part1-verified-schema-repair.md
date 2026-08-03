# Task 1.2B-Part 1 — Verified Schema Repair Files

Date: 2026-08-03
Scope: repository repair, allow-listed disposable migration verification, and the separately approved live colour-column removal.

## Agents migration

The applied migration ledger entry `2026_07_17_000006_create_agents_table` had no repository file. The restored migration mirrors the live table contract:

| Column | Migration definition |
| --- | --- |
| `id` | unsigned incrementing integer, primary key |
| `individual_id` | signed integer, not null, default `0` |
| `agent_from` | varchar(255), not null |
| `agent_to` | varchar(255), not null |
| `created_by` | signed integer, not null |
| `modified_by` | signed integer, not null |
| `created` | datetime, not null |
| `modified` | datetime, not null |
| `status` | enum `Active`, `Inactive`, `Deleted`; default `Active` |

No foreign key or extra index was added because neither exists in the live table. Since the migration is already recorded as applied, it will not recreate the live table. Its `down()` uses `dropIfExists` for fresh-database rollback safety.

## Duplicate states migration

`2026_07_17_000002_create_states_table.php` remains the only owner of the `states` table. The later applied file `2026_07_17_122153_create_states_table.php` is retained by its exact filename as a documented no-op. Its rollback is also a no-op, so rolling back that ledger entry cannot drop the valid table created by the canonical migration.

## Cache tables

Verified state:

- Live `.env`: `CACHE_STORE=file`.
- Effective Laravel cache driver: `file`.
- `cache` and `cache_locks` are absent from the live database.
- The cache migration is present in the applied ledger.
- `.env.example` and the configuration fallback select `database` when a deployment does not explicitly choose a cache store.

Decision after final review: retain the standard committed Laravel cache migration and restore both configuration files to their committed `database` defaults. The current deployment continues to use `file` through its explicit live `.env` value, while fresh databases include the framework tables needed if database caching is selected later. Schema parity alone is not a reason to change application configuration or disable a standard framework migration.

## `colours.individual_id`

No active use was found in the current Colour model, relationships, controllers, queries, validation, Blade forms, reports, filters, APIs, AJAX consumers, or JavaScript. `CommonController::list_master_color()` serializes Colour records, but all located consumers use only the returned `name`; no consumer reads `individual_id`.

The live table has 3 rows and all 3 have `individual_id = NULL`. The old ERP did use this column for vendor-scoped colours, but that behavior is not present in the current project. The old ERP remains unchanged.

`database/manual/schema-repair/remove_colours_individual_id.sql` contains pre-checks, the separately approved drop statement, and post-checks. It also documents a table-only `mysqldump` and SHA-256 verification. After disposable verification and a fresh live-table backup, the approved script was executed once on `blackgrd`.

## `work_process_requirements.warehouse_balance_item_id`

The field was present only in the repository create migration; it is absent from the live table. Current code reads it defensively through an accessor and optional fallback paths, but no current insert/update assignment was found. Active stock linkage is stored in `wis_id`.

The old ERP database contains the field, but all 21,935 rows have it null; `wis_id` is populated on 3,035 rows. Old and current controllers treat `warehouse_balance_item_id` as an optional WarehouseBalanceItem shortcut and fall back to attribute-based balance lookup when it is absent.

Decision: the never-populated optional field is obsolete for the current schema. Remove it from the original create migration so a fresh database matches production. Do not add it to the live database. Existing accessor and fallback reads remain unchanged to avoid changing current business logic.

## Disposable MySQL verification

Exact connected database: `blackgrd_schema_testing`.

- `migrate:fresh`: successful for all 65 migration files.
- Full `migrate:rollback`: successful; only the empty `migrations` table remained.
- Full `migrate`: successful after a clean rollback; 65 ledger rows are present in batch 1.
- Final table count: 67, including the `migrations`, `cache`, and `cache_locks` framework tables.
- `agents` exists and its columns match the live contract.
- `states` exists exactly once with the canonical migration's structure.
- The duplicate states migration completed as a no-op and its rollback did not drop the canonical table.
- `work_process_requirements.warehouse_balance_item_id` is absent.
- `cache` and `cache_locks` are present.
- `CHECK TABLE` returned OK for agents, states, work process requirements, cache, and cache locks.

The first plain re-migration attempt exposed a fail-closed guard gap: a historical migration executes `ALTER TABLE ... DROP`, but `migrate` was not classified for destructive-command preflight. The guard blocked that SQL before execution. `migrate` is now included in the protected command list, with an automated test and documentation, so it requires an exact allow-listed database plus process-scoped confirmation. A subsequent clean full rollback and full migration passed.

The disposable database was retained for possible later schema verification. It is isolated, empty of business data, and remains allow-listed by name.

## Approved live colour repair

Backup created before the change:

- Path: `E:\projects\blackgrd\storage\app\recovery\task_1_2b_part1\colours_before_individual_id_removal_20260803_180232.sql`
- Size: 2,637 bytes
- SHA-256: `8FB2CCECE98788794B0C4C394C61BF99C562F9415BEB57EA0393C3FFC1925817`

Preconditions matched: connected database `blackgrd`, 3 total rows, 0 non-null values, and no active current-project use. The only live schema statement executed was:

```sql
ALTER TABLE `colours` DROP COLUMN `individual_id`;
```

Post-verification found 3 unchanged colour records, zero matching columns in `information_schema`, and `CHECK TABLE colours` returned OK. Anonymous HTTP GET smoke checks reached 200 responses through the expected authentication redirects for the admin colour index, create page, and master-colour endpoint; no create/update/delete request was made.

## Database application scope

- Agents, states, cache, and WPR migrations were executed only against `blackgrd_schema_testing`.
- No migration, rollback, fresh, wipe, or full schema operation ran against live `blackgrd`.
- The sole live schema change was removal of `colours.individual_id` after backup and verification.
- `work_process_requirements.warehouse_balance_item_id` was not added or dropped on live.
- No business rows, IDs, foreign keys, signedness, status values, or old ERP files were changed.
