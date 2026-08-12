# Full Runtime, Route, Schema, and Template Stabilization

Date: 2026-08-11
Project: `E:\projects\blackgrd`
Branch: `main`
Starting `HEAD`: `4b8a2e882457e426cc17745247971378a98b1d83`
Starting `origin/main`: `4b8a2e882457e426cc17745247971378a98b1d83`

The exhaustive per-route matrix is the companion file [route-runtime-inventory.json](route-runtime-inventory.json). It contains all requested fields for every current registered route: method, URI, name, action, middleware, panel, identity, mutation classification, safe test, before result, error, root cause, fix, after result, and template status.

## 1. Starting Git State

The branch and remote were aligned. The working tree was already dirty and all existing user work was preserved. The starting user-owned changes covered `.gitignore`, user/admin authorization and organization-context providers/services, Coting views/tests, Process tests, three reviewed migration commands, and the runtime schema-drift migration. No reset, revert, commit, push, merge, or force operation was performed.

## 2. Project Rules Followed

- Read and followed `AGENTS.md` before implementation.
- Kept `E:\xampp\htdocs\erp` read-only and did not use it as the route source of truth.
- Kept `DatabaseSafetyGuard`, company isolation, canonical statuses, RBAC, department access, and request-scoped organization context intact.
- Used `blackgrd_schema_testing` for mutation, integration, and migration reversal tests.
- Used only the reviewed, hash-pinned, backup-verified targeted command for live schema repair.
- Preserved Bootstrap 3.3.7 and the current Admin/Frontend shells.
- Kept Task 5.1 disabled and did not apply its workflow migration.
- Did not commit or push.

## 3. Route Count

| Measure | Count |
|---|---:|
| Total registered routes | 470 |
| Total accounted for | 470 |
| Full Laravel lifecycle executed | 467 |
| Static framework audit | 3 |
| Admin | 316 |
| Frontend | 151 |
| Framework | 3 |
| Passed initially / no defect observed | 453 |
| Fixed and exact-route retested | 17 |
| Blocked | 0 |
| Remaining failed routes | 0 |

The original count was 479. Nine Task 5.1 workflow routes were removed from the active runtime registry by the disabled `workflow_definitions` feature flag, producing the correct current source-of-truth count of 470.

Runtime coverage consisted of 149 authenticated/public non-parameterized GET lifecycles, 48 valid-record Admin lifecycles, 18 valid-record Frontend lifecycles, one guest lifecycle, and 251 disposable mutation/unsafe lifecycles. Three Laravel framework routes were statically audited; the framework `PUT storage/{path}` route is the one unsafe route not executed.

## 4. Admin Route Matrix

All 316 Admin rows are present in the companion JSON. The routes that changed from failure to pass are summarized here.

| Method | Route | Before / Error | Root Cause | Fix | After | UI |
|---|---|---|---|---|---|---|
| GET | `admin/branches/create` | 500, invalid named route | Legacy branch/factory names in the form | Rebuilt on registered branch/factory route contract | PASS | FIXED |
| GET | `admin/factories/create` | 500, invalid named route | Same shared form defect | Same canonical form repair | PASS | FIXED |
| GET | `admin/companies/edit` | 500, missing status scope | `State` lacked `HasRecordStatus` | Added canonical status trait | PASS | FIXED |
| GET/PUT | `admin/document-settings` | 500, undefined variable | Private validation code did not receive current settings | Passed the explicit settings contract | PASS | FIXED |
| GET/POST | `admin/fabric-fault-reasons` | 500, missing layout | Views extended nonexistent `admin.common.layout` | Rebuilt on current Admin shell | PASS | FIXED |
| GET | `admin/fabric-qualities/create` | 500, null enum access | Form assumed an enum while cast contract returns string | Canonical string default/normalization | PASS | FIXED |
| GET | `admin/printing-designs/create` | 500, null enum access | Same status contract mismatch | Canonical string default/normalization | PASS | FIXED |
| GET | `admin/ware-house-compartments/{id}/edit` | 500, status/key query errors | String dereferenced as enum; nonexistent `orWhereKey` | String status contract and canonical key query | PASS | FIXED |

