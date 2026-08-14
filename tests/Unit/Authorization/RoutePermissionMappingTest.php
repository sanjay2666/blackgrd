<?php

namespace Tests\Unit\Authorization;

use App\Models\AllPage;
use Tests\TestCase;

final class RoutePermissionMappingTest extends TestCase
{
    public function test_every_authenticated_frontend_route_uses_page_permission_middleware(): void
    {
        $unmapped = collect(app('router')->getRoutes())
            ->filter(fn ($route): bool => collect($route->middleware())->contains(fn (string $middleware): bool => str_starts_with($middleware, 'auth:web')))
            ->filter(fn ($route): bool => ! collect($route->middleware())->contains('frontend-page'))
            ->map(fn ($route): string => $route->methods()[0].' '.$route->uri().' ['.$route->getName().']')
            ->values()->all();

        $this->assertSame([], $unmapped);
    }

    public function test_frontend_page_definitions_are_individual_and_exclude_admin_routes(): void
    {
        $pages = AllPage::frontendRouteDefinitions();
        $pageNames = $pages->pluck('page_name')->all();

        $this->assertContains('GET /show-workorders', $pageNames);
        $this->assertContains('POST /workorder/update-machine', $pageNames);
        $this->assertContains('GET /ajax_script/deleteGpInspDetails', $pageNames);
        $this->assertNotContains('GET /admin/dashboard', $pageNames);
        $this->assertSame($pageNames, $pages->pluck('page_name')->unique()->values()->all());
    }

    public function test_route_groups_keep_admin_full_access_and_frontend_page_enforcement(): void
    {
        $routes = file_get_contents(base_path('routes/web.php'));
        $this->assertStringContainsString("['auth:web,admin', 'organization', 'frontend-page', 'audit']", $routes);
        $this->assertStringContainsString("['auth:web', 'organization', 'frontend-page', 'audit']", $routes);
        $this->assertStringContainsString("['auth:admin', 'organization', 'audit']", $routes);
        $this->assertStringNotContainsString("middleware('permission:", $routes);
    }
}
