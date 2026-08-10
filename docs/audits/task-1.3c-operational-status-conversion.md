# Task 1.3C operational status conversion audit

Date: 2026-08-10

Live database: `blackgrd`

Disposable database: `blackgrd_schema_testing`

Result: completed successfully

## Scope and safety gates

- Work was limited to incomplete Task 1.3C. Original Prompt 7 was not started.
- `DatabaseSafetyGuard` remained enabled and unchanged in strength.
- Empty-schema verification completed `migrate:fresh`, full rollback, and re-migration on exact `blackgrd_schema_testing`.
- A populated clone of live data then passed the hash-pinned eight-migration apply, exact eight-migration rollback, and re-apply cycle.
- Live `blackgrd` was put in maintenance mode. No Laravel queue/Horizon/scheduler PHP process and no matching Windows scheduled task was present.
- Live preflight verified exactly eight pending Task 1.3C migrations, matching hashes, three backups, exact connected database, and three explicit exclusions.
- No unrestricted `php artisan migrate` and none of `migrate:fresh`, `migrate:refresh`, `migrate:reset`, `db:wipe`, or `schema:load` ran on live.

## Applied migrations and reviewed hashes

| Migration | SHA-256 |
| --- | --- |
| `2026_08_03_100001_add_sale_order_operational_statuses` | `dabbeffdcb522ae806f6108ea926df200322ded0037d4482d85125369a4e426e` |
| `2026_08_03_100002_add_purchase_order_operational_statuses` | `3da71f736d0c0534d028ca74384f3fe8d51c58900a5e30cb635fdde45d7c1c47` |
| `2026_08_03_100003_add_work_order_operational_statuses` | `a2f2f57856f3d08c019fee233d81d2c45a834348c6fcf8acc549701812b23d0b` |
| `2026_08_03_100004_add_work_requirement_operational_statuses` | `26a71e28d10022091fced52f22bf411f570e350fc00270be7254057a2bbe5b03` |
| `2026_08_03_100005_add_inspection_operational_statuses` | `2138feae9d4fcb72dbb6e059963dce6c737830818d6635e7a5fda145cff3570b` |
| `2026_08_03_100006_add_inventory_operational_statuses` | `d4e395ddd6b9287df1892955cb691ff1de4b313c2b9d13509e91775839253da9` |
| `2026_08_03_100007_add_gate_pass_operational_status` | `3bcf3cabcb66bc0d1cfaf791b4ee726a3f97edc7baef18a08798624b4fa159f2` |
| `2026_08_03_100008_add_job_work_operational_statuses` | `20acee8184858142996a854f03cf2f0eca137d6fb10cc289290a751e321703cc` |

## Verified backups

| Kind | Exact path | Bytes | SHA-256 |
| --- | --- | ---: | --- |
| Full database | `E:\backups\blackgrd\task-1.3c-20260810_073316\full-blackgrd.sql` | 613687 | `078be935d8422081877080b23695f9f8368606b869b2b03df8b940b751df69c4` |
| Task-affected tables | `E:\backups\blackgrd\task-1.3c-20260810_073316\task-1.3c-affected-tables.sql` | 81561 | `42df1db3d0fe734ba9e6194d0be6ec6ccbd9c6f2962a0691d598e158bb0c3480` |
| `migrations` table | `E:\backups\blackgrd\task-1.3c-20260810_073316\migrations-table.sql` | 5943 | `3e3947d3e5a5c7ecfeaf62b06783f58fbf9447dfa301c7424b8cea3b52f270b2` |

Manifest: `E:\backups\blackgrd\task-1.3c-20260810_073316\backup-manifest.json`

## Backfill results

