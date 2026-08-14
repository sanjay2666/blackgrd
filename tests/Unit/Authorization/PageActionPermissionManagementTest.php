<?php

namespace Tests\Unit\Authorization;

use Tests\TestCase;

final class PageActionPermissionManagementTest extends TestCase
{
    public function test_frontend_permission_screen_uses_existing_page_assignment_tables(): void
    {
        $controller = file_get_contents(base_path('app/Http/Controllers/Admin/UserPermissionController.php'));
        $middleware = file_get_contents(base_path('app/Http/Middleware/EnforceFrontendPagePermission.php'));

        $this->assertStringContainsString('AllPage::frontendRouteDefinitions()', $controller);
        $this->assertStringContainsString('UserWebPage::query()', $controller);
        $this->assertStringContainsString("where('status', 'Active')->exists()", $middleware);
        $this->assertStringNotContainsString('FrontendPermissionCatalog', $middleware);
    }

    public function test_role_and_user_permission_screens_use_module_grouping_and_effective_checkbox_assignment(): void
    {
        $roleForm = file_get_contents(base_path('resources/views/admin/roles/form.blade.php'));
        $userPermissions = file_get_contents(base_path('resources/views/admin/user-permissions/index.blade.php'));
        $service = file_get_contents(base_path('app/Services/RoleManagementService.php'));

        $templateFiles = [
            'resources/views/admin/roles/index.blade.php',
            'resources/views/admin/roles/form.blade.php',
            'resources/views/admin/roles/assign.blade.php',
            'resources/views/admin/users/index.blade.php',
            'resources/views/admin/users/form.blade.php',
            'resources/views/admin/users/department-access.blade.php',
            'resources/views/admin/user-permissions/index.blade.php',
        ];

        foreach ($templateFiles as $templateFile) {
            $template = file_get_contents(base_path($templateFile));

            $this->assertStringContainsString('id="preloader"', $template, $templateFile);
            $this->assertStringContainsString('class="content-header"', $template, $templateFile);
            $this->assertStringContainsString('class="panel panel-bd lobidrag"', $template, $templateFile);
            $this->assertStringContainsString('admin.common.formfooterscript', $template, $templateFile);
        }

        $this->assertStringContainsString('permission-module', $roleForm);
        $this->assertStringContainsString('select-visible', $roleForm);
        $this->assertStringContainsString('admin.common.formfooterscript', $roleForm);
        $this->assertStringContainsString('class="content-header"', $roleForm);
        $this->assertStringContainsString('class="panel panel-bd lobidrag"', $roleForm);
        $this->assertStringContainsString('class="reset-button"', $roleForm);
        $this->assertStringContainsString('permission-checkbox', $userPermissions);
        $this->assertStringContainsString('select-all-permissions', $userPermissions);
        $this->assertStringContainsString('name="page_ids[]"', $userPermissions);
        $this->assertStringNotContainsString('<select name="permissions[', $userPermissions);
        $this->assertStringContainsString('PermissionRegistry::assignableForPanel($panel)', $service);
    }
}
