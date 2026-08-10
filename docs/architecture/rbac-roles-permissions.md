# Single-Company RBAC, Roles and Permissions

Status: implemented core/live schema, registry, approved account bootstrap and exact fail-closed route/action enforcement for Task 1.7B (2026-08-10)

This is the source of truth for Prompt 12. The ERP is one textile manufacturing
business, not a SaaS or multi-company ERP. Existing company, branch, factory
and department fields remain ownership/configuration metadata and organization
context; RBAC does not create tenant role copies or cross-company operations.

Authentication remains intentionally split: `admin` guard + Admin-panel Admin
accounts serve the Admin Panel, while `web` guard + User accounts serve the
frontend ERP. RBAC reuses one permission catalog but assignments include the
principal type and role panel, so the two login systems cannot collide.

## Current access-control state

The application has one User identity model, web and admin session guards using the same Eloquent provider, User/Admin account types, canonical account status, user_organization_access, CurrentOrganizationContext, and ResolveOrganizationContext.

The RBAC schema, canonical registry, centralized authorization service,
permission middleware, role UI and user-role assignment model are implemented.
`user_web_pages` remains a legacy page/customization table, not authorization.

## Boundary

The request pipeline is:

```text
Authenticate identity
  -> Resolve trusted company/branch/factory context
  -> Verify organization access
  -> Check RBAC permission
  -> Check record/company policy
  -> Execute action
```

Authentication answers who. Organization context answers which branch/factory/
department data is in scope. RBAC answers what capability. Every route, query,
model binding, AJAX endpoint, print/download, export, job and service must retain
the existing organization boundary.

## Role model

Use multiple roles per user:

```text
Admin/User principal -> panel-tagged role assignment -> Role -> role_permissions -> Permission
```

Permissions are additive across active roles. Do not implement direct user-permission overrides initially; exceptions require a later audited, expiring design.

The reserved System role is Admin-panel-only and outside ordinary role management, for example
Super Admin. Ordinary roles such as Admin, Sales and Warehouse Operator belong
to this ERP and are not duplicated per company. Inactive/deleted roles and
assignments are ignored.

## Super Admin and Admin

Super Admin is an active assignment to reserved system role super-admin. It is never inferred from user ID, email, user_type, request input, session values, or scattered role-name checks. A centralized AuthorizationContext/RBAC service owns this decision.

Super Admin still needs the resolved organization context for company data
operations. There is no cross-company operation in this ERP. System roles
cannot be edited, deleted, or assigned by the ordinary Admin role.

Admin may manage users, roles, assignments, masters and configuration only
where its permissions allow. It cannot create or assign Super Admin or grant
permissions it does not possess.

## Permission convention

Use stable lowercase resource.action keys describing business capabilities, not URLs, methods or IDs:

```text
sale-orders.view
sale-orders.create
sale-orders.update
sale-orders.cancel
sale-orders.print
warehouse.view-stock
warehouse.receive
warehouse.adjust
financial-years.set-current
roles.assign
users.manage
```

update never implies cancel, approve, reject, complete, adjust, or set-current. Use view when list/detail share a boundary; use view-detail only for materially different sensitivity. manage is limited configuration, not everything. Future namespaces approvals.*, workflow.*, number-series.*, and audit-log.* are reserved but not seeded.

## Prompt 12 schema

Current parent types: users.id is unsigned bigint and companies.id is unsigned integer. No migration is part of Task 1.7A.

roles:
- id unsigned bigint primary key
- company_id unsigned integer nullable ownership metadata; FK companies restrict
- role_key varchar(120) unique immutable; name varchar(120)
- scope System or Company; description nullable
- canonical status; created_by/updated_by unsigned bigint nullable FKs to users
- created_at/updated_at timestamps
- indexes company_id/status, company_id/name, scope/status

Services enforce System/null-company and Company/non-null-company invariants plus company-local name uniqueness.

permissions:
- id unsigned bigint primary key
- permission_key varchar(120) unique immutable
- resource varchar(60) indexed; action varchar(60); category varchar(40) indexed
- description; is_critical boolean; canonical status; timestamps

role_permissions:
- role_id unsigned bigint FK roles
- permission_id unsigned bigint FK permissions
- assigned_by unsigned bigint nullable FK users; timestamps
- composite primary key role_id/permission_id and reverse permission_id/role_id index
- physical deletion restricted after assignment; status transitions preferred

user_role_assignments:
- id unsigned bigint primary key
- user_id and role_id unsigned bigint FKs
- company_id unsigned integer nullable ownership metadata; null for System roles
- future nullable branch_id, factory_id, department_id with restrict FKs
- starts_at, ends_at, canonical status, assigned_by, revoked_by, revoked_at, timestamps
- indexes user/status, company/status, role/status and unit scope

