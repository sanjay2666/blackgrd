# Task 1.3A — Status and State Vocabulary Audit

Audit date: 2026-08-03  
Project: `E:\projects\blackgrd`  
Live database inspected read-only: `mysql / blackgrd`  
Old ERP reference inspected read-only: `E:\xampp\htdocs\erp`

## 1. Executive summary

This audit found **116 status-bearing fields** in the live schema under a documented, state-focused definition: 56 columns named exactly `status`, 8 other fields ending in `_status`, 2 `insp_work_status_process` fields, and 50 boolean/enum flags that represent record or workflow state. Configuration facts such as `items.is_jobwork`, `items.is_outsourced`, and `item_type.is_purchase` are not counted because they describe capability/type rather than a state transition.

The live database contains **14 distinct non-null raw values** across those fields (`0`, `1`, `2`, `Active`, `Inactive`, `Deleted`, `Complete`, `Completed`, `Pending`, `Yes`, `No`, `accepted`, `yes`, `no`) plus `NULL`. The union of schema-allowed and observed values is **22 case-sensitive raw terms**. Code adds the labels/values `Accepted`, `Denied`, and `Defective`, producing **25 terms in the verified effective vocabulary**. Case differences are deliberately counted because the application currently treats them as different contracts.

The main architectural defect is not merely spelling. One generic-looking `status` name represents at least four meanings: master-record availability, soft deletion, department-return decision, and login-attempt outcome. Major documents such as Sale Order and Purchase Order have no document lifecycle or approval state; their `status` is only `Active/Inactive/Deleted`. Work execution is spread across overlapping flags, dates, quantities, `work_status`, and `insp_status`.

The most urgent compatibility defect is numeric access to string enums. A fixed-string source audit found 72 `status=1/0`-shaped occurrences in `app`; 4 are JSON response flags, leaving **68 persistence/query occurrences**. They are concentrated in `WorkProcessRequirementController` (47), dormant `LabTestController` (10), `StockMillReturn*` relationships (7), `WorkOrderController` (2), and `CommonController` (2). Several models currently normalize numeric `1` writes to `Active`, but numeric reads and models without that mutator still depend on MySQL ENUM index coercion.

No migration, database write, status normalization, enum implementation, controller refactor, route/model/view change, foreign key change, or old-ERP change was made. The only product artifact from this task is this report.

## 2. Current status-field inventory

### 2.1 Scope and notation

- `R` = `enum('Active','Inactive','Deleted')`, required, default `Active`.
- `D` = `enum('pending','accepted','rejected')`, required, default `pending`.
- `B1` = `tinyint(1)`, required, default `1`.
- Live values use `value:row-count`; `—` means the table has no rows.
- “Surface” identifies the verified controller/query and Blade/UI family. “No active UI” means none was found, not that the field is unused by every possible external consumer.

### 2.2 Exact `status` columns (56)

