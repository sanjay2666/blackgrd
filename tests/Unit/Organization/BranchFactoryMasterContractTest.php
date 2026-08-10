<?php

namespace Tests\Unit\Organization;

use App\Support\PermissionRegistry;
use Tests\TestCase;

final class BranchFactoryMasterContractTest extends TestCase
{
    public function test_existing_branch_factory_structure_and_single_company_rules_are_documented(): void
    {
        $migration = file_get_contents(base_path('database/migrations/2026_08_10_000001_create_organization_scope_tables.php'));
        $service = file_get_contents(base_path('app/Services/BranchFactoryMasterService.php'));
        $navigation = file_get_contents(base_path('app/Support/AdminNavigation.php'));
        $this->assertStringContainsString("Schema::create('branches'", $migration);
        $this->assertStringContainsString("Schema::create('factories'", $migration);
        $this->assertStringContainsString('CurrentOrganizationContext', $service);
        $this->assertStringContainsString("'branches' => ['view', 'create', 'update', 'activate', 'deactivate']", file_get_contents(base_path('app/Support/PermissionRegistry.php')));
        $this->assertContains('branches.view', array_column(PermissionRegistry::all(), 'key'));
        $this->assertStringContainsString('admin.branches.index', $navigation);
    }

    public function test_branch_factory_routes_are_admin_only_and_share_canonical_permission(): void
    {
        $routes = file_get_contents(base_path('routes/web.php'));
        $mapping = file_get_contents(base_path('config/rbac_routes.php'));
        $this->assertStringContainsString("Route::middleware(['auth:admin', 'organization', 'rbac', 'audit'])", $routes);
        $this->assertStringContainsString("'admin.factories.update' => 'branches.update'", $mapping);
        $this->assertStringContainsString("'admin.branches.deactivate' => 'branches.deactivate'", $mapping);
    }
}