Services enforce role/company equality, parent ownership, active organization access, effective dates and duplicate prevention. Do not add role_id to users.

## Registry and enforcement

Use a version-controlled PHP permission registry synchronized idempotently by a Prompt 12 command/seeder. It defines key, resource, action, category, description and critical flag. Sync may insert/update metadata but must not delete assigned permissions; deprecated entries become Inactive after review. Templates use keys, never numeric IDs.

Use auth guard, organization middleware, reusable permission middleware, then a centralized Gate/authorization service. Policies handle record-level company, parent, factory, warehouse and lifecycle checks. Domain actions re-check high-risk transitions. Route middleware is coarse; policies/services are mandatory for AJAX, print/download, exports, bindings and direct actions.

Blade uses reusable can/canany backed by the same Gate. Menus show a section when the user has any relevant capability. Buttons use exact action permissions. Blade must not query permission tables directly.

## Branch/factory and approval interaction

Permissions remain company-level and never include factory IDs. Organization access and optional unit columns on role assignments provide data scope; effective scope is their intersection. No factory-specific permission keys. Current branches/factories have zero live rows, so none are invented.

Approval remains future domain behavior. Keep request and decision capabilities separate, for example sale-orders.submit, sale-orders.approve, sale-orders.reject, purchase-orders.approve, work-orders.complete, and inventory-adjustments.approve. The future Approval Engine decides workflow, separation of duties and history.

## Current permission matrix

This covers 18 current capability groups; a dash means no distinct verified action.

| Module | View | Create | Update | Delete/Cancel | Approve | Operational actions | Print | Export | Manage |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| Dashboard | yes | — | — | — | — | — | — | — | — |
| Organization context | yes | — | — | — | — | switch | — | — | access |
| Company | yes | yes | yes | delete | — | — | — | — | configure |
| Financial year | yes | yes | yes | delete | — | set-current | — | — | configure |
| Department/Process/Machine | yes | yes | yes | delete | — | operate | — | — | configure |
| Employee/party | yes | yes | yes | delete | — | — | — | export | manage |
| Item/reference masters | yes | yes | yes | delete | — | yarn/manage | — | export | configure |
| Sale order/item | yes | yes | yes | cancel | future | submit/route | print | export | — |
| Purchase/order/receipt | yes | yes | yes | cancel | future | receive/return | print | export | — |
| Work order/item | yes | yes | yes | cancel | future | start/complete/assign | print | export | — |
| WPR | yes | yes | yes | cancel | — | accept/allot/return | print | export | — |
| Inspection/quality | yes | yes | yes | — | — | inspect/override | print | export | configure |
| Warehouse/compartment | yes | yes | yes | delete | — | receive/issue/allot/return/adjust | print | export | configure |
| Gate pass | yes | yes | yes | cancel | — | issue/receive/close | print | export | — |
| Job work/mill dispatch | yes | yes | yes | cancel | — | dispatch/receive/close | print | export | — |
| Reports | yes | — | — | — | — | — | print | export | configure |
| Settings/notifications/pages | yes | yes | yes | delete | — | — | — | — | configure |
| Security/user activity | yes | — | — | delete | — | — | — | export | manage |

Critical keys: roles.assign, organization.access-manage, financial-years.set-current, cancel/approve/reject actions, warehouse adjust/reversal, work-order complete, inspection override, gate-pass issue/cancel, and sensitive report export.

## Route/action mapping and templates

Capability families map to route groups, not individual permission IDs:

- Company: admin.companies.* -> Admin CompanyController
- Financial year: admin.financial-years.* and set-current
- Department/process/machine: corresponding admin resource routes
- People/masters: individuals, items, item types, colours, cotings, units, GST, couriers, packaging, states
- Warehouse: warehouse/compartment admin CRUD and frontend receive/issue/allot/return
- Sale orders: sale-orders.*, sale-order.*, AJAX/print/delete
- Purchases: purchase list/create/update/delete/print routes
- Production: work-order list/store/update/inspection/receive
- WPR/job work: accept/allot/return/print routes
- Security/settings: user pages, activity logs, login history, notifications, office IPs
- Organization switch: POST organization/switch

Templates: Admin; Frontend Administrator; Sales; Purchase; Production Manager; Production Operator; Warehouse Manager; Warehouse Operator; Quality/Inspection; Accounts/Management Viewer. Templates are starting points for this ERP, never mandatory grants.

