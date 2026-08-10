<?php

namespace Tests\Unit\Authorization;

use App\Support\AdminNavigation;
use App\Support\PermissionRegistry;
use Tests\TestCase;

final class AdminNavigationTest extends TestCase
{
    public function test_navigation_contains_only_current_named_admin_routes_and_no_template_links(): void
    {
        $sidebar = file_get_contents(base_path('resources/views/admin/common/sidebar.blade.php'));
        $routeNames = collect(AdminNavigation::groups())->flatMap(fn (array $group): array => array_column($group['items'], 'route'))->all();
        $routes = collect(app('router')->getRoutes())->map(fn ($route): ?string => $route->getName())->filter()->all();

        $this->assertSame([], array_diff($routeNames, $routes));
        $this->assertStringNotContainsString('.html', $sidebar);
        $this->assertStringNotContainsString('invoice.html', $sidebar);
    }

    public function test_navigation_uses_rbac_permissions_and_reserved_items_are_not_public_by_default(): void
    {
        $items = collect(AdminNavigation::groups())->flatMap(fn (array $group): array => $group['items']);

        $this->assertTrue($items->contains(fn (array $item): bool => $item['route'] === 'admin.users.index' && $item['permission'] === 'users.manage'));
        $this->assertTrue($items->contains(fn (array $item): bool => $item['route'] === 'admin.audit-logs.index' && $item['permission'] === 'audit-logs.view'));
        $this->assertContains('companies.view', PermissionRegistry::superAdminReserved());
        $this->assertContains('audit-logs.view', PermissionRegistry::superAdminReserved());
        $this->assertStringContainsString('AdminNavigation::visible', file_get_contents(base_path('resources/views/admin/common/sidebar.blade.php')));
    }
}
