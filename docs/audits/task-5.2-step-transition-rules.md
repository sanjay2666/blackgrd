# Task 5.2 Step Transition Rules audit

## Starting point and legacy discovery

Baseline was `ebb782fe2a5492dac33b2b74ebeba93a71cdaab7` on clean `main`, equal to `origin/main`. No schema change was needed: Task 5.1 already provides company-scoped Workflow Definition, Version, and ordered Step records; Task 2.6 already provides company-scoped potential edges in `process_item_allowed_next`.

Legacy Work Order completion remains intentionally untouched. `WorkOrderController` has process-specific inspection handlers that infer a successor from `ProcessItem` IDs (for example `id > current process_type_id`), increment `process_sl_no_last`, create child Work Orders, and retain historic `print_position` handling. `work_orders` also retains `process_type_id`, `process_sl_no`, `parent_work_order_id`, and legacy completion/inspection fields. These are compatibility/runtime fields, not the Sale Order Item workflow route authority.

## Implementation

- Added `WorkflowStepTransitionRuleService`.
- It requires a current-company Sale Order Item with matching `workflow_definition_id` and `workflow_version_id`.
- It accepts only a Published assigned Version, verifies company ownership of the Definition, Version, Steps, and Processes, and requires every Process to be Active at controlled-transition time.
- `currentStep()` validates that the claimed current Process belongs to the assigned Version.
- `resolveNextStep()` returns only sequence `N + 1`; it returns `null` at the final Step.
- `validateTransition()` rejects jumps, backward requests, final-step requests, and Processes outside the assigned route.
- Each resolved edge is checked against current-company `process_item_allowed_next` records, failing closed when configuration does not permit it.

Workflow publication now checks every adjacent stored Step pair through the same service. A Draft Version cannot be published when an edge is absent from Process Configuration, or when one of its Processes is missing, inactive, or deleted. Published Step rows remain read-only; no transition path writes, reorders, or backfills Workflow Versions.

## Route authority

`process_item_allowed_next` defines possible edges only. `workflow_version_steps` determines the selected ordered route for the assigned Sale Order Item. Consequently both Dyeing → Printing → Coating and Dyeing → Coating → Printing are valid when their respective edges are configured, and neither Coating Master nor a global process map decides Printing position.

## Compatibility and scope

No migration, data backfill, Work Order rewrite, UI module, route, permission, controller endpoint, optional/skip/repeat behavior, lot identity, execution mode runtime flow, or idempotency engine was introduced. Legacy records without workflow assignment are not passed through the new rule service and retain existing operational behavior. RBAC remains unchanged because this only strengthens the existing Workflow Version publication action, which already uses `processes.update`.

## Disposable MySQL verification

Focused integration verification ran against `blackgrd_schema_testing` using the existing schema and transaction rollback isolation. It covered normal linear next-step resolution, both printing positions, final-step handling, jump/backward/out-of-route rejection, publication rejection for an unconfigured edge, published/matching-version requirements, version snapshot behavior, inactive Process rejection, and cross-company rejection.
