# Transporter Master

Transporter Master reuses canonical shared party identity; it must not create a conflicting duplicate Transporter record system. The canonical table/model is `individuals` / `App\Models\Individual`, filtered server-side to `type = 'transport'`. The repository’s Courier table is a customer shipment/tracking transaction module, not a Transporter identity master, and remains separate.

The existing company has 179 Transporter rows according to the prior shared-party audit. Their IDs are preserved exactly. The master manages only practical identity fields already present in `individuals`: name, optional company name, nullable `transporter_code`, GSTIN, PAN, phone, WhatsApp, email, and status. Codes are not backfilled, are normalized by trimming, and are case-insensitively unique among non-deleted Transporters in the company. GSTIN/PAN are separate, uppercased, optionally validated, and legacy blanks/placeholders remain compatible; GSTIN is not globally unique.

Addresses reuse `individual_address` with Billing/Shipping types, State IDs, city, PIN, default flag, and status. Transporter/address IDs are validated server-side as belonging to the same company-scoped Transporter. Transporter/address master changes must not rewrite historical Gate Pass, challan, dispatch, receiving, or Job Work snapshots.

The current schema inventory found no existing `transporter_id`, `transporter_ind_id`, or `transport_id` columns in the Gate Pass, dispatch, Purchase, or Mill Work tables; those flows currently retain their own transactional fields and no ambiguous names were migrated. The active lookup `/list_transporter` supplies only active `type = 'transport'` rows. Inactive rows remain readable for history and are excluded from new selections.

Transporter means a logistics service provider. Vehicle number, Driver, LR/GR/docket, and freight details remain transactional; no Vehicle/Fleet or Driver Master is introduced. Courier remains the existing separate customer shipment/tracking table. Vendor, Customer, Employee, and other party subtypes remain protected. Transporter is not a Frontend User and no portal/login is created.

Referenced Transporters and addresses cannot be hard-deleted. Deactivation is non-destructive and does not cancel open documents. Referenced Transporter Code, GSTIN, and PAN are protected from mutation; name/contact/address corrections remain allowed. Admin CRUD/address routes use existing `masters.*` permissions, AdminNavigation, centralized Audit Log, and Admin/web guard separation.
