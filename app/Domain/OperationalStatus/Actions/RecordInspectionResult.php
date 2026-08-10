<?php

namespace App\Domain\OperationalStatus\Actions;

use App\Domain\OperationalStatus\OperationalStatusTransitionService;
use App\Enums\InspectionResult;
use App\Enums\InspectionStatus;
use App\Enums\WorkOrderExecutionStatus;
use App\Models\WorkInspection;
use Illuminate\Support\Facades\DB;

final class RecordInspectionResult
{
    public function __construct(
        private readonly OperationalStatusTransitionService $transitions,
        private readonly TransitionWorkOrder $workOrders,
    ) {}

    public function execute(WorkInspection $inspection, InspectionResult $result, string|int|null $actorId = null): WorkInspection
    {
        return DB::transaction(function () use ($inspection, $result, $actorId): WorkInspection {
            $this->transitions->transition(
                $inspection,
                'inspection_status',
                InspectionStatus::Completed,
                ['insp_status' => 'Complete'],
                actorId: $actorId,
            );
            $legacyResult = match ($result) {
                InspectionResult::Defective => 'Defective',
                InspectionResult::Rejected => 'Rejected',
                InspectionResult::Rework => 'Rework',
                default => 'Completed',
            };
            $this->transitions->transition(
                $inspection,
                'inspection_result',
                $result,
                ['insp_work_status' => $legacyResult],
                actorId: $actorId,
            );

            if ($inspection->WorkOrder !== null) {
                $execution = match ($result) {
                    InspectionResult::Passed, InspectionResult::PartiallyPassed => WorkOrderExecutionStatus::Passed,
                    InspectionResult::Rejected, InspectionResult::Defective => WorkOrderExecutionStatus::Rejected,
                    InspectionResult::Rework => WorkOrderExecutionStatus::Rework,
                    default => WorkOrderExecutionStatus::Completed,
                };
                $this->workOrders->execute($inspection->WorkOrder, $execution, actorId: $actorId);
            }

            return $inspection->refresh();
        });
    }
}
