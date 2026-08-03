# Task 1.2C-Part 2 — Model and Relationship Stabilization

Date: 2026-08-03  
Project: `E:\projects\blackgrd`  
Old ERP reference: `E:\xampp\htdocs\erp` (read-only)

## Scope and safety result

This task inspected the three named missing relationship-target classes, the four relationships known to reference absent columns, and the backend dependency graph for Sale Order Work Order Details. No migration, table, column, foreign key, status conversion, data repair, or Lab Test change was made. Live `blackgrd` received only read-only `information_schema`, count, orphan, and sample queries.

`php artisan db:safety-check` connected to `mysql / blackgrd` and returned the required `BLOCKED` result. Relationship data tests ran only on allow-listed `blackgrd_schema_testing`, inside transactions.

## Missing target model decisions

| Reported class | Current table verification | Current/old purpose and usage | Decision |
|---|---|---|---|
| `SaleOrderItemPendingReason` | No table by that model name exists. The verified current table is `reasons`; PK `id int unsigned AUTO_INCREMENT`; references `sale_order_id`, `sale_order_item_id`, `work_order_id` (nullable signed int); status `enum('Active','Inactive','Deleted')`; timestamps `created_at`, `modified_at`; no `financial_year`; 2 rows, both active `cwo`, both linked to an existing Sale Order Item. | Current `SaleOrderController` writes and reads `Reason` for CWO and Work Order reasons. Old ERP `SaleOrderItem::CwoReason()` also targeted `Reason`, not `SaleOrderItemPendingReason`. | **Required relationship, wrong class name.** No new model created. `CwoReason()` now targets existing `Reason`, filters `reason_from_page=cwo` and `status=Active`, and orders by current `created_at`. |
| `WorkOrderItemDetail` | `work_order_item_details` is absent from current DB; row count not applicable. Current canonical `work_order_items` has PK `id int unsigned`, `work_order_id`, `sale_order_id`, `sale_order_item_id`, AID status, financial year/audit fields, and 9 rows. | Old model mapped legacy `work_order_item_details`, PK `woi_id`, and related through `work_order_id`. Current source had no caller for `WorkOrder::WorkOrderItemDetail()`. Current flows use `WorkOrder::WorkOrderItem()` and the `work_order_items` table. | **Legacy/dead target.** No empty model created. Unused invalid relationship removed. |
| `WorkProcessRequirementChangeHistory` | Old table name `work_process_requirement_changes_histories` is absent from current DB; row count not applicable. No current migration/model/controller writer exists. | Old ERP stored `wpr_id`, old/new lot values, actor, and creation time and used the relationship to prevent repeat return/edit actions. Current active Blade referenced a magic property, but the controller did not load it and Eloquent returned null because no attribute/relationship exists. | **Missing schema-backed feature, not a creatable model in this task.** No model created. The two no-op predicates were removed from the active Blade, preserving the current always-visible button behavior. The archived underscored Blade remains untouched. |

### Model configuration outcome

No model files were created. The required target classes already exist:

- `Reason`: table `reasons`, PK `id`, incrementing integer key, `$timestamps=false`. This is correct because the table has `created_at` plus nonstandard `modified_at`, not Laravel `updated_at`; status remains an enum-backed string with no artificial cast.
- `WorkOrderItem`: table `work_order_items`, PK `id`, incrementing integer, `$timestamps=false` for the legacy audit convention.
- `Individual`: table `individuals`, PK `id`; used as the verified warehouse actor/receiver target.

## Relationship correction matrix

