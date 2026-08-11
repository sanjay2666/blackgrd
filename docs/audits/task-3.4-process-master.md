# Task 3.4 — Process Master Audit

## Findings

- Canonical source: `process_items` / `ProcessItem`; no separate `process_types` table was found.
- Existing live rows were inspected read-only. IDs 1–8 are retained, including Warping 1, Weaving 2, Dyeing 3, and Coating 4.
- Current consumers include Work Orders, Work Order Items, WPR, Process Requirements, inspections, machines, individuals, warehouse/job-work records, reports, and serial-number compatibility code.
- Existing process children/configuration remain separate from Process Master; no merge with detailed Process Items/planning was performed.
- A repository inventory found fixed-ID compatibility in Work Order, WPR, inspection, warehouse, and serial-number logic. This task does not broadly refactor those consumers.

## Implemented controls

- Additive migration adds company scope, stable short code, description, optional Department association, and master display order without changing existing IDs or names.
- Existing codes were absent, so compatibility codes were populated only for null values: WRP, WEV, DYE, COA, PKG, DPR, CPR, LAB.
- Names and short codes are unique among non-deleted rows within the canonical company.
- Department IDs are backend-validated as active and company-owned; Department and Process remain distinct concepts.
- Admin Process Master has search, Department/status filters, compact Bootstrap UI, create/edit, and activate/deactivate. Delete rejects with a validation error; historical references are retained.
- IDs 1–4 are protected from identity mutation. Referenced codes are protected. Status is canonical Active/Inactive/Deleted-compatible and inactive records remain historical.
- RBAC uses admin-only `processes.*` permissions and explicit route mappings; Frontend User and Department Access do not grant Process Master administration.
- Create, update, and status changes use `AuditLogger` with before/after snapshots.

## Explicit non-goals

`display_order` is only display/default master ordering. Process Master does not define the final Sale Order Item workflow. Printing is a reusable process identity only; its position relative to Coating is intentionally not global and no printing routing was added.

## Database safety

The live `blackgrd` database was inspected read-only. No live migration, data rewrite, destructive command, or maintenance-mode change was run. The additive migration is intended for the disposable `blackgrd_schema_testing` workflow before any separately approved live application. No backup/SHA or live migration applies to this task run.
