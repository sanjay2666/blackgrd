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
            if (! Schema::hasColumn('individuals', 'customer_code')) {
                $table->string('customer_code', 50)->nullable()->after('name');
            }
            $table->index(['company_id', 'type', 'customer_code', 'status'], 'individual_customer_lookup_index');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('individuals')) {
            return;
        }

        Schema::table('individuals', function (Blueprint $table): void {
            $table->dropIndex('individual_customer_lookup_index');
            if (Schema::hasColumn('individuals', 'customer_code')) {
                $table->dropColumn('customer_code');
            }
        });
    }
};
