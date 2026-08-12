# Project Instructions

## 1. Project Paths

- Actual Laravel 13 project: `E:\projects\blackgrd`.
- Old working ERP: `E:\xampp\htdocs\erp`.
- `E:\xampp\htdocs\erp` is strictly read-only.
- Never modify old ERP files, database, configuration, migrations, Git state, or business data.
- All actual development changes must be made only in `E:\projects\blackgrd`.

## 2. Core Development Principle

- This project is NOT being rebuilt from zero.
- The old ERP already contains proven working textile-manufacturing business flows.
- For every existing ERP module/task, inspect the relevant working implementation in `E:\xampp\htdocs\erp` first.
- Then inspect what is already implemented/ported in `E:\projects\blackgrd`.
- Compare both before writing new code.

Use this principle:

```text
Old proven business flow
+
Existing Laravel 13 implementation
+
Dynamic/configurable replacement of static or hard-coded behavior
=
Final new ERP implementation
```

- Preserve and adapt proven old-ERP business logic wherever it is still valid.
- Do not redesign or rebuild working business logic merely because a roadmap task has a new name.
- Do not invent a new business process when the old ERP already has a working one.
- Change only what is missing, broken, static/hard-coded, unsafe, incompatible, or explicitly required by the approved task.
- If the new ERP already contains a partial port, complete that implementation instead of building a parallel implementation.
- Reuse existing tables, models, controllers, relationships, Blade pages, helpers, and existing services wherever practical.
- Before creating any new table, model, controller, service, route, helper, migration, or module, verify that equivalent functionality does not already exist.
- Prefer minimal targeted changes over broad rewrites.
- Preserve existing working behavior unless the current task explicitly requires changing it.
- Do not perform unrelated refactoring while completing a task.
- Do not automatically start later roadmap tasks.

## 3. Old ERP Reference Rules

- The old ERP is the primary business-flow reference for functionality that already works there.

Before changing the equivalent new ERP module, inspect relevant old:

- routes
- controllers
- public functions
- models
- relationships
- tables/columns
- Blade pages
- validations
- calculations
- process conditions
- warehouse movements
- Work Order flow
- inspection flow
- production flow
- Job Work flow
- stock allotment flow
- purchase/PO-related flow
- reports

- Do not blindly copy old PHP/Laravel code.

Preserve the proven business logic but adapt it to:

- Laravel 13
- current schema
- company isolation
- RBAC
- Department Access
- configurable workflow
- current validation/security
- current Bootstrap template
- approved architecture

If old and new ERP behavior differs, determine whether the difference is:

- intentional modernization
- dynamic replacement of static behavior
- security improvement
- company-isolation requirement
- approved roadmap change
- or incomplete port

- Do not restore old hard-coded behavior when a newer approved configurable solution already exists.
- If the old ERP already has a complete working flow and the new ERP contains only part of it, complete the existing port rather than building a second implementation.
- If old ERP behavior is unclear, inspect the full related flow before making assumptions.

## 4. Controller-First Coding Rule

- Keep task-specific and single-use business logic inside the existing public controller action whenever practical.
- Do not move ordinary controller logic into a new Service/Action/Manager/Repository merely for architectural style.

Do not create unnecessary:

- private controller helper functions
- Services
- Actions
- Managers
- Repository classes
- Support classes
- wrapper classes

Create a separate reusable Service/Action/Support class only when there is a genuine strong need, such as:

- the same business logic is reused by multiple independent controllers/flows
- complex reusable workflow validation
- shared organization/security logic
- concurrency/locking/version allocation
- or an existing approved architecture already owns that responsibility

- Reuse existing approved services when they already contain the relevant reusable logic.
- Do not create a second service for logic already owned by an existing service.
- Do not rewrite large working controllers merely for style.
- Avoid controller-private business helper functions.
- If logic is used only by one public action and remains readable, prefer keeping it inside that action.
- Do not create helper/service files merely to make a controller function shorter.

## 5. Manual Database Transaction Rule

- Every controller/action function that performs database mutations must manage its own manual database transaction.

Use this pattern:

```php
DB::beginTransaction();

try {
    // Existing function code

    DB::commit();

    // Existing success response
} catch (\Exception $e) {
    DB::rollBack();

    // Existing error response using $e->getMessage()
}
```

Every mutating controller function must independently contain and manage:

- `DB::beginTransaction()`
- `try`
- `DB::commit()`
- `catch (\Exception $e)`
- `DB::rollBack()`
- appropriate error response using `$e->getMessage()`

