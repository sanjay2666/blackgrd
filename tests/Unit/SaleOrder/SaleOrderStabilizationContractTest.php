<?php

namespace Tests\Unit\SaleOrder;

use PHPUnit\Framework\TestCase;

class SaleOrderStabilizationContractTest extends TestCase
{
    public function test_sale_order_item_is_company_scoped_without_process_order_fields(): void
    {
        $root = dirname(__DIR__, 3);
        $model = file_get_contents($root.'/app/Models/SaleOrderItem.php');
        $migration = file_get_contents($root.'/database/migrations/2026_07_19_000002_create_sale_order_items_table.php');
        $scopeRepair = file_get_contents($root.'/database/migrations/2026_08_12_000013_add_company_scope_to_sale_order_items.php');

        $this->assertStringContainsString('BelongsToCompany', $model);
        $this->assertStringContainsString("unsignedInteger('company_id')", $scopeRepair);
        $this->assertStringContainsString("join('sale_orders as order'", $scopeRepair);
        $this->assertStringContainsString("orWhereNull('order.company_id')", $scopeRepair);
        $this->assertStringNotContainsString('process_sequence', $migration);
        $this->assertStringNotContainsString('print_position', $migration);
    }

    public function test_task_document_preserves_requirement_and_workflow_boundaries(): void
    {
        $architecture = file_get_contents(dirname(__DIR__, 3).'/docs/architecture/sale-order-stabilization.md');

        $this->assertStringContainsString('Sale Order Item owns the fabric/item production requirement', $architecture);
        $this->assertStringContainsString('Printing requirement does not determine Printing position', $architecture);
        $this->assertStringContainsString('Changing current Customer or master data must not rewrite historical Sale Order transaction snapshots', $architecture);
    }

    public function test_update_route_uses_customer_and_address_validation(): void
    {
        $routes = file_get_contents(dirname(__DIR__, 3).'/routes/web.php');

        $this->assertStringContainsString("Route::post('/sale-order/update', [SaleOrderController::class, 'updateSaleOrder'])->middleware(ValidateCustomerSaleOrder::class)", $routes);
    }
}
