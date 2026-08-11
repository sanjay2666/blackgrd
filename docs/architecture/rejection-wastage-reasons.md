# Rejection and Wastage Reasons

Task 3.25 uses the existing `fabric_fault_reasons` table and `FabricFaultReason` model as the single canonical Reason Master. It is process-specific and currently contains 114 active records for Process IDs 1 Warping, 2 Weaving, 3 Dyeing, and 4 Coating. There is no separate rejection, wastage, or reason-type master, and no classification field was added. The existing IDs and records are preserved.

The master currently supports `reason`, `process_id`, legacy `financial_year` / `financial_year_id`, legacy audit-user fields, timestamps, company scope, and `Active` / `Inactive` / `Deleted` status. Existing financial-year data remains untouched; no yearly copies are generated. Logical uniqueness is company + Process + normalized reason (trimmed, case-insensitive) for non-deleted records. The same wording may exist for different Processes.

Active Processes are required for new or edited master records. The options endpoint returns only active reasons for the requested active Process. Any future transaction carrying both Process and Reason must use the server-side Process/Reason relation validation; client-side filtering is not trusted. Historical inactive reasons remain resolvable by ID.

Reason identities are retained for history. Delete is rejected and referenced reasons must be deactivated. Once a reason is referenced by inspection, work-order, warehouse, stock, job-work, rejection, wastage, or fault columns, changing its Process or core wording is blocked. Historical transaction free text and snapshots are never rewritten. Existing remarks remain transaction-level operator detail.

Reason Master defines WHY a rejection/wastage occurred; rejected or wasted quantity remains transactional data. No inspection, production, stock, Warping, Weaving, Dyeing, Coating, Warehouse, or workflow schema was redesigned. Coating Types remain separate from coating reasons.

Administration is under the existing Admin guard, organization scope, and `masters.view/create/update/delete` RBAC family. Navigation has one permission-aware Masters entry. Meaningful create, update, activate, and deactivate changes use centralized Audit Log events; reads are not business mutation events.
