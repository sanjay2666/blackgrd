# Company and Organization Scope Architecture

Status: design source of truth and implemented foundation for Task 1.4B (2026-08-10)

Task 1.4B implementation note: the additive organization migration is applied to
the live development database through the hash-pinned reviewed command. Branch
and factory rows remain empty because the 1.4A audit found no deterministic site
mapping; assigning a factory requires an explicit business decision. The current
company/access backfill is deterministic and preserves existing business IDs,
quantities and statuses.

## 1. Executive decision

The future ERP uses a shared logical tenant model: every deployable customer is a
Company tenant, and tenant-owned records carry or safely derive `company_id`.
The first deployment may still contain one company, but the schema and service
boundaries must be multi-company safe from the first scope migration.

The maintainable hierarchy is:

```text
Company
├── Branch (commercial/administrative location, optional)
│   └── Factory/Plant (operational site, optional under a branch)
├── Company-level departments, warehouses and services
└── Factory-level departments, warehouses, machines, employees and operations
```

Branch and Factory are separate concepts. A factory is not merely a branch type:
it has production capacity, machines and operating calendars. A small customer
may use a single branch and factory with the same display name. Head Office is a
company-level organizational location, represented by a Branch with
`kind = head_office` (or, for the first rollout, a nullable location on the
company); it is not a fake factory.

`OrganizationUnit` is deliberately deferred. Explicit `branches`, `factories`,
`departments`, `warehouses` and `machines` keep Laravel relationships and report
filters understandable for this textile ERP.

## 2. Scope vocabulary

| Concept | Meaning and ownership |
| --- | --- |
| Company | Legal tenant, tax identity, accounting boundary and root of all tenant data. |
| Branch | Optional commercial/administrative location of one company; may be Head Office. |
| Factory/Plant | Physical production site of one company; may be linked to a branch, but is operationally distinct. |
| Department | Functional team. It belongs to the company and optionally to a factory; company-level departments support central sales, finance and procurement. |
| Warehouse | Stock location owned by the company and optionally assigned to a factory. A central/Head Office warehouse has no factory assignment. |
| Machine | Production asset owned by the company and assigned to one factory and, where appropriate, one department. |
| Employee | Human/resource master, company-owned; may have memberships at several factories/departments. |
| User | Authentication identity. It may represent an employee but is separate so service accounts and external users remain possible. |

One company may have many branches and factories; one branch may have many
factories; one factory may have many departments, warehouses and machines.
Cross-factory stock movement is a controlled inter-factory transfer, never a
direct edit of warehouse ownership.

## 3. Tenancy decision

Option A (database per customer) provides strong isolation and simple customer
restore, but makes upgrades, consolidated reporting and shared operational
maintenance expensive. Option B (shared database with `company_id`) supports
sellable SaaS, efficient upgrades and authorized owner reporting, but makes every
query, import, queue job and export an isolation boundary. Option C is a future
hosting choice, not a second application model.

Adopt Option B now, with a deployment-per-customer mode allowed later by keeping
tenant context in a service rather than hard-coding a database name. Database
constraints, tenant-aware services, request authorization, indexes and automated
cross-tenant tests are mandatory. A shared master is never a reason to omit
ownership when the business value can differ between companies.

## 4. Master ownership matrix

| Master | Classification | Rule |
| --- | --- | --- |
| Company | Global system root | Tenant record; unique code within deployment. |
| Branch/Factory | Company-specific | Belongs to one company; factory may reference branch. |
| Department | Shared with company override | Company-level or factory-level; no global department names. |
| Process | Shared with company override | A small canonical global process catalogue may be seeded; company activation/configuration is authoritative. |
| Machine / capacity / shift | Factory-specific | Machine and capacity belong to a factory; shift may be factory-specific with company defaults. |
| Employee | Company-specific | Employee can have multiple factory/department memberships with effective dates. |
| Customer / Vendor / Transporter / Agent | Company-specific relationship master | Keep an optional future global party identity separate from company party profiles; transactions use the company profile. |
| Item Type / Item / Yarn / Fabric Quality | Company-specific or shared with company override | No unrestricted global item master; a global template may be copied/overridden into a company catalogue. |
| Colour / Shade / Chemical / Printing Design / Coating Type | Company-specific or shared override | Company owns operational meaning, codes and active status. |
| Warehouse / Compartment | Company-specific; warehouse optionally factory-specific | Compartment ownership is derived from its warehouse. |
| Unit / GST / HSN | Global system master with company applicability | Tax rates and HSN need effective dates and company applicability; never assume one company's tax configuration applies to another. |
| Fault/Reject Reasons | Company-specific, optionally process-scoped | Reason codes and translations may differ by process. |
| Workflow Definition | Company-specific, optionally factory-specific | Versioned definition; factory may select an enabled company version. |

