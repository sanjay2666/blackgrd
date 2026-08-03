<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_items', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('purchase_id')->nullable();
            $table->unsignedInteger('purchase_order_id')->nullable();
            $table->unsignedInteger('purchase_order_item_id')->nullable();
            $table->unsignedInteger('warehouse_item_id')->nullable();
            $table->unsignedInteger('warehouse_item_stock_id')->nullable();
            $table->unsignedInteger('item_id')->nullable();
            $table->integer('item_type_id')->nullable();
            $table->integer('unit_type_id')->nullable();
            $table->string('item_name', 255)->nullable();
            $table->string('dyeing_color', 555)->nullable();
            $table->string('hsn', 55)->nullable();
            $table->decimal('qty', 12, 2)->default(0.00);
            $table->decimal('meter', 12, 2)->default(0.00);
            $table->string('taka_number', 25)->nullable();
            $table->text('remarks')->nullable();
            $table->integer('warehouse_id')->nullable();
            $table->integer('ware_comp_id')->nullable();
            $table->date('receiving_date')->nullable();
            $table->char('financial_year', 4)->nullable();
            $table->integer('created_by')->nullable();
            $table->integer('modified_by')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->enum('status', ['Active', 'Inactive', 'Deleted'])->default('Active');

            $table->index(['purchase_id'], 'purchase_items_purchase_id_index');
            $table->index(['purchase_order_id'], 'purchase_items_purchase_order_id_index');
            $table->index(['purchase_order_item_id'], 'purchase_items_purchase_order_item_id_index');
            $table->index(['warehouse_item_stock_id'], 'purchase_items_warehouse_item_stock_id_index');
            $table->index(['item_id'], 'purchase_items_item_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_items');
    }
};
