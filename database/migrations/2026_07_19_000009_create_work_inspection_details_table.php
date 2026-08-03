<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_inspection_details', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('work_insp_id')->nullable();
            $table->integer('item_id')->nullable();
            $table->bigInteger('work_order_id')->nullable();
            $table->decimal('greige_item_qty', 10, 2)->nullable();
            $table->decimal('output_quantity', 10, 2)->nullable();
            $table->string('dyeing_lot_number', 22)->nullable();
            $table->string('dyeing_taka_number', 22)->nullable();
            $table->string('insp_taka_number', 22)->nullable();
            $table->decimal('shrinkage_quantity', 10, 2)->nullable();
            $table->decimal('insp_beam_meter', 10, 2)->nullable();
            $table->text('inspection_comment')->nullable();
            $table->string('insp_work_status_process', 22)->nullable();
            $table->string('insp_coating_process', 11)->nullable();
            $table->string('work_status', 11)->nullable();
            $table->integer('fabric_fault_reason_id')->nullable();
            $table->integer('insp_work_warehouse_id')->nullable();
            $table->integer('machine_id')->nullable();
            $table->string('insp_epi', 55)->nullable();
            $table->string('insp_ppi', 55)->nullable();
            $table->string('insp_width', 55)->nullable();
            $table->string('insp_gsm', 55)->nullable();
            $table->char('financial_year', 4)->nullable();
            $table->integer('created_by')->nullable();
            $table->integer('modified_by')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->enum('status', ['Active', 'Inactive', 'Deleted'])->default('Active');
            $table->index(['work_insp_id'], 'idx_work_insp_id');
            $table->index(['item_id'], 'idx_ins_item_id');
            $table->index(['work_order_id'], 'idx_ins_work_order_id');
            $table->index(['fabric_fault_reason_id'], 'idx_fabric_fault_reason_id');
            $table->index(['insp_work_warehouse_id'], 'idx_insp_work_warehouse_id');
            $table->index(['machine_id'], 'idx_machine_id');
            $table->index(['financial_year'], 'idx_financial_year');
            $table->index(['status'], 'idx_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_inspection_details');
    }
};
