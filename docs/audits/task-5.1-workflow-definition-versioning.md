# Task 5.1 Workflow Definition and Versioning audit

## Existing architecture

No workflow-definition or route-version tables existed. Canonical Process Master is `process_items` / `App\Models\ProcessItem`; known legacy identities (Warping, Weaving, Dyeing, Coating) remain untouched. Work Order sequencing uses `process_type_id`, `process_sl_no`, `process_sl_no_last`, parent/child links, and legacy `print_position`. None was promoted to reusable workflow authority.

## Implemented foundation

- `workflow_definitions`: company-scoped stable code/name/description/status.
- `workflow_versions`: company-scoped Definition revisions, unique monotonic version number, Draft/Finalized state, finalization audit metadata.
- `workflow_version_steps`: company-scoped ordered positive sequence and FK to canonical Process; repeated Process IDs remain structurally valid.
- Admin controller: create Definition + first Draft, metadata/status update, view, add/update/remove Draft steps, copy a new Draft revision, finalize, and delete only unreferenced Draft versions.
- Finalized versions are server-side immutable. Revision allocation locks the Definition/latest version and is protected by unique database constraints.

## Explicit exclusions

No guessed workflow routes or seed data, historical backfill, Sale Order Item route selection/snapshot, Work Order generation, printing-position authority, transition engine, approval engine, optional/skip/repeat execution, lot identity, or production execution was added. Printing Design and Coating Type remain independent of process order.

RBAC reuses the existing Admin-only `processes.view/create/update/delete` permissions; Frontend users receive no workflow-management route. The compact Bootstrap 3.3.7 pages are a minimum management/test surface and are not the future Process Configuration UI.

## Safety and verification

The migration is additive and has rollback order for steps, versions, then definitions. No live `blackgrd` migration or data mutation is performed. Existing Sale Orders, Work Orders, Process IDs, legacy sequencing, and `print_position` data remain unchanged. Focused structural tests cover version/sequence uniqueness, Process FK, repeated-step compatibility, immutability/revision behavior, future-task boundaries, RBAC, and navigation. Disposable MySQL migration verification is required for the final handoff when the approved `blackgrd_schema_testing` environment is available.