| # | Table | Model | Contract | Live values | Meaning and verified surface |
|---:|---|---|---|---|---|
| 1 | `agents` | none found | R | — | Master record; no active controller/UI found |
| 2 | `all_pages` | `AllPage` | B1 | — | Permission/page enablement; `Admin\\AllPageController`, admin page UI uses `0/1` |
| 3 | `colours` | `Colour` | R | Active:3 | Master record; `Admin\\ColourController`, admin CRUD select/list |
| 4 | `companies` | `Company` | R | Active:1 | Master record; `Admin\\CompanyController`, admin CRUD select/list |
| 5 | `cotings` | `Coting` | R | Active:7 | Master record only; `Admin\\CotingController`, admin CRUD. It must not decide process sequence |
| 6 | `couriers` | `Courier` | R | — | Record status; `Admin\\CourierController`, admin CRUD |
| 7 | `departments` | `Department` | R | Active:3 | Master record; `Admin\\DepartmentController`, admin CRUD |
| 8 | `department_returns` | `DepartmentReturn` | D | accepted:2 | Return decision; `WarehouseItemController`, warehouse return pages |
| 9 | `department_return_requests` | `DepartmentReturnRequest` | D | accepted:2 | Return-line decision; `WarehouseItemController` and Work Order relationships/pages |
| 10 | `fabric_fault_reasons` | `FabricFaultReason` | R | Active:114 | Master record; Work Order inspection queries/UI |
| 11 | `gate_passes` | `GatePass` | R | Active:4, Deleted:1 | Record status, not gate-pass lifecycle; `WorkOrderController`, gate-pass/inspection UI |
| 12 | `gate_pass_print_logs` | none found | R | Active:6 | Print-log record status; direct DB insert in `WorkOrderController`, no status UI |
| 13 | `greige_receive_stock_item_from_job_works` | `GreigeReceiveStockItemFromJobWorks` | R | — | Receipt-row record status; `JobMillWorkController`, no independent lifecycle UI |
| 14 | `gst_rates` | `GstRate` | R | — | Master record; `Admin\\GstRateController`, admin CRUD |
| 15 | `individuals` | `Individual` | R | Active:645 | Customer/vendor/employee/master record availability; admin individual and authentication/common queries |
| 16 | `individual_address` | `IndividualAddress` | R | Active:400 | Address record availability; individual relationships/UI |
| 17 | `items` | `Item` | R | Active:589 | Master record; admin item and production/warehouse selectors |
| 18 | `item_type` | `ItemType` | R | Active:9 | Master record; admin and production/purchase selectors |
| 19 | `item_yarn_requirements` | `ItemYarnRequirement` | R | Active:713, Inactive:131 | Requirement-master availability; admin CRUD and Work Order calculations |
| 20 | `login_attempts` | `LoginAttempt` | `enum(attempt,success,failed)`, required, default `attempt` | — | Authentication event outcome, not record status; LoginAttempt controller/admin list |
| 21 | `machines` | `Machine` | R | Active:10 | Master availability; `Admin\\MachineController`, admin machine UI |
| 22 | `notifications` | `Notification` | R | — | Notification record availability; `Admin\\NotificationController`, admin notification UI |
| 23 | `packaging_types` | `PackagingType` | R | Active:2 | Master record; admin CRUD |
| 24 | `process_items` | `ProcessItem` | R | Active:7, Inactive:1 | Process-master availability; admin CRUD and production selectors |
| 25 | `process_requirements` | `ProcessRequirement` | R | Active:5 | Process requirement configuration; Work Order generation queries |
| 26 | `purchases` | `Purchase` | R | — | Receipt document record status only; `PurchaseController`, purchase pages |
| 27 | `purchase_items` | `PurchaseItem` | R | — | Receipt-line record status; `PurchaseController`, purchase pages |
| 28 | `purchase_orders` | `PurchaseOrder` | R | Active:3 | Record status only; `PurchaseOrderController`, PO pages; no document lifecycle |
| 29 | `purchase_order_items` | `PurchaseOrderItem` | R | Active:5 | Record status only; `PurchaseOrderController`, PO pages |
| 30 | `reasons` | `Reason` | R | Active:2 | Master record; reason queries/UI |
| 31 | `receive_stock_mill_dispatches` | `ReceiveStockMillDispatch` | R | — | Job-work receipt record status; `JobMillWorkController`, receipt/challan pages |
| 32 | `receive_stock_mill_dispatch_items` | `ReceiveStockMillDispatchItem` | R | — | Job-work receipt-line record status; JobMillWork relationships/pages |
| 33 | `sale_orders` | `SaleOrder` | R | Active:1 | Record status only; `SaleOrderController`, sale-order pages; no document lifecycle |
| 34 | `sale_order_items` | `SaleOrderItem` | R | Active:2 | Record status; cancellation currently writes `Deleted`; sale-order pages/reports |
| 35 | `states` | `State` | R | Active:42 | Geographic master record; admin state/address selectors |
| 36 | `stock_mill_dispatches` | `StockMillDispatch` | R | — | Job-work dispatch record status only; `JobMillWorkController`, dispatch pages |
| 37 | `stock_mill_dispatch_items` | `StockMillDispatchItem` | R | — | Dispatch-line record status; JobMillWork relationships/pages |
| 38 | `stock_mill_returns` | `StockMillReturn` | R | — | Job-work return record status; numeric relation filters are incompatible |
| 39 | `stock_mill_return_items` | `StockMillReturnItem` | R | — | Return-line record status; numeric relation filters are incompatible |
| 40 | `unit_type` | `UnitType` | R | Active:2, Deleted:2 | Master record; admin CRUD and selectors |
| 41 | `users` | `User` | R | Active:2 | Account availability/authentication; auth controllers and user/admin surfaces |
| 42 | `user_web_pages` | `UserWebPage` | R | — | User/page assignment record status; admin permission UI |
| 43 | `warehouses` | `Warehouse` | R | Active:7, Inactive:1 | Warehouse master availability; admin CRUD and warehouse/production selectors |
| 44 | `warehouse_balance_items` | `WarehouseBalanceItem` | R | Active:27 | Ledger-row record status, not balance availability; warehouse/production queries |
| 45 | `warehouse_compartments` | `WarehouseCompartment` | R | Active:186, Inactive:39 | Location master availability; admin CRUD and stock UI |
| 46 | `warehouse_in_items` | `WarehouseItem` | R | Active:17 | Warehouse receipt-row record status; `WarehouseItemController`, stock pages |
| 47 | `warehouse_item_stocks` | `WarehouseItemStock` | R | Active:17 | Stock-unit record status; warehouse, work order, job work pages |
| 48 | `warehouse_item_stock_files` | `WarehouseItemStockFile` | R | Active:11 | Stock attachment record status; warehouse relations/UI |
| 49 | `warehouse_out_items` | `WarehouseOutItem` | R | Active:8, Deleted:2 | Issue-row record status; warehouse/work-process pages |
| 50 | `work_inspections` | `WorkInspection` | R | Active:4, Deleted:1 | Inspection record status, separate from outcome; Work Order inspection UI |
| 51 | `work_inspection_details` | `WorkInspectionDetail` | R | Active:8, Deleted:1 | Inspection-detail record status; Work Order inspection UI |
| 52 | `work_orders` | `WorkOrder` | R | Active:5, Inactive:1, Deleted:3 | Record status only; Work Order controller/list/UI |
| 53 | `work_order_items` | `WorkOrderItem` | R | Active:8, Inactive:1 | Record status only; Work Order controller/list/UI |
| 54 | `work_process_received_items` | model not found | R | Active:5 | Process receipt record status; work-process queries; no dedicated UI found |
| 55 | `work_process_requirements` | `WorkProcessRequirement` | R | Active:12 | Requirement record status, not acceptance; WorkProcessRequirement/WorkOrder UI |
| 56 | `work_purchase_requirements` | `WorkPurchaseRequirement` | R | Active:7 | Purchase-requirement record status; requirement controller/work-order UI |

