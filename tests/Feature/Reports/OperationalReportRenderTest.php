<?php

namespace Tests\Feature\Reports;

use App\Models\User;
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
        $permissionId = DB::table('permissions')->where('permission_key', 'reports.view')->value('id');
        $this->assertNotNull($permissionId, 'The canonical reports.view permission is required.');
        DB::table('user_permission_overrides')->insert([
            'user_id' => 990109,
            'permission_id' => $permissionId,
            'effect' => 'Allow',
            'status' => 'Active',
        ]);

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
            $this->get(route($route))->assertOk()->assertSee($title)->assertSee('Search');
        }
    }
}
