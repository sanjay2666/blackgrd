<?php

namespace Tests\Unit\QualityGate;

use App\Services\QualityGateService;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

final class QualityGateServiceTest extends TestCase
{
    public function test_current_foundation_registries_are_consistent(): void
    {
        $checks = app(QualityGateService::class);

        $this->assertSame([], $checks->permissionRegistryErrors());
        $this->assertSame([], $checks->sourceFoundationErrors());
    }

    public function test_unmapped_authenticated_route_is_rejected(): void
    {
        $route = (new Route(['GET'], 'future-secured', static fn (): string => 'ok'))
            ->name('future-secured')
            ->middleware('auth:web');

        $errors = app(QualityGateService::class)->routeCoverageErrors([...app('router')->getRoutes(), $route]);

        $this->assertCount(1, $errors);
        $this->assertStringContainsString('future-secured', $errors[0]);
    }

    public function test_stale_named_mapping_is_rejected(): void
    {
        Config::set('rbac_routes.frontend_named.quality-gate-test-stale', 'dashboard.view');

        $errors = app(QualityGateService::class)->routeCoverageErrors([]);

        $this->assertContains('Stale RBAC route mapping [quality-gate-test-stale].', $errors);
    }
}
