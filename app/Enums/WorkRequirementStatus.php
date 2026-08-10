<?php

namespace App\Enums;

use App\Enums\Concerns\HasStatusMetadata;

enum WorkRequirementStatus: string
{
    use HasStatusMetadata;

    case Created = 'created';
    case SentToWarehouse = 'sent_to_warehouse';
    case Pending = 'pending';
    case PartiallyAllotted = 'partially_allotted';
    case Allotted = 'allotted';
    case Accepted = 'accepted';
    case Denied = 'denied';
    case Cancelled = 'cancelled';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Created => 'Created', self::SentToWarehouse => 'Sent to Warehouse',
            self::Pending => 'Pending', self::PartiallyAllotted => 'Partially Allotted',
            self::Allotted => 'Allotted', self::Accepted => 'Accepted', self::Denied => 'Denied',
            self::Cancelled => 'Cancelled', self::Closed => 'Closed',
        };
    }
}
