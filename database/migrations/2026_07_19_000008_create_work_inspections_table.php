<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_inspections', function (Blueprint $table) {
            $table->integer('id', true);
            $table->bigInteger('work_order_id')->nullable();
            $table->integer('work_process_req_id')->nullable();
            $table->integer('item_id')->nullable();
            $table->integer('insp_quantity')->nullable();
            $table->decimal('insp_quan_size', 10, 2)->default(0.00);
            $table->decimal('shrinkage_quantity', 10, 2)->default(0.00);
            $table->decimal('insp_beam_meter', 10, 2)->nullable();
            $table->string('insp_taka_number', 22)->nullable();
            $table->string('dyeing_lot_number', 22)->nullable();
            $table->string('dyeing_taka_number', 22)->nullable();
            $table->string('insp_comment', 555)->nullable();
            $table->string('insp_work_status', 22)->nullable();
            $table->string('insp_work_status_process', 22)->nullable();
            $table->string('insp_dyeing_process', 22)->nullable();
            $table->integer('fabric_fault_reason_id')->nullable();
            $table->integer('insp_work_warehouse_id')->nullable();
            $table->integer('machine_id')->nullable();
            $table->string('insp_status', 555)->nullable();
            $table->enum('is_warehouse_accepted', ['Yes', 'No'])->default('No');
            $table->integer('warehouse_accepted_by')->nullable();
            $table->date('warehouse_accept_date')->nullable();
            $table->enum('is_item_received_in_warehouse', ['Yes', 'No'])->default('No');
            $table->date('item_received_in_warehouse_date')->nullable();
            $table->string('dyeing_color', 255)->nullable();
            $table->string('coated_type', 255)->nullable();
            $table->string('extra_job', 255)->nullable();
            $table->string('print_job', 255)->nullable();
            $table->integer('inspected_by')->nullable();
            $table->enum('destination', ['Warehouse', 'Department'])->default('Warehouse');
            $table->boolean('is_deleted')->default(0);
            $table->integer('item_interred_in_warehouse_by')->nullable();
            $table->integer('item_received_in_warehouse_by')->nullable();
            $table->char('financial_year', 4)->nullable();
            $table->integer('created_by')->nullable();
            $table->integer('modified_by')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->enum('status', ['Active', 'Inactive', 'Deleted'])->default('Active');
            $table->index(['work_order_id'], 'idx_work_order_id');
            $table->index(['work_process_req_id'], 'idx_work_process_req_id');
            $table->index(['item_id'], 'idx_ins_item_id');
            $table->index(['fabric_fault_reason_id'], 'idx_fabric_fault_reason_id');
            $table->index(['insp_work_warehouse_id'], 'idx_insp_work_warehouse_id');
            $table->index(['machine_id'], 'idx_machine_id');
            $table->index(['warehouse_accepted_by'], 'idx_warehouse_accepted_by');
            $table->index(['inspected_by'], 'idx_inspected_by');
            $table->index(['item_interred_in_warehouse_by'], 'idx_item_interred_by');
            $table->index(['item_received_in_warehouse_by'], 'idx_item_received_by');
            $table->index(['financial_year'], 'idx_financial_year');
            $table->index(['status'], 'idx_status');
            $table->index(['is_deleted'], 'idx_is_deleted');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_inspections');
    }
};
