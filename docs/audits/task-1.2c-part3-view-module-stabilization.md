# Task 1.2C Part 3 — View and Incomplete Module Stabilization

Date: 2026-08-03

Project: `E:\projects\blackgrd`

Read-only reference: `E:\xampp\htdocs\erp`

## Scope and outcome

This task audited all seven unique missing literal Blade views recorded by Task 1.1. It did not create a Packaging or Lab Test module and did not execute a migration, schema statement, or data write.

- The two active Dyed Work Order aliases now reuse the working canonical Work Order listing with the verified Dyeing process filter (`process_items.id = 3`).
- The missing Work Purchase Requirement listing belonged to an unrouted legacy `index()` method with no current caller. That dead method and its now-unused imports were removed; the active requisition creation route remains unchanged.
- The incomplete Lab Test request routes and their operational Work Order trigger were removed. Direct access now returns 404 instead of reaching missing classes/tables or partially mutating a WPR record.
- The Sale Order Work Order Details route remains registered and retains its existing explicit controlled-unavailable redirect.
- No new Blade view was created. Five missing Lab views remain documented source debt inside the inactive `LabTestController`; no active route can reach them.

## Seven-view classification matrix

| Task 1.1 missing view | Calling method(s) before stabilization | Related URI / route name | Usage evidence at task start | Controller payload | Current dependencies | Old ERP evidence | Classification and final handling |
|---|---|---|---|---|---|---|---|
| `html.labrequest.print_lab_report` | `LabTestController::print_lab_report_old()` and `print_lab_report()` | `/print-lab-report/{id}` / `print-lab-report`; registration was commented, not active | No current menu, button, Blade, AJAX, or JS caller | Old action: `labTest`, `labRequest`, `requirement`, `resultsCollection`. Newer action: `labTest`, `labRequest`, `requirement`, `report`, `clsArr`, `leadTimeText` | Missing `LabTest`, `LabTestResult`, `LabTestRequest`, `LabRequirement` models/tables and the view | `resources/views/html/labrequest/print_lab_report.blade.php` exists and was used by the old route | **Lab Test module / missing table-model dependency / later phase.** Controller retained as inactive reference; direct URL is 404. |
| `html.labrequest.check_lab_report` | `LabTestController::check_lab_report()` | `/check-lab-report/{id}` / `check-lab-report`; registration was commented | No current caller | `labTest`, `labRequest`, `requirement`, `resultsCollection` | Missing `LabTest`, `LabTestResult`, `LabTestRequest`, `LabRequirement` models/tables and view | `resources/views/html/labrequest/check_lab_report.blade.php` exists | **Lab Test module / later phase.** Left unreachable; direct URL is 404. |
| `pdf.lab-test-report` | `LabTestController::downloadReport()` via `Pdf::loadView()` and `view()` | `/lab-test/{id}/report` / `lab-test.report`; registration was commented | No current caller | `labTest`, `labRequest`, `report`, `requirement`, `tests`, `standards`, `results`, `for_pdf` | Missing `LabTest`, `LabTestResult`, `LabTestRequest`, `LabRequirement`, `LabTestStandard` models/tables and PDF view | `resources/views/pdf/lab-test-report.blade.php` exists | **Lab Test reporting / later phase.** No dummy PDF added; direct URL is 404. |
| `html.labrequest.show-labrequest` | `LabTestController::showLabRequest()` | `/show-lab-request` / `show-lab-request` and `/show-lab-workorders` / `show-lab-workorders`; both registrations were commented | No current caller | `dataLR`, `standards` | Missing `LabTestRequest`, `LabTestStandard` models/tables and view | `resources/views/html/labrequest/show-labrequest.blade.php` exists | **Lab Test module / later phase.** Left unreachable; both direct URLs are 404. |
| `html.labrequest.add-lab-test-result` | `LabTestController::showResultForm_old()` and `showResultForm()` | `/lab-test/{id}/result` / `lab-test.result.show`; registration was commented | No current caller | Old action: `requirement`, `labRequest`, `standards`. Newer action additionally sends `standardsById`, `standardsByKey`, `tests`, and six colour-fastness records | Missing `LabRequirement`, `LabTestRequest`, `LabTestStandard`, `LabColourFastness` models/tables and view | `resources/views/html/labrequest/add-lab-test-result.blade.php` exists | **Lab Test result entry / later phase.** No form invented; direct URL is 404. |
| `frontend.workorders.show-dyed-workorders` | `WorkOrderController::checkingDyedWorkOrder()` | `/show-dyed-workorders` / `show-dyed-workorders` and `/show-workorders-dyeing` / `show-workorders-dyeing`; both active under `auth:web` | No current operational caller; an archived Blade and old ERP contain the historical “Dyeing Notifications” link. Direct routes remain valid. | Removed duplicate query previously attempted `dataWI`, totals and filter variables, but used the wrong plural view namespace and targeted an incomplete duplicate page | All core Work Order models/tables exist. `process_items.id = 3` is verified as active `Dyeing`. The working `frontend.workorder.show-workorders` listing already accepts `search_process_id[]`. | Old ERP has `html/workorder/show-dyed-workorders.blade.php` and notification links | **Duplicate feature; backend-ready through canonical workflow.** Both aliases preserve query parameters, force `search_process_id[]=3`, and redirect to `show-workorders`. No duplicate Blade was created. |
| `html.workpurchaserequirements.show-work-purchase-requirement` | Removed `WorkPurchaseRequirementController::index()` | No current route URI/name. Old ERP used `/show-work-purchase-requirement` / `show-work-purchase-requirement`. | No current route, menu, button, Blade link, AJAX, or JS caller. Only the old ERP has Purchase Request navigation. | Legacy payload was `dataWPR`, `qnamesearch`, `item_type`, `qworkordersearch`, `qsalesearch`, `qworkrequestsearch`, `dataIT` | Current `work_purchase_requirements` table/model exists, but the action used old status/key assumptions and was not active. Active POST `add-work-purchase-requisition` is a separate warehouse-requisition workflow. | Old view and route exist at `resources/views/html/workpurchaserequirements/show-work-purchase-requirement.blade.php` and `routes/web.php` | **Dead or unused legacy listing.** Unrouted `index()` and imports used only by it were removed. Active requisition creation was not changed. |

