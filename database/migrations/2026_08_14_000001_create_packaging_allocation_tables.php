<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('packaging_orders', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('company_id')->index();
            $table->unsignedInteger('customer_id')->nullable()->index();
            $table->string('packaging_status', 30)->default('draft')->index();
            $table->decimal('allocated_quantity', 12, 2)->default(0);
            $table->decimal('packed_quantity', 12, 2)->default(0);
            $table->decimal('dispatched_quantity', 12, 2)->default(0);
            $table->decimal('cancelled_quantity', 12, 2)->default(0);
            $table->decimal('returned_quantity', 12, 2)->default(0);
            $table->decimal('remaining_quantity', 12, 2)->default(0);
            $table->text('remarks')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('accepted_by')->nullable();
            $table->dateTime('accepted_at')->nullable();
            $table->unsignedInteger('cancelled_by')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->string('cancellation_reason', 1000)->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->enum('status', ['Active', 'Inactive', 'Deleted'])->default('Active');
            $table->index(['company_id', 'packaging_status', 'status'], 'packaging_orders_company_status_index');
        });

        Schema::create('packaging_order_items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('company_id')->index();
            $table->unsignedBigInteger('packaging_order_id')->index();
            $table->unsignedInteger('sale_order_id')->nullable()->index();
            $table->unsignedInteger('sale_order_item_id')->index();
            $table->unsignedInteger('item_id')->nullable();
            $table->unsignedInteger('item_type_id')->nullable();
            $table->unsignedInteger('unit_type_id')->nullable();
            $table->unsignedInteger('packaging_type_id')->nullable();
            $table->decimal('allocated_quantity', 12, 2)->default(0);
            $table->decimal('packed_quantity', 12, 2)->default(0);
            $table->decimal('dispatched_quantity', 12, 2)->default(0);
            $table->decimal('cancelled_quantity', 12, 2)->default(0);
            $table->decimal('returned_quantity', 12, 2)->default(0);
            $table->decimal('remaining_quantity', 12, 2)->default(0);
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->enum('status', ['Active', 'Inactive', 'Deleted'])->default('Active');
            $table->unique(['packaging_order_id', 'sale_order_item_id'], 'packaging_order_item_source_unique');
            $table->index(['company_id', 'sale_order_item_id', 'status'], 'packaging_items_company_source_index');
        });

        Schema::create('packaging_roll_allocations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('company_id')->index();
            $table->unsignedBigInteger('packaging_order_id')->index();
            $table->unsignedBigInteger('packaging_order_item_id')->index();
            $table->unsignedBigInteger('warehouse_item_stock_id')->index();
            $table->unsignedInteger('warehouse_out_item_id')->nullable()->index();
            $table->string('packet_number', 50)->nullable();
            $table->string('insp_taka_number', 50)->nullable();
            $table->string('dyeing_lot_number', 50)->nullable();
            $table->decimal('allocated_quantity', 12, 2)->default(0);
            $table->decimal('accepted_quantity', 12, 2)->default(0);
            $table->decimal('packed_quantity', 12, 2)->default(0);
            $table->decimal('dispatched_quantity', 12, 2)->default(0);
            $table->decimal('cancelled_quantity', 12, 2)->default(0);
            $table->decimal('returned_quantity', 12, 2)->default(0);
            $table->decimal('remaining_quantity', 12, 2)->default(0);
            $table->string('allocation_status', 30)->default('proposed')->index();
            $table->unsignedInteger('accepted_by')->nullable();
            $table->dateTime('accepted_at')->nullable();
            $table->unsignedInteger('reversed_by')->nullable();
            $table->dateTime('reversed_at')->nullable();
            $table->string('reversal_reason', 1000)->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->enum('status', ['Active', 'Inactive', 'Deleted'])->default('Active');
            $table->unique(['packaging_order_item_id', 'warehouse_item_stock_id'], 'packaging_roll_source_unique');
            $table->index(['company_id', 'warehouse_item_stock_id', 'allocation_status'], 'packaging_roll_stock_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('packaging_roll_allocations');
        Schema::dropIfExists('packaging_order_items');
        Schema::dropIfExists('packaging_orders');
    }
};
