<?php

namespace App\Domain\OperationalStatus;

use App\Enums\GatePassStatus;
use App\Enums\InventoryAllocationStatus;
use App\Enums\InventoryReceiptStatus;
use App\Enums\JobWorkStatus;
use App\Enums\WorkOrderExecutionStatus;
use App\Enums\WorkRequirementStatus;

final class LegacyOperationalStatusWriter
{
    /** @return array<string, mixed> */
    public function workOrder(WorkOrderExecutionStatus $status): array
    {
        return match ($status) {
            WorkOrderExecutionStatus::Completed,
            WorkOrderExecutionStatus::InspectionPending,
            WorkOrderExecutionStatus::Passed,
            WorkOrderExecutionStatus::Rejected,
            WorkOrderExecutionStatus::Rework => ['work_status' => 'Complete'],
            default => ['work_status' => 'Pending'],
        };
    }

    /** @return array<string, mixed> */
    public function workRequirement(WorkRequirementStatus $status, InventoryAllocationStatus $allocation): array
    {
        return [
            'is_accept' => match ($status) {
                WorkRequirementStatus::Accepted,
                WorkRequirementStatus::Allotted,
                WorkRequirementStatus::PartiallyAllotted => 1,
                WorkRequirementStatus::Denied => 2,
                default => 0,
            },
            'is_pro_acc_by_warehouse' => match ($status) {
                WorkRequirementStatus::Accepted,
                WorkRequirementStatus::Allotted,
                WorkRequirementStatus::PartiallyAllotted => 'Yes',
                WorkRequirementStatus::Denied => 'No',
                default => null,
            },
            'allocation_status' => $allocation,
        ];
    }

    /** @return array<string, mixed> */
    public function receipt(InventoryReceiptStatus $status): array
    {
        return ['is_item_received_in_warehouse' => $status === InventoryReceiptStatus::Received ? '1' : '0'];
    }

    /** @return array<string, mixed> */
    public function gatePass(GatePassStatus $status): array
    {
        return ['is_item_received_in_warehouse' => in_array($status, [GatePassStatus::Received, GatePassStatus::Closed], true) ? 'Yes' : 'No'];
    }

    /** @return array<string, mixed> */
    public function jobWork(JobWorkStatus $status): array
    {
        return ['is_tot_mtr_received' => in_array($status, [JobWorkStatus::Received, JobWorkStatus::InspectionPending, JobWorkStatus::Closed], true)];
    }
}
