<?php

namespace Tests\Unit\Authorization;

use App\Support\PermissionRegistry;
use App\Support\RoutePermissionRegistry;
use Tests\TestCase;

final class RoutePermissionMappingTest extends TestCase
{
    public function test_every_authenticated_route_has_an_explicit_permission_or_logout_allowlist(): void
    {
        $unmapped = collect(app('router')->getRoutes())
            ->filter(fn ($route): bool => collect($route->middleware())->contains(fn (string $middleware): bool => str_starts_with($middleware, 'auth:')))
            ->filter(fn ($route): bool => RoutePermissionRegistry::permission($route) === null && ! in_array($route->getName(), config('rbac_routes.excluded_authenticated', []), true))
            ->map(fn ($route): string => $route->methods()[0].' '.$route->uri().' ['.$route->getName().']')
            ->values()->all();

        $this->assertSame([], $unmapped);
    }

    public function test_sensitive_actions_use_distinct_canonical_permissions(): void
    {
        $this->assertSame('sale-orders.view', $this->permission('sale-orders.index'));
        $this->assertSame('sale-orders.cancel', $this->permission('saleorders.delete'));
        $this->assertSame('warehouse.adjust', $this->permission('warehouse.breakMeter'));
        $this->assertSame('financial-years.set-current', $this->permission('admin.financial-years.set-current'));
        $this->assertSame('roles.assign', $this->permission('admin.roles.assign.store'));
        $this->assertSame('security.delete', $this->permission('admin.login-attempts.destroy'));
        $this->assertNull($this->permission('logout'));
    }

    public function test_every_mapped_permission_is_in_the_canonical_registry(): void
    {
        $canonical = array_column(PermissionRegistry::all(), 'key');
        $unregistered = collect(app('router')->getRoutes())
            ->filter(fn ($route): bool => RoutePermissionRegistry::permission($route) !== null)
            ->map(fn ($route): ?string => RoutePermissionRegistry::permission($route))
            ->unique()->reject(fn (?string $permission): bool => in_array($permission, $canonical, true))->values()->all();

        $this->assertSame([], $unregistered);
    }

    public function test_route_groups_apply_server_side_rbac_after_organization_scope(): void
    {
        $routes = file_get_contents(base_path('routes/web.php'));
        $this->assertStringContainsString("['auth:web,admin', 'organization', 'rbac', 'audit']", $routes);
        $this->assertStringContainsString("['auth:web', 'organization', 'rbac', 'audit']", $routes);
        $this->assertStringContainsString("['auth:admin', 'organization', 'rbac', 'audit']", $routes);
    }

    private function permission(string $name): ?string
    {
        return RoutePermissionRegistry::permission(collect(app('router')->getRoutes())->first(fn ($route): bool => $route->getName() === $name));
    }
}
