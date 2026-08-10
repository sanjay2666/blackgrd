# Task 2.2 — User Management Audit

## Scope and identity boundary

The existing `users` table is shared by two discriminated account types. The
`admin` guard uses `Admin` with `user_type = Admin`; the `web` guard uses `User`
with `user_type = User`. Task 2.2 adds Admin-side management for Frontend Users
only and does not merge or redesign authentication.

## Implemented controls

- Searchable, paginated Admin-side listing with status and Frontend-role filters.
- Create and edit operations preserve the User identity type, organization
  access, and existing permission override boundary.
- Existing company organization access supports branch, factory, department,
  and employee-link fields where present.
- Only active current-company Frontend roles can be assigned. Reserved/System
  and Admin roles are rejected by backend scope and panel checks.
- Activation/deactivation is supported; self-deactivation is rejected.
- Hard deletion is intentionally unavailable. Restrictive foreign keys and
  historical references make deactivation the safe lifecycle operation.
- Password creation and reset use Laravel hashing, confirmation, strong rules,
  session/reset-token invalidation, and no secret audit values.
- Central `AuditLogger` records user creation, profile changes, status changes,
  role changes through the existing role service, and administrative resets.

## Preservation and database safety

No migration or schema change was required. Existing IDs, emails, passwords,
roles, overrides, and historical references are not rewritten by this task.
The protected live `blackgrd` database was not modified, destructive commands
were not run, DatabaseSafetyGuard remained active, and maintenance mode stayed
off.