Global system masters are immutable/reference data only. All company overrides
must be explicit and unique by `(company_id, code)`; transaction rows keep a
snapshot of user-visible names, tax values and workflow version where later
master edits would change historical meaning.

## 5. Transaction ownership matrix

`company_id` is stored on each top-level aggregate for security and indexing.
Child rows normally derive company from their parent; a direct child column is
added only when it is an independent query boundary or needed for a composite
foreign-key check. `factory_id`, `warehouse_id` and `department_id` are required
only where the business event has that ownership.

| Aggregate/module | Company | Branch/factory | Warehouse | Department | Snapshot |
| --- | --- | --- | --- | --- | --- |
| Enquiry / Quotation | required | optional | no | optional | customer and commercial terms |
| Sale Order / Item | required on order | order default optional; item assignment may differ | no | optional | customer, item, tax and workflow route per item |
| Work Order / Item | derived/stored on order | required factory | input/output warehouse through movements | required process department | source order, item and workflow-version snapshot |
| WPR | derived from work order | derived | required for material allocation | derived/required process department | requested item/unit/quantity |
| Inspection | derived from work order | derived | inspection warehouse where applicable | process department | inspected specifications/result |
| Gate Pass | required on aggregate | factory or external job-work site | source/destination warehouse as applicable | optional | party, quantities and reason |
| Warehouse In/Out/Balance/Stock | required | derived from warehouse; transfer has both source and destination | required | optional | item, unit, lot, valuation and posting context |
| Purchase Request/Order | required | receiving factory optional | receiving warehouse optional | requesting department optional | vendor, item, tax and price |
| Job Work Dispatch/Receive | required | internal factory owner plus external vendor | dispatch/receive warehouse | process department | vendor, route, item and rate |
| Packaging / Dispatch / Sales Challan | required | dispatch factory/branch | source warehouse | optional | customer, item, tax and quantities |
| Notifications | company or system | optional | no | optional | recipient/context identifiers |

Sale Order Item may be allocated to multiple factories by creating explicit
factory-specific Work Orders or allocations. The Sale Order remains one company
aggregate; no child may reference another company's order, item or party.

## 6. User and organization access

User and Employee remain separate. `users.individual_id` may link a login to an
employee, but employee records can exist without logins and one login is not
required for every employee. Use a richer access table rather than
`users.company_id`:

```text
user_organization_access
  id, user_id, company_id, branch_id nullable, factory_id nullable,
  department_id nullable, is_default, starts_at, ends_at, status,
  created_by, created_at, updated_at
```

The database must enforce that branch/factory/department belong to the selected
company. One user may access multiple companies, branches and factories. The
current company and current factory are session/request context, not permission
grants. Roles and permissions are a later RBAC task and must remain conceptually
separate from this access mapping.

Roles are intentionally described only: Super Admin may cross tenants through an
explicit audited reporting path; Company Admin is restricted to assigned
company data; ordinary employees are restricted to assigned organization units.
Super Admin bypasses must never be an accidental `withoutGlobalScopes()` call.

## 7. Request context and query isolation

Use a server-resolved `CurrentOrganizationContext` service, with middleware named
`ResolveOrganizationContext` and `EnsureOrganizationAccess` when implementation
starts. The initial context comes from the authenticated user's default company;
an explicit company/factory switch is accepted from a signed/validated URL or
session value and is checked against `user_organization_access`. An arbitrary
client `company_id` is never trusted. Subdomains may select a tenant later, but
still require the same authorization check.

Use explicit tenant scopes/services as the primary enforcement mechanism:

* tenant-owned repositories/services require a `CompanyContext`;
* create operations assign company from context, never request input;
* controllers, AJAX, exports and reports use the same context service;
* background jobs serialize company, factory and financial-year context;
* console and migrations must opt into an explicit company or an authorized
  all-company operation;
* a narrow model scope may be added only for high-risk tenant models after query
  auditing, with an explicit `forAllCompanies()` path for Super Admin reports.

