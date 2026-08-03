<?php

namespace Tests\Feature\Database;

use Closure;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FirstCriticalForeignKeyTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::connection()->getDriverName() !== 'mysql') {
            $this->markTestSkipped('Critical foreign-key integration tests require disposable MySQL.');
        }

        $database = DB::connection()->getDatabaseName();

        if ($database !== 'blackgrd_schema_testing') {
            $this->fail("Refusing foreign-key integration tests on database [{$database}].");
        }
    }

    public function test_expected_constraints_and_actions_are_installed(): void
    {
        $constraints = collect(DB::select(<<<'SQL'
            SELECT
                kcu.CONSTRAINT_NAME AS constraint_name,
                kcu.TABLE_NAME AS child_table,
                kcu.COLUMN_NAME AS child_column,
                kcu.REFERENCED_TABLE_NAME AS parent_table,
                kcu.REFERENCED_COLUMN_NAME AS parent_column,
                rc.DELETE_RULE AS delete_rule,
                rc.UPDATE_RULE AS update_rule
            FROM information_schema.KEY_COLUMN_USAGE kcu
            INNER JOIN information_schema.REFERENTIAL_CONSTRAINTS rc
                ON rc.CONSTRAINT_SCHEMA = kcu.CONSTRAINT_SCHEMA
                AND rc.CONSTRAINT_NAME = kcu.CONSTRAINT_NAME
            WHERE kcu.CONSTRAINT_SCHEMA = DATABASE()
                AND kcu.CONSTRAINT_NAME IN ('fk_wo_parent', 'fk_wc_warehouse', 'fk_wis_inward')
            SQL))->keyBy('constraint_name');

        $this->assertSame(
            ['fk_wc_warehouse', 'fk_wis_inward', 'fk_wo_parent'],
            $constraints->keys()->sort()->values()->all(),
        );

        $this->assertConstraint(
            $constraints['fk_wo_parent'],
            'work_orders',
            'parent_work_order_id',
            'work_orders',
            'id',
        );
        $this->assertConstraint(
            $constraints['fk_wc_warehouse'],
            'warehouse_compartments',
            'warehouse_id',
            'warehouses',
            'id',
        );
        $this->assertConstraint(
            $constraints['fk_wis_inward'],
            'warehouse_item_stocks',
            'warehouse_item_id',
            'warehouse_in_items',
            'id',
        );
    }

    public function test_valid_parent_child_rows_are_accepted(): void
    {
        $parentWorkOrderId = $this->insertWorkOrder();
        $childWorkOrderId = $this->insertWorkOrder($parentWorkOrderId);

        $warehouseId = DB::table('warehouses')->insertGetId([
            'warehouse_name' => 'FK test warehouse',
        ]);
        $compartmentId = DB::table('warehouse_compartments')->insertGetId([
            'warehouse_id' => $warehouseId,
            'compartment_name' => 'FK test compartment',
        ]);

        $inwardId = DB::table('warehouse_in_items')->insertGetId([]);
        $stockId = DB::table('warehouse_item_stocks')->insertGetId([
            'warehouse_item_id' => $inwardId,
        ]);

        $this->assertDatabaseHas('work_orders', [
            'id' => $childWorkOrderId,
            'parent_work_order_id' => $parentWorkOrderId,
        ]);
        $this->assertDatabaseHas('warehouse_compartments', [
            'id' => $compartmentId,
            'warehouse_id' => $warehouseId,
        ]);
        $this->assertDatabaseHas('warehouse_item_stocks', [
            'id' => $stockId,
            'warehouse_item_id' => $inwardId,
        ]);
    }

    public function test_invalid_foreign_key_references_are_rejected(): void
    {
        $this->assertForeignKeyViolation(
            fn () => $this->insertWorkOrder(999999999),
            1452,
        );
        $this->assertForeignKeyViolation(
            fn () => DB::table('warehouse_compartments')->insert([
                'warehouse_id' => 999999999,
                'compartment_name' => 'Invalid warehouse reference',
            ]),
            1452,
        );
        $this->assertForeignKeyViolation(
            fn () => DB::table('warehouse_item_stocks')->insert([
                'warehouse_item_id' => 999999999,
            ]),
            1452,
        );
    }

    public function test_nullable_optional_relations_accept_null(): void
    {
        $workOrderId = $this->insertWorkOrder();
        $stockId = DB::table('warehouse_item_stocks')->insertGetId([
            'warehouse_item_id' => null,
        ]);

        $this->assertDatabaseHas('work_orders', [
            'id' => $workOrderId,
            'parent_work_order_id' => null,
        ]);
        $this->assertDatabaseHas('warehouse_item_stocks', [
            'id' => $stockId,
            'warehouse_item_id' => null,
        ]);
    }

    public function test_parent_deletes_are_restricted(): void
    {
        $parentWorkOrderId = $this->insertWorkOrder();
        $this->insertWorkOrder($parentWorkOrderId);
        $this->assertForeignKeyViolation(
            fn () => DB::table('work_orders')->where('id', $parentWorkOrderId)->delete(),
            1451,
        );

        $warehouseId = DB::table('warehouses')->insertGetId([
            'warehouse_name' => 'Restricted warehouse',
        ]);
        DB::table('warehouse_compartments')->insert([
            'warehouse_id' => $warehouseId,
            'compartment_name' => 'Restricted compartment',
        ]);
        $this->assertForeignKeyViolation(
            fn () => DB::table('warehouses')->where('id', $warehouseId)->delete(),
            1451,
        );

        $inwardId = DB::table('warehouse_in_items')->insertGetId([]);
        DB::table('warehouse_item_stocks')->insert([
            'warehouse_item_id' => $inwardId,
        ]);
        $this->assertForeignKeyViolation(
            fn () => DB::table('warehouse_in_items')->where('id', $inwardId)->delete(),
            1451,
        );
    }

    private function insertWorkOrder(?int $parentId = null): int
    {
        return DB::table('work_orders')->insertGetId([
            'parent_work_order_id' => $parentId,
            'process_type' => 'TEST',
            'user_id' => 1,
            'process_type_id' => 1,
            'item_type_id' => 1,
            'item_id' => 1,
            'item_name' => 'Foreign-key test item',
            'process_started_by' => 1,
            'process_ended_by' => 1,
            'process_inspected_by' => 1,
            'process_started_remarks' => 'Test',
            'process_ended_remarks' => 'Test',
        ]);
    }

    private function assertForeignKeyViolation(Closure $operation, int $expectedError): void
    {
        try {
            $operation();
            $this->fail("Expected MySQL foreign-key error [{$expectedError}] was not raised.");
        } catch (QueryException $exception) {
            $this->assertSame($expectedError, $exception->errorInfo[1] ?? null);
        }
    }

    private function assertConstraint(
        object $constraint,
        string $childTable,
        string $childColumn,
        string $parentTable,
        string $parentColumn,
    ): void {
        $this->assertSame($childTable, $constraint->child_table);
        $this->assertSame($childColumn, $constraint->child_column);
        $this->assertSame($parentTable, $constraint->parent_table);
        $this->assertSame($parentColumn, $constraint->parent_column);
        $this->assertSame('RESTRICT', $constraint->delete_rule);
        $this->assertSame('RESTRICT', $constraint->update_rule);
    }
}
