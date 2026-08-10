<?php

namespace App\Domain\OperationalStatus;

use App\Enums\GatePassStatus;
use App\Enums\InspectionResult;
use App\Enums\InspectionStatus;
use App\Enums\InventoryAllocationStatus;
use App\Enums\InventoryMovementStatus;
use App\Enums\InventoryReceiptStatus;
use App\Enums\JobWorkStatus;
use App\Enums\PurchaseOrderDocumentStatus;
use App\Enums\SaleOrderDocumentStatus;
use App\Enums\WorkOrderExecutionStatus;
use App\Enums\WorkRequirementStatus;
use BackedEnum;
use InvalidArgumentException;

final class OperationalStatusTransitionMap
{
    /** @return list<string> */
    public function allowedTargets(BackedEnum $status): array
    {
        return match ($status::class) {
            SaleOrderDocumentStatus::class => $this->saleOrder($status),
            PurchaseOrderDocumentStatus::class => $this->purchaseOrder($status),
            WorkOrderExecutionStatus::class => $this->workOrder($status),
            WorkRequirementStatus::class => $this->workRequirement($status),
            InspectionStatus::class => $this->inspectionStatus($status),
            InspectionResult::class => $this->inspectionResult($status),
            InventoryMovementStatus::class => $this->inventoryMovement($status),
            InventoryAllocationStatus::class => $this->allocation($status),
            InventoryReceiptStatus::class => $this->receipt($status),
            GatePassStatus::class => $this->gatePass($status),
            JobWorkStatus::class => $this->jobWork($status),
            default => throw new InvalidArgumentException('Unsupported operational status enum ['.$status::class.'].')
        };
    }

    /** @return list<string> */
    private function saleOrder(SaleOrderDocumentStatus $status): array
    {
        return match ($status) {
            SaleOrderDocumentStatus::Draft => ['pending_approval', 'approved', 'in_production', 'cancelled'],
            SaleOrderDocumentStatus::PendingApproval => ['approved', 'rejected', 'cancelled'],
            SaleOrderDocumentStatus::Approved => ['in_production', 'on_hold', 'cancelled'],
            SaleOrderDocumentStatus::InProduction => ['partially_dispatched', 'completed', 'on_hold', 'cancelled'],
            SaleOrderDocumentStatus::PartiallyDispatched => ['completed', 'on_hold', 'cancelled'],
            SaleOrderDocumentStatus::OnHold => ['approved', 'in_production', 'partially_dispatched', 'cancelled'],
            SaleOrderDocumentStatus::Completed,
            SaleOrderDocumentStatus::Rejected,
            SaleOrderDocumentStatus::Cancelled => [],
        };
    }

    /** @return list<string> */
    private function purchaseOrder(PurchaseOrderDocumentStatus $status): array
    {
        return match ($status) {
            PurchaseOrderDocumentStatus::Draft => ['pending_approval', 'approved', 'cancelled'],
            PurchaseOrderDocumentStatus::PendingApproval => ['approved', 'cancelled'],
            PurchaseOrderDocumentStatus::Approved => ['partially_received', 'received', 'on_hold', 'cancelled'],
            PurchaseOrderDocumentStatus::PartiallyReceived => ['received', 'on_hold', 'cancelled'],
            PurchaseOrderDocumentStatus::Received => ['closed'],
            PurchaseOrderDocumentStatus::OnHold => ['approved', 'partially_received', 'cancelled'],
            PurchaseOrderDocumentStatus::Closed,
            PurchaseOrderDocumentStatus::Cancelled => [],
        };
    }

    /** @return list<string> */
    private function workOrder(WorkOrderExecutionStatus $status): array
    {
        return match ($status) {
            WorkOrderExecutionStatus::Created => ['material_requested', 'on_hold', 'cancelled'],
            WorkOrderExecutionStatus::MaterialRequested => ['material_allotted', 'on_hold', 'cancelled'],
            WorkOrderExecutionStatus::MaterialAllotted => ['ready', 'on_hold', 'cancelled'],
            WorkOrderExecutionStatus::Ready => ['started', 'on_hold', 'cancelled'],
            WorkOrderExecutionStatus::Started => ['partially_completed', 'completed', 'inspection_pending', 'on_hold', 'cancelled'],
            WorkOrderExecutionStatus::PartiallyCompleted => ['completed', 'inspection_pending', 'on_hold', 'cancelled'],
            WorkOrderExecutionStatus::Completed => ['inspection_pending', 'passed', 'rejected', 'rework'],
            WorkOrderExecutionStatus::InspectionPending => ['passed', 'rejected', 'rework'],
            WorkOrderExecutionStatus::Rejected => ['rework'],
            WorkOrderExecutionStatus::Rework => ['ready', 'started', 'cancelled'],
            WorkOrderExecutionStatus::OnHold => ['material_requested', 'material_allotted', 'ready', 'started', 'cancelled'],
            WorkOrderExecutionStatus::Passed,
            WorkOrderExecutionStatus::Cancelled => [],
        };
    }

