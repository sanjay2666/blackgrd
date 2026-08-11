# Task 3.10 — Vendor Master Audit

Audit confirms one canonical Vendor identity: `individuals` with `type = 'vendors'`; no duplicate `vendors` identity table was created. Existing Vendor IDs and records are preserved. The shared table currently supports Customer, Employee, Transporter, Agent, Labourer, and Master subtypes, and Vendor service/controller assertions require the Vendor subtype and canonical company scope.

Existing references include Purchase Orders and Purchases (`vendor_id`), Warehouse in/stock/stock-file records (`vendor_id`), Stock Mill Dispatch/returns (`vendor_id`), Work Purchase Requirements, and receiving (`vendor_ind_id`). Historical `vendor_name`, invoice, address, and document snapshots remain untouched. Vendor Master does not redesign Purchase Requests, Purchase, receiving, warehouse, Job Work, invoices, taxation, or accounting.

The only schema addition is nullable `individuals.vendor_code` with a lookup index. No existing codes, GSTIN, PAN, or IDs are fabricated. Codes are trimmed and case-insensitively unique among non-deleted Vendors in the company. GSTIN/PAN are separate, normalized uppercase, optionally validated, and GSTIN is not made globally unique. Referenced legal identity fields are protected.

`individual_address` is reused. Address create/update validates active State and ownership; a forged Vendor/address pair is rejected. Referenced addresses cannot be deleted. Active/Inactive status is safe and non-destructive: deactivation does not cancel open transactions, and inactive Vendors remain historically readable but are excluded from active Vendor autocomplete. Referenced Vendors cannot be hard-deleted.

Admin CRUD, address CRUD, RBAC, navigation, Audit Log, and server-side subtype/company authorization are implemented. Vendor is not a Frontend User, Customer, or Transporter and no portal/login is introduced. Vendor/address master changes must not rewrite historical Purchase, receiving, invoice, or Job Work snapshots.
