<?php

namespace App\Domain\OperationalStatus\Actions;

use App\Domain\OperationalStatus\OperationalStatusTransitionService;
use App\Enums\SaleOrderDocumentStatus;
use App\Models\SaleOrder;

final class TransitionSaleOrder
{
    public function __construct(private readonly OperationalStatusTransitionService $transitions) {}

    public function execute(
        SaleOrder $order,
        SaleOrderDocumentStatus $to,
        ?string $reason = null,
        string|int|null $actorId = null,
        bool $force = false,
    ): SaleOrder {
        return $this->transitions->transition(
            $order,
            'document_status',
            $to,
            reason: $reason,
            actorId: $actorId,
            force: $force,
        );
    }
}
