<?php

namespace Tests\Feature\Authorization;

use App\Models\User;
use App\Services\CurrentOrganizationContext;
use App\Services\DepartmentAccessService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class DepartmentProcessMappingIntegrationTest extends TestCase
{
    use DatabaseTransactions;

    private int $companyId;

    private User $user;

    /** @var array<string, int> */
    private array $processIds;

    /** @var array<string, int> */
    private array $departmentIds;

    private DepartmentAccessService $access;

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::connection()->getDriverName() !== 'mysql') {
            $this->markTestSkipped('Department/Process mapping integration tests require disposable MySQL.');
        }
        if (DB::connection()->getDatabaseName() !== 'blackgrd_schema_testing') {
            $this->fail('Refusing Department/Process mapping integration tests outside blackgrd_schema_testing.');
        }
        foreach (['companies', 'departments', 'process_items', 'users', 'user_organization_access', 'user_department_access'] as $table) {
            if (! Schema::hasTable($table)) {
                $this->fail("Department/Process mapping tests require [{$table}] in disposable MySQL.");
            }
        }

        $marker = bin2hex(random_bytes(5));
        $this->companyId = DB::table('companies')->insertGetId([
            'company_code' => 'DPM-'.$marker,
            'name' => 'Department Process Mapping '.$marker,
            'status' => 'Active',
        ]);
        $this->useCompany($this->companyId);
        DB::table('departments')->insert([
            'company_id' => $this->companyId,
            'department_name' => 'Warehose',
            'status' => 'Active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->processIds = collect([
            'Warping' => 'WRP', 'Weaving' => 'WEV', 'Dyeing' => 'DYE', 'Coating' => 'COA',
            'Packaging' => 'PKG', 'D-Printing' => 'DPR', 'C-Printing' => 'CPR',
        ])->mapWithKeys(function (string $code, string $name) use ($marker): array {
            return [$name => DB::table('process_items')->insertGetId([
                'company_id' => $this->companyId,
                'process_name' => $name,
                'short_code' => $code.'-'.$marker,
                'entry_name' => $name.' input',
                'output_name' => $name.' output',
                'process_sl_no_last' => 0,
                'status' => 'Active',
            ])];
        })->all();

        /** @var Migration $migration */
        $migration = require base_path('database/migrations/2026_08_13_000001_complete_department_process_mappings.php');
        $migration->up();
        $this->departmentIds = DB::table('departments')->where('company_id', $this->companyId)->where('status', 'Active')
            ->pluck('id', 'department_name')->map(fn ($id): int => (int) $id)->all();
        ksort($this->departmentIds);

        $userId = DB::table('users')->insertGetId([
            'user_type' => 'User',
            'name' => 'Department Access '.$marker,
            'email' => "department-access-{$marker}@example.test",
            'password' => 'not-used-in-this-test',
            'status' => 'Active',
        ]);
        DB::table('user_organization_access')->insert([
            'user_id' => $userId,
            'company_id' => $this->companyId,
            'is_default' => true,
            'status' => 'Active',
        ]);
        $this->user = User::query()->findOrFail($userId);
        $this->access = app(DepartmentAccessService::class);
    }

    public function test_active_processes_map_to_their_canonical_departments_and_warehouse_typo_is_repaired(): void
    {
        $expected = [
            'Warping' => 'Weaving', 'Weaving' => 'Weaving', 'Dyeing' => 'Dyeing', 'Coating' => 'Coating',
            'Packaging' => 'Packaging', 'D-Printing' => 'Printing', 'C-Printing' => 'Printing',
        ];
        $actual = DB::table('process_items')->join('departments', 'departments.id', '=', 'process_items.department_id')
            ->where('process_items.company_id', $this->companyId)->orderBy('process_items.id')
            ->pluck('departments.department_name', 'process_items.process_name')->all();

        $this->assertSame($expected, $actual);
        $this->assertSame(['Coating', 'Dyeing', 'Packaging', 'Printing', 'Warehouse', 'Weaving'], array_keys($this->departmentIds));
        $this->assertArrayNotHasKey('Warehose', $this->departmentIds);
    }

    public function test_department_access_returns_only_the_selected_department_processes_and_combines_multiple_departments(): void
    {
        $this->grant('Weaving');
        $this->assertSame([$this->processIds['Warping'], $this->processIds['Weaving']], $this->allowedProcessIds());

        $this->grant('Dyeing', 'Printing');
        $this->assertSame([$this->processIds['Dyeing'], $this->processIds['D-Printing'], $this->processIds['C-Printing']], $this->allowedProcessIds());

        $this->grant('Coating');
        $this->assertSame([$this->processIds['Coating']], $this->allowedProcessIds());

        $this->grant('Packaging');
        $this->assertSame([$this->processIds['Packaging']], $this->allowedProcessIds());
    }

    public function test_all_active_department_rows_grant_all_mapped_processes_but_no_rows_fail_closed_and_other_company_processes_stay_hidden(): void
    {
        $this->assertSame([], $this->allowedProcessIds());

        $this->grant(...array_keys($this->departmentIds));
        $this->assertSame(array_values($this->processIds), $this->allowedProcessIds());

        $otherCompanyId = DB::table('companies')->insertGetId([
            'company_code' => 'OTHER-'.bin2hex(random_bytes(4)),
            'name' => 'Other Company',
            'status' => 'Active',
        ]);
        $otherProcessId = DB::table('process_items')->insertGetId([
            'company_id' => $otherCompanyId,
            'process_name' => 'Weaving',
            'short_code' => 'OTHER-WEV',
            'entry_name' => 'Beam',
            'output_name' => 'Greige',
            'process_sl_no_last' => 0,
            'status' => 'Active',
        ]);
        /** @var Migration $migration */
        $migration = require base_path('database/migrations/2026_08_13_000001_complete_department_process_mappings.php');
        $migration->up();

        $this->assertNotContains($otherProcessId, $this->allowedProcessIds());
    }

    private function grant(string ...$departmentNames): void
    {
        DB::table('user_department_access')->where('user_id', $this->user->id)->where('company_id', $this->companyId)->delete();
        foreach ($departmentNames as $departmentName) {
            DB::table('user_department_access')->insert([
                'user_id' => $this->user->id,
                'company_id' => $this->companyId,
                'department_id' => $this->departmentIds[$departmentName],
                'status' => 'Active',
            ]);
        }
    }

    /** @return list<int> */
    private function allowedProcessIds(): array
    {
        return $this->access->allowedProcessIds($this->user);
    }

    private function useCompany(int $companyId): void
    {
        $context = new class ($companyId) extends CurrentOrganizationContext {
            public function __construct(private readonly int $testCompanyId)
            {
            }

            public function companyId(): int
            {
                return $this->testCompanyId;
            }
        };
        request()->attributes->set(CurrentOrganizationContext::class, $context);
        $this->app->instance(CurrentOrganizationContext::class, $context);
    }
}
