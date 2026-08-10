<?php

namespace Tests\Feature\Regression;

use App\Http\Controllers\WorkOrderController;
use App\Http\Controllers\WorkPurchaseRequirementController;
use App\Http\Middleware\EnforceMappedPermission;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Blade;
use ReflectionMethod;
use Tests\TestCase;

class ViewModuleStabilizationTest extends TestCase
{
    public function test_active_controller_actions_do_not_reference_missing_literal_views(): void
    {
        $missing = [];

        /** @var Route $route */
        foreach (app('router')->getRoutes() as $route) {
            $action = $route->getActionName();
            if (! str_contains($action, '@')) {
                continue;
            }

            [$controller, $method] = explode('@', $action, 2);
            if (! class_exists($controller) || ! method_exists($controller, $method)) {
                continue;
            }

            $reflection = new ReflectionMethod($controller, $method);
            $lines = file($reflection->getFileName());
            $source = implode('', array_slice(
                $lines,
                $reflection->getStartLine() - 1,
                $reflection->getEndLine() - $reflection->getStartLine() + 1,
            ));

            preg_match_all(
                "/(?:view|loadView|View::make)\\(\\s*['\"]([^'\"]+)['\"]/",
                $source,
                $matches,
            );

            foreach ($matches[1] as $view) {
                if (! view()->exists($view)) {
                    $missing[] = ($route->getName() ?: $route->uri())." -> {$controller}@{$method} -> {$view}";
                }
            }
        }

        sort($missing);
        $this->assertSame([], array_values(array_unique($missing)));
    }

    public function test_dyed_work_order_aliases_redirect_to_the_canonical_filtered_listing(): void
    {
        $this->withoutMiddleware(EnforceMappedPermission::class);
        $this->actingAs($this->transientUser(), 'web');

        foreach (['show-dyed-workorders', 'show-workorders-dyeing'] as $name) {
            $route = app('router')->getRoutes()->getByName($name);

            $this->assertNotNull($route);
            $this->assertSame(WorkOrderController::class.'@checkingDyedWorkOrder', $route->getActionName());
            $this->get(route($name, ['year_record' => '2025', 'colorSearch' => 'Blue']))
                ->assertRedirect(route('show-workorders', [
                    'year_record' => '2025',
                    'colorSearch' => 'Blue',
                    'search_process_id' => [3],
                ]));
        }
    }

    public function test_canonical_work_order_body_renders_with_the_controller_payload_contract(): void
    {
        $template = file_get_contents(resource_path('views/frontend/workorder/show-workorders.blade.php'));
        $template = preg_replace('/@include\\([^\\r\\n]+\\)/', '', $template);

        $html = Blade::render($template, [
            'dataWI' => new LengthAwarePaginator([], 0, 10),
            'totSumMtr' => 0,
            'cusSearch' => '',
            'individualId' => '',
            'itemSearch' => '',
            'ordNumSearch' => '',
            'priority' => '',
            'dataMas' => collect(),
            'machine' => collect(),
            'processI' => collect(),
            'dataW' => collect(),
            'dataF' => collect(),
            'dataIT' => collect(),
            'dataI' => collect(),
            'dataITP' => collect(),
            'priorityArr' => [],
            'search_process_id' => [3],
            'fromDate' => '',
            'toDate' => '',
            'workStatus' => '1',
            'colorSearch' => '',
            'LotNumSearch' => '',
            'userIndId' => 990001,
            'proceStatus' => '0',
            'recLotNumSerch' => '',
            'yearRecord' => '2025',
            'availableTakaCounts' => collect(),
            'childLotNumbersByWorkOrder' => collect(),
            'totalChildWorkByWorkOrder' => collect(),
            'customerNamesById' => collect(),
            'allotedStocksByWorkOrder' => collect(),
        ]);

        $this->assertStringContainsString('Work Order List', $html);
        $this->assertStringContainsString(route('show-workorders'), $html);
        $this->assertStringNotContainsString('lab-request.send', $html);
    }

    public function test_incomplete_lab_module_has_no_operational_route_or_trigger(): void
    {
        foreach (['labtests.store', 'lab-request.send'] as $name) {
            $this->assertNull(app('router')->getRoutes()->getByName($name));
        }

        $this->get('/lab-request/send')->assertNotFound();
        $this->post('/labtests')->assertNotFound();

        $view = file_get_contents(resource_path('views/frontend/workorder/show-workorders.blade.php'));
        $scripts = file_get_contents(resource_path('views/frontend/workorder/partials/show-workorders-scripts.blade.php'));
        $modals = file_get_contents(resource_path('views/frontend/workorder/partials/show-workorders-modals.blade.php'));

        $this->assertStringContainsString('Lab unavailable', $view);
        $this->assertStringNotContainsString('openLabRequestModal', $view.$scripts);
        $this->assertStringNotContainsString("route('lab-request.send')", $scripts);
        $this->assertStringNotContainsString('labRequestModal', $modals);
    }

    public function test_dead_work_purchase_listing_reference_is_removed_without_breaking_active_requisition(): void
    {
        $this->assertFalse(method_exists(WorkPurchaseRequirementController::class, 'index'));
        $this->assertNull(app('router')->getRoutes()->getByName('show-work-purchase-requirement'));
        $this->assertNotNull(app('router')->getRoutes()->getByName('add-work-purchase-requisition'));
        $this->assertStringNotContainsString(
            'show-work-purchase-requirement',
            file_get_contents(app_path('Http/Controllers/WorkPurchaseRequirementController.php')),
        );
    }

    public function test_sale_order_work_order_details_keeps_its_controlled_route(): void
    {
        $route = app('router')->getRoutes()->getByName('show-saleorder-workorder-details');

        $this->assertNotNull($route);
        $this->assertContains('auth:web', $route->gatherMiddleware());
    }

    private function transientUser(): User
    {
        $user = new User;
        $user->forceFill([
            'id' => 990002,
            'individual_id' => 990002,
            'user_type' => 'User',
            'name' => 'View Stabilization User',
            'email' => 'view-stabilization@example.test',
            'status' => 'Active',
        ]);
        $user->exists = true;

        return $user;
    }
}
