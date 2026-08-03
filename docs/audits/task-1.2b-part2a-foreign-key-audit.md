# Task 1.2B-Part 2A — Primary Key Signedness and Critical Foreign Key Audit

Date: 2026-08-03

Project: `E:\projects\blackgrd`

Database: live `blackgrd`, inspected read-only

Old ERP: `E:\xampp\htdocs\erp` and database `blackgrd_erp`, inspected read-only only

No migration was created, edited, or run. No schema or business data was changed.

## Executive result

The safe recommendation is a **staged compatibility approach** with unsigned BIGINT as the eventual canonical type for `individuals.id`. The parent is numerically safe to convert because all current IDs are positive, but it must not be altered by itself: genuine child references use four different integer contracts, actor columns have mixed meanings, and current identity/orphan evidence must be resolved first.

Only five audited candidates are type-compatible and orphan-free now. Most core relationships are logically clear but cannot accept a foreign key until their signedness/width matches. Two warehouse relationships also contain a `0` sentinel that is an FK orphan. No existing IDs should be renumbered.

## 1. `individuals.id` signedness contract

| Check | Current `blackgrd` | Fresh migration | Old ERP reference |
| --- | --- | --- | --- |
| Type | signed `bigint(22)` | `$table->id()` = unsigned BIGINT | unsigned `bigint(20)` |
| Rows | 645 | n/a | 645 |
| Minimum ID | 1 | n/a | 1 |
| Maximum ID | 645 | n/a | 903 |
| Negative IDs | 0 | n/a | 0 |
| Next auto-increment | 646 | generated | not used as current contract evidence |

Numerically, converting the current parent to unsigned would preserve every current ID. However, the old/current identity sets are not interchangeable:

- Shared IDs: 387.
- Old-only IDs: 258; first ten are 646–655.
- Current-only IDs: 258; first ten are 17, 18, 19, 20, 21, 33, 34, 35, 36, 303.
- Of the 387 shared numeric IDs, 371 have a different `name` value.

The old ERP is reference-only, so this comparison is an audit warning rather than authority to copy, remap, or renumber anything.

### Individual-reference type families

The following genuine or probable Individual references exist:

- Signed INT: `agents.individual_id`, `individual_address.individual_id`, `users.individual_id`, `sale_orders.customer_id`, `sale_orders.order_by_employee`, `work_order_items.customer_id`, most vendor/receiver/master/employee fields in warehouse and work-order tables, and job-work vendor/receiver fields.
- Unsigned INT: `purchases.vendor_id`, `purchase_orders.vendor_id`.
- Signed BIGINT: `department_return_requests.employee_id`.
- Unsigned BIGINT: `department_returns.employee_id`, `warehouse_compartments.ind_emp_id`, and `warehouses.supervisor_id`.
- String: `sale_orders.ind_agent_id varchar(60)`; it has no current non-empty value and must not receive a numeric FK until its storage contract is changed and its business cardinality confirmed.

Every inspected numeric Individual reference has zero negative values. Except for `individual_address.individual_id`, the inspected non-zero references resolve to current Individuals. The address relationship is materially unsafe:

- `individual_address` rows: 400.
- Orphan rows: 136.
- Distinct missing parent IDs: 68, spanning 648–903.
- First ten child/missing-parent pairs: `249→648`, `250→648`, `251→650`, `252→650`, `253→651`, `254→651`, `255→652`, `256→652`, `257→653`, `258→653`.

This blocks an `individual_address.individual_id` FK regardless of signedness.

### User and audit-actor references

`users.id` is unsigned BIGINT. Compatible user columns include `login_otps.user_id`, `sessions.user_id`, and `user_activity_logs.user_id`. Signed INT user columns such as `gate_pass_print_logs.user_id`, `notifications.user_id`, `user_web_pages.user_id`, and `work_orders.user_id` are incompatible by width/signedness.

There are 94 `created_by`, `modified_by`, `updated_by`, or `deleted_by` columns:

| Column/type family | Count |
| --- | ---: |
| `created_by` signed INT | 41 |
| `created_by` unsigned BIGINT | 2 |
| `modified_by` signed INT | 40 |
| `modified_by` unsigned BIGINT | 2 |
| `deleted_by` signed INT | 8 |
| `updated_by` signed INT | 1 |

