<?php

namespace Tests\Feature\Regression;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ActiveRouteResponseTest extends TestCase
{
    use DatabaseTransactions;

    private string $marker;

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::connection()->getDriverName() !== 'mysql') {
            $this->markTestSkipped('Route response integration tests require disposable MySQL.');
        }

        $database = DB::connection()->getDatabaseName();
        if ($database !== 'blackgrd_schema_testing') {
            $this->fail("Refusing route response integration tests on database [{$database}].");
        }

        $this->marker = 'route-'.bin2hex(random_bytes(5));
        $companyId = DB::table('companies')->where('status', 'Active')->value('id');
        if ($companyId === null) {
            $companyId = DB::table('companies')->insertGetId([
                'name' => "Route Response Company {$this->marker}",
                'status' => 'Active',
            ]);
        }
        DB::table('users')->insertOrIgnore([
            'id' => 990002,
            'user_type' => 'User',
            'name' => 'Route Response User',
            'email' => 'route-response@example.test',
            'password' => 'test-password',
            'status' => 'Active',
        ]);
        DB::table('user_organization_access')->insert([
            'user_id' => 990002,
            'company_id' => $companyId,
            'is_default' => true,
            'status' => 'Active',
        ]);
        foreach (['employees.view', 'masters.view', 'sale-orders.view'] as $permissionKey) {
            $permissionId = DB::table('permissions')->where('permission_key', $permissionKey)->value('id');
            if ($permissionId === null) {
                [$resource, $action] = explode('.', $permissionKey, 2);
                $permissionId = DB::table('permissions')->insertGetId([
                    'permission_key' => $permissionKey,
                    'resource' => $resource,
                    'action' => $action,
                    'category' => 'test',
                    'status' => 'Active',
                ]);
            }
            DB::table('user_permission_overrides')->insert([
                'user_id' => 990002,
                'permission_id' => $permissionId,
                'effect' => 'Allow',
                'status' => 'Active',
            ]);
        }
        $this->actingAs($this->transientUser(), 'web');
    }

    public function test_employee_item_and_sale_order_autocomplete_json_contracts(): void
    {
        DB::table('individuals')->insert([
            [
                'name' => "Active employee {$this->marker}",
                'type' => 'employee',
                'email' => "{$this->marker}@example.test",
                'gstin' => 'GST-ACTIVE',
                'company_id' => $this->companyId(),
                'status' => 'Active',
            ],
            [
                'name' => "Inactive employee {$this->marker}",
                'type' => 'employee',
                'email' => "inactive-{$this->marker}@example.test",
                'gstin' => null,
                'company_id' => $this->companyId(),
                'status' => 'Inactive',
            ],
        ]);

        $itemId = DB::table('items')->insertGetId([
            'item_name' => "Active item {$this->marker}",
            'item_code' => "I-{$this->marker}",
            'company_id' => $this->companyId(),
            'status' => 'Active',
        ]);
        DB::table('items')->insert([
            'item_name' => "Inactive item {$this->marker}",
            'item_code' => "X-{$this->marker}",
            'company_id' => $this->companyId(),
            'status' => 'Inactive',
        ]);

        $saleOrderId = DB::table('sale_orders')->insertGetId([
            'sale_order_number' => "SO-{$this->marker}",
            'order_by_employee' => 1,
            'company_id' => $this->companyId(),
            'status' => 'Active',
        ]);
        DB::table('sale_orders')->insert([
            'sale_order_number' => "SO-INACTIVE-{$this->marker}",
            'order_by_employee' => 1,
            'company_id' => $this->companyId(),
            'status' => 'Inactive',
        ]);

        $employeeResponse = $this->getJson('/list_employee?term='.urlencode($this->marker));
        $employeeResponse->assertOk()->assertJsonCount(1);
        $this->assertSame(['id', 'name', 'gstin'], array_keys($employeeResponse->json('0')));

        $itemResponse = $this->getJson('/list_item?term='.urlencode($this->marker));
        $itemResponse->assertOk()->assertJsonCount(1)->assertJsonPath('0.item_id', $itemId);
        $this->assertSame(['item_id', 'item_name', 'item_code'], array_keys($itemResponse->json('0')));

        $saleResponse = $this->getJson('/list_saleOrderNumer?term='.urlencode("SO-{$this->marker}"));
        $saleResponse->assertOk()->assertJsonCount(1)
            ->assertJsonPath('0.sale_order_id', $saleOrderId)
            ->assertJsonPath('0.sale_order_number', "SO-{$this->marker}");
        $this->assertSame(['sale_order_id', 'sale_order_number'], array_keys($saleResponse->json('0')));

        $this->assertTrue(
            collect($this->getJson('/list_saleOrderNumer?term='.$saleOrderId)->json())
                ->contains('sale_order_id', $saleOrderId),
        );
    }

    public function test_billing_and_shipping_address_html_contracts_are_escaped(): void
    {
        $stateId = DB::table('states')->insertGetId([
            'name' => "State {$this->marker}",
            'status' => 'Active',
        ]);
        $individualId = DB::table('individuals')->insertGetId([
            'name' => "Customer {$this->marker}",
            'type' => 'customers',
            'company_id' => $this->companyId(),
            'status' => 'Active',
        ]);
        DB::table('companies')->insert([
            'name' => "Company {$this->marker}",
            'state_id' => $stateId,
            'status' => 'Active',
        ]);

        $billingId = $this->insertAddress($individualId, $stateId, 'b', true);
        $shippingId = $this->insertAddress($individualId, $stateId, 's', true);
        $this->insertAddress($individualId, $stateId, 'b', false, 'Inactive');

        $billing = $this->get('/ajax_script/search_customer_bill_address?individualId='.$individualId);
        $billing->assertOk()->assertHeader('Content-Type', 'text/html; charset=UTF-8');
        $this->assertStringContainsString('name="ind_add_id"', $billing->getContent());
        $this->assertStringContainsString('value="'.$billingId.'"', $billing->getContent());
        $this->assertStringContainsString('name="address"', $billing->getContent());
        $this->assertStringContainsString('calcAddress('.$stateId.')', $billing->getContent());
        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $billing->getContent());
        $this->assertStringNotContainsString('<script>alert(1)</script>', $billing->getContent());

        $shipping = $this->get('/ajax_script/search_customer_ship_address?individualId='.$individualId);
        $shipping->assertOk()->assertHeader('Content-Type', 'text/html; charset=UTF-8');
        $this->assertStringContainsString('name="ind_add_id_ship"', $shipping->getContent());
        $this->assertStringContainsString('value="'.$shippingId.'"', $shipping->getContent());
        $this->assertStringContainsString('name="shiping_address"', $shipping->getContent());
        $this->assertStringContainsString('calcAddresss('.$stateId.')', $shipping->getContent());
    }

    public function test_address_routes_reject_invalid_ids(): void
    {
        $this->getJson('/ajax_script/search_customer_bill_address?individualId=invalid')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('individualId');
        $this->getJson('/ajax_script/search_customer_ship_address?individualId=0')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('individualId');
    }

    public function test_common_routes_accept_user_and_admin_guards(): void
    {
        $this->getJson('/list_employee?term=no-matching-employee')->assertOk();


        auth('web')->logout();
        $this->actingAs($this->transientUser(), 'admin');

        $this->getJson('/list_employee?term=no-matching-employee')->assertOk();
    }

    private function insertAddress(
        int $individualId,
        int $stateId,
        string $type,
        bool $default,
        string $status = 'Active',
    ): int {
        return DB::table('individual_address')->insertGetId([
            'individual_id' => $individualId,
            'address_type' => $type,
            'address_1' => '<script>alert(1)</script>',
            'address_2' => "Address {$this->marker}",
            'state_id' => $stateId,
            'city' => 'Test City',
            'zip_code' => '123456',
            'default_address' => $default,
            'created' => now(),
            'status' => $status,
        ]);
    }

    private function transientUser(): User
    {
        $user = new User();
        $user->forceFill([
            'id' => 990002,
            'user_type' => 'User',
            'name' => 'Route Response User',
            'email' => 'route-response@example.test',
            'status' => 'Active',
        ]);
        $user->exists = true;

        return $user;
    }

    private function companyId(): int
    {
        return (int) DB::table('user_organization_access')->where('user_id', 990002)->value('company_id');
    }
}
