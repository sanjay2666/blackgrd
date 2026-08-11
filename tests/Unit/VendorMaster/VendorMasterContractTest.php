<?php

namespace Tests\Unit\VendorMaster;

use Tests\TestCase;

final class VendorMasterContractTest extends TestCase
{
    public function test_vendor_master_reuses_shared_party_and_preserves_transaction_snapshots(): void
    {
        $migration = file_get_contents(base_path('database/migrations/2026_08_11_000009_extend_individuals_for_vendor_master.php'));
        $service = file_get_contents(base_path('app/Services/VendorMasterService.php'));
        $routes = file_get_contents(base_path('routes/web.php'));
        $navigation = file_get_contents(base_path('app/Support/AdminNavigation.php'));
        $this->assertStringContainsString("Schema::hasTable('individuals')", $migration);
        $this->assertStringContainsString("where('type', 'vendors')", $service);
        $this->assertStringContainsString('does not belong to this Vendor', $service);
        $this->assertStringContainsString('Referenced Vendors cannot be deleted', $service);
        $this->assertStringContainsString('vendor_code', $service);
        $this->assertStringContainsString('Vendor Master', $navigation);
        $this->assertStringContainsString('vendors.addresses', $routes);
        $this->assertStringNotContainsString("Schema::create('vendors'", $migration);
    }
}