Their semantics are not uniform. Controllers assign admin/user `Auth::id()` in some flows and an authenticated user's `individual_id` in others. Only `WorkProcessRequirement` and `WorkPurchaseRequirement` declare explicit `created_by`/`modified_by → individuals.id` model relationships. Therefore actor columns are **not recommended** for a global FK until each table's actor namespace is documented.

`warehouses.supervisor_id` illustrates the ambiguity: it is unsigned BIGINT and four values do not resolve to `users.id`, while all eight resolve to `individuals.id`. There is no model relationship and the admin form accepts a raw numeric value. Treat it as an Individual candidate only after the warehouse owner confirms that meaning.

### Signedness recommendation

1. Keep current IDs unchanged.
2. Adopt unsigned BIGINT as the eventual parent contract because the fresh migration and old ERP parent both use it and no negative current/reference values were found.
3. Do not alter `individuals.id` alone.
4. First classify every genuine Individual reference, resolve the 136 address orphans through a separately approved identity investigation, and decide ambiguous receiver/supervisor/actor namespaces.
5. Widen approved child references to unsigned BIGINT in bounded stages, verify ranges/orphans again, then convert the parent in the same controlled compatibility programme.
6. Do not force string, historical, sentinel, or ambiguous columns into this FK family.

Changing migrations to signed BIGINT would make fresh installs resemble the current parent but would preserve the larger mismatch with the old canonical parent and existing unsigned BIGINT employee columns. It is not the preferred final contract.

## 2. Model relationship verification

| Area | Verified current model relationships | Audit warnings |
| --- | --- | --- |
| Company/users | `Individual.department → departments`; `Individual.addresses → individual_address` | `User.individual_id` has no model relationship. `branches` and `factories` have no table, migration, or model in the current project. `companies` has no FK column to the listed company-chain tables. |
| Sales | `SaleOrder.customer/employee/agent`; `SaleOrder.saleOrderItems`; `SaleOrderItem.saleOrder/item/itemType/unitType` | `ind_agent_id` is varchar. Address links exist in controllers/schema but are outside the first ownership-key set. |
| Work orders | Work Order master/process/item/machine/requirements/items/inspection/gate-pass relations; Work Order Item work/sale/customer relations; WPR work/item/type/unit relations | `WorkOrder.sale_order_item_id` and `WorkProcessRequirement.work_order_item_id` relationships reference columns absent from the actual schema. No FK should be planned for missing columns in this task. |
| Warehouse | Warehouse Item and Stock relationships to warehouse/compartment/item/vendor/receiver; stock-to-inward and outward-to-stock/inward relationships; balance-to-inward/outward | `receiver_id` is related to both User and Individual in some models. `WarehouseOutItem.ind_emp_id` and `WarehouseBalanceItem.ind_emp_id` model relationships reference absent columns. These are not recommended until semantics/schema are corrected separately. |
| Inspection/gate pass | `WorkInspection → WorkOrder`; `GatePass → WorkInspection`; Work Order has inspection and gate-pass relations | Inspection detail has only a fault-reason relation; other candidates come from clear schema/controller ownership, not an explicit model relation. |
| Purchase/job work | PO→items/purchases/vendor/addresses; PO Item→PO/item/type; dispatch→items/vendor; receive items→dispatch/dispatch item/item/unit | Dispatch/receive tables are currently empty, but their child types still do not match their parents. Empty data does not make an incompatible FK safe. |

## 3. Critical candidate matrix

Legend:

- `N/R` = null child values / total child rows.
- `D` = repeated non-null child references. These are expected for many-to-one relationships and are not a blocker; parent PKs are unique.
- `O` = orphan rows.
- `Idx` means an index starts with the child FK column.
- Delete/update actions are shown as `ON DELETE / ON UPDATE`.
- Transaction and audit history uses `RESTRICT / RESTRICT`; no cascade delete is recommended.

