<?php

namespace Tests\Unit\Masters;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class CotingMasterContractTest extends TestCase
{
    public function test_coating_type_keeps_one_canonical_legacy_master_and_safe_fields(): void
    {
        $model = file_get_contents(base_path('app/Models/Coting.php'));
        $migration = file_get_contents(base_path('database/migrations/2026_08_12_000004_harden_coting_master.php'));
        $service = file_get_contents(base_path('app/Services/CotingMasterService.php'));
        $this->assertStringContainsString("protected \$table = 'cotings'", $model);
        $this->assertStringContainsString("Schema::hasTable('cotings')", $migration);
        foreach (['description', 'display_order', 'status'] as $field) {
            $this->assertStringContainsString($field, $migration);
        }
        $this->assertStringContainsString('Referenced Coating Type identity cannot be changed.', $service);
        $this->assertStringContainsString('deactivate them instead', $service);
        foreach (['printing_position', 'print_before_coating', 'print_after_coating', 'chemical', 'workflow', 'previous_process', 'next_process'] as $forbidden) {
            $this->assertStringNotContainsString($forbidden, strtolower($migration.$service));
        }
    }

    public function test_coating_type_routes_use_existing_master_rbac_and_navigation(): void
    {
        foreach (['admin.cotings.index', 'admin.cotings.store', 'admin.cotings.update', 'admin.cotings.activate', 'admin.cotings.deactivate', 'admin.cotings.options', 'admin.cotings.destroy'] as $route) {
            $this->assertTrue(Route::has($route), $route);
        }
        $rbac = file_get_contents(base_path('config/rbac_routes.php'));
        $navigation = file_get_contents(base_path('app/Support/AdminNavigation.php'));
        $this->assertStringContainsString("'cotings' => 'masters'", $rbac);
        $this->assertStringContainsString("'admin.cotings.options' => 'masters.view'", $rbac);
        $this->assertStringContainsString('admin.cotings.index', $navigation);
    }
}
