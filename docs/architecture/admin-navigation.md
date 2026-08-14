# Admin navigation

The Admin sidebar is a static manifest in `App\Support\AdminNavigation`. It contains only current named Admin routes and groups them into Administration, Organization, Masters, Configuration, and Security & Audit, alongside Dashboard. The manifest is intentionally not database-driven and does not add links for future or unimplemented masters.

Each item declares a route name, canonical RBAC permission, label, and route-name active pattern. `AdminNavigation::visible()` asks the existing `AuthorizationService` for each permission and removes inaccessible items. Empty groups are removed before rendering. The Blade include only renders the resolved result; it performs no database queries and does not duplicate permission calculations.

The current route pattern marks the child active and expands only its parent group. Dashboard uses an exact route match. All links use named routes, so route integrity is testable and URL changes do not require navigation URL edits.

Menu visibility is presentation only. Every authenticated Admin has full backend route access after the existing organization and audit middleware; Admin routes do not use `EnforceMappedPermission` or page-level permission middleware. No user ID, email, or first-record shortcut is used.

To add a future item: Create working route/page → register RBAC permission/mapping → verify backend authorization → add navigation link using existing authorization helper → run `php artisan quality:check`.
