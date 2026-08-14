<?php

namespace Tests\Unit\Warehouse;

use PHPUnit\Framework\TestCase;

final class WarehouseLocationEditorContractTest extends TestCase
{
    public function test_warehouse_selection_refetches_only_its_compartments(): void
    {
        $view = file_get_contents(dirname(__DIR__, 3).'/resources/views/frontend/warehouseitems/show_stock_details_listing.blade.php');

        $this->assertStringContainsString('class="warehouseSelect form-control input-sm is-hidden"', $view);
        $this->assertStringContainsString("fetchWarehouseCompOptions(stockId, this.value, row.querySelector(`.warehouseCompSelect[data-id='\${stockId}']`), '');", $view);
        $this->assertStringContainsString('"warehouseId": warehouseId', $view);
    }
}
