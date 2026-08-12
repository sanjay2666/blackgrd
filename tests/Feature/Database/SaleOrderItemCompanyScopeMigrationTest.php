<?php

namespace Tests\Feature\Database;

use App\DatabaseSafety\DatabaseSafetyGuard;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SaleOrderItemCompanyScopeMigrationTest extends TestCase
{
    public function test_reviewed_company_scope_repair_rolls_back_and_reapplies_without_data_loss(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            $this->markTestSkipped('Reviewed schema repair verification requires disposable MySQL.');
        }

        $database = DB::connection()->getDatabaseName();
        if ($database !== 'blackgrd_schema_testing') {
            $this->fail("Refusing schema repair verification on database [{$database}].");
        }

        $guard = app(DatabaseSafetyGuard::class);
        if (! $guard->check()->executionArmed) {
            $this->markTestSkipped('Set the exact disposable destructive-operation confirmation to run migration reversal verification.');
        }
        $guard->authorizeDestructiveCommand('migrate:rollback');

        $companyId = DB::table('companies')->insertGetId([
            'company_code' => 'MIG-REVERSAL',
            'name' => 'Migration Reversal Company',
            'status' => 'Active',
        ]);
        $saleOrderId = DB::table('sale_orders')->insertGetId([
            'company_id' => $companyId,
            'order_by_employee' => 1,
            'sale_order_number' => 'MIG-REVERSAL-SO',
            'status' => 'Active',
        ]);
        $itemId = DB::table('sale_order_items')->insertGetId([
            'sale_order_id' => $saleOrderId,
            'company_id' => $companyId,
            'item_name' => 'Preserved migration item',
            'meter' => 125.50,
            'status' => 'Active',
        ]);
        $before = DB::table('sale_order_items')->where('id', $itemId)
            ->first(['id', 'sale_order_id', 'item_name', 'meter', 'status']);

        $migration = require database_path('migrations/2026_08_12_000013_add_company_scope_to_sale_order_items.php');

        try {
            $migration->down();
            $this->assertFalse(Schema::hasColumn('sale_order_items', 'company_id'));

            $migration->up();
            $this->assertTrue(Schema::hasColumn('sale_order_items', 'company_id'));
            $this->assertSame($companyId, (int) DB::table('sale_order_items')->where('id', $itemId)->value('company_id'));
            $after = DB::table('sale_order_items')->where('id', $itemId)
                ->first(['id', 'sale_order_id', 'item_name', 'meter', 'status']);
            $this->assertEquals($before, $after);
        } finally {
            if (! Schema::hasColumn('sale_order_items', 'company_id')) {
                $migration->up();
            }
            DB::table('sale_order_items')->where('id', $itemId)->delete();
            DB::table('sale_orders')->where('id', $saleOrderId)->delete();
            DB::table('companies')->where('id', $companyId)->delete();
            $guard->revokeDestructiveAuthorization();
        }
    }
}
