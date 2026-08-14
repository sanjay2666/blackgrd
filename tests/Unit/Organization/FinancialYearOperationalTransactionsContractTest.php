<?php

namespace Tests\Unit\Organization;

use Tests\TestCase;

class FinancialYearOperationalTransactionsContractTest extends TestCase
{
    public function test_new_operational_transaction_tables_have_additive_financial_year_tracking(): void
    {
        $migration = file_get_contents(database_path('migrations/2026_08_14_000005_add_financial_year_to_new_operational_transactions.php'));

        foreach ([
            'packaging_orders',
            'packaging_order_items',
            'packaging_roll_allocations',
            'sales_challan_items',
            'sales_challan_roll_allocations',
            'production_genealogy_links',
        ] as $table) {
            $this->assertStringContainsString("'{$table}'", $migration);
        }
        $this->assertStringContainsString("unsignedBigInteger('financial_year_id')->nullable()", $migration);
        $this->assertStringContainsString("index('financial_year_id'", $migration);
        $this->assertStringContainsString("dropColumn('financial_year_id')", $migration);
    }

    public function test_packaging_and_sales_challan_store_current_financial_year_and_filter_by_it(): void
    {
        $packaging = file_get_contents(app_path('Http/Controllers/PackagingController.php'));
        $challans = file_get_contents(app_path('Http/Controllers/SalesChallanController.php'));
        $workOrders = file_get_contents(app_path('Http/Controllers/WorkOrderController.php'));

        $this->assertStringContainsString('$financialYears->current($companyId)', $packaging);
        $this->assertStringContainsString("'financial_year_id' => \$financialYear->id", $packaging);
        $this->assertStringContainsString("where('financial_year_id', (int) dec((string) \$request->financial_year_id))", $packaging);
        $this->assertStringContainsString("'financial_year_id' => \$financialYear->id", $challans);
        $this->assertStringContainsString("where('financial_year_id', (int) dec((string) \$request->financial_year_id))", $challans);
        $this->assertStringContainsString('ProductionGenealogyLink::create([', $workOrders);
        $this->assertSame(4, substr_count($workOrders, "'financial_year_id' => \$financialYear->id"));
    }
}
