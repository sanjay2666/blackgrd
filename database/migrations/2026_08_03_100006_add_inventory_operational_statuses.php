<?php

use App\Enums\InventoryAllocationStatus;
use App\Enums\InventoryMovementStatus;
use App\Enums\InventoryReceiptStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('warehouse_in_items', function (Blueprint $table) {
            $table->string('movement_status', 40)->nullable()->default('posted')->index();
            $table->string('receipt_status', 40)->nullable()->default('received')->index();
        });
        Schema::table('warehouse_out_items', function (Blueprint $table) {
            $table->string('movement_status', 40)->nullable()->default('posted')->index();
        });
        Schema::table('warehouse_balance_items', function (Blueprint $table) {
            $table->string('movement_status', 40)->nullable()->default('posted')->index();
        });
        Schema::table('warehouse_item_stocks', function (Blueprint $table) {
            $table->string('allocation_status', 40)->nullable()->default('unallocated')->index();
        });

        DB::table('warehouse_in_items')->where('status', '!=', 'Deleted')->update([
            'movement_status' => InventoryMovementStatus::Posted->value,
            'receipt_status' => InventoryReceiptStatus::Received->value,
        ]);
        DB::table('warehouse_in_items')->where('status', 'Deleted')->update([
            'movement_status' => null,
            'receipt_status' => null,
        ]);
        DB::table('warehouse_out_items')->where('status', '!=', 'Deleted')->update([
            'movement_status' => InventoryMovementStatus::Posted->value,
        ]);
        DB::table('warehouse_out_items')->where('status', 'Deleted')->update(['movement_status' => null]);
        DB::table('warehouse_balance_items')->where('status', '!=', 'Deleted')->update([
            'movement_status' => InventoryMovementStatus::Posted->value,
        ]);
        DB::table('warehouse_balance_items')->where('status', 'Deleted')->update(['movement_status' => null]);
        DB::table('warehouse_item_stocks')->where('status', '!=', 'Deleted')->orderBy('id')->each(
            function (object $stock): void {
                DB::table('warehouse_item_stocks')->where('id', $stock->id)->update([
                    'allocation_status' => $stock->is_allotted_stock === 'Yes'
                        ? InventoryAllocationStatus::Allocated->value
                        : InventoryAllocationStatus::Unallocated->value,
                ]);
            }
        );
        DB::table('warehouse_item_stocks')->where('status', 'Deleted')->update(['allocation_status' => null]);
    }

    public function down(): void
    {
        Schema::table('warehouse_item_stocks', function (Blueprint $table) {
            $table->dropIndex('warehouse_item_stocks_allocation_status_index');
            $table->dropColumn('allocation_status');
        });
        Schema::table('warehouse_balance_items', function (Blueprint $table) {
            $table->dropIndex('warehouse_balance_items_movement_status_index');
            $table->dropColumn('movement_status');
        });
        Schema::table('warehouse_out_items', function (Blueprint $table) {
            $table->dropIndex('warehouse_out_items_movement_status_index');
            $table->dropColumn('movement_status');
        });
        Schema::table('warehouse_in_items', function (Blueprint $table) {
            $table->dropIndex('warehouse_in_items_movement_status_index');
            $table->dropIndex('warehouse_in_items_receipt_status_index');
            $table->dropColumn(['movement_status', 'receipt_status']);
        });
    }
};
