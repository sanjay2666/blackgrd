<?php

namespace App\Enums;

use App\Enums\Concerns\HasStatusMetadata;

enum InspectionResult: string
{
    use HasStatusMetadata;

    case Pending = 'pending';
    case Passed = 'passed';
    case PartiallyPassed = 'partially_passed';
    case Rejected = 'rejected';
    case Defective = 'defective';
    case Rework = 'rework';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending', self::Passed => 'Passed',
            self::PartiallyPassed => 'Partially Passed', self::Rejected => 'Rejected',
            self::Defective => 'Defective', self::Rework => 'Rework', self::Completed => 'Completed',
        };
    }
}