### 2.3 Other state-bearing fields (60)

| Table.field | Type / null / default | Live values | Current meaning and surface | Recommended future concept |
|---|---|---|---|---|
| `couriers.is_msg_send` | enum Yes/No, required, No | — | Initial message sent; Courier controller/UI fields | `message_delivery_status` or immutable event |
| `couriers.is_track_msg_send` | enum Yes/No, required, No | — | Tracking message sent | Separate delivery status/event |
| `couriers.is_deleted` | int, nullable, NULL | — | Duplicate hidden record-deletion flag | Retire after `record_status` reconciliation |
| `department_returns.is_deleted` | enum 0/1, required, 0 | 0:2 | Duplicate record deletion beside decision `status` | One record-retirement mechanism |
| `gate_passes.is_item_received_in_warehouse` | enum Yes/No, required, No | Yes:3, No:2 | Warehouse receipt marker; WorkOrderController/gate-pass UI | Inventory movement `Posted/Received` transition |
| `gate_passes.is_deleted` | bool nullable, 0 | 0:4, 1:1 | Duplicate deletion; live rows align with `status` today | One record-retirement mechanism |
| `individuals.is_verified` | enum yes/no, required, no | yes:245, no:400 | Verification/approval-like state; admin individual UI | `verification_status: Pending/Verified/Rejected` |
| `machines.is_busy` | enum 1/0, nullable, NULL | 0:10 | Busy flag; admin machine UI labels 0=No, 1=Yes | `machine_status` state machine |
| `notifications.is_read` | bool required, 0 | — | Read/unread; Notification controller checkbox | Keep boolean or `read_at`; not record status |
| `office_ips.is_active` | bool required, 1 | 1:1 | Master availability; OfficeIp admin UI | `record_status` or boolean cast |
| `purchase_orders.is_all_item_received` | enum Yes/No, required, No | No:3 | Header receipt completion | Derived receipt status: Not/Partial/Fully Received |
| `purchase_orders.is_item_received_in_warehouse` | enum Yes/No, required, No | Yes:2, No:1 | Warehouse receipt marker | Inventory document/receipt status |
| `purchase_orders.is_deleted` | enum Yes/No, required, No | No:3 | Duplicate deletion beside `status` | One record-retirement mechanism |
| `purchase_orders.is_return` | enum Yes/No, required, No | No:3 | Return marker | Separate return document/link, not PO lifecycle |
| `purchase_order_items.is_item_received_in_warehouse` | enum 0/1, required, 0 | 0:2, 1:3 | Line receipt marker; PO/Warehouse controllers and UI | Line receipt `Pending/Partial/Fully Received` |
| `purchase_order_items.is_deleted` | bool nullable, 0 | 0:5 | Duplicate deletion | One record-retirement mechanism |
| `purchase_order_items.is_return` | bool nullable, 0 | 0:5 | Return marker | Return quantity/document state |
| `receive_stock_mill_dispatches.is_pe_completed` | bool required, 0 | — | Undocumented “PE” completion | Manual decision; expand name and lifecycle |
| `sale_orders.is_return` | enum Yes/No, required, No | No:1 | Return marker, not order lifecycle | Separate return/RMA document |
| `sale_order_items.is_deleted` | bool required, 0 | 0:2 | Duplicate deletion beside `status` | One record-retirement mechanism |
| `sale_order_items.is_return` | bool required, 0 | 0:2 | Return marker | Return document/quantity state |
| `sale_order_items.is_work_order_created` | bool required, 0 | 0:1, 1:1 | Work-order generation marker; SaleOrder/WorkOrder queries/UI | Derived `planning_status` |
| `sale_order_items.is_work_completed` | bool nullable, NULL | NULL:1, 1:1 | Ambiguous three-state completion | `execution_status`, no hidden NULL state |
| `sale_order_items.is_work_final_completed` | enum 0/1, required, 0 | 0:2 | Final production completion | `execution_status=Completed` |
| `sale_order_items.is_work_final_dlvr_completed` | enum 0/1, required, 0 | 0:2 | Delivery clearance/completion | `dispatch_status` or fulfillment status |
| `sale_order_items.is_packaging_done` | enum 0/1, required, 0 | 0:2 | Packaging completion; WorkOrderController and reports | Packaging document/execution status |
| `stock_mill_dispatches.is_tot_mtr_received` | bool required, 0 | — | Header fully-received marker; JobMillWork filters/updates | Job-work receipt status with partial state |
| `stock_mill_dispatch_items.is_item_received_in_warehouse` | bool required, 0 | — | Dispatch-line fully-received marker | Job-work line receipt status |
| `stock_mill_returns.is_tot_mtr_received` | bool required, 0 | — | Return header fully received | Job-work return receipt status |
| `stock_mill_return_items.is_item_received_in_warehouse` | bool required, 0 | — | Return-line receipt marker | Job-work line receipt status |
| `warehouse_balance_items.balance_status` | tinyint, required, 1 | 0:17, 1:10 | Open/usable balance row versus consumed/closed; production queries update 1→0 | Ledger allocation/balance state, documented enum |
| `warehouse_in_items.is_updated` | tinyint, required, 0 | 0:17 | Undocumented update/processing flag | Manual decision; replace with explicit posting state if applicable |
| `warehouse_item_stocks.is_allotted_stock` | enum Yes/No, required, No | Yes:4, No:13 | Allocation state; warehouse/work-order queries/UI | `allocation_status: Available/Reserved/Issued` |
| `warehouse_item_stocks.is_item_returned` | enum Yes/No, required, No | No:17 | Return state | Inventory movement/return status |
| `warehouse_out_items.is_item_return_whouse` | enum 0/1, required, 0 | 0:8, 1:2 | Returned-to-warehouse marker; typo in field name | Return movement status |
| `work_inspections.insp_work_status` | varchar(22), nullable, NULL | Completed:5 | Inspection work outcome; controller accepts Completed/Defective in one path | `inspection_result: Passed/Failed/Rework` |
| `work_inspections.insp_work_status_process` | varchar(22), nullable, NULL | No:5 | Whether process is complete (Yes/No) | Remove from outcome; derive execution transition |
| `work_inspections.insp_status` | varchar(555), nullable, NULL | Pending:5 | Inspection completion state | `inspection_status: Pending/In Progress/Completed` |
| `work_inspections.is_warehouse_accepted` | enum Yes/No, required, No | Yes:3, No:2 | Warehouse acceptance | Inventory handoff/receipt transition |
| `work_inspections.is_item_received_in_warehouse` | enum Yes/No, required, No | Yes:1, No:4 | Warehouse receipt | Inventory movement status |
| `work_inspections.is_deleted` | bool required, 0 | 0:4, 1:1 | Duplicate deletion; live aligns with `status` | One record-retirement mechanism |
| `work_inspection_details.insp_work_status_process` | varchar(22), nullable, NULL | No:9 | Copied Yes/No process-completion flag | Derived snapshot/event, not free text |
| `work_inspection_details.work_status` | varchar(11), nullable, NULL | Completed:9 | Outcome; code also permits `Defective` | `inspection_result` enum |
| `work_orders.insp_status` | enum Complete/Pending, required, Pending | Complete:1, Pending:8 | Inspection/execution completion filter | Separate inspection status from execution |
| `work_orders.is_warehouse_accepted` | enum Yes/No, required, No | Yes:2, No:7 | Warehouse acceptance | Inventory handoff status |
| `work_orders.is_item_received_in_warehouse` | enum Yes/No, required, No | No:9 | Warehouse receipt marker | Inventory receipt status |
| `work_orders.is_work_require_request_accepted` | enum Yes/No, nullable, NULL | NULL:2, Yes:4, No:3 | Requirement acceptance; NULL is hidden state | Explicit `Not Requested/Pending/Accepted/Rejected` |
| `work_orders.is_gatepass_genrated_by_warehouse` | enum Yes/No, required, Yes | Yes:9 | Gate-pass generated marker; surprising default Yes | Gate-pass document relation/state |
| `work_orders.is_item_received_from_warehouse` | enum Yes/No, required, No | Yes:4, No:5 | Material received by process | Execution material-handoff status |
| `work_orders.work_status` | enum Complete/Pending, required, Pending | Complete:1, Pending:8 | Overall execution completion, often updated with `insp_status` | Rich `execution_status` state machine |
| `work_order_items.is_work_completed` | enum 0/1, required, 0 | 0:8, 1:1 | Line execution completion | Derived line `execution_status` |
| `work_process_requirements.is_pro_acc_by_warehouse` | enum Yes/No, nullable, NULL | NULL:11, Yes:1 | Compatibility accessor also derives it from `is_accept`; conflicting source of truth | Retire or define warehouse acceptance separately |
| `work_process_requirements.is_accept` | tinyint, required, 0; comment 0 Pending/1 Accepted/2 Denied | 1:6, 2:6 | Requirement decision; WorkProcessRequirement/WorkOrder/Warehouse UI | `request_status: Pending/Accepted/Rejected` |
| `work_process_requirements.is_jw_generated_by_warehouse` | enum Yes/No, required, No | No:12 | Job-work document generated marker | Job-work order relation/state |
| `work_process_requirements.is_lab_test_complete` | enum Yes/No, required, No | No:12 | Lab completion marker | Derive from lab request/result lifecycle |
| `work_process_requirements.lab_req_status` | enum Pending/Requested/Approved/Rejected, required, Pending | Pending:12 | Lab approval/request lifecycle | Dedicated lab request status; extend only after module decision |
| `work_process_requirements.insp_status` | enum Pending/Complete, required, Pending | Pending:12 | Requirement inspection completion | `inspection_status` |
| `work_process_requirements.is_item_returned` | enum Yes/No, required, No | No:12 | Partial/any return marker unclear | Return quantity/status |
| `work_process_requirements.is_all_item_returned` | enum Yes/No, required, No | No:12 | Full return marker | `return_status: None/Partial/Complete` |
| `work_purchase_requirements.is_purchase_order_created` | bool required, 0 | 0:7 | PO creation marker | Derived procurement status |

