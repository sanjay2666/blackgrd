<?php

namespace Tests\Unit\Authorization;

use App\Support\FrontendPermissionCatalog;
use App\Support\PermissionRegistry;
use App\Support\RoleTemplateCatalog;
use Tests\TestCase;

final class AdminBoundaryTest extends TestCase
{
    public function test_reserved_permissions_have_one_canonical_classification(): void
    {
        $reserved = PermissionRegistry::superAdminReserved();

        $this->assertContains('companies.configure', $reserved);
        $this->assertContains('security.manage', $reserved);
        $this->assertContains('audit-logs.view', $reserved);
        $this->assertContains('settings.configure', $reserved);
        $this->assertContains('organization.access-manage', $reserved);
        $this->assertSame([], array_intersect($reserved, RoleTemplateCatalog::all()['Admin']));
        $this->assertSame([], array_intersect($reserved, FrontendPermissionCatalog::keys()));
    }

    public function test_super_admin_authority_has_no_identity_id_or_email_bypass(): void
    {
        $authorization = file_get_contents(base_path('app/Services/AuthorizationService.php'));

        $this->assertStringContainsString("where('role_key', 'super-admin')", $authorization);
        $this->assertStringNotContainsString('whereKey(1)', $authorization);
        $this->assertStringNotContainsString('admin@blackgrd', $authorization);
        $this->assertStringNotContainsString('orderBy', $authorization);
    }

    public function test_role_management_enforces_reserved_boundary_on_the_server(): void
    {
        $service = file_get_contents(base_path('app/Services/RoleManagementService.php'));
        $controller = file_get_contents(base_path('app/Http/Controllers/Admin/RoleController.php'));

        $this->assertStringContainsString('PermissionRegistry::superAdminReserved()', $service);
        $this->assertStringContainsString('reserved_role_assignment_attempt', $service);
        $this->assertStringContainsString('reserved_role_ui_access_attempt', $controller);
        $this->assertStringContainsString("\$role->scope !== 'Company'", $controller);
    }

    public function test_admin_and_web_guards_remain_separate(): void
    {
        $auth = file_get_contents(base_path('config/auth.php'));
        $this->assertStringContainsString("'provider' => 'admins'", $auth);
        $this->assertStringContainsString("'provider' => 'users'", $auth);
        $this->assertStringContainsString("'model' => Admin::class", $auth);
        $this->assertStringContainsString("'model' => User::class", $auth);
    }
}
