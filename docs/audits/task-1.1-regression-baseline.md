# Task 1.1 — Current Project Regression Baseline

Date: 2026-08-03  
Project under test: `E:\projects\blackgrd`  
Read-only reference: `E:\xampp\htdocs\erp`

## Scope and safety

- केवल `blackgrd` के tests और यह audit report जोड़े गए हैं।
- Production controllers, models, routes, views, migrations और business logic नहीं बदले गए।
- Database schema/data नहीं बदला गया और कोई migration/seed command नहीं चलाया गया।
- Old ERP में कोई file create/edit/delete नहीं की गई।
- PHPUnit `sqlite :memory:` पर configured है (`phpunit.xml:26-27`), लेकिन project में उस database के लिए complete ERP test schema/fixtures उपलब्ध नहीं हैं। इसलिए operational write flows को live MySQL पर submit नहीं किया गया।

## Audited inventory

| Area | Current inventory | Verification |
|---|---:|---|
| Active routes | 302 | Laravel route collection loaded and audited |
| Controller actions on routes | 298 across 37 controllers | Controller class and target method reflection scan |
| `auth:admin` routes | 137 | Route middleware scan plus guard HTTP tests |
| `auth:web` routes | 151 | Route middleware scan plus guard HTTP tests |
| Model files | 56 | Model import and relationship target scan |
| Blade files | 167 | Literal controller view reference scan |
| Model files declaring relationships | 35 | Key contracts plus missing related-class scan |

## Pass/fail test matrix

`PASS (contract)` means the active route, HTTP verb, controller action and expected guard are locked by a regression test. It does not claim that a database-changing transaction was submitted.

| Flow | Covered contract/runtime check | Baseline result | Full transaction |
|---|---|---|---|
| Admin/User guard separation | Separate login views, guest redirects, admin cannot enter user dashboard, user cannot enter admin dashboard | PASS (HTTP) | Covered without DB writes |
| Sale Order create/edit/cancel/print | Named route, verb, controller method, `auth:web` | PASS (contract) | NOT RUN |
| Work Order generation | `store_workorder` route contract | PASS (contract) | NOT RUN |
| Weaving → Warping shift | Unnamed `ajax_script/shiftWorkOrderToWarping` route contract | PASS (contract) | NOT RUN |
| Warehouse manual inward | `add-item-in-warehouse`, `store_item_in_warehouse` | PASS (contract) | NOT RUN |
| Purchase receipt inward | `add-received-item-in-warehouse`, `storeReceivedItemsFromInvoice` | PASS (contract) | NOT RUN |
| Work requisition allotment | `StoreWarehouseStockAllotment` | PASS (contract) | NOT RUN |
| Inspection and Gate Pass | Inspection and gate-pass route contracts | PASS (contract) | NOT RUN |
| Department warehouse receipt | `receiveWorkItemInDepartmentWarehouse` | PASS (contract) | NOT RUN |
| Department return | Store and accept return request routes | PASS (contract) | NOT RUN |
| Mill dispatch and partial receive | Dispatch and receive route contracts | PASS (contract) | Partial receive semantics NOT RUN |
| Printing before/after decision | `decide-printing-position` route contract | PASS (contract) | Both decision branches NOT RUN |
| Purchase Order create/edit/print | Route, verb, action and guard contracts | PASS (contract) | NOT RUN |
| Admin master CRUD | 21 resources × 6 CRUD route operations, `auth:admin` | PASS (contract) | Create/update/delete NOT RUN |
| Encrypted IDs | `enc`/`dec` round-trip; malformed value produces 404 | PASS (unit) | Covered |
| Direct URL access | Guest direct URLs redirect to correct guard login before ID resolution | PASS (HTTP) | Authenticated record access NOT RUN |
| Active route controller targets | Current failures locked as characterization snapshot | FAIL (17 known missing methods) | See list below |
| Controller model imports | Current failures locked as characterization snapshot | FAIL (11 missing model classes) | See list below |
| Relationship targets | Current failures locked as characterization snapshot | FAIL (2 missing related models) | See list below |
| Literal Blade view references | Current failures locked as characterization snapshot | FAIL (7 missing views) | See list below |