- Do not depend on another controller action's transaction.
- Do not shortcut one mutating controller action into another mutating controller action.

This is NOT allowed:

```php
return $this->updateinspectionworkorder($request);
```

- Do not create wrapper controller actions whose only purpose is calling another mutating controller action.
- If two controller actions perform similar work, each action must still execute its own required business logic and manage its own transaction.

Preserve each function's existing:

- validation
- request handling
- database operations
- redirects
- success response
- JSON response format
- error response

- `DB::commit()` must happen only after all required database operations have completed successfully.
- If any operation fails, execute `DB::rollBack()` before returning the error response.
- Multi-step database mutations must not leave partial business data after an exception.

Use database locking where genuinely required for concurrency-safe:

- numbering
- stock allocation
- quantity allocation
- workflow/version sequencing
- publication
- similar critical operations

## 6. External Processing / Job Work Business Rule

- Do not assume that Internal vs External execution is selected or permanently stored on a Work Order.
- Do not make Work Order the controlling point for deciding whether material is processed internally or externally.
- Preserve the actual old ERP Warehouse/Job Work business flow.

When workload or operational requirements make outside processing necessary, authorized staff may manually decide to send available Warehouse material outside for processing.

The business flow is:

```text
Warehouse Stock
→ Staff decides material should be processed outside
→ External Dispatch / Job Work
→ Material sent to Vendor / Mill
→ External Processing
→ Processed material returns
→ Warehouse receives returned material into Stock
→ Returned stock remains linked to the relevant PO / production requirement
→ Warehouse allots that stock against the relevant PO / production requirement
→ Material is allotted/sent to the required next process, such as Coating
```

Example:

```text
Warehouse has material required for a PO
→ Dyeing is outsourced because of production pressure
→ Material is dispatched outside
→ Dyed material returns
→ Warehouse receives it as stock
→ Returned dyed stock is allotted against that PO requirement
→ Material is then allotted to Coating
```

- The system must NOT automatically decide Internal vs External based only on:
    - Process Master
    - Workflow
    - Process ID
    - Work Order
    - order pressure
    - machine capacity
    - machine availability
    - delivery urgency

- Staff makes the operational decision to send material outside when required.
- Order pressure may influence the staff's decision, but the system must not automatically make that decision unless a future explicitly approved feature requires such automation.

External processing must preserve enough information to identify:

- what material was sent outside
- from which Warehouse stock it came
- why / for which PO or production requirement it was sent
- vendor/mill/job-work context where applicable
- what processed material was received back
- what stock was created or updated after receipt
- which PO or production requirement the returned stock must satisfy
- where that stock was subsequently allotted

Reuse and preserve the existing old-ERP:

- Warehouse stock
- Job Work
- external dispatch
- challan
- gate pass
- external receipt
- stock receipt
- requirement
- allotment
- next-process flow

- Do not create a second External Execution architecture when the existing Warehouse/Job Work flow already handles the business requirement.
- Do not create a new Work Order execution-mode engine merely to support Internal/External processing.
- Do not automatically route a Workflow Step to External Job Work.
- Do not automatically route a Process to External Job Work merely because Process Configuration contains `External` or `Both`.

Existing Process Configuration values:

- `Internal`
- `External`
- `Both`

must not control runtime Work Order routing unless a later explicitly approved business requirement defines their exact operational use.

- Until such a requirement is explicitly approved, treat those values as configuration/capability information only.
- Do not remove or redesign those Process Configuration fields merely because they are not currently responsible for runtime external-processing decisions.
- Do not rebuild the existing Job Work module during a Workflow task.
- If a roadmap task touches External Processing, first inspect the old ERP Warehouse/Job Work flow and reuse/adapt that proven behavior.

## 7. Eloquent and Code Style

- Keep Eloquent queries simple and readable.
- Keep straightforward `where()`, `select()`, `with()`, and `compact()` usage compact when readable.
- Do not unnecessarily split every query condition or short array onto separate lines.
- Break lines only for genuinely complex joins, closures, nested conditions, subqueries, or long logic.
- Do not put database queries inside Blade files.
- Avoid unnecessary `DB::raw()` when clear Eloquent/query-builder logic is practical.
- Do not refactor unrelated working code merely for style.
- Follow Laravel 13 conventions while preserving existing project behavior.
- Keep related short relationship lists compact when readable.

Example:

```php
->with(['ProcessType', 'GatePass', 'Item', 'WorkReqSend', 'WorkInspection'])
```

