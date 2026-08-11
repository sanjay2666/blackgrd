<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        if (! Schema::hasColumn('process_items', 'company_id')) {
            Schema::table('process_items', function (Blueprint $table): void {
                $table->unsignedInteger('company_id')->nullable()->after('id');
            });
        }

        foreach ([
            'short_code' => fn (Blueprint $table) => $table->string('short_code', 30)->nullable()->after('process_name'),
            'description' => fn (Blueprint $table) => $table->text('description')->nullable()->after('short_code'),
            'department_id' => fn (Blueprint $table) => $table->unsignedBigInteger('department_id')->nullable()->after('description'),
            'display_order' => fn (Blueprint $table) => $table->unsignedInteger('display_order')->nullable()->after('process_sl_no_last'),
        ] as $column => $definition) {
            if (! Schema::hasColumn('process_items', $column)) {
                Schema::table('process_items', $definition);
            }
        }

        $companyId = DB::table('companies')->where('status', 'Active')->orderBy('id')->value('id');
        if ($companyId !== null) {
            DB::table('process_items')->whereNull('company_id')->update(['company_id' => $companyId]);
        }

        $codes = [1 => 'WRP', 2 => 'WEV', 3 => 'DYE', 4 => 'COA', 5 => 'PKG', 6 => 'DPR', 7 => 'CPR', 8 => 'LAB'];
        foreach ($codes as $id => $code) {
            DB::table('process_items')->where('id', $id)->whereNull('short_code')->update(['short_code' => $code]);
        }

        if (Schema::hasColumn('process_items', 'company_id')) {
            Schema::table('process_items', function (Blueprint $table): void {
                $table->index(['company_id', 'status'], 'process_items_company_status_idx');
                $table->index(['company_id', 'display_order'], 'process_items_company_order_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::table('process_items', function (Blueprint $table): void {
            foreach (['company_id', 'short_code', 'description', 'department_id', 'display_order'] as $column) {
                if (Schema::hasColumn('process_items', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
