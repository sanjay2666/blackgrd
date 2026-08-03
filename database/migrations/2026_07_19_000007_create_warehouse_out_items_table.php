<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouse_out_items', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('wis_id')->nullable();
            $table->integer('warehouse_item_id')->nullable();
            $table->integer('process_type_id')->nullable();
            $table->integer('warehouse_id')->nullable();
            $table->integer('ware_comp_id')->nullable();
            $table->integer('item_id')->nullable();
            $table->integer('item_type_id')->nullable();
            $table->integer('unit_type_id')->nullable();
            $table->decimal('item_qty', 10, 2)->nullable();
            $table->decimal('qty_consumed', 10, 2)->nullable();
            $table->decimal('qty_returned', 10, 2)->nullable();
            $table->double('pcs')->default(0);
            $table->double('cut')->nullable();
            $table->double('meter')->default(0);
            $table->string('insp_taka_number', 40)->nullable();
            $table->string('dyeing_lot_number', 25)->nullable();
            $table->string('dyeing_taka_number', 25)->nullable();
            $table->integer('fabric_fault_reason_id')->nullable();
            $table->integer('individual_id')->nullable();
            $table->integer('receiver_id')->nullable();
            $table->integer('work_pro_req_id')->nullable();
            $table->integer('work_order_id')->nullable();
            $table->integer('packaging_ord_id')->nullable();
            $table->integer('ppr_id')->nullable();
            $table->integer('mill_dispatch_id')->nullable();
            $table->integer('mill_dispatch_item_id')->nullable();
            $table->integer('sample_stock_id')->nullable();
            $table->integer('sample_stock_item_id')->nullable();
            $table->string('item_remark', 555)->nullable();
            $table->string('grey_quality', 555)->nullable();
            $table->string('dyeing_color', 555)->nullable();
            $table->string('coating_type', 555)->nullable();
            $table->string('print_job', 555)->nullable();
            $table->text('extra_job')->nullable();
            $table->enum('is_item_return_whouse', ['0', '1'])->default('0');
            $table->char('financial_year', 4)->nullable();
            $table->integer('created_by')->nullable();
            $table->integer('modified_by')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->enum('status', ['Active', 'Inactive', 'Deleted'])->default('Active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouse_out_items');
    }
};
