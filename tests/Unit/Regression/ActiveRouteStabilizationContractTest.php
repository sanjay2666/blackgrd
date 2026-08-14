<?php

namespace Tests\Unit\Regression;

use App\Http\Controllers\CommonController;
use App\Http\Controllers\SaleOrderController;
use App\Http\Middleware\EnforceFrontendPagePermission;
use App\Models\User;
use Illuminate\Routing\Route;
use Tests\TestCase;

class ActiveRouteStabilizationContractTest extends TestCase
{
    public function test_required_routes_resolve_to_public_controller_methods(): void
    {
        $expected = [
            'list_employee' => [CommonController::class, 'list_employee', 'auth:web,admin'],
            'list_item' => [CommonController::class, 'list_item', 'auth:web,admin'],
            'ajax_script/search_customer_ship_address' => [CommonController::class, 'search_customer_ship_address', 'auth:web,admin'],
            'list_saleOrderNumer' => [CommonController::class, 'list_saleOrderNumer', 'auth:web,admin'],
            'ajax_script/search_customer_bill_address' => [CommonController::class, 'search_customer_bill_address', 'auth:web,admin'],
            'show-saleorder-workorder-details/{id}' => [SaleOrderController::class, 'showSaleOrderWorkOrderDetails', 'auth:web'],
        ];

        foreach ($expected as $uri => [$controller, $method, $middleware]) {
            $route = $this->getRoute($uri);

            $this->assertNotNull($route, "Required GET route [{$uri}] is not registered.");
            $this->assertSame("{$controller}@{$method}", $route->getActionName());
            $this->assertTrue((new \ReflectionMethod($controller, $method))->isPublic());
            $this->assertContains($middleware, $route->gatherMiddleware());
        }
    }

    public function test_unsupported_legacy_routes_are_not_registered(): void
    {
        $removedUris = [
            'list_dyeing',
            'list_transport',
            'list_color_item',
            'list_item_type',
            'list_purchase_items',
            'ajax_script/search_vendor_address',
            'ajax_script/search_item_type',
            'ajax_script/search_customer_addressBilling',
            'ajax_script/search_customer_addressShipping',
            'ajax_script/search_customer_address',
            'find_saleOrderNumerByCustomer',
        ];

        foreach ($removedUris as $uri) {
            $this->assertNull($this->getRoute($uri), "Legacy GET route [{$uri}] is still active.");
        }
    }

    public function test_sale_order_work_order_details_has_one_canonical_registration(): void
    {
        $routes = $this->getRoutes('show-saleorder-workorder-details/{id}');

        $this->assertCount(1, $routes);
        $this->assertSame('show-saleorder-workorder-details', $routes->first()->getName());
        $this->assertSame(
            SaleOrderController::class.'@showSaleOrderWorkOrderDetails',
            $routes->first()->getActionName(),
        );

        $routeSource = file_get_contents(base_path('routes/web.php'));
        preg_match_all("/Route::get\('\/show-saleorder-workorder-details\/\{id\}'/", $routeSource, $matches);
        $this->assertCount(1, $matches[0]);
    }

    public function test_required_routes_reject_guests(): void
    {
        foreach ([
            '/list_employee',
            '/list_item',
            '/ajax_script/search_customer_ship_address?individualId=1',
            '/list_saleOrderNumer',
            '/ajax_script/search_customer_bill_address?individualId=1',
            '/show-saleorder-workorder-details/1',
        ] as $uri) {
            $this->get($uri)->assertRedirect(route('login'));
        }
    }

    public function test_sale_order_work_order_details_returns_the_existing_explicit_unavailable_response(): void
    {
        $this->withoutMiddleware(EnforceFrontendPagePermission::class);
        $this->actingAs($this->transientUser(), 'web');

        foreach (['1', 'not-an-encrypted-id'] as $id) {
            $this->get(route('show-saleorder-workorder-details', ['id' => $id]))
                ->assertRedirect(route('sale-orders.index'))
                ->assertSessionHas('message', 'Work order details page is not ready yet.')
                ->assertSessionHas('messageClass', 'errorClass');
        }
    }

    private function getRoute(string $uri): ?Route
    {
        return $this->getRoutes($uri)->first();
    }

    private function getRoutes(string $uri)
    {
        return collect(app('router')->getRoutes())
            ->filter(fn (Route $route) => in_array('GET', $route->methods(), true) && $route->uri() === $uri)
            ->values();
    }

    private function transientUser(): User
    {
        $user = new User();
        $user->forceFill([
            'id' => 990001,
            'user_type' => 'User',
            'name' => 'Route Stabilization User',
            'email' => 'route-stabilization@example.test',
            'status' => 'Active',
        ]);
        $user->exists = true;

        return $user;
    }
}
