<?php

namespace Tests\Unit\Status;

use App\Domain\OperationalStatus\OperationalStatusTransitionMap;
use App\Enums\GatePassStatus;
use App\Enums\InventoryMovementStatus;
use App\Enums\PurchaseOrderDocumentStatus;
use App\Enums\SaleOrderDocumentStatus;
use App\Enums\WorkOrderExecutionStatus;
use App\Enums\WorkRequirementStatus;
use PHPUnit\Framework\TestCase;

class OperationalStatusTransitionMapTest extends TestCase
{
    public function test_valid_forward_transitions_are_exposed(): void
    {
        $map = new OperationalStatusTransitionMap;

        $this->assertContains('in_production', $map->allowedTargets(SaleOrderDocumentStatus::Draft));
        $this->assertContains('received', $map->allowedTargets(PurchaseOrderDocumentStatus::PartiallyReceived));
        $this->assertContains('started', $map->allowedTargets(WorkOrderExecutionStatus::Ready));
        $this->assertContains('accepted', $map->allowedTargets(WorkRequirementStatus::Allotted));
        $this->assertContains('reversed', $map->allowedTargets(InventoryMovementStatus::Posted));
        $this->assertContains('received', $map->allowedTargets(GatePassStatus::PartiallyReceived));
    }

    public function test_completed_cancelled_and_closed_states_cannot_reopen_directly(): void
    {
        $map = new OperationalStatusTransitionMap;

        $this->assertSame([], $map->allowedTargets(SaleOrderDocumentStatus::Completed));
        $this->assertSame([], $map->allowedTargets(SaleOrderDocumentStatus::Cancelled));
        $this->assertSame([], $map->allowedTargets(PurchaseOrderDocumentStatus::Closed));
        $this->assertSame([], $map->allowedTargets(WorkOrderExecutionStatus::Cancelled));
        $this->assertSame([], $map->allowedTargets(GatePassStatus::Closed));
    }
}
