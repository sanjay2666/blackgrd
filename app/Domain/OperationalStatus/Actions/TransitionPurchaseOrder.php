<?php

namespace App\Domain\OperationalStatus\Actions;

use App\Domain\OperationalStatus\OperationalStatusTransitionService;
use App\Enums\PurchaseOrderDocumentStatus;
use App\Models\PurchaseOrder;

final class TransitionPurchaseOrder
{
    public function __construct(private readonly OperationalStatusTransitionService $transitions) {}

    public function execute(PurchaseOrder $order, PurchaseOrderDocumentStatus $to, bool $force = false): PurchaseOrder
    {
        $legacy = match ($to) {
            PurchaseOrderDocumentStatus::Received, PurchaseOrderDocumentStatus::Closed => [
                'is_all_item_received' => 'Yes',
                'is_item_received_in_warehouse' => 'Yes',
            ],
            PurchaseOrderDocumentStatus::PartiallyReceived => [
                'is_all_item_received' => 'No',
                'is_item_received_in_warehouse' => 'Yes',
            ],
            default => [
                'is_all_item_received' => 'No',
                'is_item_received_in_warehouse' => 'No',
            ],
        };

        return $this->transitions->transition($order, 'document_status', $to, $legacy, force: $force);
    }
}
