# Task 2.1 — Super Admin and Company Admin Boundary

## Result

The boundary is explicit for the single-company ERP:

`Super Admin → System/Software Owner`

`Company Admin → Single Customer Company Administrator`

The existing ordinary `Admin` company role is treated as Company Admin. The
reserved `super-admin` role remains a System/Admin role and is not assigned by
normal Admin/User UI. Admin ID 1 has no automatic Super Admin bypass.

## Controls

- Super Admin authority is an active RBAC assignment to the reserved role only.
- Reserved permissions are `companies.*`, `security.*`, `audit-logs.*`,
  `settings.*`, and `organization.access-manage`.
- Company Admin may administer company roles, Frontend Users, individual
  Frontend permissions, operational masters, Financial Years, and Number Series
  only through existing permissions; it cannot grant the reserved set.
- Role services reject reserved role edits/assignments and reserved permission
  grants. Runtime authorization removes reserved permissions from non-Super
  Admin Admin accounts. The same controls protect direct URL, POST, and AJAX
  attempts.
- Frontend User permission overrides use the existing Frontend catalog and the
  separate `web` guard; they cannot enter Admin security administration.

## Audit and database safety

Allowed role and permission changes, plus denied reserved-role and reserved-
permission escalation attempts, use the existing centralized Audit Log.
No migration or schema change was needed. The protected live `blackgrd`
database was not modified and no destructive command was run.
