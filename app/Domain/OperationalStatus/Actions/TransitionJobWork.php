<?php

namespace App\Domain\OperationalStatus\Actions;

use App\Domain\OperationalStatus\LegacyOperationalStatusWriter;
use App\Domain\OperationalStatus\OperationalStatusTransitionService;
use App\Enums\JobWorkStatus;
use App\Models\StockMillDispatch;

final class TransitionJobWork
{
    public function __construct(
        private readonly OperationalStatusTransitionService $transitions,
        private readonly LegacyOperationalStatusWriter $legacy,
    ) {}

    public function execute(StockMillDispatch $dispatch, JobWorkStatus $to, bool $force = false): StockMillDispatch
    {
        return $this->transitions->transition(
            $dispatch,
            'job_work_status',
            $to,
            $this->legacy->jobWork($to),
            force: $force,
        );
    }
}
