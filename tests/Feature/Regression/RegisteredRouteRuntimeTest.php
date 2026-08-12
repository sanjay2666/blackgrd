<?php

namespace Tests\Feature\Regression;

use App\Models\Admin;
use App\Models\Concerns\BelongsToCompany;
use App\Models\User;
use App\Support\PermissionRegistry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RegisteredRouteRuntimeTest extends TestCase
{
    use DatabaseTransactions;

    private Admin $admin;

    private User $user;

    private int $companyId;

    private int $adminRoleId;

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::connection()->getDriverName() !== 'mysql') {
            $this->markTestSkipped('Registered route runtime tests require disposable MySQL.');
        }

        $database = DB::connection()->getDatabaseName();
        if ($database !== 'blackgrd_schema_testing') {
            $this->fail("Refusing registered route runtime tests on database [{$database}].");
        }

        $companyId = DB::table('companies')->insertGetId([
            'company_code' => 'ROUTE-AUDIT',
            'name' => 'Route Audit Company',
            'status' => 'Active',
        ]);
        $this->companyId = $companyId;

        $adminId = DB::table('users')->insertGetId([
            'user_type' => 'Admin',
            'name' => 'Route Audit Admin',
            'email' => 'route-audit-admin@example.test',
            'password' => bcrypt('route-audit-password'),
            'status' => 'Active',
        ]);
        $userId = DB::table('users')->insertGetId([
            'user_type' => 'User',
            'name' => 'Route Audit User',
            'email' => 'route-audit-user@example.test',
            'password' => bcrypt('route-audit-password'),
            'status' => 'Active',
        ]);

        DB::table('user_organization_access')->insert([
            ['user_id' => $adminId, 'company_id' => $companyId, 'is_default' => true, 'status' => 'Active'],
            ['user_id' => $userId, 'company_id' => $companyId, 'is_default' => true, 'status' => 'Active'],
        ]);

        $permissionIds = [];
        foreach (PermissionRegistry::all() as $permission) {
            $permissionIds[$permission['key']] = DB::table('permissions')->insertGetId([
                'permission_key' => $permission['key'],
                'resource' => $permission['resource'],
                'action' => $permission['action'],
                'category' => $permission['category'],
                'description' => $permission['description'],
                'is_critical' => $permission['critical'],
                'status' => 'Active',
            ]);
        }

        $adminRoleId = DB::table('roles')->insertGetId([
            'role_key' => 'super-admin',
            'name' => 'Super Admin',
            'scope' => 'System',
            'panel' => 'Admin',
            'status' => 'Active',
        ]);
        $this->adminRoleId = $adminRoleId;
        $userRoleId = DB::table('roles')->insertGetId([
            'company_id' => $companyId,
            'role_key' => 'route-audit-frontend',
            'name' => 'Route Audit Frontend',
            'scope' => 'Company',
            'panel' => 'Frontend',
            'status' => 'Active',
        ]);

        DB::table('role_permissions')->insert(collect($permissionIds)->map(
            fn (int $permissionId) => ['role_id' => $adminRoleId, 'permission_id' => $permissionId]
        )->values()->all());
        DB::table('role_permissions')->insert(collect(PermissionRegistry::frontendAssignable())->map(
            fn (string $key) => ['role_id' => $userRoleId, 'permission_id' => $permissionIds[$key]]
        )->values()->all());
        DB::table('user_role_assignments')->insert([
            ['principal_type' => 'Admin', 'principal_id' => $adminId, 'role_id' => $adminRoleId, 'company_id' => null, 'status' => 'Active'],
            ['principal_type' => 'User', 'principal_id' => $userId, 'role_id' => $userRoleId, 'company_id' => $companyId, 'status' => 'Active'],
        ]);

        $this->admin = Admin::query()->findOrFail($adminId);
        $this->user = User::query()->findOrFail($userId);
    }

    public function test_every_registered_route_has_a_resolvable_action_and_is_accounted_for(): void
    {
        $routes = collect(app('router')->getRoutes()->getRoutes());

        $this->assertCount(470, $routes);

        $failures = [];
        foreach ($routes as $route) {
            $action = $route->getActionName();
            if ($action === 'Closure') {
                continue;
            }

            [$class, $method] = str_contains($action, '@')
                ? explode('@', $action, 2)
                : [$action, '__invoke'];

            if (! class_exists($class) || ! method_exists($class, $method)) {
                $failures[] = implode(' ', $route->methods())." {$route->uri()} -> {$action}";
            }
        }

        $this->assertSame([], $failures, implode(PHP_EOL, $failures));
    }

    public function test_every_company_scoped_model_has_its_required_schema_contract(): void
    {
        $failures = [];

        foreach (File::allFiles(app_path('Models')) as $file) {
            $relative = str_replace(['/', '.php'], ['\\', ''], $file->getRelativePathname());
            $class = 'App\\Models\\'.$relative;
            if (! class_exists($class) || ! is_subclass_of($class, Model::class)) {
                continue;
            }
            if (! in_array(BelongsToCompany::class, class_uses_recursive($class), true)) {
                continue;
            }
            if (! config('features.workflow_definitions') && str_starts_with($class, 'App\\Models\\Workflow')) {
                continue;
            }

            $table = (new ($class))->getTable();
            if (! Schema::hasTable($table)) {
                $failures[] = "{$class}: table [{$table}] is missing";
            } elseif (! Schema::hasColumn($table, 'company_id')) {
                $failures[] = "{$class}: [{$table}.company_id] is missing";
            }
        }

        $this->assertSame([], $failures, implode(PHP_EOL, $failures));
    }

    public function test_full_page_blades_use_the_current_panel_shell(): void
    {
        $failures = [];
        $checked = 0;

        foreach (File::allFiles(resource_path('views/admin')) as $file) {
            $contents = File::get($file->getPathname());
            if (! str_contains(strtolower($contents), '<!doctype')) {
                continue;
            }
            $checked++;
            $relative = str_replace('\\', '/', $file->getRelativePathname());
            if ($relative === 'auth/login.blade.php') {
                continue;
            }
            foreach (['admin.common.head', 'admin.common.header', 'admin.common.sidebar', 'admin.common.footer'] as $include) {
                if (! str_contains($contents, $include)) {
                    $failures[] = "admin/{$relative}: missing {$include}";
                }
            }
            foreach (['class="content-header"', 'class="panel panel-bd'] as $templateContract) {
                if (! str_contains($contents, $templateContract)) {
                    $failures[] = "admin/{$relative}: missing template contract {$templateContract}";
                }
            }
            if (! str_contains($contents, 'admin.common.formfooterscript') && ! str_contains($contents, 'admin.common.footerscript')) {
                $failures[] = "admin/{$relative}: missing Admin footer scripts";
            }
        }

        foreach (File::allFiles(resource_path('views/frontend')) as $file) {
            $contents = File::get($file->getPathname());
            if (! str_contains(strtolower($contents), '<!doctype')) {
                continue;
            }
            $checked++;
            $relative = str_replace('\\', '/', $file->getRelativePathname());
            $isSpecialDocument = str_starts_with($relative, 'auth/')
                || $relative === 'home.blade.php'
                || preg_match('/(print|pdf|barcode)/i', $relative);
            if ($isSpecialDocument) {
                continue;
            }

            $hasHead = str_contains($contents, 'frontend.common.head') || str_contains($contents, 'common.head');
            $hasHeader = str_contains($contents, 'frontend.common.header') || str_contains($contents, 'common.header');
            $hasFooter = str_contains($contents, 'frontend.common.footer') || str_contains($contents, 'common.footer');
            if (! $hasHead || ! $hasHeader || ! $hasFooter) {
                $failures[] = "frontend/{$relative}: incomplete frontend shell";
            }
            if (str_contains($contents, 'content-wrapperd')) {
                $failures[] = "frontend/{$relative}: invalid content-wrapperd class";
            }
        }

        $this->assertSame(187, $checked);
        $this->assertSame([], $failures, implode(PHP_EOL, $failures));
    }

    public function test_role_form_uses_theme_compatible_checkboxes_and_persists_permissions(): void
    {
        $permissionKey = PermissionRegistry::companyAdminAssignable()[0];

        $response = $this->actingAs($this->admin, 'admin')->get(route('admin.roles.create'));

        $response->assertOk();
        $response->assertSee('checkbox checkbox-success', false);
        $this->assertMatchesRegularExpression(
            '/<input\s+id="(permission-[^"]+)"[^>]+name="permissions\[\]"[^>]*>\s*<label\s+for="\1">/',
            $response->getContent()
        );

        $this->post(route('admin.roles.store'), [
            'name' => 'Checkbox Interaction Role',
            'description' => 'Created by the role form interaction regression test.',
            'panel' => 'Admin',
            'status' => 'Active',
            'permissions' => [$permissionKey],
        ])->assertRedirect(route('admin.roles.index'));

        $roleId = DB::table('roles')->where('name', 'Checkbox Interaction Role')->value('id');
        $this->assertNotNull($roleId);
        $this->assertTrue(
            DB::table('role_permissions')
                ->join('permissions', 'permissions.id', '=', 'role_permissions.permission_id')
                ->where('role_permissions.role_id', $roleId)
                ->where('permissions.permission_key', $permissionKey)
                ->exists()
        );
    }

    public function test_every_safe_non_parameterized_get_route_completes_the_laravel_lifecycle(): void
    {
        $routes = collect(app('router')->getRoutes()->getRoutes())
            ->filter(fn (Route $route) => in_array('GET', $route->methods(), true))
            ->filter(fn (Route $route) => $route->parameterNames() === [])
            ->reject(fn (Route $route) => $this->isUnsafeGet($route))
            ->values();

        $guestRoutes = $routes->filter(fn (Route $route) => $this->usesMiddleware($route, 'guest:'));
        $adminRoutes = $routes->filter(fn (Route $route) => $this->usesMiddleware($route, 'auth:admin'));
        $frontendRoutes = $routes->filter(fn (Route $route) => $this->usesMiddleware($route, 'auth:web'))
            ->reject(fn (Route $route) => $this->usesMiddleware($route, 'auth:web,admin'));
        $sharedRoutes = $routes->filter(fn (Route $route) => $this->usesMiddleware($route, 'auth:web,admin'));
        $publicRoutes = $routes->reject(fn (Route $route) => $this->usesMiddleware($route, 'auth:') || $this->usesMiddleware($route, 'guest:'));

        $failures = [];
        $executed = 0;

        auth('admin')->logout();
        auth('web')->logout();
        $this->runRoutes($guestRoutes->merge($publicRoutes), $failures, $executed);

        $this->actingAs($this->admin, 'admin');
        $this->runRoutes($adminRoutes->merge($sharedRoutes), $failures, $executed);

        auth('admin')->logout();
        $this->actingAs($this->user, 'web');
        $this->runRoutes($frontendRoutes->merge($sharedRoutes), $failures, $executed);

        $this->assertGreaterThan(150, $executed);
        $this->assertSame([], $failures, implode(PHP_EOL.PHP_EOL, $failures));
    }

    public function test_admin_parameterized_get_routes_render_with_valid_records(): void
    {
        $id = $this->seedAdminRouteFixtures();
        $encoded = fn (string $key): string => enc($id[$key]);

        $urls = [
            route('admin.all-pages.edit', $encoded('all_page'), false),
            route('admin.audit-logs.show', $id['audit_log'], false),
            route('admin.branches.edit', $id['branch'], false),
            route('admin.chemicals.edit', $encoded('chemical'), false),
            route('admin.colours.edit', $encoded('colour'), false),
            route('admin.cotings.edit', $encoded('coting'), false),
            route('admin.couriers.edit', $encoded('courier'), false),
            route('admin.customers.edit', $id['customer'], false),
            route('admin.customers.addresses.create', $id['customer'], false),
            route('admin.customers.addresses.edit', [$id['customer'], $id['customer_address']], false),
            route('admin.departments.edit', $id['department'], false),
            route('admin.dyeing-colours.edit', $encoded('dyeing_colour'), false),
            route('admin.employees.edit', $id['employee'], false),
            route('admin.fabric-fault-reasons.edit', $encoded('fabric_fault_reason'), false),
            route('admin.fabric-qualities.edit', $encoded('fabric_quality'), false),
            route('admin.factories.edit', $id['factory'], false),
            route('admin.financial-years.edit', $id['financial_year'], false),
            route('admin.gst-rates.edit', $encoded('gst_rate'), false),
            route('admin.hsn-codes.edit', $encoded('hsn_code'), false),
            route('admin.individuals.edit', $encoded('individual'), false),
            route('admin.item-types.edit', $encoded('item_type'), false),
            route('admin.item-yarn-requirements.edit', $encoded('item_yarn_requirement'), false),
            route('admin.items.manage-yarn', $encoded('item'), false),
            route('admin.items.edit', $encoded('item'), false),
            route('admin.machine-capacities.edit', $id['machine_capacity'], false),
            route('admin.machines.edit', $id['machine'], false),
            route('admin.notifications.edit', $encoded('notification'), false),
            route('admin.office-ips.edit', $id['office_ip'], false),
            route('admin.packaging-types.edit', $encoded('packaging_type'), false),
            route('admin.printing-designs.edit', $encoded('printing_design'), false),
            route('admin.process-items.edit', $id['process_item'], false),
            route('admin.roles.assign', $id['role'], false),
            route('admin.roles.edit', $id['role'], false),
            route('admin.shifts.edit', $id['shift'], false),
            route('admin.states.edit', $encoded('state'), false),
            route('admin.transporters.edit', $id['transporter'], false),
            route('admin.transporters.addresses.create', $id['transporter'], false),
            route('admin.transporters.addresses.edit', [$id['transporter'], $id['transporter_address']], false),
            route('admin.unit-types.edit', $encoded('unit_type'), false),
            route('admin.user-web-pages.edit', $encoded('user_web_page'), false),
            route('admin.users.department-access', $this->user->id, false),
            route('admin.users.edit', $this->user->id, false),
            route('admin.users.permissions.edit', $this->user->id, false),
            route('admin.vendors.edit', $id['vendor'], false),
            route('admin.vendors.addresses.create', $id['vendor'], false),
            route('admin.vendors.addresses.edit', [$id['vendor'], $id['vendor_address']], false),
            route('admin.ware-house-compartments.edit', $encoded('warehouse_compartment'), false),
            route('admin.warehouses.edit', $encoded('warehouse'), false),
        ];

        $this->actingAs($this->admin, 'admin');
        $failures = [];
        foreach ($urls as $url) {
            $response = $this->get($url);
            if ($response->getStatusCode() >= 500 || in_array($response->getStatusCode(), [403, 404], true)) {
                $failures[] = "GET {$url} -> {$response->getStatusCode()}";
            }
        }

        $this->assertCount(48, $urls);
        $this->assertSame([], $failures, implode(PHP_EOL, $failures));
    }

    public function test_frontend_parameterized_read_routes_render_with_valid_records(): void
    {
        $id = $this->seedFrontendRouteFixtures();
        $encoded = fn (string $key): string => enc($id[$key]);

        auth('web')->logout();
        $guestResponse = $this->get(route('password.reset', ['token' => 'route-audit-token'], false));
        $this->assertLessThan(500, $guestResponse->getStatusCode());

        $urls = [
            route('edit-purchaseorder', $encoded('purchase_order'), false),
            '/get-reason-history/'.$id['sale_order_item'],
            '/get-work-reason-history/'.$id['work_order'],
            route('mill_dispatch_received_items_in_warehouse', $encoded('stock_mill_dispatch'), false),
            route('mill_dispatch_received_weaving_items_in_warehouse', $encoded('stock_mill_dispatch'), false),
            route('print-job-card-traceability', enc($id['requirement_lot']), false),
            route('print-mill-dispatch-chalan', $encoded('stock_mill_dispatch'), false),
            route('print-mill-dispatch-received-chalan', $encoded('stock_mill_dispatch'), false),
            route('print-purchaseorder', $encoded('purchase_order'), false),
            route('saleorders.print', $encoded('sale_order'), false),
            route('receive-work-item', $id['work_inspection'], false),
            '/sale-order/ajax-details/'.$encoded('sale_order'),
            route('sale-orders.edit', $encoded('sale_order'), false),
            route('show-accepted-department-return-request', $id['department_return'], false),
            route('show-purchase', $encoded('purchase'), false),
            route('show-saleorder-workorder-details', $encoded('sale_order'), false),
            route('show-stock-details-inline', $encoded('warehouse_balance_item'), false),
            route('show-stock-details-listing', $encoded('warehouse_balance_item'), false),
            route('start-requisition-process', $encoded('work_order'), false),
            route('warehouse-stock-document', $encoded('warehouse_item_stock'), false),
        ];

        $this->actingAs($this->user, 'web');
        $failures = [];
        foreach ($urls as $url) {
            $response = $this->get($url);
            if ($response->getStatusCode() >= 500 || in_array($response->getStatusCode(), [403, 404], true)) {
                $failures[] = "GET {$url} -> {$response->getStatusCode()}";
            }
        }

        $this->assertCount(20, $urls);
        $this->assertSame([], $failures, implode(PHP_EOL, $failures));
    }

    public function test_every_mutating_or_unsafe_route_completes_without_a_server_error_on_disposable_data(): void
    {
        $routes = collect(app('router')->getRoutes()->getRoutes())
            ->filter(function (Route $route): bool {
                $hasGet = in_array('GET', $route->methods(), true);

                return ! $hasGet || $this->isUnsafeGet($route);
            })
            ->values();

        $this->actingAs($this->admin, 'admin');
        $this->actingAs($this->user, 'web');

        $failures = [];
        foreach ($routes as $route) {
            $method = collect($route->methods())
                ->first(fn (string $candidate) => ! in_array($candidate, ['HEAD', 'OPTIONS'], true)) ?? 'GET';
            $uri = '/'.ltrim((string) preg_replace('/\{[^}]+\??\}/', '999999999', $route->uri()), '/');

            if ($this->usesMiddleware($route, 'guest:web')) {
                auth('web')->logout();
            }
            if ($this->usesMiddleware($route, 'guest:admin')) {
                auth('admin')->logout();
            }

            $response = $this->call($method, $uri);
            if ($response->getStatusCode() >= 500) {
                $failures[] = "{$method} {$uri} -> {$response->getStatusCode()} ({$route->getActionName()})";
            }

            if ($this->usesMiddleware($route, 'guest:web')) {
                $this->actingAs($this->user, 'web');
            }
            if ($this->usesMiddleware($route, 'guest:admin')) {
                $this->actingAs($this->admin, 'admin');
            }
        }

        $this->assertGreaterThan(200, $routes->count());
        $this->assertSame([], $failures, implode(PHP_EOL, $failures));
    }

    /** @param iterable<int, Route> $routes @param list<string> $failures */
    private function runRoutes(iterable $routes, array &$failures, int &$executed): void
    {
        foreach ($routes as $route) {
            $executed++;
            $uri = '/'.ltrim($route->uri(), '/');
            $response = $this->get($uri);
            $status = $response->getStatusCode();

            $unexpectedNotFound = $status === 404 && ! str_starts_with($route->uri(), 'ajax_script/');
            if ($status >= 500 || $status === 403 || $unexpectedNotFound) {
                $content = trim(strip_tags($response->getContent()));
                $failures[] = "GET {$uri} -> {$status}\n".mb_substr(preg_replace('/\s+/', ' ', $content) ?? '', 0, 700);
            }
        }
    }

    private function usesMiddleware(Route $route, string $prefix): bool
    {
        return collect($route->gatherMiddleware())->contains(
            fn (string $middleware) => str_starts_with($middleware, $prefix)
        );
    }

    private function isUnsafeGet(Route $route): bool
    {
        $signature = strtolower($route->uri().' '.$route->getActionName());

        return collect([
            'accept', 'deny', 'delete', 'print-job-card', 'print-warehouse-item-requirement',
            'print-workorder-gatepass', 'refreshwarehouseitem', 'shiftworkorder',
            'updatecoatingrequirement', 'updatewarehousecomp', 'update-vendor',
            'update_mtr_received_status',
        ])->contains(fn (string $needle) => str_contains($signature, $needle));
    }

    /** @return array<string, int> */
    private function seedAdminRouteFixtures(): array
    {
        $id = [];
        $id['state'] = $this->insertMinimal('states', ['name' => 'Route State', 'status' => 'Active']);
        $id['branch'] = $this->insertMinimal('branches', ['company_id' => $this->companyId, 'branch_code' => 'ROUTE-BR', 'name' => 'Route Branch', 'kind' => 'head_office', 'status' => 'Active']);
        $id['factory'] = $this->insertMinimal('factories', ['company_id' => $this->companyId, 'branch_id' => $id['branch'], 'factory_code' => 'ROUTE-FA', 'name' => 'Route Factory', 'status' => 'Active']);
        $id['department'] = $this->insertMinimal('departments', ['company_id' => $this->companyId, 'factory_id' => $id['factory'], 'department_name' => 'Route Department', 'status' => 'Active']);
        $id['process_item'] = $this->insertMinimal('process_items', ['company_id' => $this->companyId, 'department_id' => $id['department'], 'process_name' => 'Route Process', 'status' => 'Active']);
        $id['unit_type'] = $this->insertMinimal('unit_type', ['unit_type_name' => 'Route Unit', 'status' => 'Active']);
        $id['item_type'] = $this->insertMinimal('item_type', ['company_id' => $this->companyId, 'item_type_name' => 'Route Type', 'unit_type_id' => $id['unit_type'], 'status' => 'Active']);
        $chemicalType = $this->insertMinimal('item_type', ['company_id' => $this->companyId, 'item_type_name' => 'Chemical', 'unit_type_id' => $id['unit_type'], 'status' => 'Active']);
        $id['item'] = $this->insertMinimal('items', ['company_id' => $this->companyId, 'item_name' => 'Route Item', 'item_code' => 'ROUTE-ITEM', 'item_type_id' => $id['item_type'], 'unit_type_id' => $id['unit_type'], 'status' => 'Active']);
        $id['chemical'] = $this->insertMinimal('items', ['company_id' => $this->companyId, 'item_name' => 'Route Chemical', 'item_code' => 'ROUTE-CHEM', 'item_type_id' => $chemicalType, 'unit_type_id' => $id['unit_type'], 'status' => 'Active']);
        $id['colour'] = $this->insertMinimal('colours', ['company_id' => $this->companyId, 'name' => 'Route Colour', 'status' => 'Active']);
        $id['dyeing_colour'] = $this->insertMinimal('dyeing_colours', ['company_id' => $this->companyId, 'colour_id' => $id['colour'], 'name' => 'Route Shade', 'status' => 'Active']);
        $id['coting'] = $this->insertMinimal('cotings', ['company_id' => $this->companyId, 'name' => 'Route Coating', 'code' => 'ROUTE-COAT', 'status' => 'Active']);
        $id['courier'] = $this->insertMinimal('couriers', ['courier_name' => 'Route Courier', 'status' => 'Active']);
        foreach (['individual' => 'master', 'employee' => 'employee', 'customer' => 'customers', 'vendor' => 'vendors', 'transporter' => 'transport'] as $key => $type) {
            $id[$key] = $this->insertMinimal('individuals', ['company_id' => $this->companyId, 'name' => 'Route '.ucfirst($key), 'type' => $type, 'email' => "route-{$key}@example.test", 'status' => 'Active']);
        }
        foreach (['customer', 'vendor', 'transporter'] as $key) {
            $id[$key.'_address'] = $this->insertMinimal('individual_address', ['individual_id' => $id[$key], 'address_type' => 'b', 'address_1' => 'Route Address', 'city' => 'Route City', 'status' => 'Active']);
        }
        $id['fabric_fault_reason'] = $this->insertMinimal('fabric_fault_reasons', ['company_id' => $this->companyId, 'process_id' => $id['process_item'], 'reason' => 'Route Fault', 'status' => 'Active']);
        $id['fabric_quality'] = $this->insertMinimal('fabric_qualities', ['company_id' => $this->companyId, 'quality_name' => 'Route Quality', 'status' => 'Active']);
        $id['financial_year'] = $this->insertMinimal('financial_years', ['company_id' => $this->companyId, 'status' => 'Active']);
        $id['gst_rate'] = $this->insertMinimal('gst_rates', ['gst_rate' => 5, 'status' => 'Active']);
        $id['hsn_code'] = $this->insertMinimal('hsn_codes', ['hsn_code' => 'ROUTE-HSN', 'gst_rate_id' => $id['gst_rate'], 'status' => 'Active']);
        $id['item_yarn_requirement'] = $this->insertMinimal('item_yarn_requirements', ['item_id' => $id['item'], 'yarn_id' => $id['item'], 'process_id' => $id['process_item'], 'status' => 'Active']);
        $id['machine'] = $this->insertMinimal('machines', ['company_id' => $this->companyId, 'factory_id' => $id['factory'], 'department_id' => $id['department'], 'process_wise' => $id['process_item'], 'name' => 'Route Machine', 'status' => 'Active']);
        $id['machine_capacity'] = $this->insertMinimal('machine_capacities', ['company_id' => $this->companyId, 'machine_id' => $id['machine'], 'unit_type_id' => $id['unit_type'], 'status' => 'Active']);
        $id['notification'] = $this->insertMinimal('notifications', ['company_id' => $this->companyId, 'status' => 'Active']);
        $id['office_ip'] = $this->insertMinimal('office_ips', []);
        $id['packaging_type'] = $this->insertMinimal('packaging_types', ['company_id' => $this->companyId, 'status' => 'Active']);
        $id['printing_design'] = $this->insertMinimal('printing_designs', ['company_id' => $this->companyId, 'design_name' => 'Route Design', 'status' => 'Active']);
        $id['shift'] = $this->insertMinimal('shifts', ['company_id' => $this->companyId, 'factory_id' => $id['factory'], 'status' => 'Active']);
        $id['user_web_page'] = $this->insertMinimal('user_web_pages', ['user_id' => $this->user->id, 'status' => 'Active']);
        $id['warehouse'] = $this->insertMinimal('warehouses', ['company_id' => $this->companyId, 'factory_id' => $id['factory'], 'warehouse_name' => 'Route Warehouse', 'status' => 'Active']);
        $id['warehouse_compartment'] = $this->insertMinimal('warehouse_compartments', ['warehouse_id' => $id['warehouse'], 'status' => 'Active']);
        $id['all_page'] = $this->insertMinimal('all_pages', ['status' => 1]);
        $id['audit_log'] = $this->insertMinimal('audit_logs', ['actor_type' => 'Admin', 'actor_id' => $this->admin->id]);
        $id['role'] = $this->insertMinimal('roles', [
            'company_id' => $this->companyId,
            'role_key' => 'route-audit-admin-company',
            'name' => 'Route Audit Company Admin',
            'scope' => 'Company',
            'panel' => 'Admin',
            'status' => 'Active',
        ]);

        return $id;
    }

    /** @return array<string, int|string> */
    private function seedFrontendRouteFixtures(): array
    {
        $id = $this->seedAdminRouteFixtures();

        DB::table('users')->where('id', $this->user->id)->update(['individual_id' => $id['employee']]);
        $this->user->refresh();
        $this->insertMinimal('user_department_access', [
            'user_id' => $this->user->id,
            'company_id' => $this->companyId,
            'department_id' => $id['department'],
            'status' => 'Active',
        ]);

        $traceProcess = $this->insertMinimal('process_items', [
            'company_id' => $this->companyId,
            'department_id' => $id['department'],
            'process_name' => 'Dyeing',
            'status' => 'Active',
        ]);
        if ($traceProcess !== 3 && ! DB::table('process_items')->where('id', 3)->exists()) {
            DB::table('process_items')->where('id', $traceProcess)->update(['id' => 3]);
            $traceProcess = 3;
        }

        $id['sale_order'] = $this->insertMinimal('sale_orders', [
            'customer_id' => $id['customer'],
            'order_by_employee' => $id['employee'],
            'sale_order_number' => 'ROUTE-SO-1',
            'status' => 'Active',
        ]);
        $id['sale_order_item'] = $this->insertMinimal('sale_order_items', [
            'sale_order_id' => $id['sale_order'],
            'company_id' => $this->companyId,
            'item_id' => $id['item'],
            'item_type_id' => $id['item_type'],
            'unit_type_id' => $id['unit_type'],
            'item_name' => 'Route Sale Item',
            'status' => 'Active',
        ]);
        $id['purchase_order'] = $this->insertMinimal('purchase_orders', [
            'vendor_id' => $id['vendor'],
            'is_deleted' => 'No',
            'status' => 'Active',
        ]);
        $id['purchase_order_item'] = $this->insertMinimal('purchase_order_items', [
            'purchase_id' => $id['purchase_order'],
            'item_id' => $id['item'],
            'item_type_id' => $id['item_type'],
            'status' => 'Active',
        ]);
        $id['purchase'] = $this->insertMinimal('purchases', [
            'purchase_order_id' => $id['purchase_order'],
            'vendor_id' => $id['vendor'],
            'status' => 'Active',
        ]);

        $id['work_order'] = $this->insertMinimal('work_orders', [
            'company_id' => $this->companyId,
            'process_type' => 'D',
            'user_id' => $this->user->id,
            'process_type_id' => $traceProcess,
            'item_type_id' => $id['item_type'],
            'item_id' => $id['item'],
            'item_name' => 'Route Work Item',
            'process_started_by' => $id['employee'],
            'process_ended_by' => $id['employee'],
            'process_inspected_by' => $id['employee'],
            'process_started_remarks' => '',
            'process_ended_remarks' => '',
            'status' => 'Active',
        ]);
        $id['work_order_item'] = $this->insertMinimal('work_order_items', [
            'work_order_id' => $id['work_order'],
            'customer_id' => $id['customer'],
            'sale_order_id' => $id['sale_order'],
            'sale_order_item_id' => $id['sale_order_item'],
            'item_id' => $id['item'],
            'item_type_id' => $id['item_type'],
            'unit_type_id' => $id['unit_type'],
            'status' => 'Active',
        ]);
        $id['requirement_lot'] = 8675309;
        $id['work_process_requirement'] = $this->insertMinimal('work_process_requirements', [
            'company_id' => $this->companyId,
            'work_order_id' => $id['work_order'],
            'item_id' => $id['item'],
            'item_type_id' => $id['item_type'],
            'unit_type_id' => $id['unit_type'],
            'process_type_id' => 3,
            'req_fabric_type' => 1,
            'req_lot_no' => (string) $id['requirement_lot'],
            'is_accept' => 1,
            'status' => 'Active',
        ]);

        $id['stock_mill_dispatch'] = $this->insertMinimal('stock_mill_dispatches', [
            'company_id' => $this->companyId,
            'vendor_id' => $id['vendor'],
            'process_type' => $traceProcess,
            'item_id' => $id['item'],
            'status' => 'Active',
        ]);
        $this->insertMinimal('stock_mill_dispatch_items', [
            'stock_mill_dispatch_id' => $id['stock_mill_dispatch'],
            'item_id' => $id['item'],
            'item_type_id' => $id['item_type'],
            'work_order_id' => $id['work_order'],
            'status' => 'Active',
        ]);

        $id['warehouse_balance_item'] = $this->insertMinimal('warehouse_balance_items', [
            'company_id' => $this->companyId,
            'warehouse_id' => $id['warehouse'],
            'ware_comp_id' => $id['warehouse_compartment'],
            'item_id' => $id['item'],
            'item_type_id' => $id['item_type'],
            'unit_type_id' => $id['unit_type'],
            'status' => 'Active',
        ]);
        $id['warehouse_item'] = $this->insertMinimal('warehouse_in_items', [
            'company_id' => $this->companyId,
            'process_type_id' => $traceProcess,
            'warehouse_id' => $id['warehouse'],
            'ware_comp_id' => $id['warehouse_compartment'],
            'receiver_id' => $id['employee'],
            'item_id' => $id['item'],
            'item_type_id' => $id['item_type'],
            'unit_type_id' => $id['unit_type'],
            'work_order_id' => $id['work_order'],
            'status' => 'Active',
        ]);
        $id['warehouse_item_stock'] = $this->insertMinimal('warehouse_item_stocks', [
            'company_id' => $this->companyId,
            'warehouse_item_id' => $id['warehouse_item'],
            'warehouse_id' => $id['warehouse'],
            'ware_comp_id' => $id['warehouse_compartment'],
            'item_id' => $id['item'],
            'item_type_id' => $id['item_type'],
            'unit_type_id' => $id['unit_type'],
            'work_order_id' => $id['work_order'],
            'status' => 'Active',
        ]);
        $this->insertMinimal('warehouse_item_stock_files', [
            'wis_id' => $id['warehouse_item_stock'],
            'invoice_copy_file' => 'uploads/route-audit.pdf',
            'status' => 'Active',
        ]);

        $id['department_return'] = $this->insertMinimal('department_returns', [
            'company_id' => $this->companyId,
            'work_order_id' => $id['work_order'],
            'employee_id' => $id['employee'],
            'work_pro_req_id' => $id['work_process_requirement'],
            'process_type_id' => $traceProcess,
            'item_type_id' => $id['item_type'],
            'status' => 'accepted',
        ]);
        $this->insertMinimal('department_return_requests', [
            'company_id' => $this->companyId,
            'depart_reqst_id' => $id['department_return'],
            'work_order_id' => $id['work_order'],
            'employee_id' => $id['employee'],
            'work_pro_req_id' => $id['work_process_requirement'],
            'item_id' => $id['item'],
            'status' => 'accepted',
        ]);

        $id['work_inspection'] = $this->insertMinimal('work_inspections', [
            'company_id' => $this->companyId,
            'work_order_id' => $id['work_order'],
            'work_process_req_id' => $id['work_process_requirement'],
            'item_id' => $id['item'],
            'is_deleted' => 0,
            'status' => 'Active',
        ]);
        $this->insertMinimal('work_inspection_details', [
            'work_insp_id' => $id['work_inspection'],
            'work_order_id' => $id['work_order'],
            'item_id' => $id['item'],
            'status' => 'Active',
        ]);
        $this->insertMinimal('gate_passes', [
            'company_id' => $this->companyId,
            'inspection_id' => $id['work_inspection'],
            'work_order_id' => $id['work_order'],
            'sale_order_item_id' => $id['sale_order_item'],
            'item_id' => $id['item'],
            'item_type_id' => $id['item_type'],
            'unit_type_id' => $id['unit_type'],
            'is_deleted' => 0,
            'status' => 'Active',
        ]);

        return $id;
    }

    /** @param array<string, mixed> $overrides */
    private function insertMinimal(string $table, array $overrides): int
    {
        $attributes = $overrides;
        if (Schema::hasColumn($table, 'company_id') && ! array_key_exists('company_id', $attributes)) {
            $attributes['company_id'] = $this->companyId;
        }

        foreach (Schema::getColumns($table) as $column) {
            $name = $column['name'];
            if (array_key_exists($name, $attributes) || $column['auto_increment'] || $column['nullable'] || $column['default'] !== null) {
                continue;
            }

            $attributes[$name] = match ($column['type_name']) {
                'varchar', 'char', 'text', 'tinytext', 'mediumtext', 'longtext' => 'R1',
                'date' => now()->toDateString(),
                'datetime', 'timestamp' => now(),
                'decimal', 'double', 'float' => 1,
                'enum' => preg_match("/enum\\('([^']+)'/", $column['type'], $match) ? $match[1] : 'Active',
                default => 1,
            };
        }

        return (int) DB::table($table)->insertGetId($attributes);
    }
}
