# Task 3.25 Audit — Rejection and Wastage Reasons

## Findings and outcome

- Canonical structure: existing `fabric_fault_reasons` / `FabricFaultReason`; no duplicate table was created.
- Role: existing records are process-specific fabric fault / rejection reasons. No established wastage-only table or separate type field exists, so rejection and wastage use this shared master without pretending the master stores quantities.
- Live read-only audit: 114 records, IDs 1–114 preserved, all Active, company 1, financial year `2627` / financial-year ID 1. Process IDs are 1–4; Process Master confirms Warping, Weaving, Dyeing, Coating. Packaging, D-Printing, C-Printing, and inactive Lab have no current reasons.
- Fields and boundaries: reason, Process, legacy financial-year metadata, company/audit metadata, timestamps, and status. No code, description, display order, reason type, quantity, stock adjustment, or valuation was added.
- Lifecycle: Active / Inactive / historical Deleted vocabulary is preserved. Inactive records remain readable but are excluded from new options. Deletion is rejected; referenced records are never cascade-deleted.
- Identity protection: Process and core wording cannot change after operational reference; typo/meaning changes are therefore conservatively protected as identity changes. Duplicate prevention is Process-scoped and normalized for new data; legacy duplicates are not merged.
- Compatibility: existing Work Inspection, Work Order, Warehouse receiving/rejection, Warping, Weaving, Dyeing, Coating, Job Work, reports, free-text remarks, and snapshot behavior were not redesigned. Historical rejection/wastage reason snapshots must not be rewritten when master data changes.
- Validation: the new options endpoint is active-only and Process-filtered. A process-specific Reason selected in a transaction must be validated against the transaction Process server-side. `validateProcessRelation()` supplies this invariant for integrations.
- RBAC and authorization: existing canonical `masters.*` permissions, Admin/web guard separation, organization scope, route middleware, and permission-aware AdminNavigation are reused. Frontend users do not receive master-management access.
- Audit Log: create, update, activate, and deactivate mutations record meaningful before/after values through `AuditLogger`; reads are not logged as mutations.
- Financial Year: legacy fields remain because current records use them; the master is not duplicated automatically per FY.

## Hard-coded reason inventory

The repository’s operational reason references are `fabric_fault_reason_id` / `fault_reason_id` in Work Inspection, Work Order, Warehouse, Gate Pass, Job Work, and report views. No competing rejection or wastage master, type array, or reason literal dropdown was found. Existing frontend Work Order selection continues to use the canonical model and was intentionally not refactored as part of this foundation task.

Reason Master defines WHY a rejection/wastage occurred; rejected or wasted quantity remains transactional data.

Historical rejection/wastage reason snapshots must not be rewritten when master data changes.

A process-specific Reason selected in a transaction must be validated against the transaction Process server-side.

## Schema and safety

No migration, live data write, backup, SHA-256 migration apply, or maintenance-mode change was required. The live `blackgrd` database remained protected and DatabaseSafetyGuard remained enabled.
