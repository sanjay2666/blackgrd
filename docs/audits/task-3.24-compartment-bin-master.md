# Task 3.24 — Compartment / Bin Master Audit

## Findings

- Canonical table/model: `warehouse_compartments` / `WarehouseCompartment`.
- Live baseline: 225 records, 186 Active and 39 Inactive; IDs are preserved and no live rows were recreated or remapped.
- Parent relationship: required `warehouse_compartments.warehouse_id -> warehouses.id` with existing foreign-key protection.
- Scope: company/factory is inherited through `Warehouse`; no duplicated scope fields were added.
- Existing fields: name, warehouse, legacy employee reference, financial year, audit fields, and status. No code field exists, so no codes were invented or regenerated.
- References: `ware_comp_id` occurs in Warehouse IN/OUT, Warehouse Balance, Warehouse Item Stock, and Purchase Item records. No `warehouse_compartment_id`, `compartment_id`, or `bin_id` canonical column was created.

## Implemented rules

- Warehouse-scoped normalized name uniqueness; identical names in different Warehouses are allowed.
- New assignments require an active canonical Warehouse in the current company.
- Existing inactive parent relationships remain readable.
- Active-only lifecycle selection is preserved for new operational use.
- Referenced Compartments cannot be hard-deleted, renamed, or reassigned to another Warehouse.
- Active stock blocks deactivation; no stock or balance recalculation is performed.
- Warehouse/Compartment parent-child consistency is checked server-side rather than trusting form inputs.
- Admin authorization uses existing admin guard/RBAC and `warehouse.view-stock` / `warehouse.update`; Frontend User cannot administer this master.
- AuditLogger records meaningful create/update/status changes with before/after values.

Compartment/Bin Master defines a storage subdivision within a Warehouse; it does not own inventory quantities.

A referenced Compartment must not be moved to another Warehouse if doing so would change historical stock-location meaning.

Warehouse and Compartment IDs supplied together must be validated as a valid parent-child pair server-side.

## Boundaries and compatibility

No schema migration or live data change was required. Existing Warehouse Master compartment counts remain read-only. No rack/shelf hierarchy, barcode system, transfer redesign, balance redesign, or operational-module rewrite was introduced. Existing active compartment selection endpoints and historical relation reads remain in place.
