<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('machines') && Schema::hasColumn('machines', 'financial_year')) {
            Schema::table('machines', function (Blueprint $table) {
                $table->dropColumn('financial_year');
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('machines') && ! Schema::hasColumn('machines', 'financial_year')) {
            Schema::table('machines', function (Blueprint $table) {
                $table->char('financial_year', 4)->nullable()->after('modified');
            });
        }
    }
};
