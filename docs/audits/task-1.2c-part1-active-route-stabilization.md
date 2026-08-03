# Task 1.2C-Part 1 — Active Route Stabilization

Date: 2026-08-03  
Project: `E:\projects\blackgrd`  
Reference only: `E:\xampp\htdocs\erp`

## Scope and safety

This task reviewed the 17 missing-method route failures recorded in Task 1.1. No migration, schema command, foreign-key change, status conversion, data repair, missing model, relationship, or Blade page was added. The old ERP was read-only. Live database inspection was read-only. Test fixture writes were confined to the allow-listed `blackgrd_schema_testing` database and wrapped in transactions.

The live connection remained `mysql / blackgrd`. `php artisan db:safety-check` returned the required `BLOCKED` result because `blackgrd` is not allow-listed.

## 17-route classification matrix

All routes were `GET` routes under `auth:web,admin`, except the Sale Order details route, which is under `auth:web`.

| # | URI / route name | Missing target at Task 1.1 | Current caller and purpose | Old ERP reference / current schema | Classification and action |
|---:|---|---|---|---|---|
| 1 | `list_dyeing` / `list_dyeing` | `CommonController::list_dyeing` | No current Blade, JS, menu, or controller caller. | Old `CommonController.php:509` queried the old dyeing entity. Current database has no `dyings`/`dyeings` table and the current project has no compatible model. | **Later-phase module / old ERP legacy.** Active route removed; implementation deferred because its data dependency does not exist. |
| 2 | `list_employee` / `list_employee` | `CommonController::list_employee` | Actively called by Work Order create, department receipt/report/inspection, and manual warehouse inward pages. The UI consumes `id`, `name`, and sometimes `gstin`. | Old `CommonController.php:346` supplied employee autocomplete data. Current `individuals.id` and `type=employee`, `status=Active` are used. | **Required and actively used.** Implemented with bounded, injection-safe search and the required JSON keys only. |
| 3 | `list_transport` / `list_transport` | `CommonController::list_transport` | No current caller. | Old `CommonController.php:2097` queried transport individuals. Current `individuals.type=transport` can represent it, but no active current feature requests this endpoint. | **Old ERP legacy / unused.** Active route removed. |
| 4 | `list_item` / `list_item` | `CommonController::list_item` | Actively called by Work Order create, department receipt, and Job Mill warehouse stock pages. The current scripts consume `item_id`, `item_name`, and `item_code`. | Old `CommonController.php:386` returned a much wider object and relationships. Current `items.item_id`, string fields, and `status=Active` are used. | **Required and actively used.** Implemented with grouped search, a 20-row limit, and only the verified JSON fields. |
| 5 | `list_color_item` / `list_color_item` | `CommonController::list_color_item` | No current caller. | Old `CommonController.php:417` filtered item type 9 for legacy colour-item selection. Current colour/master flows use other routes. | **Old ERP legacy / unused.** Active route removed. |
| 6 | `list_item_type` / `list_item_type` | `CommonController::list_item_type` | No current caller. | Old `CommonController.php:461` accepted a legacy `type` parameter. Current screens use `fabric_list_item` or other module-specific routes. | **Old ERP legacy / unused.** Active route removed. |
| 7 | `list_purchase_items` / `list_purchase_items` | `CommonController::list_purchase_items` | No current caller. | Old `CommonController.php:363` joined purchase and purchase-item data using the old purchase-receipt flow. | **Old ERP legacy / unused.** Active route removed; no replacement behavior invented. |
| 8 | `ajax_script/search_vendor_address` / unnamed | `CommonController::search_vendor_address` | No current caller. | Old `CommonController.php:552` emitted raw vendor billing-address HTML. Current pages use different address endpoints/contracts. | **Old ERP legacy / unused.** Active route removed. |
| 9 | `ajax_script/search_customer_ship_address` / unnamed | `CommonController::search_customer_ship_address` | Actively called by printing-requirement acceptance, Job Mill warehouse stock, and mill dispatch stock pages. Scripts inject an HTML fragment and require `ind_add_id_ship`, `shiping_address`, and `calcAddresss(stateId)`. | Old `CommonController.php:619` established the HTML contract. Current `individual_address.ind_add_id`, `address_type=s`, `states.id/name`, and enum `status=Active` are used. | **Required and actively used.** Implemented with integer validation, active filtering, default-first order, output escaping, and the verified HTML contract. |
| 10 | `ajax_script/search_item_type` / unnamed | `CommonController::search_item_type` | No current caller. | Old `CommonController.php:611` is an empty stub and returns no real business response. | **Dead/unused route.** Active route removed. |
| 11 | `list_saleOrderNumer` / `list_saleOrderNumer` | `CommonController::list_saleOrderNumer` | Actively called by `frontend/workorder/add-workorder.blade.php`. Its autocomplete requires `sale_order_id` and `sale_order_number`. | Old `CommonController.php:538` searched the old `sale_order_id` field. Current table uses `sale_orders.id`; the query aliases it to `sale_order_id` and filters current `status=Active`. | **Required and actively used.** Implemented with the exact two-key JSON contract and a 10-row limit. |
| 12 | `ajax_script/search_customer_addressBilling` / unnamed | `CommonController::search_customer_addressBilling` | No current caller. | Old `CommonController.php:647` is an older billing fragment variant. The actively used billing endpoint is `search_customer_bill_address`. | **Duplicate legacy endpoint / unused.** Active route removed. |
| 13 | `ajax_script/search_customer_addressShipping` / unnamed | `CommonController::search_customer_addressShipping` | No current caller. | Old `CommonController.php:674` is an older shipping fragment variant with a different field-name contract. | **Duplicate legacy endpoint / unused.** Active route removed. |
| 14 | `ajax_script/search_customer_bill_address` / unnamed | `CommonController::search_customer_bill_address` | Actively called by printing-requirement acceptance, Job Mill warehouse stock, and mill dispatch stock pages. Scripts require `state`, `ind_add_id`, `address`, and `calcAddress(stateId)`. | Old `CommonController.php:581` established the HTML contract. Current `companies.state_id`, `individual_address`, `states`, and enum statuses are used. | **Required and actively used.** Implemented using the same safe shared address renderer as shipping. |
| 15 | `ajax_script/search_customer_address` / unnamed | `CommonController::search_customer_address` | No current caller. | No corresponding controller method was found in the old or current ERP. Current code already has the JSON `customer-addresses` endpoint for the supported generic-address use case. | **Dead/unused route.** Active route removed. |
| 16 | `find_saleOrderNumerByCustomer` / `find_saleOrderNumerByCustomer` | `CommonController::find_saleOrderNumerByCustomer` | No current caller. | Old `CommonController.php:2234` used old Sale Order/customer fields for amendments. Current `sale_orders` uses `id` and `customer_id`, but no current UI requests this endpoint. | **Old ERP legacy / unused.** Active route removed. |
| 17 | `show-saleorder-workorder-details/{id}` / conflicting names | Later duplicate called missing `SaleOrderController::show_saleorder_work_order_details` | No active current Blade calls it. An archived underscored Work Order Blade calls the name `show-saleorder-workorder-details`. | The earlier current route already targeted public `showSaleOrderWorkOrderDetails()` (`SaleOrderController.php:382`). It explicitly redirects with “not ready” instead of a dummy success. The old full method at `SaleOrderController.php:1023` depends on old columns, missing packaging models, and an absent `html.saleorder.show-saleorder-workorder-details` view. | **Duplicate/conflicting route; feature required but incomplete.** Removed the shadowing registration, retained one URI, preserved the currently referenced route name, and targeted the existing public method. Full feature was not ported. |

