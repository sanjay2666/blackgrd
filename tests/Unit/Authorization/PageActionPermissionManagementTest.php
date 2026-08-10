<?php

namespace Tests\Unit\Authorization;

use App\Support\FrontendPermissionCatalog;
use App\Support\PermissionRegistry;
use Tests\TestCase;

final class PageActionPermissionManagementTest extends TestCase
{
    public function test_registry_has_unique_action_keys_and_panel_classification_is_canonical(): void
    {
        $keys = array_column(PermissionRegistry::all(), 'key');

        $this->assertSame($keys, array_values(array_unique($keys)));
        $this->assertContains('sale-orders.cancel', PermissionRegistry::assignableForPanel('Frontend'));
        $this->assertNotContains('roles.assign', PermissionRegistry::assignableForPanel('Frontend'));
        $this->assertContains('roles.assign', PermissionRegistry::assignableForPanel('Admin'));
        $this->assertSame(PermissionRegistry::frontendAssignable(), FrontendPermissionCatalog::keys());
    }

    public function test_role_and_user_permission_screens_use_module_grouping_and_effective_explanation(): void
    {
        $roleForm = file_get_contents(base_path('resources/views/admin/roles/form.blade.php'));
        $userPermissions = file_get_contents(base_path('resources/views/admin/user-permissions/index.blade.php'));
        $service = file_get_contents(base_path('app/Services/RoleManagementService.php'));

        $this->assertStringContainsString('permission-module', $roleForm);
        $this->assertStringContainsString('select-visible', $roleForm);
        $this->assertStringContainsString('Role:', $userPermissions);
        $this->assertStringContainsString('Override:', $userPermissions);
        $this->assertStringContainsString('Effective:', $userPermissions);
        $this->assertStringContainsString('PermissionRegistry::assignableForPanel($panel)', $service);
    }
}
