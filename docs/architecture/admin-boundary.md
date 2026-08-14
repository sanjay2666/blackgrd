# Admin Boundary

This ERP is single-company. The `admin` guard authenticates Admin-side accounts;
the `web` guard authenticates Frontend Users. These identities and guards remain
separate.

Every authenticated Admin has full Admin Panel route access after the existing
organization and audit middleware. Admin routes do not consult `all_pages`,
`user_web_pages`, route-wise RBAC middleware, or Frontend permission keys.

## Roles

- **Super Admin** — system/software owner. This is the reserved `super-admin`
  System/Admin RBAC role. Runtime authority comes only from an active assignment
  to that role. It is never inferred from an ID, email, record order, or
  `user_type`, and assignment is available only through the reviewed CLI
  bootstrap command.
- **Company Admin** — administrator of this one customer company. The existing
  company-scoped `Admin` role is the Company Admin role; no duplicate role is
  introduced. Existing assignments remain valid.

## Permission boundary

`App\Support\PermissionRegistry` is the authoritative permission registry and
classification source. Company Admin roles may use the registry permissions
except for the reserved system/security set: `companies.*`, `security.*`,
`audit-logs.*`, `settings.*`, and `organization.access-manage`. Frontend User
customization is derived from the same registry and remains restricted to the
Frontend catalog.

The reserved set is filtered from ordinary Admin effective permissions at
runtime. Role create/update and role assignment services enforce the same rule,
so direct POST, AJAX, or crafted requests cannot grant or assign reserved
authority. The reserved System role is excluded from Company Admin role routes.

## Audit and safety

Role changes and denied reserved-role/permission escalation attempts use the
existing centralized `AuditLogger`. No schema or live database change is
required for this boundary.
