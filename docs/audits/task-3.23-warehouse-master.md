# Task 3.23 — Warehouse Master Audit

## Findings

- Canonical table/model: `warehouses` / `App\Models\Warehouse`.
- Live IDs: 1–8; names are Yarn, Beam, Greige, Dyed, Coated, Colour, Chemical, and SENTER- AREA warehouses. IDs 1–7 are Active; ID 8 is Inactive. All current rows are company 1 and have no factory assignment.
- Company scope is supplied by `CurrentOrganizationContext`; no company selector or switching was added.
- The established location relation is nullable `warehouses.factory_id` → `factories.id`, with no existing branch relation on Warehouse. No live factory mapping existed to preserve. No Department relation exists.
- Existing fields were reused. No schema migration was required and no warehouse code was invented.
- Name uniqueness is company + factory, with a separate central (`NULL factory_id`) scope. Existing duplicates are preserved.

## Safety decisions

Active/Inactive is the only supported lifecycle. Deletion is rejected; references are never cascaded. Factory reassignment is rejected after operational references exist. Warehouse IDs, historical transactions, balances, compartments, and stock rows are not changed.

Warehouse Master defines storage-location identity; Warehouse stock quantities remain transactional/balance data.

Compartment/Bin Master is a separate child master handled in Task 3.24.

Warehouse Master changes must never rewrite historical inventory transactions or balances.

Known warehouse-reference categories audited in source: Warehouse IN/OUT, Warehouse Balance, Warehouse Item Stock, compartments, purchase items/receiving, work inspection, Work Order/WPR, job work/dispatch, and reporting/export helpers. The operational modules and selection contracts were not broadly refactored.

## Implementation

`WarehouseMasterService` centralizes company/factory validation, duplicate prevention, historical-location protection, lifecycle transitions, deletion refusal, and Audit Log snapshots. The admin list now supports search, factory filter, status filter, pagination, factory display, and read-only compartment counts. It remains Bootstrap 3.3.7-compatible and does not use a `well well-sm` container.

No live backup, migration, or data mutation was required. Live database safety remained armed/protected.
