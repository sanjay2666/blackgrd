<?php

namespace App\Enums;

use App\Enums\Concerns\HasStatusMetadata;

enum InventoryAllocationStatus: string
{
    use HasStatusMetadata;

    case Unallocated = 'unallocated';
    case PartiallyAllocated = 'partially_allocated';
    case Allocated = 'allocated';
    case Released = 'released';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Unallocated => 'Unallocated', self::PartiallyAllocated => 'Partially Allocated',
            self::Allocated => 'Allocated', self::Released => 'Released', self::Cancelled => 'Cancelled',
        };
    }
}
