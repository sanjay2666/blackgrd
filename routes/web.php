<?php

use App\Http\Controllers\Admin\AllPageController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\BranchFactoryController;
use App\Http\Controllers\Admin\ChemicalController;
use App\Http\Controllers\Admin\ColourController;
use App\Http\Controllers\Admin\CompanyController;
use App\Http\Controllers\Admin\CotingController;
use App\Http\Controllers\Admin\CourierController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Admin\DyeingColourController;
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
use App\Http\Controllers\Admin\MachineController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\NumberSeriesController;
use App\Http\Controllers\Admin\OfficeIpController;
use App\Http\Controllers\Admin\PackagingTypeController;
use App\Http\Controllers\Admin\PrintingDesignController;
use App\Http\Controllers\Admin\ProcessItemController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\StateController;
use App\Http\Controllers\Admin\UnitTypeController;
use App\Http\Controllers\Admin\UserActivityLogController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\UserPermissionController;
use App\Http\Controllers\Admin\UserWebPageController;
use App\Http\Controllers\Admin\WareHouseCompartmentController;
use App\Http\Controllers\Admin\WarehouseController;
use App\Http\Controllers\Auth\AdminAuthController;
use App\Http\Controllers\Auth\UserAuthController;
use App\Http\Controllers\CommonController;
use App\Http\Controllers\DyeingColourLookupController;
use App\Http\Controllers\JobMillWorkController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\SaleOrderController;
use App\Http\Controllers\WarehouseItemController;
use App\Http\Controllers\WorkOrderController;
use App\Http\Controllers\WorkProcessRequirementController;
use App\Http\Controllers\WorkPurchaseRequirementController;
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

    Route::post('/sale-orders', [SaleOrderController::class, 'store'])->name('sale-orders.store');
    Route::post('/ajax_script/deleteSaleOrder', [SaleOrderController::class, 'deleteSaleOrder'])->name('saleorders.delete');
    Route::post('/sale-order/submit-selected-items', [SaleOrderController::class, 'submitSelectedItems'])->name('sale-order.submit-selected-items');
    Route::post('/sale-order/update', [SaleOrderController::class, 'updateSaleOrder'])->name('sale-order.update');
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
    Route::get('/ajax_script/getProcessRequirementItems', [WorkProcessRequirementController::class, 'getProcessRequirementItems']);
    Route::get('/ajax_script/getLotReturnItems', [WorkProcessRequirementController::class, 'getLotReturnItems']);
    Route::get('/ajax_script/getBeamReturnItems', [WorkProcessRequirementController::class, 'getBeamReturnItems']);
    Route::get('/ajax_script/getSumWarehouseItemStockValue', [WorkProcessRequirementController::class, 'getSumWarehouseItemStockValue']);

    Route::get('/ajax_script/DenyWarehouseReq', [WorkProcessRequirementController::class, 'DenyWarehouseReq']);

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
        Route::resource('office-ips', OfficeIpController::class)->except(['show']);
        Route::resource('process-items', ProcessItemController::class)->except(['show']);
        Route::patch('/process-items/{process_item}/activate', [ProcessItemController::class, 'activate'])->name('process-items.activate');
        Route::patch('/process-items/{process_item}/deactivate', [ProcessItemController::class, 'deactivate'])->name('process-items.deactivate');
        Route::resource('individuals', IndividualController::class)->except(['show']);

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
