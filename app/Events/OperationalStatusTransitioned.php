<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OperationalStatusTransitioned
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly string $entityType,
        public readonly string|int $entityId,
        public readonly string $attribute,
        public readonly ?string $from,
        public readonly string $to,
        public readonly ?string $reason = null,
        public readonly string|int|null $actorId = null,
        public readonly bool $forced = false,
    ) {}
}