Additional Admin repairs covered corrupt `old('status', ...)` Blade expressions, shared status options, Chemical and Printing Design status rendering, and status handling in branch, chemical, item, and compartment services.

## 5. Frontend Route Matrix

All 151 Frontend rows are present in the companion JSON. The routes that changed from failure to pass are summarized here.

| Method | Route | Before / Error | Root Cause | Fix | After | UI |
|---|---|---|---|---|---|---|
| GET | `ajax_script/DenyWarehouseReq` | Reported 500 | Missing requirement was thrown/reported inside mutation flow | Controlled 404 before transaction | PASS | N/A |
| GET | `print-job-card-traceability/{id}` | 500 | Legacy SQL keys, missing relationship/helpers, inactive packaging model reference | Canonical keys, relationship/helpers, removal of inactive dependency | PASS | FIXED |
| GET | `print-mill-dispatch-chalan/{id}` | 500 | Missing user-name helper | Added canonical user lookup | PASS | FIXED |
| GET/POST | `sale-orders` | 500 | Middleware requested a service as a route argument | Constructor injection | PASS | FIXED |
| POST | `sale-order/update` | 500 | Same middleware signature defect | Constructor injection | PASS | N/A |
| GET | `show-warehouse-item-for-printing-requirement` | 500 | Missing AJAX/deny routes and unsafe lookup contract | Added RBAC-mapped routes and canonical validation/lookups | PASS | FIXED |

Work Order, Job/Mill Work, Lab, and Work Process Requirement queries were reconciled from legacy aliases (`work_order_id`, `woi_id`, `coated_pvc`) to canonical physical columns while preserving existing accessors and business output.

## 6. Mutation Route Matrix

| Classification | Count | Verification |
|---|---:|---|
| Mutating or unsafe routes | 252 | Accounted for |
| Executed with invalid/non-destructive disposable input | 251 | No server error |
| Framework storage mutation statically audited | 1 | Action/middleware accounted |
| Live business mutations | 0 | Deliberately not executed |

Every individual mutation row, its guard/middleware, identity, action, and outcome is in `route-runtime-inventory.json`. The pass covered POST, PUT, PATCH, DELETE, and GET endpoints whose names/actions indicate operational mutation.

## 7. Schema Repairs

| Table | Incorrect schema | Repair | Disposable result | Live result | Preservation | Affected runtime |
|---|---|---|---|---|---|---|
| `user_department_access` | Missing | Applied existing required foundation migration | Created and integration-tested | Batch 46, present | Existing users/companies/departments preserved | RBAC, department scope, Work Order visibility |
| `dyeing_colours` | Missing | Applied existing required foundation migration | Created and route-tested | Batch 47, present | No existing business row altered | Dyeing Colour master/lookups |
| `cotings` | `description`, `display_order`, runtime index missing despite old ledger history | Additive/idempotent drift repair | Up/down contract and routes passed | Batch 48, columns/index present | Coting row count preserved | `/admin/cotings` and lookups |
| `sale_order_items` | Company-scoped model had no `company_id` | Backfill from parent Sale Order, enforce non-null, add company/status index | Rollback/reapply passed | Batch 49, 2 rows, 0 nulls, 0 ownership mismatches | Row content snapshot preserved | Sale Order Item and all company-scoped descendants |

Live backup set: `storage/backups/runtime-foundation-20260811_163300`.

- Full backup: 716,080 bytes, SHA-256 `f6bb23bfcea7497a178f4f3a82cfb2da7a5099a8688b857c601c95cd5dbed18e`
- Affected tables: 9,166 bytes, SHA-256 `06f9002c31c7a27e0173c600fc373a82e4ee42a128349e403daf1b6cb83c7aa6`
- Migration ledger: 7,127 bytes, SHA-256 `c82722e0be6d314378918e16da070fb032eeb4391197fe0b5d69ccc8784494bb`

The manifest checksums, sizes, database identity, maintenance mode, and write-stop confirmation were validated before application. The application was restored from maintenance mode after verification. Generic live migration remained blocked.