    /** @return list<string> */
    private function workRequirement(WorkRequirementStatus $status): array
    {
        return match ($status) {
            WorkRequirementStatus::Created => ['sent_to_warehouse', 'cancelled'],
            WorkRequirementStatus::SentToWarehouse => ['pending', 'partially_allotted', 'allotted', 'denied', 'cancelled'],
            WorkRequirementStatus::Pending => ['partially_allotted', 'allotted', 'accepted', 'denied', 'cancelled'],
            WorkRequirementStatus::PartiallyAllotted => ['allotted', 'accepted', 'denied', 'cancelled'],
            WorkRequirementStatus::Allotted => ['accepted', 'denied', 'closed', 'cancelled'],
            WorkRequirementStatus::Accepted => ['closed', 'cancelled'],
            WorkRequirementStatus::Denied => ['pending', 'cancelled'],
            WorkRequirementStatus::Cancelled,
            WorkRequirementStatus::Closed => [],
        };
    }

    /** @return list<string> */
    private function inspectionStatus(InspectionStatus $status): array
    {
        return match ($status) {
            InspectionStatus::Pending => ['completed', 'cancelled'],
            InspectionStatus::Completed,
            InspectionStatus::Cancelled => [],
        };
    }

    /** @return list<string> */
    private function inspectionResult(InspectionResult $status): array
    {
        return match ($status) {
            InspectionResult::Pending => ['passed', 'partially_passed', 'rejected', 'defective', 'rework', 'completed'],
            InspectionResult::PartiallyPassed,
            InspectionResult::Defective,
            InspectionResult::Rejected => ['rework'],
            InspectionResult::Rework => ['passed', 'partially_passed', 'rejected', 'defective', 'completed'],
            InspectionResult::Passed,
            InspectionResult::Completed => [],
        };
    }

    /** @return list<string> */
    private function inventoryMovement(InventoryMovementStatus $status): array
    {
        return match ($status) {
            InventoryMovementStatus::Draft => ['posted', 'partially_posted', 'cancelled'],
            InventoryMovementStatus::PartiallyPosted => ['posted', 'reversed', 'cancelled'],
            InventoryMovementStatus::Posted => ['reversed'],
            InventoryMovementStatus::Reversed,
            InventoryMovementStatus::Cancelled => [],
        };
    }

    /** @return list<string> */
    private function allocation(InventoryAllocationStatus $status): array
    {
        return match ($status) {
            InventoryAllocationStatus::Unallocated => ['partially_allocated', 'allocated', 'cancelled'],
            InventoryAllocationStatus::PartiallyAllocated => ['allocated', 'released', 'cancelled'],
            InventoryAllocationStatus::Allocated => ['released', 'cancelled'],
            InventoryAllocationStatus::Released => ['unallocated'],
            InventoryAllocationStatus::Cancelled => [],
        };
    }

    /** @return list<string> */
    private function receipt(InventoryReceiptStatus $status): array
    {
        return match ($status) {
            InventoryReceiptStatus::Pending => ['partially_received', 'received', 'rejected'],
            InventoryReceiptStatus::PartiallyReceived => ['received', 'rejected'],
            InventoryReceiptStatus::Received => ['closed'],
            InventoryReceiptStatus::Rejected,
            InventoryReceiptStatus::Closed => [],
        };
    }

    /** @return list<string> */
    private function gatePass(GatePassStatus $status): array
    {
        return match ($status) {
            GatePassStatus::Draft => ['issued', 'cancelled'],
            GatePassStatus::Issued => ['partially_received', 'received', 'cancelled'],
            GatePassStatus::PartiallyReceived => ['received', 'cancelled'],
            GatePassStatus::Received => ['closed'],
            GatePassStatus::Cancelled,
            GatePassStatus::Closed => [],
        };
    }

    /** @return list<string> */
    private function jobWork(JobWorkStatus $status): array
    {
        return match ($status) {
            JobWorkStatus::RequirementRaised => ['vendor_selected', 'cancelled'],
            JobWorkStatus::VendorSelected => ['approved', 'cancelled'],
            JobWorkStatus::Approved => ['dispatched', 'cancelled'],
            JobWorkStatus::Dispatched => ['partially_received', 'received', 'shortage_pending', 'cancelled'],
            JobWorkStatus::PartiallyReceived => ['received', 'shortage_pending', 'cancelled'],
            JobWorkStatus::Received => ['inspection_pending', 'closed'],
            JobWorkStatus::InspectionPending => ['rework', 'closed'],
            JobWorkStatus::ShortagePending => ['partially_received', 'received', 'closed'],
            JobWorkStatus::Rework => ['dispatched', 'partially_received', 'received'],
            JobWorkStatus::Closed,
            JobWorkStatus::Cancelled => [],
        };
    }
}
