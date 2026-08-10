# Operational status lifecycle (Task 1.3C)

## Purpose

Task 1.3C separates business lifecycle state from the shared `status` record-lifecycle field. The shared field continues to mean only `Active`, `Inactive`, or `Deleted`; operational progress is represented by module-specific, string-backed enums and indexed nullable `VARCHAR(40)` columns.

Legacy columns remain available during the compatibility period. New application writes use transition actions that validate the state change, update the canonical field, and dual-write the legacy representation where one exists. Unknown legacy values are never coerced to a default business result.

## Canonical domains

| Module | Canonical columns | Allowed lifecycle values |
| --- | --- | --- |
| Sale Order | `sale_orders.document_status` | `draft`, `pending_approval`, `approved`, `in_production`, `partially_dispatched`, `completed`, `on_hold`, `rejected`, `cancelled` |
| Purchase Order | `purchase_orders.document_status`; `purchase_order_items.receipt_status` | Document: `draft`, `pending_approval`, `approved`, `partially_received`, `received`, `closed`, `on_hold`, `cancelled`; receipt: `pending`, `partially_received`, `received`, `rejected`, `closed` |
| Work Order | `work_orders.execution_status`; `work_orders.inspection_status` | Execution: `created`, `material_requested`, `material_allotted`, `ready`, `started`, `partially_completed`, `completed`, `inspection_pending`, `passed`, `rejected`, `rework`, `on_hold`, `cancelled`; inspection: `pending`, `completed`, `cancelled` |
| Work Process Requirement | `work_process_requirements.requirement_status`; `allocation_status` | Requirement: `created`, `sent_to_warehouse`, `pending`, `partially_allotted`, `allotted`, `accepted`, `denied`, `cancelled`, `closed`; allocation: `unallocated`, `partially_allocated`, `allocated`, `released`, `cancelled` |
| Inspection | `work_inspections.inspection_status`; `inspection_result`; `work_inspection_details.inspection_result` | Status: `pending`, `completed`, `cancelled`; result: `pending`, `passed`, `partially_passed`, `rejected`, `defective`, `rework`, `completed` |
| Warehouse inventory | `movement_status`, `allocation_status`, and `receipt_status` on the applicable inward/outward/balance/stock records | Movement: `draft`, `posted`, `partially_posted`, `reversed`, `cancelled`; allocation: `unallocated`, `partially_allocated`, `allocated`, `released`, `cancelled`; receipt: `pending`, `partially_received`, `received`, `rejected`, `closed` |
| Gate Pass | `gate_passes.gate_pass_status` | `draft`, `issued`, `partially_received`, `received`, `cancelled`, `closed` |
| Job Mill Work | `stock_mill_dispatches.job_work_status`; receipt status on dispatch/receipt lines | Job: `requirement_raised`, `vendor_selected`, `approved`, `dispatched`, `partially_received`, `received`, `inspection_pending`, `shortage_pending`, `rework`, `closed`, `cancelled`; receipt values use the shared receipt domain |

## Transition boundary

`OperationalStatusTransitionMap` is the allow-list for state changes. `OperationalStatusTransitionService` rejects invalid transitions before saving, dispatches `OperationalStatusTransitioned` after a successful save, and supports an explicit audited `force` path for reconciliation only. Module actions are the application boundary:

- `TransitionSaleOrder` and `TransitionPurchaseOrder`
- `TransitionWorkOrder` and `TransitionWorkRequirement`
- `RecordInspectionResult`
- `TransitionInventoryMovement`, `TransitionGatePass`, and `TransitionJobWork`

Terminal states do not reopen through normal transitions. Record deletion/restoration remains independent of business lifecycle transitions.

## Legacy mapping and compatibility

- Sale Order backfill derives `in_production` from an existing non-deleted work order and `completed` only when every non-deleted line is complete.
- Purchase line receipt state derives only from ordered and received quantities. The document state rolls up from its active lines.
- Work Order execution derives from existing work, start-date, warehouse receipt, allotment, and requirement evidence in precedence order. Inspection state remains separate.
- WPR `is_accept=2` is `denied`; accepted/allotted quantities distinguish accepted, partially allotted, and allocation state. Partial allotment remains legacy accepted (`is_accept=1`) during dual-write.
- Legacy inspection `Completed` is retained as the canonical `completed` result; it is not guessed to mean `passed`.
- Existing posted warehouse movements are represented as `posted`. Deleted movements are left `NULL`, not guessed to be reversed or cancelled.
- A received Gate Pass maps to `received`; an issued number maps to `issued`. Deleted Gate Passes remain `NULL`, because deletion does not prove cancellation.
- Existing job dispatch receipt flags/meters map to dispatched, partially received, or received. Deleted rows remain `NULL`.

## Migration and rollback policy

The eight Task 1.3C migrations are additive and reversible. Each `down()` removes only its added indexes and columns. Live application is restricted to `db:apply-reviewed-operational-statuses`, which requires exact `blackgrd` connection identity, maintenance mode, a three-part verified backup manifest, explicit queue/scheduler stop confirmation, an exact pending set, and pinned SHA-256 hashes. It invokes the migrator with exact file paths; it does not expose unrestricted migration execution.

Disposable verification is restricted to exact `blackgrd_schema_testing`. `db:verify-operational-status-backfill` snapshots row counts, ordered ID hashes, selected quantity totals, and auto-increment values, then performs exact apply, rollback, and re-apply verification.

## Follow-up boundaries

Approval workflow, generalized workflow orchestration, and a new inventory ledger are consumers of these statuses, not part of Task 1.3C. Legacy columns must not be removed until all reads and integrations have been migrated and independently audited. The dormant Lab Test module remains outside this task.
