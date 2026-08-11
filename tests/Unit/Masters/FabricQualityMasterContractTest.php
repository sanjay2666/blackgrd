<?php

namespace Tests\Unit\Masters;

use Tests\TestCase;

final class FabricQualityMasterContractTest extends TestCase
{
    public function test_fabric_quality_has_one_canonical_master_and_safe_lifecycle_contract(): void
    {
        $model = file_get_contents(base_path('app/Models/FabricQuality.php'));
        $migration = file_get_contents(base_path('database/migrations/2026_08_11_000005_create_fabric_qualities_table.php'));
        $service = file_get_contents(base_path('app/Services/FabricQualityMasterService.php'));

        $this->assertStringContainsString("protected \$table = 'fabric_qualities'", $model);
        $this->assertStringContainsString("Schema::create('fabric_qualities'", $migration);
        $this->assertStringContainsString('quality_name', $migration);
        $this->assertStringContainsString('gsm', $migration);
        $this->assertStringContainsString('width', $migration);
        $this->assertStringContainsString("'fabric_quality_id', 'quality_id'", $service);
        $this->assertStringContainsString('Referenced Fabric Quality identity fields cannot be changed.', $service);
        $this->assertStringContainsString('deactivate them instead', $service);
    }

    public function test_fabric_quality_does_not_absorb_item_recipe_colour_coating_or_workflow_concerns(): void
    {
        $migration = strtolower(file_get_contents(base_path('database/migrations/2026_08_11_000005_create_fabric_qualities_table.php')));
        foreach (['item_name', 'item_code', 'hsn', 'unit', 'yarn_recipe', 'dyeing_color', 'coating_type', 'print_job', 'workflow'] as $forbidden) {
            $this->assertStringNotContainsString($forbidden, $migration);
        }
    }
}
