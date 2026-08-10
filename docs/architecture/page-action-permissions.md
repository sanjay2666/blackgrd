# Page and action permissions

Blackgrd uses one action-based permission registry: `App\Support\PermissionRegistry`. Keys such as `sale-orders.view`, `sale-orders.cancel`, and `warehouse.adjust` are stable identifiers; URLs and Blade components are never permission keys.

## Administration model

The registry groups permissions by resource/module and action. `companyAdminAssignable()` excludes reserved system/security permissions. `frontendAssignable()` is derived from the same registry and excludes Admin-only resources. `assignableForPanel()` is the server-side classification used when saving a role. The database `permissions` table is a materialized copy of these canonical definitions and is not a second source of truth.

Company roles have a panel (`Admin` or `Frontend`). Role management displays modules and actions with search and select/clear controls. Super Admin system roles are outside the company-role routes and cannot be edited or assigned by ordinary administrators.

## User overrides and effective access

Frontend Users retain explicit per-permission `Inherit`, `Allow`, or `Deny` state. The effective result is:

`(role permissions + explicit Allow) - explicit Deny`

Therefore an explicit Deny wins over role access, while Allow can add access not supplied by a role. Role changes do not delete overrides. The user screen shows the role result, override, and final effective result.

## Enforcement and UI

`RoutePermissionRegistry` maps named routes and legacy URI actions to canonical permissions. `EnforceMappedPermission` is authoritative for pages, mutations, AJAX, print, export, and download routes; hidden buttons are only presentation. Blade may use a resolved authorization result, but must not query permissions directly or reproduce the effective-permission calculation.

Role changes, role assignment/revocation, and user override changes call `AuthorizationService::forget()`, so updates apply on the next request. Centralized `AuditLogger` records permission additions/removals, override changes (including Inherit), role changes, and rejected reserved-permission attempts; permission checks are not audited.

## Adding a permission safely

Create action → define canonical permission → classify it → map route/action → use backend authorization → optionally hide UI → add tests → run `php artisan quality:check`.

Do not add URL-shaped keys, permission-per-component keys, duplicate registries, or broad “grant everything” behavior. Keep actions separate when a real ERP operation has a distinct authority (for example view versus complete or adjust).
