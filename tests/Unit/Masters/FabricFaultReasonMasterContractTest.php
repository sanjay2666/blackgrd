<?php

namespace Tests\Unit\Masters;

use App\Support\AdminNavigation;
use Tests\TestCase;

final class FabricFaultReasonMasterContractTest extends TestCase
{
    public function test_reason_master_contract_is_canonical_and_process_scoped(): void
    {
        $model = file_get_contents(base_path('app/Models/FabricFaultReason.php'));
        $service = file_get_contents(base_path('app/Services/FabricFaultReasonMasterService.php'));
        $routes = file_get_contents(base_path('routes/web.php'));
        $this->assertStringContainsString("protected \$table = 'fabric_fault_reasons'", $model);
        $this->assertStringContainsString("where('process_id', \$processId)", $service);
        $this->assertStringContainsString('validateProcessRelation', $service);
        $this->assertStringContainsString("fabric-fault-reasons/options", $routes);
        $items = collect(AdminNavigation::groups())->flatMap(fn (array $group): array => $group['items']);
        $this->assertTrue($items->contains(fn (array $item): bool => $item['route'] === 'admin.fabric-fault-reasons.index' && $item['permission'] === 'masters.view'));
    }

    public function test_reason_master_does_not_add_type_or_quantity_fields(): void
    {
        $migration = file_get_contents(base_path('database/migrations/2026_07_27_000004_create_fabric_fault_reasons_table.php'));
        $this->assertStringNotContainsString('quantity', strtolower($migration));
        $this->assertStringNotContainsString('reason_type', strtolower($migration));
        $this->assertStringNotContainsString('wastage_quantity', strtolower($migration));
    }
}
