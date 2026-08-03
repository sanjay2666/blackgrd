<?php

route mein decrypt kyo use kiya gaya hai

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\StateController;
use App\Http\Controllers\Admin\AllPageController;
use App\Http\Controllers\Admin\ColourController;
use App\Http\Controllers\Admin\CompanyController;
use App\Http\Controllers\Admin\CotingController;
use App\Http\Controllers\Admin\CourierController;
use App\Http\Controllers\Admin\GstRateController;
use App\Http\Controllers\Admin\ItemTypeController;
use App\Http\Controllers\Admin\ItemController;
use App\Http\Controllers\Admin\ItemYarnRequirementController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\PackagingTypeController;
use App\Http\Controllers\Admin\UnitTypeController;
use App\Http\Controllers\Admin\UserWebPageController;
use App\Http\Controllers\Admin\WarehouseController;
use App\Http\Controllers\Admin\WareHouseCompartmentController;
use App\Http\Controllers\Admin\LoginAttemptController;
use App\Http\Controllers\Admin\LoginOtpController;
use App\Http\Controllers\Admin\UserActivityLogController;
use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Admin\ProcessItemController;
use App\Http\Controllers\Admin\IndividualController;
use App\Http\Controllers\Admin\MachineController;
use App\Http\Controllers\Admin\OfficeIpController;
use App\Http\Controllers\Auth\AdminAuthController;
use App\Http\Controllers\Auth\UserAuthController;
use App\Http\Controllers\CommonController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\SaleOrderController;
use App\Models\Department;
use App\Models\Individual;
use App\Models\Machine;
use App\Models\OfficeIp;
use App\Models\ProcessItem;
use App\Models\State;
use Illuminate\Support\Facades\Route;

Route::bind('individual', function ($value) {
    try {
        $id = decrypt($value);
    } catch (Exception $e) {
        $id = $value;
    }

    return Individual::findOrFail($id);
});

Route::bind('department', function ($value) {
    try {
        $id = decrypt($value);
    } catch (Exception $e) {
        $id = $value;
    }

    return Department::findOrFail($id);
});

Route::bind('process_item', function ($value) {
    try {
        $id = decrypt($value);
    } catch (Exception $e) {
        $id = $value;
    }

    return ProcessItem::findOrFail($id);
});

Route::bind('machine', function ($value) {
    try {
        $id = decrypt($value);
    } catch (Exception $e) {
        $id = $value;
    }

    return Machine::findOrFail($id);
});

Route::bind('office_ip', function ($value) {
    try {
        $id = decrypt($value);
    } catch (Exception $e) {
        $id = $value;
    }

    return OfficeIp::findOrFail($id);
});


Route::get('/', [PageController::class, 'home'])->name('home');
Route::get('/list_customer', [CommonController::class, 'list_customer'])->name('list_customer');
Route::get('/fabric_list_item', [CommonController::class, 'fabric_list_item'])->name('fabric_list_item');
Route::get('/list_individual', [CommonController::class, 'list_individual'])->name('list_individual');
Route::get('/list_coating', [CommonController::class, 'list_coating'])->name('list_coating');
Route::get('/customer-addresses', [CommonController::class, 'customer_addresses'])->name('customer_addresses');

