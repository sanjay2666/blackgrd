# Task 1.7B — Single-Company RBAC Implementation

Status: schema, registry, panel-aware role system, live additive migration, approved deterministic bootstrap and exact route/action enforcement completed (2026-08-10).

## Delivered

The ERP is a single-company textile manufacturing system. The additive migration `2026_08_10_000003_create_rbac_tables` creates `roles`,
`permissions`, `role_permissions`, and `user_role_assignments` using the
approved primary-key types, harmless company ownership metadata, canonical
status values, audit columns, indexes, and restricted foreign keys. Role scope
distinguishes the reserved `System` Super Admin role from ordinary ERP roles;
company columns remain metadata for the existing organization foundation, not
tenant isolation or duplicate role systems.

Authentication remains two-panel: `admin` guard/Admin model for the Admin
Panel and `web` guard/User model for the frontend. Assignments store
`principal_type` and `principal_id`, while roles store `panel`, preventing
Admin/User numeric-ID collisions and preventing a role from crossing panels.

`PermissionRegistry` is the version-controlled source for the current-module
capability matrix. `rbac:sync` is deterministic and idempotent, synchronizes
metadata without deleting permissions, creates the reserved `super-admin`
role with the canonical registry permissions, and can create the ten approved
role templates with `--templates`. Templates are never
assigned automatically.

`AuthorizationService` resolves the authenticated identity, trusted current
organization, active/effective assignments, active roles and active registry
permissions once per request. It fails closed when identity or organization
context is missing. The reserved Super Admin decision is centralized on an
active assignment to the `super-admin` System role; no identity ID, email,
username, user type, or request value is trusted.

Role management is available under `admin/roles`. It lists ordinary ERP roles,
validates submitted permission keys against the registry and the manager's
effective set, and only lists users with active organization access. The
reserved Super Admin role is not editable or assignable through this UI.

The Admin template is for Admin-panel management. Sales, Purchase, Production,
Warehouse, Inspection and Accounts templates are frontend roles. Super Admin
is a reserved Admin-panel role.

## Live apply evidence

The reviewed hash-pinned migration was applied to `blackgrd` while maintenance
mode was active, with the preservation snapshot unchanged. `rbac:sync` then
created 125 active permissions and the ten ordinary templates plus the
reserved Super Admin role. Maintenance mode was turned OFF and caches were
cleared afterward.

Verified backup manifest: `E:\tmp\blackgrd-rbac-20260810\manifest.json`

| Backup | Size | SHA-256 |
|---|---:|---|
| `blackgrd-full.sql` | 646768 | `5025d48158298837fedc62a2e7d1ae9b0b193ec1918ffd8f6cec13581e57aa06` |
| `blackgrd-affected-tables.sql` | 38055 | `a5e23d0a8291b81931e7aff4ab2e2d907527d0cdec3570d2607ee7333ae0b58b` |
| `blackgrd-migrations.sql` | 5970 | `d9b7e4c964f4740031ca61bebe11ec3be98cb95b66a7973600a28d60eb893320` |

The live read-only bootstrap result is Admin ID 1 `admin@blackgrd.test`, User
ID 2 `unsanjay4@gmail.com`, and no active Admin account named
`uvsanjay48@gmail.com`. The approved bootstrap assigned Admin #1 to ordinary
Admin and User #2 to Frontend Administrator. The reserved Super Admin role
remains unassigned; no account was created for that email.

All 293 authenticated current routes are now covered by the exact central
registry: 291 require a permission and two logout routes are explicitly
allowlisted. Unknown authenticated routes fail closed. The map distinguishes
view/create/update/delete/cancel, operational, print and report permissions;
AJAX and unusual GET actions are listed explicitly rather than classified from
URL words.

Mapping examples: `saleorders.delete` → `sale-orders.cancel`,
`warehouse.breakMeter` → `warehouse.adjust`,
`admin.financial-years.set-current` → `financial-years.set-current`,
`admin.roles.assign.store` → `roles.assign`, and
`admin.login-attempts.destroy` → `security.delete`.

## Individual Frontend User permissions

Task 1.7B now includes Admin-side User Permission Management. The controller
loads an active Frontend User's active role assignments, inherited role
permissions, active user-specific overrides and computed final permissions;
Blade performs no queries. The Admin can set each assignable canonical
permission to Inherit, Allow for this User, or Deny for this User.

The additive live table is `user_permission_overrides`. It is keyed by
`user_id + permission_id`, stores Allow/Deny and audit/effective status, and
does not create permanent roles for one-off customization. Final resolution is
role permissions plus Allow overrides minus Deny overrides. Admin-only
companies/roles/users/security/settings permissions are excluded from the
Frontend catalog, and the service requires an authenticated Admin, active
Frontend User, active company access, `users.manage`, and manager possession
of each requested permission. Cache invalidation occurs immediately after a
successful save.

## Bootstrap and rollout safety

The exact bootstrap command is `rbac:bootstrap --execute
--confirm-database=<configured database>` and contains only the approved
Admin #1/User #2 preconditions. The future owner-controlled command is
`rbac:assign-super-admin <exact-email>`; it accepts only an existing active
Admin, never creates an account, rejects frontend Users, confirms unless
`--force` is supplied, and is idempotent.

## Verification record

The RBAC PHP files pass syntax checks and the RBAC routes register under the
organization and permission middleware. `php artisan db:safety-check` remains
blocked for the configured live database, as required. No live schema, data,
backup, maintenance-mode, or cache state was changed by this implementation.

The focused contract tests cover the approved schema, canonical registry,
sensitive permission separation, single-company role management, and
system-role exclusion. Full disposable migration and integration verification must be run
against the explicitly allow-listed `blackgrd_schema_testing` database after
that database is prepared under the database-safety procedure.

Audit Log, Approval, Workflow, Number Series, MFA/OTP activation and inventory
ledger integration remain deferred to later approved tasks.
