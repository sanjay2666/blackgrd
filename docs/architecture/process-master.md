# Process Master

## Canonical identity

`process_items` is the established canonical process table and `App\Models\ProcessItem` is its model. It is reused because Work Orders, Work Order Items, WPR, inspections, machines, individuals, warehouse records, reports, and legacy helpers already store the process identity in `process_type_id`.

The additive Process Master fields are `short_code`, `description`, `department_id`, `display_order`, and `company_id`. Existing `entry_name`, `process_name`, `output_name`, `process_sl_no_last`, and status remain compatible. Codes are stable, company-scoped, and unique with process names among non-deleted records.

Live compatibility identities are preserved: 1 Warping (`WRP`), 2 Weaving (`WEV`), 3 Dyeing (`DYE`), 4 Coating (`COA`), 5 Packaging (`PKG`), 6 D-Printing (`DPR`), 7 C-Printing (`CPR`), and 8 Lab (`LAB`). The codes were only populated where the existing column did not exist; process names and IDs were not resequenced.

## Boundaries and lifecycle

Department is an organizational unit. Process is a reusable manufacturing operation. A process may optionally reference an active Department in the same canonical company, but process identity never depends on matching IDs or names. Process Items remain the existing process/operation configuration hierarchy; detailed dyeing planning is not moved into this master.

`display_order` is only a master display/default order. It is not an authoritative Sale Order Item route. Inactive processes remain readable for historical references and are excluded from new active selections as each operational screen adopts the status contract.

## Process Configuration (Task 2.6)

`process_item_configurations` is an optional, company-scoped extension of the canonical `process_items` identity. It stores only the future execution capability: `Internal`, `External`, or `Both`. Existing processes have no required configuration row, so legacy production behavior remains unchanged until an administrator saves configuration.

`process_item_material_configurations` links a Process to one or more canonical active `item_type` records as `Input` or `Output`. The legacy `entry_name` and `output_name` columns remain readable compatibility labels; they are not rewritten or backfilled. `process_item_allowed_next` records possible company-scoped next Process relationships. It rejects duplicates, self-relations, inactive references, and cross-company references.

Allowed-next relationships are a configuration/validation possibility only. `workflow_definitions` → `workflow_versions` → `workflow_version_steps` remain the sole ordered Sale Order Item route snapshot. Configuration does not execute transitions, create Work Orders, or select printing before/after Coating. Both Printing positions remain expressible only through ordered Workflow Version Steps.

Processes are never hard-deleted. Referenced records and core identities are protected, and the admin action is deactivate/activate. Core IDs 1–4 cannot be renamed; referenced process codes cannot be changed. This preserves historical transactions and compatibility with current fixed-ID logic.

**Process Master does not define the final Sale Order Item workflow.** No workflow engine or order-specific route sequencing is implemented here. **Printing position must not be globally defined relative to Coating.** No `print_position` or before/after-Coating rule exists; future routing may place Printing before or after Coating per Sale Order Item.

## Administration

Admin CRUD, filters, and status transitions use `processes.view/create/update/delete/activate/deactivate`. The resource is admin-only in the permission catalog, so Frontend User Department Access is not used to manage this master. All routes are server-side permission mapped and meaningful mutations are recorded by the centralized Audit Log.

The Process List has a compact Configure page. It reuses `processes.view` to read and `processes.update` to save configuration; no new permission or navigation hierarchy is needed.
