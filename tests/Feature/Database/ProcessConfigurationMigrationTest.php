<?php

namespace Tests\Feature\Database;

use App\DatabaseSafety\DatabaseSafetyGuard;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProcessConfigurationMigrationTest extends TestCase
{
    public function test_reviewed_process_configuration_migration_rolls_back_and_reapplies_without_business_data_loss(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            $this->markTestSkipped('Process Configuration migration reversal requires disposable MySQL.');
        }
        if (DB::connection()->getDatabaseName() !== 'blackgrd_schema_testing') {
            $this->fail('Refusing Process Configuration migration reversal outside blackgrd_schema_testing.');
        }
        $guard = app(DatabaseSafetyGuard::class);
        if (! $guard->check()->executionArmed) {
            $this->markTestSkipped('Set the exact disposable destructive-operation confirmation to run migration reversal verification.');
        }
        foreach (['process_item_configurations', 'process_item_material_configurations', 'process_item_allowed_next'] as $table) {
            if (Schema::hasTable($table) && DB::table($table)->exists()) {
                $this->fail("Disposable Process Configuration table [{$table}] must be empty before reversal verification.");
            }
        }

        $before = $this->businessSnapshot();
        $migration = require database_path('migrations/2026_08_12_000014_create_process_configuration_tables.php');
        $guard->authorizeDestructiveCommand('migrate:rollback');

        try {
            $migration->down();
            $this->assertFalse(Schema::hasTable('process_item_configurations'));
            $this->assertFalse(Schema::hasTable('process_item_material_configurations'));
            $this->assertFalse(Schema::hasTable('process_item_allowed_next'));
            $this->assertSame($before, $this->businessSnapshot());

            $migration->up();
            $this->assertTrue(Schema::hasTable('process_item_configurations'));
            $this->assertTrue(Schema::hasTable('process_item_material_configurations'));
            $this->assertTrue(Schema::hasTable('process_item_allowed_next'));
            $this->assertSame($before, $this->businessSnapshot());
        } finally {
            if (! Schema::hasTable('process_item_configurations')) {
                $migration->up();
            }
            $guard->revokeDestructiveAuthorization();
        }
    }

    /** @return array<string, array{count:int,ids:string}> */
    private function businessSnapshot(): array
    {
        $snapshot = [];
        foreach (['companies', 'process_items', 'item_type', 'sale_orders', 'sale_order_items', 'work_orders', 'work_order_items'] as $table) {
            $key = $table === 'item_type' ? 'item_type_id' : 'id';
            $ids = DB::table($table)->orderBy($key)->pluck($key)->map(fn ($id): string => (string) $id)->all();
            $snapshot[$table] = ['count' => count($ids), 'ids' => hash('sha256', implode(',', $ids))];
        }

        return $snapshot;
    }
}
