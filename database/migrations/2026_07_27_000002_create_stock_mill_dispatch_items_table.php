<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_mill_dispatch_items', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('stock_mill_dispatch_id')->nullable();
            $table->integer('wis_id')->nullable();
            $table->integer('warehouse_item_id')->nullable();
            $table->integer('item_id')->nullable();
            $table->integer('item_type_id')->nullable();
            $table->string('dyeing_color', 255)->nullable();
            $table->string('coated_pvc', 255)->nullable();
            $table->string('extra_job', 255)->nullable();
            $table->string('print_job', 255)->nullable();
            $table->decimal('insp_quan_size', 10, 2)->nullable();
            $table->decimal('received_quantity', 10, 2)->default(0.00);
            $table->decimal('balance_quantity', 10, 2)->default(0.00);
            $table->string('insp_taka_number', 35)->nullable();
            $table->string('dyeing_lot_number', 25)->nullable();
            $table->string('dyeing_taka_number', 25)->nullable();
            $table->integer('work_order_id')->nullable();
            $table->boolean('is_item_received_in_warehouse')->default(false);
            $table->char('financial_year', 4)->nullable();
            $table->integer('created_by')->nullable();
            $table->integer('modified_by')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('modified_at')->nullable();
            $table->enum('status', ['Active', 'Inactive', 'Deleted'])->default('Active');
            $table->index(['stock_mill_dispatch_id', 'wis_id', 'warehouse_item_id', 'item_id', 'item_type_id'], 'stock_mill_dispatch_item_lookup_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_mill_dispatch_items');
    }
};
