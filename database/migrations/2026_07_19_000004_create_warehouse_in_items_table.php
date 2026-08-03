<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouse_in_items', function (Blueprint $table) {
            $table->bigInteger('id', true);
            $table->bigInteger('insp_id')->nullable();
            $table->integer('process_type_id')->nullable();
            $table->integer('purchase_id')->nullable();
            $table->integer('warehouse_id')->nullable();
            $table->integer('ware_comp_id')->nullable();
            $table->integer('receiver_id')->nullable();
            $table->integer('ind_emp_id')->nullable();
            $table->string('emp_name', 255)->nullable();
            $table->date('receive_date')->nullable();
			$table->integer('vendor_id')->nullable();
			$table->string('vendor_name', 255)->nullable();
            $table->string('invoice_number', 100)->nullable();
            $table->string('challan_number', 100)->nullable();
            $table->integer('pur_item_id')->nullable();
            $table->integer('item_id')->nullable();
            $table->integer('item_type_id')->nullable();
            $table->integer('unit_type_id')->nullable();
            $table->string('pur_item_name', 255)->nullable();
            $table->decimal('item_qty', 10, 2)->nullable();
            $table->decimal('allotted_qty', 10, 2)->default(0.00);
            $table->decimal('beam_meter', 10, 2)->nullable();
            $table->string('unit', 11)->nullable();
            $table->double('pcs')->nullable();
            $table->double('cut')->nullable();
            $table->double('meter')->nullable();
            $table->string('insp_taka_number', 22)->nullable();
            $table->string('dyeing_lot_number', 11)->nullable();
            $table->string('dyeing_taka_number', 22)->nullable();
            $table->integer('fabric_fault_reason_id')->nullable();
            
            $table->date('purchase_date')->nullable();
            $table->integer('work_order_id')->nullable();
            $table->integer('packaging_ord_id')->nullable();
            $table->integer('master_id')->nullable();
            $table->integer('machine_id')->nullable();
            $table->string('item_remark', 555)->nullable();
            $table->string('grey_quality', 555)->nullable();
            $table->string('dyeing_color', 555)->nullable();
            $table->string('coating_type', 555)->nullable();
            $table->string('print_job', 555)->nullable();
            $table->text('extra_job')->nullable();
            $table->string('report_document', 255)->nullable();
            $table->bigInteger('gate_pass_number')->nullable();
            $table->date('del_date')->nullable();
            $table->integer('dept_return_id')->nullable();
            $table->integer('dept_return_req_id')->nullable();
            $table->tinyInteger('is_updated')->default(0);
            $table->dateTime('reversed_at')->nullable();
            $table->bigInteger('reversed_by')->unsigned()->nullable();
            $table->string('reversal_reason', 1000)->nullable();
            $table->char('financial_year', 4)->nullable();
            $table->integer('created_by')->nullable();
            $table->integer('modified_by')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->enum('status', ['Active', 'Inactive', 'Deleted'])->default('Active');
            $table->index(['challan_number'], 'warehouse_in_items_challan_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouse_in_items');
    }
};