rather than unnecessarily putting every simple item on a separate line.

## 8. Company and Organization Isolation

Preserve the current organization architecture:

```text
Company
→ Branch / Factory
→ Department
```

- Preserve `BelongsToCompany`.
- Preserve `CurrentOrganizationContext`.
- Do not hard-code company IDs.
- Do not bypass company scope merely to make a query work.
- Do not use `withoutGlobalScopes()` as a workaround for schema or relationship problems.
- Cross-company relationships must fail closed.
- Company-scoped models/tables must have valid company-scope schema.

## 9. Authentication Architecture

- Admin Panel and Frontend Panel remain separate authentication systems.
- Admin Panel uses the existing `admin` guard/account system.
- Frontend Panel uses the existing `web` guard/account system.
- Do not merge Admin and Frontend identities.
- Do not merge guards or authentication providers.
- Do not create a shared login system unless explicitly approved by a future task.
- A person may legitimately have a separate Admin account and Frontend account when access to both panels is required.

## 10. RBAC and Department Access

- Preserve the existing RBAC architecture.

Use this distinction:

```text
RBAC
= WHAT the user can do

Department Access
= WHERE the user can operate
```

- Do not use hard-coded User IDs.
- Do not use hard-coded emails.
- Do not create hard-coded authorization exceptions.
- Do not create hard-coded Department exceptions.
- Reuse existing permissions before creating new permission families.
- Navigation and operational access must remain permission-aware.
- Missing authorization/context must fail closed.

## 11. Configurable Workflow Engine

- Production process flow must be database-driven and configurable.
- Do not hard-code one fixed production route inside controllers, models, Blade files, JavaScript, or helper functions.

Different routes may be configured according to:

- company
- item
- item type
- fabric quality
- customer requirement where applicable
- Sale Order
- Sale Order Item / fabric-specific requirement

Valid examples include:

```text
Yarn → Warping → Weaving → Dyeing → Printing → Coating → Packaging
```

```text
Yarn → Warping → Weaving → Dyeing → Coating → Printing → Packaging
```

```text
Greige Receive → Printing → Coating → Packaging
```

```text
Weaving → Dyeing → Packaging
```

```text
Dyeing → Printing → Packaging
```

- Do not assume every Workflow starts with Yarn or Warping.
- Do not assume every Workflow contains every Process.
- Different Sale Order Items in the same Sale Order may legitimately use different routes.

Example:

```text
Sale Order
├── Fabric A → Dyeing → Printing → Coating
├── Fabric B → Dyeing → Coating
└── Fabric C → Printing → Packaging
```

## 12. Workflow Architecture

Preserve the existing canonical architecture:

```text
Workflow Definition
→ Workflow Version
→ Workflow Version Steps
```

- Do not create a second competing Workflow engine.
- `process_items` is the canonical Process Master unless the approved current architecture explicitly changes it.
- Do not create a duplicate Process Master.
- Published Workflow Versions are immutable snapshots.
- Existing Sale Order Items continue using their assigned Workflow Version even when newer Versions exist.
- Do not silently modify an in-use Published Workflow Version.
- Workflow Version Steps define the order/fabric-specific process sequence.

Keep this distinction:

```text
Process Configuration
= potentially allowed process behavior/transitions

Workflow Version Steps
= selected Sale Order Item / fabric-specific route

Step Transition Rules
= validation of movement through that route
```

- Process Configuration must not replace the Workflow Version.
- Workflow Version must not bypass Process Configuration validation.

## 13. Required, Optional, Skip and Repeat Workflow Rules

- Required/Optional status belongs to Workflow Version Step, not globally to Process Master.
- Required steps cannot be skipped during normal Workflow movement.
- Optional steps may be skipped only according to approved transition rules.
- Effective skip transitions must still satisfy Process Configuration.
- Do not allow arbitrary jumps across Required steps.
- The same canonical Process may appear multiple times at different Workflow Version Step positions.
- Repeated Process occurrences must be identified using Workflow Step identity/sequence, not only Process ID.
- Do not duplicate Process Master records to represent repeats.
- Repeat-step support does not mean unlimited runtime looping.
- Rework/runtime loops belong to later approved roadmap tasks.

## 14. Workflow Transition Rules

- Workflow progression must use the configured Workflow Version Steps.
- Do not determine the next Process through hard-coded Process IDs or Process names when Workflow configuration owns the route.

Do not write routing such as:

```php
if ($processId == 1) {
    $nextProcessId = 2;
} elseif ($processId == 2) {
    $nextProcessId = 3;
}
```

