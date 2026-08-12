# Task 5.4 — Lot, Roll, Taka and Beam Identity

## Scope and reference review

The old ERP was inspected read-only before changing the Laravel implementation. The active production flow uses existing transactional fields rather than separate Lot, Roll, Taka, or Beam masters:

- `work_process_requirements.req_lot_no` is the dyeing/production lot and is carried into the later dyeing requirement and inspection records as `dyeing_lot_number`.
- `work_inspections.insp_taka_number` represents the output beam after warping; the weaving requisition explicitly selects that same value as its Beam Number.
- inspection output rows keep the Greige and dyeing Taka labels in `insp_taka_number` and `dyeing_taka_number`, and preserve them through Gate Pass, Warehouse Out, Warehouse Stock, and Job Work.
- `warehouse_item_stocks.packet_number` is the system roll/packet identity. It is copied on Job Work movement so one physical roll can retain its identity across stock rows.

## Existing Laravel implementation

The port already preserves the Lot, Beam, and Taka flow using the same fields and foreign-key references (`work_order_id`, `work_process_req_id`, `insp_id`, `inspection_id`, `gate_pass_id`, and `wis_id`). Lots are already allocated from the `wpr-lot` number series. Work-inspection warehouse receipts already create deterministic `packet_number` values from inspection and row position.

The remaining incomplete port was ordinary warehouse inward and invoice receipt: the old ERP assigned a packet number there, but the ordinary inward path had the assignment commented out and the invoice receipt used a timestamp/random string.

## Minimal stabilization

- Reused `warehouse_item_stocks.packet_number`; no replacement table, new master, relation, or data rewrite was added.
- New ordinary warehouse inward and invoice-receipt rolls now receive `ROL-{warehouse_item_stocks.id}` after their stock row is created, inside their existing transactions.
- This is deterministic, stable, and collision-free for new physical rolls. Existing Job Work copies continue retaining the original packet number, as required for movement traceability.
- No database migration is required. A unique index would be incorrect because copied rows intentionally represent the same physical roll.

Task 5.5 split/merge genealogy and later production execution work remain out of scope.
