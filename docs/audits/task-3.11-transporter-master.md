# Task 3.11 — Transporter Master Audit

The canonical Transporter identity is the shared `individuals` table with `type = 'transport'`; no duplicate `transporters` identity table was created. The prior shared-party audit records 179 Transporters, and no IDs are resequenced, recreated, remapped, or fabricated. Existing Courier rows are a separate customer shipment/tracking module with `cus_id`, item, tracking, and `courier_name`; Courier is not merged into Transporter.

Repository inventory found no current Gate Pass, Sales Challan, dispatch, Purchase receiving, Stock Mill Dispatch, or Job Work `transporter_id`, `transporter_ind_id`, or `transport_id` reference columns. Their current vehicle/driver/LR/GR/freight/document values remain transactional where present; no historical snapshot fields were removed or rewritten and no ambiguous names were guessed into IDs.

The only schema change is nullable `individuals.transporter_code` with a lookup index. Existing codes, GSTIN, PAN, and IDs are not fabricated. Codes are trimmed and case-insensitively unique within the company and Transporter subtype. GSTIN/PAN are separate, uppercase, optionally validated, and GSTIN is not globally unique. Referenced legal identity fields are protected.

`individual_address` is reused. Address create/update validates active State and same-party ownership; forged Customer/Vendor/other-Transporter address IDs are rejected. Referenced addresses cannot be deleted. Active/Inactive status preserves open/history records without cancellation; inactive Transporters are excluded from `/list_transporter` but remain historically readable.

Admin CRUD, address CRUD, active lookup, RBAC via existing `masters.*`, navigation, Audit Log, and server-side subtype/company authorization are implemented. Transporter is distinct from Vehicle, Driver, Vendor, Customer, Frontend User, and the existing Courier module. Vehicle, Driver, LR/GR and freight details remain transactional unless a separate reviewed master is introduced later. Transporter/address master changes must not rewrite historical Gate Pass, challan, dispatch, receiving, or Job Work snapshots.
