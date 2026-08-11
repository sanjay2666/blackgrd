# Task 3.9 — Customer Master Audit

## Existing architecture and live data

The canonical Customer identity is the shared `individuals` table with `type = 'customers'`; no duplicate customer table was created. Live read-only inspection found 248 customers, alongside 17 employees, 194 vendors, 179 transporters, and 7 master-party rows. Customer IDs remain unchanged. Existing customer records contain GSTIN/PAN/contact values with legitimate blanks and duplicate GSTINs, so no code or tax data was fabricated and no global GSTIN constraint was added.

`individual_address` is the canonical multi-address table. Billing and Shipping are separate address types, with State ID, city, PIN, default flag, and Active/Inactive/Deleted status. Sale Orders reference `customer_id`, `billing_id`, and `shipping_id` and also store billing/shipping text snapshots. The existing customer autocomplete returns active `type='customers'` rows; this contract remains intact. Existing Lab/Work Order/dispatch/report flows and snapshot fields were not redesigned.

## Implemented contract

Customer Master adds optional Customer Code to the shared table, with case-insensitive uniqueness for current customers. Name, company, GSTIN, PAN, phone, WhatsApp, email, and status are managed without touching non-customer subtypes. GSTIN/PAN are normalized and validated server-side when changed; existing legacy placeholders remain compatible. Customer references are validated by subtype, company, active status, and address ownership. Sale Order creation now rejects inactive/non-customer IDs and cross-customer billing/shipping address IDs while preserving existing snapshot writes.

Address CRUD uses the existing `individual_address` table, supports multiple billing/shipping rows and default-per-type behavior, validates active State IDs, and logically protects referenced addresses. Referenced customers cannot be hard-deleted. Deactivation is safe and non-destructive: open orders are not cancelled, addresses are not removed, and historical transactions are untouched. Referenced legal identity fields (Customer Code, GSTIN, PAN) cannot be changed; name/contact/address corrections remain possible.

## Boundaries and inventory

Customer Master is separate from Frontend Users, Employees, Vendors, Transporters, GST Rate Master, CRM, receivables, credit control, accounting, and customer portal behavior. Meaningful existing inventory includes Sale Order customer/address references, Work Order Item customer IDs, courier customer IDs, address lookups, Sale Order reports, dispatch/challan address snapshots, and existing `cus_name`/`cus_id` courier snapshots. No hard-coded Customer IDs were changed. Customer/address master changes must not rewrite historical Sale Order, Lab Test, challan, invoice, or dispatch snapshots.
