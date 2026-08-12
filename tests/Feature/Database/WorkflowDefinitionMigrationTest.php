<?php

namespace Tests\Feature\Database;

use App\DatabaseSafety\DatabaseSafetyGuard;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class WorkflowDefinitionMigrationTest extends TestCase
{
    public function test_reviewed_workflow_migration_rolls_back_and_reapplies_without_business_data_loss(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            $this->markTestSkipped('Workflow migration reversal requires disposable MySQL.');
        }
        $database = DB::connection()->getDatabaseName();
        if ($database !== 'blackgrd_schema_testing') {
            $this->fail("Refusing Workflow migration reversal on database [{$database}].");
        }

        $guard = app(DatabaseSafetyGuard::class);
        if (! $guard->check()->executionArmed) {
            $this->markTestSkipped('Set the exact disposable destructive-operation confirmation to run migration reversal verification.');
        }
        foreach (['workflow_version_steps', 'workflow_versions', 'workflow_definitions'] as $table) {
            if (Schema::hasTable($table) && DB::table($table)->exists()) {
                $this->fail("Disposable Workflow table [{$table}] must be empty before reversal verification.");
            }
        }

        $before = $this->businessSnapshot();
        $migration = require database_path('migrations/2026_08_11_000001_create_workflow_definition_tables.php');
        $guard->authorizeDestructiveCommand('migrate:rollback');

        try {
            $migration->down();
            $this->assertFalse(Schema::hasTable('workflow_definitions'));
            $this->assertFalse(Schema::hasColumn('sale_order_items', 'workflow_version_id'));
            $this->assertSame($before, $this->businessSnapshot());

            $migration->up();
            $this->assertTrue(Schema::hasTable('workflow_definitions'));
            $this->assertTrue(Schema::hasTable('workflow_versions'));
            $this->assertTrue(Schema::hasTable('workflow_version_steps'));
            $this->assertTrue(Schema::hasColumn('sale_order_items', 'workflow_definition_id'));
            $this->assertTrue(Schema::hasColumn('sale_order_items', 'workflow_version_id'));
            $this->assertSame($before, $this->businessSnapshot());
        } finally {
            if (! Schema::hasTable('workflow_definitions')) {
                $migration->up();
            }
            $guard->revokeDestructiveAuthorization();
        }
    }

    /** @return array<string, array{count: int, ids: string}> */
    private function businessSnapshot(): array
    {
        $snapshot = [];
        foreach (['companies', 'process_items', 'sale_orders', 'sale_order_items', 'work_orders', 'work_order_items'] as $table) {
            $ids = DB::table($table)->orderBy('id')->pluck('id')->map(fn ($id): string => (string) $id)->all();
            $snapshot[$table] = [
                'count' => count($ids),
                'ids' => hash('sha256', implode(',', $ids)),
            ];
        }

        return $snapshot;
    }
}
