# Task 1.4A — Company / Organization Scope Audit

Audit date: 2026-08-10  
Repository: `E:\projects\blackgrd`  
Database: `blackgrd` (read-only inspection)  
Expected baseline: `1d3b81d Convert operational modules to canonical statuses`

## 1. Baseline and method

`git status --short --branch` reported `## main...origin/main` with no working-tree
changes before this documentation task. `HEAD` is `1d3b81d`, aligned with the
requested baseline. The current architecture/audit documents, migrations,
models, controllers, routes, Blade/AJAX usage, tests and configuration were
searched for company, branch, factory, plant, unit, department, warehouse,
individual, audit-user and financial-year identifiers. The legacy ERP at
`E:\xampp\htdocs\erp` was searched read-only for business-flow comparison only.

No application code, migration, schema or data was changed by this audit.

## 2. Live entity inventory

Counts below came from read-only `DB::table(...)->count()` calls against the
`blackgrd` database on 2026-08-10.

| Entity | Table | Model | PK/current organization columns | Live rows | Current ownership |
| --- | --- | --- | --- | ---:| --- |
| Company | `companies` | `Company` | unsigned int `id`; no company owner column | 1 | singleton-style master; not tenant isolation |
| Branch | none | none | — | — | missing |
| Factory/Plant | none | none | — | — | missing |
| Department | `departments` | `Department` | bigint `id`; nullable `financial_year`, audit users | 3 | effectively global; individuals may point to it |
| Process | `process_items` | `ProcessItem` | integer PK/legacy process fields; no company | 8 | global/shared legacy master |
| Warehouse | `warehouses` | `Warehouse` | bigint `id`; nullable `process_type_id`, `financial_year`, audit users | 8 | stock location only; no company/factory |
| Warehouse compartment | `warehouse_compartments` | `WarehouseCompartment` | bigint `id`; required `warehouse_id`; audit/year fields | 225 | owned by warehouse; no company independently |
| Machine | `machines` | `Machine` | bigint `id`; `process_wise`, `financial_year` removed by migration; no factory/department | 10 | global machine master |
| Employee/customer/vendor/agent/transporter | `individuals` | `Individual` | bigint `id`; `type`, `process_type_id`, nullable `department_id` | 645 | mixed party/person table; no company |
| User | `users` | `User` | bigint `id`; `individual_id`, nullable `financial_year`, audit users | 2 | Admin/User account; no company access relation |
| Sale Order | `sale_orders` | `SaleOrder` | bigint `id`; `customer_id`, `financial_year` | 1 | customer-linked, no company/factory |
| Sale Order Item | `sale_order_items` | `SaleOrderItem` | bigint `id`; `sale_order_id`, item/process fields | 2 | derives from order; no scope |
| Work Order | `work_orders` | `WorkOrder` | bigint `id`; `sale_order_id`/item/process/machine/warehouse references, year | 9 | operational references only; no factory |
| Work Order Item | `work_order_items` | `WorkOrderItem` | bigint `id`; `work_order_id`, item/process fields, year | 9 | derives from work order |
| WPR | `work_process_requirements` | `WorkProcessRequirement` | bigint `id`; `work_order_id`, item, status, audit users, year | 12 | derives from work order; warehouse stock relation |
| Inspection | `work_inspections`, `work_inspection_details` | `WorkInspection`, `WorkInspectionDetail` | work order and `insp_work_warehouse_id`, year | 5 detail rows not separately counted | derives from work/warehouse |
| Gate Pass | `gate_passes` | `GatePass` | work order and warehouse/process references, year | 5 | operational only |
| Warehouse movements/stock | `warehouse_in_items`, `warehouse_out_items`, `warehouse_balance_items`, `warehouse_item_stocks` | corresponding Warehouse models | warehouse/compartment and transaction references, year | 17 / 10 / 27 / table present | warehouse-derived, no company |
| Purchase | `purchase_orders`, `purchase_order_items`, `purchases`, `purchase_items` | corresponding models | vendor/item/warehouse/year fields | 3 / 5; purchase tables present | vendor/warehouse-linked, no company |
| Job work | `stock_mill_dispatches`, `receive_stock_mill_dispatches` | corresponding models | vendor/warehouse/process/year fields | 0 / 0 | no live rows; no company |
| Notifications | `notifications` | `Notification` | recipient/user context only | 0 | no company scope |

The live database contains 65 tables. All listed tables are InnoDB except
`purchase_orders` and `purchase_order_items`, which are MyISAM. This existing
engine limitation is explicitly outside this task.

## 3. Current schema/code evidence

* `companies` has a broad legal/contact/tax/banking/print configuration, status,
  `created_by` and `modified_by`; it has no financial-year relation, branch,
  factory or user relation.
