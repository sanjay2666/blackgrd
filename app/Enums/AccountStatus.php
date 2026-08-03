<?php

namespace App\Enums;

enum AccountStatus: string
{
    case Active = 'Active';
    case Inactive = 'Inactive';
    case Locked = 'Locked';
    case Disabled = 'Disabled';

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

    public function isActive(): bool
    {
        return $this === self::Active;
    }
}