## 8. Required Foundation Migrations Applied

| Migration | Classification / reason | Disposable verification | Rollback/re-migrate | Live targeted repair | Final schema |
|---|---|---|---|---|---|
| `2026_08_11_000002_create_user_department_access_table` | A: active authorization foundation | PASS | Covered by disposable foundation cycle | Hash-pinned command | Present |
| `2026_08_11_000006_create_dyeing_colours_table` | A: active master dependency | PASS | Covered by disposable foundation cycle | Hash-pinned command | Present |
| `2026_08_12_000012_repair_runtime_master_schema_drift` | A: active Coting schema drift | PASS | Idempotent repair verified | Hash-pinned command | Required columns/index present |
| `2026_08_12_000013_add_company_scope_to_sale_order_items` | A: company isolation contract | PASS | Explicit down/up test: 1 pass, 4 assertions | Hash-pinned command | Non-null ownership and index present |

Other A-class migration files still shown as `Pending` were not blindly executed: their active live table/column contracts already passed actual schema and route lifecycle verification, while applying the historical files could duplicate existing schema. The pending files are branch extension, HSN/GST/item tax, unit/item type, fabric quality, machine capacity, shifts, individual role extensions, printing designs, and document settings. Task 5.1's `2026_08_11_000001_create_workflow_definition_tables` is B-class future work and remains pending/disabled.

## 9. Code Repairs

- Added missing canonical model trait/relationship contracts.
- Corrected physical key and column usage in Work Order, Lab, Job/Mill, and requirement queries.
- Removed references to nonexistent packaging/work-print models from active code.
- Reused the canonical `TransitionWorkRequirement` action for deny transitions.
- Added validation, null handling, canonical `whereKey` lookup, and controlled 404 behavior.
- Corrected status string/enum assumptions throughout affected services and views.
- Fixed Document Settings validation state and Sale Order validation middleware injection.

## 10. RBAC / Organization Repairs

- Added permission mappings for the Printing Requirement AJAX and deny routes.
- Feature-gated workflow routes, navigation, resource mapping, and custom mappings together.
- Verified every authenticated route has a current permission mapping.
- Verified Admin and Frontend guard separation, organization middleware order, and default-deny behavior.
- Preserved the request-scoped `CurrentOrganizationContext`; no hard-coded company or identity bypass was added.

## 11. Department Access Repairs

The required `user_department_access` table was installed through the reviewed foundation path. Department access and Work Order visibility tests pass, no legacy user-ID authorization rule was introduced, and organization/company filtering remains active.

## 12. Controller / Route / View Repairs

- Registered the two missing Printing Requirement endpoints and aligned forms with route names.
- Repaired missing controller helpers and model relationships used by rendered pages.
- Rebuilt Fabric Fault Reason pages on the current Admin include structure.
- Corrected invalid Blade status expressions across six Admin edit pages.
- Kept incomplete Lab and held Workflow functionality outside the active route set.

## 13. Template / UI Repairs

| Measure | Count |
|---|---:|
| Blade source files scanned | 232 |
| Full Admin/Frontend page documents structurally tested | 187 |
| Route-level Template PASS | 190 |
| Route-level Template FIXED | 17 |
| Route-level NOT APPLICABLE | 263 |
| Template BLOCKED | 0 |

Twenty-nine Frontend Blade files used the broken `content-wrapperd` class; these now use the current `content-wrapper` shell. Branch/Factory and Fabric Fault Reason pages were aligned with current Bootstrap 3.3.7 Admin forms/listings. The full-page regression validates the correct panel shell and rejects the broken wrapper spelling. The in-app browser REPL was not available in this environment, so authenticated Laravel lifecycle tests and rendered HTML/shell assertions were used; work did not stop or skip protected pages.

The final UI follow-up aligned the remaining raw Admin role/user permission pages with the States page shell (content header, panel body, sidebar/footer assets). Role permission checkboxes now use the theme-compatible input/label sibling structure, so checked permissions render correctly and persist through the role form; department-access checkboxes use the same contract.

## 14. Tests and Gates

