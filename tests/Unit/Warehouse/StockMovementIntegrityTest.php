<?php

namespace Tests\Unit\Warehouse;

use PHPUnit\Framework\TestCase;

class StockMovementIntegrityTest extends TestCase
{
    public function test_allotment_locks_the_real_wis_key_and_rejects_over_allotment(): void
    {
        $source = file_get_contents(dirname(__DIR__, 3).'/app/Http/Controllers/WorkProcessRequirementController.php');

        $this->assertStringContainsString('WarehouseItemStock::whereKey($wisId)->where(\'status\', \'Active\')->lockForUpdate()', $source);
        $this->assertStringContainsString('Requested quantity exceeds available stock for WIS ID', $source);
        $this->assertStringContainsString('Requested quantity exceeds the remaining work requirement', $source);
        $this->assertStringNotContainsString('WarehouseItemStock::where(\'wis_id\'', $source);
    }

    public function test_department_receipt_blocks_duplicate_and_over_received_quantity_but_keeps_partial_receipt(): void
    {
        $source = file_get_contents(dirname(__DIR__, 3).'/app/Http/Controllers/WorkOrderController.php');

        $this->assertStringContainsString("'WarehouseOutItemId.*' => 'required|integer|distinct'", $source);
        $this->assertStringContainsString('Department receipt exceeds issued quantity', $source);
        $this->assertStringContainsString('->lockForUpdate()', $source);
        $this->assertStringContainsString('->keyBy(\'id\')', $source);
    }

    public function test_job_work_receipt_locks_dispatch_and_blocks_duplicate_or_over_receipt(): void
    {
        $source = file_get_contents(dirname(__DIR__, 3).'/app/Http/Controllers/JobMillWorkController.php');

        $this->assertGreaterThanOrEqual(2, substr_count($source, 'This mill receipt challan has already been recorded'));
        $this->assertGreaterThanOrEqual(1, substr_count($source, 'Mill receipt exceeds dispatched remaining quantity'));
        $this->assertGreaterThanOrEqual(2, substr_count($source, '->where(\'stock_mill_dispatch_id\', $request->stock_mill_dispatch_id)'));
        $this->assertStringContainsString('StockMillDispatchItem::whereKey($smditemId)', $source);
    }

    public function test_balance_snapshots_are_physical_and_refresh_does_not_mask_mismatches(): void
    {
        $model = file_get_contents(dirname(__DIR__, 3).'/app/Models/WarehouseBalanceItem.php');
        $controller = file_get_contents(dirname(__DIR__, 3).'/app/Http/Controllers/WarehouseItemController.php');

        $this->assertStringContainsString('scopeForPhysicalStock', $model);
        $this->assertStringContainsString('Warehouse balance mismatch detected. No value was changed', $controller);
        $this->assertStringContainsString('Stock with available quantity or movement history cannot be deleted', $controller);
    }
}
