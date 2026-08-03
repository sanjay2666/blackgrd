<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_orders', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('parent_work_order_id')->unsigned()->nullable();
            $table->integer('inspection_id')->nullable();
            $table->string('process_type', 4);
            $table->integer('process_sl_no')->nullable();
            $table->integer('user_id');
            $table->integer('process_type_id');
            $table->integer('item_type_id');
            $table->integer('item_id');
            $table->string('item_name', 555);
            $table->integer('pcs')->nullable();
            $table->integer('cut')->nullable();
            $table->integer('meter')->nullable();
            $table->integer('process_started_by');
            $table->integer('process_ended_by');
            $table->integer('process_inspected_by');
            $table->date('process_started_date')->nullable();
            $table->date('process_ended_date')->nullable();
            $table->date('process_inspected_date')->nullable();
            $table->string('process_started_remarks', 555);
            $table->string('process_ended_remarks', 555);
            $table->integer('master_ind_id')->nullable();
            $table->integer('machine_id')->nullable();
            $table->integer('output_quantity')->nullable();
            $table->integer('output_process')->nullable();
            $table->integer('end_process_emp_id')->nullable();
            $table->enum('insp_status', ['Complete', 'Pending'])->default('Pending');
            $table->enum('is_warehouse_accepted', ['Yes', 'No'])->default('No');
            $table->integer('warehouse_accepted_by')->nullable();
            $table->date('warehouse_accept_date')->nullable();
            $table->enum('is_item_received_in_warehouse', ['Yes', 'No'])->default('No');
            $table->integer('item_received_in_warehouse_by')->default(0);
            $table->date('item_received_in_warehouse_date')->nullable();
            $table->integer('item_interred_in_warehouse_by')->nullable();
            $table->integer('work_req_send_by')->nullable();
            $table->date('work_req_send_date')->nullable();
            $table->enum('is_work_require_request_accepted', ['Yes', 'No'])->nullable();
            $table->enum('is_gatepass_genrated_by_warehouse', ['Yes', 'No'])->default('Yes');
            $table->integer('gatepass_genrated_by_warehouse_user')->nullable();
            $table->enum('is_item_received_from_warehouse', ['Yes', 'No'])->default('No');
            $table->integer('item_received_in_department_by')->nullable();
            $table->enum('work_status', ['Complete', 'Pending'])->default('Pending');
            $table->enum('print_position', ['before', 'after', 'none'])->default('none');
            $table->integer('re_opend_by')->default(0);
            $table->integer('work_shifted_by')->nullable();
            $table->integer('deleted_by')->nullable();
            $table->dateTime('deleted_date')->nullable();
            $table->char('financial_year', 4)->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('modified_at')->nullable();
            $table->integer('created_by')->default(0);
            $table->integer('modified_by')->default(0);
            $table->enum('status', ['Active', 'Inactive', 'Deleted'])->default('Active');
            $table->index(['parent_work_order_id'], 'work_orders_parent_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_orders');
    }
};
