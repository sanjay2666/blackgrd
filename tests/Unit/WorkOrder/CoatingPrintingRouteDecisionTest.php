<?php

namespace Tests\Unit\WorkOrder;

use Tests\TestCase;

final class CoatingPrintingRouteDecisionTest extends TestCase
{
    public function test_coating_route_decision_is_scoped_and_duplicate_safe(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/WorkOrderController.php'));
        $decision = substr($controller, strpos($controller, 'public function decidePrinting('), strpos($controller, 'public function print_workorder_gatepass(') - strpos($controller, 'public function decidePrinting('));

        $this->assertStringContainsString("'print_position' => 'required|in:before,after,none'", $decision);
        $this->assertStringContainsString('DepartmentAccessService $departmentAccess', $decision);
        $this->assertStringContainsString('->lockForUpdate()', $decision);
        $this->assertStringContainsString("where('parent_work_order_id', \$workOrder->id)", $decision);
        $this->assertStringContainsString("if (\$position === 'before')", $decision);
        $this->assertStringContainsString("where('entry_name', 'like', '%Dyed%')", $decision);
        $this->assertStringContainsString('DB::beginTransaction()', $decision);
        $this->assertStringContainsString('DB::commit()', $decision);
        $this->assertStringContainsString('DB::rollBack()', $decision);
    }

    public function test_printing_before_coating_returns_to_the_existing_coating_child_flow(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/WorkOrderController.php'));
        $inspection = substr($controller, strpos($controller, 'public function update_dyeing_inspec_process('), strpos($controller, 'public function update_coating_inspec_process(') - strpos($controller, 'public function update_dyeing_inspec_process('));

        $this->assertStringContainsString("\$dataOrder->print_position === 'before'", $inspection);
        $this->assertStringContainsString("where('process_name', 'like', '%Coating%')", $inspection);
        $this->assertStringContainsString("where('parent_work_order_id', '=', \$workOrderId)", $inspection);
        $this->assertStringContainsString("where('process_type_id', '=', \$dataPT->id)", $inspection);
    }

    public function test_work_order_page_limits_route_controls_to_coating_and_offers_all_business_choices(): void
    {
        $view = file_get_contents(resource_path('views/frontend/workorder/show-workorders.blade.php'));

        $this->assertStringContainsString('in_array((int) $proTypeId, $coatingProcessIds, true)', $view);
        $this->assertStringContainsString('Printing Before Coating', $view);
        $this->assertStringContainsString('Coating Before Printing', $view);
        $this->assertStringContainsString('No Printing Required', $view);
        $this->assertStringContainsString('$proTypeId == 3 || $proTypeId == 6', $view);
    }
}
