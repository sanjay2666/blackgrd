<?php

namespace Tests\Unit\QualityGate;

use App\Services\QualityGateService;
use Illuminate\Routing\Route;
use Tests\TestCase;

final class QualityGateServiceTest extends TestCase
{
    public function test_current_foundation_registries_are_consistent(): void
    {
        $checks = app(QualityGateService::class);

        $this->assertSame([], $checks->permissionRegistryErrors());
        $this->assertSame([], $checks->sourceFoundationErrors());
    }

    public function test_frontend_route_without_page_permission_middleware_is_rejected(): void
    {
        $route = (new Route(['GET'], 'future-secured', static fn (): string => 'ok'))
            ->name('future-secured')
            ->middleware('auth:web');

        $errors = app(QualityGateService::class)->routeCoverageErrors([...app('router')->getRoutes(), $route]);

        $this->assertCount(1, $errors);
        $this->assertStringContainsString('future-secured', $errors[0]);
        $this->assertStringContainsString('page-permission', $errors[0]);
    }

    public function test_admin_route_does_not_require_frontend_page_permission_middleware(): void
    {
        $route = (new Route(['GET'], 'admin/future-secured', static fn (): string => 'ok'))
            ->name('admin.future-secured')
            ->middleware('auth:admin');

        $errors = app(QualityGateService::class)->routeCoverageErrors([$route]);

        $this->assertSame([], $errors);
    }
}
