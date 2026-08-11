# Task 4.3 Sale Order stabilization audit

## Findings and disposition

- Existing architecture: `SaleOrderController` serves the established Sale Order list/create/store, soft-delete, reports, print, item update, cancellation, clear, reason, and AJAX routes. `SaleOrder` is the commercial header; `SaleOrderItem` is the requirement/detail row. Work Order Items reference Sale Order Items downstream.
- Header: Customer, address references and snapshots, order date/number, priority, development type, slip, status, and totals remain on `sale_orders`.
- Item contract: Item/Fabric + Item Type, positive meter + Unit, grey/fabric-quality specification, optional Dyeing colour/shade, Printing requirement/design, Coating requirement/type, optional job/remarks, delivery date, and priority. It deliberately does not contain an ordered process route.
- Customer: new writes require active `customers` subtype and canonical company. Vendor, Employee, and Transporter records are rejected by subtype validation. Historical inactive customers remain queryable through existing rows.
- Addresses: billing/shipping IDs must belong to the selected Customer. Stored address text is authoritative for the transaction and is never refreshed from the master.
- Item/Item Type/Unit: active, company-scoped Item and Item Type are required; Unit must be active and compatible with the Item where the Item has a canonical Unit. Hidden form values are not trusted.
- Fabric Quality, Dyeing, Printing, and Coating: current schema uses legacy/text requirement columns; no ambiguous historical value was converted or guessed. No global Dyeing/Printing/Coating rule was introduced.
- Quantity: `meter` remains the existing requested quantity. New and edited values must be greater than zero; no conversion or historical rewrite occurs.
- Numbering: existing numbering remains intact; no manual duplicate counter, ID resequencing, or historical renumbering was added.
- Transactions: header and all item inserts/updates remain atomic. Duplicate number checking is locked inside the transaction. Generated upload names avoid client filename/path control.
- Downstream protection: any Work Order Item history blocks material item mutation and Sale Order deletion. Production rows are not cascaded or deleted.
- Status: existing `Active`, `Inactive`, and `Deleted` compatibility values are retained. No new workflow or approval state machine was created.
- Authorization/company: existing auth, organization, RBAC, and route-to-permission mapping remain authoritative; update now receives the Customer/address validation middleware.
- Audit: centralized Audit Logger records meaningful Sale Order create/delete activity; route mutation auditing remains in place for the existing action surface.

## Hard-coded inventory

The audit found legacy text comparisons and downstream Work Order `print_position` handling, `grey_quality`/`print_job` propagation, and the existing `SaleOrder::max('id') + 1` display-only lot-number hint. These remain compatibility behavior. No Sale Order route logic was made dependent on process IDs, Coating Type, Printing Design, `print_position`, or hard-coded Item Type IDs.

## Verification scope

No schema migration was required, so no disposable MySQL schema mutation was performed. Existing IDs, numbers, snapshots, Work Orders, and production history are preserved. Focused contract coverage is in `tests/Unit/SaleOrder/SaleOrderStabilizationContractTest.php`; the repository regression suite and final quality gate are required before commit.