## 3. Distinct live database values

All queries were `SELECT` statements through the existing Laravel connection. No value was normalized or updated.

| Raw value | Rows across audited fields | Notes |
|---|---:|---|
| `Active` | 2,899 | Record availability, not business execution |
| `Inactive` | 174 | Record availability |
| `Deleted` | 10 | Soft-deletion encoded inside record status |
| `Pending` | 45 | Work/inspection/lab contexts |
| `Complete` | 2 | Work Order execution/inspection |
| `Completed` | 14 | Inspection header/detail outcome |
| `accepted` | 4 | Department returns, lower-case |
| `Yes` | 33 | Many unrelated workflow flags |
| `No` | 135 | Many unrelated workflow flags |
| `yes` | 245 | Individual verification, lower-case |
| `no` | 400 | Individual verification, lower-case |
| `0` | 100 | Boolean/enum flags |
| `1` | 27 | Boolean/enum flags |
| `2` | 6 | `work_process_requirements.is_accept = Denied` |
| `NULL` | 14 | Hidden state in nullable status flags |

Twenty-five of 116 fields are in empty tables. Schema-allowed but currently unused terms include `attempt`, `success`, `failed`, lower-case `pending`/`rejected`, `Requested`, `Approved`, and `Rejected`. Code-only or code-label terms include `Accepted`, `Denied`, and `Defective`.

