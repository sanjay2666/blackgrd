<?php

namespace Tests\Unit\Status;

use App\Domain\OperationalStatus\LegacyOperationalStatusMapper;
use App\Enums\GatePassStatus;
use App\Enums\InspectionResult;
use App\Enums\InventoryAllocationStatus;
use App\Enums\InventoryReceiptStatus;
use App\Enums\PurchaseOrderDocumentStatus;
use App\Enums\SaleOrderDocumentStatus;
use App\Enums\WorkOrderExecutionStatus;
use App\Enums\WorkRequirementStatus;
use PHPUnit\Framework\TestCase;

class LegacyOperationalStatusMapperTest extends TestCase
{
    private LegacyOperationalStatusMapper $mapper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mapper = new LegacyOperationalStatusMapper;
    }

    public function test_sale_and_purchase_quantity_mappings_are_deterministic(): void
    {
        $this->assertSame(SaleOrderDocumentStatus::Draft, $this->mapper->saleOrder(false, false));
        $this->assertSame(SaleOrderDocumentStatus::InProduction, $this->mapper->saleOrder(true, false));
        $this->assertSame(SaleOrderDocumentStatus::Completed, $this->mapper->saleOrder(true, true));

        $pending = $this->mapper->purchaseReceipt(100, 0);
        $partial = $this->mapper->purchaseReceipt(100, 40);
        $received = $this->mapper->purchaseReceipt(100, 100);
        $this->assertSame(InventoryReceiptStatus::Pending, $pending);
        $this->assertSame(InventoryReceiptStatus::PartiallyReceived, $partial);
        $this->assertSame(InventoryReceiptStatus::Received, $received);
        $this->assertSame(PurchaseOrderDocumentStatus::PartiallyReceived, $this->mapper->purchaseOrder([$received, $pending]));
        $this->assertSame(PurchaseOrderDocumentStatus::Received, $this->mapper->purchaseOrder([$received]));
    }

    public function test_work_order_and_requirement_legacy_flags_keep_domains_separate(): void
    {
        $this->assertSame(WorkOrderExecutionStatus::Created, $this->mapper->workOrder((object) [], false));
        $this->assertSame(WorkOrderExecutionStatus::MaterialRequested, $this->mapper->workOrder((object) [], true));
        $this->assertSame(WorkOrderExecutionStatus::Ready, $this->mapper->workOrder((object) ['is_item_received_from_warehouse' => 'Yes'], true));
        $this->assertSame(WorkOrderExecutionStatus::Started, $this->mapper->workOrder((object) ['process_started_date' => '2026-08-03'], true));
        $this->assertSame(WorkOrderExecutionStatus::Completed, $this->mapper->workOrder((object) ['work_status' => 'Complete'], true));

        $this->assertSame(WorkRequirementStatus::Pending, $this->mapper->workRequirement(0, 100, 0));
        $this->assertSame(WorkRequirementStatus::PartiallyAllotted, $this->mapper->workRequirement(1, 100, 40));
        $this->assertSame(WorkRequirementStatus::Accepted, $this->mapper->workRequirement(1, 100, 100));
        $this->assertSame(WorkRequirementStatus::Denied, $this->mapper->workRequirement(2, 100, 0));
        $this->assertSame(InventoryAllocationStatus::PartiallyAllocated, $this->mapper->allocation(100, 40));
    }

    public function test_ambiguous_values_are_not_silently_normalized(): void
    {
        $this->assertNull($this->mapper->inspectionResult('mystery'));
        $this->assertNull($this->mapper->gatePass('Deleted', false, 'GP-1'));
        $this->assertSame(InspectionResult::Completed, $this->mapper->inspectionResult('Complete'));
        $this->assertSame(InspectionResult::Completed, $this->mapper->inspectionResult('Completed'));
        $this->assertSame(GatePassStatus::Issued, $this->mapper->gatePass('Active', false, 'GP-1'));
    }
}
