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
        $indexView = file_get_contents(base_path('resources/views/admin/cotings/index.blade.php'));
        $formView = file_get_contents(base_path('resources/views/admin/cotings/form.blade.php'));
        $this->assertStringContainsString("'cotings' => 'masters'", $rbac);
        $this->assertStringContainsString("'admin.cotings.options' => 'masters.view'", $rbac);
        $this->assertStringContainsString('admin.cotings.index', $navigation);
        $this->assertStringContainsString('\Illuminate\Support\Str::limit', $indexView);
        $this->assertStringContainsString("getRawOriginal('status') ?: 'Active'", $formView);
        $this->assertStringNotContainsString('IlluminateSupportStr', $indexView.$formView);
        $this->assertStringNotContainsString('status->value', $indexView.$formView);
    }

    public function test_coating_type_reviewed_live_apply_is_hash_pinned_and_preserves_identity(): void
    {
        $command = file_get_contents(base_path('app/Console/Commands/ApplyReviewedCotingMasterMigrationCommand.php'));
        $provider = file_get_contents(base_path('app/Providers/DatabaseSafetyServiceProvider.php'));
        $this->assertStringContainsString('2026_08_12_000004_harden_coting_master', $command);
        $this->assertStringContainsString('eddf682375e9d83630959c524757166a296a73acbf73b42903ad8cced5fcd6bc', $command);
        $this->assertStringContainsString('backup-manifest', $command);
        $this->assertStringContainsString('writes-stopped', $command);
        $this->assertStringContainsString("'id', 'name', 'code', 'status'", $command);
        $this->assertStringContainsString('company_id', $command);
        $this->assertStringContainsString('ApplyReviewedCotingMasterMigrationCommand::class', $provider);
    }
}
