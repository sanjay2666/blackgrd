<?php

namespace Tests\Unit\MachineCapacity;

use Tests\TestCase;

class MachineCapacityContractTest extends TestCase
{
    public function test_capacity_foundation_keeps_runtime_boundaries(): void
    {
        $migration = file_get_contents(base_path('database/migrations/2026_08_11_000005_create_machine_capacities_table.php'));
        $service = file_get_contents(base_path('app/Services/MachineCapacityService.php'));
        $architecture = file_get_contents(base_path('docs/architecture/machine-capacity.md'));
        $this->assertStringContainsString("Schema::create('machine_capacities'", $migration);
        $this->assertStringContainsString("capacity_value', 12, 3", $migration);
        $this->assertStringContainsString("'capacity_value' => 'required|numeric|gt:0'", file_get_contents(base_path('app/Http/Controllers/Admin/MachineCapacityController.php')));
        $this->assertStringContainsString('Please select a valid active Machine.', $service);
        $this->assertStringContainsString('no runtime consumer is introduced', $architecture);
        $this->assertStringContainsString('JET-DYEING IDs 7–9', file_get_contents(base_path('docs/audits/task-3.6-machine-capacity.md')));
    }
}
