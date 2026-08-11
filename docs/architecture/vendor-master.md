# Vendor Master

Vendor Master reuses canonical shared party identity; it must not create a conflicting duplicate Vendor record system. The canonical table/model is `individuals` / `App\Models\Individual`, filtered server-side to `type = 'vendors'`. Existing vendor IDs are retained exactly. The current architecture also contains Customers, Employees, Transporters, Agents, Labourers, and Master parties in this shared table; Vendor routes cannot operate on another subtype.

The live architecture uses Vendor Name, optional Company Name, GSTIN, PAN, phone, WhatsApp, email, status, and the additive nullable `vendor_code`. Existing vendor codes are not fabricated or backfilled. Supplied codes are trimmed and unique case-insensitively within the company and Vendor subtype. GSTIN and PAN remain separate, are trimmed/uppercased and validated when supplied; legacy blanks/placeholders remain compatible. GSTIN is not globally unique because legacy duplicates are possible.

Addresses reuse `individual_address` with Billing/Shipping types, State IDs, city, PIN, default flag, and status. Vendor/address master changes must not rewrite historical Purchase, receiving, invoice, or Job Work snapshots. Vendor and address IDs supplied together must be validated server-side as belonging to the same Vendor. Referenced addresses cannot be deleted.

Active and Inactive Vendors remain readable for history. Inactive Vendors are excluded by the active Vendor selection endpoint; existing transactions are not cancelled. Vendor records referenced by Purchase Orders, purchases, warehouse receipt/stock, Job Work/Mill Work, or receiving cannot be hard-deleted. Referenced Vendor Code, GSTIN, and PAN are protected from mutation; name/contact/address corrections remain allowed.

Purchase Orders retain `vendor_id`, billing address references, and their existing snapshots. Purchases and receiving retain `vendor_id`/`vendor_ind_id` and `vendor_name` snapshots. Stock Mill Dispatch, returns, warehouse stock, and Job Work retain their existing Vendor IDs and snapshot fields. No Purchase Request, Purchase, AP, payment, taxation, warehouse, or Job Work redesign is included.

Vendor Master has dedicated `vendors.*` Admin permissions, permission-aware AdminNavigation under Masters, centralized Audit Log mutations, and Admin/web guard separation. It is not a Frontend User and creates no login or portal. Customer and future Transporter boundaries remain separate.
