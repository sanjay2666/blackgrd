# Task 5.5 — Split and Merge Genealogy

## Reference review

The old ERP was reviewed read-only before this change. Its proven operational split is inspection output: a dyeing or coating Work Inspection stores one process/lot context and creates one or more `work_inspection_details` from the submitted output and break sizes. Each detail carries its own `insp_taka_number`, quantity, dyeing lot, and dyeing Taka. Warehouse receipt then creates one `warehouse_item_stocks` row per inspection-detail output and carries the existing Taka and Lot fields into that roll/packet row.

Warping stores a Beam as `work_inspections.insp_taka_number`; weaving selects that same identity. Job Work dispatch and partial/multiple receipts copy `warehouse_item_stocks.packet_number`, `insp_taka_number`, and lot/Taka context for a physical movement. They do not combine several source identities into a new downstream identity.

No separate old-ERP runtime merge operation was found. Parent/child Work Orders describe process progression and grouped order lines, not a material-identity merge.

## Existing Laravel behavior retained

Task 5.4 identities remain unchanged:

- Lot: `work_process_requirements.req_lot_no`
- Beam: warping `work_inspections.insp_taka_number`, selected by weaving
- Taka: existing inspection/gate-pass/warehouse/job-work identity fields
- Roll: `warehouse_item_stocks.packet_number`, including stable `ROL-{warehouse_item_stocks.id}` where Task 5.4 assigns a new roll

Inspection details and warehouse receipt already preserve individual quantities and do not require a new numbering system. The work inspection, warehouse, Job Work, warping, weaving, and Work Order operational flows were not redesigned.

## Minimal genealogy foundation

`production_genealogy_links` is an additive, company-scoped link table. Existing transactional rows retain identities but cannot provide an immutable, queryable source-row to result-row association once text identities repeat or a physical roll moves through subsequent stock rows. The table therefore records the existing source and result row IDs and their stable displayed identities, event/relationship type, quantity, and the real Work Order/requirement/inspection context.

The runtime records only proven relationships:

- `lot_to_taka` / `inspection_output`: the selected current-company Work Process Requirement Lot to each resulting Work Inspection Detail Taka.
- `taka_to_roll` / `warehouse_receipt`: each resulting Work Inspection Detail Taka to its newly received Warehouse Stock Roll.

The unique operation index prevents the same completed source-row to result-row event from being recorded twice. The current source Work Process Requirement is locked and must match the Work Order, company, and submitted Lot identity. Warehouse receipt locks and verifies the inspection, Work Order, and exact submitted Inspection Detail rows before link creation.

## Merge and historical data

No runtime merge was added because no real merge creation flow exists in the working ERP. The table can represent multiple sources to one result if a future proven flow requires that relationship, but Task 5.5 introduces no merge UI, operation, or inferred links.

No historical backfill was performed. Legacy records remain fully operational; their genealogy is intentionally absent unless a relationship is created by a new completed operation with explicit source/result rows.

## Deployment safety and verification

`db:apply-reviewed-production-genealogy` is the hash-pinned, backup/maintenance/writes-stopped reviewed command for the exact Task 5.5 migration on `blackgrd`. It does not run automatically and rejects any non-live target, hash mismatch, prior ledger entry, pre-existing table, missing backup manifest, or changed business-row snapshot.

The exact migration was applied, inspected, rolled back, and reapplied only on `blackgrd_schema_testing`. The table was empty after reapply, its unique and lookup indexes were present, the migration ledger was recorded, and business-row count/ID snapshots were unchanged. No live database migration was executed.