This hybrid is safer than blind global scopes for aggregate reports and console
work, while explicit service boundaries reduce accidental unscoped queries.
Every tenant-owned foreign reference must be validated in application code and,
where practical, with composite database constraints.

## 8. Cross-company protection rules

On create, `company_id = CurrentOrganizationContext.companyId`. On update, the
loaded record must belong to the context; changing ownership is a separate,
audited transfer operation. Route model binding and AJAX/autocomplete queries
must filter by the current company before returning a record. Export and print
queries must use the same filter.

Required checks include:

* Sale Order customer/item belongs to the same company.
* Work Order belongs to its Sale Order company; its factory belongs to that company.
* WPR belongs to its Work Order; its item and warehouse belong to the company.
* Warehouse movements reference a company-owned warehouse and compartment whose
  warehouse is the selected warehouse.
* Purchase Order vendor, Job Work vendor and all selected items belong to the company.
* Machine belongs to the selected factory/department; employee membership is
  effective for the operation date.

Where ownership is safely derivable from a parent, do not duplicate mutable
columns on every child. Where fast isolation queries justify a child
`company_id`, keep it immutable and enforce equality with the parent through a
composite foreign key or service invariant.

## 9. Warehouse and inventory scope

Warehouse belongs to Company and optionally Factory. A Head Office or central
warehouse has a null factory. A compartment belongs only to its warehouse;
there is no independent compartment company choice. Inter-factory movement is a
transfer document with source and destination warehouse/company context. A
cross-company transfer is prohibited unless a later inter-company trade process
explicitly models it.

Existing quantities are not changed in this design task. Future immutable ledger
entries should capture company, source/destination factory, warehouse,
compartment, item, lot, unit, transaction reference and posting time. Balance
views aggregate the ledger within the requested company and warehouse scope;
current stock tables remain compatibility consumers until a dedicated ledger task.

## 10. Production and workflow scope

The Sale Order is company-owned. Factory assignment occurs when the item is
planned/accepted for production, not when the customer order is first entered.
One Sale Order Item can split across factories through separate work-order
allocations. A Work Order is always one factory's responsibility, even when job
work is performed by an external vendor; the internal factory remains the owner
of dispatch, receipt, inspection and stock accountability.

Process definitions are company-configurable. Workflow definitions are
company-specific, versioned, and optionally factory-enabled. A workflow snapshot
is copied onto each Sale Order Item/work-order planning record with company,
factory (when selected), version and ordered steps.

Printing is selected independently for each Fabric/Sale Order Item. Valid routes
include `Dyeing -> Printing -> Coating` and `Dyeing -> Coating -> Printing`.
Coating Master must not determine printing position. Different items on the same
Sale Order may use different snapshots.

## 11. Financial year and number series

Financial Year is a company-owned accounting-period master. Branch/factory may
have an operational period within the company's active year, not a separate
accounting year. Resolution is `CurrentOrganizationContext -> company active
financial year`; historical rows retain an immutable `financial_year_id` and
legacy string snapshot and must not change when the current year changes. Task
1.5 implements this compatibility-first foundation with canonical four-digit
codes such as `2627` and display labels such as `2026-27`.

Number series are company + document type + financial year scoped, with optional
branch/factory partition only where the business requires local numbering. The
recommended uniqueness key is `(company_id, document_type, financial_year_id,
branch_id nullable, factory_id nullable, sequence_number)`. Generate numbers in a
transaction with a row lock; do not use the current `invoice_prefix` fields as a
complete numbering design.

## 12. Reporting

Default reports filter Company, then optionally Branch, Factory, Department,
Process and Machine. Warehouse reports filter Company and Warehouse, deriving
factory from the warehouse. Aggregation may roll up factory to company, but may
not combine companies unless the caller is an authorized Super Admin/owner and
the report explicitly says it is cross-company. Report exports, dashboard cards,
AJAX lists and print templates follow identical boundaries.

## 13. Proposed schema and constraints (future, no migration in Task 1.4A)

