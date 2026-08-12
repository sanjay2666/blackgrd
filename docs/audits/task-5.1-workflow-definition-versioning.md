# Task 5.1 Workflow Definition and Versioning audit

## Existing architecture

No workflow-definition or route-version tables existed. Canonical Process Master is `process_items` / `App\Models\ProcessItem`; known legacy identities (Warping, Weaving, Dyeing, Coating) remain untouched. Work Order sequencing uses `process_type_id`, `process_sl_no`, `process_sl_no_last`, parent/child links, and legacy `print_position`. None was promoted to reusable workflow authority.

## Implemented foundation

- `workflow_definitions`: company-scoped stable code/name/description/status.
- `workflow_versions`: company-scoped Definition revisions, unique monotonic version number, Draft/Published state, current flag, effective date, remarks, and publication audit metadata.
- `workflow_version_steps`: company-scoped ordered positive sequence and FK to canonical Process; both sequence and Process are unique within one version.
- `sale_order_items`: nullable canonical Definition and published Version references; no historical row is backfilled.
- Admin controller: create Definition + first Draft, metadata/status update, view, add/update/remove Draft steps, copy a new Draft revision, finalize, and delete only unreferenced Draft versions.
- Published versions are server-side immutable. Revision allocation locks the Definition/latest version and is protected by unique database constraints. Publishing changes only the current-version pointer; prior snapshots remain unchanged.

## Explicit exclusions

No guessed workflow seed data, historical backfill, Work Order generation, printing-position compatibility rewrite, transition engine, approval engine, optional/skip/repeat execution, lot identity, or production execution was added. Printing Design and Coating Type remain independent of process order. A dedicated Admin assignment page can store a published version reference before downstream Work Order history exists, but existing Work Order behavior does not consume it in Task 5.1.

RBAC reuses the existing Admin-only `processes.view/create/update/delete` permissions; Frontend users receive no workflow-management route. The compact Bootstrap 3.3.7 pages are a minimum management/test surface and are not the future Process Configuration UI.

## Safety and verification

The reviewed migration is additive, adds nullable Sale Order Item references, and rolls those references back before steps, versions, and definitions. Existing Sale Orders, Work Orders, Process IDs, legacy sequencing, and `print_position` data remain unchanged. The hash-pinned live command requires maintenance mode, stopped writers, verified full/affected/migration-ledger backups, exact database identity, and preservation hashes. Focused tests cover company isolation, creation, version relationships, ordered unique steps, publication/immutability, Sale Order Item references, both Printing positions, and disposable rollback/reapply safety.

## Verification and live apply

Disposable `blackgrd_schema_testing` verification completed with the exact Task 5.1 migration only: apply, schema inspection, focused tests, exact rollback to Pending, reapply, focused tests, and a direct down/up preservation test. The final focused disposable result was 7 tests, 30 assertions. Workflow tables and Sale Order Item references were empty/nullable during migration verification, and business-table row counts plus ordered ID hashes were unchanged.

The live apply used reviewed migration SHA-256 `f248d07f7248cb7d261a2a9ae4809b4d29560f1b51ebb172f0537e618094931e`. Final PSR-12-only formatting changed no migration operation and produced the committed/source hash `8eb036d0ef536ddff307b1d9fbaac88124e2a77a2f08f5d8d18a6d77a4e5bed9`, which is pinned by the command and was reverified through the disposable down/up cycle. With the application in maintenance mode and no Laravel queue/scheduler/Horizon writer found, verified full, `sale_order_items`, and `migrations` backups were stored under `storage/app/backups/task-5.1-20260812_070128`. The command dry-run passed, then `db:apply-reviewed-workflow-definition` applied only the Workflow migration to live `blackgrd`. It was recorded as batch 50, schema/preservation checks passed, and the application was returned to live mode.

Task 5.1 intentionally activates 13 Admin Workflow routes. The registered-route source of truth is therefore 483: the stabilized 470-route baseline plus 11 Definition/version/step routes and 2 Sale Order Item assignment routes. Existing runtime routes were not otherwise changed.