## 4. Numeric/string conflicts

1. **String enum versus numeric filters.** Fifty-six `status` columns are not uniformly typed: 55 use strings except `all_pages.status`. Yet 68 persistence/query sites use numeric `1/0` against tables whose schema says `Active/Inactive/Deleted`.
2. **Partial compatibility mutators.** `WorkOrder`, `WorkInspection`, `WorkInspectionDetail`, `GatePass`, `WarehouseItem`, `WarehouseBalanceItem`, `WarehouseItemStock`, `WarehouseItemStockFile`, and `WarehouseOutItem` map numeric `1` writes to `Active`. This does not fix numeric reads and is not consistently present on `Item`, `Individual`, `ProcessItem`, `StockMillDispatch`, or the `StockMillReturn*` models.
3. **MySQL ENUM index dependency.** Queries such as `where('status', 1)` can compare the numeric enum index; `1` means the first enum member (`Active`). That is an implicit database-engine contract, not an application vocabulary.
4. **Same flag, different encoding.** Warehouse receipt is `Yes/No` on headers and several production tables, `0/1` enum on PO lines, and boolean on mill dispatch/return lines.
5. **`Complete` versus `Completed`.** Work Order and WPR enums use `Complete`; inspection outcome strings use `Completed`; one controller validation also permits `Defective`.
6. **`accepted` versus `Accepted`.** Department-return DB values are lower-case; Work Order UI renders `Accepted` from numeric `is_accept=1`.
7. **`Denied` versus `Rejected`.** `is_accept=2` is documented/rendered as Denied, while canonical approval and department-return vocabularies use Rejected/rejected.
8. **`Cancel`/`Cancelled` absent as data.** Sale item cancellation writes `status='Deleted'` and `cancel_reason`; purchase orders have cancellation metadata but no cancellation state. UI button text must not be confused with stored lifecycle.
9. **Hidden NULL states.** `sale_order_items.is_work_completed`, `work_orders.is_work_require_request_accepted`, `work_process_requirements.is_pro_acc_by_warehouse`, and `machines.is_busy` are nullable, making NULL semantically different from both Yes and No without documentation.
10. **Transport `status` collisions.** AJAX JSON responses also use `status` as success/failure booleans or strings. Those four numeric matches are not database status fields and should remain a separate API-response concept.

## 5. Master record status

Canonical future vocabulary: `record_status = Active | Inactive | Archived` plus framework soft deletion (`deleted_at`) where recoverable deletion is required. Do not use `Deleted` as both a selectable enum member and a second `is_deleted` flag.

Appropriate targets include companies, departments, items, item types, colours, coating parameter masters, machines (availability only), warehouses/compartments, customers/vendors/employees (`individuals`), users, processes, units, GST rates, fault reasons, packaging types, and supporting master records.

Compatibility requirement: retain `Active/Inactive/Deleted` reads initially; translate at an anti-corruption boundary. A later migration can decide whether legacy `Deleted` becomes `Archived` or `deleted_at`. That decision must not be made globally because business documents currently also use the same values.

## 6. Sale Order lifecycle

Verified current flow:

```text
Sale Order row Active
→ item optionally gets work order (`is_work_order_created`)
→ item work flags are set by production
→ packaging flag may be set
→ delivery-final flag may be set
```

Current exits are unsafe: deleting an order writes `Deleted` to header and items; cancelling one item also writes `Deleted` and stores `cancel_reason`. There is no approval status, on-hold status, document completion status, or route-version approval.

Recommended future transition:

```text
Draft → Pending Approval → Approved → In Production
      → Partially Dispatched → Completed
```

Controlled exits:

```text
Pending Approval → Rejected
Approved/In Production → On Hold → previous allowed state
Draft/Pending Approval/Approved → Cancelled (reason required)
```

`record_status` remains separate. Each Sale Order Item must own a versioned workflow snapshot selected from customer/fabric/item requirements. Approval freezes that version. Printing position must come from the item route, never Coating Master. A route change after approval requires request, reason, approval, revised workflow version, and audit event.

