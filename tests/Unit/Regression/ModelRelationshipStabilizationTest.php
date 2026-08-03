<?php

namespace Tests\Unit\Regression;

use App\Models\Individual;
use App\Models\Reason;
use App\Models\SaleOrderItem;
use App\Models\WarehouseBalanceItem;
use App\Models\WarehouseOutItem;
use App\Models\WorkOrder;
use App\Models\WorkOrderItem;
use App\Models\WorkProcessRequirement;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Tests\TestCase;

class ModelRelationshipStabilizationTest extends TestCase
{
    public function test_cwo_reasons_use_the_current_reasons_table_contract(): void
    {
        $reason = new Reason();
        $relation = (new SaleOrderItem())->CwoReason();

        $this->assertSame('reasons', $reason->getTable());
        $this->assertSame('id', $reason->getKeyName());
        $this->assertTrue($reason->getIncrementing());
        $this->assertSame('int', $reason->getKeyType());
        $this->assertFalse($reason->usesTimestamps());
        $this->assertInstanceOf(HasMany::class, $relation);
        $this->assertSame(Reason::class, get_class($relation->getRelated()));
        $this->assertSame('sale_order_item_id', $relation->getForeignKeyName());
        $this->assertSame('id', $relation->getLocalKeyName());
        $this->assertContains('cwo', $relation->getQuery()->getBindings());
        $this->assertContains('Active', $relation->getQuery()->getBindings());
    }

    public function test_sale_order_item_to_work_order_items_uses_current_keys(): void
    {
        $relation = (new SaleOrderItem())->WorkOrderItem();

        $this->assertInstanceOf(HasMany::class, $relation);
        $this->assertSame(WorkOrderItem::class, get_class($relation->getRelated()));
        $this->assertSame('sale_order_item_id', $relation->getForeignKeyName());
        $this->assertSame('id', $relation->getLocalKeyName());
        $this->assertContains('Active', $relation->getQuery()->getBindings());
    }

    public function test_work_process_requirement_uses_work_order_id_for_its_single_item_contract(): void
    {
        $relation = (new WorkProcessRequirement())->WorkOrderItem();

        $this->assertInstanceOf(HasOne::class, $relation);
        $this->assertSame(WorkOrderItem::class, get_class($relation->getRelated()));
        $this->assertSame('work_order_id', $relation->getForeignKeyName());
        $this->assertSame('work_order_id', $relation->getLocalKeyName());
        $this->assertContains('Active', $relation->getQuery()->getBindings());
    }

    public function test_warehouse_individual_relationships_use_existing_columns_and_direction(): void
    {
        $outRelation = (new WarehouseOutItem())->Individual();
        $balanceRelation = (new WarehouseBalanceItem())->ReceiverIndividual();

        $this->assertInstanceOf(BelongsTo::class, $outRelation);
        $this->assertSame(Individual::class, get_class($outRelation->getRelated()));
        $this->assertSame('individual_id', $outRelation->getForeignKeyName());
        $this->assertSame('id', $outRelation->getOwnerKeyName());

        $this->assertInstanceOf(BelongsTo::class, $balanceRelation);
        $this->assertSame(Individual::class, get_class($balanceRelation->getRelated()));
        $this->assertSame('receiver_id', $balanceRelation->getForeignKeyName());
        $this->assertSame('id', $balanceRelation->getOwnerKeyName());
    }

    public function test_absent_table_and_column_relationships_are_not_declared(): void
    {
        $this->assertFalse(method_exists(WorkOrder::class, 'saleOrderItem'));
        $this->assertFalse(method_exists(WorkOrder::class, 'WorkOrderItemDetail'));
        $this->assertFalse(method_exists(WarehouseBalanceItem::class, 'Individual'));
        $this->assertFalse(class_exists('App\\Models\\SaleOrderItemPendingReason'));
        $this->assertFalse(class_exists('App\\Models\\WorkOrderItemDetail'));
        $this->assertFalse(class_exists('App\\Models\\WorkProcessRequirementChangeHistory'));

        $this->assertStringNotContainsString(
            'work_order_item_id',
            file_get_contents(app_path('Models/WorkProcessRequirement.php')),
        );
        $this->assertStringNotContainsString(
            'sale_order_item_id',
            file_get_contents(app_path('Models/WorkOrder.php')),
        );
        $this->assertStringNotContainsString(
            'ind_emp_id',
            file_get_contents(app_path('Models/WarehouseOutItem.php')),
        );
        $this->assertStringNotContainsString(
            'ind_emp_id',
            file_get_contents(app_path('Models/WarehouseBalanceItem.php')),
        );
        $this->assertStringNotContainsString(
            'WorkProcessRequirementChangeHistory',
            file_get_contents(resource_path('views/frontend/workorder/show-workorders.blade.php')),
        );
    }
}