| Table/column | Type/nullability/index | FK and behavior | Backfill/reason |
| --- | --- | --- | --- |
| `companies.company_code` | `varchar(30)`, future NOT NULL, unique | root; restrict delete | validate existing code/name before tightening |
| `branches` | bigint PK; `company_id` unsigned bigint NOT NULL, indexed; `kind`, code, name, status | company restrict; branch references from children restrict | create only after a reviewed mapping decision |
| `factories` | bigint PK; `company_id` NOT NULL indexed; `branch_id` nullable indexed; code/name/status | company restrict; branch restrict | create one explicit factory only when evidence/business owner confirms it |
| `departments.company_id` | unsigned bigint nullable initially, indexed, later NOT NULL | companies restrict | derive only from verified department/employee/factory evidence |
| `departments.factory_id` | unsigned bigint nullable indexed | factories restrict | null for central departments |
| `warehouses.company_id` | unsigned bigint nullable initially, indexed, later NOT NULL | companies restrict | derive from one current company only after validation |
| `warehouses.factory_id` | unsigned bigint nullable indexed | factories restrict | null for central warehouse |
| `machines.company_id/factory_id/department_id` | unsigned bigint; company/factory required after backfill; indexed | restrict; composite ownership check | derive process/department only where deterministic |
| tenant transaction `company_id` | unsigned bigint nullable during rollout, indexed, later NOT NULL | companies restrict | derive through parent/customer/warehouse; unresolved rows remain quarantine |
| tenant transaction `factory_id` | unsigned bigint nullable/indexed | factories restrict | assign only from planning/warehouse evidence |
| `user_organization_access` | bigint PK; user/company required and indexed; optional branch/factory/department indexes | all restrict; effective-date check | no automatic access grants; explicit admin mapping |
| company master ownership keys | `company_id` + business code unique | companies restrict | copy global templates only with explicit company ownership |
| historical snapshots | version/name/tax/route fields non-null where required | no mutable master FK for displayed history | populate at transaction creation; old rows need documented reconstruction |

Use unsigned types matching existing parent IDs. Preserve the known deferred
`individuals.id` signedness issue and do not combine it with this task. Do not
convert MyISAM purchase tables or add unrelated foreign keys here.

## 14. Deterministic backfill and rollout for Prompt 8

1. Freeze the verified baseline and inventory every table/column/query.
2. Create the organization root and access schema in a reviewed additive migration.
3. Add nullable company/factory columns and indexes to high-value aggregates.
4. Build a mapping report from current Company, Warehouse, Department, employee,
   customer, order and stock evidence; do not guess unresolved ownership.
5. Obtain an explicit business decision for ambiguous rows. Create a default
   factory only if the owner confirms the actual site; preserve legacy IDs.
6. Backfill in dependency order: company root, branches/factories, departments,
   warehouses/compartments, people/masters, orders, production, inventory,
   purchases and reports. Keep quantities and primary keys unchanged.
7. Verify row counts, null counts, orphan counts, parent-company equality,
   stock totals and deterministic before/after ID hashes.
8. Add foreign keys/composite checks and tighten NOT NULL only for verified sets.
9. Introduce context resolution and authorization, then switch reads.
10. Switch creates/updates to trusted context; reject client ownership fields.
11. Add UI company/factory context and explicit admin switching.
12. Add tenant-aware queue payloads, exports, print pages and regression tests.
13. Remove hard-coded company IDs and legacy fallback only after usage audit.

Rollback is additive until the read/write cutover. Never delete or rewrite
existing quantities as part of scope backfill.

## 15. Main risks and mitigations

| Risk | Mitigation |
| --- | --- |
| Cross-company leakage in an unscoped query/AJAX/report | context-required services, code review rule, tenant tests, filtered route binding and export tests |
| Wrong session/company context | allow-list validation, signed context, re-check on every request and visible context indicator |
| Hard-coded `Company::find(1)`/printed company identity | inventory and replace with context-aware company relation before cutover |
| Shared master conflicts | company profiles/overrides and transaction snapshots |
| Ambiguous backfill | quarantine and business decision; never infer from names alone |
| Factory transfers | explicit transfer aggregate, source/destination validation and immutable ledger later |
| Queue jobs lose tenant context | serialize company/factory/year and fail closed if absent |
| Super Admin bypass | explicit audited all-company query path, no blanket scope bypass |
| Performance/index cost | indexes on company plus common factory/warehouse/year predicates; measure reports |
| Existing purchase MyISAM/FK limitations | leave purchase engine conversion to its dedicated task |

## 16. Explicitly deferred

No migration, CRUD, switcher, middleware, global scope, tenant service, user pivot,
RBAC, financial-year implementation, number-series implementation, audit log,
workflow engine/tables, inventory ledger, packaging, dispatch, lab-test work,
purchase engine conversion, `individuals.id` conversion or unrelated FK work is
part of Task 1.4A.
