<?php

namespace App\Enums;

use App\Enums\Concerns\HasStatusMetadata;

enum InventoryMovementStatus: string
{
    use HasStatusMetadata;

    case Draft = 'draft';
    case Posted = 'posted';
    case PartiallyPosted = 'partially_posted';
    case Reversed = 'reversed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft', self::Posted => 'Posted',
            self::PartiallyPosted => 'Partially Posted', self::Reversed => 'Reversed',
            self::Cancelled => 'Cancelled',
        };
    }
}
