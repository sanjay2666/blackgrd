<?php

namespace Tests\Unit\Masters;

use Tests\TestCase;

final class ItemTypeMasterContractTest extends TestCase
{
    public function test_item_type_master_preserves_canonical_table_and_protected_legacy_mapping(): void
    {
        $model = file_get_contents(base_path('app/Models/ItemType.php'));
        $service = file_get_contents(base_path('app/Services/ItemTypeMasterService.php'));
        $migration = file_get_contents(base_path('database/migrations/2026_08_11_000004_harden_item_type_master.php'));

        $this->assertStringContainsString('protected $table = \'item_type\'', $model);
        $this->assertStringContainsString("3 => 'Greige'", $service);
        $this->assertStringContainsString("4 => 'Dyed'", $service);
        $this->assertStringContainsString("5 => 'Coated'", $service);
        $this->assertStringContainsString("8 => 'Fabric'", $service);
        $this->assertStringContainsString('short_code', $migration);
        $this->assertStringContainsString('display_order', $migration);
    }

    public function test_item_type_master_does_not_create_item_or_workflow_fields(): void
    {
        $migration = file_get_contents(base_path('database/migrations/2026_08_11_000004_harden_item_type_master.php'));
        $this->assertStringNotContainsString('dyeing_color', $migration);
        $this->assertStringNotContainsString('coating_type', $migration);
        $this->assertStringNotContainsString('workflow', strtolower($migration));
        $this->assertStringNotContainsString('item_name', $migration);
    }
}
