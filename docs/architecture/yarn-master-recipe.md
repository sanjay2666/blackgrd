# Yarn Master and Recipe Architecture

Yarn Master reuses canonical Item identity; it must not become a separate conflicting product master.

There is no independent Yarn product table in the current ERP. A Yarn is an `items` row whose canonical `item_type_id` references the active Item Type named `Yarn` (the verified current company record is ID 1). Item name, code, lifecycle status, and Unit are owned by Item Master. The Yarn-specific details currently used by the ERP are therefore the Item name/code and Item Unit; textile specification such as count notation remains in the Item name/remarks rather than being forced into a numeric count field. No duplicate Yarn name/code/unit source was added.

## Recipe ownership and semantics

`item_yarn_requirements` is the canonical recipe/requirement table. A row belongs to a target Item, a Process Master row, and a Yarn Item. `reed_peak` is the existing Reed/Pick planning value and `yarn_quantity` is the existing decimal requirement quantity. The schema does not label the quantity as a percentage or per-meter factor, so the application preserves it as an ERP-defined requirement quantity and does not introduce a conversion or 100%-total formula.

Process remains part of the ownership. Warping and Weaving may have different recipe lines, and Start Requisition continues to retrieve active Yarn IDs by `item_id + process_id`. The Yarn master/recipe layer does not redesign Warping, Weaving, stock, purchasing, or production consumption.

Recipe edits define expected/default requirements for future planning. Yarn Recipe defines expected/default requirements. Actual production consumption and warehouse stock remain transactional sources of truth. Existing warehouse issues, returns, Work Order usage, Job Work usage, and historical production are not rewritten by recipe edits.

## Units and lifecycle

Recipe Unit is stored in the existing text column for compatibility, but new/edited rows must match the selected Yarn Item's canonical active Unit Master name. Existing Unit IDs are unchanged; the current live Yarn stock/recipe convention is Kg. No Cone-to-Kg or generic UOM conversion engine is introduced. Inactive Yarn Items remain visible through historical relationships but are excluded from new recipe selectors. Referenced Items follow Item Master protection and are deactivated rather than hard-deleted.

An exact duplicate active line (same target Item, Process, Yarn, Reed/Pick, quantity, and Unit) is rejected. The live database contains historical repeated `(item, process, yarn)` combinations, including lines differing by Reed/Pick or status, so the broader three-column combination is intentionally not made unique.

## Authorization, audit, and navigation

Recipe CRUD and the existing Item Master Manage Yarn routes use the canonical `masters.*` permissions (`masters.view`, `masters.create`, `masters.update`, `masters.delete`, and `masters.manage-yarn`). They are inside the admin guard and RBAC middleware; Frontend Users do not receive these admin routes. Yarn Recipe create/update/remove events are recorded through the centralized Audit Log with before/after values. The existing Item Yarn Requirements entry remains under the Masters section of `AdminNavigation`.

Legacy operational references such as `yarn_id`, `used_yarn_id`, and Item Type checks remain in their owning modules. The known legacy numeric Item Type 1 checks in production views and controllers are compatibility debt; this task does not rewrite operational flows. The canonical master validation resolves the Yarn Item Type by its verified master name and preserves all existing IDs and references.
