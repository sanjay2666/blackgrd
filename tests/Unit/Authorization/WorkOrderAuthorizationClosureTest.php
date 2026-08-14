<?php

namespace Tests\Unit\Authorization;

use App\Models\AllPage;
use Tests\TestCase;

final class WorkOrderAuthorizationClosureTest extends TestCase
{
    public function test_work_order_visibility_uses_process_department_access(): void
    {
        $controller = file_get_contents(base_path('app/Http/Controllers/WorkOrderController.php'));
        $service = file_get_contents(base_path('app/Services/DepartmentAccessService.php'));

        $this->assertStringContainsString('allowedProcessIds', $controller);
        $this->assertStringContainsString('whereIn(\'process_type_id\', $allowedProcessIds)', $controller);
        $this->assertStringContainsString('whereIn(\'work_orders.process_type_id\', $departmentAccess->allowedProcessIds())', $controller);
        $this->assertStringContainsString("whereIn('department_id', \$departmentIds)", $service);
        $this->assertStringContainsString("where('status', 'Active')", $service);
    }

    public function test_legacy_special_user_ids_are_not_authorization_rules(): void
    {
        $controller = file_get_contents(base_path('app/Http/Controllers/WorkOrderController.php'));

        foreach ([11, 13, 21, 26] as $userId) {
            $this->assertDoesNotMatchRegularExpression('/userId\\s*[!=]==?\\s*'.$userId.'/', $controller);
            $this->assertDoesNotMatchRegularExpression('/(?:in_array|whereIn)\\([^;]*'.$userId.'/', $controller);
        }
    }

    public function test_work_order_and_inspection_routes_have_individual_frontend_page_definitions(): void
    {
        $pages = AllPage::frontendRouteDefinitions()->pluck('page_name')->all();

        $this->assertContains('GET /show-workorders', $pages);
        $this->assertContains('GET /workorders/totals', $pages);
        $this->assertContains('GET /show-workorder-inspection', $pages);
        $this->assertContains('GET /receive-work-item/{id}', $pages);
    }

    public function test_work_order_scope_does_not_use_employee_home_department_or_process_names(): void
    {
        $controller = file_get_contents(base_path('app/Http/Controllers/WorkOrderController.php'));
        $service = file_get_contents(base_path('app/Services/DepartmentAccessService.php'));

        $this->assertStringNotContainsString('process_name', $service);
        $this->assertStringNotContainsString('designation', $controller);
        $this->assertStringNotContainsString('organizationAccess()->where', $controller);
    }
}
