<?php

namespace Tests\Unit\Masters;

use Tests\TestCase;

final class ColourMasterContractTest extends TestCase
{
    public function test_colour_reuses_the_canonical_table_and_safe_lifecycle_service(): void
    {
        $model = file_get_contents(base_path('app/Models/Colour.php'));
        $migration = file_get_contents(base_path('database/migrations/2026_07_17_000008_create_colours_table.php'));
        $service = file_get_contents(base_path('app/Services/ColourMasterService.php'));

        $this->assertStringContainsString("protected \$table = 'colours'", $model);
        $this->assertStringContainsString("Schema::create('colours'", $migration);
        $this->assertStringContainsString('company_id', $service);
        $this->assertStringContainsString('LOWER(TRIM(name))', $service);
        $this->assertStringContainsString('LOWER(TRIM(code))', $service);
        $this->assertStringContainsString('Referenced Colour identity fields cannot be changed.', $service);
        $this->assertStringContainsString('deactivate them instead.', $service);
    }

    public function test_colour_does_not_absorb_shade_recipe_or_design_concerns(): void
    {
        $migration = strtolower(file_get_contents(base_path('database/migrations/2026_07_17_000008_create_colours_table.php')));
        foreach (['dye_recipe', 'chemical', 'shade_formula', 'print_artwork', 'individual_id', 'parent_color_id'] as $forbidden) {
            $this->assertStringNotContainsString($forbidden, $migration);
        }
    }

    public function test_legacy_dyeing_snapshots_and_autocomplete_contract_remain_text_based(): void
    {
        $controller = file_get_contents(base_path('app/Http/Controllers/CommonController.php'));
        $routes = file_get_contents(base_path('routes/web.php'));

        $this->assertStringContainsString("where('status', 'Active')", $controller);
        $this->assertStringContainsString('list_master_color', $routes);
        $this->assertStringContainsString("'dyeing_color'", file_get_contents(base_path('database/migrations/2026_07_19_000002_create_sale_order_items_table.php')));
    }
}