or:

```php
$nextProcesses = [
    1 => 2,
    2 => 3,
    3 => 4,
];
```

when the configured Workflow can resolve the route.

Reject invalid:

- jumps
- backwards transitions unless an approved rework flow allows them
- cross-company transitions
- out-of-route Processes
- mismatched Workflow Definitions/Versions
- invalid/inactive Processes
- transitions not allowed by Process Configuration

- Final Workflow Step must represent that no next configured Workflow Step exists.

## 15. Printing Route Rule

- Printing position is Sale Order Item / Workflow-specific.
- Coating Master must NEVER decide Printing position.

Do not add/use global Coating Master rules such as:

```text
printing_before_coating
printing_after_coating
```

Both routes must remain configurable:

```text
Dyeing → Printing → Coating
```

and:

```text
Dyeing → Coating → Printing
```

- Printing may also be absent entirely from a Workflow.
- Printing order must come from the assigned Workflow Version sequence.

## 16. Existing Production Flow Preservation

- Old ERP working production logic remains the business-flow reference.
- Do not rebuild modules solely because roadmap terminology differs.
- Existing Work Order, Inspection, Warehouse, Job Work, Packaging, Dispatch, Purchase, Production and stock-allotment flows should be reused/adapted where correct.
- Static values should become dynamic only when the approved current task genuinely requires it.
- Existing production behavior must remain functional while configuration is introduced.
- Do not prematurely connect a new foundation to every old runtime function unless the current roadmap task owns that integration.

## 17. Live Database Safety

- Live database `blackgrd` is protected.
- Never disable, weaken, remove, or bypass `DatabaseSafetyGuard`.
- Never run destructive commands against live `blackgrd`.

Never run against live:

- `migrate:fresh`
- destructive reset
- database drop
- table truncate
- destructive rollback
- equivalent destructive operations

- Do not blindly run normal `php artisan migrate` against live `blackgrd`.
- Do not apply all pending migrations together.

Use:

`blackgrd_schema_testing`

for migration/schema verification.

For schema changes follow:

```text
Inspect
→ Disposable apply
→ Schema verification
→ Focused tests
→ Rollback
→ Reapply
→ Preservation checks
→ Reviewed/hash-pinned targeted live deployment
```

Before an approved live schema/data change verify:

- correct live DB identity
- fresh backup
- checksum/manifest where required
- recovery verification where required
- maintenance/write-stop where required
- PITR requirements where required
- exact reviewed migration hash
- exact targeted migration
- data preservation

- Apply only migration(s) belonging to the current approved task.
- Do not apply unrelated pending migrations.
- Prefer additive and backward-compatible schema changes.
- Actual live schema is the source of truth when migration history and actual schema disagree.
- Prefer a new targeted repair/alter migration rather than editing an already-deployed migration.

## 18. Backup and Point-in-Time Recovery

- Preserve the approved backup and recovery procedure.
- Do not weaken backup/PITR requirements merely to make a migration executable.
- When the deployment checklist requires Point-in-Time Recovery, verify the configured recovery mechanism before live schema changes.
- Preserve MariaDB/MySQL binary logging where it is part of the approved recovery architecture.
- Do not disable `log_bin` merely for convenience.
- Use fresh task-specific backups when the deployment procedure requires them.
- Verify backup checksums/manifests.
- Perform isolated recovery verification when required.
- Do not consider a backup valid merely because a dump file exists.

## 19. Legacy Data Safety

- Preserve existing production data.
- Do not invent historical relationships.
- Do not mass-backfill ambiguous data.
- Do not merge records merely because legacy text values match.
- Before adding unique constraints or FKs, inspect:
    - duplicates
    - orphans
    - data types
    - existing business behavior
    - compatibility

- Prefer backward-compatible nullable references when staged migration is required.
- Historical/current production records must remain readable after every task.

## 20. Lot, Beam, Taka, Roll and Genealogy Rules

Preserve the established Task 5.4 identity behavior:

```text
Lot
= work_process_requirements.req_lot_no

Beam
= existing Warping/Weaving beam identity using the established insp_taka_number flow

Taka
= existing physical Taka identity flowing through Inspection, Gate Pass, Warehouse and Job Work

Roll
= warehouse_item_stocks.packet_number
```

New Warehouse Roll identity follows the established stable form:

```text
ROL-{warehouse_item_stocks.id}
```

- Do not invent duplicate identity masters unless a future approved requirement genuinely needs them.
- Preserve existing identity fields and relationships.