## 7. Work Order lifecycle

Verified current gates are: active row; material requirement created; `is_work_require_request_accepted`; `is_item_received_from_warehouse`; `work_status=Pending`; inspection submission; `insp_status/work_status=Complete`; warehouse acceptance/receipt. Process start/end actors and timestamps exist, but the two-state enums cannot represent all those stages.

Recommended transition, mapped to current evidence:

```text
Created
→ Material Requested                     (WPR created)
→ Material Allotted                      (WPR accepted/allotted quantity)
→ Ready                                  (requirement accepted + material received)
→ Started                                (process_started_* evidence)
→ Partially Completed                    (output exists but balance remains)
→ Completed                              (work execution finished)
→ Inspection Pending
→ Passed                                 (inspection complete and accepted)
```

Controlled branches: `Rejected`, `Rework`, `Cancelled`, `On Hold`. `Defective` inspection must lead to Rework/Rejected rather than directly creating the next process. `insp_status` and `work_status` must stop being updated to the same value as if they were synonyms.

## 8. Production execution lifecycle

Recommended canonical vocabulary:

```text
Pending → Ready → Material Requested → Material Allotted → Material Received
→ Started → Partially Completed → Completed → Inspection Pending
→ Inspected → Passed
```

Branches: `Rejected`, `Rework`, `On Hold`, `Cancelled`.

Current mappings are provisional:

- `work_orders.work_status=Pending` covers Created through Started.
- `work_orders.work_status=Complete` covers Completed and later stages.
- `work_order_items.is_work_completed` and three Sale Order Item completion flags duplicate roll-up state.
- Start/end timestamps are currently more informative than the two-state enum.
- Quantities must determine partial completion; a boolean cannot.

## 9. Warehouse/inventory states

Inventory movements need their own document lifecycle:

```text
Draft → Posted → Partially Posted → Posted
                     ↘ Reversed
Draft/Postable → Cancelled
```

For allocation:

```text
Available → Reserved → Issued → Consumed
                   ↘ Released → Available
```

For receipt:

```text
Expected → Partially Received → Fully Received → Inspected → Put Away
```

Verified current evidence is split across `balance_status`, `is_allotted_stock`, warehouse in/out records, gate-pass receipt, PO receipt flags, return flags, and quantities. `warehouse_balance_items.status=Active` says the row exists; `balance_status=1` appears to say the balance is open/usable. Those meanings must remain separate. Inventory quantities should be derived from an immutable ledger; status should control posting/reversal, not replace the ledger.

## 10. Inspection states

Current data has three overlapping axes:

- completion: `insp_status = Pending/Complete`;
- outcome: `work_status` or `insp_work_status = Completed/Defective`;
- handoff: warehouse accepted/item received Yes/No.

Recommended separation:

```text
inspection_status: Not Required | Pending | In Progress | Completed | Cancelled
inspection_result: Pending | Passed | Failed | Conditional Pass | Rework Required
handoff_status:     Pending | Accepted | Rejected | Received
```

Transition:

```text
Pending → In Progress → Completed
Completed + Passed → warehouse handoff / next workflow step
Completed + Failed → Rejected or Rework Required
Rework Required → Pending (new inspection attempt; do not overwrite history)
```

## 11. Job Work states

Verified current flow uses `stock_mill_dispatches`, dispatch items, receive headers/items, returns, warehouse receipt flags, `is_tot_mtr_received`, quantities, and challan pages. It can express none versus fully received, but partial receipt exists only implicitly in quantities.

Recommended transition:

```text
Requirement Raised → Vendor Selected → Dispatch Prepared → Dispatched
→ Partially Received → Fully Received → Inspection Pending
→ Accepted → Closed
```

Branches: `Shortage Pending`, `Rejected`, `Return to Vendor`, `Cancelled`. Header state must roll up from line quantities; it must not be manually toggled independently. `state` on mill dispatch/return is address geography, not lifecycle, and is excluded from the 116-field count.

## 12. Approval states

No general approval framework exists in the live schema. The closest contracts are WPR lab approval, department-return decisions, individual verification, and WPR acceptance.

Recommended canonical approval vocabulary:

```text
Draft → Pending Approval → Approved
                         ↘ Rejected
                         ↘ Returned
Approved → Cancelled (controlled, reason required)
```

Approval is an axis, not a document/execution status. `Approved` must identify approver, timestamp, version, comments, and the business snapshot approved. Sale Order workflow route changes must use this axis and audit history.

## 13. Machine states

Current machine state is only nullable `is_busy=0/1`; `machines.status` is record availability. This cannot distinguish operational causes.

Recommended state machine:

```text
Available → Running → Idle → Available
Available/Running/Idle → Maintenance → Available
Available/Running/Idle → Breakdown → Maintenance → Available
Any operational state → Blocked → prior permitted state
```

Whether `Running` is derived from active work assignment or explicitly commanded is a business decision. Do not infer maintenance/breakdown from `is_busy=0`.

## 14. Invalid/ambiguous fields