| Model / relationship | Previous contract | Verified current evidence | Final contract |
|---|---|---|---|
| `SaleOrderItem::CwoReason()` | `hasMany(SaleOrderItemPendingReason, sale_order_item_id → id)`; class missing | `reasons.sale_order_item_id → sale_order_items.id`; 2/2 rows valid, zero orphans; controller and old ERP use `Reason` | `HasMany Reason`, FK `sale_order_item_id`, local key `id`, active CWO scope, newest first |
| `SaleOrderItem::WorkOrderItem()` | No inverse relationship | `work_order_items.sale_order_item_id` exists and is used throughout Work Order generation/details | Added `HasMany WorkOrderItem`, FK `sale_order_item_id`, local `id`, active scope; supplies the schema-backed portion of the pending Sale Order Details graph |
| `WorkOrder::saleOrderItem()` | `belongsTo` through absent `work_orders.sale_order_item_id` | Column absent; no active caller. Correct path is Work Order → Work Order Items → Sale Order Item | Removed as dead/invalid |
| `WorkOrder::WorkOrderItemDetail()` | `hasMany` missing model/table | Table absent; no active caller; current `WorkOrderItem()` is canonical | Removed as dead/legacy |
| `WorkProcessRequirement::WorkOrderItem()` | `belongsTo` through absent `work_process_requirements.work_order_item_id` | `work_process_requirements.work_order_id` and `work_order_items.work_order_id` exist. All 12 WPR rows have a valid Work Order and active Work Order Item; current maximum active items per referenced order is 1 | Corrected to `HasOne WorkOrderItem`, foreign/local `work_order_id`, active scope |
| `WarehouseOutItem::Individual()` | `hasOne` through absent `warehouse_out_items.ind_emp_id` | Actual `individual_id` is written from authenticated user's individual ID; 10 non-null rows, zero individual orphans | Corrected to `BelongsTo Individual`, child FK `individual_id`, owner `id` |
| `WarehouseBalanceItem::Individual()` | `hasOne` through absent `warehouse_balance_items.ind_emp_id` | No `individual_id` or `ind_emp_id`; active screens use `ReceiverIndividual` and actual `receiver_id`; 27 non-null receiver rows, zero individual orphans | Invalid duplicate removed; two stale eager-load entries removed |
| `WarehouseBalanceItem::ReceiverIndividual()` | `hasOne(Individual, id → receiver_id)` | This record stores the child reference in `receiver_id` | Corrected direction/type to `BelongsTo Individual`, child FK `receiver_id`, owner `id` |

All corrected relationships now declare `HasMany`, `HasOne`, or `BelongsTo` return types. Nullable foreign values were verified not to throw or accidentally return an unrelated record.

## Removed/deprecated usage

- Removed `WorkOrder::saleOrderItem()`; no current controller/Blade eager-load or property caller was found.
- Removed `WorkOrder::WorkOrderItemDetail()`; no current caller and no current table exists.
- Removed `WarehouseBalanceItem::Individual()` and its two eager-load requests in `WarehouseItemController`; current views already use null-safe `ReceiverIndividual`.
- Removed `WorkProcessRequirementChangeHistory` checks from active `frontend/workorder/show-workorders.blade.php`. With no attribute, relationship, table, or eager load, both old predicates always evaluated as “no history”; the simpler process-type checks preserve that observed behavior.
- No references in the archived `show-workorders____________________.blade.php` were changed because it is not the rendered view.

## Pending schema dependencies

### Work Process Requirement change history

Reintroducing the old guard behavior requires a separately approved database feature, not an empty model:

1. Define and migrate a current history table, PK, `wpr_id` type, actor type, AID status, and timestamp contract.
2. Add a verified Work Process Requirement writer that records the old/new lot atomically.
3. Add a typed relationship and eager-load it in the Work Order listing query.
4. Define whether one or many changes block a return, and how inactive/deleted history behaves.
5. Decide whether the existing 12 WPR records require backfill. No historical data currently exists to infer it.

No part of this dependency was implemented here.

## Sale Order Work Order Details dependency

The route still resolves to existing `showSaleOrderWorkOrderDetails()`, which deliberately redirects with “not ready”. The old ERP implementation cannot be copied directly.

The schema-backed relationship portion now available is:

```text
SaleOrder.id
  → SaleOrderItem.sale_order_id
  → WorkOrderItem.sale_order_item_id
  → WorkOrderItem.work_order_id → WorkOrder.id
  → WorkOrderItem.WarehouseOutItem via work_order_id
```

Remaining blockers:

- No current `packaging_orders`, `packaging_order_items`, or `packaging_process_requirements` tables/models. Only `packaging_types` exists.
- Missing `html.saleorder.show-saleorder-workorder-details` Blade view.
- Current controller action does not decrypt/load the ID or build the details query; it is an explicit unavailable response.
- Old fields/keys (`sale_order_id` as PK, `individual_id`, `ind_add_id`, `is_deleted`, numeric statuses) differ from current (`id`, `customer_id`, billing/shipping IDs, AID statuses).
- The next view/controller task must define whether packaging is optional and build a null-safe data contract from current relations. No missing Blade or controller business action was created in this task.

## Lab Test restriction

The following remain intentionally untouched for a dedicated task: `LabTest`, `LabTestResult`, `LabTestRequest`, `LabTestStandard`, `LabRequirement`, and `LabColourFastness`, together with their controller/routes/views. No Lab Test file appears in this diff.

## Tests added and updated

- `tests/Unit/Regression/ModelRelationshipStabilizationTest.php`
  - canonical table/key/timestamp contract for `Reason`;
  - related class, relation type, foreign/local/owner keys, and active scopes;
  - absence of invalid target classes/methods/column references;
  - active Work Order Blade no longer references nonexistent history.
