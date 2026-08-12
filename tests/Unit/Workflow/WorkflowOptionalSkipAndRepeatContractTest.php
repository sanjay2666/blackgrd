<?php

namespace Tests\Unit\Workflow;

use PHPUnit\Framework\TestCase;

class WorkflowOptionalSkipAndRepeatContractTest extends TestCase
{
    public function test_optional_steps_and_repeat_occurrences_use_a_new_migration_without_changing_task_5_1_snapshot_schema(): void
    {
        $migration = file_get_contents(dirname(__DIR__, 2).'/../database/migrations/2026_08_12_000015_add_optional_steps_and_repeat_support_to_workflow_version_steps.php');

        $this->assertStringContainsString("boolean('is_required')->default(true)", $migration);
        $this->assertStringContainsString('dropUnique(self::PROCESS_UNIQUE_INDEX)', $migration);
        $this->assertStringContainsString("unique(['workflow_version_id', 'process_id'], self::PROCESS_UNIQUE_INDEX)", $migration);
        $this->assertStringContainsString("unique(['workflow_version_id', 'sequence'])", file_get_contents(dirname(__DIR__, 2).'/../database/migrations/2026_08_11_000001_create_workflow_definition_tables.php'));
    }

    public function test_transition_rules_use_step_occurrences_and_validate_optional_skip_edges(): void
    {
        $service = file_get_contents(dirname(__DIR__, 2).'/../app/Services/WorkflowStepTransitionRuleService.php');

        $this->assertStringContainsString('int|ProcessItem|WorkflowVersionStep', $service);
        $this->assertStringContainsString('allowedNextSteps', $service);
        $this->assertStringContainsString('occurs more than once', $service);
        $this->assertStringContainsString('assertAllowedEdge', $service);
    }
}
