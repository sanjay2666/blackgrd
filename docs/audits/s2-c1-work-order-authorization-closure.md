# S2-C1 — Work Order Authorization Closure

## Finding closed

The Stage 2 final review identified frontend Work Order visibility branches in
`WorkOrderController` that granted process visibility based on hard-coded User
IDs `11`, `13`, `21`, and `26`. Those branches were present in the Work Order
listing and Work Order totals/report paths.

Frontend Work Order authorization must not depend on a User's numeric ID.

## Canonical replacement

Work Order visibility now derives permitted Process IDs from the existing
`DepartmentAccessService`:

1. Read the authenticated frontend User's active Department Access rows for the
   canonical company.
2. Resolve active `ProcessItem` rows whose `department_id` belongs to those
   Departments.
3. Scope Work Order and Inspection queries to those Process IDs.

The Process-to-Department relationship is read from `process_items.department_id`.
Process IDs are not treated as Department IDs, and Department names are not
used for authorization.

RBAC determines WHAT an authenticated User may do; Department Access determines
WHERE the User may perform that operation.

The existing `auth:web`, organization, RBAC, and audit middleware remains the
server-side WHAT/action boundary. The new query scope supplies the WHERE/data
boundary. No dynamic Department permission keys or Department-specific roles
were introduced.

## Affected paths

- `show-workorders` / `WorkOrderController@index`
- `workorders.totals` / `WorkOrderController@workOrderTotals`
- `show-workorder-inspection` / `WorkOrderController@show_workorder_inspection_report`
- `ajax_script/getWorkOrderDetails` / direct Work Order detail lookup
- `print-workorder-gatepass` / direct Work Order gate-pass lookup
- `receive-work-item` / inspection-to-Work-Order lookup

Inspection workflow, Work Order generation, sequencing, statuses, parent/child
links, warehouse data, and historical transactions were not redesigned or
modified.

## Compatibility and limitations

Process records without an active canonical Department relationship are not
included in the new frontend operational scope. The implementation does not
guess a Department from a Process ID, Process name, Employee, designation, or
User identity. Such legacy ownership metadata must be reviewed before future
operational migration tasks depend on it.

No schema or database changes were required.

## Verification

Focused tests cover:

- Process/Department-based visibility;
- removal of the four legacy User-ID authorization branches;
- preservation of canonical RBAC route permissions;
- separation from Employee home Department and designation logic;
- direct Work Order and inspection route scope contracts.

Full regression and the Quality Gate are required before commit/push.
