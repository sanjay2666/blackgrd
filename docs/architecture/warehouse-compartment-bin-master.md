# Warehouse Compartment / Bin Master

## Canonical architecture

`warehouse_compartments` and `App\Models\WarehouseCompartment` are the canonical Compartment/Bin master. The hierarchy is `Company -> Factory/Branch -> Warehouse -> Compartment/Bin`; company and factory scope is inherited through the canonical `Warehouse` relationship. No second bin table, direct company/factory selector, rack/shelf hierarchy, code field, or barcode field is introduced.

Compartment/Bin Master defines a storage subdivision within a Warehouse; it does not own inventory quantities.

The live audit baseline contains 225 records: 186 Active and 39 Inactive. Existing IDs and names are preserved. The existing `warehouse_id` foreign key to `warehouses.id` remains in place.

## Fields and lifecycle

The supported fields are the existing `compartment_name`, `warehouse_id`, legacy `ind_emp_id`, audit/year fields, and canonical `status` (`Active`, `Inactive`, `Deleted`). There is no stock quantity, item, lot, colour, coating, financial amount, barcode, rack, or shelf field. Name uniqueness is logical and Warehouse-scoped using normalized case/trim comparison; the same name may exist in another Warehouse.

New records may select only an active Warehouse in the current company. An inactive historical parent remains readable for its existing child. Inactive Compartments remain readable for history but are excluded from new operational selections by the existing active filters.

## Reference protection

The service audits `warehouse_in_items`, `warehouse_out_items`, `warehouse_balance_items`, `warehouse_item_stocks`, and `purchase_items` through their existing `ware_comp_id` references. A referenced Compartment cannot be hard-deleted, moved to another Warehouse, or renamed. Deletion is replaced by deactivation; active stock prevents deactivation because it could strand operational inventory. No stock, balance, transfer, or historical row is moved or recalculated.

A referenced Compartment must not be moved to another Warehouse if doing so would change historical stock-location meaning.

Warehouse and Compartment IDs supplied together must be validated as a valid parent-child pair server-side. The master validates the selected Warehouse against the current company and operational modules continue to validate their existing Warehouse/Compartment contracts.

## UI, authorization, and audit

The dedicated Bootstrap 3.3.7 admin master provides search, Warehouse filter, status filter, pagination, compact create/edit forms, and activate/deactivate actions. Warehouse Master retains its read-only compartment count integration. Admin routes use the existing `auth:admin`, organization, RBAC, and audit middleware; Frontend User routes do not gain master-management access. Navigation remains under `Masters` using the canonical `warehouse.view-stock` visibility and `warehouse.update` mutation permission.

Meaningful create, update, activate, and deactivate changes are written through `AuditLogger` with before/after values. Dropdown reads and stock reads are not audited.

## Explicit boundaries

This master is not Warehouse Master, stock quantity, stock transfer, barcode/QR allocation, inventory valuation, or a Rack -> Shelf -> Bin engine. Existing operational references and selection APIs remain compatible; no broad operational rewrite is part of Task 3.24.