Admin-panel templates are Super Admin (reserved) and Admin. Frontend templates
are Sales, Purchase, Production Manager, Production Operator, Warehouse
Manager, Warehouse Operator, Quality/Inspection, and Accounts/Management
Viewer. The permission catalog is shared; roles are panel-tagged rather than
duplicated per company.

## Existing-user bootstrap

Live evidence is one active frontend user, one active admin, one active company, two active default access mappings, zero branches and factories. No role intent is stored.

The approved bootstrap assigns Admin #1 (`admin@blackgrd.test`) to ordinary Admin and User #2 (`unsanjay4@gmail.com`) to Frontend Administrator. Frontend Administrator aggregates current frontend/business permissions while excluding Admin-only account, security, settings and RBAC controls. Super Admin remains unassigned. Future users require explicit assignments; there is no identity bypass. A future active Admin can be assigned the reserved role only through `php artisan rbac:assign-super-admin <exact-email>` after exact lookup and confirmation.

## Security, caching and rollout

Deny by default; backend authorization is mandatory; client role/permission and organization IDs are validated; Admin cannot grant outside its effective set or assign system roles; inactive/deleted roles and assignments are ignored; organization context precedes RBAC; no hard-coded identity/session bypass; critical actions have distinct keys and domain checks. Permission resolution is user/assignment/role based; organization context only limits branch/factory/department data access.

Resolve effective permissions once per request. A short-lived cache may key by user, company, unit scope and role-assignment version. Do not persist grants in session. Role/permission/assignment writes invalidate affected versions. Revocation applies next request; cache failure fails closed for critical actions.

Prompt 12 sequence:
1. Freeze design and verify counts.
2. Create four reviewed tables in disposable DB.
3. Add registry and idempotent sync.
4. Create reserved system permissions/role and optional templates.
5. Implement models, relationships, statuses and assignment invariants.
6. Implement AuthorizationContext, Gate and permission middleware.
7. Add record policies and critical domain checks.
8. Bootstrap users only from explicit decision report.
9. Protect admin/frontend capability families.
10. Protect AJAX, print, download, export and direct actions.
11. Add Gate-backed Blade helpers.
12. Add role UI with escalation safeguards.
13. Add scope/critical/revocation tests.
14. Run disposable rollback/re-migration and regression tests.
15. Back up and apply only an approved hash-pinned migration.

Deferred: Audit Log, Number Series, Workflow, Approval, MFA/OTP activation,
inventory ledger, branch/factory creation and employee membership redesign.

## Current route/action mapping report

`config/rbac_routes.php` is the central source, resolved by
`App\Support\RoutePermissionRegistry`. The current inventory contains 293
authenticated routes: 291 RBAC-protected routes and two explicit logout
exclusions. `RoutePermissionMappingTest` detects new authenticated routes that
lack a permission decision.

| Guard | Route/action | Permission | Notes |
| --- | --- | --- | --- |
| Admin | `admin.{resource}.index/show` | `{resource}.view` | List/detail |
| Admin | `admin.{resource}.store/create` | `{resource}.create` | Create |
| Admin | `admin.{resource}.update/edit` | `{resource}.update` | Update |
| Admin | `admin.{resource}.destroy` | `{resource}.delete` | Destructive |
| Admin | `admin.financial-years.set-current` | `financial-years.set-current` | Critical FY action |
| Admin | `admin.roles.*` | `roles.view/create/update/assign` | Super Admin excluded |
| Frontend | Sale/Purchase/Work Order | Module-specific action | Cancel/start/complete/print remain distinct |
| Frontend | Warehouse/WPR/Inspection/Job Work | Operation-specific action | Receive/issue/allot/return/adjust/inspect distinct |
| Both | Shared lookup/AJAX | Read/module permission | Data exposure is checked |

Print, export, download and AJAX endpoints use the same server-side registry.
Unknown authenticated routes fail closed; logout is the only explicit
authenticated allowlist.

## Frontend User-specific permission customization

Admins manage an individual Frontend User at `admin/users/{user}/permissions`.
The page is grouped by canonical permission resource/action and shows assigned
roles, role-inherited permissions, active user-specific Allow/Deny changes and
the final effective state. Blade only renders controller-provided data; all
authorization remains server-side.

The additive `user_permission_overrides` table stores one active `Allow` or
`Deny` per User/permission, with effective dates and audit actor. Effective
permissions are role grants plus active user Allows minus active user Denies.
Only active `User` principals are accepted, only the FrontendPermissionCatalog
is assignable, ordinary Admins cannot grant permissions outside their own
effective set, and Admin-only security/RBAC permissions never appear in the
Frontend User UI. Writes invalidate the request authorization cache and future
requests resolve the change immediately. Frontend Users cannot reach the
Admin-only routes or modify their own overrides.
