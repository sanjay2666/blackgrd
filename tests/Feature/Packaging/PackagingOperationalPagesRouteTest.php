<?php

namespace Tests\Feature\Packaging;

use Tests\TestCase;

class PackagingOperationalPagesRouteTest extends TestCase
{
    public function test_operational_packaging_routes_resolve_and_reject_guests(): void
    {
        $this->get('/show-add-packaging-list')->assertRedirect(route('login'));
        $this->get('/show-packagings')->assertRedirect(route('login'));
        $this->get('/packaging/available-stock?sale_order_item_ids[]=1')->assertRedirect(route('login'));
    }
}
