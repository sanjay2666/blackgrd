<?php

namespace Tests\Unit\Workflow;

use PHPUnit\Framework\TestCase;

class WorkflowDefinitionContractTest extends TestCase
{
    public function test_workflow_schema_supports_published_versions_unique_ordered_steps_and_sale_order_reference(): void
    {
        $migration = file_get_contents(dirname(__DIR__, 2).'/../database/migrations/2026_08_11_000001_create_workflow_definition_tables.php');

        $this->assertStringContainsString("Schema::create('workflow_definitions'", $migration);
        $this->assertStringContainsString("Schema::create('workflow_versions'", $migration);
        $this->assertStringContainsString("Schema::create('workflow_version_steps'", $migration);
        $this->assertStringContainsString("\$table->unique(['workflow_version_id', 'sequence'])", $migration);
        $this->assertStringContainsString("unique(['workflow_version_id', 'process_id'])", $migration);
        $this->assertStringContainsString("references('id')->on('process_items')", $migration);
        $this->assertStringContainsString("enum('status', ['Draft', 'Published'])", $migration);
        $this->assertStringContainsString("'workflow_definition_id'", $migration);
        $this->assertStringContainsString("'workflow_version_id'", $migration);
    }

    public function test_controller_enforces_draft_immutability_and_revision_copy(): void
    {
        $service = file_get_contents(dirname(__DIR__, 2).'/../app/Services/WorkflowDefinitionService.php');

        $this->assertStringContainsString("\$version->status !== 'Draft'", $service);
        $this->assertStringContainsString('lockForUpdate()', $service);
        $this->assertStringContainsString("'status' => 'Published'", $service);
        $this->assertStringContainsString("'is_current' => true", $service);
        $this->assertStringContainsString('Published versions are immutable.', $service);
    }

    public function test_workflow_does_not_implement_future_route_execution_concepts(): void
    {
        $controller = strtolower(file_get_contents(dirname(__DIR__, 2).'/../app/Http/Controllers/Admin/WorkflowDefinitionController.php'));

        foreach (['transition', 'optional', 'skip', 'workorder generation', 'event dispatcher', 'state machine'] as $forbidden) {
            $this->assertStringNotContainsString($forbidden, $controller);
        }
    }

    public function test_workflow_routes_reuse_admin_process_permissions(): void
    {
        $routes = file_get_contents(dirname(__DIR__, 2).'/../config/rbac_routes.php');
        $navigation = file_get_contents(dirname(__DIR__, 2).'/../app/Support/AdminNavigation.php');

        $this->assertStringContainsString("'workflow-definitions' => 'processes'", $routes);
        $this->assertStringContainsString("'workflow-assignments' => 'processes'", $routes);
        $this->assertStringContainsString("admin.workflow-definitions.versions.publish' => 'processes.update'", $routes);
        $this->assertStringContainsString("'Workflow Definitions', 'admin.workflow-definitions.index', 'processes.view'", $navigation);
        $this->assertStringContainsString("'Workflow Assignments', 'admin.workflow-assignments.index', 'processes.view'", $navigation);
    }

    public function test_live_apply_is_hash_pinned_and_backup_guarded(): void
    {
        $migrationPath = dirname(__DIR__, 2).'/../database/migrations/2026_08_11_000001_create_workflow_definition_tables.php';
        $command = file_get_contents(dirname(__DIR__, 2).'/../app/Console/Commands/ApplyReviewedWorkflowDefinitionMigrationCommand.php');
        $hash = hash_file('sha256', $migrationPath);

        $this->assertStringContainsString("private const HASH = '{$hash}'", $command);
        $this->assertStringContainsString('{--backup-manifest=', $command);
        $this->assertStringContainsString("private const DATABASE = 'blackgrd'", $command);
        $this->assertStringContainsString("private const MIGRATION = '2026_08_11_000001_create_workflow_definition_tables'", $command);
    }
}
