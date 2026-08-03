# Task 1.2A — Schema Contract Audit

Date: 2026-08-03  
Project: `E:\projects\blackgrd`  
Database: current `blackgrd` MySQL database  
Mode: read-only; no migration was run and no database object/data was changed.

## Executive result

The repository cannot currently reproduce the current database from a fresh database.

- Repository migration files: **64**
- Applied rows in `migrations`: **65**, across batches 1–26
- Actual base tables: **65**, containing **1,338 columns**
- Unique tables declared by repository `Schema::create`: **65**
- Repository-created/current overlap: **63 tables**
- Actual-only tables: **`agents`, `migrations`**
- Migration-only tables: **`cache`, `cache_locks`**
- A fresh `php artisan migrate` would create the framework `migrations` ledger plus 65 repository-declared tables: **66 tables**, but not the same set or schema as the current 65.

Legend used below:

- `u` = unsigned; otherwise the integer is signed.
- `AID` = `enum('Active','Inactive','Deleted')`.
- `PAR` = `enum('pending','accepted','rejected')`.
- `ASF` = `enum('attempt','success','failed')`.
- Audit fields are shown as `created-side / updated-side`.
- `OK` means table/column names and the requested contract fields match the effective repository migration contract. It is not a claim that every legacy ID is a valid foreign key.

## Complete table schema matrix