| Priority | Field(s) | Problem |
|---|---|---|
| Critical | `sale_orders.status`, `purchase_orders.status` | Record state masquerades as document lifecycle; approval/cancellation/completion absent |
| Critical | `sale_order_items.status` | Cancellation is stored as deletion; cancelled and deleted are indistinguishable |
| Critical | enum `status` fields queried with `1/0` | Engine-coercion dependency across 68 persistence/query sites |
| Critical | `work_orders.work_status` + `insp_status` | Two separate concepts are often updated together to the same two values |
| Critical | inspection `Complete`/`Completed`/`Defective` | Inconsistent vocabulary and free-text columns |
| High | WPR `status`, `is_accept`, `is_pro_acc_by_warehouse` | Record state, request decision, and warehouse decision overlap; compatibility accessor derives one from another |
| High | multiple `is_deleted` plus `status=Deleted` | Duplicate sources of truth; live rows happen to align only in sampled populated tables |
| High | PO/job-work receipt booleans | Cannot represent partial receipt even though quantities can |
| High | Sale Order Item completion flags | Four flags plus quantities can contradict each other |
| High | `balance_status` | Values 0/1 have no schema comment and directly control stock availability |
| Medium | `is_pe_completed` | Acronym/meaning undocumented |
| Medium | `warehouse_in_items.is_updated` | State meaning undocumented |
| Medium | `is_gatepass_genrated_by_warehouse` default Yes | “Generated” defaults true before evidence/document relation |
| Medium | lower/upper case values | `pending` vs `Pending`, `accepted` vs `Accepted`, `yes/no` vs `Yes/No` |
| Medium | nullable flags | NULL is an undocumented third state |

## 15. Recommended canonical vocabulary

Do not create one global `Status` enum. Use bounded vocabularies:

| Category | Canonical values |
|---|---|
| Record status | Active, Inactive, Archived |
| Document status | Draft, Submitted, In Progress, Partially Fulfilled, Completed, Cancelled |
| Approval status | Draft, Pending Approval, Approved, Rejected, Returned, Cancelled |
| Execution status | Pending, Ready, Material Requested, Material Allotted, Material Received, Started, Partially Completed, Completed, On Hold, Cancelled |
| Inspection status | Not Required, Pending, In Progress, Completed, Cancelled |
| Inspection result | Pending, Passed, Failed, Conditional Pass, Rework Required |
| Inventory document status | Draft, Posted, Partially Posted, Reversed, Cancelled |
| Allocation status | Available, Reserved, Issued, Consumed, Released |
| Receipt status | Expected, Partially Received, Fully Received, Inspected, Put Away, Closed |
| Job-work status | Requirement Raised, Vendor Selected, Dispatch Prepared, Dispatched, Partially Received, Fully Received, Shortage Pending, Inspection Pending, Closed, Cancelled |
| Machine status | Available, Running, Idle, Maintenance, Breakdown, Blocked |
| Notification read state | Unread, Read (prefer `read_at`) |

Store stable machine keys such as `pending_approval` or backed-enum scalar values; render translatable labels separately. UI spelling must never be the database contract.

## 16. Recommended transition rules

1. Only a domain transition action/service may change lifecycle fields; controllers validate intent and call it.
2. Every transition checks current state, target state, actor permission, company/branch/factory scope, required reason, and required quantities/documents.
3. Invalid transitions fail atomically with a domain error; no partial flag updates.
4. Header roll-ups are computed from line states/quantities inside the same transaction.
5. Posted inventory movement is immutable; correction is reversal plus a replacement entry.
6. Approval records the approved version/snapshot. Master changes never rewrite an approved Sale Order Item route.
7. Inspection rework creates a new attempt/event and preserves the previous result.
8. Cancellation is not deletion. It requires a reason and blocks later operational transitions, while record archival remains administrative.
9. Each successful transition writes an audit event with entity, old/new state, actor, timestamp, reason, request/correlation ID, and relevant version.

## 17. Backward-compatibility strategy

### PHP backed enums

Use separate backed enums for `RecordStatus`, `DocumentStatus`, `ApprovalStatus`, `ExecutionStatus`, `InspectionStatus`, `InspectionResult`, `InventoryDocumentStatus`, `ReceiptStatus`, `JobWorkStatus`, and `MachineStatus`. Do not enum pure timestamps or quantitative derivations. Simple UI facts such as notification read state may remain boolean/`read_at`.

### Compatibility layer

1. Introduce read-only translators first, with explicit context: `1 → Active` only for legacy record-status fields; `1 → Accepted` only for WPR decision; never a global numeric map.
2. Instrument/log unknown raw values and numeric-coercion use before writes change.
3. Add canonical shadow fields only in a separately reviewed migration; dual-read old then new, and dual-write through one service during a short compatibility window.
4. Backfill in batches from a signed mapping report; unresolved/NULL rows go to a quarantine list, not a guessed value.
5. Switch queries and UI to canonical fields; compare old/new roll-ups.
6. Stop legacy writes, enforce constraints, then retire old flags in a later release.

Existing numeric/string access must be replaced table by table. The current numeric-`1` mutators are temporary write shims, not the final boundary. Direct query-builder inserts bypass mutators and require explicit translation.

### State-machine/service candidates

- `SaleOrderLifecycle`, `SaleOrderItemWorkflowRoute`, `WorkOrderExecution`, `WorkProcessRequirementDecision`, `InspectionLifecycle`, `InventoryPosting`, `PurchaseReceipt`, `JobWorkLifecycle`, `DepartmentReturnDecision`, and `Packaging/DispatchLifecycle`.
- Master records need simple actions/policies, not the production state machine.

