<?php

namespace App\DatabaseSafety;

class DatabaseSafetyResult
{
    /**
     * @param  list<string>  $reasons
     */
    public function __construct(
        public readonly DatabaseSafetySnapshot $snapshot,
        public readonly bool $allowed,
        public readonly array $reasons,
        public readonly bool $executionArmed,
    ) {}
}
