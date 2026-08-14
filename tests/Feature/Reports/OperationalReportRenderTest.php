<?php

namespace Tests\Feature\Reports;

use App\Models\AllPage;
use App\Models\User;
use App\Models\UserWebPage;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OperationalReportRenderTest extends TestCase
{
    use DatabaseTransactions;

    private int $companyId;

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::connection()->getDriverName() !== 'mysql') {
            $this->markTestSkipped('Operational report rendering requires disposable MySQL.');
        }
        if (DB::connection()->getDatabaseName() !== 'blackgrd_schema_testing') {
            $this->fail('Refusing operational report rendering tests outside blackgrd_schema_testing.');
        }

        $this->companyId = (int) DB::table('companies')->where('status', 'Active')->orderBy('id')->value('id');
        $this->assertNotSame(0, $this->companyId, 'An active canonical company is required.');
        DB::table('users')->insertOrIgnore([
            'id' => 990109,
            'user_type' => 'User',
            'name' => 'Operational Report Test User',
            'email' => 'operational-reports@example.test',
            'password' => 'test-password',
            'status' => 'Active',
        ]);
        DB::table('user_organization_access')->insert([
            'user_id' => 990109,
            'company_id' => $this->companyId,
            'is_default' => true,
            'status' => 'Active',
        ]);
        foreach (AllPage::frontendRouteDefinitions() as $routeDefinition) {
            if (! str_starts_with($routeDefinition['page_name'], 'GET /reports/')) {
                continue;
            }

            $page = AllPage::query()->firstOrCreate(['page_name' => $routeDefinition['page_name']], [
                'model_name' => $routeDefinition['module'], 'page_title' => $routeDefinition['title'],
                'page_rank' => (int) AllPage::query()->max('page_rank') + 1, 'status' => true,
            ]);
            UserWebPage::query()->updateOrCreate(['user_id' => 990109, 'page_id' => $page->id], [
                'created' => now(), 'modified' => now(), 'status' => 'Active',
            ]);
        }

        $user = new User();
        $user->forceFill(['id' => 990109, 'user_type' => 'User', 'name' => 'Operational Report Test User', 'email' => 'operational-reports@example.test', 'status' => 'Active']);
        $user->exists = true;
        $this->actingAs($user, 'web');
    }

    public function test_each_operational_report_renders_read_only_listing_without_business_data(): void
    {
        $reports = [
            'reports.pending-orders' => 'Pending Sale Order Report',
            'reports.production-status' => 'Work Order / Production Status Report',
            'reports.stock-movement' => 'Stock Movement Report',
            'reports.packaging' => 'Packaging Report',
            'reports.customer-dispatch' => 'Sales Challan / Customer Dispatch Report',
            'reports.purchase-receiving' => 'Purchase / Receiving Report',
            'reports.job-work' => 'Job Work Dispatch / Receive / Pending Report',
            'reports.inspection-rejection' => 'Inspection / Rejection Report',
        ];

        foreach ($reports as $route => $title) {
            $this->get(route($route))
                ->assertOk()
                ->assertSee($title)
                ->assertSee('operational-reports-page', false)
                ->assertSee('warehouse-filter-panel', false)
                ->assertSee('col-sm-2 col-xs-12', false)
                ->assertSee('name="from_date" id="from_date" placeholder="From Date"', false)
                ->assertSee('name="to_date" id="to_date" placeholder="To Date"', false)
                ->assertSee('dateFormat: "dd-mm-yy"', false)
                ->assertSee('maxDate: 0', false)
                ->assertSee('minLength: 0', false)
                ->assertDontSee('type="date"', false)
                ->assertSee('btn btn-success btn-sm btn-block', false)
                ->assertSee('frontend-assets/plugins/jQuery/jquery-1.12.4.min.js', false)
                ->assertSee('frontend-assets/plugins/jquery-ui-1.12.1/jquery-ui.min.js', false)
                ->assertSee('frontend-assets/bootstrap/js/bootstrap.min.js', false)
                ->assertSee('frontend-assets/dist/js/custom.js', false)
                ->assertSee('Search');
        }
    }
}
