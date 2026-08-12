<?php

namespace Tests\Feature\Production;

use App\Models\ProductionGenealogyLink;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProductionGenealogyDatabaseIntegrationTest extends TestCase
{
    use DatabaseTransactions;

    private int $companyId;

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::connection()->getDriverName() !== 'mysql') {
            $this->markTestSkipped('Production genealogy database integration requires disposable MySQL.');
        }
        if (DB::connection()->getDatabaseName() !== 'blackgrd_schema_testing') {
            $this->fail('Refusing Production genealogy integration tests outside blackgrd_schema_testing.');
        }
        if (! Schema::hasTable('production_genealogy_links')) {
            $this->fail('Task 5.5 genealogy migration must be applied to disposable MySQL.');
        }

        $this->companyId = (int) DB::table('companies')->orderBy('id')->value('id');
        if ($this->companyId < 1) {
            $this->companyId = DB::table('companies')->insertGetId([
                'company_code' => 'GEN-'.random_int(100000, 999999),
                'name' => 'Production Genealogy Test Company',
                'status' => 'Active',
            ]);
        }
    }

    public function test_one_lot_can_trace_to_multiple_takas_and_their_rolls_with_preserved_quantities(): void
    {
        $suffix = (string) random_int(100000, 999999);
        $firstTaka = $this->link('inspection_output', 'lot_to_taka', 501, 'LOT-'.$suffix, 701, 'TAKA-'.$suffix.'-1', 30);
        $secondTaka = $this->link('inspection_output', 'lot_to_taka', 501, 'LOT-'.$suffix, 702, 'TAKA-'.$suffix.'-2', 70);
        $firstRoll = $this->link('warehouse_receipt', 'taka_to_roll', 701, 'TAKA-'.$suffix.'-1', 801, 'ROL-'.$suffix.'-1', 30);
        $secondRoll = $this->link('warehouse_receipt', 'taka_to_roll', 702, 'TAKA-'.$suffix.'-2', 802, 'ROL-'.$suffix.'-2', 70);

        $lotOutputs = ProductionGenealogyLink::query()
            ->where('source_table', 'work_process_requirements')
            ->where('source_id', 501)
            ->where('source_identity', 'LOT-'.$suffix)
            ->orderBy('result_id')
            ->get();

        $this->assertSame([$firstTaka->id, $secondTaka->id], $lotOutputs->pluck('id')->all());
        $this->assertSame(['TAKA-'.$suffix.'-1', 'TAKA-'.$suffix.'-2'], $lotOutputs->pluck('result_identity')->all());
        $this->assertSame(100.0, (float) $lotOutputs->sum('quantity'));
        $this->assertSame(['ROL-'.$suffix.'-1', 'ROL-'.$suffix.'-2'], ProductionGenealogyLink::query()->whereIn('id', [$firstRoll->id, $secondRoll->id])->orderBy('id')->pluck('result_identity')->all());
    }

    public function test_duplicate_completed_operation_and_cross_company_link_are_rejected(): void
    {
        $suffix = (string) random_int(100000, 999999);
        $this->link('warehouse_receipt', 'taka_to_roll', 701, 'TAKA-'.$suffix, 801, 'ROL-'.$suffix, 30);

        try {
            $this->link('warehouse_receipt', 'taka_to_roll', 701, 'TAKA-'.$suffix, 801, 'ROL-'.$suffix, 30);
            $this->fail('A completed genealogy operation was recorded twice.');
        } catch (QueryException) {
            $this->assertTrue(true);
        }

        $this->expectException(QueryException::class);
        ProductionGenealogyLink::query()->create([
            'company_id' => 999999999,
            'event_type' => 'inspection_output',
            'relationship_type' => 'lot_to_taka',
            'source_type' => 'lot',
            'source_table' => 'work_process_requirements',
            'source_id' => 1,
            'source_identity' => 'FOREIGN-LOT',
            'result_type' => 'taka',
            'result_table' => 'work_inspection_details',
            'result_id' => 1,
            'result_identity' => 'FOREIGN-TAKA',
        ]);
    }

    private function link(string $eventType, string $relationshipType, int $sourceId, string $sourceIdentity, int $resultId, string $resultIdentity, float $quantity): ProductionGenealogyLink
    {
        return ProductionGenealogyLink::query()->create([
            'company_id' => $this->companyId,
            'event_type' => $eventType,
            'relationship_type' => $relationshipType,
            'source_type' => $relationshipType === 'lot_to_taka' ? 'lot' : 'taka',
            'source_table' => $relationshipType === 'lot_to_taka' ? 'work_process_requirements' : 'work_inspection_details',
            'source_id' => $sourceId,
            'source_identity' => $sourceIdentity,
            'result_type' => $relationshipType === 'lot_to_taka' ? 'taka' : 'roll',
            'result_table' => $relationshipType === 'lot_to_taka' ? 'work_inspection_details' : 'warehouse_item_stocks',
            'result_id' => $resultId,
            'result_identity' => $resultIdentity,
            'quantity' => $quantity,
        ]);
    }
}
