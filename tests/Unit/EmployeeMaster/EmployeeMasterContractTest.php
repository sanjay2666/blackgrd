<?php

namespace Tests\Unit\EmployeeMaster;

use Tests\TestCase;

class EmployeeMasterContractTest extends TestCase
{
    public function test_employee_master_reuses_individual_identity_and_keeps_security_boundaries(): void
    {
        $migration = file_get_contents(base_path('database/migrations/2026_08_11_000007_extend_individuals_for_employee_master.php'));
        $service = file_get_contents(base_path('app/Services/EmployeeMasterService.php'));
        $controller = file_get_contents(base_path('app/Http/Controllers/Admin/EmployeeController.php'));
        $architecture = file_get_contents(base_path('docs/architecture/employee-master.md'));
        $this->assertStringContainsString("Schema::hasTable('individuals')", $migration);
        $this->assertStringContainsString("\$table->string('employee_code', 50)", $migration);
        $this->assertStringContainsString("\$employee->type = 'employee'", $service);
        $this->assertStringContainsString('Referenced Employees cannot be deleted', $service);
        $this->assertStringContainsString("where('type', 'employee')", $controller);
        $this->assertStringContainsString('separate optional login identity', $architecture);
        $this->assertStringContainsString('Employee home Department and Frontend User Department Access are separate concepts', $architecture);
    }
}
