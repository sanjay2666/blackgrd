<?php

use App\Http\Controllers\Admin\AllPageController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\BranchFactoryController;
use App\Http\Controllers\Admin\ChemicalController;
use App\Http\Controllers\Admin\ColourController;
use App\Http\Controllers\Admin\CompanyController;
use App\Http\Controllers\Admin\CotingController;
use App\Http\Controllers\Admin\CourierController;
use App\Http\Controllers\Admin\CustomerAddressController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Admin\DocumentSettingsController;
use App\Http\Controllers\Admin\DyeingColourController;
use App\Http\Controllers\Admin\EmployeeController;
use App\Http\Controllers\Admin\FabricFaultReasonController;
use App\Http\Controllers\Admin\FabricQualityController;
use App\Http\Controllers\Admin\FinancialYearController;
use App\Http\Controllers\Admin\GstRateController;
use App\Http\Controllers\Admin\HsnCodeController;
use App\Http\Controllers\Admin\IndividualController;
use App\Http\Controllers\Admin\ItemController;
use App\Http\Controllers\Admin\ItemTypeController;
use App\Http\Controllers\Admin\ItemYarnRequirementController;
use App\Http\Controllers\Admin\LoginAttemptController;
use App\Http\Controllers\Admin\LoginOtpController;
use App\Http\Controllers\Admin\MachineCapacityController;
use App\Http\Controllers\Admin\MachineController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\NumberSeriesController;
use App\Http\Controllers\Admin\OfficeIpController;
use App\Http\Controllers\Admin\PackagingTypeController;
use App\Http\Controllers\Admin\PrintingDesignController;
use App\Http\Controllers\Admin\ProcessItemController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\ShiftController;
use App\Http\Controllers\Admin\StateController;
use App\Http\Controllers\Admin\TransporterAddressController;
use App\Http\Controllers\Admin\TransporterController;
use App\Http\Controllers\Admin\UnitTypeController;
use App\Http\Controllers\Admin\UserActivityLogController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\UserPermissionController;
use App\Http\Controllers\Admin\UserWebPageController;
use App\Http\Controllers\Admin\VendorAddressController;
use App\Http\Controllers\Admin\VendorController;
use App\Http\Controllers\Admin\WareHouseCompartmentController;
use App\Http\Controllers\Admin\WarehouseController;
use App\Http\Controllers\Admin\WorkflowAssignmentController;
use App\Http\Controllers\Admin\WorkflowDefinitionController;
use App\Http\Controllers\Auth\AdminAuthController;
use App\Http\Controllers\Auth\UserAuthController;
use App\Http\Controllers\CommonController;
use App\Http\Controllers\DyeingColourLookupController;
use App\Http\Controllers\JobMillWorkController;
use App\Http\Controllers\PackagingController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\SaleOrderController;
use App\Http\Controllers\SalesChallanController;
use App\Http\Controllers\WarehouseItemController;
use App\Http\Controllers\WorkOrderController;
use App\Http\Controllers\WorkProcessRequirementController;
use App\Http\Controllers\WorkPurchaseRequirementController;
use App\Http\Middleware\ValidateCustomerSaleOrder;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Frontend Route
|--------------------------------------------------------------------------
*/

Route::get('/', [PageController::class, 'home'])->name('home');

/*
|--------------------------------------------------------------------------
| Shared Authenticated AJAX / Autocomplete Routes
|--------------------------------------------------------------------------
|
| These endpoints are used by both frontend users and admins for dropdowns,
| autocomplete inputs, address lookup, and common list data.
|
*/
Route::middleware(['auth:web,admin', 'organization', 'rbac', 'audit'])->group(function () {
    Route::get('/list_customer', [CommonController::class, 'list_customer'])->name('list_customer');
    Route::get('/fabric_list_item', [CommonController::class, 'fabric_list_item'])->name('fabric_list_item');
    Route::get('/list_warehouse_item_type', [CommonController::class, 'list_warehouse_item_type'])->name('list_warehouse_item_type');
    Route::get('/find_saleOrderNumer', [CommonController::class, 'find_saleOrderNumer'])->name('find_saleOrderNumer');
    Route::get('/list_individual', [CommonController::class, 'list_individual'])->name('list_individual');
    Route::get('/list_coating', [CommonController::class, 'list_coating'])->name('list_coating');
    Route::get('/customer-addresses', [CommonController::class, 'customer_addresses'])->name('customer_addresses');
    Route::get('/individual-addresses', [CommonController::class, 'individual_addresses'])->name('individual_addresses');
    Route::get('/find_saleDyeingColor', [CommonController::class, 'find_saleDyeingColor'])->name('find_saleDyeingColor');
    Route::get('/list_vendor', [CommonController::class, 'list_vendor'])->name('list_vendor');
    Route::get('/list_transporter', [CommonController::class, 'list_transporter'])->name('list_transporter');
    Route::get('/list_customerandvendor', [CommonController::class, 'list_customerandvendor'])->name('list_customerandvendor');
    Route::get('/list_employee', [CommonController::class, 'list_employee'])->name('list_employee');
    Route::get('/list_item', [CommonController::class, 'list_item'])->name('list_item');
    Route::get('/ajax_script/search_customer_ship_address', [CommonController::class, 'search_customer_ship_address']);
    Route::get('/list_warehouse_compartment', [CommonController::class, 'list_warehouse_compartment'])->name('list_warehouse_compartment');
    Route::get('/list_saleOrderNumer', [CommonController::class, 'list_saleOrderNumer'])->name('list_saleOrderNumer');
    Route::get('/ajax_script/search_customer_bill_address', [CommonController::class, 'search_customer_bill_address']);
    Route::get('/list_master_color', [CommonController::class, 'list_master_color'])->name('list_master_color');
    Route::get('/list_master_dyeing_colour', DyeingColourLookupController::class)->name('list_master_dyeing_colour');
});

