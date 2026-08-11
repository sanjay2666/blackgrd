# Coating Type Master

Coating Type Master defines reusable coating identity; it does not define the production route.
Printing position relative to Coating must never be globally controlled by Coating Type Master.

## Canonical source

The canonical source is the existing `cotings` table and `App\Models\Coting` model. The legacy
model/table names are retained for operational compatibility; no duplicate `coating_types` table
was introduced. The repository baseline contains IDs 1–7: AF / AF Coating, PUWR / PU Coated WR,
PU / PU, PVC / PVC, WR / Water Repellant, WRC / Water Repellant Cire, and NO / No Coating.
IDs and existing values are preserved; live records are not resequenced or rewritten.

Supported master fields are `name`, `code`, `description`, `display_order`, and `status`.
Codes are normalized to uppercase for new and edited values, while existing referenced identity
values cannot be changed. Name and code are logical duplicate keys (case/trim insensitive),
excluding Deleted rows. No unsafe database unique constraint was added to legacy data.

`Active` values are selectable for new configuration through the existing `list_coating` JSON
contract and the new `admin.cotings.options` endpoint. Inactive values remain readable in
historical text snapshots. Deactivation is preferred to deletion; identity deletion is rejected
even when no current text reference is found.

Operational rows continue to store text snapshots in fields such as `coating_type`, `coated_pvc`,
and legacy `coating_pvc`. These snapshots are not converted, bulk rewritten, or used to infer
workflow. The existing Greige/Dyed/Coated warehouse matching remains unchanged: null/`0` coating
for Greige/Dyed and exact coating text matching for coated stock.

Item Type, Process, Fabric Quality, Shade/Colour, Chemical/formula, and Printing Design remain
separate concepts. No chemical IDs, recipes, process parameters, workflow edges, printing position,
printing-before/after fields, or Fabric Quality combinations were added. Coated fabric may retain
Item Type = Coated while its coating type remains an independent dimension.

The master uses existing `masters.view` / `masters.update` RBAC, Admin guard authorization, the
permission-aware `AdminNavigation`, and centralized Audit Log events for create, update, activate,
and deactivate changes with before/after values. Frontend users do not gain master-management access.

Historical coating snapshots must not be rewritten when Coating Type Master changes.
