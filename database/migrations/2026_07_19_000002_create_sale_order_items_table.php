<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_order_items', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('sale_order_id')->nullable();
            $table->integer('item_id')->nullable();
            $table->integer('item_type_id')->nullable();
            $table->integer('unit_type_id')->nullable();
            $table->string('item_name', 128)->nullable();
            $table->string('discount_type', 1)->nullable();
            $table->string('unit', 25)->nullable();
            $table->string('order_item_priority', 55)->nullable();
            $table->decimal('pcs', 10, 2)->nullable();
            $table->decimal('cut', 10, 2)->nullable();
            $table->decimal('meter', 10, 2)->nullable();
            $table->decimal('rate', 10, 2)->nullable();
            $table->decimal('amount', 10, 2)->nullable();
            $table->decimal('discount', 10, 2)->nullable();
            $table->decimal('discount_amount', 10, 2)->nullable();
            $table->decimal('net_amount', 10, 2)->nullable();
            $table->decimal('total_price', 10, 2)->nullable();
            $table->string('grey_quality', 555)->nullable();
            $table->string('dyeing_color', 555)->nullable();
            $table->string('coating_type', 555)->nullable();
            $table->text('extra_job')->nullable();
            $table->string('print_job', 555)->nullable();
            $table->string('packing_roll_length', 255)->nullable();
            $table->string('final_dispatch_width', 255)->nullable();
            $table->string('tube_width', 55)->nullable();
            $table->enum('development_type', ['Bulk', 'Sample', 'JobWork'])->default('Bulk');
            $table->date('expect_delivery_date')->nullable();
            $table->decimal('delivered_item_mtr', 10, 2)->nullable();
            $table->decimal('pending_item_mtr', 10, 2)->nullable();
            $table->decimal('extra_deliver_item_mtr', 10, 2)->default(0.00);
            $table->text('remarks')->nullable();
            $table->text('cancel_reason')->nullable();
            $table->boolean('is_deleted')->default(0);
            $table->boolean('is_return')->default(0);
            $table->boolean('is_work_order_created')->default(0);
            $table->boolean('is_work_completed')->nullable();
            $table->enum('is_work_final_completed', ['0', '1'])->default('0');
            $table->enum('is_work_final_dlvr_completed', ['0', '1'])->default('0');
            $table->enum('is_packaging_done', ['0', '1'])->default('0');
            $table->text('dlvr_cleared_reason')->nullable();
            $table->dateTime('dlvr_clear_date')->nullable();
            $table->integer('dlvr_cleared_by')->nullable();
            $table->integer('created_by')->nullable();
            $table->integer('modified_by')->nullable();
            $table->integer('edited_by')->nullable();
            $table->integer('reactive_wo_by')->default(0);
            $table->integer('reopen_packaging_by')->default(0);
            $table->integer('change_coating_by')->default(0);
            $table->integer('in_packaging_send_by')->nullable();
            $table->dateTime('in_packaging_send_date')->nullable();
            $table->integer('edit_count')->default(0);
            $table->char('financial_year', 4)->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('modified_at')->nullable();
            $table->enum('status', ['Active', 'Inactive', 'Deleted'])->default('Active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_order_items');
    }
};
