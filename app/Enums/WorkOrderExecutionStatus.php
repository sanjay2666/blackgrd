<?php

namespace App\Enums;

use App\Enums\Concerns\HasStatusMetadata;

enum WorkOrderExecutionStatus: string
{
    use HasStatusMetadata;

    case Created = 'created';
    case MaterialRequested = 'material_requested';
    case MaterialAllotted = 'material_allotted';
    case Ready = 'ready';
    case Started = 'started';
    case PartiallyCompleted = 'partially_completed';
    case Completed = 'completed';
    case InspectionPending = 'inspection_pending';
    case Passed = 'passed';
    case Rejected = 'rejected';
    case Rework = 'rework';
    case OnHold = 'on_hold';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Created => 'Created', self::MaterialRequested => 'Material Requested',
            self::MaterialAllotted => 'Material Allotted', self::Ready => 'Ready',
            self::Started => 'Started', self::PartiallyCompleted => 'Partially Completed',
            self::Completed => 'Completed', self::InspectionPending => 'Inspection Pending',
            self::Passed => 'Passed', self::Rejected => 'Rejected', self::Rework => 'Rework',
            self::OnHold => 'On Hold', self::Cancelled => 'Cancelled',
        };
    }
}