- `tests/Feature/Database/ModelRelationshipIntegrationTest.php`
  - refuses any database except `blackgrd_schema_testing`;
  - transaction-scoped query tests for CWO reason/status filtering, Sale Order Item → Work Order Items, WPR → Work Order Item, warehouse individual resolution, and null relations.
- `tests/Unit/Regression/CodeIntegritySnapshotTest.php`
  - expected missing relationship targets updated from two failures to none.

## Verification results

| Check | Result |
|---|---|
| Changed PHP file lint | PASS |
| Relationship/snapshot/model focused suite | PASS — 10 tests, 71 assertions |
| Disposable relationship integration suite | PASS — 2 tests, 6 assertions |
| Full default suite | PASS — 45 passed, 11 skipped, 557 assertions |
| Skip reason | MySQL-only relationship/route/FK integration tests skip under default SQLite; new relationship integration suite passed separately on disposable MySQL |
| `php artisan route:list` | PASS — 291 routes |
| `php artisan route:cache` | PASS |
| Relationship/snapshot tests against route cache | PASS — 9 tests, 44 assertions |
| `php artisan route:clear` | PASS; final route cache absent |
| Live `php artisan db:safety-check` | Expected BLOCKED on `blackgrd` |
| Disposable safety check | ALLOWED only for `blackgrd_schema_testing`; destructive confirmation NOT ARMED |
| `git diff --check` | PASS |

## Commands executed

Read-only source and schema audit:

```powershell
Get-Content docs\audits\task-1.1-regression-baseline.md
Get-Content docs\audits\task-1.2a-schema-contract-audit.md
Get-Content docs\audits\task-1.2c-part1-active-route-stabilization.md
rg -n "SaleOrderItemPendingReason|WorkOrderItemDetail|WorkProcessRequirementChangeHistory|CwoReason|work_order_item_id|sale_order_item_id|ind_emp_id" app resources routes tests -S
rg -n "CwoReason|WorkOrderItemDetail|WorkProcessRequirementChangeHistory" E:\xampp\htdocs\erp\app E:\xampp\htdocs\erp\resources -S
Get-Content <affected current and old model/controller/view files>
E:\xampp\mysql\bin\mysql.exe -u root -D blackgrd -N -B -e "SELECT ... FROM information_schema.TABLES ..."
E:\xampp\mysql\bin\mysql.exe -u root -D blackgrd -N -B -e "SELECT ... FROM information_schema.COLUMNS ..."
E:\xampp\mysql\bin\mysql.exe -u root -D blackgrd -N -B -e "SELECT COUNT/orphan aggregates ..."
E:\xampp\mysql\bin\mysql.exe -u root -D blackgrd -N -B -e "SHOW CREATE TABLE reasons"
git status --short
git log --oneline -5
git diff
```

Verification:

```powershell
E:\php85\php.exe -l <each changed PHP/model/test file>
E:\php85\php.exe artisan test --compact --do-not-cache-result tests\Unit\Regression\ModelRelationshipStabilizationTest.php tests\Unit\Regression\CodeIntegritySnapshotTest.php tests\Unit\Regression\ModelRelationshipBaselineTest.php
$env:DB_CONNECTION='mysql'
$env:DB_DATABASE='blackgrd_schema_testing'
E:\php85\php.exe artisan db:safety-check
E:\php85\php.exe artisan test --compact --do-not-cache-result tests\Feature\Database\ModelRelationshipIntegrationTest.php
E:\php85\php.exe artisan test --compact --do-not-cache-result
E:\php85\php.exe artisan route:list
E:\php85\php.exe artisan route:cache
E:\php85\php.exe artisan test --compact --do-not-cache-result tests\Unit\Regression\ModelRelationshipStabilizationTest.php tests\Unit\Regression\CodeIntegritySnapshotTest.php
E:\php85\php.exe artisan route:clear
E:\php85\php.exe artisan db:safety-check
git diff --check
git status --short
```

No `migrate`, rollback, refresh, wipe, schema DDL, data repair, or live write command was run.

## Changed files / review state

- `app/Models/SaleOrderItem.php`
- `app/Models/WorkOrder.php`
- `app/Models/WorkProcessRequirement.php`
- `app/Models/WarehouseOutItem.php`
- `app/Models/WarehouseBalanceItem.php`
- `app/Http/Controllers/WarehouseItemController.php`
- `resources/views/frontend/workorder/show-workorders.blade.php`
- `tests/Unit/Regression/CodeIntegritySnapshotTest.php`
- `tests/Unit/Regression/ModelRelationshipStabilizationTest.php` (new)
- `tests/Feature/Database/ModelRelationshipIntegrationTest.php` (new)
- `docs/audits/task-1.2c-part2-model-relationship-stabilization.md` (new)

All changes remain uncommitted for review.
