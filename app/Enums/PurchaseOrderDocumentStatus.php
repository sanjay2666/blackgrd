<?php

namespace App\Enums;

use App\Enums\Concerns\HasStatusMetadata;

enum PurchaseOrderDocumentStatus: string
{
    use HasStatusMetadata;

    case Draft = 'draft';
    case PendingApproval = 'pending_approval';
    case Approved = 'approved';
    case PartiallyReceived = 'partially_received';
    case Received = 'received';
    case Closed = 'closed';
    case OnHold = 'on_hold';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft', self::PendingApproval => 'Pending Approval',
            self::Approved => 'Approved', self::PartiallyReceived => 'Partially Received',
            self::Received => 'Received', self::Closed => 'Closed',
            self::OnHold => 'On Hold', self::Cancelled => 'Cancelled',
        };
    }
}
