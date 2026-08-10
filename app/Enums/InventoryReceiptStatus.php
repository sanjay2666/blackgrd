<?php

namespace App\Enums;

use App\Enums\Concerns\HasStatusMetadata;

enum InventoryReceiptStatus: string
{
    use HasStatusMetadata;

    case Pending = 'pending';
    case PartiallyReceived = 'partially_received';
    case Received = 'received';
    case Rejected = 'rejected';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending', self::PartiallyReceived => 'Partially Received',
            self::Received => 'Received', self::Rejected => 'Rejected', self::Closed => 'Closed',
        };
    }
}
