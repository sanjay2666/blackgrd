<?php

namespace App\Domain\OperationalStatus;

use App\Enums\GatePassStatus;
use App\Enums\InspectionResult;
use App\Enums\InventoryAllocationStatus;
use App\Enums\InventoryReceiptStatus;
use App\Enums\PurchaseOrderDocumentStatus;
use App\Enums\SaleOrderDocumentStatus;
use App\Enums\WorkOrderExecutionStatus;
use App\Enums\WorkRequirementStatus;

final class LegacyOperationalStatusMapper
{
    public function saleOrder(bool $hasWorkOrder, bool $productionComplete): SaleOrderDocumentStatus
    {
        return match (true) {
            $productionComplete => SaleOrderDocumentStatus::Completed,
            $hasWorkOrder => SaleOrderDocumentStatus::InProduction,
            default => SaleOrderDocumentStatus::Draft,
        };
    }

    public function purchaseReceipt(float $ordered, float $received): InventoryReceiptStatus
    {
        return match (true) {
            $received <= 0 => InventoryReceiptStatus::Pending,
            $ordered > 0 && $received >= $ordered => InventoryReceiptStatus::Received,
            default => InventoryReceiptStatus::PartiallyReceived,
        };
    }

    /** @param list<InventoryReceiptStatus> $lineStatuses */
    public function purchaseOrder(array $lineStatuses): PurchaseOrderDocumentStatus
    {
        if ($lineStatuses === []) {
            return PurchaseOrderDocumentStatus::Draft;
        }

        if (count(array_filter($lineStatuses, fn ($status) => $status === InventoryReceiptStatus::Received)) === count($lineStatuses)) {
            return PurchaseOrderDocumentStatus::Received;
        }

        if (count(array_filter($lineStatuses, fn ($status) => $status !== InventoryReceiptStatus::Pending)) > 0) {
            return PurchaseOrderDocumentStatus::PartiallyReceived;
        }

        return PurchaseOrderDocumentStatus::Draft;
    }

    public function workOrder(object $row, bool $hasRequirement): WorkOrderExecutionStatus
    {
        return match (true) {
            ($row->work_status ?? null) === 'Complete' => WorkOrderExecutionStatus::Completed,
            ! empty($row->process_started_date) => WorkOrderExecutionStatus::Started,
            ($row->is_item_received_from_warehouse ?? null) === 'Yes' => WorkOrderExecutionStatus::Ready,
            ($row->is_work_require_request_accepted ?? null) === 'Yes' => WorkOrderExecutionStatus::MaterialAllotted,
            $hasRequirement => WorkOrderExecutionStatus::MaterialRequested,
            default => WorkOrderExecutionStatus::Created,
        };
    }

    public function workRequirement(int $decision, float $required, float $allotted): WorkRequirementStatus
    {
        return match (true) {
            $decision === 2 => WorkRequirementStatus::Denied,
            $decision === 1 && $required > 0 && $allotted >= $required => WorkRequirementStatus::Accepted,
            $allotted > 0 && $allotted < $required => WorkRequirementStatus::PartiallyAllotted,
            $allotted > 0 => WorkRequirementStatus::Allotted,
            default => WorkRequirementStatus::Pending,
        };
    }

    public function allocation(float $required, float $allotted, int $decision = 0): InventoryAllocationStatus
    {
        return match (true) {
            $decision === 2 => InventoryAllocationStatus::Cancelled,
            $allotted <= 0 => InventoryAllocationStatus::Unallocated,
            $required > 0 && $allotted >= $required => InventoryAllocationStatus::Allocated,
            default => InventoryAllocationStatus::PartiallyAllocated,
        };
    }

    public function inspectionResult(?string $legacy): ?InspectionResult
    {
        return match (strtolower(trim((string) $legacy))) {
            'completed', 'complete' => InspectionResult::Completed,
            'passed', 'pass' => InspectionResult::Passed,
            'defective' => InspectionResult::Defective,
            'rejected' => InspectionResult::Rejected,
            'rework' => InspectionResult::Rework,
            'pending', '' => InspectionResult::Pending,
            default => null,
        };
    }

    public function gatePass(string $recordStatus, bool $received, ?string $number): ?GatePassStatus
    {
        if ($recordStatus === 'Deleted') {
            return null;
        }

        return match (true) {
            $received => GatePassStatus::Received,
            filled($number) => GatePassStatus::Issued,
            default => GatePassStatus::Draft,
        };
    }
}
