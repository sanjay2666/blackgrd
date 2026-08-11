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
            if (! Schema::hasColumn('individuals', 'transporter_code')) {
                $table->string('transporter_code', 50)->nullable()->after('name');
            }
            $table->index(['company_id', 'type', 'transporter_code', 'status'], 'individual_transporter_lookup_index');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('individuals')) {
            return;
        }

        Schema::table('individuals', function (Blueprint $table): void {
            $table->dropIndex('individual_transporter_lookup_index');
            if (Schema::hasColumn('individuals', 'transporter_code')) {
                $table->dropColumn('transporter_code');
            }
        });
    }
};