| Priority / verdict | Child → parent | Child type → parent type | N/R | D | O | Idx | Delete / update |
| --- | --- | --- | ---: | ---: | ---: | --- | --- |
| High — Unsafe type | `users.individual_id → individuals.id` | signed INT → signed BIGINT | 0/2 | 1 | 0 | No | RESTRICT / RESTRICT |
| Medium — Unsafe type, dormant | `individuals.department_id → departments.id` | signed INT → unsigned BIGINT | 645/645 | 0 | 0 | No | SET NULL / RESTRICT |
| Critical — Unsafe type/identity | `sale_orders.customer_id → individuals.id` | signed INT → signed BIGINT | 0/1 | 0 | 0 | No | RESTRICT / RESTRICT |
| High — Unsafe type/identity | `sale_orders.order_by_employee → individuals.id` | signed INT → signed BIGINT | 0/1 | 0 | 0 | No | RESTRICT / RESTRICT |
| Critical — Unsafe signedness | `sale_order_items.sale_order_id → sale_orders.id` | signed INT → unsigned INT | 0/2 | 1 | 0 | No | RESTRICT / RESTRICT |
| Critical — **Safe now** | `work_orders.parent_work_order_id → work_orders.id` | unsigned INT → unsigned INT | 1/9 | 4 | 0 | Yes | RESTRICT / RESTRICT |
| High — Unsafe type | `work_orders.user_id → users.id` | signed INT → unsigned BIGINT | 0/9 | 7 | 0 | No | RESTRICT / RESTRICT |
| Critical — Unsafe signedness | `work_order_items.work_order_id → work_orders.id` | signed INT → unsigned INT | 0/9 | 0 | 0 | Yes | RESTRICT / RESTRICT |
| High — Unsafe signedness | `work_order_items.sale_order_id → sale_orders.id` | signed INT → unsigned INT | 0/9 | 8 | 0 | Yes | RESTRICT / RESTRICT |
| High — Unsafe signedness | `work_order_items.sale_order_item_id → sale_order_items.id` | signed INT → unsigned INT | 0/9 | 8 | 0 | Yes | RESTRICT / RESTRICT |
| Critical — Unsafe signedness | `work_process_requirements.work_order_id → work_orders.id` | signed INT → unsigned INT | 0/12 | 5 | 0 | Yes | RESTRICT / RESTRICT |
| High — Unsafe width | `work_process_requirements.wis_id → warehouse_item_stocks.id` | signed INT → signed BIGINT | 3/12 | 4 | 0 | No | RESTRICT / RESTRICT |
| Critical — **Safe after index** | `warehouse_compartments.warehouse_id → warehouses.id` | unsigned BIGINT → unsigned BIGINT | 0/225 | 218 | 0 | No | RESTRICT / RESTRICT |
| High — Unsafe type | `warehouse_in_items.warehouse_id → warehouses.id` | signed INT → unsigned BIGINT | 0/17 | 13 | 0 | No | RESTRICT / RESTRICT |
| High — Unsafe type | `warehouse_in_items.ware_comp_id → warehouse_compartments.id` | signed INT → unsigned BIGINT | 6/17 | 3 | 0 | No | RESTRICT / RESTRICT |
| Critical — **Safe after index** | `warehouse_item_stocks.warehouse_item_id → warehouse_in_items.id` | signed BIGINT → signed BIGINT | 0/17 | 1 | 0 | No | RESTRICT / RESTRICT |
| High — Unsafe type | `warehouse_item_stocks.warehouse_id → warehouses.id` | signed INT → unsigned BIGINT | 0/17 | 13 | 0 | No | RESTRICT / RESTRICT |
| High — Unsafe type | `warehouse_item_stocks.ware_comp_id → warehouse_compartments.id` | signed INT → unsigned BIGINT | 5/17 | 4 | 0 | No | RESTRICT / RESTRICT |
| Critical — Unsafe width | `warehouse_out_items.wis_id → warehouse_item_stocks.id` | signed INT → signed BIGINT | 0/10 | 3 | 0 | No | RESTRICT / RESTRICT |
| Critical — Unsafe width | `warehouse_out_items.warehouse_item_id → warehouse_in_items.id` | signed INT → signed BIGINT | 0/10 | 3 | 0 | No | RESTRICT / RESTRICT |
| High — Unsafe type/data | `warehouse_balance_items.ware_in_item_id → warehouse_in_items.id` | signed INT → signed BIGINT | 0/27 | 9 | **10** | Yes | RESTRICT / RESTRICT |
| High — Unsafe type/data | `warehouse_balance_items.ware_out_item_id → warehouse_out_items.id` | signed INT → unsigned INT | 0/27 | 16 | **17** | No | RESTRICT / RESTRICT |
| Critical — Unsafe type | `work_inspections.work_order_id → work_orders.id` | signed BIGINT → unsigned INT | 0/5 | 2 | 0 | Yes | RESTRICT / RESTRICT |
| Critical — Unsafe signedness | `work_inspection_details.work_insp_id → work_inspections.id` | signed INT → unsigned INT | 0/9 | 4 | 0 | Yes | RESTRICT / RESTRICT |
| High — Unsafe signedness | `gate_passes.inspection_id → work_inspections.id` | signed INT → unsigned INT | 0/5 | 0 | 0 | Yes | RESTRICT / RESTRICT |
| High — Unsafe signedness | `gate_passes.work_order_id → work_orders.id` | signed INT → unsigned INT | 0/5 | 2 | 0 | Yes | RESTRICT / RESTRICT |
| High — Unsafe signedness | `gate_pass_print_logs.gate_pass_id → gate_passes.id` | signed INT → unsigned INT | 0/6 | 5 | 0 | No | RESTRICT / RESTRICT |
| High — Unsafe type/identity | `purchase_orders.vendor_id → individuals.id` | unsigned INT → signed BIGINT | 0/3 | 2 | 0 | No | RESTRICT / RESTRICT |
| Critical — **Safe after index** | `purchase_order_items.purchase_id → purchase_orders.id` | unsigned INT → unsigned INT | 0/5 | 2 | 0 | No | RESTRICT / RESTRICT |
| High — **Safe after index** | `purchase_order_items.item_id → items.item_id` | unsigned INT → unsigned INT | 0/5 | 2 | 0 | No | RESTRICT / RESTRICT |
| Medium — Unsafe type, empty | `stock_mill_dispatches.vendor_id → individuals.id` | signed INT → signed BIGINT | 0/0 | 0 | 0 | No | RESTRICT / RESTRICT |
| Critical — Unsafe type, empty | `stock_mill_dispatch_items.stock_mill_dispatch_id → stock_mill_dispatches.id` | signed INT → unsigned BIGINT | 0/0 | 0 | 0 | Yes | RESTRICT / RESTRICT |
| High — Unsafe type, empty | `receive_stock_mill_dispatches.stock_mill_dispatch_id → stock_mill_dispatches.id` | signed INT → unsigned BIGINT | 0/0 | 0 | 0 | No | RESTRICT / RESTRICT |
| Critical — Unsafe signedness, empty | `receive_stock_mill_dispatch_items.receive_mill_dispatch_id → receive_stock_mill_dispatches.id` | signed INT → unsigned INT | 0/0 | 0 | 0 | No | RESTRICT / RESTRICT |
| Critical — Unsafe signedness, empty | `receive_stock_mill_dispatch_items.stock_mill_dispatch_item_id → stock_mill_dispatch_items.id` | signed INT → unsigned INT | 0/0 | 0 | 0 | No | RESTRICT / RESTRICT |

