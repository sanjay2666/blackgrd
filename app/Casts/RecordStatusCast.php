<?php

namespace App\Casts;

use App\Enums\RecordStatus;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Canonicalize master-record values while preserving string access for legacy UI code.
 *
 * @implements CastsAttributes<string, RecordStatus|int|string>
 */
final class RecordStatusCast implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): string
    {
        return RecordStatus::fromLegacyValue($value)->value;
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): string
    {
        return RecordStatus::fromLegacyValue($value)->value;
    }
}
