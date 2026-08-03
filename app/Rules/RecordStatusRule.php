<?php

namespace App\Rules;

use App\Enums\RecordStatus;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final class RecordStatusRule implements ValidationRule
{
    public function __construct(private readonly bool $allowDeleted = false) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $status = RecordStatus::tryFromLegacyValue($value);

        if ($status === null || (! $this->allowDeleted && $status->isDeleted())) {
            $allowed = $this->allowDeleted
                ? 'Active, Inactive, Deleted, 1, or 0'
                : 'Active, Inactive, 1, or 0';

            $fail("The {$attribute} field must be one of: {$allowed}.");
        }
    }
}
