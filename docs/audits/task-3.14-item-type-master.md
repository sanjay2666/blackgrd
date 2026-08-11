# Task 3.14 — Item Type Master Audit

Audit date: 2026-08-11. Baseline: `949fd09`.

## Result

The canonical architecture is the existing `item_type` / `ItemType` pair. Live read-only inspection found nine company-1 records: Yarn (1), Beam (2), Greige (3), Dyed (4), Coated (5), General (6), Chemical (7), Fabric (8), and Colour (9). No IDs were changed. The migration is reviewed source only; no live migration, backup, or maintenance-mode operation was performed.

## Compatibility findings

IDs 3/4/5 are used as Greige/Dyed/Coated in work, warehouse, requisition, and stock code. ID 8 is named Fabric and is used by legacy item and Sale Order paths; Common legacy logic includes it with Greige-compatible IDs `[3, 8]`. It remains a separate historical identity. Greige/Dyed/Coated colour and coating semantics are represented by transaction fields and existing stock matching, not new Item Type rules.

Hard-coded inventory: WorkProcessRequirement (IDs 2, 3, 4, 7, 9), WarehouseItem (IDs 1, 2, 3, 4, 5, 6, 8 and dynamic filters), CommonController (sets `[3,8]`, `[1,2]`), JobMillWork (sets `[1,2]`, `[3,4,5,6]`), SaleOrderController (type 8), and related Blade selectors/report labels. This is compatibility debt documented for later scoped refactoring, not rewritten here.

## Implemented controls

The existing admin master now supports search by name/code, status filtering, stable short codes, canonical status changes, name/code uniqueness among non-deleted records, protected core identities, and reference-aware deletion prevention. Existing flags (`is_purchase`, `is_work`, `is_department`) and unit relation were preserved because current selectors use them. No Item Master fields, process workflows, printing routes, stock redesign, or new taxonomy were introduced.

The existing `masters.*` permission, AdminNavigation, admin guard, route permission registry, centralized AuditLogger, and audit middleware are reused. Item Type create/update/status mutations add meaningful audit before/after payloads; reads are not audited as business events.

Database safety: the baseline `php artisan quality:check` passed. Live `blackgrd` was read only and remains unarmed; no destructive command, schema apply, backup, hash, or maintenance-mode change was run. Migration verification must use disposable `blackgrd_schema_testing` under the reviewed migration process.
