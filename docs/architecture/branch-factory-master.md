# Branch / Factory Master

This ERP is single-company. A Branch or Factory is an operational location of the one canonical `companies` row; it is not a tenant, company, authentication scope, or separate RBAC universe. Company selection is never exposed on these forms. `CurrentOrganizationContext` assigns the canonical `company_id`, and the model scope prevents cross-company access.

## Canonical structure

The existing `branches` and `factories` tables and `Branch` / `Factory` models are retained. A Branch represents a commercial, administrative, or head-office location (`kind` is `head_office`, `commercial`, or `other`). A Factory represents a production location and may optionally belong to a Branch through `branch_id`. Both have stable, company-unique codes (`branch_code` and `factory_code`). No live records were invented.

The master stores name, code, type/kind, optional parent branch, address/contact fields, optional location GSTIN, remarks, and canonical `Active` / `Inactive` / `Deleted` status. Location GSTIN is normalized uppercase and validated when supplied; blank means the company GSTIN applies. Company legal identity remains in Company Master.

Inactive locations remain available to historical references and are excluded from new parent-branch selection. Status changes are explicit. The admin workflow does not hard-delete locations, preserving departments, warehouses, machines, users, transactions, and audit history. Departments, warehouses, and machines already have the future-facing factory relationships; Task 3.3 will provide Department Master behavior without changing this master.

The shared `branches.*` permissions cover both pages: view, create, update, activate, and deactivate. Backend route middleware and company-scoped model/service checks enforce them; frontend users have no Admin master route. Meaningful create, profile/type/GST changes, and status transitions use the centralized AuditLogger with before/after values. AdminNavigation exposes the page under Organization only when `branches.view` is granted.
