# Task 3.18 — Colour Master Audit

## Result

The existing `colours` table is the canonical company-level Colour master. No duplicate table, ownership field, or Shade/Dyeing Colour master was introduced. `individual_id` remains removed.

## Boundary and compatibility

Colour Master defines reusable/base colour identity. Specific Dyeing Shades belong to the separate Shade/Dyeing Colour Master. The repository contains no current Colour parent/child columns. Operational tables contain legacy `dyeing_color` text snapshots in Sale Order Items, Work Order Items, work requirements, warehouse/stock, inspections, gate passes, purchases, and dispatch/return records. These values are preserved exactly; no mapping or rewrite was attempted. Lab-test shade/formula concerns remain outside this task. Fabric Quality, Chemical/Dye Recipe, Coating, and Printing Design remain separate concerns.

## Fields and lifecycle

Existing IDs and codes are preserved. New names and non-empty codes are normalized by trimming and checked case-insensitively within the active/non-deleted company rows. Legacy duplicates are documented rather than merged. Active and Inactive are supported; inactive records remain historically resolvable and are excluded from the existing active-only `list_master_color` autocomplete. Hard deletion is rejected for all Colour identities, and referenced identity fields are protected if Colour foreign keys are added by a later task.

## Authorization, audit, and navigation

Admin routes use `masters.view/create/update/delete` through the canonical RBAC registry, with explicit status routes mapped to `masters.update`. Frontend users are denied by the Admin guard. `AdminNavigation` exposes one permission-aware Colours link under Masters. Create, update, activate, and deactivate actions record before/after values through `AuditLogger`; reads and autocomplete are not logged.

## Inventory and hard-coded inventory

No operational module was broadly refactored. Meaningful Colour-related occurrences are: `CommonController::list_master_color` and warehouse autocomplete consumers (canonical active Colour lookup); Sale Orders, Work Orders, Dyeing/work requirements, Warehouse/stock, inspections, gate passes, purchases, dispatch/returns, exports, and reports (legacy `dyeing_color` snapshots). There are no current `colour_id`, `color_id`, `parent_id`, `parent_color_id`, `shade_name`, or `shade_code` references in the canonical Colour architecture.

## Database safety

No migration or live data change was required. Existing IDs, references, parent/child compatibility, snapshots, maintenance mode, and DatabaseSafetyGuard were left unchanged. No live backup or SHA-256 migration artifact applies.
