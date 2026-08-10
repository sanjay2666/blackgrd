<?php

namespace App\Domain\OperationalStatus\Actions;

use App\Domain\OperationalStatus\LegacyOperationalStatusMapper;
use App\Domain\OperationalStatus\LegacyOperationalStatusWriter;
use App\Domain\OperationalStatus\OperationalStatusTransitionService;
use App\Enums\WorkRequirementStatus;
use App\Models\WorkProcessRequirement;

final class TransitionWorkRequirement
{
    public function __construct(
        private readonly OperationalStatusTransitionService $transitions,
        private readonly LegacyOperationalStatusMapper $mapper,
        private readonly LegacyOperationalStatusWriter $legacy,
    ) {}

    public function synchronize(WorkProcessRequirement|int $requirement, bool $force = false): WorkProcessRequirement
    {
        $model = $requirement instanceof WorkProcessRequirement
            ? $requirement
            : WorkProcessRequirement::query()->findOrFail($requirement);
        $decision = (int) $model->getRawOriginal('is_accept');
        $required = (float) $model->quantity;
        $allotted = (float) $model->alloted_quantity;
        $status = $this->mapper->workRequirement($decision, $required, $allotted);
        $allocation = $this->mapper->allocation($required, $allotted, $decision);

        return $this->transitions->transition(
            $model,
            'requirement_status',
            $status,
            $this->legacy->workRequirement($status, $allocation),
            force: $force,
        );
    }

    public function deny(WorkProcessRequirement $requirement, ?string $reason, string|int|null $actorId): WorkProcessRequirement
    {
        return $this->transitions->transition(
            $requirement,
            'requirement_status',
            WorkRequirementStatus::Denied,
            ['is_accept' => 2, 'is_pro_acc_by_warehouse' => 'No', 'alloted_remark' => $reason],
            $reason,
            $actorId,
        );
    }
}
