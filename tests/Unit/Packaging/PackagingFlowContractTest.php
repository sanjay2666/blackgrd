<?php

namespace Tests\Unit\Packaging;

use Tests\TestCase;

class PackagingFlowContractTest extends TestCase
{
    public function test_packaging_controller_preserves_roll_identity_and_blocks_over_packaging_before_stock_issue(): void
    {
        $source = file_get_contents(app_path('Http/Controllers/PackagingController.php'));

        $this->assertStringContainsString("->where('is_work_completed', 1)", $source);
        $this->assertStringContainsString("->where('is_work_final_completed', '0')", $source);
        $this->assertStringContainsString("->where('insp_bal_quan_size', '>', 0)", $source);
        $this->assertStringContainsString('Requested packaging quantity exceeds the sale-order item remaining quantity.', $source);
        $this->assertStringContainsString('Requested packaging quantity exceeds available stock for Roll/Taka', $source);
        $this->assertStringContainsString('\'packet_number\' => $stock->packet_number', $source);
        $this->assertStringContainsString('\'insp_taka_number\' => $stock->insp_taka_number', $source);
        $this->assertStringContainsString('lockForUpdate()', $source);
        $this->assertStringContainsString('\'packaging_mode\' => $request->packaging_mode', $source);
        $this->assertStringContainsString('Different customers cannot be combined in one Packaging Order.', $source);
        $this->assertStringContainsString('\'dyeing_lot_number\' => $stock->dyeing_lot_number', $source);
    }

    public function test_warehouse_acceptance_and_reversal_keep_stock_movement_connected_and_non_negative(): void
    {
        $source = file_get_contents(app_path('Http/Controllers/PackagingController.php'));

        $this->assertStringContainsString('WarehouseOutItem::create(', $source);
        $this->assertStringContainsString('\'warehouse_out_item_id\' => $warehouseOutItem->id', $source);
        $this->assertStringContainsString('\'insp_bal_quan_size\' => max(0, $remainingStock)', $source);
        $this->assertStringContainsString('\'item_qty\' => round((float) $warehouseItem->item_qty - $quantity, 2)', $source);
        $this->assertStringContainsString('InventoryMovementStatus::Reversed->value', $source);
        $this->assertStringContainsString("'is_packaging_done' => '0'", $source);
        $this->assertStringContainsString('DB::beginTransaction()', $source);
        $this->assertStringContainsString('DB::rollBack()', $source);
    }

    public function test_sales_challan_and_delivery_quantities_remain_outside_the_bounded_packaging_flow(): void
    {
        $source = file_get_contents(app_path('Http/Controllers/PackagingController.php'));

        $this->assertStringNotContainsString('delivered_item_mtr', $source);
        $this->assertStringNotContainsString('pending_item_mtr', $source);
        $this->assertStringNotContainsString('SalesChallan', $source);
    }

    public function test_bulk_and_sample_cart_remain_inside_the_packaging_controller_and_use_current_bootstrap_stack(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/PackagingController.php'));
        $cart = file_get_contents(resource_path('views/frontend/packaging/cart.blade.php'));
        $worklist = file_get_contents(resource_path('views/frontend/packaging/index.blade.php'));

        $this->assertStringContainsString('public function showPackagingOrderCart(Request $request)', $controller);
        $this->assertStringContainsString("'packaging_mode' => 'nullable|in:bulk,sample'", $controller);
        $this->assertStringContainsString('One Packaging Order can contain Sale Order Items for one customer only.', $controller);
        $this->assertStringContainsString('Packaging Cart', $cart);
        $this->assertStringContainsString('lot-running-total', $cart);
        $this->assertStringNotContainsString('Sample multi-order', $worklist);
    }
}
