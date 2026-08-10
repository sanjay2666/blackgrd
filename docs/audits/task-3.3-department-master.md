# Task 3.3 — Department Master Audit

## Existing implementation audit

The existing `departments` table and `App\Models\Department` model were retained. Live read-only inspection on 2026-08-10 found three active company-1 departments (`Warehose`, `Packaging`, `Coating`), all with `factory_id = null`; no live factories and no current Individual or Machine department references were found. Legacy company-level records are valid and were not remapped.

Department is referenced by Individual, Machine, RBAC organization access, audit metadata, and broader business schema. Process references remain separate.

## Implementation

`DepartmentMasterService` centralizes company/factory validation, location-scoped name uniqueness, persistence, status transitions, and before/after Audit Log entries. Active factory selectors are limited to the canonical company. The existing nullable `company_id`/`factory_id` organization-scope columns are reused, so no migration or live database change was needed.

The Admin CRUD is Bootstrap 3.3.7-compatible and provides search, location/status filters, edit, activate, and deactivate. The legacy destroy route is retained for route compatibility but always rejects deletion, protecting history. RBAC route mappings and Organization navigation are permission-aware; Frontend Users do not receive Department Master management.

## Scope exclusions

No Department Access pivot, department switching, Process Master, workflow redesign, Employee Master redesign, Warehouse redesign, or multi-company behavior was added.

## Verification

Focused contract tests cover the canonical table/model, company/factory scope, permissions, no premature user-access pivot, process boundary, and preserved legacy references. Final verification includes task-scoped lint/Pint, regression tests, `git diff --check`, and `php artisan quality:check`.
