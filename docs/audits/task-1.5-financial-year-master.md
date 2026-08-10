# Task 1.5 — Financial Year Master

Date: 2026-08-10
Database: `blackgrd`
Disposable verification: `blackgrd_schema_testing`

## Read-only audit

The existing convention is `CHAR(4)` and the helper returned the current Indian
accounting year as a two-digit pair. Live values were primarily `2627`, meaning
FY `2026-27`. `purchase_orders` contained three rows with `financial_year =
2026`; all had `purchased_on` dates in July 2026, so they deterministically map
to `2627`. No other valid distinct year was present.

Existing live distributions included: sale orders 1/1 at `2627`, sale-order
items 2/2, work orders 9/9, WPR 12/12, inspections 3/5, gate passes 3/5,
warehouse balance 15/27, warehouse inward 9/17, warehouse stock 10/17,
warehouse outward 6/10, purchase orders 3/3 at invalid `2026`, and purchase
order items 5/5 at `2026`.

Legacy null snapshots remain explicitly deferred where no deterministic date or
parent evidence was available: departments 3, warehouses 8, compartments 225,
item-yarn requirements 844, and multiple deleted/auxiliary stock and return
tables. These rows are not silently assigned.

## Implementation

Migration `2026_08_10_000002_create_financial_year_master` creates the master and
adds nullable/indexed `financial_year_id` compatibility links to active business
tables. It seeds the current company year from the verified current accounting
period (`2627` / `2026-27`) and backfills valid snapshots. The reviewed command
`db:apply-reviewed-financial-year` pins the migration hash and verifies database
identity, maintenance mode, write stop, backups, exact pending set and row
counts before execution.

`FinancialYearManager` owns validation and atomic current-year switching.
`FinancialYearResolver` owns trusted current-year lookup. Admin CRUD is exposed
under `/admin/financial-years`; all routes inherit organization context, so
cross-company direct URLs and forged current-year selections fail closed.

The reviewed live apply completed after maintenance mode, write-stop
confirmation, exact pending-set verification and checksum validation. Post-apply
read-only checks found one active current row, all three purchase orders linked
to FY `2627`, and the migration ledger entry present.

Verified backups (not committed):

| Kind | Path | Bytes | SHA-256 |
| --- | --- | ---: | --- |
| Full | `E:\backups\blackgrd\task-1.5-20260810_123000\full-blackgrd.sql` | 634264 | `d44fabbfc101c8f59be00cf0d76a4f0ef58149657b266bf451f22b56724b5d50` |
| Affected tables | `E:\backups\blackgrd\task-1.5-20260810_123000\task-1.5-affected-tables.sql` | 107914 | `fb7a52b4121fe7d1d254a83dc19497a8efed6cbb2b6c89fa0fb3e780c342eda8` |
| Migrations | `E:\backups\blackgrd\task-1.5-20260810_123000\migrations-table.sql` | 6575 | `a3dd172065963086a9ed5ab247e6e604d1c47780d28ad40414e4b7d21d5ec4fa` |

## Scope and deferred work

The implementation updates the existing helper and all active call sites that
already write `financial_year`, without changing operational status behavior.
Legacy fields are retained for compatibility and history. Number Series,
historical reconstruction for ambiguous nulls, and broader master cleanup remain
future work.

## Verification

Disposable `blackgrd_schema_testing` passed fresh migration, rollback of both
scope migrations, and re-migration. Direct PHPUnit passed 109 tests and 802
assertions, with 11 expected MySQL-specific skips. New Task 1.5 files passed
Pint; existing legacy formatting debt was not rewritten solely for formatting.
