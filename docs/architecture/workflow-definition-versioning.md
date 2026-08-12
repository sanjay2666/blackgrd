# Workflow Definition and Versioning architecture

The repository had no canonical Workflow Definition tables. `process_items` / `ProcessItem` is the existing canonical Process Master, while Work Orders retain legacy `process_type_id`, `process_sl_no`, `process_sl_no_last`, parent/child links, and `print_position` behavior. Those fields remain downstream compatibility/history and are not used as Workflow Definition authority.

## Canonical structure

`workflow_definitions` is the reusable company-scoped identity: stable code, name, description, and Active/Inactive/Deleted lifecycle. Creating one atomically creates Version 1 in Draft state. `workflow_versions` belongs to a Definition and has a monotonic, unique version number, Draft/Published state, current-version flag, effective date, remarks, and publication audit metadata. `workflow_version_steps` belongs to a Version and stores a positive unique sequence, canonical `process_id`, and optional presentation label/description.

Workflow Definition describes a reusable ordered manufacturing route. A Sale Order Item stores nullable `workflow_definition_id` and `workflow_version_id` references. Only published versions can be selected. Because published steps are immutable, the selected version is the order item's route snapshot even after a newer version becomes current.

## Lifecycle and revisions

Draft versions can have steps added, edited, or removed. Publication requires at least one step, consecutive sequence values starting at 1, unique Process references, and existing non-deleted Process records. A published Workflow Version is immutable; changes require a new version. Revision creation locks the Definition and latest Version, allocates the next number in a transaction, copies all ordered steps, and leaves the source unchanged. Publishing makes that version current and clears the previous current flag without changing any historical version. Published versions cannot be deleted; an unreferenced Draft may be removed.

Unique Definition/version, Version/sequence, and Version/Process constraints provide database-level integrity. A Process cannot repeat within a Task 5.1 version; optional, skip, and repeat semantics remain Task 5.3.

## Boundaries

Printing is an ordinary Process step, so both Dyeing → Printing → Coating and Dyeing → Coating → Printing are representable without a global default. Printing Design and Coating Type never determine process order. Task 5.1 adds an Admin Sale Order Item assignment page and stores the selected reference only; it does not implement transition rules, optional/skip/repeat execution, automatic Work Order generation, or process execution. Assignment changes are blocked after downstream Work Order history exists. Existing Sale Order Items and Work Orders are not backfilled or changed.

Workflow steps validate active Process records when added to a Draft. A Process later becoming inactive remains resolvable by a historical published version; deleted or missing Process references block publication. Definitions, versions, steps, and Sale Order Item selection queries use canonical company scope. Admin management reuses Admin-only `processes.*` RBAC. Mutations use centralized Audit Log; ordinary reads are not logged.
