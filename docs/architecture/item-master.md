# Item Master

`items` / `App\Models\Item` is the single canonical company-level Item Master. Item identity is the preserved `item_id`, with the existing common fields `item_name`, `item_code`, `item_type_id`, `unit_type_id`, legacy `hsncode`, tax defaults, descriptions/remarks, and record status. Item codes are normalized to uppercase and are unique among non-deleted Items when supplied. Names are intentionally not globally unique because legacy data permits the same visible name across types/configurations.

Items classify through the canonical Item Type Master (`item_type`). New configuration accepts active Item Types and active Units from `unit_type`; an inactive historical relation remains visible while editing. Canonical HSN and GST defaults are nullable additive references (`hsn_code_id`, `gst_rate_id`). Legacy HSN text and existing item tax fields remain for compatibility. These defaults never rewrite transaction tax snapshots.

Item status is Active or Inactive for configuration. Inactive Items remain readable for history and are excluded by existing active autocomplete/search paths. A referenced Item cannot be hard-deleted: the existing delete endpoint deactivates it. An unreferenced Item may use the established Deleted status. Referenced Items also cannot change Item Type, preventing unsafe reclassification of historical Yarn, Greige, Dyed, Coated, or other material identity.

Legacy Item Type ID 8 (`Fabric`) is preserved. Existing UI and stock paths that explicitly use Type 8 continue to do so; no bulk conversion is performed. Greige/Dyed/Coated stock state remains transaction/warehouse logic based on Item Type and dyeing colour/coating values. Item Master does not encode stock state.

The Item Master preserves existing process relationships, including Item Yarn Requirements, but does not define a final process sequence. It does not implement Yarn composition/recipes, Fabric Quality, Colour/Shade, Chemical, Coating, UOM conversion, or printing route fields. In particular, printing remains Sale Order Item/workflow-specific.

**Item Master defines canonical material/item identity. It does not define the final order-specific production workflow.**

Historical transaction snapshots must not be rewritten when Item Master changes. Existing Sale Order Item, Work Order, Purchase, warehouse, inspection, job-work, and reporting snapshot/name fields therefore remain unchanged.
