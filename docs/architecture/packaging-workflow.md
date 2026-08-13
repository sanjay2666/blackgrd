# Packaging Workflow

## Scope

Packaging is owned by `PackagingController`. It starts only after the existing Work Order action marks a Sale Order Item with `is_packaging_done = 1`; the Work Order page and its behavior remain unchanged.

## Packaging Order structure

```text
Customer
  -> Packaging Order (bulk or sample)
      -> Packaging Order Item (one Sale Order Item)
          -> Packaging Roll Allocation (one exact Warehouse Item Stock Roll/Taka)
```

One Packaging Order may contain multiple Sale Orders and Sale Order Items only when every source item belongs to the same customer. Each line snapshots item, quality, shade, coating, final-dispatch width, tube width, packaging type, Roll count, and Lot count. Each allocation preserves source WIS, warehouse/compartment, Lot, Roll, Taka, and the source available meter.

## Bulk and sample modes

- `bulk`: lot-wise Roll/Taka cart for large orders; partial Roll allocation is valid.
- `sample`: same-customer Sale Order Items can share one physical package while retaining independent Sale Order, item, Lot, Roll/Taka, and quantity records.

The cart uses `warehouse_item_stocks.insp_bal_quan_size` as the physical source and filters by company, item/type, colour, coating, print/extra job, active state, and optional warehouse. Proposed allocations reserve quantity; acceptance rechecks quantity under ordered locks.

## State and stock movement

Proposal changes only Packaging records. It never changes Sale Order delivered or pending meter.

```text
Draft allocation
  -> Warehouse acceptance
      -> WIS available decreases
      -> warehouse_in_items balance/allotted quantities update
      -> warehouse_balance_items snapshot updates
      -> warehouse_out_items link is created
  -> Pack quantity
  -> Future Sales Challan / Transport / Dispatch
```

Cancellation/reversal retains Packaging and Warehouse OUT history, restores WIS and Warehouse balances, and rejects already cancelled/dispatched orders. `production_genealogy_links` and existing Roll/Taka identities are never recreated or changed.

## Future dispatch boundary

Sales Challan, transport/LR, dispatch and invoice are intentionally not implemented here. They can link through `packaging_orders`, `packaging_order_items`, and accepted `packaging_roll_allocations.warehouse_out_item_id`; only that future approved dispatch event may update Sale Order delivery quantities.
