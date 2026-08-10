<?php

namespace App\Domain\OperationalStatus\Actions;

use App\Domain\OperationalStatus\LegacyOperationalStatusWriter;
use App\Domain\OperationalStatus\OperationalStatusTransitionService;
use App\Enums\GatePassStatus;
use App\Models\GatePass;

final class TransitionGatePass
{
    public function __construct(
        private readonly OperationalStatusTransitionService $transitions,
        private readonly LegacyOperationalStatusWriter $legacy,
    ) {}

    public function execute(GatePass $gatePass, GatePassStatus $to, bool $force = false): GatePass
    {
        return $this->transitions->transition(
            $gatePass,
            'gate_pass_status',
            $to,
            $this->legacy->gatePass($to),
            force: $force,
        );
    }
}
