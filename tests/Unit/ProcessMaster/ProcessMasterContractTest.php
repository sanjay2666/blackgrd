<?php

namespace Tests\Unit\ProcessMaster;

use App\Support\PermissionRegistry;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ProcessMasterContractTest extends TestCase
{
    public function test_process_master_contract_preserves_legacy_identity_and_boundaries(): void
    {
        $migration = file_get_contents(base_path('database/migrations/2026_08_11_000003_harden_process_master.php'));
        $service = file_get_contents(base_path('app/Services/ProcessMasterService.php'));
        $architecture = file_get_contents(base_path('docs/architecture/process-master.md'));

        $this->assertStringContainsString("1 => 'WRP'", $migration);
        $this->assertStringContainsString("4 => 'COA'", $migration);
        $this->assertStringContainsString('Core process identities cannot be renamed.', $service);
        $this->assertStringContainsString('cannot be deleted; deactivate them instead.', $service);
        $this->assertStringContainsString('does not define the final Sale Order Item workflow', $architecture);
        $this->assertStringContainsString('must not be globally defined relative to Coating', $architecture);
        $this->assertFalse(str_contains($service, 'print_position'));
    }

    public function test_process_master_routes_and_admin_permissions_are_explicit(): void
    {
        foreach (['admin.process-items.index', 'admin.process-items.store', 'admin.process-items.update', 'admin.process-items.activate', 'admin.process-items.deactivate', 'admin.process-items.destroy'] as $route) {
            $this->assertTrue(Route::has($route), $route);
        }

        $permissions = array_column(PermissionRegistry::all(), 'key');
        foreach (['processes.view', 'processes.create', 'processes.update', 'processes.activate', 'processes.deactivate'] as $permission) {
            $this->assertContains($permission, $permissions);
        }
        $this->assertNotContains('processes.view', PermissionRegistry::frontendAssignable());
    }
}
