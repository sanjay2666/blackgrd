<?php

namespace Tests\Unit\NumberSeries;

use App\Models\FinancialYear;
use App\Models\NumberSeries;
use App\Services\NumberSeriesService;
use App\Support\FrontendPermissionCatalog;
use App\Support\PermissionRegistry;
use Tests\TestCase;

final class NumberSeriesContractTest extends TestCase
{
    public function test_schema_bootstrap_and_service_are_forward_only_and_locked(): void
    {
        $migration = file_get_contents(base_path('database/migrations/2026_08_12_000001_create_number_series_table.php'));
        $bootstrap = file_get_contents(base_path('database/migrations/2026_08_12_000002_seed_number_series.php'));
        $service = file_get_contents(base_path('app/Services/NumberSeriesService.php'));

        $this->assertStringContainsString("Schema::create('number_series'", $migration);
        $this->assertStringContainsString('lockForUpdate()', $service);
        $this->assertStringContainsString('next_number', $bootstrap);
        $this->assertStringContainsString('MAX(CAST', $bootstrap);
        $this->assertStringContainsString('number_series_key_year_unique', $migration);
    }

    public function test_number_series_permissions_are_admin_only(): void
    {
        $this->assertContains('number-series.view', array_column(PermissionRegistry::all(), 'key'));
        $this->assertContains('number-series.manage', array_column(PermissionRegistry::all(), 'key'));
        $this->assertNotContains('number-series.view', FrontendPermissionCatalog::keys());
        $this->assertNotContains('number-series.manage', FrontendPermissionCatalog::keys());
    }

    public function test_manual_external_references_are_not_migrated(): void
    {
        $saleOrder = file_get_contents(base_path('app/Http/Controllers/SaleOrderController.php'));
        $purchase = file_get_contents(base_path('app/Http/Controllers/PurchaseController.php'));

        $this->assertStringContainsString("'sale_order_number' => 'required'", $saleOrder);
        $this->assertStringNotContainsString("next('sale-order'", $saleOrder);
        $this->assertStringNotContainsString("next('purchase-order'", $purchase);
    }

    public function test_configurable_prefix_fy_token_and_padding_are_deterministic(): void
    {
        $series = new NumberSeries(['prefix' => 'SO/{FY}/', 'suffix' => null, 'padding' => 5]);
        $year = new FinancialYear(['code' => '2627']);

        $this->assertSame('SO/2627/00007', app(NumberSeriesService::class)->format($series, 7, $year));
    }
}