The regression suite itself passes because known defects are intentionally asserted as the current snapshot. Repairing a listed defect later must also update the corresponding snapshot expectation.

## Broken active route/controller methods

All 17 failures below are registered active route actions. No controller class is missing; the listed method is absent.

| Route definition | Route | Target function | Current error |
|---|---|---|---|
| `routes/web.php:74` | `GET list_dyeing` | `CommonController::list_dyeing` | Method does not exist |
| `routes/web.php:75` | `GET list_employee` | `CommonController::list_employee` | Method does not exist |
| `routes/web.php:76` | `GET list_transport` | `CommonController::list_transport` | Method does not exist |
| `routes/web.php:77` | `GET list_item` | `CommonController::list_item` | Method does not exist |
| `routes/web.php:78` | `GET list_color_item` | `CommonController::list_color_item` | Method does not exist |
| `routes/web.php:79` | `GET list_item_type` | `CommonController::list_item_type` | Method does not exist |
| `routes/web.php:80` | `GET list_purchase_items` | `CommonController::list_purchase_items` | Method does not exist |
| `routes/web.php:81` | `GET ajax_script/search_vendor_address` | `CommonController::search_vendor_address` | Method does not exist |
| `routes/web.php:82` | `GET ajax_script/search_customer_ship_address` | `CommonController::search_customer_ship_address` | Method does not exist |
| `routes/web.php:83` | `GET ajax_script/search_item_type` | `CommonController::search_item_type` | Method does not exist |
| `routes/web.php:85` | `GET list_saleOrderNumer` | `CommonController::list_saleOrderNumer` | Method does not exist |
| `routes/web.php:86` | `GET ajax_script/search_customer_addressBilling` | `CommonController::search_customer_addressBilling` | Method does not exist |
| `routes/web.php:87` | `GET ajax_script/search_customer_addressShipping` | `CommonController::search_customer_addressShipping` | Method does not exist |
| `routes/web.php:88` | `GET ajax_script/search_customer_bill_address` | `CommonController::search_customer_bill_address` | Method does not exist |
| `routes/web.php:89` | `GET ajax_script/search_customer_address` | `CommonController::search_customer_address` | Method does not exist |
| `routes/web.php:90` | `GET find_saleOrderNumerByCustomer` | `CommonController::find_saleOrderNumerByCustomer` | Method does not exist |
| `routes/web.php:182` | `GET show-saleorder-workorder-details/{id}` | `SaleOrderController::show_saleorder_work_order_details` | Method does not exist |

Important duplicate: the same `show-saleorder-workorder-details/{id}` URI is also registered at `routes/web.php:131` to the existing `SaleOrderController::showSaleOrderWorkOrderDetails` (`app/Http/Controllers/SaleOrderController.php:382`). The later snake-case registration points to the absent method.

## Missing model classes referenced by controllers

| File/import | Missing class file |
|---|---|
| `app/Http/Controllers/LabTestController.php:6` | `app/Models/LabTest.php` |
| `app/Http/Controllers/LabTestController.php:7` | `app/Models/LabTestResult.php` |
| `app/Http/Controllers/LabTestController.php:8` | `app/Models/LabTestRequest.php` |
| `app/Http/Controllers/LabTestController.php:9` | `app/Models/LabTestStandard.php` |
| `app/Http/Controllers/LabTestController.php:10` | `app/Models/LabRequirement.php` |
| `app/Http/Controllers/LabTestController.php:18` | `app/Models/LabColourFastness.php` |
| `app/Http/Controllers/WorkProcessRequirementController.php:23` | `app/Models/PackagingOrder.php` |
| `app/Http/Controllers/WorkProcessRequirementController.php:25` | `app/Models/PackagingProcessRequirement.php` |
| `app/Http/Controllers/WorkProcessRequirementController.php:26` | `app/Models/WorkPrintProcessRequirement.php` |
| `app/Http/Controllers/WorkProcessRequirementController.php:31` | `app/Models/DyeingPlanningItem.php` |
| `app/Http/Controllers/WorkProcessRequirementController.php:33` | `app/Models/PackagingOrderItem.php` |

