<?php

namespace Tests\Unit\Packaging;

use Tests\TestCase;

class PackagingEncryptedReferenceContractTest extends TestCase
{
    public function test_packaging_and_sales_challan_navigation_uses_encrypted_references(): void
    {
        $packaging = file_get_contents(app_path('Http/Controllers/PackagingController.php'));
        $challans = file_get_contents(app_path('Http/Controllers/SalesChallanController.php'));
        $cart = file_get_contents(resource_path('views/frontend/packaging/cart.blade.php'));
        $detail = file_get_contents(resource_path('views/frontend/packaging/show.blade.php'));
        $salesCreate = file_get_contents(resource_path('views/frontend/sales_challans/create.blade.php'));
        $salesShow = file_get_contents(resource_path('views/frontend/sales_challans/show.blade.php'));

        $this->assertStringContainsString('dec($saleOrderItem)', $packaging);
        $this->assertStringContainsString('dec($packagingOrder)', $packaging);
        $this->assertStringContainsString('dec($salesChallan)', $challans);
        $this->assertStringContainsString("'id' => enc(\$stock->id)", $packaging);
        $this->assertStringContainsString('enc($saleOrderItem->id)', $cart);
        $this->assertStringContainsString('enc($stock->id)', $cart);
        $this->assertStringContainsString('enc($allocation->id)', $detail);
        $this->assertStringContainsString('enc($allocation->id)', $salesCreate);
        $this->assertStringContainsString('enc($salesChallan->id)', $salesShow);
    }
}
