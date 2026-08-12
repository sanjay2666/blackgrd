# Task 2.6 — Process Configuration UI audit

## Existing architecture retained

`process_items` / `ProcessItem` is the existing canonical process identity used by Work Orders, WPR, inspections, machines, warehouse records, and historical helpers. `processes` is not a separate project table. `item_type` / `ItemType` is the existing company-scoped canonical material category, including protected identities such as Yarn, Beam, Greige, Dyed, Coated, and Fabric.

Task 5.1 remains unchanged: Workflow Definitions own reusable route identities, Workflow Versions own immutable ordered revisions, Workflow Version Steps own ordered canonical Process references, and Sale Order Items store a nullable selected published Version reference.

## Implemented configuration foundation

- `process_item_configurations`: optional one-to-one Process execution capability (`Internal`, `External`, or `Both`).
- `process_item_material_configurations`: multiple canonical Item Type input/output references per Process.
- `process_item_allowed_next`: possible next Process references per Process.
- All three tables are company-scoped, foreign-keyed, indexed, additive, and empty when introduced.
- The service validates active current-company Item Types and Processes, rejects cross-company references, duplicate selections, self next-process links, and invalid modes. Configuration changes are transactionally replaced and audit logged.
- The existing Process Master input/output labels remain intact as legacy compatibility labels. No process identity, display order, Work Order sequencing, or historic data is changed.

## UI and permissions

The existing Admin Process List now exposes a compact Configure page with basic metadata, execution mode, Item Type input/output checkboxes, and allowed next Process checkboxes. It uses Bootstrap 3.3.7 and the existing Admin shell. `processes.view` provides read access and `processes.update` provides save access; no new permission or navigation item is required.

## Explicit boundaries

Allowed-next configuration is not a second workflow engine. It stores only potential transitions. It does not execute transitions, generate Work Orders, implement optional/skip/repeat logic, or implement Internal/External Job Work runtime behavior. No global Printing position was added to Coating Master. Workflow Version Steps remain the only place that determines whether Printing is before or after Coating for a Sale Order Item.
