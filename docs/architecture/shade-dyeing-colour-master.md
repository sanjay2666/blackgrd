# Shade / Dyeing Colour Master

`colours` / `App\Models\Colour` remains the canonical reusable Base Colour identity. `dyeing_colours` / `App\Models\DyeingColour` is the one canonical master for specific production or dyeing shades. **Colour Master defines the base colour identity; Shade/Dyeing Colour Master defines the specific dyeing shade.**

## Canonical structure

Each Shade belongs to one company and one active `colours.id` through `dyeing_colours.colour_id`. The table stores `name`, optional stable `code`, optional description, display order, and the standard Active/Inactive/Deleted lifecycle. There is no parent/child Colour hierarchy in the current repository and no existing child Colour records to migrate or recreate. No guessed live mappings were made.

Shade uniqueness is scoped to Base Colour: a non-deleted Shade name is case-insensitively unique after trimming within the Base Colour; a non-empty code is also unique there. Existing legacy text values were not converted into IDs. A referenced Shade cannot be hard-deleted, and identity fields (name, code, Base Colour) are protected if a future/direct operational FK references the record. Deactivation preserves historical readability.

Only active Base Colours may be assigned to a new or changed Shade. The current architecture has no same-table parent hierarchy, so circular/self-parent validation is not applicable. Shade Master is not an Item Type and does not own Fabric Quality, Coating Type, Printing Design, chemicals, formula, process parameters, or production consumption.

## Compatibility boundaries

Operational `dyeing_color` fields remain text snapshots in Sale Order Items, Work Order Items, WPR, Warehouse/Balance/Stock, inspections, job work, gate passes, exports, reports, and related production paths. **Historical dyeing/shade snapshots must not be rewritten when Shade Master changes.** Greige/dyed/coated stock matching continues to use its existing null/`0`, text shade, and coating semantics. Item Master and Fabric Quality remain separate.

Dyeing Lab Test and Lab Request shade/formula values remain transaction/lab snapshots. **Shade Master does not own the Dyeing Lab chemical formula or production consumption.** No Chemical Master or recipe engine was introduced. No Coating or Printing relationship was introduced.

The existing `/list_master_color` and `/find_saleDyeingColor` contracts remain unchanged. New canonical selection is available through authenticated `/list_master_dyeing_colour`, returning active Shades with their Base Colour; inactive records remain resolvable through the admin master and direct historical text remains untouched.

## Security and operations

Admin CRUD and status routes use the existing `masters.view/create/update/delete` RBAC mapping and Admin guard. Frontend users cannot access the master. `AdminNavigation` exposes one permission-aware entry beside Colours. Creation, identity/metadata updates, status transitions, and attempted deletion use centralized `AuditLogger`; reads and autocomplete are not logged. Company scoping is enforced by `BelongsToCompany` and explicit Base Colour validation.

Task 3.19 adds only the reviewed `dyeing_colours` schema and application files. No live migration, data backfill, snapshot rewrite, backup, or maintenance-mode operation is performed by the application change.
