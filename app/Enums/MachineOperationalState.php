<?php

namespace App\Enums;

enum MachineOperationalState: string
{
    case Available = 'Available';
    case Running = 'Running';
    case Idle = 'Idle';
    case Maintenance = 'Maintenance';
    case Breakdown = 'Breakdown';
    case Blocked = 'Blocked';

    public function label(): string
    {
        return $this->value;
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return array_column(self::cases(), 'value', 'value');
    }
}
