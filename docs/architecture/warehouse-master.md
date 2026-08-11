# Warehouse Master

## Canonical identity

The canonical warehouse master is the `warehouses` table and `App\Models\Warehouse`. It is a single-company master. Existing warehouse IDs are stable and are not resequenced or remapped. The current live records are IDs 1–8: Yarn Warehouse, Beam Warehouse, Greige Warehouse, Dyed Warehouse, Coated Warehouse, Colour Warehouse, Chemical Warehouse, and SENTER- AREA. IDs 1–7 are Active and ID 8 is Inactive. Existing records have `company_id = 1` and `factory_id = null`.

The existing fields are retained: `warehouse_name`, `location`, `capacity`, `supervisor_id`, `contact_number`, `process_type_id`, financial/audit fields, `company_id`, `factory_id`, `financial_year_id`, and `status`. There is no existing warehouse-code field; this task does not invent one or regenerate identity values.

## Scope and relationships

`company_id` is assigned from `CurrentOrganizationContext`. A `factory_id`, when present, must be an Active factory in the canonical company. The existing `Factory::warehouses()` relationship is preserved. Warehouses have no Department ownership relation; Department Access remains a separate frontend operational concern. No branch/factory mappings were guessed for existing rows.

Warehouse name uniqueness is scoped to company plus factory, with central warehouses (`factory_id IS NULL`) forming their own location scope. Legacy duplicates are not merged. Existing descriptive fields remain editable. A referenced warehouse cannot be moved to another factory because that would change historical location meaning; forged or inactive/out-of-company factory IDs are rejected.

## Lifecycle and protection

The supported lifecycle is Active/Inactive. Inactive warehouses remain queryable for historical pages but are excluded by active selectors already used by operational modules. Deletion is not a valid lifecycle operation: the admin destroy endpoint rejects it and instructs administrators to deactivate instead. This protects compartments, stock, balance, receiving, inspection, dispatch, and other history. The application performs reference checks across warehouse compartments and known operational warehouse-reference tables before any future destructive policy could be considered.

Warehouse Master defines storage-location identity; Warehouse stock quantities remain transactional/balance data. This task does not calculate, store, migrate, reset, or redesign stock, balances, lots, transfers, or stock locking.

Compartment/Bin Master is a separate child master handled in Task 3.24. This task only preserves the existing `Warehouse -> WarehouseCompartment` relation and displays a read-only compartment count. It does not add compartment CRUD, barcode, rack, shelf, or bin reorganization.

## Administration and audit

Admin CRUD remains under `/admin/warehouses` and uses existing `warehouse.view-stock`, `warehouse.create`, `warehouse.update`, and `warehouse.delete` RBAC mappings. The admin guard, organization middleware, RBAC middleware, audit middleware, and permission-aware `AdminNavigation` remain in force. Frontend users do not receive Warehouse Master access.

Create, descriptive updates, factory changes where safe, and Active/Inactive transitions are recorded through `AuditLogger` with before/after values. Stock reads and dropdown lookups are not audited.

## Historical references and selectors

Warehouse IDs remain untouched in Warehouse IN/OUT, Warehouse Balance, Warehouse Item Stock, purchase receiving, work inspection, WPR, job-work/dispatch, and reporting flows. Existing active-only selectors remain compatible; historical pages can still resolve inactive records. No operational stock route or `/show` route was redesigned.
