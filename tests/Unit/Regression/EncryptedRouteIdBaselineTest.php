<?php

namespace Tests\Unit\Regression;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

class EncryptedRouteIdBaselineTest extends TestCase
{
    public function test_encrypted_route_id_round_trips_through_current_helpers(): void
    {
        $encoded = enc(123456);

        $this->assertNotSame('123456', $encoded);
        $this->assertSame('123456', dec($encoded));
    }

    public function test_invalid_encrypted_route_id_currently_aborts_with_404(): void
    {
        $this->expectException(NotFoundHttpException::class);

        dec('not-a-valid-encrypted-reference');
    }
}
