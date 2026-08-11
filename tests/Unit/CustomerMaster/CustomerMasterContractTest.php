<?php

namespace Tests\Unit\CustomerMaster;

use Tests\TestCase;

class CustomerMasterContractTest extends TestCase
{
    public function test_customer_master_reuses_shared_party_and_protects_addresses(): void
    {
        $migration = file_get_contents(base_path('database/migrations/2026_08_11_000008_extend_individuals_for_customer_master.php'));
        $service = file_get_contents(base_path('app/Services/CustomerMasterService.php'));
        $architecture = file_get_contents(base_path('docs/architecture/customer-master.md'));
        $this->assertStringContainsString("Schema::hasTable('individuals')", $migration);
        $this->assertStringContainsString("\$table->string('customer_code', 50)", $migration);
        $this->assertStringContainsString("where('type', 'customers')", $service);
        $this->assertStringContainsString('The selected address does not belong to this Customer.', $service);
        $this->assertStringContainsString('Customer Master reuses canonical party identity', $architecture);
        $this->assertStringContainsString('never rewrite transaction snapshots', $architecture);
        $this->assertStringContainsString('customers.addresses', file_get_contents(base_path('routes/web.php')));
        $this->assertStringContainsString('ValidateCustomerSaleOrder', file_get_contents(base_path('app/Http/Middleware/ValidateCustomerSaleOrder.php')));
    }
}
