# Workflow Definition and Versioning architecture

> Current scope: Task 5.3 implements optional, controlled-skip, and configured-repeat definition semantics only. It does not add runtime loop counters, rework behavior, transition persistence, idempotency, or Work Order generation.

The repository had no canonical Workflow Definition tables. `process_items` / `ProcessItem` is the existing canonical Process Master, while Work Orders retain legacy `process_type_id`, `process_sl_no`, `process_sl_no_last`, parent/child links, and `print_position` behavior. Those fields remain downstream compatibility/history and are not used as Workflow Definition authority.

## Canonical structure

`workflow_definitions` is the reusable company-scoped identity: stable code, name, description, and Active/Inactive/Deleted lifecycle. Creating one atomically creates Version 1 in Draft state. `workflow_versions` belongs to a Definition and has a monotonic, unique version number, Draft/Published state, current-version flag, effective date, remarks, and publication audit metadata. `workflow_version_steps` belongs to a Version and stores a positive unique sequence, canonical `process_id`, an `is_required` snapshot flag, and optional presentation label/description.

Workflow Definition describes a reusable ordered manufacturing route. A Sale Order Item stores nullable `workflow_definition_id` and `workflow_version_id` references. Only published versions can be selected. Because published steps are immutable, the selected version is the order item's route snapshot even after a newer version becomes current.

## Lifecycle and revisions

Draft versions can have steps added, edited, or removed. Publication requires at least one step, consecutive sequence values starting at 1, a valid Required/Optional value, and existing non-deleted Process records. A canonical Process may occur at more than one distinct sequence in a Version: the sequence/step row is its occurrence identity. A published Workflow Version is immutable; changes require a new version. Revision creation locks the Definition and latest Version, allocates the next number in a transaction, copies all ordered steps including optionality, and leaves the source unchanged. Publishing makes that version current and clears the previous current flag without changing any historical version. Published versions cannot be deleted; an unreferenced Draft may be removed.

Unique Definition/version and Version/sequence constraints provide database-level integrity. Task 5.3 intentionally removes the Version/Process constraint while retaining the Version/sequence constraint, so repeats are explicit configured occurrences rather than duplicate Process Master records or runtime loops. Existing rows receive `is_required = true`.

## Boundaries

Printing is an ordinary Process step, so both Dyeing → Printing → Coating and Dyeing → Coating → Printing are representable without a global default. Printing Design and Coating Type never determine process order. Task 5.1 adds an Admin Sale Order Item assignment page and stores the selected reference only; it does not implement transition rules, optional/skip/repeat execution, automatic Work Order generation, or process execution. Assignment changes are blocked after downstream Work Order history exists. Existing Sale Order Items and Work Orders are not backfilled or changed.

Workflow steps validate active Process records when added to a Draft. Publication also requires active Process records; a Process later becoming inactive remains readable for historical records but blocks a new workflow-controlled transition. Deleted or missing Process references also block publication and controlled transition validation. Definitions, versions, steps, and Sale Order Item selection queries use canonical company scope. Admin management reuses Admin-only `processes.*` RBAC. Mutations use centralized Audit Log; ordinary reads are not logged.

## Step transition rules

`WorkflowStepTransitionRuleService` is the reusable validation foundation for a workflow-controlled Sale Order Item. It reads the assigned published Version and its immutable ordered Steps. Process-only lookup is allowed only when that Process occurs once; repeated occurrences require the actual `WorkflowVersionStep` identity and otherwise fail closed. The normal `resolveNextStep()` result remains only sequence N + 1. `validateTransition()` additionally permits a requested later Step only when every intervening Step is Optional. The final Step resolves to no next Step. It rejects an unassigned, Draft, mismatched Definition/Version, non-adjacent required-step jump, backward, out-of-route, cross-company, inactive, or deleted Process transition.

`process_item_allowed_next` remains the potential-edge validation layer. Every adjacent pair and every effective optional-skip edge must be present when a Draft Version is published. The selected edge is rechecked when resolving a controlled transition. Therefore Process Configuration can prohibit an edge but cannot choose an order or silently authorize an optional skip. A configuration change can safely stop later controlled transitions until the ordered route is brought back into compliance; it never mutates the published version snapshot.

The service does not store runtime current-step state, transition history, idempotency keys, optional/skip/repeat behavior, or create Work Orders. Those concerns remain scheduled for Tasks 5.3, 5.4–5.8, and 4.6. Existing Work Orders without Sale Order Item workflow assignment continue through their legacy handlers unchanged. Those handlers still use `process_type_id`, process serial counters, parent/child Work Order links, and `print_position` compatibility behavior; none are an authority for a workflow-controlled route.
