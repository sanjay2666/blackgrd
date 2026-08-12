<?php

namespace Tests\Feature\Database;

use App\DatabaseSafety\DatabaseSafetyGuard;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProductionGenealogyMigrationTest extends TestCase
{
    public function test_genealogy_migration_rolls_back_and_reapplies_without_business_data_loss(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            $this->markTestSkipped('Production genealogy migration reversal requires disposable MySQL.');
        }
        if (DB::connection()->getDatabaseName() !== 'blackgrd_schema_testing') {
            $this->fail('Refusing Production genealogy migration reversal outside blackgrd_schema_testing.');
        }

        $guard = app(DatabaseSafetyGuard::class);
        if (! $guard->check()->executionArmed) {
            $this->markTestSkipped('Set the exact disposable destructive-operation confirmation to run migration reversal verification.');
        }
        if (! Schema::hasTable('production_genealogy_links') || DB::table('production_genealogy_links')->exists()) {
            $this->fail('Disposable production genealogy table must exist and be empty before reversal verification.');
        }

        $before = $this->businessSnapshot();
        $migration = require database_path('migrations/2026_08_12_000016_create_production_genealogy_links_table.php');
        $guard->authorizeDestructiveCommand('migrate:rollback');

        try {
            $migration->down();
            $this->assertFalse(Schema::hasTable('production_genealogy_links'));
            $this->assertSame($before, $this->businessSnapshot());

            $migration->up();
            $this->assertTrue(Schema::hasTable('production_genealogy_links'));
            $this->assertSame(0, DB::table('production_genealogy_links')->count());
            $this->assertTrue($this->hasIndex('production_genealogy_operation_unique'));
            $this->assertTrue($this->hasIndex('production_genealogy_source_lookup'));
            $this->assertTrue($this->hasIndex('production_genealogy_result_lookup'));
            $this->assertSame($before, $this->businessSnapshot());
        } finally {
            if (! Schema::hasTable('production_genealogy_links')) {
                $migration->up();
            }
            $guard->revokeDestructiveAuthorization();
        }
    }

    /** @return array<string, array{count: int, ids: string}> */
    private function businessSnapshot(): array
    {
        $snapshot = [];
        foreach (['companies', 'sale_orders', 'sale_order_items', 'work_orders', 'work_order_items', 'work_process_requirements', 'work_inspections', 'work_inspection_details', 'warehouse_item_stocks'] as $table) {
            $ids = DB::table($table)->orderBy('id')->pluck('id')->map(fn ($id): string => (string) $id)->all();
            $snapshot[$table] = ['count' => count($ids), 'ids' => hash('sha256', implode(',', $ids))];
        }

        return $snapshot;
    }

    private function hasIndex(string $name): bool
    {
        return collect(Schema::getIndexes('production_genealogy_links'))->contains(
            fn (array $index): bool => ($index['name'] ?? '') === $name,
        );
    }
}
