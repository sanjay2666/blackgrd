<?php

namespace Tests\Unit\Regression;

use App\Models\Individual;
use App\Models\PurchaseOrder;
use App\Models\SaleOrder;
use App\Models\SaleOrderItem;
use App\Models\WarehouseItemStock;
use App\Models\WorkOrder;
use App\Models\WorkProcessRequirement;
use Illuminate\Database\Eloquent\Relations\Relation;
use Tests\TestCase;

class ModelRelationshipBaselineTest extends TestCase
{
    public function test_key_current_relationship_contracts(): void
    {
        $contracts = [
            [new SaleOrder(), 'customer', Individual::class, 'customer_id'],
            [new SaleOrder(), 'saleOrderItems', SaleOrderItem::class, 'sale_order_id'],
            [new SaleOrderItem(), 'saleOrder', SaleOrder::class, 'sale_order_id'],
            [new WorkOrder(), 'WorkOrderItem', \App\Models\WorkOrderItem::class, 'work_order_id'],
            [new WorkOrder(), 'WorkProcessRequirement', WorkProcessRequirement::class, 'work_order_id'],
            [new WorkProcessRequirement(), 'WorkOrder', WorkOrder::class, 'work_order_id'],
            [new WarehouseItemStock(), 'Warehouse', \App\Models\Warehouse::class, 'warehouse_id'],
            [new WarehouseItemStock(), 'WarehouseCompartment', \App\Models\WarehouseCompartment::class, 'ware_comp_id'],
            [new PurchaseOrder(), 'vendor', Individual::class, 'vendor_id'],
        ];

        foreach ($contracts as [$model, $method, $relatedClass, $foreignKey]) {
            $relation = $model->{$method}();

            $this->assertInstanceOf(Relation::class, $relation, get_class($model)."::{$method} is not a relation.");
            $this->assertSame($relatedClass, get_class($relation->getRelated()));
            $this->assertStringEndsWith(".{$foreignKey}", $relation->getQualifiedForeignKeyName());
        }
    }
}