/*
|--------------------------------------------------------------------------
| Frontend Guest Authentication Routes
|--------------------------------------------------------------------------
*/
Route::middleware('guest:web')->group(function () {
    Route::get('/register', [UserAuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [UserAuthController::class, 'register'])->name('register.store');
    Route::get('/login', [UserAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [UserAuthController::class, 'login'])->middleware('throttle:auth-login')->name('login.store');
    Route::get('/forgot-password', [UserAuthController::class, 'showForgotPassword'])->name('password.request');
    Route::post('/forgot-password', [UserAuthController::class, 'sendResetLink'])->middleware('throttle:password-reset')->name('password.email');
    Route::get('/reset-password/{token}', [UserAuthController::class, 'showResetPassword'])->name('password.reset');
    Route::post('/reset-password', [UserAuthController::class, 'resetPassword'])->middleware('throttle:password-reset')->name('password.update');
});

/*
|--------------------------------------------------------------------------
| Frontend Authenticated User Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:web', 'organization', 'rbac', 'audit'])->group(function () {
    /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    */
    Route::get('/dashboard', [PageController::class, 'dashboard'])->name('dashboard');

    /*
    |--------------------------------------------------------------------------
    | Sale Orders, Work Orders From Sale Orders, and Sale Order Reports
    |--------------------------------------------------------------------------
    */
    Route::get('/sale-orders', [SaleOrderController::class, 'index'])->name('sale-orders.index');
    Route::get('/sale-orders/create', [SaleOrderController::class, 'create'])->name('sale-orders.create');
    Route::get('/sale-orders/{id}/edit', [SaleOrderController::class, 'edit'])->name('sale-orders.edit');
    Route::get('/show-saleorder-workorder-details/{id}', [SaleOrderController::class, 'showSaleOrderWorkOrderDetails'])->name('show-saleorder-workorder-details');
    Route::get('/print-saleorder/{id}', [SaleOrderController::class, 'printSaleOrder'])->name('saleorders.print');
    Route::get('/sale-order/ajax-details/{id}', [SaleOrderController::class, 'ajaxSaleOrderDetails']);
    Route::get('/show-saleorder-reports', [SaleOrderController::class, 'show_sale_order_reports'])->name('show-saleorder-reports');

    Route::post('/sale-orders', [SaleOrderController::class, 'store'])->middleware(ValidateCustomerSaleOrder::class)->name('sale-orders.store');
    Route::post('/ajax_script/deleteSaleOrder', [SaleOrderController::class, 'deleteSaleOrder'])->name('saleorders.delete');
    Route::post('/sale-order/submit-selected-items', [SaleOrderController::class, 'submitSelectedItems'])->name('sale-order.submit-selected-items');
    Route::post('/sale-order/update', [SaleOrderController::class, 'updateSaleOrder'])->middleware(ValidateCustomerSaleOrder::class)->name('sale-order.update');
    Route::post('/sale-order/update-item', [SaleOrderController::class, 'updateSaleOrderItem'])->name('sale-order.update-item');
    Route::post('/cancelSaleOrderItem', [SaleOrderController::class, 'cancelSaleOrderItem'])->name('cancelSaleOrderItem');
    Route::post('/clearSaleOrderItem', [SaleOrderController::class, 'clearSaleOrderItem'])->name('clearSaleOrderItem');
    Route::get('/show-saleorderitems', [SaleOrderController::class, 'show_sale_order_items'])->name('show-saleorderitems');
    Route::post('/SetReasonForSaleOrderItem', [SaleOrderController::class, 'SetReasonForSaleOrderItem'])->name('SetReasonForSaleOrderItem');
    Route::post('/SetReasonForWorkOrderItem', [SaleOrderController::class, 'SetReasonForWorkOrderItem'])->name('SetReasonForWorkOrderItem');
    Route::get('/get-reason-history/{soItemId}', [SaleOrderController::class, 'getReasonHistory']);
    Route::get('/get-work-reason-history/{woId}', [SaleOrderController::class, 'getWorkReasonHistory']);
    Route::get('/ajax_script/updateCoatingRequirement', [SaleOrderController::class, 'updateCoatingRequirement']);

    /*
    |--------------------------------------------------------------------------
    | Purchase Orders and Received Purchase Transactions
    |--------------------------------------------------------------------------
    */
    Route::get('/show-purchaseorders', [PurchaseOrderController::class, 'index'])->name('show-purchaseorders');
    Route::get('/add-purchaseorder', [PurchaseOrderController::class, 'create'])->name('add-purchaseorder');
    Route::get('/edit-purchaseorder/{id}', [PurchaseOrderController::class, 'edit'])->name('edit-purchaseorder');
    Route::post('/store_purchaseorder', [PurchaseOrderController::class, 'store'])->name('store_purchaseorder');
    Route::post('/update_purchaseorder/{id}', [PurchaseOrderController::class, 'update'])->name('update_purchaseorder');
    Route::post('/ajax_script/deletePurchaseOrder', [PurchaseOrderController::class, 'delete'])->name('purchase-orders.delete');
    Route::get('/print-purchaseorder/{id}', [PurchaseOrderController::class, 'printPurchaseOrder'])->name('print-purchaseorder');
    Route::get('/show-purchases', [PurchaseController::class, 'index'])->name('show-purchases');
    Route::get('/show-purchase/{id}', [PurchaseController::class, 'show'])->name('show-purchase');

    /*
    |--------------------------------------------------------------------------
    | Work Order Process Updates and Department Movement
    |--------------------------------------------------------------------------
    */
    Route::post('/update_startprocess', [WorkOrderController::class, 'updateworkorder'])->name('update_startprocess');
    Route::post('/workorder/update-machine', [WorkOrderController::class, 'updateMachine'])->name('workorder.updateMachine');
    Route::post('/workorder/update-machine-wo', [WorkOrderController::class, 'updateMachineWo'])->name('workorder.updateMachineWo');
    Route::post('/update_inspec_process', [WorkOrderController::class, 'updateinspectionworkorder'])->name('update_inspec_process');
    Route::post('/update_weaving_inspec_process', [WorkOrderController::class, 'update_weaving_inspec_process'])->name('update_weaving_inspec_process');
    Route::post('/update_dyeing_inspec_process', [WorkOrderController::class, 'update_dyeing_inspec_process'])->name('update_dyeing_inspec_process');
    Route::post('/update_coating_inspec_process', [WorkOrderController::class, 'update_coating_inspec_process'])->name('update_coating_inspec_process');
    Route::post('/ajax_script/closeWorkOrder', [WorkOrderController::class, 'closeWorkOrder'])->name('ajax.closeWorkOrder');

    Route::post('/update_coating_print_inspec_process', [WorkOrderController::class, 'updateCoatingPrintInspecProcess'])->name('update_coating_print_inspec_process');

    Route::get('/start-requisition-process/{id}', [WorkOrderController::class, 'start_requisition_process'])->name('start-requisition-process');

    Route::post('/add_work_requisition', [WorkOrderController::class, 'add_work_requisition'])->name('add_work_requisition');
    Route::post('/add_work_requisition_for_weaving', [WorkOrderController::class, 'add_work_requisition_for_weaving'])->name('add_work_requisition_for_weaving');
    Route::post('/add_work_requisition_for_dyeing', [WorkOrderController::class, 'add_work_requisition_for_dyeing'])->name('add_work_requisition_for_dyeing');

    Route::get('/show-workorders', [WorkOrderController::class, 'index'])->name('show-workorders');
    Route::post('/store_workorder', [WorkOrderController::class, 'store'])->name('store_workorder');
    Route::post('/accept_item_for_work', [WorkOrderController::class, 'accept_item_for_work'])->name('accept_item_for_work');
    Route::post('/receive_work_item_in_warehouse', [WorkOrderController::class, 'receive_work_item_in_warehouse'])->name('receive_work_item_in_warehouse');
    Route::post('/receiveWorkItemInDepartmentWarehouse', [WorkOrderController::class, 'receiveWorkItemInDepartmentWarehouse'])->name('receiveWorkItemInDepartmentWarehouse');

    Route::get('/ajax_script/checkIteminWarehouse', [WorkOrderController::class, 'checkIteminWarehouse']);

    Route::get('/ajax_script/shiftWorkOrderToWarping', [WorkOrderController::class, 'shiftWorkOrderToWarping']);
    Route::get('/show-dyed-workorders', [WorkOrderController::class, 'checkingDyedWorkOrder'])->name('show-dyed-workorders');
    Route::get('/show-workorders-dyeing', [WorkOrderController::class, 'checkingDyedWorkOrder'])->name('show-workorders-dyeing');
    Route::post('/decide-printing-position', [WorkOrderController::class, 'decidePrinting'])->name('decide-printing-position');
    Route::get('/workorders/totals', [WorkOrderController::class, 'workOrderTotals'])->name('workorders.totals');
    Route::get('/ajax_script/getWorkOrderDetails', [WorkOrderController::class, 'getWorkOrderDetails']);
    Route::get('/print-workorder-gatepass/{id}', [WorkOrderController::class, 'print_workorder_gatepass'])->name('print-workorder-gatepass');

    Route::get('/ajax_script/denyWorkInspection', [WorkOrderController::class, 'denyWorkInspection']);
    Route::match(['get', 'post'], '/ajax_script/deleteGpInspDetails', [WorkOrderController::class, 'deleteGpInspDetails']);

    Route::get('/show-workorder-inspection', [WorkOrderController::class, 'show_workorder_inspection_report'])->name('show-workorder-inspection');
    Route::post('/addWorkRequisitionForRfDyeing', [WorkOrderController::class, 'addWorkRequisitionForRfDyeing'])->name('addWorkRequisitionForRfDyeing');

    Route::post('/accept_work_item_in_warehouse', [WorkOrderController::class, 'accept_work_item_in_warehouse'])->name('accept_work_item_in_warehouse');

    Route::get('/receive-work-item/{id}', [WorkOrderController::class, 'receive_work_item'])->name('receive-work-item');

    /*
    |--------------------------------------------------------------------------
    | Warehouse Items, Stock Details, Received Items, and Reports
    |--------------------------------------------------------------------------
    */
    Route::get('/show', [WarehouseItemController::class, 'index'])->name('show');
    Route::get('/show-stock-details-listing/{id}', [WarehouseItemController::class, 'stock_details_listing'])->name('show-stock-details-listing');
    Route::get('/show-stock-details-inline/{id}', [WarehouseItemController::class, 'stock_details_inline'])->name('show-stock-details-inline');

    Route::get('/add-item-in-warehouse', [WarehouseItemController::class, 'add_item_in_warehouse'])->name('add-item-in-warehouse');
    Route::post('/store_item_in_warehouse', [WarehouseItemController::class, 'store_item_in_warehouse'])->name('store_item_in_warehouse');

    Route::get('/add-received-item-in-warehouse', [WarehouseItemController::class, 'add_received_item_in_warehouse'])->name('add-received-item-in-warehouse');
    Route::post('/storeReceivedItemsFromInvoice', [WarehouseItemController::class, 'storeReceivedItemsFromInvoice'])->name('storeReceivedItemsFromInvoice');

    Route::get('/show-warehouse-stock-report', [WarehouseItemController::class, 'warehouse_stock_report'])->name('show-warehouse-stock-report');
    Route::get('/show-warehouse-balance-report', [WarehouseItemController::class, 'warehouse_balance_report'])->name('show-warehouse-balance-report');
    Route::get('/warehouse-stock-document/{id}', [WarehouseItemController::class, 'warehouse_stock_document'])->name('warehouse-stock-document');

    Route::get('/ajax_script/search_warehouse_compartment', [WarehouseItemController::class, 'search_warehouse_compartment'])->name('search_warehouse_compartment');
    Route::get('/ajax_script/search_warehouse_compartment_arr', [WarehouseItemController::class, 'search_warehouse_compartment_arr'])->name('search_warehouse_compartment_arr');
    Route::get('/ajax_script/get_warehouse_compartment_options', [WarehouseItemController::class, 'get_warehouse_compartment_options'])->name('get_warehouse_compartment_options');
    Route::get('/ajax_script/updateWarehouseComp', [WarehouseItemController::class, 'updateWarehouseComp'])->name('updateWarehouseComp');
    Route::get('/ajax_script/deleteWarehouseItemStock', [WarehouseItemController::class, 'deleteWarehouseItemStock'])->name('deleteWarehouseItemStock');

    Route::get('/ajax_script/getWarehouseCompEmployee', [WarehouseItemController::class, 'getWarehouseCompEmployee'])->name('getWarehouseCompEmployee');
    Route::post('/sendItemReturnRequest', [WarehouseItemController::class, 'sendItemReturnRequest'])->name('sendItemReturnRequest');
    Route::get('/show-department-return-requests', [WarehouseItemController::class, 'ShowDepartmentReturnRequest'])->name('show-department-return-requests');
    Route::post('/storeDepartmentReturnRequest', [WarehouseItemController::class, 'storeDepartmentReturnRequest'])->name('storeDepartmentReturnRequest');
    Route::post('/deny-department-return-request', [WarehouseItemController::class, 'denyDepartmentRequest'])->name('warehouse.denyDepartmentRequest');

    Route::get('/show-accepted-department-return-request/{id}', [WarehouseItemController::class, 'showAcceptedDepartmentReturnRequest'])->name('show-accepted-department-return-request');
    Route::get('/accept-department-return-request/{id}', [WarehouseItemController::class, 'acceptDepartmentReturnRequest'])->name('accept-department-return-request');

    Route::get('/show-balance-table-stock', [WarehouseItemController::class, 'ShowBalanceTableStock'])->name('show-balance-table-stock');
    Route::get('/ajax_script/RefreshWarehouseItem', [WarehouseItemController::class, 'RefreshWarehouseItem']);

    /*
    |--------------------------------------------------------------------------
    | Mill Dispatch / Mill Received Stock Routes
    |--------------------------------------------------------------------------
    */
    Route::get('/show-warehouse-item-stock', [JobMillWorkController::class, 'showWarehouseItemStock'])->name('show-warehouse-item-stock');
    Route::post('/storeStockForMillDispatch', [JobMillWorkController::class, 'storeStockForMillDispatch'])->name('storeStockForMillDispatch');
    Route::get('/show-mill-chalan', [JobMillWorkController::class, 'showMillChalan'])->name('show-mill-chalan');
    Route::get('/print-mill-dispatch-chalan/{Id}', [JobMillWorkController::class, 'printMillDispatchChalan'])->name('print-mill-dispatch-chalan');
    Route::get('/print-mill-dispatch-received-chalan/{Id}', [JobMillWorkController::class, 'printMillDispatchReceivedChalan'])->name('print-mill-dispatch-received-chalan');
    Route::get('mill-dispatch-received-items-in-warehouse/{id}', [JobMillWorkController::class, 'millDispatchReceivedItemInWarehouse'])->name('mill_dispatch_received_items_in_warehouse');
    Route::post('store-mill-dispatch-received-items-in-warehouse', [JobMillWorkController::class, 'storeMillDispatchReceivedItemInWarehouse'])->name('store_mill_dispatch_received_item_in_warehouse');
    Route::get('mill-dispatch-received-weaving-items-in-warehouse/{id}', [JobMillWorkController::class, 'millDispatchReceivedWeavingItemInWarehouse'])->name('mill_dispatch_received_weaving_items_in_warehouse');
    Route::post('store-mill-dispatch-received-weaving-items-in-warehouse', [JobMillWorkController::class, 'storeMillDispatchReceivedWeavingItemInWarehouse'])->name('store_mill_dispatch_received_weaving_item_in_warehouse');
    Route::post('/warehouse/break-meter', [JobMillWorkController::class, 'breakMeter'])->name('warehouse.breakMeter');
    Route::match(['get', 'post'], '/stock-mill-dispatch/update-vendor', [JobMillWorkController::class, 'updateVendor'])->name('updateVendor');
    Route::match(['get', 'post'], '/update_mtr_received_status', [JobMillWorkController::class, 'updateMtrReceivedStatus'])->name('update_mtr_received_status');

    /*
    |--------------------------------------------------------------------------
    | Packaging
    |--------------------------------------------------------------------------
    */
    Route::get('/show-add-packaging-list', [PackagingController::class, 'showPackagingAvailableOrders'])->name('packaging.show-available-orders');
    Route::get('/show-packagings', [PackagingController::class, 'showPackagedOrders'])->name('packaging.show-packaged-orders');
    Route::get('/packaging', [PackagingController::class, 'showPackagingAvailableOrders'])->name('packaging.show-available-orders-legacy');
    Route::get('/packaging/lot-autocomplete', [PackagingController::class, 'listPackagingLots'])->name('packaging.lot-autocomplete');
    Route::get('/packaging/sale-order-items/{saleOrderItem}/create', [PackagingController::class, 'openPackagingCartForSaleOrderItem'])->name('packaging.open-cart-for-sale-order-item');
    Route::get('/packaging/cart', [PackagingController::class, 'showPackagingOrderCart'])->name('packaging.show-order-cart');
    Route::get('/packaging/available-stock', [PackagingController::class, 'getPackagingAvailableStock'])->name('packaging.get-available-stock');
    Route::post('/packaging', [PackagingController::class, 'storePackagingOrder'])->name('packaging.store-packaging-order');
    Route::get('/packaging/{packagingOrder}', [PackagingController::class, 'showPackagingOrderDetails'])->name('packaging.show-order-details');
    Route::get('/packaging/{packagingOrder}/print', [PackagingController::class, 'printPackagingSlip'])->name('packaging.print-packaging-slip');
    Route::post('/packaging/{packagingOrder}/accept', [PackagingController::class, 'acceptPackagingWarehouseStock'])->name('packaging.accept-warehouse-stock');
    Route::post('/packaging/{packagingOrder}/pack', [PackagingController::class, 'updatePackagingPackedQuantity'])->name('packaging.update-packed-quantity');
    Route::post('/packaging/{packagingOrder}/reverse', [PackagingController::class, 'cancelPackagingOrderAndRestoreStock'])->name('packaging.cancel-and-restore-stock');

    /* Customer dispatch is deliberately separate from Packaging stock issue. */
    Route::get('/sales-challans', [SalesChallanController::class, 'index'])->name('sales-challans.index');
    Route::get('/sales-challans/create', [SalesChallanController::class, 'create'])->name('sales-challans.create');
    Route::post('/sales-challans', [SalesChallanController::class, 'store'])->name('sales-challans.store');
    Route::get('/sales-challans/{salesChallan}', [SalesChallanController::class, 'show'])->name('sales-challans.show');
    Route::post('/sales-challans/{salesChallan}/post', [SalesChallanController::class, 'post'])->name('sales-challans.post');
    Route::post('/sales-challans/{salesChallan}/cancel', [SalesChallanController::class, 'cancel'])->name('sales-challans.cancel');
    Route::get('/sales-challans/{salesChallan}/print', [SalesChallanController::class, 'print'])->name('sales-challans.print');
    Route::post('/sales-challans/{salesChallan}/print-count', [SalesChallanController::class, 'incrementPrint'])->name('sales-challans.print-count');

    /*
    |--------------------------------------------------------------------------
    | Work Process Requirement Controller
    |--------------------------------------------------------------------------
    */

    Route::get('/show-warehouse-item-requirement', [WorkProcessRequirementController::class, 'index'])->name('show-warehouse-item-requirement');
    Route::get('/accept-warehouse-item-requirement/{id}', [WorkProcessRequirementController::class, 'acceptWarehouseItemRequirement'])->name('accept-warehouse-item-requirement');
    Route::get('/accept-warehouse-item-requirement-for-printing/{id}', [WorkProcessRequirementController::class, 'acceptWarehouseItemRequirementForPrinting'])->name('accept-warehouse-item-requirement-for-printing');
    Route::get('/print-warehouse-item-requirement-gatepass/{id}', [WorkProcessRequirementController::class, 'print_warehouse_item_requirement_gatepass'])->name('print-warehouse-item-requirement-gatepass');
    Route::get('/print-warehouse-item-requirement-gatepass-by-lot/{id}', [WorkProcessRequirementController::class, 'print_warehouse_item_requirement_gatepass_by_lot'])->name('print-warehouse-item-requirement-gatepass-by-lot');
    Route::get('/print-job-card-gatepass/{id}', [WorkProcessRequirementController::class, 'printJobCardGatepass'])->name('print-job-card-gatepass');
    Route::get('/print-job-card-traceability/{id}', [WorkProcessRequirementController::class, 'printJobCardTraceability'])->name('print-job-card-traceability');
    Route::get('/show-warehouse-item-for-printing-requirement', [WorkProcessRequirementController::class, 'showWarehouseItemForPrintingRequirement'])->name('show-warehouse-item-for-printing-requirement');
    Route::post('/store-warehouse-stock-allotment', [WorkProcessRequirementController::class, 'StoreWarehouseStockAllotment'])->name('StoreWarehouseStockAllotment');
    Route::post('/store-warehouse-grey-and-color-stock-allotment', [WorkProcessRequirementController::class, 'StoreWarehouseGreyAndColorStockAllotment'])->name('StoreWarehouseGreyAndColorStockAllotment');
    Route::post('/store-warehouse-yarn-beam-stock-allotment', [WorkProcessRequirementController::class, 'StoreWarehouseYarnBeamStockAllotment'])->name('StoreWarehouseYarnBeamStockAllotment');
    Route::post('/store-warehouse-yarn-stock-allotment', [WorkProcessRequirementController::class, 'StoreWarehouseYarnStockAllotment'])->name('StoreWarehouseYarnStockAllotment');

    Route::get('/ajax_script/getWorkProcessRequirement', [WorkProcessRequirementController::class, 'getWorkProcessRequirement']);
    Route::get('/ajax_script/getWorkProcessPrintingRequirement', [WorkProcessRequirementController::class, 'getWorkProcessPrintingRequirement']);
    Route::get('/ajax_script/getProcessRequirementItems', [WorkProcessRequirementController::class, 'getProcessRequirementItems']);
    Route::get('/ajax_script/getLotReturnItems', [WorkProcessRequirementController::class, 'getLotReturnItems']);
    Route::get('/ajax_script/getBeamReturnItems', [WorkProcessRequirementController::class, 'getBeamReturnItems']);
    Route::get('/ajax_script/getSumWarehouseItemStockValue', [WorkProcessRequirementController::class, 'getSumWarehouseItemStockValue']);

    Route::get('/ajax_script/DenyWarehouseReq', [WorkProcessRequirementController::class, 'DenyWarehouseReq']);
    Route::post('/deny-printing-requisition', [WorkProcessRequirementController::class, 'add_remark_for_deny_requisition'])->name('add_remark_for_deny_requisition');

    Route::get('ajax_script/getWarehouseItemStock', [WorkProcessRequirementController::class, 'getWarehouseItemStock']);

    Route::post('/addWorkRequisitionForCoatingAndStockAllotment', [WorkProcessRequirementController::class, 'addWorkRequisitionForCoatingAndStockAllotment'])->name('addWorkRequisitionForCoatingAndStockAllotment');

    /*
    |--------------------------------------------------------------------------
    | WorkPurchaseRequirementController
    |--------------------------------------------------------------------------
    */

    Route::post('/add-work-purchase-requisition', [WorkPurchaseRequirementController::class, 'add_work_purchase_requisition'])->name('add-work-purchase-requisition');

    /*
    |--------------------------------------------------------------------------
    | Frontend Logout
    |--------------------------------------------------------------------------
    */
    Route::post('/logout', [UserAuthController::class, 'logout'])->name('logout');
});

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/

