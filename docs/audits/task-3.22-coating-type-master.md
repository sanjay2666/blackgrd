# Task 3.22 — Coating Type Master Audit

## Result

The existing `cotings` / `Coting` implementation is the one canonical Coating Type master. It was
hardened in place; no duplicate table, ID resequencing, live data rewrite, or stock-engine refactor
was performed.

The baseline values are IDs 1–7: AF, PUWR, PU, PVC, WR, WRC, and NO, with the names documented in
the architecture record. Existing `coating_type` and `coated_pvc` values remain transaction/history
snapshots. Warehouse, Sale Order, Work Order, Inspection, Job Work, reports, and production code
continue using their established text contracts.

The master now supports name, code, description, display order, and Active/Inactive/Deleted status.
New logical duplicates are rejected by normalized name or code. Existing referenced names/codes are
identity-protected; status and non-identity metadata remain maintainable. Hard deletion is rejected,
so historical inactive records remain readable.

The compact Bootstrap 3.3.7 UI provides search, status filtering, pagination, edit, and activate /
deactivate actions. Existing `list_coating` response behavior remains active-only; the admin options
endpoint also returns only active values for new selections. RBAC remains `masters.*`, AdminNavigation
is permission-aware, and authorization remains on the admin guard rather than frontend users.

Audit Log records meaningful master mutations with before/after values. No printing position, coating
workflow, process sequencing, chemical formula, GSM, temperature, viscosity, machine speed, curing
time, Item Type, Fabric Quality, Shade, Colour, or Printing Design relationship was introduced.

Coating Type Master defines reusable coating identity; it does not define the production route.
Printing position relative to Coating must never be globally controlled by Coating Type Master.
Historical coating snapshots must not be rewritten when Coating Type Master changes.

Schema change: one reviewed additive migration adds only nullable `description`, nullable
`display_order`, and a status/order index to the existing table. The live `blackgrd` database was
not migrated or destructively touched; DatabaseSafetyGuard remains enabled.
