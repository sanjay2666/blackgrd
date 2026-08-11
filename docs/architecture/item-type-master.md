# Item Type Master

Item Type Master is the existing `item_type` table and `App\Models\ItemType` model. It defines the classification/state/category used by the current Item, stock, warehouse, purchasing, work-order, inspection, requisition, job-work, and Sale Order Item flows. It is not an Item Master and it is not a process or workflow engine.

## Established identities

The live single-company data audit on 2026-08-11 found these records (IDs are historical and immutable):

| ID | Name | Stable code | Compatibility |
|---:|---|---|---|
| 1 | Yarn | YARN | Yarn selectors and stock |
| 2 | Beam | BEAM | Beam selectors and stock |
| 3 | Greige | GREIGE | Greige stock/requisition logic |
| 4 | Dyed | DYED | Dyed stock/requisition logic |
| 5 | Coated | COATED | Coated stock/requisition logic |
| 6 | General | GENERAL | General purchased items |
| 7 | Chemical | CHEMICAL | Chemical items |
| 8 | Fabric | FABRIC | Legacy Fabric identity; some legacy selectors treat it as Greige-compatible |
| 9 | Colour | COLOUR | Colour items |

IDs 1, 2, 3, 4, 5, and 8 are protected from identity changes. The migration adds `short_code` and `display_order` and backfills these codes by explicit historical ID mapping; it never resequences, merges, or deletes records.

Greige transactions conventionally have no dyeing colour and no coating; Dyed transactions carry dyeing colour and no coating; Coated transactions carry dyeing colour and coating. Those attributes remain transaction/stock data. No capability flags or rule engine were added.

## Lifecycle and authorization

The admin CRUD uses the existing `masters.*` RBAC permission and permission-aware `AdminNavigation`. Name and short code are normalized and unique among non-deleted records. Status is canonical `Active`/`Inactive`; inactive types remain available to historical joins but are excluded from active selectors. Referenced types cannot be deleted. Core types cannot be deactivated because existing ERP flows require them.

Meaningful create, update, and status changes are written through the centralized `AuditLogger` with before/after values. Backend route enforcement remains required; navigation visibility is not authorization.

## Boundaries and compatibility inventory

Item Type does not contain item name, quality, HSN, unit, recipe, colour, coating, or stock. Task 3.15 owns Item Master. Item Type does not define mandatory process sequences, printing routes, or order-specific routing.

The repository inventory found hard-coded IDs in legacy Work Process Requirement, Warehouse, Common, Job Mill Work, Sale Order, and Blade selector/report code. Meaningful examples include Work Process Requirement ID 4 checks, Warehouse ID sets `[1,2,3,4,5,6,8]`, Common ID sets `[3,8]` and `[1,2]`, Job Mill Work sets `[3,4,5,6]`, Sale Order Item Type 8, and display selectors for IDs 1/2/8. These were intentionally not broadly refactored in this master task. ID 8 remains Fabric and is not silently merged into ID 3.

The stock compatibility contract remains attribute-based: warehouse balance matching preserves `dyeing_color` null/match and `coated_pvc` null/match behavior. Item Type Master does not move stock logic into the master.

`Item Type Master defines item classification and must not become an order-specific production workflow engine.`
