# Company Master

This ERP supports one canonical company per installation. Company Master is a
profile/settings page; it is not a company list and has no Add, Delete, or
Switch Company operations.

The canonical record is the existing companies table and App\Models\Company
model. Company::canonical() selects the first active record by stable ID.
CurrentOrganizationContext is the application resolver and also verifies the
authenticated identity's existing organization access. Company IDs and user
organization mappings are preserved.

The profile supports the existing identity, contact, registered-address,
registration/tax, and branding fields that are appropriate to the current ERP.
GSTIN and PAN are normalized to uppercase and validated when supplied. PIN,
email, and website values are validated. Logos accept generated public-disk
filenames for JPG, PNG, and WEBP images up to 2 MB; a replacement is committed
before the previous managed logo is removed.

companies.view and companies.update are the existing canonical RBAC
permissions. Server-side route mapping protects both profile view and update.
Company Admin may edit the profile when assigned the update permission.
Company-level configuration remains outside that permission; Super Admin
reserved permissions and boundaries are unchanged.

Company profile updates do not rewrite historical transactional records,
financial years, audit history, or organization references. Branch and Factory
addresses remain separate operational masters and are outside Task 3.1.
