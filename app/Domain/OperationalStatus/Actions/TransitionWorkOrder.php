<?php

namespace App\Domain\OperationalStatus\Actions;

use App\Domain\OperationalStatus\LegacyOperationalStatusWriter;
use App\Domain\OperationalStatus\OperationalStatusTransitionService;
use App\Enums\WorkOrderExecutionStatus;
use App\Models\WorkOrder;

final class TransitionWorkOrder
{
    public function __construct(
        private readonly OperationalStatusTransitionService $transitions,
        private readonly LegacyOperationalStatusWriter $legacy,
    ) {}

    public function execute(
        WorkOrder $workOrder,
        WorkOrderExecutionStatus $to,
        ?string $reason = null,
        string|int|null $actorId = null,
        bool $force = false,
    ): WorkOrder {
        return $this->transitions->transition(
            $workOrder,
            'execution_status',
            $to,
            $this->legacy->workOrder($to),
            $reason,
            $actorId,
            $force,
        );
    }
}
