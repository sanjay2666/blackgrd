# Task 3.15 — Item Master audit

## Result

The canonical table/model is `items` / `App\Models\Item`; no duplicate Product or Material master was introduced. Item IDs are never resequenced, merged, replaced, or deleted by this task. The model remains company-global within the single organization context through the existing company scope foundation.

## Implemented controls

- Item Type is validated against active canonical `item_type` records for new configuration. Type 8 Fabric is preserved and not migrated.
- Unit is validated against active canonical `unit_type` records. Existing inactive historical Unit links remain editable/readable.
- Nullable canonical HSN and GST Rate defaults were added additively. Legacy `hsncode` and item tax columns remain intact.
- Supplied Item codes are normalized to uppercase and unique among non-deleted Items. Item names are not globally unique.
- Referenced Item Type changes are rejected. Referenced Items are deactivated by the existing delete action; unreferenced Items retain the established Deleted status.
- Mutations are audited centrally with before/after identity, classification, unit, tax-reference, and status values.
- Existing `list_item` and `fabric_list_item` response contracts remain active; both already select Active Items only. Historical pages can still resolve inactive IDs directly.
- Admin resource routes remain under the existing `auth:admin`, organization, RBAC, and audit middleware. Resource mapping uses `masters.view/create/update/delete`; navigation remains permission-aware under Masters.

## Compatibility audit

Operational references include Sale Order Items, Work Orders and Work Order Items, WPR/requisitions, warehouse in/out/balance/stock, purchase items, inspections, job work, Item Yarn Requirements, gate passes, and reporting paths. The service checks these reference tables before lifecycle changes. Existing Item Type 8 and Greige/Dyed/Coated warehouse assumptions remain untouched. Transaction snapshot fields are not replaced with live master values.

Hard-coded technical debt remains intentionally documented rather than broadly refactored: Type 8 is used by the legacy fabric autocomplete and the Item list’s legacy Manage Yarn affordance; protected Item Type IDs are centralized in the Task 3.14 service. No specific Item IDs were changed. No Yarn Recipe, Fabric Quality Master, printing route, workflow engine, conversion engine, or multi-company Item redesign was added.

## Database safety and verification

The only schema change is a reviewed additive migration adding nullable indexed `hsn_code_id` and `gst_rate_id` to `items`; no legacy values are rewritten and no live migration was run. Live database `blackgrd` remained protected. No backup/SHA or maintenance window was applicable because live apply was not authorized or performed; maintenance mode remained off.

Baseline and final quality checks passed. Full PHPUnit result: 160 passed, 11 skipped (the existing disposable-MySQL-only integration skips). `git diff --check`, route inspection, and PHP lint passed. Task 3.16 and later specialized masters were not started.
