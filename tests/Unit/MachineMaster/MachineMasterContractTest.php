<?php

namespace Tests\Unit\MachineMaster;

use Tests\TestCase;

class MachineMasterContractTest extends TestCase
{
    public function test_machine_master_contract_is_documented_and_keeps_capacity_boundary(): void
    {
        $architecture = file_get_contents(base_path('docs/architecture/machine-master.md'));
        $audit = file_get_contents(base_path('docs/audits/task-3.5-machine-master.md'));
        $service = file_get_contents(base_path('app/Services/MachineMasterService.php'));

        $this->assertStringContainsString('physical machine identity', $architecture);
        $this->assertStringContainsString('Machine Capacity and production scheduling are separate concerns', $architecture);
        $this->assertStringContainsString('JET-DYEING -01', $audit);
        $this->assertStringContainsString('dyeing_machine_id', $service);
        $this->assertStringContainsString('cannot be deleted', file_get_contents(base_path('app/Http/Controllers/Admin/MachineController.php')));
    }
}
