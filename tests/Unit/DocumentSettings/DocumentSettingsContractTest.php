<?php

namespace Tests\Unit\DocumentSettings;

use Tests\TestCase;

final class DocumentSettingsContractTest extends TestCase
{
    public function test_document_settings_are_typed_company_scoped_and_do_not_replace_transactional_owners(): void
    {
        $migration = file_get_contents(base_path('database/migrations/2026_08_12_000011_create_document_settings_table.php'));
        $service = file_get_contents(base_path('app/Services/DocumentSettingsService.php'));
        $routes = file_get_contents(base_path('routes/web.php'));
        $navigation = file_get_contents(base_path('app/Support/AdminNavigation.php'));
        $purchasePrint = file_get_contents(base_path('resources/views/frontend/purchaseorder/print.blade.php'));
        $this->assertStringContainsString("Schema::create('document_settings'", $migration);
        $this->assertStringContainsString("unique(['company_id', 'document_key']", $migration);
        $this->assertStringContainsString("'purchase_order'", $service);
        $this->assertStringContainsString('Unsupported document setting', $service);
        $this->assertStringContainsString('Footer text contains unsupported markup', $service);
        $this->assertStringContainsString('document-settings.update', $routes);
        $this->assertStringContainsString('Print & Document Settings', $navigation);
        $this->assertStringContainsString("documentSettings['terms_text']", $purchasePrint);
        $this->assertStringNotContainsString('eval(', $service);
        $this->assertStringNotContainsString('NumberSeries', $service);
    }
}
