<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('unit_type')) {
            return;
        }

        Schema::table('unit_type', function (Blueprint $table): void {
            if (! Schema::hasColumn('unit_type', 'unit_code')) {
                $table->string('unit_code', 20)->nullable()->after('unit_type_name');
            }
            if (! Schema::hasColumn('unit_type', 'description')) {
                $table->text('description')->nullable()->after('unit_code');
            }
            if (! Schema::hasColumn('unit_type', 'decimal_places')) {
                $table->unsignedTinyInteger('decimal_places')->nullable()->after('description');
            }
            if (! Schema::hasColumn('unit_type', 'display_order')) {
                $table->unsignedInteger('display_order')->default(0)->after('decimal_places');
            }
            $table->index(['status', 'display_order'], 'unit_type_status_order_index');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('unit_type')) {
            return;
        }

        Schema::table('unit_type', function (Blueprint $table): void {
            $table->dropIndex('unit_type_status_order_index');
            foreach (['display_order', 'decimal_places', 'description', 'unit_code'] as $column) {
                if (Schema::hasColumn('unit_type', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