The database currently has one enforced FK only: `login_otps.user_id → users.id`, with `ON DELETE CASCADE` and `ON UPDATE RESTRICT`.

## 4. Orphan groups

Only three verified groups in this audit have orphans:

| Relationship | Orphans | Cause/evidence | Sample child IDs (max 10) | Recommendation |
| --- | ---: | --- | --- | --- |
| `individual_address.individual_id → individuals.id` | 136 | 68 distinct missing IDs, range 648–903 | 249, 250, 251, 252, 253, 254, 255, 256, 257, 258 | Identity recovery/mapping investigation first; do not renumber IDs or add FK. |
| `warehouse_balance_items.ware_in_item_id → warehouse_in_items.id` | 10 | Every orphan reference value is sentinel `0` | 8, 9, 12, 15, 20, 21, 26, 27, 31, 32 | Separately approve converting sentinel semantics to `NULL` or another explicit representation, then widen type and re-audit. |
| `warehouse_balance_items.ware_out_item_id → warehouse_out_items.id` | 17 | Every orphan reference value is sentinel `0` | 1, 2, 3, 4, 5, 6, 7, 10, 11, 14 | Same as above; no FK now. |

All other rows in the formal candidate matrix have zero verified orphans. Zero-row dispatch tables still require type alignment and indexes before constraints.