Route::prefix('admin')->name('admin.')->group(function () {
    // Admin guest routes.
    Route::middleware('guest:admin')->group(function () {
        Route::get('/login', [AdminAuthController::class, 'showLogin'])->name('login');
        Route::post('/login', [AdminAuthController::class, 'login'])->middleware('throttle:auth-login')->name('login.store');
    });

    // Admin logged-in routes.
    Route::middleware(['auth:admin', 'organization', 'rbac', 'audit'])->group(function () {
        Route::get('/dashboard', AdminDashboardController::class)->name('dashboard');

        // Admin master routes.
        Route::resource('states', StateController::class)->except(['show']);
        Route::resource('all-pages', AllPageController::class)->except(['show']);
        Route::resource('colours', ColourController::class)->except(['show']);
        Route::resource('dyeing-colours', DyeingColourController::class)->except(['show']);
        Route::patch('/chemicals/{id}/activate', [ChemicalController::class, 'activate'])->name('chemicals.activate');
        Route::patch('/chemicals/{id}/deactivate', [ChemicalController::class, 'deactivate'])->name('chemicals.deactivate');
        Route::get('/chemicals/options', [ChemicalController::class, 'options'])->name('chemicals.options');
        Route::resource('chemicals', ChemicalController::class)->except(['show']);
        Route::patch('/dyeing-colours/{id}/activate', [DyeingColourController::class, 'activate'])->name('dyeing-colours.activate');
        Route::patch('/dyeing-colours/{id}/deactivate', [DyeingColourController::class, 'deactivate'])->name('dyeing-colours.deactivate');
        Route::patch('/colours/{id}/activate', [ColourController::class, 'activate'])->name('colours.activate');
        Route::patch('/colours/{id}/deactivate', [ColourController::class, 'deactivate'])->name('colours.deactivate');
        Route::get('/companies', [CompanyController::class, 'index'])->name('companies.index');
        Route::get('/companies/edit', [CompanyController::class, 'edit'])->name('companies.edit');
        Route::put('/companies', [CompanyController::class, 'update'])->name('companies.update');
        Route::get('/branches', [BranchFactoryController::class, 'index'])->name('branches.index');
        Route::get('/branches/create', [BranchFactoryController::class, 'create'])->name('branches.create');
        Route::post('/branches', [BranchFactoryController::class, 'store'])->name('branches.store');
        Route::get('/branches/{branch}/edit', [BranchFactoryController::class, 'editBranch'])->name('branches.edit');
        Route::put('/branches/{branch}', [BranchFactoryController::class, 'updateBranch'])->name('branches.update');
        Route::patch('/branches/{branch}/activate', [BranchFactoryController::class, 'activateBranch'])->name('branches.activate');
        Route::patch('/branches/{branch}/deactivate', [BranchFactoryController::class, 'deactivateBranch'])->name('branches.deactivate');
        Route::get('/factories', [BranchFactoryController::class, 'index'])->name('factories.index');
        Route::get('/factories/create', [BranchFactoryController::class, 'create'])->name('factories.create');
        Route::post('/factories', [BranchFactoryController::class, 'store'])->name('factories.store');
        Route::get('/factories/{factory}/edit', [BranchFactoryController::class, 'editFactory'])->name('factories.edit');
        Route::put('/factories/{factory}', [BranchFactoryController::class, 'updateFactory'])->name('factories.update');
        Route::patch('/factories/{factory}/activate', [BranchFactoryController::class, 'activateFactory'])->name('factories.activate');
        Route::patch('/factories/{factory}/deactivate', [BranchFactoryController::class, 'deactivateFactory'])->name('factories.deactivate');
        Route::resource('cotings', CotingController::class)->except(['show']);
        Route::patch('cotings/{id}/activate', [CotingController::class, 'activate'])->name('cotings.activate');
        Route::patch('cotings/{id}/deactivate', [CotingController::class, 'deactivate'])->name('cotings.deactivate');
        Route::get('cotings/options', [CotingController::class, 'options'])->name('cotings.options');
        Route::resource('couriers', CourierController::class)->except(['show']);
        Route::resource('gst-rates', GstRateController::class)->except(['show']);
        Route::patch('/gst-rates/{id}/activate', [GstRateController::class, 'activate'])->name('gst-rates.activate');
        Route::patch('/gst-rates/{id}/deactivate', [GstRateController::class, 'deactivate'])->name('gst-rates.deactivate');
        Route::resource('hsn-codes', HsnCodeController::class)->except(['show']);
        Route::patch('/hsn-codes/{id}/activate', [HsnCodeController::class, 'activate'])->name('hsn-codes.activate');
        Route::patch('/hsn-codes/{id}/deactivate', [HsnCodeController::class, 'deactivate'])->name('hsn-codes.deactivate');
        Route::patch('item-types/{id}/activate', [ItemTypeController::class, 'activate'])->name('item-types.activate');
        Route::patch('item-types/{id}/deactivate', [ItemTypeController::class, 'deactivate'])->name('item-types.deactivate');
        Route::resource('item-types', ItemTypeController::class)->except(['show']);
        Route::patch('fabric-qualities/{id}/activate', [FabricQualityController::class, 'activate'])->name('fabric-qualities.activate');
        Route::patch('fabric-qualities/{id}/deactivate', [FabricQualityController::class, 'deactivate'])->name('fabric-qualities.deactivate');
        Route::resource('fabric-qualities', FabricQualityController::class)->except(['show']);
        Route::patch('fabric-fault-reasons/{id}/activate', [FabricFaultReasonController::class, 'activate'])->name('fabric-fault-reasons.activate');
        Route::patch('fabric-fault-reasons/{id}/deactivate', [FabricFaultReasonController::class, 'deactivate'])->name('fabric-fault-reasons.deactivate');
        Route::resource('fabric-fault-reasons', FabricFaultReasonController::class)->except(['show']);
        Route::get('fabric-fault-reasons/options', [FabricFaultReasonController::class, 'options'])->middleware('permission:masters.view')->name('fabric-fault-reasons.options');
        Route::patch('printing-designs/{id}/activate', [PrintingDesignController::class, 'activate'])->name('printing-designs.activate');
        Route::patch('printing-designs/{id}/deactivate', [PrintingDesignController::class, 'deactivate'])->name('printing-designs.deactivate');
        Route::get('printing-designs/options', [PrintingDesignController::class, 'options'])->name('printing-designs.options');
        Route::resource('printing-designs', PrintingDesignController::class)->except(['show']);

        // Admin item/yarn routes.
        Route::get('/items/{id}/manage-yarn', [ItemController::class, 'manageYarn'])->name('items.manage-yarn');
        Route::post('/items/add-manage-yarn', [ItemController::class, 'addManageYarn'])->name('items.add-manage-yarn');
        Route::delete('/items/delete-yarn/{id}', [ItemController::class, 'deleteYarn'])->name('items.delete-yarn');
        Route::resource('items', ItemController::class)->except(['show']);
        Route::resource('item-yarn-requirements', ItemYarnRequirementController::class)->except(['show']);

        // Admin configuration routes.
        Route::resource('notifications', NotificationController::class)->except(['show']);
        Route::resource('packaging-types', PackagingTypeController::class)->except(['show']);
        Route::resource('unit-types', UnitTypeController::class)->except(['show']);
        Route::patch('/unit-types/{id}/activate', [UnitTypeController::class, 'activate'])->name('unit-types.activate');
        Route::patch('/unit-types/{id}/deactivate', [UnitTypeController::class, 'deactivate'])->name('unit-types.deactivate');
        Route::resource('financial-years', FinancialYearController::class)->except(['show']);
        Route::post('/financial-years/{financial_year}/set-current', [FinancialYearController::class, 'setCurrent'])->name('financial-years.set-current');
        Route::get('/number-series', [NumberSeriesController::class, 'index'])->middleware('permission:number-series.view')->name('number-series.index');
        Route::put('/number-series/{number_series}', [NumberSeriesController::class, 'update'])->middleware('permission:number-series.manage')->name('number-series.update');
        Route::get('/document-settings', [DocumentSettingsController::class, 'edit'])->name('document-settings.edit');
        Route::put('/document-settings', [DocumentSettingsController::class, 'update'])->name('document-settings.update');
        Route::resource('user-web-pages', UserWebPageController::class)->except(['show']);
        Route::resource('warehouses', WarehouseController::class)->except(['show']);
        Route::patch('/warehouses/{id}/activate', [WarehouseController::class, 'activate'])->name('warehouses.activate');
        Route::patch('/warehouses/{id}/deactivate', [WarehouseController::class, 'deactivate'])->name('warehouses.deactivate');
        Route::resource('ware-house-compartments', WareHouseCompartmentController::class)->except(['show']);
        Route::patch('/ware-house-compartments/{id}/activate', [WareHouseCompartmentController::class, 'activate'])->name('ware-house-compartments.activate');
        Route::patch('/ware-house-compartments/{id}/deactivate', [WareHouseCompartmentController::class, 'deactivate'])->name('ware-house-compartments.deactivate');

        // Admin logs/security routes.
        Route::get('/login-attempts', [LoginAttemptController::class, 'index'])->name('login-attempts.index');
        Route::delete('/login-attempts/{id}', [LoginAttemptController::class, 'destroy'])->name('login-attempts.destroy');
        Route::get('/login-otps', [LoginOtpController::class, 'index'])->name('login-otps.index');
        Route::delete('/login-otps/{id}', [LoginOtpController::class, 'destroy'])->name('login-otps.destroy');
        Route::get('/user-activity-logs', [UserActivityLogController::class, 'index'])->name('user-activity-logs.index');
        Route::delete('/user-activity-logs/{id}', [UserActivityLogController::class, 'destroy'])->name('user-activity-logs.destroy');

        // Admin production/organization routes.
        Route::resource('departments', DepartmentController::class)->except(['show']);
        Route::patch('/departments/{department}/activate', [DepartmentController::class, 'activate'])->name('departments.activate');
        Route::patch('/departments/{department}/deactivate', [DepartmentController::class, 'deactivate'])->name('departments.deactivate');
        Route::resource('machines', MachineController::class)->except(['show']);
        Route::patch('/machines/{machine}/activate', [MachineController::class, 'activate'])->name('machines.activate');
        Route::patch('/machines/{machine}/deactivate', [MachineController::class, 'deactivate'])->name('machines.deactivate');
        Route::resource('machine-capacities', MachineCapacityController::class)->except(['show', 'destroy']);
        Route::delete('/machine-capacities/{machine_capacity}', [MachineCapacityController::class, 'destroy'])->name('machine-capacities.destroy');
        Route::resource('shifts', ShiftController::class)->except(['show']);
        Route::patch('/shifts/{shift}/activate', [ShiftController::class, 'activate'])->name('shifts.activate');
        Route::patch('/shifts/{shift}/deactivate', [ShiftController::class, 'deactivate'])->name('shifts.deactivate');
        Route::resource('office-ips', OfficeIpController::class)->except(['show']);
        Route::resource('process-items', ProcessItemController::class)->except(['show']);
        Route::get('/process-items/{process_item}/configuration', [ProcessItemController::class, 'configuration'])->name('process-items.configuration');
        Route::put('/process-items/{process_item}/configuration', [ProcessItemController::class, 'updateConfiguration'])->name('process-items.configuration.update');
        Route::patch('/process-items/{process_item}/activate', [ProcessItemController::class, 'activate'])->name('process-items.activate');
        Route::patch('/process-items/{process_item}/deactivate', [ProcessItemController::class, 'deactivate'])->name('process-items.deactivate');
        if (config('features.workflow_definitions')) {
            Route::get('/workflow-assignments', [WorkflowAssignmentController::class, 'index'])->name('workflow-assignments.index');
            Route::patch('/workflow-assignments/{sale_order_item}', [WorkflowAssignmentController::class, 'update'])->name('workflow-assignments.update');
            Route::resource('workflow-definitions', WorkflowDefinitionController::class)->only(['index', 'create', 'store', 'update']);
            Route::get('/workflow-definitions/{workflow_definition}/versions/{workflow_version}', [WorkflowDefinitionController::class, 'show'])->name('workflow-definitions.show');
            Route::post('/workflow-definitions/{workflow_definition}/versions', [WorkflowDefinitionController::class, 'createVersion'])->name('workflow-definitions.versions.store');
            Route::patch('/workflow-definitions/{workflow_definition}/versions/{workflow_version}/publish', [WorkflowDefinitionController::class, 'publishVersion'])->name('workflow-definitions.versions.publish');
            Route::post('/workflow-definitions/{workflow_definition}/versions/{workflow_version}/steps', [WorkflowDefinitionController::class, 'storeStep'])->name('workflow-definitions.steps.store');
            Route::put('/workflow-definitions/{workflow_definition}/versions/{workflow_version}/steps/{workflow_version_step}', [WorkflowDefinitionController::class, 'updateStep'])->name('workflow-definitions.steps.update');
            Route::delete('/workflow-definitions/{workflow_definition}/versions/{workflow_version}/steps/{workflow_version_step}', [WorkflowDefinitionController::class, 'destroyStep'])->name('workflow-definitions.steps.destroy');
            Route::delete('/workflow-definitions/{workflow_definition}/versions/{workflow_version}', [WorkflowDefinitionController::class, 'destroyVersion'])->name('workflow-definitions.versions.destroy');
        }
        Route::resource('individuals', IndividualController::class)->except(['show']);
        Route::resource('employees', EmployeeController::class)->except(['show']);
        Route::patch('/employees/{employee}/activate', [EmployeeController::class, 'activate'])->name('employees.activate');
        Route::patch('/employees/{employee}/deactivate', [EmployeeController::class, 'deactivate'])->name('employees.deactivate');
        Route::resource('customers', CustomerController::class)->except(['show']);
        Route::patch('/customers/{customer}/activate', [CustomerController::class, 'activate'])->name('customers.activate');
        Route::patch('/customers/{customer}/deactivate', [CustomerController::class, 'deactivate'])->name('customers.deactivate');
        Route::get('/customers/{customer}/addresses/create', [CustomerAddressController::class, 'create'])->name('customers.addresses.create');
        Route::post('/customers/{customer}/addresses', [CustomerAddressController::class, 'store'])->name('customers.addresses.store');
        Route::get('/customers/{customer}/addresses/{address}/edit', [CustomerAddressController::class, 'edit'])->name('customers.addresses.edit');
        Route::put('/customers/{customer}/addresses/{address}', [CustomerAddressController::class, 'update'])->name('customers.addresses.update');
        Route::delete('/customers/{customer}/addresses/{address}', [CustomerAddressController::class, 'destroy'])->name('customers.addresses.destroy');
        Route::resource('vendors', VendorController::class)->except(['show']);
        Route::patch('/vendors/{vendor}/activate', [VendorController::class, 'activate'])->name('vendors.activate');
        Route::patch('/vendors/{vendor}/deactivate', [VendorController::class, 'deactivate'])->name('vendors.deactivate');
        Route::get('/vendors/{vendor}/addresses/create', [VendorAddressController::class, 'create'])->name('vendors.addresses.create');
        Route::post('/vendors/{vendor}/addresses', [VendorAddressController::class, 'store'])->name('vendors.addresses.store');
        Route::get('/vendors/{vendor}/addresses/{address}/edit', [VendorAddressController::class, 'edit'])->name('vendors.addresses.edit');
        Route::put('/vendors/{vendor}/addresses/{address}', [VendorAddressController::class, 'update'])->name('vendors.addresses.update');
        Route::delete('/vendors/{vendor}/addresses/{address}', [VendorAddressController::class, 'destroy'])->name('vendors.addresses.destroy');
        Route::resource('transporters', TransporterController::class)->except(['show']);
        Route::patch('/transporters/{transporter}/activate', [TransporterController::class, 'activate'])->name('transporters.activate');
        Route::patch('/transporters/{transporter}/deactivate', [TransporterController::class, 'deactivate'])->name('transporters.deactivate');
        Route::get('/transporters/{transporter}/addresses/create', [TransporterAddressController::class, 'create'])->name('transporters.addresses.create');
        Route::post('/transporters/{transporter}/addresses', [TransporterAddressController::class, 'store'])->name('transporters.addresses.store');
        Route::get('/transporters/{transporter}/addresses/{address}/edit', [TransporterAddressController::class, 'edit'])->name('transporters.addresses.edit');
        Route::put('/transporters/{transporter}/addresses/{address}', [TransporterAddressController::class, 'update'])->name('transporters.addresses.update');
        Route::delete('/transporters/{transporter}/addresses/{address}', [TransporterAddressController::class, 'destroy'])->name('transporters.addresses.destroy');

        // Company-scoped RBAC administration. System roles are deliberately absent.
        Route::middleware('permission:roles.view')->group(function (): void {
            Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');
            Route::get('/roles/create', [RoleController::class, 'create'])->middleware('permission:roles.create')->name('roles.create');
            Route::post('/roles', [RoleController::class, 'store'])->middleware('permission:roles.create')->name('roles.store');
            Route::get('/roles/{role}/edit', [RoleController::class, 'edit'])->middleware('permission:roles.update')->name('roles.edit');
            Route::put('/roles/{role}', [RoleController::class, 'update'])->middleware('permission:roles.update')->name('roles.update');
            Route::get('/roles/{role}/assign', [RoleController::class, 'assignForm'])->middleware('permission:roles.assign')->name('roles.assign');
            Route::post('/roles/{role}/assign', [RoleController::class, 'assign'])->middleware('permission:roles.assign')->name('roles.assign.store');
            Route::delete('/role-assignments/{assignment}', [RoleController::class, 'revoke'])->middleware('permission:roles.assign')->name('roles.assignments.revoke');
        });
        Route::middleware('permission:users.manage')->group(function (): void {
            Route::get('/users/{user}/permissions', [UserPermissionController::class, 'index'])->name('users.permissions.edit');
            Route::put('/users/{user}/permissions', [UserPermissionController::class, 'update'])->name('users.permissions.update');
        });
        Route::middleware('permission:users.manage')->get('/users', [UserController::class, 'index'])->name('users.index');
        Route::middleware('permission:users.manage')->group(function (): void {
            Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
            Route::post('/users', [UserController::class, 'store'])->name('users.store');
        });
        Route::middleware('permission:users.manage')->group(function (): void {
            Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
            Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        });
        Route::patch('/users/{user}/activate', [UserController::class, 'activate'])->middleware('permission:users.manage')->name('users.activate');
        Route::patch('/users/{user}/deactivate', [UserController::class, 'deactivate'])->middleware('permission:users.manage')->name('users.deactivate');
        Route::post('/users/{user}/reset-password', [UserController::class, 'resetPassword'])->middleware('permission:users.manage')->name('users.reset-password');
        Route::get('/users/{user}/department-access', [UserController::class, 'departmentAccess'])->middleware('permission:users.manage')->name('users.department-access');
        Route::put('/users/{user}/department-access', [UserController::class, 'updateDepartmentAccess'])->middleware('permission:users.manage')->name('users.department-access.update');
        Route::middleware('permission:audit-logs.view')->group(function (): void {
            Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
            Route::get('/audit-logs/{auditLog}', [AuditLogController::class, 'show'])->name('audit-logs.show');
        });

        Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');
    });
});
