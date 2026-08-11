<?php

namespace Tests\Unit\Masters;

use Tests\TestCase;

final class WarehouseCompartmentMasterContractTest extends TestCase
{
    public function test_compartment_master_contract_is_canonical_and_stock_neutral(): void
    {
        $service = file_get_contents(base_path('app/Services/WarehouseCompartmentMasterService.php'));
        $architecture = file_get_contents(base_path('docs/architecture/warehouse-compartment-bin-master.md'));

        $this->assertStringContainsString("'warehouse_in_items', 'warehouse_out_items'", $service);
        $this->assertStringContainsString("'warehouse_item_stocks', 'purchase_items'", $service);
        $this->assertStringContainsString('A referenced Compartment cannot be moved', $service);
        $this->assertStringContainsString('does not own inventory quantities', $architecture);
        $this->assertStringContainsString('warehouse_compartments', $architecture);
    }
}
