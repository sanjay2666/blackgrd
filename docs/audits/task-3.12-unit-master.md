# Task 3.12 — Unit Master Audit

## Findings and implementation

- Canonical table/model: existing `unit_type` / `App\Models\UnitType`; no duplicate created.
- Live IDs/codes: ID 1 `PCS` deleted, ID 2 `Meter` active, ID 3 `Line` deleted, ID 4 `Kg` active. Existing rows and IDs are preserved. The legacy table had no separate short-code column; the migration adds nullable `unit_code` without rewriting current identities.
- Supported fields: name, short code/symbol, description, decimal precision, display order, canonical status.
- Precision: optional metadata only; no transaction quantity precision rewrite.
- Uniqueness: normalized name and code checks are case-insensitive and whitespace-trimmed. No unsafe unique index was imposed on existing live data.
- Status/deletion: active/inactive/deleted uses the existing `RecordStatus` foundation. Referenced units cannot be deleted; deactivation preserves history.
- Scope: company-global only; no department, branch, multi-company, or conversion architecture was added.
- Conversion boundary: **Unit Master defines measurement identities, not Item-specific conversion quantities.**
- Hard-coded ID inventory: ID 2 (`Meter`) and ID 4 (`Kg`) are used in `WorkOrderController` (including lines 1897, 1915, 1937, 1982, 2118, 2136, 2158, 2203, 2568, 2664, 2930, 3016, 3355, 3404, 3693, 3744, 3975) and `WarehouseItemController` (1777, 1796, 1863). A legacy view also maps ID 2 to Meter and all other values to Kg. These consumers were not broadly refactored; IDs 2 and 4 are protected by the Unit service.
- References audited: items, item types, warehouse in/out/balance/stock, sale order items, work order items, work process requirements, work purchase requirements, purchase items/order items, gate passes, and job-work stock item tables.
- RBAC: existing canonical `masters.view/create/update/delete` permissions are reused; explicit activate/deactivate routes map to `masters.update`. Admin route middleware enforces authorization server-side and frontend users remain on the web guard.
- Audit Log: centralized `AuditLogger` records create/update/status changes and route mutations; reads/dropdowns are not logged.
- Navigation: existing permission-aware `AdminNavigation`, under Masters, renamed from Unit Types to Unit Master.

## Database safety

The only schema change is the reviewed additive migration `2026_08_11_000004_extend_unit_type_master`. No live migration or destructive command was run. Live inspection was read-only against `blackgrd`; the live database remains protected and no maintenance-mode change was made. No backup/SHA/migration apply was applicable because the live schema was not changed.

## Verification

Focused Unit service tests cover creation, identity metadata, reference deletion rejection, protected legacy identity, status deactivation, and historical references. Existing status, navigation, route-permission, full regression, and final quality checks are required before commit.