| Table | Migration available | In DB | Primary key | Foreign keys | `status` type | Financial year | Created / updated fields | Migration ↔ actual mismatch |
|---|---|---|---|---|---|---|---|---|
| agents | **No** | Yes | `id int u` | — | `AID` | — | `created_by,created / modified_by,modified` | **FAIL:** applied migration file missing; actual table has 9 columns |
| all_pages | Yes | Yes | `id int u` | — | `tinyint(1) default 1` | — | — / — | OK; numeric status is intentional in migration |
| cache | Yes | **No** | migration expects `key string` | — | — | — | — / — | **FAIL:** migration is marked applied but table is absent |
| cache_locks | Yes | **No** | migration expects `key string` | — | — | — | — / — | **FAIL:** migration is marked applied but table is absent |
| colours | Yes | Yes | `id bigint u` | — | `AID` | — | `created / modified` | **FAIL:** actual-only `individual_id int NULL` plus index |
| companies | Yes | Yes | `id int u` | — | `AID` | — | `created_by,created_at / modified_by,updated_at` | OK |
| cotings | Yes | Yes | `id bigint u` | — | `AID` | — | `created / modified` | OK |
| couriers | Yes | Yes | `id int u` | — | `AID` | — | `created_by,created / updated_by,modified` | OK |
| departments | Yes | Yes | `id bigint u` | — | `AID` | `char(4)` | `created_by,created_at / modified_by,updated_at` | OK |
| department_returns | Yes | Yes | `id bigint u` | — | `PAR` | `char(4)` | `created_by,created_at / modified_by,modified_at` | OK |
| department_return_requests | Yes | Yes | `id bigint u` | — | `PAR` | `char(4)` | `created_by,created_at / modified_by,modified_at` | OK |
| fabric_fault_reasons | Yes | Yes | `id int u` | — | `AID` | `char(4)` | `created_by,created_at / modified_by,modified_at` | OK |
| failed_jobs | Yes | Yes | `id bigint u` | — | — | — | — / — | OK |
| gate_passes | Yes | Yes | `id int u` | — | `AID` | `char(4)` | `created_by,created_at / modified_by,modified_at` | OK |
| gate_pass_print_logs | Yes | Yes | `id int u` | — | `AID` | `char(4)` | `created_by,created_at / modified_by,modified_at` | OK |
| greige_receive_stock_item_from_job_works | Yes | Yes | `id int u` | — | `AID` | `char(4)` | `created_by,created_at / modified_by,modified_at` | OK |
| gst_rates | Yes | Yes | `gst_rate_id int u` | — | `AID` | — | `created / modified` | OK; nonstandard PK name |
| individuals | Yes | Yes | `id bigint` | — | `AID` | — | `created_at,created_by / modified_at,modified_by` | **FAIL:** migration `$table->id()` expects unsigned bigint; actual PK is signed bigint |
| individual_address | Yes | Yes | `ind_add_id int u` | — | `AID` | — | `created,created_by / modified_at,modified_by` | OK; nonstandard PK name |
| items | Yes | Yes | `item_id int u` | — | `AID` | — | `created,created_by / modified,modified_by` | OK; nonstandard PK name |
| item_type | Yes | Yes | `item_type_id int u` | — | `AID` | — | `created / modified` | OK; nonstandard PK name |
| item_yarn_requirements | Yes | Yes | `id int u` | — | `AID` | `char(4)` | `created_by,created_at / modified_by,modified_at` | OK |
| jobs | Yes | Yes | `id bigint u` | — | — | — | `created_at / —` | OK |
| job_batches | Yes | Yes | `id string` | — | — | — | `created_at / —` | OK; string PK |
| login_attempts | Yes | Yes | `id bigint u` | — | `ASF` | — | `created_at / updated_at` | OK; workflow status enum |
| login_otps | Yes | Yes | `id bigint u` | `user_id → users.id` | — | — | `created_at / updated_at` | OK; only enforced FK in database |
| machines | Yes | Yes | `id bigint u` | — | `AID` | — | `created,created_by,created_at / modified,modified_by,updated_at` | OK; later migration removes `financial_year` |
| migrations | Framework-managed | Yes | `id int u` | — | — | — | — / — | Expected framework ledger; no repository `Schema::create` |
| notifications | Yes | Yes | `id bigint u` | — | `AID` | — | `created / modified` | OK |
| office_ips | Yes | Yes | `id bigint u` | — | — | — | `created_at / updated_at` | OK |
| packaging_types | Yes | Yes | `id int u` | — | `AID` | — | `created / modified` | OK |
| password_reset_tokens | Yes | Yes | `email string` | — | — | — | `created_at / —` | OK; email PK |
| process_items | Yes | Yes | `id bigint u` | — | `AID` | — | `created / modified` | OK |
| process_requirements | Yes | Yes | `id int u` | — | `AID` | — | — / — | OK |
| purchases | Yes | Yes | `id int u` | — | `AID` | `char(4)` | `created_by,created_at / modified_by,updated_at` | OK |
| purchase_items | Yes | Yes | `id int u` | — | `AID` | `char(4)` | `created_by,created_at / modified_by,updated_at` | OK |
| purchase_orders | Yes | Yes | `id int u` | — | `AID` | `char(4)` | `created_by,created_at / modified_by,modified_at` | OK |
| purchase_order_items | Yes | Yes | `id int u` | — | `AID` | `char(4)` | `created_by,created_at / modified_by,modified_at` | OK |
| reasons | Yes | Yes | `id int u` | — | `AID` | — | `created_by,created_at / modified_by,modified_at` | OK |
| receive_stock_mill_dispatches | Yes | Yes | `id int u` | — | `AID` | `char(4)` | `created_by,created_at / modified_by,modified_at` | OK |
| receive_stock_mill_dispatch_items | Yes | Yes | `id int u` | — | `AID` | `char(4)` | `created_by,created_at / modified_by,modified_at` | OK |
| sale_orders | Yes | Yes | `id int u` | — | `AID` | `char(4)` | `created_by,created_at / modified_by,modified_at` | OK |
| sale_order_items | Yes | Yes | `id int u` | — | `AID` | `char(4)` | `created_by,created_at / modified_by,modified_at` | OK |
| sessions | Yes | Yes | `id string` | — | — | — | — / — | OK; string PK |
| states | **Yes ×2** | Yes | `id int u` | — | `AID` | — | `created / modified` | **WARN:** duplicate create migrations; current/fresh schema comes from first |
| stock_mill_dispatches | Yes | Yes | `id bigint u` | — | `AID` | `char(4)` | `created_by,created_at / modified_by,modified_at` | OK |
| stock_mill_dispatch_items | Yes | Yes | `id int u` | — | `AID` | `char(4)` | `created_by,created_at / modified_by,modified_at` | OK |
| stock_mill_returns | Yes | Yes | `id bigint u` | — | `AID` | `char(4)` | `created_by,created_at / modified_by,modified_at` | OK |
| stock_mill_return_items | Yes | Yes | `id int u` | — | `AID` | `char(4)` | `created_by,created_at / modified_by,modified_at` | OK |
| unit_type | Yes | Yes | `unit_type_id int u` | — | `AID` | — | `created / modified` | OK; nonstandard PK name |
| users | Yes | Yes | `id bigint u` | — | `AID` | `char(4)` | `created_by,created_at / modified_by,updated_at` | OK |
| user_activity_logs | Yes | Yes | `id bigint u` | — | — | — | `created_at / updated_at` | OK |
| user_web_pages | Yes | Yes | `id bigint u` | — | `AID` | — | `created / modified` | OK |
| warehouses | Yes | Yes | `id bigint u` | — | `AID` | `char(4)` | `created_by,created_at / modified_by,updated_at` | OK |
| warehouse_balance_items | Yes | Yes | `id int` | — | `AID` | `char(4)` | `created_by,created_at / modified_by,updated_at` | OK; signed legacy PK |
| warehouse_compartments | Yes | Yes | `id bigint u` | — | `AID` | `char(4)` | `created_by,created_at / modified_by,updated_at` | OK |
| warehouse_in_items | Yes | Yes | `id bigint` | — | `AID` | `char(4)` | `created_by,created_at / modified_by,updated_at` | OK; signed legacy PK |
| warehouse_item_stocks | Yes | Yes | `id bigint` | — | `AID` | `char(4)` | `created_by,created_at / modified_by,updated_at` | OK; signed legacy PK |
| warehouse_item_stock_files | Yes | Yes | `id int u` | — | `AID` | `char(4)` | `created_by,created_at / modified_by,updated_at` | OK |
| warehouse_out_items | Yes | Yes | `id int u` | — | `AID` | `char(4)` | `created_by,created_at / modified_by,updated_at` | OK |
| work_inspections | Yes | Yes | `id int u` | — | `AID` | `char(4)` | `created_by,created_at / modified_by,updated_at` | OK |
| work_inspection_details | Yes | Yes | `id int u` | — | `AID` | `char(4)` | `created_by,created_at / modified_by,updated_at` | OK |
| work_orders | Yes | Yes | `id int u` | — | `AID` | `char(4)` | `created_at,created_by / modified_at,modified_by` | OK |
| work_order_items | Yes | Yes | `id int u` | — | `AID` | `char(4)` | `created_by,created_at / modified_by,updated_at` | OK |
| work_process_received_items | Yes | Yes | `id int u` | — | `AID` | `char(4)` | `created_at,created_by / modified_at,modified_by` | OK |
| work_process_requirements | Yes | Yes | `id int u` | — | `AID` | `char(4)` | `created_by,created_at / modified_by,modified_at` | **FAIL:** migration-only `warehouse_balance_item_id int NULL` |
| work_purchase_requirements | Yes | Yes | `id int u` | — | `AID` | `char(4)` | `created_by,created_at / modified_by,updated_at` | OK |

