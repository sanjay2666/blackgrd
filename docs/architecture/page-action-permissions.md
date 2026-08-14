# Frontend page and action permissions

> Current runtime rule: authenticated Frontend routes use only `all_pages` plus
> active `user_web_pages` assignments. The historical RBAC text below is not a
> runtime Frontend authorization path.

Blackgrd uses one action-based permission registry: `App\Support\PermissionRegistry`. Keys such as `sale-orders.view`, `sale-orders.cancel`, and `warehouse.adjust` are stable identifiers; URLs and Blade components are never permission keys.

## Administration model

The registry groups permissions by resource/module and action. `companyAdminAssignable()` excludes reserved system/security permissions. `frontendAssignable()` is derived from the same registry and excludes Admin-only resources. `assignableForPanel()` is the server-side classification used when saving a role. The database `permissions` table is a materialized copy of these canonical definitions and is not a second source of truth.

Company roles have a panel (`Admin` or `Frontend`). Role management displays modules and actions with search and select/clear controls. Super Admin system roles are outside the company-role routes and cannot be edited or assigned by ordinary administrators.

## User overrides and effective access

The Admin permission page syncs missing authenticated Frontend routes into
`all_pages`, preserving existing page metadata and excluding Admin routes. Its
existing checkboxes submit `all_pages.id` values; selected entries are Active in
`user_web_pages`, while unselected entries are Inactive. Each method/route entry
is independently controllable, including nested routes and AJAX endpoints.

## Enforcement and UI

`EnforceFrontendPagePermission` is authoritative for Frontend pages, mutations,
AJAX, print, export, and download routes. It resolves the exact method plus
declared route URI in `all_pages`, then requires an active matching
`user_web_pages` row for the authenticated Frontend User. Missing, inactive, or
unmatched entries return 403. `RoutePermissionRegistry` is retained only to
label audit records and is not a runtime authorization path.

Role changes, role assignment/revocation, and user override changes call `AuthorizationService::forget()`, so updates apply on the next request. Centralized `AuditLogger` records permission additions/removals, override changes (including Inherit), role changes, and rejected reserved-permission attempts; permission checks are not audited.

## Adding a permission safely

Create action → define canonical permission → classify it → map route/action → use backend authorization → optionally hide UI → add tests → run `php artisan quality:check`.

Do not add URL-shaped keys, permission-per-component keys, duplicate registries, or broad “grant everything” behavior. Keep actions separate when a real ERP operation has a distinct authority (for example view versus complete or adjust).
