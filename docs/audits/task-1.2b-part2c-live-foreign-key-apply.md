# Task 1.2B-Part 2C — Controlled Live Foreign-Key Apply

Date: 2026-08-03
Project: `E:\projects\blackgrd`
Live database: `blackgrd`
Result: successful; application returned to live mode.

## Scope

Only these reviewed migrations were applied:

1. `2026_08_03_000001_add_parent_foreign_key_to_work_orders_table.php`
2. `2026_08_03_000002_add_critical_foreign_keys_to_warehouse_tables.php`

The Purchase Order tables and their MyISAM engines were not changed. No signedness, status, ID, route, model, Blade, or unrelated schema repair was performed.

## Pre-apply verification

The Laravel runtime declared, configured, and connected to `blackgrd`. The generic database safety check returned `BLOCKED`, as required for a live database. Only the two reviewed migrations were pending.

| Relationship | Child type / nullability | Parent type | Child/parent engine | Rows | Nulls | Sentinel `0` | Orphans |
| --- | --- | --- | --- | ---: | ---: | ---: | ---: |
| `work_orders.parent_work_order_id → work_orders.id` | `int(10) unsigned`, nullable | `int(10) unsigned` PK | InnoDB / InnoDB | 9 | 1 | 0 | 0 |
| `warehouse_compartments.warehouse_id → warehouses.id` | `bigint(20) unsigned`, required | `bigint(20) unsigned` PK | InnoDB / InnoDB | 225 | 0 | 0 | 0 |
| `warehouse_item_stocks.warehouse_item_id → warehouse_in_items.id` | `bigint(20)` signed, nullable | `bigint(20)` signed PK | InnoDB / InnoDB | 17 | 0 | 0 | 0 |

All parent columns had leading unique/primary indexes. The reviewed constraint names did not exist, and neither reviewed migration had a live ledger row. Application code creates these references from already-loaded parent records; the corresponding model/controller paths were rechecked.

Approved migration hashes:

- Work Order: `13E5B7390AFA6107AE53F998AB4145A9F3BC1B56270632666367751FCA9A3602`
- Warehouse: `339366D0831484EDB9E2F16C9EA1A9AEB86FD9F7EF16A26D4A93EE28DF2B3ADC`

## Write freeze and backups

`php artisan down` was run before backup/apply. Elevated OS inspection found no active `artisan queue:work`, `queue:listen`, `schedule:work`, or `schedule:run` process and no matching Windows scheduled task, so no process termination or task disablement was needed.

Recovery directory:

`E:\projects\blackgrd\storage\app\recovery\task_1_2b_part2c_20260803_195015`

| Backup | Bytes | SHA-256 |
| --- | ---: | --- |
| `blackgrd_full.sql` | 605,333 | `7DAFA190E87C14678E7017CDFDE5B3577EFA386D4A718EEDF15E060D410BC43A` |
| `work_orders.sql` | 7,162 | `BD3F761FDA8A2E0A2B786EB6261523AFA96E6889759E99B71858F6105321F4B6` |
| `warehouse_compartments.sql` | 21,391 | `A7F546E6B535AD11226A0B9E743B8B6AF53F0859A943A0451BFE27702932D176` |
| `warehouse_item_stocks.sql` | 12,130 | `E59A44235A5F906B5FAA3617DB2FFC8AB920155F2B83439BF00F37C8C6EA4C5D` |
| `migrations.sql` | 5,458 | `6B1761B175094ED6D2AC6B0CF9616A99EFB66F4AC5636AD818159C30180F1313` |

All five files were newly created, non-empty, readable, and were not committed to Git.

## Reviewed execution authorization

`db:apply-reviewed-foreign-keys` is a deliberately non-generic command. It accepts no migration path or filename input and enforces all of the following before execution:

- MySQL runtime with environment-declared, configured, and connected database all exactly `blackgrd`.
- Active Laravel maintenance mode.
- Exact hard-coded migration filenames and SHA-256 hashes.
- Repository pending set exactly equal to those two migrations; any additional or missing pending migration blocks execution.
- InnoDB engines, exact column types/nullability, unique parent indexes, absent constraint names, zero sentinels, and zero orphans.
- Explicit `--execute --confirm-database=blackgrd` confirmation.
- Direct Migrator execution using only the two verified absolute file paths.
- A process-local execution scope, finalized with `authorization_revoked_at`; no environment variable, persistent token, or generic guard bypass is created.

