<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        DB::table('number_series')->updateOrInsert(
            ['series_key' => 'sales-challan', 'financial_year_id' => null],
            ['document_name' => 'Sales Challan', 'prefix' => 'SC-', 'suffix' => null, 'padding' => 5, 'next_number' => 1, 'reset_policy' => 'financial_year', 'financial_year_aware' => true, 'status' => 'Active', 'created_at' => $now, 'updated_at' => $now]
        );

        foreach (['view', 'create', 'dispatch', 'cancel', 'print'] as $action) {
            DB::table('permissions')->updateOrInsert(
                ['permission_key' => 'sales-challans.'.$action],
                ['resource' => 'sales-challans', 'action' => $action, 'category' => 'sales-challans', 'description' => 'Sales challans '.$action, 'is_critical' => in_array($action, ['dispatch', 'cancel'], true), 'status' => 'Active']
            );
        }
    }

    public function down(): void
    {
        DB::table('number_series')->where('series_key', 'sales-challan')->delete();
        DB::table('permissions')->whereIn('permission_key', ['sales-challans.view', 'sales-challans.create', 'sales-challans.dispatch', 'sales-challans.cancel', 'sales-challans.print'])->delete();
    }
};
