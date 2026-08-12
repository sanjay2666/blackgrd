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

    public function test_live_apply_is_hash_pinned_backed_up_and_preserves_process_identity(): void
    {
        $command = file_get_contents(base_path('app/Console/Commands/ApplyReviewedProcessMasterMigrationCommand.php'));

        $this->assertStringContainsString("private const MIGRATION = '2026_08_11_000003_harden_process_master'", $command);
        $this->assertStringContainsString('private const HASH =', $command);
        $this->assertStringContainsString('backup-manifest', $command);
        $this->assertStringContainsString('writes-stopped', $command);
        $this->assertStringContainsString('CORE_IDENTITIES', $command);
        $this->assertStringContainsString("whereNull('company_id')", $command);
    }

    public function test_process_company_scope_uses_the_resolved_request_context_without_a_permissive_fallback(): void
    {
        $provider = file_get_contents(base_path('app/Providers/AppServiceProvider.php'));
        $process = file_get_contents(base_path('app/Models/ProcessItem.php'));
        $scope = file_get_contents(base_path('app/Models/Concerns/BelongsToCompany.php'));
        $individuals = file_get_contents(base_path('app/Http/Controllers/Admin/IndividualController.php'));

        $this->assertStringContainsString('scoped(CurrentOrganizationContext::class', $provider);
        $this->assertStringContainsString('use BelongsToCompany;', $process);
        $this->assertStringContainsString(".company_id', \$context->companyId()", $scope);
        $this->assertStringNotContainsString('Schema::hasColumn', $scope);
        $this->assertStringContainsString("Individual::with(['processItem', 'department'])", $individuals);
    }
}