## Dyed Work Order decision

The apparent missing view was a namespace mismatch (`frontend.workorders...`) combined with an incomplete duplicate page. The current canonical Work Order page already supplies the filters, relationships, forms, modals, permission context, and process filtering needed for this listing.

`checkingDyedWorkOrder()` now performs no database query. It preserves the incoming query string, overrides `search_process_id` with `[3]`, and redirects to named route `show-workorders`. Both historical alias routes remain registered and guarded. This avoids maintaining a second Work Order UI and ensures the canonical controller owns its complete view payload.

The existing file `resources/views/frontend/workorder/show-dyed-workorders.blade.php` was not deleted; it is no longer an active route target and can be reviewed separately as legacy content.

## Work Purchase Requirement decision

Current source usage proved the missing listing dead:

- no current GET route or named route targets `WorkPurchaseRequirementController::index()`;
- no current navigation, Blade, JavaScript, or AJAX caller references `show-work-purchase-requirement`;
- the only active controller route is POST `/add-work-purchase-requisition` (`add-work-purchase-requisition`), called from the warehouse item requirement flow;
- old ERP alone contains the listing route, navigation and Blade.

The dead listing method was removed. The active POST action, its validation, transaction, `work_purchase_requirements` inserts, and WPR updates were left unchanged.

## Sale Order Work Order Details and Packaging blockers

Route `/show-saleorder-workorder-details/{id}` (`show-saleorder-workorder-details`) still resolves to `SaleOrderController::showSaleOrderWorkOrderDetails()` under `auth:web`. The action intentionally performs no lookup and redirects to `sale-orders.index` with:

```text
Work order details page is not ready yet.
```

Current schema-backed data that a future implementation can load is:

```text
SaleOrder.id
  -> SaleOrderItem.sale_order_id
  -> WorkOrderItem.sale_order_item_id
  -> WorkOrderItem.work_order_id -> WorkOrder.id
  -> WarehouseOutItem through work_order_id
```

Remaining Packaging dependencies are exact and unchanged:

| Required model | Required table | Current state |
|---|---|---|
| `PackagingOrder` | `packaging_orders` | Missing |
| `PackagingOrderItem` | `packaging_order_items` | Missing |
| `PackagingProcessRequirement` | `packaging_process_requirements` | Missing |

Only `PackagingType` / `packaging_types` exists. The intended details Blade is also absent. The old action and view use legacy PKs, address fields, deletion flags and numeric statuses that do not match current AID schema. No fake details page was created. The route was not removed because historical/archived caller evidence exists and its controlled response is already safe.

## Lab Test dependency matrix

The live `blackgrd` database has 65 application tables. Read-only table inventory and the repository migrations contain none of the six Lab tables below.

| Current controller dependency | Expected table from old ERP model | Current model | Current table/migration | Used by controller actions |
|---|---|---|---|---|
| `LabTest` | `lab_tests` | Missing | Missing | print, store, check, report, requirement submission |
| `LabTestResult` | `lab_test_results` | Missing | Missing | store, print/check relations, report, result status |
| `LabTestRequest` | `lab_test_requests` | Missing | Missing | request list/send/accept/reject/approve, print/report/result |
| `LabTestStandard` | `lab_test_standards` | Missing | Missing | request list, report, result form |
| `LabRequirement` | `lab_requirements` | Missing | Missing | print/check/report/result form and submission |
| `LabColourFastness` | `lab_colour_fastness` | Missing | Missing | result-form colour-fastness defaults |

Related route/action state:

