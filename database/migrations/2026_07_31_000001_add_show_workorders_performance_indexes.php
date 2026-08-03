<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            $table->index(['status', 'financial_year', 'insp_status', 'id'], 'idx_wo_status_fy_insp_id');
            $table->index(['process_type_id', 'status'], 'idx_wo_process_status');
        });

        Schema::table('work_order_items', function (Blueprint $table) {
            $table->index(['work_order_id', 'status'], 'idx_woi_wo_status');
            $table->index(['customer_id', 'status'], 'idx_woi_customer_status');
            $table->index(['sale_order_id', 'status'], 'idx_woi_so_status');
            $table->index(['sale_order_item_id', 'status'], 'idx_woi_soi_status');
        });

        Schema::table('work_process_requirements', function (Blueprint $table) {
            $table->index(['work_order_id', 'status', 'is_accept', 'item_type_id'], 'idx_wpr_wo_status_accept_type');
            $table->index(['req_lot_no', 'status', 'is_accept'], 'idx_wpr_lot_status_accept');
        });

        Schema::table('warehouse_item_stocks', function (Blueprint $table) {
            $table->index(['work_order_id', 'status', 'dyeing_lot_number'], 'idx_wis_wo_status_lot');
            $table->index(['insp_id', 'status', 'is_allotted_stock'], 'idx_wis_insp_status_allotted');
        });

        Schema::table('warehouse_out_items', function (Blueprint $table) {
            $table->index(['work_order_id', 'status'], 'idx_wo_out_wo_status');
            $table->index(['work_pro_req_id', 'status'], 'idx_wo_out_req_status');
        });

        Schema::table('gate_passes', function (Blueprint $table) {
            $table->index(['work_order_id', 'status'], 'idx_gp_wo_status');
            $table->index(['inspection_id', 'status'], 'idx_gp_insp_status');
            $table->index(['dyeing_lot_number', 'status'], 'idx_gp_lot_status');
        });

        Schema::table('department_return_requests', function (Blueprint $table) {
            $table->index(['work_order_id', 'status'], 'idx_drr_wo_status');
        });
    }

    public function down(): void
    {
        Schema::table('department_return_requests', function (Blueprint $table) {
            $table->dropIndex('idx_drr_wo_status');
        });

        Schema::table('gate_passes', function (Blueprint $table) {
            $table->dropIndex('idx_gp_wo_status');
            $table->dropIndex('idx_gp_insp_status');
            $table->dropIndex('idx_gp_lot_status');
        });

        Schema::table('warehouse_out_items', function (Blueprint $table) {
            $table->dropIndex('idx_wo_out_wo_status');
            $table->dropIndex('idx_wo_out_req_status');
        });

        Schema::table('warehouse_item_stocks', function (Blueprint $table) {
            $table->dropIndex('idx_wis_wo_status_lot');
            $table->dropIndex('idx_wis_insp_status_allotted');
        });

        Schema::table('work_process_requirements', function (Blueprint $table) {
            $table->dropIndex('idx_wpr_wo_status_accept_type');
            $table->dropIndex('idx_wpr_lot_status_accept');
        });

        Schema::table('work_order_items', function (Blueprint $table) {
            $table->dropIndex('idx_woi_wo_status');
            $table->dropIndex('idx_woi_customer_status');
            $table->dropIndex('idx_woi_so_status');
            $table->dropIndex('idx_woi_soi_status');
        });

        Schema::table('work_orders', function (Blueprint $table) {
            $table->dropIndex('idx_wo_status_fy_insp_id');
            $table->dropIndex('idx_wo_process_status');
        });
    }
};
