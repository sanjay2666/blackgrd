# Printing Design Master

Printing Design Master is the single canonical `printing_designs` table and `App\Models\PrintingDesign` model. It defines reusable artwork/design identity within the canonical company context; it does not define process routing.

## Identity and fields

The master stores `design_name`, optional `design_code`, optional descriptive `description`, optional `display_order`, company scope, and `Active`/`Inactive`/historical `Deleted` status. Existing codes are preserved and normalized to trimmed uppercase for new or edited values. New logical duplicates are rejected by case-insensitive name or code within the company. No customer association exists because the current repository has no Printing Design/customer relationship; designs are reusable company-level identities.

The current architecture has no canonical artwork/file field or storage contract for Printing Designs, so this task does not invent uploads or media storage. No Fabric Quality, Colour/Shade, Chemical, Coating Type, formula, or production fields are duplicated here.

## Lifecycle and history

Inactive designs remain readable for historical references and are excluded from the active selection endpoint. The master is deactivation-first: deletion is rejected for both referenced and unreferenced identities. References are checked across current operational tables for `printing_design_id`, `print_design_id`, or `design_id` where such columns exist. Once referenced, `design_name` and `design_code` cannot change; descriptive metadata, display order, and status remain editable. Historical transaction snapshots are not rewritten when the master changes.

## Boundaries

`Printing` is a Process Master identity; `Printing Design` is artwork/design identity used by that process. Printing Design Master never controls a previous process, next process, workflow order, or Printing position relative to Coating. Printing position relative to Coating must never be globally controlled by Printing Design Master. Future Printing/Coating order must be captured per Fabric/Sale Order Item in an ordered, versioned workflow snapshot.

Fabric Quality, Colour/Shade, Chemical/formula, and Coating Type remain independent masters or transaction/configuration concerns. A design is not a Fabric Quality + Design record, shade recipe, chemical formula, mandatory coating, or production transaction.

## Access and audit

Admin management uses canonical `masters.view`/`masters.create`/`masters.update`/`masters.delete` RBAC resolution through the `admin` guard. The master appears once under AdminNavigation > Masters. Frontend users do not receive admin management access. Create, update, status transitions, and rejected destructive operations use the centralized Audit Log without binary file contents.
