<?php

namespace Tests\Feature\Warehouse;

use App\Http\Controllers\WarehouseItemController;
use App\Models\Warehouse;
use App\Models\WarehouseBalanceItem;
use App\Models\WarehouseCompartment;
use App\Models\WarehouseItem;
use App\Models\WarehouseItemStock;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class WarehouseLocationChangeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('warehouses')) {
            Schema::create('warehouses', function (Blueprint $table) {
                $table->integer('id')->primary();
                $table->string('warehouse_name');
                $table->string('status');
            });
        }
        if (! Schema::hasTable('warehouse_compartments')) {
            Schema::create('warehouse_compartments', function (Blueprint $table) {
                $table->integer('id')->primary();
                $table->integer('warehouse_id');
                $table->string('compartment_name');
                $table->string('status');
            });
        }
        if (! Schema::hasTable('warehouse_in_items')) {
            Schema::create('warehouse_in_items', function (Blueprint $table) {
                $table->integer('id')->primary();
                $table->integer('warehouse_id');
                $table->integer('ware_comp_id');
            });
        }
        if (! Schema::hasTable('warehouse_item_stocks')) {
            Schema::create('warehouse_item_stocks', function (Blueprint $table) {
                $table->integer('id')->primary();
                $table->integer('warehouse_item_id');
                $table->integer('warehouse_id');
                $table->integer('ware_comp_id');
                $table->decimal('insp_bal_quan_size', 12, 2);
                $table->integer('item_id');
                $table->integer('item_type_id');
                $table->string('dyeing_color')->nullable();
                $table->string('coating_type')->nullable();
                $table->string('print_job')->nullable();
                $table->string('extra_job')->nullable();
                $table->string('status');
            });
        }
        if (! Schema::hasTable('warehouse_balance_items')) {
            Schema::create('warehouse_balance_items', function (Blueprint $table) {
                $table->integer('id')->primary();
                $table->integer('ware_in_item_id');
                $table->integer('warehouse_id');
                $table->integer('ware_comp_id');
                $table->decimal('item_qty', 12, 2);
                $table->integer('item_id');
                $table->integer('item_type_id');
                $table->string('dyeing_color')->nullable();
                $table->string('coating_type')->nullable();
                $table->string('print_job')->nullable();
                $table->string('extra_job')->nullable();
                $table->boolean('balance_status');
                $table->string('status');
            });
        }

        DB::table('warehouse_balance_items')->delete();
        DB::table('warehouse_item_stocks')->delete();
        DB::table('warehouse_in_items')->delete();
        DB::table('warehouse_compartments')->delete();
        DB::table('warehouses')->delete();
    }

    public function test_available_stock_can_move_to_another_warehouse_and_compartment_without_quantity_change(): void
    {
        Warehouse::insert([
            ['id' => 1, 'warehouse_name' => 'Warehouse A', 'status' => 'Active'],
            ['id' => 2, 'warehouse_name' => 'Warehouse B', 'status' => 'Active'],
        ]);
        WarehouseCompartment::insert([
            ['id' => 11, 'warehouse_id' => 1, 'compartment_name' => 'Compartment 1', 'status' => 'Active'],
            ['id' => 98, 'warehouse_id' => 2, 'compartment_name' => 'Compartment 98', 'status' => 'Active'],
        ]);
        WarehouseItem::insert(['id' => 50, 'warehouse_id' => 1, 'ware_comp_id' => 11]);
        WarehouseItemStock::insert([
            'id' => 400,
            'warehouse_item_id' => 50,
            'warehouse_id' => 1,
            'ware_comp_id' => 11,
            'insp_bal_quan_size' => 400,
            'item_id' => 8,
            'item_type_id' => 4,
            'dyeing_color' => 'Blue',
            'coating_type' => null,
            'print_job' => null,
            'extra_job' => null,
            'status' => 'Active',
        ]);
        WarehouseBalanceItem::insert([
            'id' => 75,
            'ware_in_item_id' => 50,
            'warehouse_id' => 1,
            'ware_comp_id' => 11,
            'item_qty' => 400,
            'item_id' => 8,
            'item_type_id' => 4,
            'dyeing_color' => 'Blue',
            'coating_type' => null,
            'print_job' => null,
            'extra_job' => null,
            'balance_status' => 1,
            'status' => 'Active',
        ]);

        $options = app(WarehouseItemController::class)->get_warehouse_compartment_options(Request::create('/', 'GET', ['Id' => 400]));
        $this->assertSame([
            ['id' => 11, 'compartment_name' => 'Warehouse A / Compartment 1'],
            ['id' => 98, 'compartment_name' => 'Warehouse B / Compartment 98'],
        ], $options->getData(true));

        $response = app(WarehouseItemController::class)->updateWarehouseComp(Request::create('/', 'GET', [
            'id' => 400,
            'selectedValue' => 98,
        ]));

        $this->assertSame(['success' => true], $response->getData(true));
        $this->assertDatabaseHas('warehouse_item_stocks', ['id' => 400, 'warehouse_id' => 2, 'ware_comp_id' => 98, 'insp_bal_quan_size' => 400]);
        $this->assertDatabaseCount('warehouse_item_stocks', 1);
        $this->assertDatabaseHas('warehouse_in_items', ['id' => 50, 'warehouse_id' => 2, 'ware_comp_id' => 98]);
        $this->assertDatabaseHas('warehouse_balance_items', ['id' => 75, 'warehouse_id' => 2, 'ware_comp_id' => 98, 'item_qty' => 400]);
        $this->assertDatabaseCount('warehouse_balance_items', 1);
    }

    public function test_refresh_uses_matching_stock_across_all_locations_without_a_location_filter(): void
    {
        WarehouseBalanceItem::insert([
            'id' => 75,
            'ware_in_item_id' => 50,
            'warehouse_id' => 1,
            'ware_comp_id' => 11,
            'item_qty' => 400,
            'item_id' => 8,
            'item_type_id' => 4,
            'dyeing_color' => 'Blue',
            'coating_type' => null,
            'print_job' => null,
            'extra_job' => null,
            'balance_status' => 1,
            'status' => 'Active',
        ]);
        WarehouseItemStock::insert([
            ['id' => 400, 'warehouse_item_id' => 50, 'warehouse_id' => 1, 'ware_comp_id' => 11, 'insp_bal_quan_size' => 250, 'item_id' => 8, 'item_type_id' => 4, 'dyeing_color' => 'Blue', 'coating_type' => null, 'print_job' => null, 'extra_job' => null, 'status' => 'Active'],
            ['id' => 401, 'warehouse_item_id' => 51, 'warehouse_id' => 2, 'ware_comp_id' => 98, 'insp_bal_quan_size' => 150, 'item_id' => 8, 'item_type_id' => 4, 'dyeing_color' => 'Blue', 'coating_type' => null, 'print_job' => null, 'extra_job' => null, 'status' => 'Active'],
        ]);

        $response = app(WarehouseItemController::class)->RefreshWarehouseItem(Request::create('/', 'GET', ['FId' => 75]));

        $this->assertSame(['success' => true, 'new_qty' => 400, 'message' => 'Warehouse balance is already synchronized.'], $response->getData(true));
    }
}
