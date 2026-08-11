# Task 3.21 — Printing Design Master Audit

## Existing architecture

The repository baseline (`eb7dc1c`) contained no `printing_designs`, `print_designs`, or generic design master table/model/controller/route. Existing Printing occurrences are process/transaction compatibility paths: Process Master provides manufacturing process identity, and legacy Sale Order/Work Order/warehouse flows retain text snapshots such as `print_job`. No current customer-specific Printing Design relationship, artwork storage field, or `printing_design_id` reference was found. Existing live records therefore required no mapping or ID preservation work.

## Implemented canonical design

The new canonical table/model is `printing_designs` / `App\Models\PrintingDesign`, company-scoped through `BelongsToCompany`. It stores only design name, optional code/number, description, display order, and lifecycle status. Name and code are normalized safely; new duplicates are blocked case-insensitively by name or code within the company. Existing referenced identity values are protected. There is no customer association because the audited architecture had none, and there is no artwork upload because no existing storage contract required one.

Inactive designs remain historically readable and are omitted from active selection results. Hard deletion is rejected; referenced designs must be deactivated and historical business records are never cascaded or rewritten. Reference detection covers current operational tables when canonical or legacy design ID columns exist. Audit events capture creation, updates, activation/deactivation, and before/after metadata only.

## Boundary and compatibility findings

Printing remains a Process Master identity, not a Printing Design. Fabric Quality, Colour/Shade, Chemical/formula, and Coating Type remain separate. No global `print_before_coating`, `print_after_coating`, `printing_position`, workflow, or process-sequence field was introduced. Printing position relative to Coating must never be globally controlled by Printing Design Master. Future Printing/Coating order must be captured per Fabric/Sale Order Item in an ordered, versioned workflow snapshot. Historical transaction design snapshots must not be rewritten when Printing Design Master changes.

The existing `decide-printing-position` route and downstream Coating/Printing compatibility code remain outside this identity-master task. They are documented technical-debt/legacy workflow inventory, not authority granted to Printing Design Master. No Sale Order, Work Order, inspection, warehouse, Job Work, or reporting flow was broadly refactored.

## Access and verification

Admin CRUD, status transitions, and active-only options are protected by the existing admin guard and canonical `masters.*` RBAC mapping. Navigation is permission-aware under Masters. Frontend users have no admin route access. Focused contract tests cover canonical schema/model, uniqueness and lifecycle/protection boundaries, route/RBAC/navigation registration, and absence of workflow/Coating/Chemical/Colour/Fabric Quality fields. No live migration or data rewrite was performed; the live `blackgrd` database remained protected.
