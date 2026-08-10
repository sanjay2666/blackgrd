<?php

namespace Tests\Unit\Auth;

use Tests\TestCase;

class AuthenticationSecurityContractTest extends TestCase
{
    public function test_authentication_routes_are_throttled_and_logout_is_post_only(): void
    {
        $routes = file_get_contents(base_path('routes/web.php'));

        $this->assertStringContainsString("->middleware('throttle:auth-login')", $routes);
        $this->assertStringContainsString("->middleware('throttle:password-reset')", $routes);
        $this->assertStringContainsString("Route::post('/logout'", $routes);
    }

    public function test_authentication_controllers_use_generic_failures_and_safe_redirects(): void
    {
        $userController = file_get_contents(base_path('app/Http/Controllers/Auth/UserAuthController.php'));
        $adminController = file_get_contents(base_path('app/Http/Controllers/Auth/AdminAuthController.php'));

        $this->assertStringContainsString('SafeAuthRedirect', $userController);
        $this->assertStringContainsString('SafeAuthRedirect', $adminController);
        $this->assertStringContainsString('Email or Password is incorrect.', $userController);
        $this->assertStringContainsString('Email or Password is incorrect.', $adminController);
        $this->assertStringContainsString("'status', 'Active'", $userController);
        $this->assertStringContainsString("'status'] = 'Active'", $adminController);
    }

    public function test_password_reset_is_generic_and_otp_is_not_routed(): void
    {
        $controller = file_get_contents(base_path('app/Http/Controllers/Auth/UserAuthController.php'));
        $routes = file_get_contents(base_path('routes/web.php'));

        $this->assertStringContainsString('If an account exists, a password reset link will be sent.', $controller);
        $this->assertStringNotContainsString('LoginOtpController', $controller);
        $this->assertStringNotContainsString("Route::post('/login-otp", $routes);
    }

    public function test_organization_context_is_single_company_and_validates_active_access(): void
    {
        $middleware = file_get_contents(base_path('app/Http/Middleware/ResolveOrganizationContext.php'));
        $context = file_get_contents(base_path('app/Services/CurrentOrganizationContext.php'));

        $this->assertStringContainsString("forget(['organization.company_id', 'organization.factory_id'])", $middleware);
        $this->assertStringContainsString('Company::canonical()', $context);
        $this->assertStringContainsString('Company switching is not available', $context);
        $this->assertStringContainsString("where('status', 'Active')", $context);
    }
}