The generic safety guard was not disabled or weakened. `migrate`, `migrate:fresh`, rollback, reset, refresh, and wipe remain blocked on live `blackgrd`.

The finalized execution audit is stored at:

`E:\projects\blackgrd\storage\logs\reviewed-foreign-key-migration-20260803_142353.json`

It records status `succeeded`, both migration hashes, and the authorization revocation timestamp.

## Applied result

The exact execution was:

```powershell
php artisan db:apply-reviewed-foreign-keys --execute --confirm-database=blackgrd
```

Both migrations completed without error. No rollback was run.

| Constraint | Child → parent | Delete / update |
| --- | --- | --- |
| `fk_wo_parent` | `work_orders.parent_work_order_id → work_orders.id` | RESTRICT / RESTRICT |
| `fk_wc_warehouse` | `warehouse_compartments.warehouse_id → warehouses.id` | RESTRICT / RESTRICT |
| `fk_wis_inward` | `warehouse_item_stocks.warehouse_item_id → warehouse_in_items.id` | RESTRICT / RESTRICT |

Indexes:

- Reused: `work_orders_parent_index`
- Added: `idx_wc_warehouse`
- Added: `idx_wis_inward`

## Before/after invariants

| Table | Rows before | Rows after | Auto-increment before | Auto-increment after |
| --- | ---: | ---: | ---: | ---: |
| `work_orders` | 9 | 9 | 18 | 18 |
| `warehouses` | 8 | 8 | 9 | 9 |
| `warehouse_compartments` | 225 | 225 | 226 | 226 |
| `warehouse_in_items` | 17 | 17 | 23 | 23 |
| `warehouse_item_stocks` | 17 | 17 | 22 | 22 |
| `purchase_orders` | 3 | 3 | 4 | 4 |
| `purchase_order_items` | 5 | 5 | 6 | 6 |
| `migrations` | 65 | 67 | 76 | 78 |

The two migration ledger rows were added as batches 27 and 28. The database remained at 65 base tables and 1,337 columns; total enforced foreign keys changed only from 1 to 4. All three post-apply orphan and sentinel counts remained zero. `CHECK TABLE` returned `OK` for all reviewed child/parent tables, both purchase tables, and `migrations`.

Purchase Order tables remained MyISAM and were otherwise untouched.

## Read-only application verification

A recovery-folder smoke runner used the live application container with `session=array`, an in-memory authenticated guard, and a connection-level blocker that rejected any `INSERT`, `UPDATE`, `DELETE`, or DDL statement. It directly resolved each registered GET route, invoked the controller, and rendered the resulting Blade response.

| Check | Result |
| --- | --- |
| `/show-workorders` | PASS — 200, rendered 191,868 bytes |
| Warehouse `/show` | PASS — 200, rendered 39,662 bytes |
| `/admin/ware-house-compartments` | PASS — 200, rendered 64,788 bytes |
| `/show-warehouse-item-stock` | PASS — 200, rendered 41,696 bytes |
| Work Order parent/child access | PASS — child 2 resolved parent 1 |

`php artisan route:list` completed successfully with 302 routes.

The first full test invocation correctly received maintenance-mode 503 responses in seven HTTP tests. Without bringing the live application up, the suite was rerun in an isolated test process using the array maintenance/cache driver:

- Full suite: **35 passed, 5 skipped, 458 assertions**.
- Disposable MySQL FK suite: **5 passed, 30 assertions**.
- Pint: passed.
- `git diff --check`: passed.

The five default-suite skips are the MySQL-specific foreign-key tests; they passed separately against `blackgrd_schema_testing`.

## Final state

After all verification, `php artisan up` completed successfully. Maintenance mode is OFF. No queue/scheduler process needed to be resumed. Generic `php artisan db:safety-check` still returns `BLOCKED` on live `blackgrd`.
