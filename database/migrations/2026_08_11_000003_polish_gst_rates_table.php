<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('gst_rates', function (Blueprint $table): void {
            $table->decimal('gst_rate', 6, 2)->change();
            $table->string('description', 1000)->nullable()->after('gst_rate');
        });
    }

    public function down(): void
    {
        Schema::table('gst_rates', function (Blueprint $table): void {
            $table->dropColumn('description');
            $table->decimal('gst_rate', 10, 1)->change();
        });
    }
};
