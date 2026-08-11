<?php

namespace Tests\Unit\Masters;

use Tests\TestCase;

final class ChemicalMasterContractTest extends TestCase
{
    public function test_chemical_master_reuses_item_identity_and_validates_chemical_type(): void
    {
        $service = file_get_contents(base_path('app/Services/ChemicalMasterService.php'));
        $controller = file_get_contents(base_path('app/Http/Controllers/Admin/ChemicalController.php'));
        $this->assertStringContainsString("LOWER(TRIM(item_type_name)) = ?", $service);
        $this->assertStringContainsString("Select the canonical Chemical Item Type.", $service);
        $this->assertStringContainsString("'item_name', 'item_code', 'unit_type_id'", $service);
        $this->assertStringContainsString('activeChemicals', $controller);
    }

    public function test_chemical_master_preserves_reference_history_and_formula_boundary(): void
    {
        $service = strtolower(file_get_contents(base_path('app/Services/ChemicalMasterService.php')));
        $routes = file_get_contents(base_path('routes/web.php'));
        $navigation = file_get_contents(base_path('app/Support/AdminNavigation.php'));
        $this->assertStringContainsString('referenced chemical identity and unit cannot be changed', $service);
        $this->assertStringContainsString('chemical_deactivated', $service);
        $this->assertStringContainsString("Route::resource('chemicals'", $routes);
        $this->assertStringContainsString('admin.chemicals.index', $navigation);
        $this->assertStringNotContainsString('dye_formula', $service);
        $this->assertStringNotContainsString('recipe', $service);
    }

    public function test_chemical_routes_use_canonical_rbac_and_no_separate_table_is_created(): void
    {
        $rbac = file_get_contents(base_path('config/rbac_routes.php'));
        $this->assertStringContainsString("'chemicals' => 'masters'", $rbac);
        $this->assertStringContainsString("'admin.chemicals.options' => 'masters.view'", $rbac);
        $this->assertFileDoesNotExist(base_path('database/migrations/2026_08_11_000007_create_chemicals_table.php'));
    }
}
