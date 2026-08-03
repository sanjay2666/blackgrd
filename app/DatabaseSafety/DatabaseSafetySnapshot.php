<?php

namespace App\DatabaseSafety;

class DatabaseSafetySnapshot
{
    public function __construct(
        public readonly string $environment,
        public readonly string $connectionName,
        public readonly string $driver,
        public readonly ?string $host,
        public readonly ?string $port,
        public readonly ?string $declaredDatabase,
        public readonly ?string $configuredDatabase,
        public readonly ?string $connectedDatabase,
        public readonly bool $configurationCached,
        public readonly ?string $connectionError = null,
    ) {}
}