## 5. Not recommended or not actionable now

- All 94 audit-actor columns: mixed User/Admin/Individual namespaces.
- `sale_orders.ind_agent_id`: varchar and possibly historical/multi-value semantics.
- `receiver_id` on stock/out/balance tables: models point the same column to both User and Individual in places.
- `warehouses.supervisor_id`: likely Individual data but no model/business contract; raw admin input.
- Missing-column relationships: `work_orders.sale_order_item_id`, `work_process_requirements.work_order_item_id`, `warehouse_out_items.ind_emp_id`, and `warehouse_balance_items.ind_emp_id`.
- Optional process/item/master columns whose ownership is not confirmed should remain application-level references until separately audited. This report does not convert the full inventory of 253 `_id` candidates into FK recommendations.

## 6. First foreign keys — ordered maximum 10

The first five are structurally ready after any listed index. Items 6–10 must wait for Stage A type alignment.

| Order | FK | Precondition | Index |
| ---: | --- | --- | --- |
| 1 | `work_orders.parent_work_order_id → work_orders.id` | Final self-reference/business-cycle review | Existing `work_orders_parent_index` |
| 2 | `warehouse_compartments.warehouse_id → warehouses.id` | None beyond final write-race precheck | Add leading index |
| 3 | `warehouse_item_stocks.warehouse_item_id → warehouse_in_items.id` | Confirm inward rows are never hard-deleted | Add leading index |
| 4 | `purchase_order_items.purchase_id → purchase_orders.id` | Confirm `purchase_id` is the canonical header name | Add leading index |
| 5 | `purchase_order_items.item_id → items.item_id` | None beyond final write-race precheck | Add leading index |
| 6 | `sale_order_items.sale_order_id → sale_orders.id` | Change child to unsigned INT; recheck orphans | Add leading index |
| 7 | `work_order_items.work_order_id → work_orders.id` | Change child to unsigned INT | Existing leading composite index |
| 8 | `work_process_requirements.work_order_id → work_orders.id` | Change child to unsigned INT | Existing leading composite index |
| 9 | `work_inspection_details.work_insp_id → work_inspections.id` | Change child to unsigned INT | Existing leading index |
| 10 | `gate_pass_print_logs.gate_pass_id → gate_passes.id` | Change child to unsigned INT | Add leading index |

Recommended actions for these ten are `ON DELETE RESTRICT ON UPDATE RESTRICT`. The application already uses logical status/deletion fields; cascading hard deletion would risk historical sales, stock, inspection, and audit records.

### Required indexes

Required before the ordered keys can be added:

- `warehouse_compartments(warehouse_id)`
- `warehouse_item_stocks(warehouse_item_id)`
- `purchase_order_items(purchase_id)`
- `purchase_order_items(item_id)`
- `sale_order_items(sale_order_id)`
- `gate_pass_print_logs(gate_pass_id)`

The other four ordered candidates already have a leading index. Later-stage missing indexes are recorded in the candidate matrix and should be added only with their associated FK stage, not as a blanket `_id` indexing exercise.

## 7. Staged implementation plan

### Stage A — type alignment, indexes, and orphan evidence

1. Take a verified live backup and export PK/column/index/FK metadata.
2. Freeze an identity evidence report for current and old Individuals; investigate the 136 address orphans without renumbering existing IDs.
3. Confirm namespaces for actor, receiver, supervisor, agent, master, and employee fields.
4. For each approved relationship, change only the child type required for the next stage; use unsigned BIGINT for genuine Individual references and the exact parent type for all other references.
5. Add only the six named indexes needed by the first ten keys.
6. Re-run null/orphan/range checks immediately before every future ALTER.

Risk: integer modification can rebuild/lock a table; concurrent writes can create a new orphan after precheck. Prove every operation on `blackgrd_schema_testing`, estimate table/lock time, enter a controlled write freeze, verify the actual connected database, and keep a table-level backup. Rollback may require another table rebuild, so rollback is not instantaneous.

### Stage B — first type-compatible critical keys

Add one constraint per reviewed migration, starting with ordered items 1–5. Run exact prechecks in the same maintenance window. Do not bundle unrelated modules.

