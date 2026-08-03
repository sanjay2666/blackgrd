<?php

namespace Tests\Feature\Database;

use App\Models\SaleOrderItem;
use App\Models\WarehouseOutItem;
use App\Models\WorkProcessRequirement;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ModelRelationshipIntegrationTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::connection()->getDriverName() !== 'mysql') {
            $this->markTestSkipped('Model relationship integration tests require disposable MySQL.');
        }

        $database = DB::connection()->getDatabaseName();
        if ($database !== 'blackgrd_schema_testing') {
            $this->fail("Refusing model relationship integration tests on database [{$database}].");
        }
    }

    public function test_cwo_reason_scope_and_sale_order_work_item_relation(): void
    {
        $saleOrderItemId = 900000001;
        $workOrderId = 900000002;

        DB::table('reasons')->insert([
            [
                'reason_from_page' => 'cwo',
                'sale_order_item_id' => $saleOrderItemId,
                'reason' => 'Current CWO reason',
                'created_at' => now(),
                'status' => 'Active',
            ],
            [
                'reason_from_page' => 'soi',
                'sale_order_item_id' => $saleOrderItemId,
                'reason' => 'Different reason type',
                'created_at' => now(),
                'status' => 'Active',
            ],
            [
                'reason_from_page' => 'cwo',
                'sale_order_item_id' => $saleOrderItemId,
                'reason' => 'Deleted CWO reason',
                'created_at' => now(),
                'status' => 'Deleted',
            ],
        ]);

        $activeWorkItemId = $this->insertWorkOrderItem($workOrderId, $saleOrderItemId, 'Active');
        $this->insertWorkOrderItem($workOrderId, $saleOrderItemId, 'Deleted');

        $saleOrderItem = $this->modelWithKey(new SaleOrderItem(), $saleOrderItemId);

        $this->assertSame(['Current CWO reason'], $saleOrderItem->CwoReason->pluck('reason')->all());
        $this->assertSame([$activeWorkItemId], $saleOrderItem->WorkOrderItem->pluck('id')->all());
    }

    public function test_work_process_requirement_and_warehouse_individual_relations_are_nullable_safe(): void
    {
        $workOrderId = 900000003;
        $activeWorkItemId = $this->insertWorkOrderItem($workOrderId, null, 'Active');

        $requirement = new WorkProcessRequirement(['work_order_id' => $workOrderId]);
        $this->assertSame($activeWorkItemId, $requirement->WorkOrderItem?->id);
        $this->assertNull((new WorkProcessRequirement())->WorkOrderItem);

        $individualId = DB::table('individuals')->insertGetId([
            'name' => 'Relationship integration individual',
            'type' => 'employee',
            'status' => 'Active',
        ]);
        $outItem = new WarehouseOutItem(['individual_id' => $individualId]);

        $this->assertSame($individualId, $outItem->Individual?->id);
        $this->assertNull((new WarehouseOutItem())->Individual);
    }

    private function insertWorkOrderItem(int $workOrderId, ?int $saleOrderItemId, string $status): int
    {
        return DB::table('work_order_items')->insertGetId([
            'work_order_id' => $workOrderId,
            'sale_order_item_id' => $saleOrderItemId,
            'pcs' => 0,
            'cut' => 0,
            'meter' => 0,
            'order_item_priority' => 'Normal',
            'status' => $status,
        ]);
    }

    private function modelWithKey(SaleOrderItem $model, int $id): SaleOrderItem
    {
        $model->forceFill(['id' => $id]);
        $model->exists = true;

        return $model;
    }
}
