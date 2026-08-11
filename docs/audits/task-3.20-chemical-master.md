# Task 3.20 Chemical Master Audit

## Result

The repository and live schema contain no independent Chemical identity. Chemical identity is `items` + the canonical `item_type` row named `Chemical`; the verified Item Type is ID 7 / `CHEMICAL`. Existing live Chemicals were read-only audited; IDs and references were not changed. No migration or live data change was required.

## Inventory and boundaries

`chemical_id` and a Chemical-specific table are absent from the current migrations/models. Item-based references occur in purchase, warehouse/stock, work, inspection, gate-pass, job-work, sales, and Item Yarn Requirement flows. Existing Dyeing Lab Test `material_name`/`material_type` and quantity fields are snapshots/formula usage, not master identity; no formula rows are migrated. Existing Dyeing/Coating, Purchase, Warehouse, and production contracts were not broadly refactored. The master does not calculate available stock.

The supported fields are Item name/code, canonical Unit, HSN/GST references, remarks/specification, and status. No chemistry-specific speculative fields, conversion engine, Recipe Master, or formula engine was added. Codes are normalized uppercase; names are normalized for Chemical duplicate checks. Legacy duplicate records are not merged.

## Safety and access

Active/Inactive is the lifecycle. Referenced records are deactivated instead of deleted; identity/name/code/Unit/type mutation is blocked when referenced. Historical inactive records remain resolvable. Admin CRUD, status, and options routes are protected by Admin/web guard separation, organization middleware, audit middleware, and `masters.view/create/update/delete`; Frontend Users cannot manage the master. `AdminNavigation` contains one permission-aware Masters link. Audit Log captures create/update/activate/deactivate/delete decisions with before/after values and does not log lookups.

## Verification

Focused contract coverage verifies canonical Item reuse, Chemical Item Type enforcement, Unit/tax validation contracts, duplicate/reference lifecycle rules, RBAC/navigation, active-only options, and the absence of a separate Chemical table or recipe engine. The required quality gate and full regression are run with the final change set. Live `blackgrd` remains protected; no destructive command, migration, maintenance change, or live write is authorized by this task.