Risk: even metadata-only FK creation takes metadata locks and validates existing rows. `RESTRICT` can expose previously hidden hard-delete behavior. Rollback drops the constraint but cannot reverse application writes that failed while it was active; test delete/cancel flows in disposable MySQL first.

### Stage C — sales and work-order ownership

After child alignment, add ordered items 6–10, then evaluate customer/user/sale-item and inspection/gate-pass header links. Keep Individual/customer keys out until identity evidence is resolved.

Risk: these are high-write operational tables. A precheck/add race is possible, composite indexes may need review, and parent deletion may currently rely on unconstrained behavior. Use a write freeze, `RESTRICT`, application smoke tests, and one constraint per deployable unit.

### Stage D — warehouse and job work

First resolve the two sentinel-0 balance groups through separately approved data semantics. Align warehouse/header IDs, then add outward/stock/balance and dispatch/receive chains in workflow order. Empty job-work tables still require exact types before their first production data arrives.

Risk: warehouse stock history is audit-sensitive and contains signed INT/BIGINT plus unsigned parent mixtures. Table rebuilds may lock active inward/outward processing. Preserve row counts and stock totals, compare critical rows before/after, and keep rollback SQL limited to dropping the new constraint/index—not changing IDs.

## 8. Read-only command log

The following commands or read-only variants were executed. The candidate loops substituted only fixed table/column identifiers from the matrix.

```powershell
git status --short --branch
Get-Content -Raw docs\audits\task-1.2a-schema-contract-audit.md
Get-Content -Raw docs\audits\task-1.2b-part1-verified-schema-repair.md
Get-Content database\migrations\2026_07_17_065102_create_individuals_table.php
Get-Content database\migrations\2026_07_17_180000_create_departments_table_and_add_department_id.php
Get-Content app\Models\*.php   # selected critical models only
rg -n "belongsTo\(|hasMany\(|hasOne\(|belongsToMany\(|morph" app\Models
rg -n "individual_id|customer_id|vendor_id|employee_id|department_id|supervisor_id" database\migrations
rg -n "created_by|modified_by|supervisor_id|ind_emp_id|receiver_id" app database\migrations resources\views

E:\xampp\mysql\bin\mysql.exe -u root -D blackgrd -e "SHOW COLUMNS FROM individuals LIKE 'id'"
E:\xampp\mysql\bin\mysql.exe -u root -D blackgrd -e "SELECT COUNT(*),MIN(id),MAX(id),SUM(id<0) FROM individuals"
E:\xampp\mysql\bin\mysql.exe -u root -D blackgrd -e "SELECT AUTO_INCREMENT FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='individuals'"
E:\xampp\mysql\bin\mysql.exe -u root -D blackgrd -e "SELECT ... FROM information_schema.columns WHERE table_schema=DATABASE() AND ..."
E:\xampp\mysql\bin\mysql.exe -u root -D blackgrd -e "SELECT ... FROM information_schema.statistics WHERE table_schema=DATABASE() AND ..."
E:\xampp\mysql\bin\mysql.exe -u root -D blackgrd -e "SELECT ... FROM information_schema.key_column_usage ..."
E:\xampp\mysql\bin\mysql.exe -u root -D blackgrd -e "SELECT ... FROM information_schema.referential_constraints ..."
E:\xampp\mysql\bin\mysql.exe -u root -D blackgrd -e "SELECT COUNT(...),SUM(... IS NULL),COUNT(DISTINCT ...),SUM(parent.id IS NULL) FROM child LEFT JOIN parent ..."
E:\xampp\mysql\bin\mysql.exe -u root -D blackgrd -e "SELECT child.id,child.reference_id FROM child LEFT JOIN parent ... WHERE parent.id IS NULL ORDER BY child.id LIMIT 10"
E:\xampp\mysql\bin\mysql.exe -u root -D blackgrd_erp -e "SHOW COLUMNS FROM individuals LIKE 'id'; SELECT COUNT(*),MIN(id),MAX(id),SUM(id<0) FROM individuals"
E:\xampp\mysql\bin\mysql.exe -u root -e "SELECT ... FROM blackgrd_erp.individuals LEFT JOIN blackgrd.individuals ..."
```

No `migrate`, `ALTER`, `CREATE`, `DROP`, `INSERT`, `UPDATE`, `DELETE`, `TRUNCATE`, data repair, or ID change was executed.
