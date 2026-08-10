<?php

namespace App\Enums;

use App\Enums\Concerns\HasStatusMetadata;

enum GatePassStatus: string
{
    use HasStatusMetadata;

    case Draft = 'draft';
    case Issued = 'issued';
    case PartiallyReceived = 'partially_received';
    case Received = 'received';
    case Cancelled = 'cancelled';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft', self::Issued => 'Issued',
            self::PartiallyReceived => 'Partially Received', self::Received => 'Received',
            self::Cancelled => 'Cancelled', self::Closed => 'Closed',
        };
    }
}
