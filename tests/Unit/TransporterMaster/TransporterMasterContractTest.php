<?php

namespace Tests\Unit\TransporterMaster;

use Tests\TestCase;

final class TransporterMasterContractTest extends TestCase
{
    public function test_transporter_master_reuses_shared_party_and_preserves_logistics_snapshots(): void
    {
        $migration = file_get_contents(base_path('database/migrations/2026_08_11_000010_extend_individuals_for_transporter_master.php'));
        $service = file_get_contents(base_path('app/Services/TransporterMasterService.php'));
        $routes = file_get_contents(base_path('routes/web.php'));
        $navigation = file_get_contents(base_path('app/Support/AdminNavigation.php'));
        $this->assertStringContainsString("Schema::hasTable('individuals')", $migration);
        $this->assertStringContainsString("where('type', 'transport')", $service);
        $this->assertStringContainsString('does not belong to this Transporter', $service);
        $this->assertStringContainsString('Referenced Transporters cannot be deleted', $service);
        $this->assertStringContainsString('transporter_code', $service);
        $this->assertStringContainsString('Transporter Master', $navigation);
        $this->assertStringContainsString('transporters.addresses', $routes);
        $this->assertStringContainsString('list_transporter', $routes);
        $this->assertStringNotContainsString("Schema::create('transporters'", $migration);
        $this->assertStringNotContainsString('vehicle_code', $service);
    }
}