| Canonical column | Non-null rows | Distribution |
| --- | ---: | --- |
| `sale_orders.document_status` | 1 | `in_production=1` |
| `purchase_orders.document_status` | 3 | `draft=1`, `received=2` |
| `purchase_order_items.receipt_status` | 5 | `pending=2`, `received=3` |
| `work_orders.execution_status` | 9 | `created=1`, `material_requested=2`, `material_allotted=1`, `ready=1`, `started=3`, `completed=1` |
| `work_orders.inspection_status` | 9 | `pending=8`, `completed=1` |
| `work_process_requirements.requirement_status` | 12 | `accepted=6`, `denied=6` |
| `work_process_requirements.allocation_status` | 12 | `allocated=6`, `cancelled=6` |
| `work_inspections.inspection_status` | 5 | `pending=5` |
| `work_inspections.inspection_result` | 5 | `completed=5` |
| `work_inspection_details.inspection_result` | 9 | `completed=9` |
| `warehouse_in_items.movement_status` / `receipt_status` | 17 / 17 | `posted=17` / `received=17` |
| `warehouse_out_items.movement_status` | 8 | `posted=8`; 2 deleted rows are `NULL` |
| `warehouse_balance_items.movement_status` | 27 | `posted=27` |
| `warehouse_item_stocks.allocation_status` | 17 | `allocated=4`, `unallocated=13` |
| `gate_passes.gate_pass_status` | 4 | `issued=1`, `received=3`; 1 deleted row is `NULL` |
| Job Mill Work canonical columns | 0 | Tables currently contain no rows; columns and read path verified |

No unrecognized active legacy value was silently converted. Explicitly excluded: deleted `warehouse_out_items` IDs 1 and 2, and deleted `gate_passes` ID 6. Their operational statuses remain `NULL`.

## Preservation evidence

Before/after ordered ID SHA-256, row count, selected quantity sums, and auto-increment values were identical on both disposable cycles and live apply. Verified row counts were:

| Tables | Row counts |
| --- | --- |
| Sale Order | orders 1; items 2 |
| Purchase Order | orders 3; items 5 |
| Work Order / WPR | work orders 9; requirements 12 |
| Inspection | headers 5; details 9 |
| Warehouse | inward 17; outward 10; balance 27; stock 17 |
| Gate Pass | 5 |
| Job Mill Work | dispatch headers 0; dispatch lines 0; receipt headers 0; receipt lines 0 |

Preserved quantity totals included PO ordered `14666.00`, PO received `10289.00`, WPR required `4004.00`, WPR allotted `1693.00`, warehouse inward `21467.00`, outward `2800.00`, balance `44716.00`, and inspected stock balance `20887.00`. No row, ID, quantity, or auto-increment value changed.

## Read-only live smoke verification

- Sale Order, Purchase Order, Work Order, WPR, Inspection, Warehouse, and Gate Pass Eloquent reads returned their expected enum casts.
- Canonical scopes returned SO in-production 1, PO received 2, WO started 3, and WPR accepted 6.
- Job Mill Work tables are empty; the canonical column and query path exist and return zero rows.
- No business create/update/delete smoke operation was performed on live data.

## Test and tooling results

- Focused Task 1.3C: 20 passed, 106 assertions.
- Full suite: 94 passed, 11 skipped, 787 assertions. Baseline was 94 passed, 11 skipped, 784 assertions.
- Task-scoped Pint: passed for all new Task 1.3C enums, domain services/actions, events, exception, commands, migrations, provider changes, and tests. Legacy controller-wide formatting debt remains outside the formatting gate; no unrelated file was added merely to satisfy Pint.
- `php artisan route:list`: passed, 286 routes.
- `php artisan route:cache`, `route:clear`, `view:cache`, and `view:clear`: passed.
- `git diff --check`: passed.

## Machine-readable evidence

- Disposable audit: `storage/logs/task-1.3c-disposable-verification-20260810_020503.json`
- Live apply audit: `storage/logs/task-1.3c-live-apply-20260810_020753.json`

The storage logs are runtime evidence and are not committed. This report preserves their material results in the repository.
