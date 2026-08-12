<?php

namespace Tests\Unit\DuplicateProtection;

use Tests\TestCase;

final class DuplicateBusinessTransactionProtectionTest extends TestCase
{
    public function test_shared_protection_covers_mutation_forms_and_in_flight_ajax_requests(): void
    {
        $script = file_get_contents(public_path('frontend-assets/dist/js/duplicate-submit-protection.js'));

        $this->assertStringContainsString('$.ajaxPrefilter', $script);
        $this->assertStringContainsString("jqXHR.abort('duplicate-submit')", $script);
        $this->assertStringContainsString("$(document).on('submit.duplicateSubmitProtection'", $script);
        $this->assertStringContainsString("['POST', 'PUT', 'PATCH', 'DELETE']", $script);
        $this->assertStringContainsString('$form.data(\'submitting\')', $script);
        $this->assertStringContainsString("$(window).on('pageshow.duplicateSubmitProtection'", $script);
        $this->assertStringContainsString("options.duplicateProtect === false", $script);

        $this->assertStringContainsString('duplicate-submit-protection.js', file_get_contents(resource_path('views/frontend/common/footerscript.blade.php')));
        $this->assertStringContainsString('duplicate-submit-protection.js', file_get_contents(resource_path('views/admin/common/formfooterscript.blade.php')));
    }

    public function test_work_order_creation_locks_sale_order_items_and_rejects_the_same_business_action(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/WorkOrderController.php'));
        $storeMethod = substr($controller, strpos($controller, 'public function store('), strpos($controller, 'public function checkIteminWarehouse') - strpos($controller, 'public function store('));

        $this->assertStringContainsString('Duplicate sale order item selected.', $storeMethod);
        $this->assertStringContainsString('->lockForUpdate()', $storeMethod);
        $this->assertStringContainsString("WorkOrderItem::where('sale_order_item_id', \$saleOrderItem->id)", $storeMethod);
        $this->assertStringContainsString("->where('process_type_id', \$processTypeId)", $storeMethod);
        $this->assertStringContainsString('work order has already been created.', $storeMethod);
        $this->assertStringContainsString('DB::beginTransaction()', $storeMethod);
        $this->assertStringContainsString('DB::commit()', $storeMethod);
        $this->assertStringContainsString('DB::rollBack()', $storeMethod);
    }

    public function test_existing_receipt_and_job_work_guards_remain_business_specific(): void
    {
        $warehouse = file_get_contents(app_path('Http/Controllers/WarehouseItemController.php'));
        $workOrders = file_get_contents(app_path('Http/Controllers/WorkOrderController.php'));
        $jobWork = file_get_contents(app_path('Http/Controllers/JobMillWorkController.php'));

        $this->assertStringContainsString("WarehouseItemStock::where('invoice_number'", $warehouse);
        $this->assertStringContainsString("where('insp_taka_number', '=', \$takaNumber)", $warehouse);
        $this->assertStringContainsString("WarehouseItem::where('gate_pass_number', \$gatePassId)", $workOrders);
        $this->assertStringContainsString("WarehouseItem::where('insp_id', \$inspId)", $workOrders);
        $this->assertStringContainsString('GET_LOCK', $jobWork);
        $this->assertStringContainsString('StockMillDispatchItem::whereIn', $jobWork);
    }
}
