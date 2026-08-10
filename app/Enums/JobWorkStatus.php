<?php

namespace App\Enums;

use App\Enums\Concerns\HasStatusMetadata;

enum JobWorkStatus: string
{
    use HasStatusMetadata;

    case RequirementRaised = 'requirement_raised';
    case VendorSelected = 'vendor_selected';
    case Approved = 'approved';
    case Dispatched = 'dispatched';
    case PartiallyReceived = 'partially_received';
    case Received = 'received';
    case InspectionPending = 'inspection_pending';
    case ShortagePending = 'shortage_pending';
    case Rework = 'rework';
    case Closed = 'closed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::RequirementRaised => 'Requirement Raised', self::VendorSelected => 'Vendor Selected',
            self::Approved => 'Approved', self::Dispatched => 'Dispatched',
            self::PartiallyReceived => 'Partially Received', self::Received => 'Received',
            self::InspectionPending => 'Inspection Pending', self::ShortagePending => 'Shortage Pending',
            self::Rework => 'Rework', self::Closed => 'Closed', self::Cancelled => 'Cancelled',
        };
    }
}
