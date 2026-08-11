# Task 2.3 — Department Access Audit

Baseline `83035e53e482d88d24b35e8113b342dd3f2228a8` passed the initial quality gate.

## Findings and decisions

- Existing organization access was `user_organization_access` with one optional `department_id`; it is retained as primary/home context.
- Employee `individuals.department_id` is preserved as Employee Master data and is not reused as User authorization.
- No existing User↔Department access pivot existed. `user_department_access` is now canonical and supports multiple Departments.
- Backfill copies only explicit active organization Department assignments that belong to the same company. Null/ambiguous assignments are preserved without broad access.
- Access is deny-by-default for Department-owned data when no active pivot row exists. No implicit all-Departments capability was found or added.
- Department assignment validation requires the canonical company and active Department; Department factory hierarchy remains on the Department record.
- Existing Admin/web guards and RBAC remain separate. Admin User Management uses `users.manage`; Frontend Users cannot reach these routes.
- Assignment changes are immediate because runtime resolution reads active pivot rows per request. Inactive Departments remain historical but cannot be assigned or used for active access.

## Scope audit

The current organization migration adds `company_id` broadly, but most operational Work Order, WPR, Warehouse, inspection, report, print, and AJAX records do not expose a canonical Department ownership column. Their process-type/department-like values are not interchangeable with Department Master IDs. No blanket filters were added. The centralized `DepartmentAccessService` provides the required server-side resolver and query helper for modules once direct ownership is established; callers must protect list, detail, mutation, AJAX, print, and export paths together.

## Verification

The migration is additive and preserves historical rows. Live `blackgrd` was not modified. The final report records test and quality-gate results.
