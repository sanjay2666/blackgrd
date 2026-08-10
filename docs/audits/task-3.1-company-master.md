# Task 3.1 — Company Master Audit

## Result

The existing organization foundation was reused. The ERP supports one
canonical company per installation.

## Architecture

- Canonical table/model: companies / App\Models\Company.
- Canonical resolution: Company::canonical() through
  CurrentOrganizationContext.
- Stable company IDs, user organization access, financial-year associations,
  number-series behavior, audit metadata, and historical references remain
  unchanged.
- No second Company table, multi-company selector, tenant switching, or
  company duplication was introduced.

## Profile and controls

The Admin Company Profile page edits existing identity, contact, registered
address, legal/tax, and branding fields. GSTIN and PAN are uppercase-normalized
and format-validated; email, website, PIN, state, and image uploads are
validated. Logo uploads are restricted to image types and 2 MB, use generated
storage names, preserve the current logo until replacement succeeds, and do
not log binary content.

companies.view and companies.update provide the RBAC boundary. There are no
create or delete actions. Direct requests are covered by the existing mapped
permission middleware, and Frontend Users do not receive Company Master
permissions. Meaningful profile changes are recorded by the centralized Audit
Logger with before/after fields.

Branch/Factory Master is not part of this task. Updating the current profile
does not rewrite old orders, work orders, purchase orders, gate passes,
warehouse history, audit history, or prior financial-year records.
