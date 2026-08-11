<?php

namespace Tests\Unit\Masters;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class PrintingDesignMasterContractTest extends TestCase
{
    public function test_printing_design_has_one_canonical_company_scoped_master(): void
    {
        $model = file_get_contents(base_path('app/Models/PrintingDesign.php'));
        $migration = file_get_contents(base_path('database/migrations/2026_08_12_000003_create_printing_designs_table.php'));
        $service = file_get_contents(base_path('app/Services/PrintingDesignMasterService.php'));

        $this->assertStringContainsString("protected \$table = 'printing_designs'", $model);
        $this->assertStringContainsString("Schema::create('printing_designs'", $migration);
        foreach (['design_name', 'design_code', 'description', 'display_order', 'status'] as $field) {
            $this->assertStringContainsString($field, $migration);
        }
        $this->assertStringContainsString('Referenced Printing Design identity fields cannot be changed.', $service);
        $this->assertStringContainsString('deactivate them instead', $service);
        $this->assertStringContainsString("'printing_design_id', 'print_design_id', 'design_id'", $service);
    }

    public function test_printing_design_keeps_workflow_and_other_master_boundaries_separate(): void
    {
        $migration = strtolower(file_get_contents(base_path('database/migrations/2026_08_12_000003_create_printing_designs_table.php')));
        $service = strtolower(file_get_contents(base_path('app/Services/PrintingDesignMasterService.php')));
        foreach (['print_before_coating', 'print_after_coating', 'printing_position', 'previous_process', 'next_process', 'workflow', 'coating_type', 'chemical', 'dyeing_color', 'fabric_quality_id'] as $forbidden) {
            $this->assertStringNotContainsString($forbidden, $migration);
        }
        $this->assertStringNotContainsString('artwork', $migration);
        $this->assertStringContainsString('printing_design_created', $service);
        $this->assertStringContainsString("'printing_design_'.strtolower", $service);
    }

    public function test_printing_design_routes_use_masters_rbac_and_admin_navigation(): void
    {
        foreach (['admin.printing-designs.index', 'admin.printing-designs.store', 'admin.printing-designs.update', 'admin.printing-designs.activate', 'admin.printing-designs.deactivate', 'admin.printing-designs.options', 'admin.printing-designs.destroy'] as $route) {
            $this->assertTrue(Route::has($route), $route);
        }

        $rbac = file_get_contents(base_path('config/rbac_routes.php'));
        $navigation = file_get_contents(base_path('app/Support/AdminNavigation.php'));
        $this->assertStringContainsString("'printing-designs' => 'masters'", $rbac);
        $this->assertStringContainsString("'admin.printing-designs.options' => 'masters.view'", $rbac);
        $this->assertStringContainsString('admin.printing-designs.index', $navigation);
    }
}