## 18. Future implementation sequence

1. **Freeze vocabulary and business decisions.** Resolve Section 19; approve state diagrams and raw-value maps.
2. **Compatibility hot spot first.** Remove numeric `status=1/0` dependency from WorkProcessRequirement, Common, WorkOrder, and StockMillReturn paths while preserving behavior with focused tests.
3. **Separate record deletion.** Reconcile `status=Deleted` and `is_deleted`; do not yet alter business cancellation.
4. **Work Process Requirement + warehouse allocation.** It is the densest active conflict and gates material flow.
5. **Work Order execution + inspection.** Separate execution/completion/result/handoff and preserve process-specific behavior.
6. **Inventory ledger/posting + gate pass.** Canonical posting, allocation, receipt, reversal, and return states.
7. **Sale Order / Sale Order Item.** Add document/approval state and versioned per-item workflow snapshot; migrate cancellation away from deletion.
8. **Purchase Order receipt lifecycle.** Add partial/full receipt from quantities and remove duplicated booleans.
9. **Job Work dispatch/receive.** Add partial receipt, shortage, inspection, and closure.
10. **Packaging/dispatch module.** Replace Sale Order Item completion flags only after the module contract exists.
11. **Machine status.** Replace `is_busy` after work-assignment semantics are agreed.
12. **Lab module last among current broken/dormant surfaces.** Reconcile schema/models/routes before adopting its richer statuses.

The first modules to migrate should therefore be **Work Process Requirement → Work Order/Inspection → Inventory/Gate Pass**, followed by Sale Order, Purchase Order, Job Work, and Packaging/Dispatch.

## 19. Fields/modules requiring manual business decision

1. Does Sale Order require approval at header, item, or both? Who can revise an approved item route?
2. Is an item cancellation reversible, and must cancelled items remain reportable separately from deleted rows?
3. What exactly distinguishes Sale Order Item `is_work_completed`, `is_work_final_completed`, and `is_work_final_dlvr_completed`?
4. Does `work_orders.work_status=Complete` mean process output complete, inspection complete, or warehouse handoff complete?
5. Can a Work Order be partially completed across multiple taka/rolls, and how is its header rolled up?
6. Is inspection `Defective` equivalent to Failed, Rejected, or Rework Required?
7. Is WPR `is_accept=2` Denied or Rejected? Can it be resubmitted?
8. Is `is_pro_acc_by_warehouse` an independent warehouse decision or only a legacy projection of `is_accept`?
9. What does `warehouse_balance_items.balance_status=0` mean: consumed, closed, superseded, or invalid?
10. What does `warehouse_in_items.is_updated` mean?
11. What does `receive_stock_mill_dispatches.is_pe_completed` mean, and what is PE?
12. When is job-work receipt “partial”, how are shortage/tolerance and closure approved, and which quantity is authoritative?
13. Should gate-pass creation default to true, or be derived from an actual gate-pass row?
14. Is machine status manually controlled, work-assignment derived, or both with precedence rules?
15. Are individual `is_verified=yes/no` values approval, KYC, contact verification, or migration completeness?
16. What is the desired lab lifecycle after `Approved`: Accepted, Form Submitted, Result Submitted, Pass/Fail, Closed?
17. Is packaging per Sale Order Item, lot/taka/roll, package, or dispatch line?

## 20. Risks

- Numeric ENUM comparisons may silently change behavior across SQL modes, database engines, or enum ordering.
- Direct status updates across large controllers can bypass permissions, transition validation, and audit logging.
- Migrating flags independently can create impossible combinations and incorrect header roll-ups.
- Treating `Deleted` as `Cancelled` can remove commercial traceability and distort reports.
- Backfilling NULL without business decisions can invent history.
- Current live data is small in several transactional tables and empty in 25 fields; unused schema values are not proof that a transition is unnecessary.
- The inactive Lab Test controller references missing `App\\Models\\Lab*` classes/tables/routes and uses a broader vocabulary (`Requested`, `Approved`, `Rejected`, `Accepted`, `FormSubmitted`, `ResultSubmitted`, `Pass`, `Fail`). It must not drive a migration until its module contract exists.
- There is no current Branch/Factory scope in these statuses; future transitions must include tenancy scope from the start.
- The workflow route rule is mandatory: Coating Master stores coating parameters only. It must never determine whether Printing occurs before or after Coating.

### Verification and commands executed

Read-only evidence included:

- `git status --short`, `git status --branch --short`, and `git log --oneline -10`.
- `rg` searches over migrations, models, controllers, routes, Blade, JavaScript/AJAX-bearing Blade, reports/exports, tests, and previous audits for `status`, related flags, and the requested vocabulary.
- `information_schema.COLUMNS` SELECTs for field name, type, nullability, default, comments, and enum domain.
- Per-field `SELECT field, COUNT(*) ... GROUP BY field` against all 116 audited fields in live `blackgrd`.
- Read-only `rg` searches in `E:\xampp\htdocs\erp` for workflow reference only.
- `php artisan db:safety-check`, which returned the required `BLOCKED` result for connected database `blackgrd` (allow-list no match; destructive confirmation not armed).

No destructive Artisan command and no SQL other than SELECT was executed. The temporary local read-only audit helper used to issue the SELECTs was removed after report generation.
