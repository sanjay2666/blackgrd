# Task 1.4B — Company / Organization Scope Implementation

Date: 2026-08-10  
Repository: `E:\projects\blackgrd`  
Live database: `blackgrd`  
Disposable verification database: `blackgrd_schema_testing`

## Result

The approved shared-database organization foundation is implemented. The
existing `companies` entity remains the tenant root. Existing rows were assigned
to the one active company deterministically; no branch or factory was invented
because the 1.4A audit found no evidence-based site mapping.

## Schema and access

Migration `2026_08_10_000001_create_organization_scope_tables` creates:

* `branches` and `factories`, with company ownership, status, indexes and root FKs;
* `user_organization_access`, with user/company access, optional branch/factory/
  department restrictions, default flag, effective dates and status;
* nullable/indexed `company_id` on company-owned masters and active aggregates;
* nullable/indexed `factory_id` on departments, warehouses and machines;
* nullable/indexed `department_id` on machines.

The company primary key is the existing unsigned integer, so new company keys
use `unsignedInteger`; branch/factory/user keys match their actual bigint parent
types. Purchase MyISAM engines and the known `individuals.id` signedness issue
were not changed. Child ownership is derived from parent data where approved;
tenant FKs on staged legacy columns remain deferred until all future rows are
verified, while organization root/access FKs are installed.

The migration backfilled all existing scoped rows to the single active company
and created two active default user access mappings. Branch/factory counts are
zero by design. Existing primary IDs, row counts and operational data were
preserved.

## Runtime enforcement

`CurrentOrganizationContext` resolves authenticated identity to an allowed,
active company and optional factory using validated session state. The
`ResolveOrganizationContext` middleware is applied to authenticated AJAX,
frontend and admin route groups; missing access fails closed once the access
table exists. `POST /organization/switch` validates the requested company and
factory against the access mapping before changing session context.

The reusable `BelongsToCompany` model concern scopes active company-owned master
and aggregate models and assigns `company_id` from server context on create.
Active print/report paths no longer use `Company::find(1)`. The existing
operational status fields and printing workflow behavior were not redesigned.

## Verification and live apply

Disposable verification completed on `blackgrd_schema_testing`:

1. fresh migration;
2. full migration rollback of the organization migration;
3. re-migration;
4. read-only schema confirmation.

Live safety preflight correctly blocked destructive operations on `blackgrd`
until the application was placed in maintenance mode, writes were confirmed
stopped, and all three backup checksums matched. The reviewed command
`db:apply-reviewed-organization-scope` then applied the exact hash-pinned
migration and confirmed preservation of existing row counts. Maintenance mode
was turned off afterward.

Backups (not committed):

| Kind | Path | Bytes | SHA-256 |
| --- | --- | ---: | --- |
| Full | `E:\backups\blackgrd\task-1.4b-20260810_123000\full-blackgrd.sql` | 622651 | `f421da21164610a4e4e504b0421d60edb08815cb1c533f757632da84aa4dff8c` |
| Affected tables | `E:\backups\blackgrd\task-1.4b-20260810_123000\task-1.4b-affected-tables.sql` | 374669 | `5ce50ec9352846f8bd1f49c999d5f482182abdfcdd4e9411e996b447151d1a52` |
| Migrations | `E:\backups\blackgrd\task-1.4b-20260810_123000\migrations-table.sql` | 6514 | `6fbd9f0c275c8f66f0d23f70277a25e91e3dbf69d67aa8c8ac5a4a2bf1e69746` |

Post-apply read-only checks confirmed database `blackgrd`, migration ledger
entry present, 1 company, 0 branches, 0 factories, 2 access mappings, and no
unassigned company values in the verified master/transaction columns.

## Tests and remaining dependencies

Direct PHPUnit execution with `APP_ENV=testing` passed: 107 tests, 789
assertions, 11 MySQL-specific skips. The Laravel `artisan test` wrapper still
inherits the repository `.env` local environment unless invoked with the test
environment explicitly. Added organization contract tests cover schema,
context, switch route and removal of active Company-1 lookups.

Remaining work belongs to later tasks: explicit branch/factory business mapping
and CRUD, richer party profiles, RBAC, financial year/number series, workflow
snapshots, inventory ledger, queue context payloads, and complete endpoint-by-
endpoint ownership validation for legacy tables that only derive ownership.
