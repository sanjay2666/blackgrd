<?php

namespace App\Enums;

use App\Enums\Concerns\HasStatusMetadata;

enum InspectionStatus: string
{
    use HasStatusMetadata;

    case Pending = 'pending';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending', self::Completed => 'Completed', self::Cancelled => 'Cancelled',
        };
    }
}
