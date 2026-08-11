# Colour Master

`colours` and `App\Models\Colour` are the single canonical Colour master. Colour Master defines reusable/base colour identity. Specific Dyeing Shades belong to the separate Shade/Dyeing Colour Master.

The existing numeric IDs are historical identifiers and are never resequenced or remapped. Current fields are `id`, `company_id`, `name`, `code`, `created`, `modified`, `deleted_by`, `deleted_date`, and `status`. No dye formula, chemical recipe, artwork, fabric quality, or stock-state fields belong here. `code` is retained as supplied (trimmed only); new names and codes are compared case-insensitively within the company, while legacy duplicates are not merged.

There is no current `parent_id`, `parent_color_id`, or parent/child Colour schema. Existing operational `dyeing_color` values are text snapshots, not Colour foreign keys. Existing historical dyeing/shade snapshots must not be rewritten when Colour Master changes. Task 3.19 owns future Shade/Dyeing Colour normalization.

Active and Inactive are selectable lifecycle states; inactive rows remain readable for history. Colour identity records are retained and deletion is rejected; deactivation is the supported replacement. If a future Colour foreign key is introduced, referenced identity fields must remain protected and parent/child integrity must be enforced server-side.

The existing `/list_master_color` endpoint and JSON response contract are unchanged and already return active canonical Colours only. It is used by warehouse screens; Sale Orders, Work Orders, Dyeing, stock, and reports continue to use their historical `dyeing_color` snapshots. Greige may have no dyeing colour; Dyed and Coated flows retain their current snapshot semantics.

Admin CRUD and status routes use `masters.*`, Admin guard, organization scope, centralized Audit Log, and permission-aware `AdminNavigation`. Frontend users do not manage this master. No schema migration was required for Task 3.18.
