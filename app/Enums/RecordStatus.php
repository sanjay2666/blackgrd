<?php

namespace App\Enums;

use InvalidArgumentException;

enum RecordStatus: string
{
    case Active = 'Active';
    case Inactive = 'Inactive';
    case Deleted = 'Deleted';

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
    public static function options(bool $includeDeleted = true): array
    {
        $cases = $includeDeleted ? self::cases() : [self::Active, self::Inactive];

        return array_column($cases, 'value', 'value');
    }

    /** @return array<string, string> */
    public static function formOptions(): array
    {
        return self::options(includeDeleted: false);
    }

    public function isActive(): bool
    {
        return $this === self::Active;
    }

    public function isDeleted(): bool
    {
        return $this === self::Deleted;
    }

    public static function fromLegacyValue(mixed $value): self
    {
        return self::tryFromLegacyValue($value)
            ?? throw new InvalidArgumentException('Invalid record status value.');
    }

    public static function tryFromLegacyValue(mixed $value): ?self
    {
        if ($value instanceof self) {
            return $value;
        }

        if (is_int($value)) {
            return match ($value) {
                1 => self::Active,
                0 => self::Inactive,
                default => null,
            };
        }

        if (! is_string($value)) {
            return null;
        }

        return match (strtolower(trim($value))) {
            '1', 'active' => self::Active,
            '0', 'inactive' => self::Inactive,
            'deleted' => self::Deleted,
            default => null,
        };
    }
}
