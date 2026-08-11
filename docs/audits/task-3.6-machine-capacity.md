# Task 3.6 — Machine Capacity Audit

## Findings

The reviewed baseline contained the canonical `machines` table and Machine Master from Task 3.5, but no machine capacity column, capacity table, capacity values, utilization logic, shift model, scheduler, or forecasting logic. The only unrelated capacity field is the legacy `warehouses.capacity` field and it is not reused. Live read-only inspection confirmed the ten preserved machines, including JET-DYEING IDs 7–9, and no machine-capacity table. Existing Work Orders and Dyeing Planning references were not rewritten.

The existing Unit Master is the canonical unit source. Live data has active Meter (ID 2) and Kg (ID 4), plus deleted legacy rows. No capacity values were invented or backfilled.

## Implementation boundary

Task 3.6 adds `machine_capacities` as a separate company-scoped child configuration. Each non-deleted machine has at most one configuration enforced by the service. The record stores only a positive capacity value and Unit Master reference, with Active/Inactive/Deleted state metadata and audit fields. The admin CRUD is searchable, paginated, RBAC-protected, and audit logged.

Active machine and active unit validation is server-side. Forged IDs, inactive machines, inactive/deleted units, non-positive values, and duplicate current configurations are rejected. Removing a configuration is logical deletion; machine identity, IDs, historical assignments, Work Orders, and planning rows are untouched.

## Explicit non-goals

No production transaction changes, utilization engine, shifts, automatic scheduling, load balancing, forecasting, maintenance system, or capacity-based Work Order behavior were added. Maintenance mode remains off and the live database is not migrated by this task.
