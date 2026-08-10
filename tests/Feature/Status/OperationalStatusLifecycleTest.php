<?php

namespace Tests\Feature\Status;

use App\Domain\OperationalStatus\Actions\RecordInspectionResult;
use App\Domain\OperationalStatus\Actions\TransitionGatePass;
use App\Domain\OperationalStatus\Actions\TransitionInventoryMovement;
use App\Domain\OperationalStatus\Actions\TransitionJobWork;
use App\Domain\OperationalStatus\Actions\TransitionPurchaseOrder;
use App\Domain\OperationalStatus\Actions\TransitionSaleOrder;
use App\Domain\OperationalStatus\Actions\TransitionWorkOrder;
use App\Domain\OperationalStatus\Actions\TransitionWorkRequirement;
use App\Enums\GatePassStatus;
use App\Enums\InspectionResult;
use App\Enums\InspectionStatus;
use App\Enums\InventoryAllocationStatus;
use App\Enums\InventoryMovementStatus;
use App\Enums\JobWorkStatus;
use App\Enums\PurchaseOrderDocumentStatus;
use App\Enums\SaleOrderDocumentStatus;
use App\Enums\WorkOrderExecutionStatus;
use App\Enums\WorkRequirementStatus;
use App\Exceptions\InvalidOperationalStatusTransition;
use App\Models\GatePass;
use App\Models\PurchaseOrder;
use App\Models\SaleOrder;
use App\Models\StockMillDispatch;
use App\Models\WarehouseItem;
use App\Models\WorkInspection;
use App\Models\WorkOrder;
use App\Models\WorkProcessRequirement;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class OperationalStatusLifecycleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('sale_orders', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('status')->default('Active');
            $table->string('document_status')->nullable();
        });
        Schema::create('purchase_orders', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('status')->default('Active');
            $table->string('document_status')->nullable();
            $table->string('is_all_item_received')->default('No');
            $table->string('is_item_received_in_warehouse')->default('No');
        });
        Schema::create('work_orders', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('status')->default('Active');
            $table->string('execution_status')->nullable();
            $table->string('inspection_status')->nullable();
            $table->string('work_status')->default('Pending');
            $table->string('insp_status')->default('Pending');
        });
        Schema::create('work_process_requirements', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('status')->default('Active');
            $table->string('requirement_status')->nullable();
            $table->string('allocation_status')->nullable();
            $table->integer('is_accept')->default(0);
            $table->string('is_pro_acc_by_warehouse')->nullable();
            $table->decimal('quantity', 10, 2)->default(0);
            $table->decimal('alloted_quantity', 10, 2)->default(0);
            $table->text('alloted_remark')->nullable();
        });
        Schema::create('work_inspections', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('work_order_id')->nullable();
            $table->string('status')->default('Active');
            $table->string('inspection_status')->nullable();
            $table->string('inspection_result')->nullable();
            $table->string('insp_status')->nullable();
            $table->string('insp_work_status')->nullable();
        });
        Schema::create('warehouse_in_items', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('status')->default('Active');
            $table->string('movement_status')->nullable();
        });
        Schema::create('gate_passes', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('status')->default('Active');
            $table->string('gate_pass_status')->nullable();
            $table->string('is_item_received_in_warehouse')->default('No');
        });
        Schema::create('stock_mill_dispatches', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('status')->default('Active');
            $table->string('job_work_status')->nullable();
            $table->boolean('is_tot_mtr_received')->default(false);
        });
    }

    public function test_sale_and_purchase_document_transitions_are_controlled(): void
    {
        $saleOrder = SaleOrder::create(['document_status' => SaleOrderDocumentStatus::Draft]);
        app(TransitionSaleOrder::class)->execute($saleOrder, SaleOrderDocumentStatus::InProduction);
        $this->assertSame(SaleOrderDocumentStatus::InProduction, $saleOrder->refresh()->document_status);

        $purchaseOrder = PurchaseOrder::create(['document_status' => PurchaseOrderDocumentStatus::Approved]);
        app(TransitionPurchaseOrder::class)->execute($purchaseOrder, PurchaseOrderDocumentStatus::PartiallyReceived);
        $this->assertSame('Yes', $purchaseOrder->refresh()->is_item_received_in_warehouse);
        app(TransitionPurchaseOrder::class)->execute($purchaseOrder, PurchaseOrderDocumentStatus::Received);
        $this->assertSame('Yes', $purchaseOrder->refresh()->is_all_item_received);

        $saleOrder->document_status = SaleOrderDocumentStatus::Completed;
        $saleOrder->save();
        $this->expectException(InvalidOperationalStatusTransition::class);
        app(TransitionSaleOrder::class)->execute($saleOrder, SaleOrderDocumentStatus::Draft);
    }

    public function test_work_order_execution_and_inspection_result_stay_separate_but_coordinated(): void
    {
        $workOrder = WorkOrder::create([
            'execution_status' => WorkOrderExecutionStatus::Created,
            'inspection_status' => InspectionStatus::Pending,
        ]);
        $action = app(TransitionWorkOrder::class);
        foreach ([
            WorkOrderExecutionStatus::MaterialRequested,
            WorkOrderExecutionStatus::MaterialAllotted,
            WorkOrderExecutionStatus::Ready,
            WorkOrderExecutionStatus::Started,
            WorkOrderExecutionStatus::Completed,
        ] as $status) {
            $action->execute($workOrder, $status);
        }

        $inspection = WorkInspection::create([
            'work_order_id' => $workOrder->id,
            'inspection_status' => InspectionStatus::Pending,
            'inspection_result' => InspectionResult::Pending,
        ]);
        app(RecordInspectionResult::class)->execute($inspection, InspectionResult::Passed);

        $this->assertSame(InspectionResult::Passed, $inspection->refresh()->inspection_result);
        $this->assertSame(InspectionStatus::Completed, $inspection->inspection_status);
        $this->assertSame(WorkOrderExecutionStatus::Passed, $workOrder->refresh()->execution_status);
    }

    public function test_requirement_partial_allotment_and_acceptance_are_idempotently_synchronized(): void
    {
        $requirement = WorkProcessRequirement::create([
            'requirement_status' => WorkRequirementStatus::Pending,
            'allocation_status' => InventoryAllocationStatus::Unallocated,
            'quantity' => 100,
            'alloted_quantity' => 40,
            'is_accept' => 1,
        ]);
        $action = app(TransitionWorkRequirement::class);
        $action->synchronize($requirement);
        $this->assertSame(WorkRequirementStatus::PartiallyAllotted, $requirement->refresh()->requirement_status);
        $this->assertSame(InventoryAllocationStatus::PartiallyAllocated, $requirement->allocation_status);
        $this->assertSame(1, $requirement->is_accept);
        $this->assertSame('Yes', $requirement->is_pro_acc_by_warehouse);

        $action->synchronize($requirement);
        $this->assertSame(WorkRequirementStatus::PartiallyAllotted, $requirement->refresh()->requirement_status);

        $requirement->alloted_quantity = 100;
        $requirement->save();
        $action->synchronize($requirement);
        $action->synchronize($requirement);
        $this->assertSame(WorkRequirementStatus::Accepted, $requirement->refresh()->requirement_status);
        $this->assertSame(InventoryAllocationStatus::Allocated, $requirement->allocation_status);
    }

    public function test_inventory_gate_pass_and_job_work_transitions_cover_reversal_and_partial_receipt(): void
    {
        $movement = WarehouseItem::create(['movement_status' => InventoryMovementStatus::Draft]);
        $inventory = app(TransitionInventoryMovement::class);
        $inventory->execute($movement, InventoryMovementStatus::Posted);
        $inventory->execute($movement, InventoryMovementStatus::Reversed);
        $this->assertSame(InventoryMovementStatus::Reversed, $movement->refresh()->movement_status);

        $gatePass = GatePass::create(['gate_pass_status' => GatePassStatus::Issued]);
        $gatePassAction = app(TransitionGatePass::class);
        $gatePassAction->execute($gatePass, GatePassStatus::PartiallyReceived);
        $gatePassAction->execute($gatePass, GatePassStatus::Received);
        $this->assertSame('Yes', $gatePass->refresh()->is_item_received_in_warehouse);

        $dispatch = StockMillDispatch::create(['job_work_status' => JobWorkStatus::Dispatched]);
        $jobWork = app(TransitionJobWork::class);
        $jobWork->execute($dispatch, JobWorkStatus::PartiallyReceived);
        $jobWork->execute($dispatch, JobWorkStatus::Received);
        $this->assertTrue((bool) $dispatch->refresh()->is_tot_mtr_received);
    }
}
