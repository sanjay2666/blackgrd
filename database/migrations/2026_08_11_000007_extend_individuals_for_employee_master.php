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
            if (! Schema::hasColumn('individuals', 'employee_code')) {
                $table->string('employee_code', 50)->nullable()->after('name');
            }
            if (! Schema::hasColumn('individuals', 'designation')) {
                $table->string('designation', 100)->nullable()->after('employee_code');
            }
            if (! Schema::hasColumn('individuals', 'factory_id')) {
                $table->unsignedBigInteger('factory_id')->nullable()->after('department_id');
            }
            if (! Schema::hasColumn('individuals', 'shift_id')) {
                $table->unsignedBigInteger('shift_id')->nullable()->after('factory_id');
            }
            $table->index(['company_id', 'type', 'employee_code', 'status'], 'individual_employee_lookup_index');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('individuals')) {
            return;
        }

        Schema::table('individuals', function (Blueprint $table): void {
            $table->dropIndex('individual_employee_lookup_index');
            foreach (['shift_id', 'factory_id', 'designation', 'employee_code'] as $column) {
                if (Schema::hasColumn('individuals', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
