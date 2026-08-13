<?php

namespace Tests\Unit\Packaging;

use Tests\TestCase;

class PackagingOperationalPagesContractTest extends TestCase
{
    public function test_operational_list_history_print_and_stock_refresh_stay_in_packaging_controller(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/PackagingController.php'));
        $routes = file_get_contents(base_path('routes/web.php'));

        $this->assertStringContainsString('public function showPackagingAvailableOrders(Request $request)', $controller);
        $this->assertStringContainsString('public function showPackagedOrders(Request $request)', $controller);
        $this->assertStringContainsString('public function listPackagingLots(Request $request)', $controller);
        $this->assertStringContainsString('public function getPackagingAvailableStock(Request $request)', $controller);
        $this->assertStringContainsString('public function openPackagingCartForSaleOrderItem(Request $request, int $saleOrderItem)', $controller);
        $this->assertStringContainsString('public function showPackagingOrderCart(Request $request)', $controller);
        $this->assertStringContainsString('public function storePackagingOrder(Request $request)', $controller);
        $this->assertStringContainsString('public function showPackagingOrderDetails(Request $request, int $packagingOrder)', $controller);
        $this->assertStringContainsString('public function printPackagingSlip(Request $request, int $packagingOrder)', $controller);
        $this->assertStringContainsString('public function acceptPackagingWarehouseStock(Request $request, int $packagingOrder)', $controller);
        $this->assertStringContainsString('public function updatePackagingPackedQuantity(Request $request, int $packagingOrder)', $controller);
        $this->assertStringContainsString('public function cancelPackagingOrderAndRestoreStock(Request $request, int $packagingOrder)', $controller);
        $this->assertStringContainsString("'allocation_status', 'proposed'", $controller);
        $this->assertStringContainsString("'available_quantity'", $controller);
        $this->assertStringContainsString("Route::get('/show-add-packaging-list'", $routes);
        $this->assertStringContainsString("Route::get('/show-packagings'", $routes);
        $this->assertStringContainsString("Route::get('/packaging/available-stock'", $routes);
        $this->assertStringContainsString("Route::get('/packaging/lot-autocomplete'", $routes);
        $this->assertStringContainsString("Route::get('/packaging/{packagingOrder}/print'", $routes);
        $this->assertStringContainsString("->name('packaging.get-available-stock')", $routes);
        $this->assertStringContainsString("->name('packaging.cancel-and-restore-stock')", $routes);
        $this->assertStringContainsString("where('item_type_id', (int) \$request->item_type_id)", $controller);
        $this->assertStringContainsString("where('coating_type', 'like'", $controller);
        $this->assertStringContainsString("whereDate('expect_delivery_date'", $controller);
        $this->assertStringContainsString("where('challan_number', 'like'", $controller);
        $this->assertStringContainsString("where('packaging_mode', \$request->packaging_mode)", $controller);
        $this->assertStringContainsString("where('is_work_completed', 1)", $controller);
        $this->assertStringContainsString("where('is_work_final_completed', '0')", $controller);
        $this->assertStringContainsString('taka_count', $controller);
    }

    public function test_operational_pages_keep_bulk_sample_roll_identity_dispatch_link_and_duplicate_submit_protection(): void
    {
        $available = file_get_contents(resource_path('views/frontend/packaging/index.blade.php'));
        $history = file_get_contents(resource_path('views/frontend/packaging/history.blade.php'));
        $cart = file_get_contents(resource_path('views/frontend/packaging/cart.blade.php'));
        $detail = file_get_contents(resource_path('views/frontend/packaging/show.blade.php'));
        $print = file_get_contents(resource_path('views/frontend/packaging/print.blade.php'));
        $filters = file_get_contents(resource_path('views/frontend/packaging/partials/filter-autocomplete.blade.php'));

        $this->assertStringNotContainsString('All Priorities', $available);
        $this->assertStringNotContainsString('All Packaging States', $available);
        $this->assertStringNotContainsString('Bulk / Lot-wise', $available);
        $this->assertStringNotContainsString('Sample multi-order', $available);
        $this->assertStringNotContainsString('Select items for one customer.', $available);
        $this->assertStringContainsString('packaging-customer-search', $available);
        $this->assertStringContainsString('packaging-customer-id', $available);
        $this->assertStringContainsString('packaging_remaining_quantity', $available);
        $this->assertStringContainsString('packaging-customer-search', $history);
        $this->assertStringContainsString('packaging-lot-search', $history);
        $this->assertStringContainsString('packaging-item-type', $available);
        $this->assertStringContainsString('packaging-coating', $available);
        $this->assertStringContainsString('packaging-from-date', $available);
        $this->assertStringContainsString('Search', $available);
        $this->assertStringContainsString('Reset', $available);
        $this->assertStringContainsString('packaging-mode', $history);
        $this->assertStringContainsString('packaging-challan-number', $history);
        $this->assertStringContainsString('Lots / Rolls / Taka', $history);
        $this->assertStringContainsString('Reset', $history);
        $this->assertStringContainsString('minLength: 2', $filters);
        $this->assertStringContainsString(".on('input'", $filters);
        $this->assertStringContainsString("route('list_customer')", $filters);
        $this->assertStringContainsString("route('list_item')", $filters);
        $this->assertStringContainsString("route('find_saleOrderNumer')", $filters);
        $this->assertStringContainsString('sales-challans.create', $history);
        $this->assertStringContainsString('Print Slip', $history);
        $this->assertStringContainsString('available-stock', $cart);
        $this->assertStringContainsString('lot-running-total', $cart);
        $this->assertStringContainsString("prop('disabled', true).text('Saving...')", $cart);
        $this->assertStringContainsString('Sales Challan', $detail);
        $this->assertStringContainsString('Cancel / Return to Warehouse', $detail);
        $this->assertStringContainsString('Lot-wise Total', $print);
        $this->assertStringContainsString('packet_number', $print);
        $this->assertStringContainsString('insp_taka_number', $print);
    }
}
