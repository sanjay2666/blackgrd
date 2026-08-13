<?php

namespace Tests\Feature\Packaging;

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PackagingSchemaIntegrationTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::connection()->getDriverName() !== 'mysql') {
            $this->markTestSkipped('Packaging schema integration requires disposable MySQL.');
        }
        if (DB::connection()->getDatabaseName() !== 'blackgrd_schema_testing') {
            $this->fail('Refusing Packaging schema integration tests outside blackgrd_schema_testing.');
        }
    }

    public function test_packaging_tables_track_required_quantities_and_warehouse_out_linkage(): void
    {
        $this->assertTrue(Schema::hasTable('packaging_orders'));
        $this->assertTrue(Schema::hasTable('packaging_order_items'));
        $this->assertTrue(Schema::hasTable('packaging_roll_allocations'));
        $this->assertTrue(Schema::hasColumns('packaging_orders', [
            'company_id', 'allocated_quantity', 'packed_quantity', 'dispatched_quantity', 'cancelled_quantity', 'returned_quantity', 'remaining_quantity',
        ]));
        $this->assertTrue(Schema::hasColumns('packaging_roll_allocations', [
            'warehouse_item_stock_id', 'warehouse_out_item_id', 'allocated_quantity', 'accepted_quantity', 'packed_quantity', 'dispatched_quantity', 'cancelled_quantity', 'returned_quantity', 'remaining_quantity', 'allocation_status',
        ]));
    }

    public function test_same_roll_cannot_be_allocated_twice_to_one_packaging_item(): void
    {
        $suffix = random_int(100000, 999999);
        $orderId = DB::table('packaging_orders')->insertGetId([
            'company_id' => $suffix,
            'packaging_status' => 'draft',
            'allocated_quantity' => 30,
            'remaining_quantity' => 30,
            'status' => 'Active',
        ]);
        $itemId = DB::table('packaging_order_items')->insertGetId([
            'company_id' => $suffix,
            'packaging_order_id' => $orderId,
            'sale_order_item_id' => $suffix,
            'allocated_quantity' => 30,
            'remaining_quantity' => 30,
            'status' => 'Active',
        ]);
        $allocation = [
            'company_id' => $suffix,
            'packaging_order_id' => $orderId,
            'packaging_order_item_id' => $itemId,
            'warehouse_item_stock_id' => $suffix,
            'allocated_quantity' => 30,
            'remaining_quantity' => 30,
            'allocation_status' => 'proposed',
            'status' => 'Active',
        ];
        DB::table('packaging_roll_allocations')->insert($allocation);

        $this->expectException(QueryException::class);
        DB::table('packaging_roll_allocations')->insert($allocation);
    }
}
