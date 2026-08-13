# Department Master and Process Mapping

## Scope

This task completes the existing canonical chain:

`Department -> Process Master -> User Department Access -> Work Order visibility`

It does not change RBAC, `/show-workorders` authorization, Work Order creation, Printing/Coating routing, or the Warehouse Job Work flow.

## Inspection

The legacy ERP was inspected read-only. Its routes and Work Order/requirement controllers continue to organize operational work around Weaving/Warping, Dyeing, Coating, Packaging, Warehouse, and the printing flows; it has no equivalent canonical multi-department access implementation to copy.

The Laravel baseline already has the required `Department`, `ProcessItem`, and `UserDepartmentAccess` models, company-scoped Department Access administration, and fail-closed `DepartmentAccessService::allowedProcessIds()`. `/show-workorders` continues to use `whereIn('process_type_id', $allowedProcessIds)`.

Read-only live `blackgrd` inspection found three active company-level departments: `Warehose`, `Packaging`, and `Coating`; seven active Process Masters had null `department_id`; and `user_department_access` had no rows. All active Processes were unambiguous:

| Process | Canonical Department |
| --- | --- |
| Warping | Warping |
| Weaving | Weaving |
| Dyeing | Dyeing |
| D-Printing | Printing |
| C-Printing | Printing |
| Coating | Coating |
| Packaging | Packaging |

There was no Warehouse-specific Process Master record. The canonical Warehouse Department remains available for Department Access and any future correctly classified Warehouse Process.

## Change

`2026_08_13_000001_complete_department_process_mappings` is an idempotent company-scoped master-data migration. It preserves existing Department IDs, repairs `Warehose` only when a company-level `Warehouse` record does not already exist, creates the missing canonical Departments, and maps only the confirmed Process names. `2026_08_13_000002_correct_warping_department_mapping` preserves Warping as its own canonical Department and maps the Warping Process to it. Both leave unrecognized active Process Masters unmapped for review rather than guessing.

The migration intentionally has a non-destructive `down()` method: removing created Departments or mappings could invalidate later legitimate `user_department_access` assignments. Live application is restricted to the hash-pinned `db:apply-reviewed-department-process-mappings` command, which requires a verified backup manifest, maintenance mode, stopped writers, and exact `blackgrd` confirmation.

The Department Access page adds Select All Active Departments. It toggles and submits the existing individual `department_ids[]` checkboxes; the existing sync method therefore records ordinary individual `user_department_access` rows and no all-access flag exists.

## Verification

- `blackgrd_schema_testing`: exact migration applied, its non-destructive rollback path executed, then reapplied.
- Disposable MySQL integration: 3 tests, 10 assertions covering canonical mappings, typo repair, selected/multiple/all Department access, fail-closed no-access behavior, and cross-company exclusion.
- Focused authorization contracts: 11 tests, 57 assertions.
- Full suite: 230 passed, 50 expected MySQL-only skips, 1,540 assertions.
- Blade compilation, targeted route checks, PHP lint, task-scoped Pint, `git diff --check`, and `php artisan quality:check`: passed.

No live master data, schema, maintenance mode, backup, or protected migration was applied during this task.
