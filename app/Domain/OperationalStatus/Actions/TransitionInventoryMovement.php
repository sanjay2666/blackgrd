<?php

namespace App\Domain\OperationalStatus\Actions;

use App\Domain\OperationalStatus\OperationalStatusTransitionService;
use App\Enums\InventoryMovementStatus;
use Illuminate\Database\Eloquent\Model;

final class TransitionInventoryMovement
{
    public function __construct(private readonly OperationalStatusTransitionService $transitions) {}

    public function execute(Model $movement, InventoryMovementStatus $to, bool $force = false): Model
    {
        return $this->transitions->transition($movement, 'movement_status', $to, force: $force);
    }
}
