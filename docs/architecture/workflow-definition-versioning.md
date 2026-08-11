# Workflow Definition and Versioning architecture

The repository had no canonical Workflow Definition tables. `process_items` / `ProcessItem` is the existing canonical Process Master, while Work Orders retain legacy `process_type_id`, `process_sl_no`, `process_sl_no_last`, parent/child links, and `print_position` behavior. Those fields remain downstream compatibility/history and are not used as Workflow Definition authority.

## Canonical structure

`workflow_definitions` is the reusable company-scoped identity: stable code, name, description, and Active/Inactive/Deleted lifecycle. Creating one atomically creates Version 1 in Draft state. `workflow_versions` belongs to a Definition and has a monotonic, unique version number plus Draft/Finalized lifecycle metadata. `workflow_version_steps` belongs to a Version and stores a positive unique sequence, canonical `process_id`, and optional presentation label/description.

Workflow Definition describes a reusable ordered manufacturing route, while the historical route executed for a Sale Order Item must ultimately be preserved as an immutable order-specific workflow snapshot.

## Lifecycle and revisions

Draft versions can have steps added, edited, or removed. Finalization requires at least one step, positive unique sequence values, and existing non-deleted Process references. A finalized Workflow Version is immutable; changes require creation of a new version. Revision creation locks the Definition and latest Version, allocates the next number in a transaction, copies all ordered steps, and leaves the source unchanged. A finalized version cannot be deleted; an unreferenced Draft may be removed after its steps are removed.

The unique Definition/version and Version/sequence constraints provide database-level integrity. Repeated Process IDs are intentionally allowed because only `(workflow_version_id, sequence)` is unique; future repeat-step semantics remain Task 5.3.

## Boundaries

Printing is an ordinary Process step, so both Dyeing → Printing → Coating and Dyeing → Coating → Printing are representable without a global default. Printing Design and Coating Type must never determine global process order. Task 5.1 does not implement transition rules, optional/skip/repeat execution, route selection, or Work Order generation. No Sale Order Item or historical Work Order is backfilled or changed. Future Task 4.5 will apply a selected version and preserve the order-specific snapshot.

Workflow steps validate active Process records when added to a Draft. A Process later becoming inactive remains resolvable by a historical finalized version; deleted or missing Process references block finalization. Definitions are company-scoped through the canonical organization context and Admin-only `processes.*` RBAC. Mutations use centralized Audit Log; ordinary reads are not logged.
