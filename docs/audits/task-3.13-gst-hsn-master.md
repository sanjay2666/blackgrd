# Task 3.13 — GST and HSN Master Audit

## Existing architecture

The audit found the existing `gst_rates` table/model and Admin CRUD. It stores the reusable total rate and is used by warehouse-facing legacy flows. No canonical HSN table/model or SAC master existed. Company GSTIN is already a separate Company Master field. Items already have legacy `hsncode`, `cgst`, `sgst`, `igst`, and sale variants; purchase and sale transaction rows store their own HSN/tax values.

## Implemented boundary

`hsn_codes` is the canonical HSN master and optionally references `gst_rates`. GST rates use the single decimal `gst_rate` percentage representation. CGST/SGST/IGST remain transaction snapshot fields; no tax engine, state comparison, invoice accounting, e-invoice, SAC, or Item Master work was added.

GST/HSN Master provides reusable reference/default data; historical transactions retain their stored tax values.

HSN identity is a normalized string, not an integer. Validation rejects malformed/duplicate codes. Rates accept 0–100 and two decimal places, and duplicate rates are rejected. Existing records are preserved; no live codes or rates are seeded or merged. Referenced masters cannot be deleted and status changes are audited.

## Compatibility and hard-coded inventory

Legacy Item and transaction tax columns were not removed or renamed. The purchase UI still initializes component values with legacy zero defaults and computes transaction snapshots in its existing flow; this is intentionally deferred to Item/Purchase/Sales tasks. No fixed legal-rate list was added to controllers or Blade. Company/branch/vendor/customer GSTIN searches are identity fields, not rate logic.

## Security and operations

Both masters use the canonical `masters.view/create/update/delete` permission family, mapped server-side for resource and activate/deactivate routes. Frontend users cannot pass the Admin guard/RBAC middleware. AdminNavigation visibility is permission-aware. AuditLogger records create, update, and status changes; reads are not logged.

Schema changes are additive/minimal: create `hsn_codes`, add GST rate description, and increase GST rate precision to two decimal places. No live database migration, write, seed, destructive command, backup, or maintenance-mode operation was performed during development; the live `blackgrd` database remains protected.
