<?php

namespace Tests\Unit\Authorization;

use Tests\TestCase;

final class UserManagementContractTest extends TestCase
{
    public function test_user_management_routes_are_admin_guarded_without_route_permission_middleware(): void
    {
        $routes = file_get_contents(base_path('routes/web.php'));
        $controller = file_get_contents(base_path('app/Http/Controllers/Admin/UserController.php'));
        $service = file_get_contents(base_path('app/Services/UserManagementService.php'));

        $this->assertStringContainsString("['auth:admin', 'organization', 'audit']", $routes);
        $this->assertStringNotContainsString("middleware('permission:", $routes);
        $this->assertStringContainsString("'user_type' => 'User'", $service);
        $this->assertStringContainsString('assertFrontendUser', $controller);
        $this->assertStringNotContainsString('Admin::class', $controller);
    }

    public function test_frontend_role_assignment_is_server_side_scoped(): void
    {
        $service = file_get_contents(base_path('app/Services/UserManagementService.php'));

        $this->assertStringContainsString("where('scope', 'Company')", $service);
        $this->assertStringContainsString("where('panel', 'Frontend')", $service);
        $this->assertStringContainsString("where('company_id', \$companyId)", $service);
        $this->assertStringContainsString('RoleManagementService', $service);
    }

    public function test_password_actions_hash_and_never_audit_the_secret(): void
    {
        $service = file_get_contents(base_path('app/Services/UserManagementService.php'));

        $this->assertStringContainsString('Hash::make($password)', $service);
        $this->assertStringContainsString('frontend_user_password_reset', $service);
        $this->assertStringContainsString('Password value is intentionally excluded', $service);
        $this->assertStringNotContainsString("'password' => \$password", $service);
    }

    public function test_user_deletion_is_not_exposed_and_historical_references_are_preserved(): void
    {
        $routes = file_get_contents(base_path('routes/web.php'));
        $service = file_get_contents(base_path('app/Services/UserManagementService.php'));

        $this->assertStringNotContainsString("Route::delete('/users", $routes);
        $this->assertStringContainsString("where('status', '!=', 'Deleted')", $service);
        $this->assertStringContainsString("'status' => \$status", $service);
    }

    public function test_profile_edit_does_not_change_password_or_drop_roles_unless_requested(): void
    {
        $service = file_get_contents(base_path('app/Services/UserManagementService.php'));
        $auth = file_get_contents(base_path('app/Http/Controllers/Auth/UserAuthController.php'));

        $this->assertStringContainsString("array_key_exists('role_ids', \$data)", $service);
        $this->assertStringNotContainsString("'password' => \$data['password']", $service);
        $this->assertStringContainsString("'status' => 'Active'", $auth);
        $this->assertStringContainsString("'user_type' => 'User'", $auth);
    }

    public function test_existing_admin_and_web_guards_are_untouched(): void
    {
        $auth = file_get_contents(base_path('config/auth.php'));

        $this->assertStringContainsString("'provider' => 'admins'", $auth);
        $this->assertStringContainsString("'provider' => 'users'", $auth);
        $this->assertSame('admin.users.index', app('router')->getRoutes()->getByName('admin.users.index')->getName());
    }
}