## Implemented current-schema contracts

### `list_employee`

- Queries only `individuals.type=employee` and `status=Active`.
- Searches `name`, `email`, and `gstin` through parameter-bound Eloquent conditions.
- Returns at most 10 rows with `id`, `name`, and `gstin`.

### `list_item`

- Queries only `items.status=Active`.
- Searches `item_name`, `item_code`, `internal_item_name`, and `hsncode` in a grouped condition.
- Returns at most 20 rows with `item_id`, `item_name`, and `item_code`.

### `list_saleOrderNumer`

- Queries only active, numbered `sale_orders`.
- Searches both the current numeric `id` (the value typed into the caller's `sale_order_id` field) and `sale_order_number`.
- Uses current primary key `id` and returns it as the frontend-required `sale_order_id`.
- Returns at most 10 rows with exactly `sale_order_id` and `sale_order_number`.

### Billing and shipping address fragments

- Validate `individualId` as a positive integer; AJAX invalid input returns HTTP 422 validation JSON.
- Query `individual_address` with current `address_type` (`b`/`s`) and `status=Active`, joining `states` for its display name.
- Preserve the old, currently consumed form-field/callback contract.
- Escape every database value before inserting it into HTML; integration tests include script-tag input.
- No model relationship or schema change was introduced.

No company/tenant ownership column exists on these records. Existing route-level dual-guard authorization (`auth:web,admin`) was retained, and the tests verify both guards.

## Sale Order duplicate resolution

Before stabilization, the source registered the same `GET /show-saleorder-workorder-details/{id}` URI twice:

1. `showSaleOrderWorkOrderDetails`, name `saleorders.workorder-details`.
2. Later shadowing route to missing `show_saleorder_work_order_details`, name `show-saleorder-workorder-details`.

The later registration won at runtime and caused the Task 1.1 failure. The result now has exactly one source registration and one runtime route:

```text
GET show-saleorder-workorder-details/{id}
name: show-saleorder-workorder-details
action: App\Http\Controllers\SaleOrderController@showSaleOrderWorkOrderDetails
middleware: auth:web
```

The route currently returns the controller's explicit unavailable redirect for both ordinary and malformed/encrypted ID strings. This avoids a 500 and does not claim that the absent details page is functional. Full implementation remains pending on the missing models/view and a separately verified current-schema design.

## Deferred dependencies

- `list_dyeing`: no compatible current table/model; later-phase dyeing module work is required before an endpoint can be defined.
- Full Sale Order work-order details page: old code requires legacy field mappings, packaging-related models recorded as missing by Task 1.1, and a missing Blade view. These were expressly outside this task.
- No other removed route has a current caller. Their old behavior was therefore not recreated merely to make route resolution pass.

## Tests added or updated

- `tests/Unit/Regression/ActiveRouteStabilizationContractTest.php`
  - required route/action resolution and public methods;
  - expected authentication middleware and guest blocking;
  - removal of 11 unsupported legacy URIs;
  - exactly one Sale Order details registration/action/name;
  - valid and malformed details IDs return the explicit unavailable redirect.
- `tests/Feature/Regression/ActiveRouteResponseTest.php`
  - runs only on allow-listed `blackgrd_schema_testing`, otherwise skips/refuses live DB;
  - user and admin guard access;
  - active/inactive filtering;
  - exact autocomplete JSON fields;
  - billing/shipping HTML fields, state callbacks, escaping, and invalid-ID 422 responses;
  - all fixture writes are transaction-scoped.
- `tests/Unit/Regression/CodeIntegritySnapshotTest.php`
  - active missing-controller/method baseline changed from the Task 1.1 list to an empty list.

## Verification results

| Check | Result |
|---|---|
| PHP lint, changed controller and test files | PASS |
| `php artisan route:list` | PASS — 291 routes |
| Route structural/snapshot tests after cache build | PASS — 9 tests, 63 assertions |
| Disposable MySQL response tests | PASS — 4 tests, 37 assertions |
| Full default test suite | PASS — 40 passed, 9 skipped, 517 assertions |
| Skips | MySQL-only response/FK integration tests under the default SQLite test environment; the response suite passed separately on disposable MySQL |
| `php artisan db:safety-check` on live config | Expected BLOCKED — connected database `blackgrd` is not allow-listed |
| Disposable safety check | ALLOWED for exactly `blackgrd_schema_testing`; destructive confirmation remained NOT ARMED |
| `php artisan route:cache` | PASS |
| Tests against cached routes | PASS |
| `php artisan route:clear` | PASS; final route cache state is cleared |
| `git diff --check` | PASS |

An initial focused test run intentionally exposed that a pre-existing route cache still contained the old route map. `route:clear` removed it; all source and cached-route tests then passed. This is why the final verification includes both cache build and cache clear.

## Commands executed

Source/report audit (read-only; `rg` patterns were also repeated for individual endpoint names and caller files):

```powershell
Get-Content docs\audits\task-1.1-regression-baseline.md
Get-Content docs\audits\task-1.2a-schema-contract-audit.md
Get-Content docs\audits\task-1.2b-part1-verified-schema-repair.md
Get-Content docs\audits\task-1.2b-part2a-foreign-key-audit.md
Get-Content docs\audits\task-1.2b-part2c-live-foreign-key-apply.md
rg -n "list_dyeing|list_employee|list_transport|list_item|list_color_item|list_item_type|list_purchase_items|search_vendor_address|search_item_type|list_saleOrderNumer|search_customer_address|find_saleOrderNumerByCustomer|show-saleorder-workorder-details" routes app resources tests -S
rg -n "<endpoint patterns>" E:\xampp\htdocs\erp\app E:\xampp\htdocs\erp\routes E:\xampp\htdocs\erp\resources -S
Get-Content routes\web.php
Get-Content app\Http\Controllers\CommonController.php
Get-Content app\Http\Controllers\SaleOrderController.php
git status --short
git log --oneline -5
git diff --stat
```

Schema checks were SELECT/read-only inspections of `information_schema`, current table columns/status values/counts, models, and migrations. No schema statement was executed.

Verification commands:

```powershell
E:\php85\php.exe -l app\Http\Controllers\CommonController.php
E:\php85\php.exe -l tests\Unit\Regression\ActiveRouteStabilizationContractTest.php
E:\php85\php.exe -l tests\Feature\Regression\ActiveRouteResponseTest.php
E:\php85\php.exe artisan route:clear
E:\php85\php.exe artisan test --compact --do-not-cache-result tests\Unit\Regression\ActiveRouteStabilizationContractTest.php tests\Unit\Regression\CodeIntegritySnapshotTest.php
E:\php85\php.exe artisan db:safety-check
E:\php85\php.exe artisan route:list
E:\php85\php.exe artisan route:cache
E:\php85\php.exe artisan test --compact --do-not-cache-result tests\Unit\Regression\ActiveRouteStabilizationContractTest.php tests\Unit\Regression\CodeIntegritySnapshotTest.php
E:\php85\php.exe artisan route:clear
E:\php85\php.exe artisan test --compact --do-not-cache-result
git diff --check
git status --short
```

Disposable response verification used process-local environment overrides only:

```powershell
$env:DB_CONNECTION='mysql'
$env:DB_DATABASE='blackgrd_schema_testing'
E:\php85\php.exe artisan db:safety-check
E:\php85\php.exe artisan test --compact --do-not-cache-result tests\Feature\Regression\ActiveRouteResponseTest.php
```

No `migrate`, `migrate:fresh`, rollback, wipe, `ALTER`, `CREATE`, `DROP`, or other database schema command was run.

## Changed files and review state

- Modified: `app/Http/Controllers/CommonController.php`
- Modified: `routes/web.php`
- Modified: `tests/Unit/Regression/CodeIntegritySnapshotTest.php`
- Added: `tests/Unit/Regression/ActiveRouteStabilizationContractTest.php`
- Added: `tests/Feature/Regression/ActiveRouteResponseTest.php`
- Added: `docs/audits/task-1.2c-part1-active-route-stabilization.md`

All changes are intentionally uncommitted for review.