| URI / name | Action | State before task | Risk | Final state |
|---|---|---|---|---|
| POST `/labtests` / `labtests.store` | `LabTestController::store()` | Active, no caller found | Missing `LabTest*` classes/tables | Route removed; 404 |
| GET `/lab-request/send` / `lab-request.send` | `sendLabRequest()` | Active and called by the Work Order Lab button | It first saves `work_process_requirements.lab_req_status = Requested`, then uses obsolete `work_order_id`, `woi_id`, numeric status assumptions and missing `LabTestRequest`; no transaction protects the partial WPR write | Route, button, modal and AJAX removed; 404 |
| GET request list/accept/reject/approve/form/result/report/check/print routes | Corresponding public methods in `LabTestController` | Already commented/inactive | Missing classes, tables and views | Comment-only route declarations/imports removed; URLs remain 404 |
| Lab requirement and colour-fastness routes | Missing controller classes | Already commented/inactive | Missing controllers/models/tables | Comment-only declarations/imports removed; URLs remain 404 |

The Work Order page still displays existing persisted `Requested` and `Approved` labels. Pending/Rejected rows now show a non-clickable `Lab unavailable` label. Item/individual “Lab Test Required” master fields and WPR lab status columns were not removed or changed. No model, migration, table, result form, report, PDF or new workflow was added.

## Tests added and updated

Added `tests/Feature/Regression/ViewModuleStabilizationTest.php`:

- reflects every active controller action and verifies each literal `view()`, `View::make()` or `loadView()` target exists;
- verifies both Dyed aliases retain their controller/guarded route contract and redirect with the canonical Dyeing filter;
- renders the actual canonical Work Order Blade body with the complete controller payload and an empty paginator, catching top-level undefined variables;
- verifies Lab route names are absent, direct URLs return 404, and the active Work Order page has no Lab AJAX/modal trigger;
- verifies the dead Work Purchase listing is absent while the active requisition POST remains;
- verifies the controlled Sale Order Work Order Details route remains.

Updated `tests/Unit/Regression/CodeIntegritySnapshotTest.php` so only the five intentionally inactive Lab views remain in the repository-wide missing-view characterization. The Dyed namespace failure and dead Work Purchase view reference are no longer expected.

## Verification results

| Check | Result |
|---|---|
| Changed controller/test PHP lint | PASS |
| Focused stabilization + snapshot suite | PASS — 10 tests, 30 assertions |
| `php artisan route:list` | PASS — 289 routes |
| `php artisan route:cache` | PASS |
| Full suite with cached routes | PASS — 51 passed, 11 skipped, 583 assertions |
| Skip reason | Existing MySQL-only integration suites skip under default SQLite configuration |
| `php artisan route:clear` | Command reported success; generated cache persisted because of protected-file permissions, so the exact verified `bootstrap/cache/routes-v7.php` cache file was removed with elevated filesystem permission. Final `routesAreCached()` is false. |
| `php artisan db:safety-check` on live `blackgrd` | Expected **BLOCKED**; allow-list no match and confirmation not armed |
| `git diff --check` | PASS |

The focused run emitted a non-fatal permission warning when PHPUnit tried to update the existing `.phpunit.result.cache`; all focused tests still passed. The subsequent full suite completed without that warning.

## Commands executed

Read-only source/reference/database audit used `Get-Content`, `rg`, `rg --files`, `git status`, `git log`, `git diff`, and:

```powershell
php artisan db:safety-check
php artisan tinker --execute="dump(DB::connection()->getDatabaseName()); dump(Schema::getTableListing());"
php artisan tinker --execute="dump(\App\Models\ProcessItem::query()->get(['id','process_name','status'])->toArray());"
```

The Tinker commands executed SELECT/schema metadata reads only. One exploratory SELECT requested nonexistent `process_items.process_code` and failed read-only; it was repeated with existing columns. No database write occurred.

Required verification:

```powershell
php -l app\Http\Controllers\WorkOrderController.php
php -l app\Http\Controllers\WorkPurchaseRequirementController.php
php -l tests\Feature\Regression\ViewModuleStabilizationTest.php
php artisan test --compact tests\Feature\Regression\ViewModuleStabilizationTest.php tests\Unit\Regression\CodeIntegritySnapshotTest.php
php artisan route:list
php artisan route:cache
php artisan test --compact
php artisan route:clear
php artisan db:safety-check
git diff --check
git status --short
git diff --stat
git diff --name-status
```

`php artisan route:clear` initially reported success without deleting the protected generated cache file. After resolving and checking that its absolute path was exactly `E:\projects\blackgrd\bootstrap\cache\routes-v7.php`, the file alone was removed with `Remove-Item -Force -LiteralPath`. No source, database, backup, or user file was deleted.

## Changed files

- `app/Http/Controllers/WorkOrderController.php`
- `app/Http/Controllers/WorkPurchaseRequirementController.php`
- `routes/web.php`
- `resources/views/frontend/workorder/show-workorders.blade.php`
- `resources/views/frontend/workorder/partials/show-workorders-scripts.blade.php`
- `resources/views/frontend/workorder/partials/show-workorders-modals.blade.php`
- `tests/Unit/Regression/CodeIntegritySnapshotTest.php`
- `tests/Feature/Regression/ViewModuleStabilizationTest.php` (new)
- `docs/audits/task-1.2c-part3-view-module-stabilization.md` (new)

All changes are intentionally left uncommitted for review.
