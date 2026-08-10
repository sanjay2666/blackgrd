<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $series = [
            ['series_key' => 'wpr-lot', 'document_name' => 'Work Process Requirement lot', 'prefix' => '', 'padding' => 0, 'reset_policy' => 'never', 'financial_year_aware' => false],
            ['series_key' => 'job-work-voucher', 'document_name' => 'Job Work voucher', 'prefix' => '', 'padding' => 0, 'reset_policy' => 'never', 'financial_year_aware' => false],
            ['series_key' => 'job-work-challan', 'document_name' => 'Job Work challan', 'prefix' => '', 'padding' => 0, 'reset_policy' => 'never', 'financial_year_aware' => false],
        ];
        foreach ([1 => 'W', 2 => 'V', 3 => 'D', 4 => 'C'] as $processId => $prefix) {
            $series[] = ['series_key' => 'work-order-'.$processId, 'document_name' => 'Work Order '.$prefix, 'prefix' => $prefix, 'padding' => 0, 'reset_policy' => 'never', 'financial_year_aware' => false];
        }
        foreach ($series as $row) {
            $highest = match ($row['series_key']) {
                'wpr-lot' => (int) (DB::table('work_process_requirements')->whereNotNull('req_lot_no')->selectRaw('MAX(CAST(req_lot_no AS UNSIGNED)) AS n')->value('n') ?? 0),
                'job-work-voucher' => (int) (DB::table('stock_mill_dispatches')->whereNotNull('voucher_number')->selectRaw('MAX(CAST(voucher_number AS UNSIGNED)) AS n')->value('n') ?? 0),
                'job-work-challan' => (int) (DB::table('stock_mill_dispatches')->whereNotNull('chalan_no')->selectRaw('MAX(CAST(chalan_no AS UNSIGNED)) AS n')->value('n') ?? 0),
                default => max(
                    (int) (DB::table('work_orders')->where('process_type_id', (int) substr($row['series_key'], -1))->whereNotNull('process_sl_no')->max('process_sl_no') ?? 0),
                    (int) (DB::table('process_items')->where('id', (int) substr($row['series_key'], -1))->value('process_sl_no_last') ?? 0),
                ),
            };
            DB::table('number_series')->insert($row + ['next_number' => $highest + 1, 'status' => 'Active', 'created_at' => $now, 'updated_at' => $now]);
        }
    }

    public function down(): void
    {
        DB::table('number_series')->whereIn('series_key', ['wpr-lot', 'job-work-voucher', 'job-work-challan', 'work-order-1', 'work-order-2', 'work-order-3', 'work-order-4'])->delete();
    }
};
