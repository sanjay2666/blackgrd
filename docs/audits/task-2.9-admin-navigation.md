# Task 2.9 audit: Admin navigation cleanup

## Inventory

The former sidebar was a theme-template menu. It contained many dead `.html` destinations for invoices, accounting, payroll, reports, charts, UI examples, profile pages, and other unimplemented features. It also placed configuration and security pages under Masters, duplicated Departments as a top-level link, marked Dashboard active on every page, and rendered all links without RBAC visibility checks.

## Result

The sidebar now renders only current named Admin routes from `AdminNavigation`, grouped into Administration, Organization, Masters, Configuration, and Security & Audit. User Management and Roles are consolidated under Administration. No future master links were added. Each child uses an existing canonical permission, parent groups disappear when no child is visible, and route-name patterns control active/open state.

No route URLs, controller behavior, business functionality, permission assignments, role assignments, overrides, or database schema/data were changed. Backend authorization remains authoritative and routine navigation rendering creates no audit records.

Verification covers named-route integrity, absence of stale template links, RBAC manifest usage, reserved permission classification, full regression, and the final quality gate.
