# Task 3.17 — Fabric Quality Master Audit

## Findings

- No existing canonical Fabric Quality table/model/controller was present in the repository at baseline 000670c940f7cd37837795c3cff9f5c4ed9b8bbd.
- fabric_qualities and App\Models\FabricQuality are now the one canonical master. No legacy records were resequenced, remapped, merged, or deleted.
- Existing quality-like data is grey_quality, a transaction/stock/work-order text snapshot. It remains unchanged and is not dynamically replaced by current master values.
- No fabric_quality_id or quality_id column currently exists on Item, Sale Order Item, Work Order Item, Lab Test, production, warehouse, inspection, report, or recipe tables. No guessed mappings were made.

## Business boundaries

Fabric Quality Master defines reusable fabric specification identity; it does not define colour, coating, printing position, or order-specific production workflow. The implemented fields are quality name/code, description, current ERP-compatible GSM and width notations, display order, and status. EPI/PPI, construction, weave, reed, and pick were not invented because no canonical current schema/data usage supports them.

Item Master remains separate. Item Yarn Requirements remain Item-owned; no recipe ownership redesign occurred. Colour/Shade, coating, printing route, Process Master, and transaction-specific production remain separate.

## Lifecycle, references, and authorization

Identity is normalized name + GSM + width, with non-empty code unique within active/non-deleted company records. Active and inactive qualities remain readable. Deletion is rejected for all quality identities, with referenced qualities explicitly protected; deactivation is preferred. Once a quality is referenced, name/code/GSM/width cannot change. Historical inactive references and transaction snapshots remain preserved.

Admin routes use Admin guard and masters.* RBAC, backend route enforcement, organization scope, permission-aware AdminNavigation, and centralized AuditLogger. Frontend users do not manage the master.

## Hard-coded quality inventory

The repository inventory found grey_quality in Sale Order creation/update/search/printing, Work Order propagation, Work Process Requirement propagation, Warehouse stock/in/out/balance, Job Mill Work, and reports/views. These are intentionally retained snapshot/compatibility paths; Task 3.17 does not broadly refactor operational flows.

## Database safety and verification

One additive migration creates the canonical table; no live database migration, backup, SHA-256, or data mapping was performed. blackgrd remained protected, DatabaseSafetyGuard remained enabled, and maintenance mode remained off. The disposable schema path and reviewed migration must be used for migration verification. Task 3.18 and later masters are out of scope.
