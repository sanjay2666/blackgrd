<?php

namespace Tests\Unit\ProcessConfiguration;

use App\Support\RoutePermissionRegistry;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ProcessConfigurationContractTest extends TestCase
{
    public function test_configuration_page_and_update_route_reuse_process_rbac(): void
    {
        $configuration = Route::getRoutes()->getByName('admin.process-items.configuration');
        $update = Route::getRoutes()->getByName('admin.process-items.configuration.update');

        $this->assertNotNull($configuration);
        $this->assertNotNull($update);
        $this->assertSame('processes.view', RoutePermissionRegistry::permission($configuration));
        $this->assertSame('processes.update', RoutePermissionRegistry::permission($update));
    }

    public function test_configuration_schema_is_company_scoped_and_does_not_create_a_workflow_table(): void
    {
        $migration = file_get_contents(base_path('database/migrations/2026_08_12_000014_create_process_configuration_tables.php'));
        $service = file_get_contents(base_path('app/Services/ProcessConfigurationService.php'));

        $this->assertStringContainsString("Schema::create('process_item_configurations'", $migration);
        $this->assertStringContainsString("Schema::create('process_item_material_configurations'", $migration);
        $this->assertStringContainsString("Schema::create('process_item_allowed_next'", $migration);
        $this->assertStringContainsString("['Internal', 'External', 'Both']", $migration);
        $this->assertStringNotContainsString('workflow_definitions', $migration);
        $this->assertStringContainsString('A process cannot be its own allowed next process.', $service);
    }
}