Preserve Task 5.5 genealogy behavior:

```text
Lot → Taka
Taka → Roll
```

where actual production operations create those relationships.

- `production_genealogy_links` is the canonical additive genealogy link structure.
- Do not infer or mass-backfill ambiguous historical genealogy.
- Do not invent a runtime Merge flow when no proven old-ERP Merge business operation exists.
- Job Work movement must preserve physical identity rather than inventing merged identity.
- Do not create a second genealogy engine.

## 21. UI and Blade Rules

- Preserve Bootstrap 3.3.7.
- Preserve the approved current Admin and Frontend templates.
- Existing correct `blackgrd` pages are the UI reference.
- Do not blindly copy old ERP HTML structure when the new project already has an approved template.

Do not introduce:

- Tailwind
- React
- Vue
- another Bootstrap version
- replacement theme

- Keep forms compact.
- Keep practical related fields on the same row where appropriate.
- Keep straightforward HTML inputs/selects compact rather than splitting every attribute over multiple lines.
- Avoid unnecessary vertical spacing.
- Avoid horizontal scrolling where practical.
- Reuse existing CSS/JS/template assets.
- Keep buttons, tables, pagination, alerts, headings and modals consistent.
- Do not add huge page-specific CSS unnecessarily.
- Do not put database queries or heavy business logic inside Blade files.
- Do not use `<div class="well well-sm">` merely for section headings; follow the project's cleaner existing heading style.

## 22. Runtime Stability

- Preserve the stabilized application.

Do not knowingly introduce:

- HTTP 500
- SQLSTATE errors
- unexpected 403
- unexpected 404
- missing Blade
- missing controller methods
- broken relationships
- undefined variables
- RBAC regression
- Department Access regression
- company-isolation regression

- Fix root causes.
- Do not permanently hide errors by removing required fields, disabling relationships, bypassing scopes, weakening authorization, or suppressing exceptions.

## 23. Testing and Quality

Run task-relevant verification.

At minimum for meaningful changes:

- focused tests
- task-scoped Pint
- `git diff --check`
- `php artisan quality:check`

Use:

```bash
php artisan quality:check --quick
```

only for interim feedback.

Never use `--quick` as the final quality gate.

Also run when relevant:

- disposable MySQL tests
- migration apply/rollback/reapply verification
- Blade compilation
- PHP lint
- route checks
- affected page/request regression
- application cache clear
- live schema verification after approved migration

- Do not bulk-format unrelated legacy PSR-12 debt merely to make a quality gate pass.
- A passing unit test alone is not proof of runtime correctness when the affected request/page can safely be exercised.

## 24. Documentation

Architecture documents belong under:

`docs/architecture`

Audit/task evidence belongs under:

`docs/audits`

- Architecture documentation is the source of truth for approved current architecture.
- Update architecture documentation when meaningful architecture/business rules change.
- Keep task audits focused on what was inspected, changed, tested, migrated and verified.
- Do not create unnecessary documentation for trivial changes.

## 25. Git Rules

- Inspect Git state before meaningful tasks.
- Preserve unrelated user changes.
- Never use `git reset --hard` against user work.
- Never force push.
- Do not rewrite unrelated history.
- Do not amend a previously reviewed task commit unless explicitly instructed.
- Commit only reviewed changes belonging to the current task.
- Push only the current task's reviewed commit when authorized.
- If protected/default-branch safety requires explicit confirmation, stop after commit and provide the exact commit hash requiring authorization.

After successful push verify:

```text
HEAD == origin/main
```

- Do not automatically begin the next task after push.

## 26. Roadmap Discipline

- Follow the approved corrected roadmap/dependency order, not simply numeric task numbering.
- Implement only the currently requested task.
- Do not implement future roadmap behavior early.
- AI and later roadmap tasks remain out of scope until explicitly requested.
- If a later dependency is discovered, implement only the minimum current-task foundation required.
- Do not build an entire future feature merely because the current feature may use it later.

## 27. Final Task Procedure

For every roadmap/development task:

1. Read this `AGENTS.md` completely.
2. Verify current Git baseline and worktree state.
3. Inspect the relevant old ERP working flow first when equivalent functionality exists.
4. Inspect the corresponding current implementation in `E:\projects\blackgrd`.
5. Compare old proven behavior with current Laravel 13 behavior.
6. Identify what is already working.
7. Identify only what is missing, broken, static/hard-coded, unsafe, incompatible, or explicitly requested.
8. Reuse existing tables/models/controllers/relationships/business logic wherever practical.
9. Implement only the requested task.
10. Keep ordinary single-use logic inside the relevant public controller action whenever practical.
11. Create Services/Actions/Support classes only when genuinely necessary or when reusing an already-approved existing service.
12. Ensure every changed database-mutating controller function manages its own manual transaction.
13. Never shortcut one mutating controller action into another mutating controller action.
14. Do not make Work Order the Internal/External execution controller; preserve the Warehouse/Job Work external-processing flow.
15. Use `blackgrd_schema_testing` before any required schema/live-data change.
16. Follow all live DB backup, PITR, DatabaseSafetyGuard and targeted migration rules.
17. Run focused tests and relevant regressions.
18. Run task-scoped Pint.
19. Run `git diff --check`.
20. Run final `php artisan quality:check`.
21. Update architecture/audit documentation where appropriate.
22. Review the final diff for task scope and unrelated changes.
23. Commit/push only when authorized.
24. Report exactly what changed, what was reused, what was tested, what was migrated, commit/push status, and any remaining limitation.
25. STOP.
26. Do not start the next roadmap task automatically.

## Skipped Roadmap Features and Duplicate Protection

- Task 5.6 Internal/External Execution Mode must not be implemented. Preserve the Warehouse → External Job Work → Receive Back → Stock → PO/requirement allotment flow; do not create Work Order Internal/External runtime routing.
- Task 5.7 generic Rework/Deviation flow must not be implemented. Failed or damaged material belongs to the actual separate business flows, such as damaged-material sale or RF/Refabric when implemented; do not create generic return-to-previous-workflow-process behavior.
- Accidental duplicate submissions must be prevented across the ERP through a hybrid approach: common double-submit protection for mutation forms/AJAX actions and business-specific backend protection for critical operations.
- Global protection must not block legitimate repeated business transactions. Critical duplicate checks normally remain in the relevant public controller action, and locking or unique business constraints are used only where genuinely appropriate.
- Do not create unnecessary Services, Managers, or Repositories merely for duplicate checking. Every changed mutating controller action retains its independent manual transaction.
- `http://127.0.0.1:8000/show-saleorderitems` is regression-critical. Its Work Order creation UI, buttons, business flow, and entry point must remain unchanged unless explicitly requested; duplicate protection may be added around its existing Work Order-create action.

## Sale Order and Purchase Order Page Change Rule

The following pages are existing working business pages:

- `http://127.0.0.1:8000/sale-orders/create`
- `http://127.0.0.1:8000/add-purchaseorder`

These pages are NOT completely locked.

Requirement-based changes are allowed when they are genuinely needed for the current approved task.

Allowed changes may include:

- adding/removing required fields
- validation changes
- dynamic configuration integration
- workflow-related fields
- customer/vendor/item-related improvements
- UI polishing
- bug fixes
- database-field integration
- permission/company-scope fixes
- JavaScript/AJAX fixes
- required business-rule updates
- small layout improvements

However:

- Preserve the existing working business flow.
- Do not unnecessarily redesign these pages.
- Do not rebuild the complete Sale Order or Purchase Order module merely because a roadmap task touches them.
- Reuse existing Controller, Model, Blade, route, validation and database logic wherever practical.
- Change only what the approved requirement genuinely needs.
- Existing working fields and behavior should remain intact unless the current requirement explicitly needs them changed.
- Do not introduce unnecessary new tables/services/classes when the existing implementation can handle the requirement.
- Any change to these pages must include regression verification of the existing create/save flow.

### Sale Order Create

For:

`http://127.0.0.1:8000/sale-orders/create`

- Requirement-based changes are allowed.
- Preserve existing Sale Order and Sale Order Item creation behavior.
- Workflow-related integration may be added when required by the approved roadmap task.
- Do not unnecessarily change the overall Sale Order creation flow.

### Purchase Order Create

For:

`http://127.0.0.1:8000/add-purchaseorder`

- Requirement-based changes are allowed.
- Preserve the existing Purchase Order creation/business flow.
- Vendor, item, quantity, rate, tax, approval, Job Work, stock or related fields may be adjusted when genuinely required.
- Do not unnecessarily redesign or replace the existing Purchase Order creation implementation.

### Regression Requirement

If any shared Controller, Model, Service, JavaScript, route or database change can affect these pages, verify that both pages still:

- open successfully
- load required data
- validate correctly
- save successfully
- preserve existing relationships
- preserve company isolation
- preserve expected redirects/responses
- do not produce HTTP 500 or SQLSTATE errors