Exact error when execution reaches one of these references: PHP/Laravel cannot load the imported `App\Models\...` class (`Class ... not found`).

## Missing relationship target models

| Model/function | Relationship target | Current error |
|---|---|---|
| `app/Models/SaleOrderItem.php:62` — `CwoReason()` | `App\Models\SaleOrderItemPendingReason` (`:64`) | Related class does not exist |
| `app/Models/WorkOrder.php:106` — `WorkOrderItemDetail()` | `App\Models\WorkOrderItemDetail` (`:107`) | Related class does not exist |

Nine key valid relationship contracts are also locked: SaleOrder/customer/items, SaleOrderItem/order, WorkOrder/items/requirements, WorkProcessRequirement/work order, warehouse/compartment, and PurchaseOrder/vendor.

## Missing literal Blade views

| Calling file/function location | Missing view |
|---|---|
| `app/Http/Controllers/LabTestController.php:58,131` | `html.labrequest.print_lab_report` |
| `app/Http/Controllers/LabTestController.php:1543` | `html.labrequest.check_lab_report` |
| `app/Http/Controllers/LabTestController.php:1815,1820` | `pdf.lab-test-report` |
| `app/Http/Controllers/LabTestController.php:1868` | `html.labrequest.show-labrequest` |
| `app/Http/Controllers/LabTestController.php:2119,2165` | `html.labrequest.add-lab-test-result` |
| `app/Http/Controllers/WorkOrderController.php:4389` | `frontend.workorders.show-dyed-workorders` |
| `app/Http/Controllers/WorkPurchaseRequirementController.php:69` | `html.workpurchaserequirements.show-work-purchase-requirement` |

Exact runtime error: `InvalidArgumentException: View [...] not found.`

## Added test files

- `tests/Feature/Regression/AuthenticationGuardBaselineTest.php`
- `tests/Feature/Regression/BusinessFlowRouteBaselineTest.php`
- `tests/Unit/Regression/EncryptedRouteIdBaselineTest.php`
- `tests/Unit/Regression/ModelRelationshipBaselineTest.php`
- `tests/Unit/Regression/CodeIntegritySnapshotTest.php`

## Test results

- Focused regression suite: **16 passed, 423 assertions**, 3.01s.
- Pre-change full suite: **2 passed, 2 assertions**.
- PHP syntax checks: all five new test files passed.
- Final full suite: **18 passed, 425 assertions**, 0.93s.

Environment: Laravel 13.20.0, PHPUnit 12.5.31, PHP 8.5.8.

## Commands used

```powershell
php artisan route:list --json
php artisan test --compact
php artisan test --compact tests\Feature\Regression tests\Unit\Regression
php artisan --version
vendor\bin\phpunit --version
php -v
php -l <each-added-test-file>
rg -n <route/controller/model/view audit patterns> routes app resources
git status --short --untracked-files=all
```

No `migrate`, `db:seed`, write-oriented Artisan, or old-project mutation command was used.

## Flows not fully executable and reasons

1. Sale Order, Work Order, warehouse inward, purchase receipt, allotment, department receipt/return, mill dispatch/receive, printing decision, Purchase Order and admin CRUD were not submitted end-to-end. They are write operations and the isolated `sqlite :memory:` test environment has no complete ERP schema or representative fixture graph. Running them against `.env` MySQL would mutate operational data, which is outside this task.
2. Mill partial-receive quantities and both printing before/after branches require valid linked work orders, stocks, departments and process records; these fixtures do not exist in the isolated test DB.
3. Authenticated valid-record direct URL authorization could not be characterized without persisted users, permissions and domain records. Guest access and malformed encryption behavior are covered.
4. Browser/UI click-through was not run because the available browser skill requires a `node_repl`/`js` connection, which was not available in this session. Framework-level HTTP rendering/redirect checks were used where they were safe.
5. Known broken routes, model references, relationships and views were reported and snapshot-tested only, per instruction; none were repaired or executed beyond their failure boundary.
