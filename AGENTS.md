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

Old proven business flow

- Existing Laravel 13 implementation
- # Dynamic/configurable replacement of static or hard-coded behavior
    Final new ERP implementation

* Preserve and adapt proven old-ERP business logic wherever it is still valid.
* Do not redesign or rebuild working business logic merely because a roadmap task has a new name.
* Do not invent a new business process when the old ERP already has a working one.
* Change only what is missing, broken, static/hard-coded, unsafe, incompatible, or explicitly required by the approved task.
* If the new ERP already contains a partial port, complete that implementation instead of building a parallel implementation.
* Reuse existing tables, models, controllers, relationships, Blade pages, helpers, and existing services wherever practical.
* Before creating any new table, model, controller, service, route, helper, migration, or module, verify that equivalent functionality does not already exist.
* Prefer minimal targeted changes over broad rewrites.
* Preserve existing working behavior unless the current task explicitly requires changing it.
* Do not automatically start later roadmap tasks.

## 3. Old ERP Reference Rules

- The old ERP is the primary business-flow reference for functionality that already works there.
- Inspect relevant old:
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
    - reports

before changing the equivalent new ERP module.

- Do not blindly copy old PHP/Laravel code.
- Preserve the proven business logic but adapt it to:
    - Laravel 13
    - current schema
    - company isolation
    - RBAC
    - Department Access
    - configurable workflow
    - current validation/security
    - current Bootstrap template
    - approved architecture

- If old and new ERP behavior differs, determine whether the difference is:
    - intentional modernization,
    - dynamic replacement of static behavior,
    - security improvement,
    - company-isolation requirement,
    - approved roadmap change,
    - or incomplete port.

- Do not restore old hard-coded behavior when a newer approved configurable solution already exists.

## 4. Controller-First Coding Rule

- Keep task-specific and single-use business logic inside the existing public controller action whenever practical.
- Do not move ordinary controller logic into a new Service/Action/Manager/Repository just for architectural style.
- Do not create unnecessary:
    - private controller helper functions
    - Services
    - Actions
    - Managers
    - Repository classes
    - Support classes
    - wrapper classes

- Create a separate reusable service/action/support class only when there is a genuine strong need, such as:
    - the same business logic is reused by multiple independent controllers/flows,
    - complex reusable workflow validation,
    - shared organization/security logic,
    - concurrency/locking/version allocation,
    - or an existing approved architecture already owns that responsibility.

- Reuse existing approved services when they already contain the relevant reusable logic.
- Do not create a second service for logic already owned by an existing service.
- Do not rewrite large working controllers merely for style.
- Avoid controller-private business helper functions.
- If logic is used only by one public action and remains readable, prefer keeping it in that action.

## 5. Manual Database Transaction Rule

- Every controller/action function that performs database mutations must manage its own manual transaction.

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
