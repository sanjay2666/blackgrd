<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouse_item_stocks', function (Blueprint $table) {
            $table->bigInteger('id', true);
            $table->bigInteger('warehouse_item_id')->nullable();
            $table->bigInteger('work_order_id')->nullable();
            $table->bigInteger('allot_work_order_id')->nullable();
            $table->bigInteger('packaging_ord_id')->nullable();
            $table->bigInteger('ppr_id')->nullable();
            $table->bigInteger('insp_id')->nullable();
            $table->bigInteger('gate_pass_id')->nullable();
            $table->bigInteger('work_pro_req_id')->nullable();
            $table->integer('warehouse_id')->nullable();
            $table->integer('ware_comp_id')->nullable();
            $table->decimal('quantity', 10, 2)->nullable();
            $table->decimal('insp_quan_size', 10, 2)->nullable();
            $table->decimal('insp_allot_quan_size', 10, 2)->nullable();
            $table->decimal('insp_bal_quan_size', 10, 2)->nullable();
            $table->decimal('beam_meter', 10, 2)->nullable();
            $table->string('quan_size_unit', 22)->nullable();
            $table->enum('entry_type', ['IN', 'OUT'])->default('IN');
            $table->integer('vendor_id')->nullable();
            $table->integer('receiver_id')->nullable();
            $table->date('receive_date')->nullable();
            $table->integer('master_id')->nullable();
            $table->integer('machine_id')->nullable();
            $table->string('invoice_number', 555)->nullable();
            $table->date('purchase_date')->nullable();
            $table->integer('item_type_id')->nullable();
            $table->integer('unit_type_id')->nullable();
            $table->integer('item_id')->nullable();
            $table->enum('is_allotted_stock', ['Yes', 'No'])->default('No');
            $table->integer('stock_alloted_by')->default(0);
            $table->text('alloted_remark')->nullable();
            $table->string('packet_number', 22)->nullable();
            $table->string('insp_taka_number', 25)->nullable();
            $table->string('dyeing_lot_number', 25)->nullable();
            $table->string('dyeing_taka_number', 25)->nullable();
            $table->integer('fabric_fault_reason_id')->nullable();
            $table->string('item_remark', 555)->nullable();
            $table->string('grey_quality', 555)->nullable();
            $table->string('dyeing_color', 555)->nullable();
            $table->string('coating_type', 555)->nullable();
            $table->tinyText('extra_job')->nullable();
            $table->string('print_job', 555)->nullable();
            $table->integer('inspected_by')->nullable();
            $table->tinyText('insp_comment')->nullable();
            $table->string('insp_epi', 55)->nullable();
            $table->string('insp_ppi', 55)->nullable();
            $table->string('insp_width', 55)->nullable();
            $table->string('insp_gsm', 55)->nullable();
            $table->date('del_date')->nullable();
            $table->integer('dept_return_id')->nullable();
            $table->integer('dept_return_req_id')->nullable();
            $table->integer('mill_dispatch_id')->nullable();
            $table->integer('mill_dispatch_item_id')->nullable();
            $table->integer('receive_mill_dispatch_id')->nullable();
            $table->integer('receive_mill_dispatch_item_id')->nullable();
            $table->integer('return_packaging_ord_id')->nullable();
            $table->bigInteger('stock_mill_return_id')->nullable();
            $table->bigInteger('stock_mill_return_item_id')->nullable();
            $table->integer('chenges_by')->nullable();
            $table->integer('deleted_by')->nullable();
            $table->integer('sample_stock_id')->nullable();
            $table->integer('sample_stock_item_id')->nullable();
            $table->enum('for_stock_type', ['0', '1'])->default('0');
            $table->enum('is_item_returned', ['Yes', 'No'])->default('No');
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
        Schema::dropIfExists('warehouse_item_stocks');
    }
};
