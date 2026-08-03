<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('warehouse_compartments', function (Blueprint $table) {
            $table->index('warehouse_id', 'idx_wc_warehouse');
            $table->foreign('warehouse_id', 'fk_wc_warehouse')
                ->references('id')
                ->on('warehouses')
                ->onDelete('restrict')
                ->onUpdate('restrict');
        });

        Schema::table('warehouse_item_stocks', function (Blueprint $table) {
            $table->index('warehouse_item_id', 'idx_wis_inward');
            $table->foreign('warehouse_item_id', 'fk_wis_inward')
                ->references('id')
                ->on('warehouse_in_items')
                ->onDelete('restrict')
                ->onUpdate('restrict');
        });
    }

    public function down(): void
    {
        Schema::table('warehouse_item_stocks', function (Blueprint $table) {
            $table->dropForeign('fk_wis_inward');
            $table->dropIndex('idx_wis_inward');
        });

        Schema::table('warehouse_compartments', function (Blueprint $table) {
            $table->dropForeign('fk_wc_warehouse');
            $table->dropIndex('idx_wc_warehouse');
        });
    }
};
