# Unit Master

The canonical company-global measurement master is the existing `unit_type` table and `App\Models\UnitType` model. It is intentionally not department- or branch-scoped and no second unit/UOM table is introduced.

## Identity and fields

`unit_type_id` is the stable legacy primary key. The existing live identities are ID 1 `PCS` (Deleted), ID 2 `Meter` (Active), ID 3 `Line` (Deleted), and ID 4 `Kg` (Active). Production code contains numeric compatibility assumptions for IDs 2 and 4; those names are protected server-side.

The original `unit_type_name`, `status`, `created`, and `modified` fields remain authoritative. The Task 3.12 extension adds nullable `unit_code`, `description`, `decimal_places`, and `display_order`. Existing rows are not resequenced, renamed, merged, or seeded. Name and code comparisons are trimmed and case-insensitive for new changes.

`decimal_places` is descriptive metadata only. Transaction quantity columns are not changed and the Unit Master does not force integer or decimal storage.

## Lifecycle and boundary

Active units are available to existing selectors through the established `UnitType::active()` scope. Inactive units remain readable for historical records and are excluded by those active selectors. Delete is represented by the existing canonical `Deleted` status; referenced units are rejected and must be deactivated instead. No hard delete or ID reuse is allowed.

Unit Master defines measurement identities, not Item-specific conversion quantities. It does not contain packaging, lot, or item-specific relationships such as `1 Roll = 100 Meter`, and it introduces no conversion engine.

## Authorization and audit

The Admin resource routes reuse the existing `masters.view/create/update/delete` RBAC permissions and remain behind the Admin guard, organization middleware, and mapped-permission middleware. Frontend users do not gain Unit Master administration. Navigation is permission-aware under Masters as `Unit Master`.

Create, update, status, and delete attempts are covered by the route audit middleware; the Unit service also records meaningful create/update/status changes with old/new values through `AuditLogger`.
