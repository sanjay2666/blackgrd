<?php

namespace App\Exceptions;

use DomainException;

class InvalidOperationalStatusTransition extends DomainException
{
    public static function between(string $domain, ?string $from, string $to): self
    {
        return new self(sprintf(
            'Invalid %s transition from [%s] to [%s].',
            $domain,
            $from ?? 'unassigned',
            $to,
        ));
    }
}
