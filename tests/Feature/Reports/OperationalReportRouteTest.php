<?php

namespace Tests\Feature\Reports;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class OperationalReportRouteTest extends TestCase
{
    public function test_operational_report_routes_are_named_get_only_and_reject_guests(): void
    {
        $routes = [
            'reports.pending-orders',
            'reports.production-status',
            'reports.stock-movement',
            'reports.packaging',
            'reports.customer-dispatch',
            'reports.purchase-receiving',
            'reports.job-work',
            'reports.inspection-rejection',
        ];

        foreach ($routes as $name) {
            $route = Route::getRoutes()->getByName($name);

            $this->assertNotNull($route, "Missing operational report route [{$name}].");
            $this->assertSame(['GET', 'HEAD'], $route->methods());
            $this->get(route($name))->assertRedirect(route('login'));
        }

        $autocomplete = Route::getRoutes()->getByName('reports.autocomplete');
        $this->assertNotNull($autocomplete);
        $this->assertSame(['GET', 'HEAD'], $autocomplete->methods());
        $this->get(route('reports.autocomplete', 'item'))->assertRedirect(route('login'));
    }
}
