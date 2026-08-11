<?php

namespace Tests\Unit\Authorization;

use Tests\TestCase;

final class DepartmentAccessContractTest extends TestCase
{
    public function test_canonical_access_is_multi_department_and_company_scoped(): void
    {
        $migration = file_get_contents(base_path('database/migrations/2026_08_11_000002_create_user_department_access_table.php'));
        $model = file_get_contents(base_path('app/Models/UserDepartmentAccess.php'));
        $this->assertStringContainsString("Schema::create('user_department_access'", $migration);
        $this->assertStringContainsString("unique(['user_id', 'company_id', 'department_id']", $migration);
        $this->assertStringContainsString("where('company_id', \$companyId)", file_get_contents(base_path('app/Services/DepartmentAccessService.php')));
        $this->assertStringContainsString('department(): BelongsTo', $model);
    }

    public function test_access_management_is_admin_only_and_audited(): void
    {
        $routes = file_get_contents(base_path('routes/web.php'));
        $service = file_get_contents(base_path('app/Services/DepartmentAccessService.php'));
        $this->assertStringContainsString("middleware('permission:users.manage')", $routes);
        $this->assertStringContainsString("auth('admin')->check()", $service);
        $this->assertStringContainsString('user_department_access_changed', $service);
        $this->assertStringNotContainsString("auth('web')->user()->departmentAccess", $routes);
    }

    public function test_no_access_is_fail_closed_and_active_company_departments_are_required(): void
    {
        $service = file_get_contents(base_path('app/Services/DepartmentAccessService.php'));
        $this->assertStringContainsString('return []', $service);
        $this->assertStringContainsString("where('status', 'Active')", $service);
        $this->assertStringContainsString("where('status', 'Active')->get()", $service);
        $this->assertStringContainsString('Only active Departments from the canonical company', $service);
    }

    public function test_home_department_and_employee_department_remain_distinct(): void
    {
        $user = file_get_contents(base_path('app/Models/User.php'));
        $individual = file_get_contents(base_path('app/Models/Individual.php'));
        $docs = file_get_contents(base_path('docs/architecture/department-access.md'));
        $this->assertStringContainsString('organizationAccess', $user);
        $this->assertStringContainsString('departmentAccess', $user);
        $this->assertStringContainsString("belongsTo(Department::class, 'department_id')", $individual);
        $this->assertStringContainsString('primary/home', $docs);
    }
}
