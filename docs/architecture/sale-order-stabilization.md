# Sale Order stabilization architecture

Task 4.3 keeps the existing Sale Order header and Sale Order Item tables, URLs, Blade pages, soft-status behavior, and downstream compatibility. No workflow tables or process-order fields were added.

## Ownership and contract

The Sale Order header owns the commercial transaction: Customer, billing/shipping references and stored address text, order date/number, sales-order source, priority, development type, order slip, status, and totals. Sale Order Item owns the ordered Item/Fabric, Item Type, requested quantity (`meter`) and Unit, historical item-name/unit text, Fabric Quality/grey-quality text, Dyeing colour/shade text, Printing requirement/design text (`print_job`), Coating requirement text (`coating_type`), optional jobs/remarks, and item delivery/priority metadata.

Sale Order Item owns the fabric/item production requirement, but ordered manufacturing workflow is defined separately by the approved workflow revision.

Printing requirement does not determine Printing position; process order belongs to Workflow Definition and Versioning. Existing `print_position` usage is downstream Work Order compatibility/history and is not read from Sale Order Item as a route definition. Coating Type and Development Type do not select a process route.

## References and snapshots

`customer_id`, `billing_id`, `shipping_id`, `item_id`, `item_type_id`, and `unit_type_id` are references. `billing_address`, `shipping_address`, `item_name`, `unit`, `grey_quality`, `dyeing_color`, `coating_type`, and `print_job` are transaction values/snapshots or legacy compatibility values. Fabric Quality, Dyeing Colour, Printing Design, and Coating Type master values are not guessed into the existing text columns; ambiguous historical values remain unchanged.

Changing current Customer or master data must not rewrite historical Sale Order transaction snapshots. Active Customer and address ownership are checked for new orders and supplied update references. Inactive Customers remain readable on historical orders.

## Validation and company scope

New Sale Orders require an active Customer subtype in the canonical company, owned active billing/shipping addresses, active Item and Item Type references, an active Unit, and a positive meter quantity. Where an Item has a canonical Unit, another Unit is rejected. Sale Order Items now use the company scope, preserving the single-company boundary.

No conversion formula is introduced. `meter` remains the existing requested quantity; historical quantities are not changed. Negative/zero new or edited quantities are rejected. Legacy text requirements are retained.

## Mutations and safety

Header/item writes remain transactional. Sale Order numbers are still supplied through the existing number-series-compatible flow; no duplicate counter or resequencing was introduced. The in-transaction locked duplicate check reduces repeated-submit races, while database IDs and historical rows remain stable. Order-slip uploads accept only the existing safe document/image types and size limit and use generated filenames.

Items and orders with any downstream Work Order Item history cannot be materially changed or soft-deleted through these actions. Cancellation/deletion remains status-based and never cascades into production history. Clear and downstream operational actions remain legacy-compatible. The existing canonical RBAC route mapping and organization middleware remain in force; update now applies the same Customer/address ownership middleware as create.

Meaningful create/delete mutations are recorded through the centralized Audit Logger. Ordinary reads and uploaded binary contents are not logged.

## Deliberate boundaries and limitations

Priority, expected delivery, Development Type, scheduling, capacity, approvals, route selection, Work Order generation, and process execution remain outside Task 4.3. Existing reports and downstream pages that resolve live master labels were not broadly rewritten; touched Sale Order presentation continues to use stored transaction values where present. Existing legacy `grey_quality`, `dyeing_color`, `coating_type`, and `print_job` fields remain compatibility fields until a reviewed additive migration can map them deterministically.
