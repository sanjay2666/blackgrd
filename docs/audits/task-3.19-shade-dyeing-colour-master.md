# Task 3.19 — Shade / Dyeing Colour Master Audit

## Findings and decision

The repository audit found no `/dyeing-colors` master routes, no Shade model/table, no `parent_id`/`parent_colour_id` hierarchy, and no direct Shade foreign keys. Existing `colours` is the Task 3.18 Base Colour master. Existing `dyeing_color` usage is operational text snapshot data, not a safe source for deterministic Shade IDs. Therefore a single `dyeing_colours` table was established for new canonical Shades; existing child Colour rows were not deleted, recreated, duplicated, or remapped (none were present in the current schema).

## Contract

- Table/model: `dyeing_colours` / `App\Models\DyeingColour`.
- Relationship: `dyeing_colours.colour_id` → active company-scoped `colours.id`; `Colour::dyeingColours()` is the inverse.
- Fields: id, company_id, colour_id, name, code, description, display_order, status, created, modified.
- Identity: trimmed, case-insensitive name and optional code uniqueness within Base Colour for non-deleted records.
- New/changed Base Colour must be active; no same-table hierarchy exists, so self/circular checks do not apply.
- Active/Inactive is supported; inactive records remain readable and are excluded from new canonical selection.
- Deletion is rejected; history is retained by deactivation.
- If direct Shade references are added later, referenced name/code/Base Colour mutations are rejected.

## Compatibility inventory

Sale Order, Work Order, WPR, Warehouse Balance/Stock/In/Out, inspection, job-work dispatch/receive/return, gate passes, exports and reports continue to read/write legacy `dyeing_color` snapshots. Common warehouse helpers and Greige/Dyed/Coated matching were not refactored. Lab Test/Lab Request shade and formula fields remain snapshots and were not rewritten. Item Master, Fabric Quality, Chemical/formula, Coating, and Printing remain separate boundaries.

Existing autocomplete `/list_master_color` and `/find_saleDyeingColor` contracts are preserved. `/list_master_dyeing_colour` is the new active canonical Shade endpoint. Admin routes are protected by Admin authentication, organization scope, and `masters.*` RBAC; Frontend users are denied. AdminNavigation has one permission-aware Shade entry. AuditLogger records meaningful create/update/status/deletion-attempt events and no reads.

## Schema and safety

The migration is reviewed source-only and uses company and Base Colour restrict-on-delete foreign keys. No live `blackgrd` migration, destructive command, data mapping, backup, SHA-256 operation, or maintenance-mode change was performed. No legacy snapshot values were changed. No chemical, dye formula, coating, printing, or stock redesign was introduced.
