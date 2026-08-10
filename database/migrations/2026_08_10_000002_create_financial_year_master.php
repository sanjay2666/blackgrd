<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $tables = [
        'departments', 'department_returns', 'department_return_requests', 'fabric_fault_reasons',
        'gate_passes', 'gate_pass_print_logs', 'greige_receive_stock_item_from_job_works',
        'item_yarn_requirements', 'purchases', 'purchase_items', 'purchase_orders',
        'purchase_order_items', 'receive_stock_mill_dispatches', 'receive_stock_mill_dispatch_items',
        'sale_orders', 'sale_order_items', 'stock_mill_dispatches', 'stock_mill_dispatch_items',
        'stock_mill_returns', 'stock_mill_return_items', 'warehouses', 'warehouse_balance_items',
        'warehouse_compartments', 'warehouse_in_items', 'warehouse_item_stocks',
        'warehouse_item_stock_files', 'warehouse_out_items', 'work_inspections',
        'work_inspection_details', 'work_orders', 'work_order_items', 'work_process_received_items',
        'work_process_requirements', 'work_purchase_requirements',
    ];

    public function up(): void
    {
        Schema::create('financial_years', function (Blueprint $table): void {
            $table->id();
            $table->unsignedInteger('company_id');
            $table->char('code', 4);
            $table->string('display_name', 7);
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_current')->default(false);
            $table->dateTime('locked_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('modified_by')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->enum('status', ['Active', 'Inactive', 'Deleted'])->default('Active');
            $table->unique(['company_id', 'code']);
            $table->index(['company_id', 'is_current', 'status']);
            $table->foreign('company_id')->references('id')->on('companies')->restrictOnDelete();
        });

        foreach ($this->tables as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'financial_year')) {
                Schema::table($tableName, function (Blueprint $table): void {
                    $table->unsignedBigInteger('financial_year_id')->nullable()->index();
                });
            }
        }

        $companyId = DB::table('companies')->where('status', 'Active')->orderBy('id')->value('id');
        if ($companyId === null) {
            return;
        }

        $now = now();
        $code = $this->currentCode($now->year, $now->month);
        $this->ensureYear($companyId, $code, true);

        foreach ($this->tables as $tableName) {
            if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, 'financial_year_id')) {
                continue;
            }

            $companyColumn = Schema::hasColumn($tableName, 'company_id') ? 'company_id' : null;
            $query = DB::table($tableName)->whereNotNull('financial_year');
            if ($companyColumn !== null) {
                $query->where(function ($nested) use ($companyId): void {
                    $nested->where('company_id', $companyId)->orWhereNull('company_id');
                });
            }

            $query->where('financial_year', $code)->update(['financial_year_id' => DB::table('financial_years')->where('company_id', $companyId)->where('code', $code)->value('id')]);
        }

        // Purchase orders used the invalid four-digit value 2026, but all three
        // rows have July 2026 purchase dates and therefore map to 2026-27.
        if (Schema::hasTable('purchase_orders') && Schema::hasColumn('purchase_orders', 'financial_year_id')) {
            DB::table('purchase_orders')->where('financial_year', '2026')
                ->whereBetween('purchased_on', ['2026-04-01 00:00:00', '2027-03-31 23:59:59'])
                ->update(['financial_year_id' => DB::table('financial_years')->where('company_id', $companyId)->where('code', $code)->value('id')]);
        }
    }

    public function down(): void
    {
        foreach (array_reverse($this->tables) as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'financial_year_id')) {
                Schema::table($tableName, fn (Blueprint $table): mixed => $table->dropColumn('financial_year_id'));
            }
        }
        Schema::dropIfExists('financial_years');
    }

    private function ensureYear(int $companyId, string $code, bool $current): void
    {
        $startYear = 2000 + (int) substr($code, 0, 2);
        $endYear = 2000 + (int) substr($code, 2, 2);
        $year = DB::table('financial_years')->where('company_id', $companyId)->where('code', $code)->first();
        $data = [
            'company_id' => $companyId,
            'code' => $code,
            'display_name' => $startYear.'-'.substr((string) $endYear, -2),
            'start_date' => $startYear.'-04-01',
            'end_date' => $endYear.'-03-31',
            'is_current' => $current,
            'status' => 'Active',
            'created_at' => now(),
            'updated_at' => now(),
        ];
        if ($year === null) {
            DB::table('financial_years')->insert($data);
        } else {
            DB::table('financial_years')->where('id', $year->id)->update(['is_current' => $current, 'status' => 'Active', 'updated_at' => now()]);
        }
    }

    private function currentCode(int $year, int $month): string
    {
        $startYear = $month >= 4 ? $year : $year - 1;
        return substr((string) $startYear, -2).substr((string) ($startYear + 1), -2);
    }
};
