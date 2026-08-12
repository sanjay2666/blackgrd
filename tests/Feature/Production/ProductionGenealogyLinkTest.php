<?php

namespace Tests\Feature\Production;

use App\Models\ProductionGenealogyLink;
use Tests\TestCase;

class ProductionGenealogyLinkTest extends TestCase
{
    public function test_genealogy_migration_defines_company_scoped_source_and_result_traceability(): void
    {
        $migration = file_get_contents(database_path('migrations/2026_08_12_000016_create_production_genealogy_links_table.php'));

        $this->assertStringContainsString("Schema::create('production_genealogy_links'", $migration);
        $this->assertStringContainsString('$table->unsignedInteger(\'company_id\')', $migration);
        $this->assertStringContainsString('$table->string(\'source_identity\', 100)', $migration);
        $this->assertStringContainsString('$table->string(\'result_identity\', 100)', $migration);
        $this->assertStringContainsString('$table->decimal(\'quantity\', 12, 2)->nullable()', $migration);
        $this->assertStringContainsString('production_genealogy_operation_unique', $migration);
        $this->assertStringContainsString('$table->foreign(\'company_id\')->references(\'id\')->on(\'companies\')->restrictOnDelete()', $migration);
    }

    public function test_model_uses_company_scope_and_preserves_decimal_quantities(): void
    {
        $model = new ProductionGenealogyLink(['quantity' => '12.50']);

        $this->assertContains('App\\Models\\Concerns\\BelongsToCompany', class_uses_recursive($model));
        $this->assertSame('12.50', $model->quantity);
    }

    public function test_controller_records_only_proven_lot_to_taka_and_taka_to_roll_splits(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/WorkOrderController.php'));

        $this->assertStringContainsString("'relationship_type' => 'lot_to_taka'", $controller);
        $this->assertStringContainsString("'relationship_type' => 'taka_to_roll'", $controller);
        $this->assertStringContainsString("'event_type' => 'warehouse_receipt'", $controller);
        $this->assertStringContainsString('\'result_identity\' => $stock->packet_number ?: \'ROL-\'.$stock->id', $controller);
        $this->assertStringContainsString('The selected lot does not belong to this company work order.', $controller);
        $this->assertStringContainsString('Inspection and work order must belong to the same company.', $controller);
    }
}
