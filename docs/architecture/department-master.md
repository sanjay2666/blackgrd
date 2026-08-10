# Department Master

## Canonical record

`departments` and `App\Models\Department` are the canonical Department Master. The existing table is reused; no duplicate master or data remapping was introduced. Current records may remain company-level because `factory_id` is nullable by design.

The hierarchy is:

`companies` → `factories` (optionally under a `branch`) → `departments`

Departments belong to the canonical company through `BelongsToCompany`. A factory association is optional, and when present it must be an active factory in the current company. The branch is reached through the selected factory's existing `branch` relationship; no polymorphic location model is introduced.

## Fields and lifecycle

The master supports the established `department_name`, `financial_year`, `company_id`, `factory_id`, and canonical `status` fields. No code or description field was invented because neither exists in the current schema or has a verified business use.

Names are unique among non-deleted departments within the company and selected location. Company-level and factory-level records are therefore distinct scopes. Existing IDs and legacy company-level records are preserved.

Admin users can list, search, filter, create, edit, activate, and deactivate departments. Deactivation preserves all historical references. The legacy destroy route remains only as a guarded compatibility endpoint and always rejects deletion; referenced departments are never hard-deleted.

## Boundary and future access

Department is an organizational/operational unit. It does not own process workflow, process names, or `process_type_id`; Process Master is a separate task. Existing User/Employee and machine references continue to use `department_id` without reassignment. The existing `user_organization_access.department_id` field remains organization context metadata; user-wise Department Access, a permission pivot, switching, and session department context are not implemented by Task 3.3.

RBAC uses `departments.view`, `departments.create`, `departments.update`, `departments.activate`, and `departments.deactivate`. Route middleware and server-side factory validation enforce authorization and company scope. Navigation exposes one permission-aware Organization link.

Department create/update/status mutations are recorded through the centralized Audit Log with before/after values. No reads are audited.
