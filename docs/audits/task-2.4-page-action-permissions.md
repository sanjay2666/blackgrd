# Task 2.4 audit: Page and Action Permissions

## Scope and result

The existing RBAC foundation was retained: canonical registry, roles, role permissions, `principal_type + principal_id`, separate Admin/User guards, user overrides, effective access calculation, route mapping, default deny, audit logging, and the quality gate. No permission tables or permission engine were added and no database migration was required.

The management UI now identifies role panel and status, filters roles and actions, groups permissions by module, offers select/clear visible actions, and explains User role access, override, and effective access. The server validates panel-specific assignments, canonical reserved classification, current-company scope, and role panel when assigning principals.

## Security review

Company Admins cannot grant registry-reserved permissions, edit system roles, cross company boundaries, or give Frontend roles Admin-only permissions, including through forged requests. Frontend users do not have Admin permission-management routes. Admin and User principals remain distinguished by `principal_type`, even where numeric IDs overlap. Explicit Deny remains stronger than role Allow; role changes preserve overrides. Cache state is forgotten after role, assignment, and override changes.

## Enforcement, audit, and verification

Named route and legacy URI mappings remain canonical and are enforced by backend middleware for GET, POST, PUT/PATCH, DELETE, AJAX, print, export, and download actions. Permission administration changes use the centralized audit logger with before/after values; checks are not logged.

The permission registry remains 129 keys. The task adds no schema/data reset and makes no live database change. Baseline `php artisan quality:check` passed before implementation; final focused tests, regression tests, Pint, diff check, and the full quality gate are required before commit.
