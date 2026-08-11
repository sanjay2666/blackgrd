<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('individuals')) {
            return;
        }
        Schema::table('individuals', function (Blueprint $table): void {
            if (! Schema::hasColumn('individuals', 'vendor_code')) {
                $table->string('vendor_code', 50)->nullable()->after('name');
            }
            $table->index(['company_id', 'type', 'vendor_code', 'status'], 'individual_vendor_lookup_index');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('individuals')) {
            return;
        }
        Schema::table('individuals', function (Blueprint $table): void {
            $table->dropIndex('individual_vendor_lookup_index');
            if (Schema::hasColumn('individuals', 'vendor_code')) {
                $table->dropColumn('vendor_code');
            }
        });
    }
};
