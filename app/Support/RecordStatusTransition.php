<?php

namespace App\Support;

use App\Enums\RecordStatus;
use App\Exceptions\InvalidRecordStatusTransition;

final class RecordStatusTransition
{
    public static function ensureAllowed(
        mixed $from,
        mixed $to,
        bool $explicitRestore = false,
    ): void {
        $fromStatus = RecordStatus::fromLegacyValue($from);
        $toStatus = RecordStatus::fromLegacyValue($to);

        if ($fromStatus === $toStatus) {
            return;
        }

        $allowed = match ($fromStatus) {
            RecordStatus::Active => in_array($toStatus, [RecordStatus::Inactive, RecordStatus::Deleted], true),
            RecordStatus::Inactive => in_array($toStatus, [RecordStatus::Active, RecordStatus::Deleted], true),
            RecordStatus::Deleted => $explicitRestore
                && in_array($toStatus, [RecordStatus::Active, RecordStatus::Inactive], true),
        };

        if (! $allowed) {
            throw new InvalidRecordStatusTransition(sprintf(
                'Record status transition from %s to %s is not allowed.',
                $fromStatus->value,
                $toStatus->value,
            ));
        }
    }
}
