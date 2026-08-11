<?php

namespace Tests\Unit\Masters;

use Tests\TestCase;

final class DyeingColourMasterContractTest extends TestCase
{
    public function test_shade_master_is_separate_and_base_colour_scoped(): void
    {
        $migration = file_get_contents(base_path('database/migrations/2026_08_11_000006_create_dyeing_colours_table.php'));
        $model = file_get_contents(base_path('app/Models/DyeingColour.php'));
        $service = file_get_contents(base_path('app/Services/DyeingColourMasterService.php'));

        $this->assertStringContainsString("Schema::create('dyeing_colours'", $migration);
        $this->assertStringContainsString("'colour_id'", $migration);
        $this->assertStringContainsString("protected \$table = 'dyeing_colours'", $model);
        $this->assertStringContainsString('Select an active Base Colour.', $service);
        $this->assertStringContainsString('LOWER(TRIM(name))', $service);
        $this->assertStringContainsString('Referenced Shade identity fields cannot be changed.', $service);
    }

    public function test_shade_does_not_absorb_future_recipe_or_design_concerns(): void
    {
        $migration = strtolower(file_get_contents(base_path('database/migrations/2026_08_11_000006_create_dyeing_colours_table.php')));
        foreach (['chemical', 'dye_formula', 'recipe', 'coating', 'printing', 'temperature', 'machine_settings'] as $forbidden) {
            $this->assertStringNotContainsString($forbidden, $migration);
        }
    }

    public function test_legacy_snapshot_contract_and_navigation_are_preserved(): void
    {
        $routes = file_get_contents(base_path('routes/web.php'));
        $rbac = file_get_contents(base_path('config/rbac_routes.php'));
        $navigation = file_get_contents(base_path('app/Support/AdminNavigation.php'));

        $this->assertStringContainsString('find_saleDyeingColor', $routes);
        $this->assertStringContainsString('list_master_dyeing_colour', $routes);
        $this->assertStringContainsString("'admin.dyeing-colours.activate' => 'masters.update'", $rbac);
        $this->assertStringContainsString('admin.dyeing-colours.index', $navigation);
        $this->assertStringContainsString("'dyeing_color'", file_get_contents(base_path('database/migrations/2026_07_19_000002_create_sale_order_items_table.php')));
    }
}
