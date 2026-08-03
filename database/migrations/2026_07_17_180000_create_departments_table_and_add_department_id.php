<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('departments')) {
            Schema::create('departments', function (Blueprint $table) {
                $table->id();
                $table->string('department_name');
                $table->char('financial_year', 4)->nullable();
                $table->integer('created_by')->nullable();
                $table->integer('modified_by')->nullable();
                $table->dateTime('created_at')->nullable();
                $table->dateTime('updated_at')->nullable();
                $table->enum('status', ['Active', 'Inactive', 'Deleted'])->default('Active');
            });
        }

        if (Schema::hasTable('individuals') && ! Schema::hasColumn('individuals', 'department_id')) {
            Schema::table('individuals', function (Blueprint $table) {
                $table->integer('department_id')->nullable()->after('process_type_id');
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('individuals') && Schema::hasColumn('individuals', 'department_id')) {
            Schema::table('individuals', function (Blueprint $table) {
                $table->dropColumn('department_id');
            });
        }

        Schema::dropIfExists('departments');
    }
};
