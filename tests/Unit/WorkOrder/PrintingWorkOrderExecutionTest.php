<?php

namespace Tests\Unit\WorkOrder;

use Tests\TestCase;

final class PrintingWorkOrderExecutionTest extends TestCase
{
    public function test_authorized_printing_users_see_the_shared_work_order_action(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/WorkOrderController.php'));
        $view = file_get_contents(resource_path('views/frontend/workorder/show-workorders.blade.php'));

        $this->assertStringContainsString("->whereIn('id', \$allowedProcessIds)", $controller);
        $this->assertStringContainsString("->where('process_name', 'like', '%Printing%')", $controller);
        $this->assertStringContainsString("'printingProcessIds'", $controller);
        $this->assertStringContainsString('in_array((int) $proTypeId, $printingProcessIds, true)', $view);
        $this->assertStringContainsString('$isDirectPrintingRoute', $view);
        $this->assertStringContainsString('CoatingPrintInspProcess({{ $Id }})', $view);
    }

    public function test_printing_start_and_completion_are_department_scoped_and_duplicate_safe(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/WorkOrderController.php'));
        $start = substr($controller, strpos($controller, 'public function updateworkorder('), strpos($controller, 'public function updateMachineWo(') - strpos($controller, 'public function updateworkorder('));
        $completion = substr($controller, strpos($controller, 'public function updateCoatingPrintInspecProcess('), strpos($controller, 'public function workOrderTotals(') - strpos($controller, 'public function updateCoatingPrintInspecProcess('));

        $this->assertStringContainsString('DepartmentAccessService $departmentAccess', $start);
        $this->assertStringContainsString("->whereIn('process_type_id', \$departmentAccess->allowedProcessIds())", $start);
        $this->assertStringContainsString('This work order has already been started.', $start);
        $this->assertStringContainsString('->lockForUpdate()', $start);

        $this->assertStringContainsString('DepartmentAccessService $departmentAccess', $completion);
        $this->assertStringContainsString("->whereIn('process_type_id', \$departmentAccess->allowedProcessIds())", $completion);
        $this->assertStringContainsString("str_contains(strtolower((string) \$dataOrder->ProcessType?->process_name), 'printing')", $completion);
        $this->assertStringContainsString('This Printing work order has already been completed.', $completion);
        $this->assertStringContainsString('Duplicate submission detected.', $completion);
        $this->assertStringContainsString('->lockForUpdate()', $completion);
        $this->assertStringContainsString('DB::beginTransaction()', $completion);
        $this->assertStringContainsString('DB::commit()', $completion);
        $this->assertStringContainsString('DB::rollBack()', $completion);
    }

    public function test_printing_before_coating_creates_one_company_scoped_coating_child(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/WorkOrderController.php'));
        $completion = substr($controller, strpos($controller, 'public function updateCoatingPrintInspecProcess('), strpos($controller, 'public function workOrderTotals(') - strpos($controller, 'public function updateCoatingPrintInspecProcess('));

        $this->assertStringContainsString("\$printPosition === 'before'", $completion);
        $this->assertStringContainsString("where('parent_work_order_id', \$dataOrder->id)", $completion);
        $this->assertStringContainsString("where('process_type_id', \$coatingProcess->id)", $completion);
        $this->assertStringContainsString("'company_id' => \$dataOrder->company_id", $completion);
        $this->assertStringContainsString("'is_work_require_request_accepted' => 'Yes'", $completion);
        $this->assertStringNotContainsString('$processType = 7;', $completion);
    }

    public function test_task_four_point_five_children_are_actionable_without_creating_printing_for_none(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/WorkOrderController.php'));
        $decision = substr($controller, strpos($controller, 'public function decidePrinting('), strpos($controller, 'public function print_workorder_gatepass(') - strpos($controller, 'public function decidePrinting('));
        $coating = substr($controller, strpos($controller, 'public function update_coating_inspec_process('), strpos($controller, 'public function updateCoatingPrintInspecProcess(') - strpos($controller, 'public function update_coating_inspec_process('));

        $this->assertStringContainsString("if (\$position === 'before')", $decision);
        $this->assertStringNotContainsString("if (\$position === 'after')", $decision);
        $this->assertStringContainsString("'is_work_require_request_accepted' => 'Yes'", $decision);
        $this->assertStringContainsString("if (\$printPosition == 'after')", $coating);
        $this->assertStringContainsString("'is_work_require_request_accepted' => 'Yes'", $coating);
    }
}
