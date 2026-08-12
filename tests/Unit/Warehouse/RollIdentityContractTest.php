<?php

namespace Tests\Unit\Warehouse;

use App\Http\Controllers\WarehouseItemController;
use ReflectionMethod;
use Tests\TestCase;

final class RollIdentityContractTest extends TestCase
{
    public function test_new_roll_number_is_deterministic_from_its_stock_identity(): void
    {
        $method = new ReflectionMethod(WarehouseItemController::class, 'generatePacketNumber');
        $method->setAccessible(true);

        $controller = app(WarehouseItemController::class);

        $this->assertSame('ROL-42', $method->invoke($controller, 42));
        $this->assertSame('ROL-42', $method->invoke($controller, 42));
        $this->assertSame('ROL-43', $method->invoke($controller, 43));
    }
}
