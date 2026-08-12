<?php

namespace Tests\Feature\Workflow;

use App\Models\ProcessItem;
use App\Models\SaleOrderItem;
use App\Models\WorkflowDefinition;
use App\Models\WorkflowVersion;
use App\Services\AuditLogger;
use App\Services\CurrentOrganizationContext;
use App\Services\WorkflowDefinitionService;
use App\Services\WorkflowAssignmentService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class WorkflowDefinitionVersioningTest extends TestCase
{
    use DatabaseTransactions;

    private int $companyId;

    private WorkflowDefinitionService $service;

    private Request $request;

    /** @var array<string, ProcessItem> */
    private array $processes;

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::connection()->getDriverName() !== 'mysql') {
            $this->markTestSkipped('Workflow schema integration tests require disposable MySQL.');
        }
        $database = DB::connection()->getDatabaseName();
        if ($database !== 'blackgrd_schema_testing') {
            $this->fail("Refusing Workflow integration tests on database [{$database}].");
        }
        foreach (['workflow_definitions', 'workflow_versions', 'workflow_version_steps'] as $table) {
            if (! Schema::hasTable($table)) {
                $this->fail("Workflow migration must be applied to disposable MySQL before running this test: missing [{$table}].");
            }
        }

        $this->companyId = DB::table('companies')->insertGetId([
            'company_code' => 'WF-'.bin2hex(random_bytes(4)),
            'name' => 'Workflow Integration Company',
            'status' => 'Active',
        ]);
        $organization = $this->useCompany($this->companyId);
        $this->service = new WorkflowDefinitionService($organization, app(AuditLogger::class));
        $this->request = Request::create('/admin/workflow-definitions', 'POST');
        $this->processes = [
            'Dyeing' => $this->createProcess('Dyeing', 'DYE'),
            'Printing' => $this->createProcess('Printing', 'PRT'),
            'Coating' => $this->createProcess('Coating', 'COA'),
        ];
    }

    public function test_definition_version_and_ordered_steps_are_created_and_published(): void
    {
        [$definition, $version] = $this->draft('PRINT-BEFORE', 'Printed Before Coating');
        $this->addRoute($definition, $version, ['Dyeing', 'Printing', 'Coating']);

        $this->service->publishVersion($definition, $version, ['effective_from' => '2026-08-12'], null, $this->request);

        $this->assertSame('Published', $version->fresh()->status);
        $this->assertTrue($version->fresh()->is_current);
        $this->assertSame([1, 2, 3], $version->steps()->pluck('sequence')->map(fn ($value) => (int) $value)->all());
        $this->assertSame(
            ['Dyeing', 'Printing', 'Coating'],
            $version->steps()->with('process')->get()->pluck('process.process_name')->all(),
        );
        $this->assertTrue($definition->versions()->whereKey($version->id)->exists());
    }

    public function test_printing_before_and_after_coating_are_both_representable(): void
    {
        [$beforeDefinition, $beforeVersion] = $this->draft('BEFORE-COAT', 'Print Before Coat');
        $this->addRoute($beforeDefinition, $beforeVersion, ['Dyeing', 'Printing', 'Coating']);
        $this->service->publishVersion($beforeDefinition, $beforeVersion, [], null, $this->request);

        [$afterDefinition, $afterVersion] = $this->draft('AFTER-COAT', 'Print After Coat');
        $this->addRoute($afterDefinition, $afterVersion, ['Dyeing', 'Coating', 'Printing']);
        $this->service->publishVersion($afterDefinition, $afterVersion, [], null, $this->request);

        $this->assertSame(
            ['Dyeing', 'Printing', 'Coating'],
            $beforeVersion->steps()->with('process')->get()->pluck('process.process_name')->all(),
        );
        $this->assertSame(
            ['Dyeing', 'Coating', 'Printing'],
            $afterVersion->steps()->with('process')->get()->pluck('process.process_name')->all(),
        );
    }

    public function test_duplicate_process_and_step_number_are_rejected(): void
    {
        [$definition, $version] = $this->draft('VALIDATION', 'Validation Route');
        $this->service->addStep($definition, $version, [
            'process_id' => $this->processes['Dyeing']->id,
            'sequence' => 1,
            'step_label' => null,
            'description' => null,
        ], $this->request);

        try {
            $this->service->addStep($definition, $version, [
                'process_id' => $this->processes['Printing']->id,
                'sequence' => 1,
                'step_label' => null,
                'description' => null,
            ], $this->request);
            $this->fail('A duplicate sequence was accepted.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('sequence', $exception->errors());
        }

        $this->expectException(ValidationException::class);
        $this->service->addStep($definition, $version, [
            'process_id' => $this->processes['Dyeing']->id,
            'sequence' => 2,
            'step_label' => null,
            'description' => null,
        ], $this->request);
    }

    public function test_published_version_is_immutable_and_new_version_is_a_copy(): void
    {
        [$definition, $versionOne] = $this->draft('VERSIONED', 'Versioned Route');
        $this->addRoute($definition, $versionOne, ['Dyeing', 'Printing', 'Coating']);
        $this->service->publishVersion($definition, $versionOne, [], null, $this->request);

        try {
            $this->service->removeStep($definition, $versionOne, $versionOne->steps()->firstOrFail(), $this->request);
            $this->fail('A published version was changed.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('immutable', $exception->getMessage());
        }

        $versionTwo = $this->service->createVersion($definition, ['remarks' => 'New route'], null, $this->request);
        $this->assertSame(2, (int) $versionTwo->version_number);
        $this->assertSame('Draft', $versionTwo->status);
        $this->assertSame(
            $versionOne->steps()->pluck('process_id')->all(),
            $versionTwo->steps()->pluck('process_id')->all(),
        );
        $this->assertSame('Published', $versionOne->fresh()->status);
    }

    public function test_sale_order_item_keeps_the_selected_published_version_snapshot(): void
    {
        [$definition, $version] = $this->draft('SALE-ITEM', 'Sale Item Route');
        $this->addRoute($definition, $version, ['Dyeing', 'Printing', 'Coating']);
        $this->service->publishVersion($definition, $version, [], null, $this->request);

        $item = SaleOrderItem::query()->create([
            'company_id' => $this->companyId,
            'item_name' => 'Snapshot Fabric',
            'status' => 'Active',
        ]);
        app(WorkflowAssignmentService::class)->assign($item, $version->id, null, $this->request);
        $item->refresh();

        $this->assertTrue($item->workflowDefinition->is($definition));
        $this->assertTrue($item->workflowVersion->is($version));
        $this->assertSame(['Dyeing', 'Printing', 'Coating'], $item->workflowVersion->steps->pluck('process.process_name')->all());

        $definition->update(['workflow_name' => 'Renamed Master']);
        $this->assertSame($version->id, $item->fresh()->workflow_version_id);
        $this->assertSame(['Dyeing', 'Printing', 'Coating'], $item->fresh()->workflowVersion->steps->pluck('process.process_name')->all());
    }

    public function test_company_scope_hides_other_company_workflows(): void
    {
        [$firstDefinition] = $this->draft('COMPANY-A', 'Company A Route');
        $secondCompanyId = DB::table('companies')->insertGetId([
            'company_code' => 'WF-'.bin2hex(random_bytes(4)),
            'name' => 'Other Workflow Company',
            'status' => 'Active',
        ]);
        $secondContext = $this->useCompany($secondCompanyId);
        $secondService = new WorkflowDefinitionService($secondContext, app(AuditLogger::class));
        $second = $secondService->createDefinition([
            'workflow_code' => 'COMPANY-B',
            'workflow_name' => 'Company B Route',
            'description' => null,
        ], null, $this->request)['definition'];

        $this->assertSame([$second->id], WorkflowDefinition::query()->pluck('id')->all());

        $this->useCompany($this->companyId);
        $this->assertSame([$firstDefinition->id], WorkflowDefinition::query()->pluck('id')->all());
    }

    /** @return array{WorkflowDefinition, WorkflowVersion} */
    private function draft(string $code, string $name): array
    {
        $created = $this->service->createDefinition([
            'workflow_code' => $code,
            'workflow_name' => $name,
            'description' => null,
        ], null, $this->request);

        return [$created['definition'], $created['version']];
    }

    /** @param list<string> $processNames */
    private function addRoute(WorkflowDefinition $definition, WorkflowVersion $version, array $processNames): void
    {
        foreach ($processNames as $index => $processName) {
            $this->service->addStep($definition, $version, [
                'process_id' => $this->processes[$processName]->id,
                'sequence' => $index + 1,
                'step_label' => null,
                'description' => null,
            ], $this->request);
        }
    }

    private function createProcess(string $name, string $code): ProcessItem
    {
        return ProcessItem::query()->create([
            'company_id' => $this->companyId,
            'entry_name' => $name.' Input',
            'process_name' => $name,
            'short_code' => $code.'-'.bin2hex(random_bytes(2)),
            'output_name' => $name.' Output',
            'process_sl_no_last' => 0,
            'status' => 'Active',
        ]);
    }

    private function useCompany(int $companyId): CurrentOrganizationContext
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

        return $context;
    }
}
