# Frontend User Management

This is a single-company ERP with two separate identity boundaries:

- **Admin Panel** uses the `admin` guard and `Admin` model for Admin accounts.
- **Frontend Panel** uses the `web` guard and `User` model for Frontend User
  accounts.

The Admin-side User Management module only accepts records whose `user_type` is
`User`. It cannot create, edit, activate, deactivate, delete, or assign roles to
Admin identities.

The Frontend User Permission screen assigns individual route pages/actions/AJAX
through `all_pages` and active `user_web_pages` rows. This is independent from
Department Access: page permission answers whether the endpoint may be called;
Department Access answers which department/process data may be operated after
that authorization succeeds.

## Lifecycle

Users are listed within the current company organization access and can be
searched by name, email, or linked employee mobile. Status and Frontend role
filters, pagination, profile editing, role synchronization, and organization
access editing use the existing `users`, `user_organization_access`, and RBAC
tables. Profile edits do not change passwords unless the separate password reset
action is explicitly submitted.

Account deactivation is the supported removal workflow. Hard deletion is not
offered because organization access, RBAC assignments, permission overrides,
notifications, login records, and business ownership/reference fields may point
to a User. Historical records are preserved.

## Roles and permissions

Role assignment reuses `RoleManagementService` and is restricted server-side to
active company-scoped `Frontend` roles in the current company. System roles,
Admin roles, and Super Admin authority cannot be assigned through this module.
Individual Allow/Deny/Inherit overrides remain on the existing permission
screen; the User list links to that screen without duplicating its logic.

Existing `users.manage` is the canonical permission for this module, preserving
current Admin assignments. Role synchronization additionally requires the
existing `roles.assign` permission.

## Passwords and audit

Creation and administrative reset use Laravel hashing and strong confirmed
password validation. Password values are never displayed, placed in audit
payloads, or logged. Reset invalidates database sessions and pending reset
tokens for the account.

Create, profile update, status change, role assignment/revocation, and password
reset actions use the centralized Audit Log. Audit payloads contain meaningful
before/after profile/status data but no secrets.
