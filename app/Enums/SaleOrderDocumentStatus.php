<?php

namespace App\Enums;

use App\Enums\Concerns\HasStatusMetadata;

enum SaleOrderDocumentStatus: string
{
    use HasStatusMetadata;

    case Draft = 'draft';
    case PendingApproval = 'pending_approval';
    case Approved = 'approved';
    case InProduction = 'in_production';
    case PartiallyDispatched = 'partially_dispatched';
    case Completed = 'completed';
    case OnHold = 'on_hold';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft', self::PendingApproval => 'Pending Approval',
            self::Approved => 'Approved', self::InProduction => 'In Production',
            self::PartiallyDispatched => 'Partially Dispatched', self::Completed => 'Completed',
            self::OnHold => 'On Hold', self::Rejected => 'Rejected', self::Cancelled => 'Cancelled',
        };
    }
}
