# Machine Master

Machine Master defines physical machine identity; Machine Capacity and production scheduling are separate concerns.

## Canonical architecture

The canonical table is `machines`, represented by `App\Models\Machine`. Existing IDs are preserved. The live baseline contains IDs 1–10, all in canonical company 1:

| IDs | Current records |
| --- | --- |
| 1 | Clartech Warping Machine |
| 2–6 | WATER JET-01 through WATER JET-05 |
| 7–9 | JET-DYEING -01 through JET-DYEING -03 |
| 10 | STENTER-01 |

The supported identity fields are `name`, legacy process foreign-key field `process_wise`, `company_id`, optional `factory_id`, optional `department_id`, `status`, and the legacy `is_busy` flag/audit timestamps. There is no machine code/number, type/category, capacity, or shift column in the current schema. No migration was added and the historical Financial Year removal migration was not changed.

## Scope and relationships

Company scope is supplied by `CurrentOrganizationContext`; a company selector or tenant switching is not available. A Machine belongs to an active `ProcessItem` through `process_wise`. Department and Process remain separate identities. Optional Department and Factory values must be active, belong to the canonical company, and a department assigned to a factory must match that factory.

Because the schema has no code column, duplicate prevention uses canonical company + factory + department + process + normalized machine name. Existing names and IDs are never regenerated. A code/number can be introduced only by a separately reviewed master-data task if the business requires one.

## Lifecycle and safety

Active and Inactive records are supported. Inactive machines remain historically readable and are excluded from new active-master selections. Machines are not hard-deleted. The exposed destroy operation rejects deletion and directs administrators to deactivation.

Deactivation is blocked when active, non-deleted Work Orders or planned Dyeing requirements reference the machine. Any reference protects the historical Process, Department, and Factory identity from reassignment. Descriptive name changes remain possible where they do not violate the scoped duplicate rule. A referenced Machine must not be reassigned to another Process/Department/Factory when doing so would change historical operational meaning.

## Compatibility boundaries

Work Orders use `machine_id`; Dyeing planning uses `dyeing_machine_id` on Work Process Requirements. Existing JET-DYEING IDs 7–9 and all historical references remain unchanged. The master does not redesign selection endpoints or Dyeing Planning contracts. Capacity calculation, utilization, load balancing, availability forecasting, and scheduling belong to Task 3.6. Shift schedules belong to Task 3.7. The legacy `is_busy` field is preserved and is not converted into a capacity or shift engine.

## Authorization and audit

Machine CRUD remains Admin-only under the existing `auth:admin`, organization, RBAC, and audit middleware. Navigation uses the existing `masters.view` permission under `Masters`; Frontend User Department Access does not authorize this Admin master. Create, update, activate, and deactivate mutations use centralized `AuditLogger` events with before/after values. Reads and machine-selection reads are not logged by the master.
