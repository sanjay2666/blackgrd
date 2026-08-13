<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('packaging_orders', function (Blueprint $table) {
            $table->string('packaging_mode', 20)->default('bulk')->after('customer_id')->index();
            $table->unsignedInteger('parcel_count')->nullable()->after('remaining_quantity');
            $table->unsignedInteger('roll_count')->default(0)->after('parcel_count');
            $table->unsignedInteger('lot_count')->default(0)->after('roll_count');
        });

        Schema::table('packaging_order_items', function (Blueprint $table) {
            $table->string('item_name', 255)->nullable()->after('unit_type_id');
            $table->string('unit', 25)->nullable()->after('item_name');
            $table->string('grey_quality', 555)->nullable()->after('unit');
            $table->string('dyeing_color', 555)->nullable()->after('grey_quality');
            $table->string('coating_type', 555)->nullable()->after('dyeing_color');
            $table->string('print_job', 555)->nullable()->after('coating_type');
            $table->text('extra_job')->nullable()->after('print_job');
            $table->string('final_dispatch_width', 255)->nullable()->after('extra_job');
            $table->string('tube_width', 55)->nullable()->after('final_dispatch_width');
            $table->unsignedInteger('roll_count')->default(0)->after('remaining_quantity');
            $table->unsignedInteger('lot_count')->default(0)->after('roll_count');
        });

        Schema::table('packaging_roll_allocations', function (Blueprint $table) {
            $table->unsignedInteger('warehouse_id')->nullable()->after('warehouse_out_item_id');
            $table->unsignedInteger('ware_comp_id')->nullable()->after('warehouse_id');
            $table->decimal('source_available_quantity', 12, 2)->default(0)->after('dyeing_lot_number');
        });
    }

    public function down(): void
    {
        Schema::table('packaging_roll_allocations', function (Blueprint $table) {
            $table->dropColumn(['warehouse_id', 'ware_comp_id', 'source_available_quantity']);
        });

        Schema::table('packaging_order_items', function (Blueprint $table) {
            $table->dropColumn([
                'item_name', 'unit', 'grey_quality', 'dyeing_color', 'coating_type', 'print_job', 'extra_job',
                'final_dispatch_width', 'tube_width', 'roll_count', 'lot_count',
            ]);
        });

        Schema::table('packaging_orders', function (Blueprint $table) {
            $table->dropColumn(['packaging_mode', 'parcel_count', 'roll_count', 'lot_count']);
        });
    }
};