## Exact mismatch findings

### 1. Missing applied `agents` migration

- Ledger row: `2026_07_17_000006_create_agents_table`, batch 10.
- Repository file `database/migrations/2026_07_17_000006_create_agents_table.php` is absent.
- Actual `agents` table exists with: `id`, `individual_id`, `agent_from`, `agent_to`, `created_by`, `modified_by`, `created`, `modified`, `status`.
- Current row count: 0.
- Result: migration history is not source-controlled and fresh databases cannot create `agents`.

### 2. Duplicate `states` migrations

- `database/migrations/2026_07_17_000002_create_states_table.php` creates the actual contract: `id int u`, `name varchar(30)`, `country_id`, `created`, `modified`, `status`.
- `database/migrations/2026_07_17_122153_create_states_table.php` is also applied. Its `up()` is skipped because it checks `Schema::hasTable`, but its `down()` unconditionally drops `states`.
- Fresh migration does not fail because the second `up()` is guarded, but rolling back the second historical migration can drop the table created by the first migration.

### 3. Table-set drift

- Actual-only business table: `agents`.
- Actual-only infrastructure table: `migrations` (normal framework behavior).
- Migration-only tables: `cache`, `cache_locks`.
- `0001_01_01_000001_create_cache_table` is recorded as applied, but both tables are absent.
- Current `.env` uses `CACHE_STORE=file`, while `.env.example` uses `CACHE_STORE=database`; therefore the missing cache tables are currently dormant but will break a database-cache deployment.

