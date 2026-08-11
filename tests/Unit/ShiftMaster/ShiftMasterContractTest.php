<?php

namespace Tests\Unit\ShiftMaster;

use Tests\TestCase;

class ShiftMasterContractTest extends TestCase
{
    public function test_shift_master_contract_supports_overnight_without_operational_engines(): void
    {
        $migration = file_get_contents(base_path('database/migrations/2026_08_11_000006_create_shifts_table.php'));
        $model = file_get_contents(base_path('app/Models/Shift.php'));
        $service = file_get_contents(base_path('app/Services/ShiftMasterService.php'));
        $architecture = file_get_contents(base_path('docs/architecture/shift-master.md'));
        $this->assertStringContainsString("Schema::create('shifts'", $migration);
        $this->assertStringContainsString("\$table->time('start_time')", $migration);
        $this->assertStringContainsString('return ($end >= $start ? $end : $end + 1440) - $start;', $model);
        $this->assertStringContainsString('Start and End Time must define a meaningful Shift.', $service);
        $this->assertStringContainsString('Referenced Shift', $service);
        $this->assertStringContainsString('A Shift crossing midnight', $architecture);
        $this->assertStringContainsString('does not implement employee attendance', $architecture);
    }
}
