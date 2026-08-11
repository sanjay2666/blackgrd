<?php

namespace Tests\Unit\Authorization;

use App\Support\AdminNavigation;
use Tests\TestCase;

final class FabricQualityAuthorizationTest extends TestCase
{
    public function test_fabric_quality_is_registered_as_a_master_and_navigation_is_permission_aware(): void
    {
        $routes = collect(AdminNavigation::groups())->flatMap(fn (array $group): array => $group['items']);

        $this->assertTrue($routes->contains(fn (array $item): bool => $item['route'] === 'admin.fabric-qualities.index' && $item['permission'] === 'masters.view'));
        $this->assertSame('masters.update', config('rbac_routes.admin_custom')['admin.fabric-qualities.activate']);
        $this->assertSame('masters.update', config('rbac_routes.admin_custom')['admin.fabric-qualities.deactivate']);
        $this->assertSame('masters', config('rbac_routes.admin_resources')['fabric-qualities']);
    }
}
