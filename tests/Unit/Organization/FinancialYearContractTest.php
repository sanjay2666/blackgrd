<?php

namespace Tests\Unit\Organization;

use Tests\TestCase;

class FinancialYearContractTest extends TestCase
{
    public function test_financial_year_contract_has_company_scope_and_safe_resolver(): void
    {
        $migration = file_get_contents(base_path('database/migrations/2026_08_10_000002_create_financial_year_master.php'));
        $resolver = file_get_contents(base_path('app/Services/FinancialYearResolver.php'));
        $helper = file_get_contents(base_path('app/Helpers/helpers.php'));

        $this->assertStringContainsString("Schema::create('financial_years'", $migration);
        $this->assertStringContainsString("\$table->unique(['company_id', 'code'])", $migration);
        $this->assertStringContainsString('MissingCurrentFinancialYear', $resolver);
        $this->assertStringContainsString('FinancialYearResolver::class', $helper);
    }

    public function test_transaction_compatibility_columns_are_additive_and_nullable(): void
    {
        $migration = file_get_contents(base_path('database/migrations/2026_08_10_000002_create_financial_year_master.php'));

        $this->assertStringContainsString("\$table->unsignedBigInteger('financial_year_id')->nullable()->index()", $migration);
        $this->assertStringContainsString("where('financial_year', '2026')", $migration);
        $this->assertStringContainsString("whereBetween('purchased_on'", $migration);
    }
}