Route::middleware('guest:web')->group(function () {
    Route::get('/register', [UserAuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [UserAuthController::class, 'register'])->name('register.store');
    Route::get('/login', [UserAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [UserAuthController::class, 'login'])->name('login.store');
    Route::get('/forgot-password', [UserAuthController::class, 'showForgotPassword'])->name('password.request');
    Route::post('/forgot-password', [UserAuthController::class, 'sendResetLink'])->name('password.email');
    Route::get('/reset-password/{token}', [UserAuthController::class, 'showResetPassword'])->name('password.reset');
    Route::post('/reset-password', [UserAuthController::class, 'resetPassword'])->name('password.update');
});

Route::middleware('auth:web')->group(function () {
    Route::get('/dashboard', [PageController::class, 'dashboard'])->name('dashboard');
    Route::get('/sale-orders', [SaleOrderController::class, 'index'])->name('sale-orders.index');
    Route::get('/sale-orders/create', [SaleOrderController::class, 'create'])->name('sale-orders.create');
    Route::post('/sale-orders', [SaleOrderController::class, 'store'])->name('sale-orders.store');
    Route::post('/logout', [UserAuthController::class, 'logout'])->name('logout');
});

Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest:admin')->group(function () {
        Route::get('/login', [AdminAuthController::class, 'showLogin'])->name('login');
        Route::post('/login', [AdminAuthController::class, 'login'])->name('login.store');
    });

    Route::middleware('auth:admin')->group(function () {
        Route::get('/dashboard', AdminDashboardController::class)->name('dashboard');
        Route::get('/individuals/{individual}/edit', function (Individual $individual) {
            abort_if($individual->status === 'Deleted', 404);
            $individual->load(['activeAddresses', 'processItem', 'department']);

            return view('admin.individuals.edit', [
                'individual' => $individual,
                'types' => ['customers', 'master', 'agents', 'labourer', 'vendors', 'transport', 'employee'],
                'vendorTypes' => ['yarn', 'greige', 'chemical', 'maintanance', 'general'],
                'processItems' => ProcessItem::where('status', 'Active')->orderBy('id', 'asc')->get(),
                'departments' => Department::where('status', 'Active')->orderBy('id', 'asc')->get(),
                'states' => State::where('status', 'Active')->orderBy('id', 'asc')->get(),
            ]);
        })->name('individuals.edit');
        Route::resource('states', StateController::class)->except(['show']);
        Route::resource('all-pages', AllPageController::class)->except(['show']);
        Route::resource('colours', ColourController::class)->except(['show']);
        Route::resource('companies', CompanyController::class)->except(['show']);
        Route::resource('cotings', CotingController::class)->except(['show']);
        Route::resource('couriers', CourierController::class)->except(['show']);
        Route::resource('gst-rates', GstRateController::class)->except(['show']);
        Route::resource('item-types', ItemTypeController::class)->except(['show']);
        Route::get('/items/{id}/manage-yarn', [ItemController::class, 'manageYarn'])->name('items.manage-yarn');
        Route::post('/items/add-manage-yarn', [ItemController::class, 'addManageYarn'])->name('items.add-manage-yarn');
        Route::delete('/items/delete-yarn/{id}', [ItemController::class, 'deleteYarn'])->name('items.delete-yarn');
        Route::resource('items', ItemController::class)->except(['show']);
        Route::resource('item-yarn-requirements', ItemYarnRequirementController::class)->except(['show']);
        Route::resource('notifications', NotificationController::class)->except(['show']);
        Route::resource('packaging-types', PackagingTypeController::class)->except(['show']);
        Route::resource('unit-types', UnitTypeController::class)->except(['show']);
        Route::resource('user-web-pages', UserWebPageController::class)->except(['show']);
        Route::resource('warehouses', WarehouseController::class)->except(['show']);
        Route::resource('ware-house-compartments', WareHouseCompartmentController::class)->except(['show']);
        Route::get('/login-attempts', [LoginAttemptController::class, 'index'])->name('login-attempts.index');
        Route::delete('/login-attempts/{id}', [LoginAttemptController::class, 'destroy'])->name('login-attempts.destroy');
        Route::get('/login-otps', [LoginOtpController::class, 'index'])->name('login-otps.index');
        Route::delete('/login-otps/{id}', [LoginOtpController::class, 'destroy'])->name('login-otps.destroy');
        Route::get('/user-activity-logs', [UserActivityLogController::class, 'index'])->name('user-activity-logs.index');
        Route::delete('/user-activity-logs/{id}', [UserActivityLogController::class, 'destroy'])->name('user-activity-logs.destroy');        Route::resource('departments', DepartmentController::class)->except(['show']);
        Route::resource('machines', MachineController::class)->except(['show']);
        Route::resource('office-ips', OfficeIpController::class)->except(['show']);
        Route::resource('process-items', ProcessItemController::class)->except(['show']);
        Route::resource('individuals', IndividualController::class)->except(['show', 'edit']);
        Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');
    });
});


Route::get('show-saleorder-workorder-details/{id}', [SaleOrderController::class, 'showSaleOrderWorkOrderDetails'])->name('saleorders.workorder-details');

Route::get('print-saleorder/{id}', [SaleOrderController::class, 'printSaleOrder'])->name('saleorders.print');