* `CompanyController` intentionally reads the first non-deleted company and
  prevents a second normal company setup. This is a current single-company UI,
  not a multi-tenant model.
* `departments` has only `department_name`, `financial_year`, status and audit
  columns. `individuals.department_id` is nullable and has no enforced FK.
* `warehouses` have `process_type_id`, `supervisor_id` and `financial_year` but
  no company/factory. Compartments correctly point to a warehouse and current
  critical FK work protects that parent relationship.
* `machines.process_wise` relates to `process_items`; there is no persisted
  factory, department or machine-capacity ownership.
* `users.individual_id` links a login to the mixed `individuals` table. There is
  no user-company pivot, current company session context, or organization
  access middleware. Admin/user authentication is guard-based only.
* Transactions use `financial_year` broadly, plus warehouse, process, machine,
  employee/customer/vendor and work-order references. No transaction has
  `company_id`, `branch_id` or `factory_id`.
* `WorkProcessRequirementController` has multiple `Company::find(1)` calls in
  print/report paths. This is a verified hard-coded tenant assumption.
* Warehouse AJAX and reports filter by submitted warehouse IDs but have no
  company authorization boundary. Several print templates contain fixed
  company text in addition to the company lookup.
* Existing status architecture is compatible with this design: record status is
  separate from operational status, and no new organization behavior was added.

## 4. Entity relationship and usage audit

| Entity | Relationships found | Missing scope |
| --- | --- | --- |
| Company | model/controller/UI only; State is used for address display | no tenant root behavior, no branches/factories, no user/company relation |
| Department | `Individual::department()`; admin CRUD | no company/factory ownership |
| Warehouse | movements, stocks and compartments reference it; admin CRUD; AJAX lists | no company/factory authorization; client warehouse IDs are not tenant-bound |
| Machine | WorkOrder `WorkMachine()` and ProcessItem relation; admin CRUD | no factory/department/capacity/shift ownership |
| Individual | customer/vendor/agent/employee/master roles; addresses; department | mixed global-looking party table and no company profile/membership |
| User | authentication and `individual_id` | no multi-company access, default/current context or effective access dates |
| Sale Order | customer, employee, agent and item children | no company/factory; customer/item selection can cross future tenants |
| Work Order/WPR/Inspection | parent work/order, process, machine, warehouse and stock relations | ownership is derived informally, not tenant-enforced |
| Purchase/Job Work | vendor, item, warehouse and process references | no company; MyISAM purchase tables prevent future FK assumptions |

## 5. Old ERP comparison

The old ERP contains a Company CRUD/controller and many Company/Company ID
references, but operational code commonly loads company `id = 1`. It also has
warehouse-centric filters and print/report paths, while factory/branch ownership
is not consistently represented in the searched application tables. These are
useful business-flow references only. The hard-coded singleton behavior is not
copied into the new architecture.

## 6. Gap and ambiguity register

1. There is no verified branch/factory/plant entity or live mapping. A default
   factory must not be invented during backfill.
2. The one live company is a strong candidate for the initial tenant root, but
   legal/entity and site assignment still require owner confirmation before
   creating factories.
3. `individuals` mixes customer, vendor, employee and other party types. Whether
   a party is shared across companies needs a separate identity/profile design.
4. Existing warehouse and process references may identify an operational site,
   but no deterministic site can be inferred from names alone.
5. `financial_year` is a period string repeated on many tables, not a company
   activation relation. Task 1.5 must replace the implicit current-year helper.
6. Fixed company names and Company ID 1 in prints/reports create leakage and
   incorrect output risks once multiple companies exist.
7. Purchase MyISAM tables, known `individuals.id` signedness staging, and
   unrelated FK gaps must remain deferred.

## 7. Required future changes

The architecture source of truth defines additive `branches`, `factories`, user
organization access, company/factory columns, transaction snapshots, indexes,
FK behavior, deterministic backfill and the Prompt 8 rollout. The first
implementation should prioritize company root/access, warehouses and top-level
orders before tightening child constraints. No migration is included here.

## 8. Verification and no-write confirmation

Read-only verification performed:

* repository baseline and clean status inspection;
* source/migration/model/controller/route/view/test searches;
* `DB::table(...)->count()` for the live entity inventory;
* `information_schema.TABLES` engine inspection;
* legacy ERP source search only.

No `INSERT`, `UPDATE`, `DELETE`, `ALTER`, `CREATE`, `DROP`, `TRUNCATE`, migration,
backfill or safety-guard bypass was performed. The required final commands are
recorded in the task handoff; `php artisan db:safety-check` is expected to return
`BLOCKED` on the live `blackgrd` connection.

See [company-organization-scope.md](../architecture/company-organization-scope.md)
for the complete future design, matrices and Prompt 8 implementation sequence.
