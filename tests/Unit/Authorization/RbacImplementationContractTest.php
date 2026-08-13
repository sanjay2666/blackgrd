<?php

namespace Tests\Unit\Authorization;

use App\Support\FrontendPermissionCatalog;
use App\Support\PermissionRegistry;
use App\Support\RoleTemplateCatalog;
use Tests\TestCase;

class RbacImplementationContractTest extends TestCase
{
    public function test_rbac_schema_has_the_approved_four_tables_and_constraints(): void
    {
        $migration = file_get_contents(base_path('database/migrations/2026_08_10_000003_create_rbac_tables.php'));
        $this->assertStringContainsString("Schema::create('roles'", $migration);
        $this->assertStringContainsString("Schema::create('permissions'", $migration);
        $this->assertStringContainsString("Schema::create('role_permissions'", $migration);
        $this->assertStringContainsString("Schema::create('user_role_assignments'", $migration);
        $this->assertStringContainsString('$table->enum(\'principal_type\'', $migration);
        $this->assertStringContainsString('$table->unsignedBigInteger(\'principal_id\')', $migration);
        $this->assertStringContainsString('$table->enum(\'panel\'', $migration);
        $this->assertStringContainsString('$table->primary([\'role_id\', \'permission_id\'])', $migration);
        $this->assertStringContainsString('$table->foreign(\'company_id\')', $migration);
    }

    public function test_registry_is_version_controlled_and_sensitive_actions_are_distinct(): void
    {
        $this->assertCount(148, PermissionRegistry::all());
        $registry = file_get_contents(base_path('app/Support/PermissionRegistry.php'));
        $this->assertStringContainsString("'sale-orders' =>", $registry);
        $this->assertStringContainsString("'warehouse' =>", $registry);
        $this->assertStringContainsString("'financial-years' =>", $registry);
        $this->assertStringContainsString("'users' =>", $registry);
        $this->assertStringNotContainsString('cross-company', $registry);
        $this->assertStringContainsString("'warehouse' =>", $registry);
    }

    public function test_role_management_has_no_system_role_management_path(): void
    {
        $controller = file_get_contents(base_path('app/Http/Controllers/Admin/RoleController.php'));
        $service = file_get_contents(base_path('app/Services/RoleManagementService.php'));
        $this->assertStringContainsString('$role->scope !== \'Company\'', $service);
        $this->assertStringContainsString("where('scope', 'Company')", $controller);
        $this->assertStringNotContainsString('foreign-company', $service);
        $this->assertStringContainsString('principal_type', $service);
    }

    public function test_admin_and_frontend_authentication_use_separate_guard_models(): void
    {
        $auth = file_get_contents(base_path('config/auth.php'));
        $admin = file_get_contents(base_path('app/Models/Admin.php'));
        $this->assertStringContainsString("'provider' => 'admins'", $auth);
        $this->assertStringContainsString("'model' => Admin::class", $auth);
        $this->assertStringContainsString("user_type', 'Admin'", $admin);
        $this->assertStringContainsString("'user_type'", file_get_contents(base_path('app/Models/User.php')));
    }

    public function test_bootstrap_templates_preserve_panel_boundaries(): void
    {
        $templates = RoleTemplateCatalog::all();
        $this->assertArrayHasKey('Admin', $templates);
        $this->assertArrayHasKey('Frontend Administrator', $templates);
        $this->assertGreaterThan(100, count($templates['Frontend Administrator']));
        $this->assertNotContains('roles.assign', $templates['Frontend Administrator']);
        $this->assertNotContains('users.manage', $templates['Frontend Administrator']);
        $this->assertNotContains('security.manage', $templates['Frontend Administrator']);
        $this->assertNotContains('super-admin', $templates['Admin']);
    }

    public function test_super_admin_bootstrap_is_exact_admin_only_and_confirmed(): void
    {
        $command = file_get_contents(base_path('app/Console/Commands/AssignSuperAdminCommand.php'));
        $this->assertStringContainsString('Admin::query()', $command);
        $this->assertStringContainsString("where('status', 'Active')", $command);
        $this->assertStringContainsString("where('role_key', 'super-admin')", $command);
        $this->assertStringContainsString('$this->confirm', $command);
        $this->assertStringNotContainsString('User::query()', $command);
    }

    public function test_deterministic_bootstrap_contains_only_approved_accounts(): void
    {
        $command = file_get_contents(base_path('app/Console/Commands/BootstrapRbacCommand.php'));
        $this->assertStringContainsString('whereKey(1)', $command);
        $this->assertStringContainsString('whereKey(2)', $command);
        $this->assertStringContainsString('admin@blackgrd.test', $command);
        $this->assertStringContainsString('unsanjay4@gmail.com', $command);
        $this->assertStringContainsString("principal_type' => 'Admin'", $command);
        $this->assertStringContainsString("principal_type' => 'User'", $command);
    }

    public function test_user_specific_permission_customization_is_additive_and_frontend_only(): void
    {
        $migration = file_get_contents(base_path('database/migrations/2026_08_10_000004_create_user_permission_overrides_table.php'));
        $service = file_get_contents(base_path('app/Services/UserPermissionManagementService.php'));
        $this->assertStringContainsString("Schema::create('user_permission_overrides'", $migration);
        $this->assertStringContainsString("enum('effect', ['Allow', 'Deny'])", $migration);
        $this->assertStringContainsString('FrontendPermissionCatalog::keys()', $service);
        $this->assertStringContainsString('array_merge($rolePermissions, $allows)', file_get_contents(base_path('app/Services/AuthorizationService.php')));
        $this->assertStringContainsString('auth(\'admin\')->check()', $service);
        $this->assertStringNotContainsString('super-admin', $service);
    }

    public function test_frontend_catalog_excludes_admin_only_permissions(): void
    {
        $keys = FrontendPermissionCatalog::keys();
        $this->assertNotContains('roles.assign', $keys);
        $this->assertNotContains('users.manage', $keys);
        $this->assertNotContains('security.manage', $keys);
        $this->assertContains('sale-orders.cancel', $keys);
        $this->assertContains('warehouse.adjust', $keys);
    }
}
