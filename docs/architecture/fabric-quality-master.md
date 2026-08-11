# Fabric Quality Master

Fabric Quality Master is the single canonical fabric_qualities table and App\Models\FabricQuality model. It defines reusable fabric specification identity; it does not define colour, coating, printing position, or order-specific production workflow.

The repository audit found no prior Fabric Quality table/model or fabric_quality_id/quality_id relationship. Existing grey_quality fields are text snapshots in Sale Order Items, Work Order Items, warehouse records, and related reports. They remain historical snapshots and are not rewritten from this master.

## Supported fields and identity

The master stores quality_name, optional stable quality_code, optional description, the existing ERP-style gsm and width notations, display_order, company scope, and canonical Active/Inactive/Deleted status. GSM and width remain strings because current Item data uses integer/string and notation-compatible fields; no historical precision is rewritten and no unsupported textile limits are imposed.

Within a company, active/non-deleted records use normalized name + GSM + width as the specification identity. A non-empty quality code is also unique among active/non-deleted records. Existing IDs and values are never resequenced, merged, or regenerated.

## Boundaries and integration

Item Master remains the canonical item/material identity. No Item fields are duplicated and no guessed Item-to-quality mappings are created. Yarn Recipe remains owned by Item through ItemYarnRequirement; Fabric Quality does not own or move recipes. Colour/Shade, coating, printing, and process/order workflow remain separate masters or transaction concerns.

No Fabric Quality reference currently exists in the repository. The service detects future/historical fabric_quality_id or quality_id references without adding a second quality system. Historical transaction snapshots remain readable even when a master is inactive or later edited.

Referenced identities cannot be hard-deleted. Deactivation is the safe lifecycle action. Once referenced, quality name/code/GSM/width identity fields are immutable; metadata and status remain manageable under authorization.

## Authorization and audit

Admin CRUD and activate/deactivate routes use the existing Admin guard, organization scope, RBAC, and masters.* permission pattern. Frontend users do not receive master-management routes. AdminNavigation only exposes the link when masters.view is granted. Meaningful create, update, and status mutations use the centralized AuditLogger with before/after values; reads and searches are not logged.
