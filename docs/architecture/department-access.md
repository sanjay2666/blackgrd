# Department Access

Department Access is a second authorization dimension. **RBAC answers WHAT** a user can do; **Department Access answers WHERE** they can do it. A request needs both the required permission and, when the record is Department-owned, access to that Department. Department Access never grants an RBAC permission.

## Canonical relationship

`user_department_access` is the canonical User-to-Department access relationship. It is company-scoped, supports multiple Departments, uses one row per User/company/Department, and retains inactive rows as history. Only active Departments in the canonical company may be newly assigned. There is deliberately no all-Departments fallback and no multi-company scope.

`user_organization_access.department_id` remains the User's primary/home Department in the existing organization context. `individuals.department_id` remains the Employee Master relationship and is not an authorization grant. Explicit legacy organization Department values are backfilled into the canonical relationship; null or ambiguous values are not expanded into broad access.

The production Process Master maps to canonical organizational Departments: Warping and Weaving to Weaving; Dyeing to Dyeing; Printing, D-Printing, and C-Printing to Printing; Coating to Coating; Packaging to Packaging; and a Warehouse Process, when one exists, to Warehouse. This mapping is company-scoped and is the source for Work Order visibility; it does not alter Printing/Coating routing or Work Order creation.

An ordinary Frontend User with no canonical Department rows has no access to Department-owned functionality. Company-global records remain company-global. A Department becoming inactive does not delete historical access rows and is excluded from new assignment and runtime active access.

## Enforcement rule

Use `App\Services\DepartmentAccessService` for allowed IDs, record checks, and supported query scoping. Do not query the pivot from Blade or infer access from role names, user IDs, email, missing rows, or encrypted identifiers. For a Department-owned module:

`identify Department ownership -> enforce RBAC -> enforce Department scope server-side -> scope listings -> validate mutations -> protect AJAX/print/export -> add tests -> run quality:check`

The current operational schema has company ownership on many transaction tables, but most Work Order/WPR/Warehouse flows derive process movement rather than carrying a canonical `department_id`. Those paths are not blanket-scoped in this task; adding guessed filters would change business behaviour. They must be mapped when their Department ownership is made canonical. Direct Department-owned queries should use the service's `scope()` helper and validate submitted/derived Department IDs with `mayAccess()`.

## Administration and audit

Existing Admin User Management uses the `users.manage` permission and separate Admin guard. The Department Access action shows active Departments, identifies the home Department, supports multiple selections, and never lets Frontend Users modify their own access. Grants and removals are recorded by `AuditLogger` as `user_department_access_changed` with the Admin actor and before/after Department IDs.

Its Select All Active Departments control selects the same individual `department_ids[]` checkboxes submitted by the form. It creates no all-access flag or authorization bypass.
