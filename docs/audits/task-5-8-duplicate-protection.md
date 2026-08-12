# Task 5.8 — Duplicate Submit and Transaction Protection

Starting baseline: `1c44c7263c461dd3b11f907a9bc4c0ef9d088fcd`.

## Audit

- The legacy Work Order creation flow rejects duplicate selected Sale Order Items, locks those items, and rejects an existing Work Order for the same process.
- The current ERP already protects warehouse purchase inward with an exact invoice/vendor/warehouse/item/quantity/Taka check, Work Order warehouse receipt by Gate Pass and inspection identity, and Job Work dispatch with a named lock and locked stock rows.
- The current `WorkOrderController::store` retained its manual transaction but lacked the legacy Sale Order row lock and process-specific existing-Work-Order check.

## Change

- Added one shared jQuery asset to both common layouts. It disables non-GET form submit buttons while a submission is pending, restores them on browser back/retry, and suppresses only identical in-flight AJAX mutations. `data-duplicate-protection="off"` / `duplicateProtect: false` provide narrow opt-outs where a future legitimate repeated action requires one.
- Kept `/show-saleorderitems` UI and Work Order entry point intact. Its existing programmatic submit now dispatches the submit event so the shared protection applies.
- Added the Work Order backend check inside its existing controller action and transaction: duplicate selected IDs are rejected, Sale Order Items and Process are locked, and an active Work Order Item for the same Sale Order Item/process prevents the repeated business action.

No schema change was required. The protection does not use a generic idempotency table or block later independent receipts, movements, or purchases.
