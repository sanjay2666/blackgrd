<?php

namespace Tests\Unit\SalesChallan;

use Tests\TestCase;

class SalesChallanFlowContractTest extends TestCase
{
    public function test_sales_challan_uses_locked_packaging_rolls_and_keeps_warehouse_issue_outside_customer_dispatch(): void
    {
        $source = file_get_contents(app_path('Http/Controllers/SalesChallanController.php'));

        $this->assertStringContainsString('DB::beginTransaction()', $source);
        $this->assertStringContainsString('DB::rollBack()', $source);
        $this->assertStringContainsString('lockForUpdate()', $source);
        $this->assertStringContainsString("'submission_key'", $source);
        $this->assertStringContainsString("'dispatched_quantity' => round((float) \$allocation->dispatched_quantity + (float) \$roll->dispatched_quantity, 2)", $source);
        $this->assertStringContainsString("'delivered_item_mtr' => \$delivered", $source);
        $this->assertStringContainsString("'pending_item_mtr'", $source);
        $this->assertStringContainsString("'status' => 'Cancelled'", $source);
        $this->assertStringContainsString("'financial_year_id' => \$financialYear->id", $source);
        $this->assertStringContainsString("where('financial_year_id', (int) dec((string) \$request->financial_year_id))", $source);
        $this->assertStringNotContainsString('WarehouseOutItem::create(', $source);
        $this->assertStringNotContainsString("'remaining_quantity' => max(0, round((float) \$allocation->accepted_quantity - (float) \$allocation->packed_quantity, 2))", $source);
    }

    public function test_print_layout_keeps_exact_roll_taka_meter_and_lot_totals(): void
    {
        $source = file_get_contents(resource_path('views/frontend/sales_challans/print.blade.php'));

        $this->assertStringContainsString('Sales Challan', $source);
        $this->assertStringContainsString('Lot-wise Total', $source);
        $this->assertStringContainsString('packet_number', $source);
        $this->assertStringContainsString('insp_taka_number', $source);
        $this->assertStringContainsString('dispatched_quantity', $source);
        $this->assertStringContainsString('LR / Bilty / GR', $source);
        $this->assertStringContainsString('DUPLICATE', $source);
    }
}
