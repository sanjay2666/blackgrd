<?php

namespace Tests\Feature\Database;

use App\DatabaseSafety\DatabaseSafetyGuard;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class WorkflowOptionalStepsMigrationTest extends TestCase
{
    public function test_optional_steps_migration_rolls_back_and_reapplies_without_workflow_data_loss(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            $this->markTestSkipped('Workflow optional-steps migration reversal requires disposable MySQL.');
        }
        if (DB::connection()->getDatabaseName() !== 'blackgrd_schema_testing') {
            $this->fail('Refusing Workflow optional-steps migration reversal outside blackgrd_schema_testing.');
        }

        $guard = app(DatabaseSafetyGuard::class);
        if (! $guard->check()->executionArmed) {
            $this->markTestSkipped('Set the exact disposable destructive-operation confirmation to run migration reversal verification.');
        }

        $before = $this->workflowSnapshot();
        $migration = require database_path('migrations/2026_08_12_000015_add_optional_steps_and_repeat_support_to_workflow_version_steps.php');
        $guard->authorizeDestructiveCommand('migrate:rollback');

        try {
            $migration->down();
            $this->assertFalse(Schema::hasColumn('workflow_version_steps', 'is_required'));
            $this->assertTrue($this->hasUniqueIndex('workflow_version_steps_workflow_version_id_sequence_unique'));
            $this->assertTrue($this->hasUniqueIndex('workflow_version_steps_workflow_version_id_process_id_unique'));
            $this->assertSame($before, $this->workflowSnapshot());

            $migration->up();
            $this->assertTrue(Schema::hasColumn('workflow_version_steps', 'is_required'));
            $this->assertTrue($this->hasUniqueIndex('workflow_version_steps_workflow_version_id_sequence_unique'));
            $this->assertFalse($this->hasUniqueIndex('workflow_version_steps_workflow_version_id_process_id_unique'));
            $this->assertSame($before, $this->workflowSnapshot());
        } finally {
            if (! Schema::hasColumn('workflow_version_steps', 'is_required')) {
                $migration->up();
            }
            $guard->revokeDestructiveAuthorization();
        }
    }

    /** @return array<string, array{count: int, ids: string}> */
    private function workflowSnapshot(): array
    {
        $snapshot = [];
        foreach (['workflow_definitions', 'workflow_versions', 'workflow_version_steps'] as $table) {
            $ids = DB::table($table)->orderBy('id')->pluck('id')->map(fn ($id): string => (string) $id)->all();
            $snapshot[$table] = ['count' => count($ids), 'ids' => hash('sha256', implode(',', $ids))];
        }

        return $snapshot;
    }

    private function hasUniqueIndex(string $name): bool
    {
        return collect(Schema::getIndexes('workflow_version_steps'))->contains(
            fn (array $index): bool => ($index['name'] ?? '') === $name && (bool) ($index['unique'] ?? false),
        );
    }
}
