<?php

namespace Tests\Unit\Workflow;

use PHPUnit\Framework\TestCase;

class WorkflowDefinitionContractTest extends TestCase
{
    public function test_workflow_schema_supports_versions_ordered_steps_and_repeated_processes(): void
    {
        $migration = file_get_contents(dirname(__DIR__, 2).'/../database/migrations/2026_08_11_000001_create_workflow_definition_tables.php');

        $this->assertStringContainsString("Schema::create('workflow_definitions'", $migration);
        $this->assertStringContainsString("Schema::create('workflow_versions'", $migration);
        $this->assertStringContainsString("Schema::create('workflow_version_steps'", $migration);
        $this->assertStringContainsString("\$table->unique(['workflow_version_id', 'sequence'])", $migration);
        $this->assertStringNotContainsString("unique(['workflow_version_id', 'process_id'])", $migration);
        $this->assertStringContainsString("references('id')->on('process_items')", $migration);
    }

    public function test_controller_enforces_draft_immutability_and_revision_copy(): void
    {
        $controller = file_get_contents(dirname(__DIR__, 2).'/../app/Http/Controllers/Admin/WorkflowDefinitionController.php');

        $this->assertStringContainsString("\$workflow_version->status !== 'Draft'", $controller);
        $this->assertStringContainsString('lockForUpdate()', $controller);
        $this->assertStringContainsString("\$version->version_number = \$nextNumber", $controller);
        $this->assertStringContainsString("\$workflow_version->status = 'Finalized'", $controller);
    }

    public function test_workflow_does_not_implement_future_route_execution_concepts(): void
    {
        $controller = strtolower(file_get_contents(dirname(__DIR__, 2).'/../app/Http/Controllers/Admin/WorkflowDefinitionController.php'));

        foreach (['transition', 'optional', 'skip', 'repeat', 'workorder generation', 'sale_order_item'] as $forbidden) {
            $this->assertStringNotContainsString($forbidden, $controller);
        }
    }

    public function test_workflow_routes_reuse_admin_process_permissions(): void
    {
        $routes = file_get_contents(dirname(__DIR__, 2).'/../config/rbac_routes.php');
        $navigation = file_get_contents(dirname(__DIR__, 2).'/../app/Support/AdminNavigation.php');

        $this->assertStringContainsString("'workflow-definitions' => 'processes'", $routes);
        $this->assertStringContainsString("admin.workflow-definitions.versions.finalize' => 'processes.update'", $routes);
        $this->assertStringContainsString("'Workflow Definitions', 'admin.workflow-definitions.index', 'processes.view'", $navigation);
    }
}
