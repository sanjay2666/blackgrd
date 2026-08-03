<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('sale_orders', 'sale_order_type')) {
                $table->string('sale_order_type', 20)->nullable()->after('shipping_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sale_orders', function (Blueprint $table) {
            if (Schema::hasColumn('sale_orders', 'sale_order_type')) {
                $table->dropColumn('sale_order_type');
            }
        });
    }
};
