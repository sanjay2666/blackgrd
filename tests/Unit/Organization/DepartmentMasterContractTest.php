<?php

namespace Tests\Unit\Organization;

use App\Support\PermissionRegistry;
use Tests\TestCase;

final class DepartmentMasterContractTest extends TestCase
{
    public function test_department_master_preserves_company_level_legacy_records_and_factory_scope(): void
    {
        $migration = file_get_contents(base_path('database/migrations/2026_08_10_000001_create_organization_scope_tables.php'));
        $model = file_get_contents(base_path('app/Models/Department.php'));
        $service = file_get_contents(base_path('app/Services/DepartmentMasterService.php'));
        $this->assertStringContainsString("'departments'", $migration);
        $this->assertStringContainsString("'factory_id' => 'unsignedBigInteger'", $migration);
        $this->assertStringContainsString('BelongsToCompany', $model);
        $this->assertStringContainsString('Factory::query()', $service);
        $this->assertStringContainsString('whereNull(\'factory_id\')', $service);
    }

    public function test_department_master_uses_narrow_admin_permissions_and_no_user_access_pivot(): void
    {
        $routes = file_get_contents(base_path('routes/web.php'));
        $permissions = file_get_contents(base_path('app/Support/PermissionRegistry.php'));
        $this->assertContains('departments.activate', array_column(PermissionRegistry::all(), 'key'));
        $this->assertContains('departments.deactivate', array_column(PermissionRegistry::all(), 'key'));
        $this->assertStringContainsString('cannot be deleted; deactivate', file_get_contents(base_path('app/Http/Controllers/Admin/DepartmentController.php')));
        $this->assertStringNotContainsString('user_department_access', $routes.$permissions);
    }

    public function test_department_master_keeps_process_boundary_and_historical_references(): void
    {
        $model = file_get_contents(base_path('app/Models/Department.php'));
        $service = file_get_contents(base_path('app/Services/DepartmentMasterService.php'));
        $this->assertStringNotContainsString('process_type_id', $model.$service);
        $this->assertStringNotContainsString('delete(', strtolower($service));
        $this->assertStringContainsString('department_id', file_get_contents(base_path('app/Models/Individual.php')));
        $this->assertStringContainsString('department_id', file_get_contents(base_path('database/migrations/2026_08_10_000001_create_organization_scope_tables.php')));
    }
}
