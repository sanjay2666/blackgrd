<?php

namespace Tests\Feature\SalesChallan;

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class SalesChallanSchemaIntegrationTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::connection()->getDriverName() !== 'mysql') {
            $this->markTestSkipped('Sales Challan schema integration requires disposable MySQL.');
        }
        if (DB::connection()->getDatabaseName() !== 'blackgrd_schema_testing') {
            $this->fail('Refusing Sales Challan schema integration tests outside blackgrd_schema_testing.');
        }
    }

    public function test_sales_challan_tables_preserve_header_item_and_physical_roll_traceability(): void
    {
        $this->assertTrue(Schema::hasTable('sales_challans'));
        $this->assertTrue(Schema::hasTable('sales_challan_items'));
        $this->assertTrue(Schema::hasTable('sales_challan_roll_allocations'));
        $this->assertTrue(Schema::hasColumns('sales_challans', ['company_id', 'department_id', 'customer_id', 'financial_year_id', 'challan_number', 'status', 'billing_address', 'shipping_address', 'transporter_id', 'lr_number', 'lr_date', 'vehicle_number', 'driver_name', 'submission_key', 'print_count']));
        $this->assertTrue(Schema::hasColumns('sales_challan_items', ['financial_year_id', 'sales_challan_id', 'packaging_order_id', 'packaging_order_item_id', 'sale_order_id', 'sale_order_item_id', 'item_name', 'grey_quality', 'dyeing_color', 'coating_type', 'final_dispatch_width', 'tube_width']));
        $this->assertTrue(Schema::hasColumns('sales_challan_roll_allocations', ['financial_year_id', 'sales_challan_id', 'sales_challan_item_id', 'packaging_roll_allocation_id', 'warehouse_item_stock_id', 'dyeing_lot_number', 'packet_number', 'insp_taka_number', 'packed_quantity_snapshot', 'previously_dispatched_quantity_snapshot', 'dispatched_quantity']));
    }

    public function test_same_source_roll_cannot_be_registered_twice_on_one_challan(): void
    {
        $suffix = random_int(100000, 999999);
        $challanId = DB::table('sales_challans')->insertGetId([
            'company_id' => $suffix, 'customer_id' => $suffix, 'challan_number' => 'SC-'.$suffix, 'status' => 'Draft', 'challan_date' => now()->toDateString(), 'customer_name' => 'Schema Test', 'submission_key' => (string) Str::uuid(), 'record_status' => 'Active',
        ]);
        $itemId = DB::table('sales_challan_items')->insertGetId([
            'company_id' => $suffix, 'sales_challan_id' => $challanId, 'packaging_order_id' => $suffix, 'packaging_order_item_id' => $suffix, 'sale_order_item_id' => $suffix, 'dispatched_quantity' => 10, 'record_status' => 'Active',
        ]);
        $allocation = [
            'company_id' => $suffix, 'sales_challan_id' => $challanId, 'sales_challan_item_id' => $itemId, 'packaging_order_id' => $suffix, 'packaging_order_item_id' => $suffix, 'packaging_roll_allocation_id' => $suffix, 'warehouse_item_stock_id' => $suffix, 'dyeing_lot_number' => 'LOT-'.$suffix, 'packet_number' => 'ROL-'.$suffix, 'dispatched_quantity' => 10, 'record_status' => 'Active',
        ];
        DB::table('sales_challan_roll_allocations')->insert($allocation);

        $this->expectException(QueryException::class);
        DB::table('sales_challan_roll_allocations')->insert($allocation);
    }
}
