<?php

namespace Tests\Feature\Regression;

use Illuminate\Routing\Route;
use Tests\TestCase;

class BusinessFlowRouteBaselineTest extends TestCase
{
    /**
     * This is a route/controller characterization baseline. It deliberately
     * does not submit business transactions against the operational database.
     */
    public function test_requested_business_flow_route_contracts_are_registered(): void
    {
        $contracts = [
            // Sale Order create, edit, cancel and print.
            ['sale-orders.create', 'GET', 'App\\Http\\Controllers\\SaleOrderController@create'],
            ['sale-orders.store', 'POST', 'App\\Http\\Controllers\\SaleOrderController@store'],
            ['sale-orders.edit', 'GET', 'App\\Http\\Controllers\\SaleOrderController@edit'],
            ['sale-order.update', 'POST', 'App\\Http\\Controllers\\SaleOrderController@updateSaleOrder'],
            ['cancelSaleOrderItem', 'POST', 'App\\Http\\Controllers\\SaleOrderController@cancelSaleOrderItem'],
            ['saleorders.print', 'GET', 'App\\Http\\Controllers\\SaleOrderController@printSaleOrder'],

            // Work Order generation and Weaving -> Warping shift.
            ['store_workorder', 'POST', 'App\\Http\\Controllers\\WorkOrderController@store'],

            // Warehouse inward, purchase receipt and requisition allotment.
            ['add-item-in-warehouse', 'GET', 'App\\Http\\Controllers\\WarehouseItemController@add_item_in_warehouse'],
            ['store_item_in_warehouse', 'POST', 'App\\Http\\Controllers\\WarehouseItemController@store_item_in_warehouse'],
            ['add-received-item-in-warehouse', 'GET', 'App\\Http\\Controllers\\WarehouseItemController@add_received_item_in_warehouse'],
            ['storeReceivedItemsFromInvoice', 'POST', 'App\\Http\\Controllers\\WarehouseItemController@storeReceivedItemsFromInvoice'],
            ['StoreWarehouseStockAllotment', 'POST', 'App\\Http\\Controllers\\WorkProcessRequirementController@StoreWarehouseStockAllotment'],

            // Inspection, Gate Pass and department warehouse receipt/return.
            ['show-workorder-inspection', 'GET', 'App\\Http\\Controllers\\WorkOrderController@show_workorder_inspection_report'],
            ['print-workorder-gatepass', 'GET', 'App\\Http\\Controllers\\WorkOrderController@print_workorder_gatepass'],
            ['receiveWorkItemInDepartmentWarehouse', 'POST', 'App\\Http\\Controllers\\WorkOrderController@receiveWorkItemInDepartmentWarehouse'],
            ['storeDepartmentReturnRequest', 'POST', 'App\\Http\\Controllers\\WarehouseItemController@storeDepartmentReturnRequest'],
            ['accept-department-return-request', 'GET', 'App\\Http\\Controllers\\WarehouseItemController@acceptDepartmentReturnRequest'],

            // Mill dispatch/partial receive and printing decision.
            ['storeStockForMillDispatch', 'POST', 'App\\Http\\Controllers\\JobMillWorkController@storeStockForMillDispatch'],
            ['store_mill_dispatch_received_item_in_warehouse', 'POST', 'App\\Http\\Controllers\\JobMillWorkController@storeMillDispatchReceivedItemInWarehouse'],
            ['decide-printing-position', 'POST', 'App\\Http\\Controllers\\WorkOrderController@decidePrinting'],

            // Purchase Order create, edit and print.
            ['add-purchaseorder', 'GET', 'App\\Http\\Controllers\\PurchaseOrderController@create'],
            ['store_purchaseorder', 'POST', 'App\\Http\\Controllers\\PurchaseOrderController@store'],
            ['edit-purchaseorder', 'GET', 'App\\Http\\Controllers\\PurchaseOrderController@edit'],
            ['update_purchaseorder', 'POST', 'App\\Http\\Controllers\\PurchaseOrderController@update'],
            ['print-purchaseorder', 'GET', 'App\\Http\\Controllers\\PurchaseOrderController@printPurchaseOrder'],
        ];

        foreach ($contracts as [$name, $verb, $action]) {
            $route = $this->routeByName($name);

            $this->assertContains($verb, $route->methods(), "{$name} no longer accepts {$verb}.");
            $this->assertSame($action, $route->getActionName(), "{$name} controller action changed.");
            $this->assertContains('auth:web', $route->gatherMiddleware(), "{$name} lost auth:web middleware.");
        }

        // This legacy endpoint is active but unnamed; identify it by its stable URI.
        $shiftRoute = $this->routeByUri('ajax_script/shiftWorkOrderToWarping');
        $this->assertContains('GET', $shiftRoute->methods());
        $this->assertSame(
            'App\\Http\\Controllers\\WorkOrderController@shiftWorkOrderToWarping',
            $shiftRoute->getActionName()
        );
        $this->assertContains('auth:web', $shiftRoute->gatherMiddleware());
    }

    public function test_admin_master_resources_have_guarded_crud_route_contracts(): void
    {
        $resources = [
            'states',
            'all-pages',
            'colours',
            'companies',
            'cotings',
            'couriers',
            'gst-rates',
            'item-types',
            'items',
            'item-yarn-requirements',
            'notifications',
            'packaging-types',
            'unit-types',
            'user-web-pages',
            'warehouses',
            'ware-house-compartments',
            'departments',
            'machines',
            'office-ips',
            'process-items',
            'individuals',
        ];

        foreach ($resources as $resource) {
            foreach (['index', 'create', 'store', 'edit', 'update', 'destroy'] as $operation) {
                $route = $this->routeByName("admin.{$resource}.{$operation}");
                $this->assertContains(
                    'auth:admin',
                    $route->gatherMiddleware(),
                    "admin.{$resource}.{$operation} lost auth:admin middleware."
                );
            }
        }
    }

    public function test_shared_lookup_routes_accept_either_authenticated_guard(): void
    {
        foreach (['list_customer', 'fabric_list_item', 'list_warehouse_item_type', 'list_individual'] as $name) {
            $route = $this->routeByName($name);
            $this->assertContains('auth:web,admin', $route->gatherMiddleware());
        }
    }

    private function routeByName(string $name): Route
    {
        $route = app('router')->getRoutes()->getByName($name);
        $this->assertNotNull($route, "Expected active route [{$name}] is missing.");

        return $route;
    }

    private function routeByUri(string $uri): Route
    {
        foreach (app('router')->getRoutes() as $route) {
            if ($route->uri() === $uri) {
                return $route;
            }
        }

        $this->fail("Expected active route URI [{$uri}] is missing.");
    }
}