| Check | Result |
|---|---|
| Full PHPUnit suite | 205 passed, 20 skipped, 0 failed, 1,388 assertions |
| Disposable route/runtime pass | 8 passed, 21 assertions |
| Reviewed company-scope rollback/reapply | 1 passed, 4 assertions |
| Task-scoped Pint (new/modern stabilization files) | PASS |
| PHP lint | PASS |
| Blade cache | PASS |
| Route list | PASS, 470 |
| Git diff check | PASS |
| Recent Laravel exception scan | No new exception/SQLSTATE in the final log tail |
| Full `quality:check` | Functional checks PASS; overall FAIL only on legacy whole-file PSR-12 debt |

The 20 skips in the normal SQLite run are intentional MySQL-only tests (including the role-checkbox persistence interaction); the route tests and migration reversal were run separately on `blackgrd_schema_testing` and passed. PHPUnit also emitted a non-fatal result-cache permission warning during the disposable route run.

The full quality command passed Database Safety, RBAC Coverage, Permission Registry, Audit Integrity, Number Series, Financial Year, Migration Safety, Maintenance Mode, PHP Tests, Routes, Blade, PHP Lint, and Git Diff. Pint reports pre-existing whole-file formatting debt when a legacy controller or Blade receives even a one-line runtime/template repair. Per `AGENTS.md`, those files were not bulk-reformatted merely to make a gate green.

## 15. Remaining Errors

No registered route has a remaining 500, SQLSTATE, missing schema, missing action, missing view, or unexplained authorization failure.

One non-runtime P3 item remains: legacy PSR-12 formatting debt causes the aggregate quality command's Pint sub-check to fail. This is deliberately not mixed into the stabilization diff as a large unrelated rewrite.

## 16. Changed Files

The implementation changes are grouped below; the complete working-tree status remains available through `git status --short`.

- Commands/migrations/config: reviewed Coting, Process, and Runtime Foundation commands; feature configuration; RBAC configuration; runtime drift and Sale Order Item company-scope migrations.
- Controllers/middleware: Admin Document Settings and Warehouse Compartment; Common, Job/Mill, Lab, Work Order, Work Process Requirement; Sale Order validation middleware.
- Models/services/support: State, Work Process Requirement, branch/chemical/item/warehouse-compartment services, Admin Navigation.
- Routes: `routes/web.php`.
- Admin views: All Pages, Branches, Chemicals, Coting, Fabric Fault Reasons, Fabric Qualities, GST Rates, Notifications, Packaging Types, Printing Designs, States, User Web Pages, and shared status options.
- Frontend views: 29 Job/Mill, Warehouse, Work Order, and Work Process Requirement pages with shell corrections.
- Tests: route response/runtime, migration reversal, Coting/Process/Sale Order contracts, and code-integrity snapshot.
- Audits: this report and the exhaustive route inventory JSON.
- Preserved user-owned files also remain dirty: `.gitignore`, User Controller, application/database safety providers, authorization/organization services, Coting views, and their existing tests.

## 17. Git Diff Summary

Before adding this report, the stabilization working tree contained 74 tracked/untracked changed paths with 518 insertions and 227 deletions in the tracked diff. The large route inventory and this audit are untracked documentation artifacts and are intentionally left for review. No commit was created.

## 18. Final Stability Status

| Measure | Result |
|---|---:|
| Total routes | 470 |
| Checked/accounted | 470 |
| Final PASS | 470 |
| Fixed | 17 |
| Blocked | 0 |
| Failed | 0 |
| P0 remaining | 0 |
| P1 remaining | 0 |
| P2 remaining | 0 |
| P3 remaining | 1 (legacy formatting gate debt) |

Task 5.1 remains on HOLD: its routes, navigation, RBAC mappings, and migration are inactive.

## 19. Final Recommendation

**Is the project stable enough to resume Task 5.1? YES, from a runtime, route, schema, isolation, RBAC, and template perspective.**

Resume it only as a separately approved task with the current feature flag still defaulting to false. The existing P3 formatting baseline should be handled as a dedicated cleanup or through a reviewed baseline-aware formatting policy; it is not an active runtime blocker and should not be folded into Workflow implementation.
