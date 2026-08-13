<?php

namespace Tests\Unit\Authorization;

use Tests\TestCase;

final class DepartmentProcessMappingContractTest extends TestCase
{
    public function test_mapping_migration_uses_company_scoped_canonical_departments_without_ids(): void
    {
        $migration = file_get_contents(base_path('database/migrations/2026_08_13_000001_complete_department_process_mappings.php'));

        $this->assertStringContainsString("where('company_id', \$companyId)", $migration);
        $this->assertStringContainsString("'warping', 'weaving' => 'Weaving'", $migration);
        $this->assertStringContainsString("'dyeing' => 'Dyeing'", $migration);
        $this->assertStringContainsString("'printing', 'd-printing', 'c-printing' => 'Printing'", $migration);
        $this->assertStringContainsString("'coating' => 'Coating'", $migration);
        $this->assertStringContainsString("'packaging' => 'Packaging'", $migration);
        $this->assertStringContainsString("'warehouse' => 'Warehouse'", $migration);
        $this->assertStringContainsString("'department_name' => 'Warehouse'", $migration);
        $this->assertStringNotContainsString("where('id', 1)", $migration);
        $this->assertStringNotContainsString("where('id', 2)", $migration);
    }

    public function test_warping_correction_keeps_warping_as_a_permanent_canonical_department(): void
    {
        $migration = file_get_contents(base_path('database/migrations/2026_08_13_000002_correct_warping_department_mapping.php'));

        $this->assertStringContainsString("where('process_name', 'Warping')", $migration);
        $this->assertStringContainsString("'department_name' => 'Warping'", $migration);
        $this->assertStringContainsString("->where('department_name', 'Warping')", $migration);
        $this->assertStringNotContainsString("'department_name' => 'Weaving'", $migration);
    }

    public function test_select_all_submits_individual_department_mappings_only(): void
    {
        $view = file_get_contents(resource_path('views/admin/users/department-access.blade.php'));
        $service = file_get_contents(base_path('app/Services/DepartmentAccessService.php'));

        $this->assertStringContainsString('Select All Active Departments', $view);
        $this->assertStringContainsString('department-access-option', $view);
        $this->assertStringContainsString('name="department_ids[]"', $view);
        $this->assertStringContainsString('options.forEach', $view);
        $this->assertStringNotContainsString('all_department_access', $view);
        $this->assertStringContainsString('UserDepartmentAccess::updateOrCreate', $service);
        $this->assertStringNotContainsString('all_department_access', $service);
    }

    public function test_live_application_requires_the_reviewed_protected_path(): void
    {
        $command = file_get_contents(base_path('app/Console/Commands/ApplyReviewedDepartmentProcessMappingMigrationCommand.php'));
        $warpingCommand = file_get_contents(base_path('app/Console/Commands/ApplyReviewedWarpingDepartmentMappingMigrationCommand.php'));

        $this->assertStringContainsString('authorizeReviewedLiveMigration', $command);
        $this->assertStringContainsString('backup-manifest', $command);
        $this->assertStringContainsString('writes-stopped', $command);
        $this->assertStringContainsString('hash_file', $command);
        $this->assertStringContainsString('authorizeReviewedLiveMigration', $warpingCommand);
        $this->assertStringContainsString('user_department_access', $warpingCommand);
        $this->assertStringContainsString('hash_file', $warpingCommand);
    }
}
