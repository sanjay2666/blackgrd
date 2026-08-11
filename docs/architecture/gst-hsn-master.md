# GST and HSN Master

Task 3.13 establishes two reusable, single-company master references:

- `hsn_codes` / `App\Models\HsnCode` stores a normalized string HSN code, description, optional GST-rate reference, and canonical `Active`/`Inactive`/`Deleted` status.
- `gst_rates` / `App\Models\GstRate` stores one decimal total GST percentage (`gst_rate`, two decimal places), an optional description, and status.

The project has no separate SAC master. Existing transaction labels may say HSN/SAC, but this task remains HSN-only. Item Master is not expanded: its legacy `items.hsncode` and existing CGST/SGST/IGST fields remain compatible, and transaction rows retain their own HSN and tax snapshots.

GST component meaning remains a transaction concern: intra-state GST is CGST plus SGST, while inter-state GST is IGST. No state comparison or invoice tax engine is introduced here. The master exposes a total rate; it does not store an arbitrary component split.

Company `gstin` remains legal registration identity in Company Master and is unrelated to GST rate configuration.

HSN and GST masters provide reusable reference/default data; historical transactions retain their stored tax values. Master edits do not rewrite Items, purchase/sale orders, invoices, challans, stock, job-work, or accounting records.

HSN code identity is normalized by trimming and collapsing whitespace, uppercased for storage, and validated as a bounded code string. Duplicate active/inactive identities are rejected at the service boundary. GST rates are numeric from 0 through 100 with up to two decimal places; duplicate configured rates are rejected. Referenced records cannot be deleted; deactivation preserves historical references and prevents normal future selection. Soft-deleted rows remain in the database.

The existing `masters.*` RBAC permission family protects both resources, including backend route enforcement for CRUD and status endpoints. Admin navigation is filtered by the same permission. All meaningful mutations are recorded through `AuditLogger`.
