# Task 3.5 Machine Master Audit

The audit started from baseline `26b398b` and passed the required baseline quality gate. The canonical Machine table/model is `machines` / `App\Models\Machine`; no duplicate master was created.

Live read-only inspection found ten records, IDs 1–10, company 1, with process IDs 1–4. IDs 7, 8, and 9 are `JET-DYEING -01`, `-02`, and `-03`; these are retained for Dyeing Planning compatibility. No live Factory or Department assignment existed on these rows. No machine code, machine type, capacity, or shift fields exist. The legacy `financial_year` column was removed by the prior reviewed migration and was not rewritten.

The implementation reuses `CurrentOrganizationContext`, validates active canonical Process/Department/Factory relationships and company scope server-side, prevents scoped logical duplicates, preserves IDs, and keeps the existing `process_wise` contract. It adds list search, Process and status filters, compact location-aware CRUD, and explicit Activate/Deactivate actions. The existing AdminNavigation link remains under `Masters` with `masters.view`; no Machine Capacity link was added.

References are audited across Work Orders, Dyeing requirements, inspections, and machine-bearing warehouse records. Hard deletion is rejected. Deactivation is blocked for active Work Orders or planned Dyeing requirements. Referenced Process, Department, and Factory changes are rejected to protect historical meaning. Inactive records remain resolvable historically but are not active master choices. The master does not change operational selection endpoints, Dyeing Planning, production scheduling, capacity, utilization, or shifts.

Machine Master defines physical machine identity; Machine Capacity and production scheduling are separate concerns.

## Verification

- Baseline `php artisan quality:check`: PASS.
- PHP lint, route listing, Admin navigation tests, and `git diff --check`: PASS during implementation.
- Final full tests and `php artisan quality:check` are required before commit.
- No live migration, backup, SHA-256, or maintenance window applies because the schema was reused.