### 4. Shared-table column drift

Only two shared tables have column-name drift after applying effective `up()` order:

1. `colours.individual_id int NULL` exists only in the current database and has index `colours_individual_id_index`. All 3 current `colours` rows contain `NULL`; no direct Colour model/controller usage of this field was found.
2. `work_process_requirements.warehouse_balance_item_id int NULL` is declared at `database/migrations/2026_07_19_000013_create_work_process_requirements_table.php:14` but is absent from the current database. There are 12 current work-process-requirement rows. Controllers read this field and `WorkProcessRequirement` currently masks the absence by returning `null` from an accessor.

### 5. Foreign-key deficit

- Actual database foreign keys: **1** — `login_otps.user_id → users.id` (`fk_loginotps_user`).
- Repository migration foreign-key declarations: the same single FK.
- Non-primary columns ending in `_id` without an enforced FK: **253**.
- This 253 count is a candidate inventory, not a recommendation to add 253 constraints blindly; several columns are legacy aliases, actor IDs, or refer to polymorphic/process-specific records.
- High-value relationships such as Sale Order → Customer, Sale Order Item → Sale Order, Work Order Item → Work Order, warehouse/compartment links, purchase/vendor links and users/individuals are not enforced.
- Adding FKs immediately would fail in several areas because parent/child types are inconsistent: for example `individuals.id` is signed bigint while many individual references are signed int; Laravel `$table->id()` expects unsigned bigint.

### 6. Status contract

- Tables with a `status` column: **56**.
- Enum status columns: **55**.
  - 52 use `Active/Inactive/Deleted`.
  - 2 department-return tables use `pending/accepted/rejected`.
  - `login_attempts` uses `attempt/success/failed`.
- Numeric status: only `all_pages.status`, `tinyint(1) default 1`, matching its boolean migration.
- Nine infrastructure/audit tables have no `status` column.
- No migration/current mismatch was found for the `status` fields, but application code must not treat every `status` as the same vocabulary.

### 7. Primary-key inconsistencies

- Every actual table has a primary key.
- Nonstandard names: `gst_rate_id`, `ind_add_id`, `item_id`, `item_type_id`, `unit_type_id`, and `password_reset_tokens.email`.
- String primary keys: `job_batches.id`, `sessions.id`; email PK: `password_reset_tokens.email`.
- Integer PK families are mixed: unsigned int, unsigned bigint, signed int and signed bigint.
- Concrete type drift: `individuals` migration uses `$table->id()` (unsigned bigint), while actual `individuals.id` is signed `bigint(22)`.

### 8. Financial-year and audit timestamp fields

- `financial_year` exists in **35** actual tables, always nullable `char(4)`; repository effective migrations match.
- `machines` originally receives `financial_year` and the later `2026_07_17_190000_remove_financial_year_from_machines_table` removes it; actual `machines` correctly has no such column.
- Audit timestamp naming is intentionally mixed across legacy tables: `created/modified`, `created_at/updated_at`, and `created_at/modified_at`, often alongside `created_by/modified_by`.
- No name drift was found for these fields, but the mixed conventions require model-specific `$timestamps`/constant configuration and prevent a single global timestamp assumption.

## Can a fresh database match the current database?

**No.** Even if all 64 repository migrations complete successfully:

