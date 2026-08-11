<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCanonicalTaxReferencesToItems extends Migration
{
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table): void {
            if (! Schema::hasColumn('items', 'hsn_code_id')) {
                $table->unsignedInteger('hsn_code_id')->nullable()->after('hsncode')->index();
            }
            if (! Schema::hasColumn('items', 'gst_rate_id')) {
                $table->unsignedInteger('gst_rate_id')->nullable()->after('hsn_code_id')->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table): void {
            foreach (['gst_rate_id', 'hsn_code_id'] as $column) {
                if (Schema::hasColumn('items', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
}

return new AddCanonicalTaxReferencesToItems();
