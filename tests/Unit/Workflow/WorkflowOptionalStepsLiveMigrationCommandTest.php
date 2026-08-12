<?php

namespace Tests\Unit\Workflow;

use PHPUnit\Framework\TestCase;

class WorkflowOptionalStepsLiveMigrationCommandTest extends TestCase
{
    public function test_live_apply_is_exactly_targeted_hash_pinned_and_guarded(): void
    {
        $migrationPath = dirname(__DIR__, 2).'/../database/migrations/2026_08_12_000015_add_optional_steps_and_repeat_support_to_workflow_version_steps.php';
        $command = file_get_contents(dirname(__DIR__, 2).'/../app/Console/Commands/ApplyReviewedWorkflowOptionalStepsMigrationCommand.php');
        $hash = hash_file('sha256', $migrationPath);

        $this->assertStringContainsString("private const HASH = '{$hash}'", $command);
        $this->assertStringContainsString("private const MIGRATION = '2026_08_12_000015_add_optional_steps_and_repeat_support_to_workflow_version_steps'", $command);
        $this->assertStringContainsString('{--backup-manifest=', $command);
        $this->assertStringContainsString('{--writes-stopped', $command);
        $this->assertStringContainsString('assertReviewedMigrationIsPending()', $command);
        $this->assertStringContainsString('$migrator->run([$path]', $command);
        $this->assertStringContainsString('authorizeReviewedLiveMigration(self::DATABASE)', $command);
        $this->assertStringContainsString('revokeDestructiveAuthorization()', $command);
        $this->assertStringContainsString("'is_required'", $command);
        $this->assertStringContainsString('SEQUENCE_UNIQUE_INDEX', $command);
        $this->assertStringContainsString('PROCESS_UNIQUE_INDEX', $command);
    }
}