1. `agents` will be missing.
2. `cache` and `cache_locks` will exist although they are absent currently.
3. `colours.individual_id` and its index will be missing.
4. `work_process_requirements.warehouse_balance_item_id` will exist although it is absent currently.
5. `individuals.id` will be unsigned bigint rather than the current signed bigint.
6. The duplicate `states` history remains rollback-unsafe.

## Exact repair plan — execute in a later authorized task

No step below was executed during this audit.

1. **Capture and lock the canonical contract.** Take a verified database backup and export `information_schema.columns`, primary keys, indexes and constraints. Add the resulting contract snapshot to CI before changing migration history.
2. **Restore the historical agents file.** Recreate exactly `2026_07_17_000006_create_agents_table.php` from the actual nine-column `SHOW CREATE TABLE` contract. Keep the existing migration name because every current environment already records that exact name as applied.
3. **Neutralize the duplicate states migration safely.** Keep `2026_07_17_122153_create_states_table.php` so the applied ledger remains resolvable, but make it an explicitly documented no-op in both `up()` and `down()`. Keep `2026_07_17_000002_create_states_table.php` as the sole owner of `states`.
4. **Add an idempotent cache reconciliation migration.** Create `cache` and `cache_locks` only when absent. This makes the current database converge with fresh installs and supports the `.env.example` database cache setting. Do not delete the already-applied cache ledger row.
5. **Reconcile `colours.individual_id`.** Preserve the non-destructive current contract: add a new guarded migration that adds nullable `individual_id` plus `colours_individual_id_index` only when missing. It will no-op on the current DB and make fresh databases match. Do not add an FK until its business meaning is confirmed.
6. **Reconcile `work_process_requirements.warehouse_balance_item_id`.** Add a new guarded migration that adds nullable signed integer `warehouse_balance_item_id` only when absent. It will repair the current DB and no-op on fresh DBs where the original create migration already supplies it. Validate the 12 existing rows and the controller paths that currently receive `null`.
7. **Resolve `individuals.id` signedness before any FK work.** Choose unsigned bigint as the target because that is the repository/Laravel contract. First inventory and widen every genuine individual reference; then alter the parent PK. Do not alter the parent alone, because it would make later constraints incompatible.
8. **Build an explicit FK map.** Classify the 253 candidate `_id` columns using models and business flows. For each approved relationship: align type/signedness, run orphan-count queries, resolve orphan data, decide `RESTRICT`/`CASCADE`/`SET NULL`, and add one bounded FK migration. Prioritize users/individuals, sale order/items, work order/items/requirements, warehouse/compartment/stock, and purchase/vendor/items.
9. **Do not normalize status or timestamps globally.** Preserve the four current status vocabularies and model-specific timestamp conventions. Add schema contract tests so numeric `all_pages.status` or workflow enums cannot silently be converted to `AID`.
10. **Prove parity in a disposable database.** Run all migrations on an empty isolated MySQL database, then compare tables, columns, PK types, indexes and FKs against the repaired canonical snapshot. The target after restoring `agents` and reconciling cache tables is **67 tables including `migrations`**, with zero unexplained column/type drift.

## Read-only commands used

```powershell
rg --files database\migrations
rg -n "Schema::create|Schema::table|financial_year|status|foreign" database\migrations
E:\xampp\mysql\bin\mysql.exe -u root -D blackgrd -N -B -e "SELECT ... FROM information_schema.tables"
E:\xampp\mysql\bin\mysql.exe -u root -D blackgrd -N -B -e "SELECT ... FROM information_schema.columns"
E:\xampp\mysql\bin\mysql.exe -u root -D blackgrd -N -B -e "SELECT ... FROM information_schema.key_column_usage"
E:\xampp\mysql\bin\mysql.exe -u root -D blackgrd -N -B -e "SELECT migration,batch FROM migrations"
E:\xampp\mysql\bin\mysql.exe -u root -D blackgrd -N -e "SHOW CREATE TABLE ..."
```

No `migrate`, `rollback`, `schema:dump`, `CREATE`, `ALTER`, `DROP`, `INSERT`, `UPDATE` or `DELETE` command was executed.
