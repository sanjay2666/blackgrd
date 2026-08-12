<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('sale_order_items') || Schema::hasColumn('sale_order_items', 'company_id')) {
            return;
        }

        $invalidRows = DB::table('sale_order_items as item')
            ->leftJoin('sale_orders as order', 'order.id', '=', 'item.sale_order_id')
            ->where(fn ($query) => $query->whereNull('order.id')->orWhereNull('order.company_id'))
            ->count();
        if ($invalidRows !== 0) {
            throw new RuntimeException("Cannot company-scope sale order items: {$invalidRows} rows have no company-owned sale order.");
        }

        Schema::table('sale_order_items', function (Blueprint $table): void {
            $table->unsignedInteger('company_id')->nullable()->after('sale_order_id');
        });

        DB::table('sale_order_items as item')
            ->join('sale_orders as order', 'order.id', '=', 'item.sale_order_id')
            ->whereNull('item.company_id')
            ->update(['item.company_id' => DB::raw('`order`.`company_id`')]);

        if (DB::table('sale_order_items')->whereNull('company_id')->exists()) {
            throw new RuntimeException('Sale order item company ownership backfill is incomplete.');
        }

        Schema::table('sale_order_items', function (Blueprint $table): void {
            $table->unsignedInteger('company_id')->nullable(false)->change();
            $table->index(['company_id', 'status', 'id'], 'sale_order_items_company_status_idx');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('sale_order_items') || ! Schema::hasColumn('sale_order_items', 'company_id')) {
            return;
        }

        Schema::table('sale_order_items', function (Blueprint $table): void {
            $table->dropIndex('sale_order_items_company_status_idx');
            $table->dropColumn('company_id');
        });
    }
};
