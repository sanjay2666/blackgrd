<?php

namespace Tests\Unit\Masters;

use Tests\TestCase;

class ItemMasterContractTest extends TestCase
{
    public function test_item_master_preserves_identity_and_boundaries(): void
    {
        $model = file_get_contents(base_path('app/Models/Item.php'));
        $service = file_get_contents(base_path('app/Services/ItemMasterService.php'));
        $migration = file_get_contents(base_path('database/migrations/2026_08_11_000004_add_canonical_tax_references_to_items.php'));

        $this->assertStringContainsString('protected $table = \'items\';', $model);
        $this->assertStringContainsString('protected $primaryKey = \'item_id\';', $model);
        $this->assertStringContainsString('Referenced Items cannot be reclassified', $service);
        $this->assertStringContainsString('RecordStatus::Inactive->value : RecordStatus::Deleted->value', $service);
        $this->assertStringContainsString('\'hsn_code_id\'', $migration);
        $this->assertStringContainsString('\'gst_rate_id\'', $migration);
        $this->assertFileDoesNotContain('print_before', base_path('app/Models/Item.php'));
        $this->assertFileDoesNotContain('yarn_recipe', base_path('app/Models/Item.php'));
    }

    private function assertFileDoesNotContain(string $needle, string $path): void
    {
        $this->assertStringNotContainsString($needle, file_get_contents($path));
    }
}
