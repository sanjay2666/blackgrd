<?php

namespace Tests\Feature\Workflow;

use App\Models\ProcessItem;
use App\Models\ProcessItemAllowedNext;
use App\Models\SaleOrderItem;
use App\Models\WorkflowDefinition;
use App\Models\WorkflowVersion;
use App\Services\AuditLogger;
use App\Services\CurrentOrganizationContext;
use App\Services\WorkflowDefinitionService;
use App\Services\WorkflowStepTransitionRuleService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class WorkflowStepTransitionRuleTest extends TestCase
{
    use DatabaseTransactions;

    private int $companyId;

    private WorkflowDefinitionService $definitions;

    private WorkflowStepTransitionRuleService $rules;

    private Request $request;

    /** @var array<string, ProcessItem> */
    private array $processes;

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::connection()->getDriverName() !== 'mysql') {
            $this->markTestSkipped('Workflow transition integration tests require disposable MySQL.');
        }
        if (DB::connection()->getDatabaseName() !== 'blackgrd_schema_testing') {
            $this->fail('Refusing Workflow transition integration tests outside blackgrd_schema_testing.');
        }
        foreach (['workflow_definitions', 'workflow_versions', 'workflow_version_steps', 'process_item_allowed_next'] as $table) {
            if (! Schema::hasTable($table)) {
                $this->fail("Workflow transition tests require [{$table}] in disposable MySQL.");
            }
        }

        $this->companyId = $this->createCompany('WTR');
        $organization = $this->useCompany($this->companyId);
        $this->rules = new WorkflowStepTransitionRuleService($organization);
        $this->definitions = new WorkflowDefinitionService($organization, app(AuditLogger::class), $this->rules);
        $this->request = Request::create('/admin/workflow-definitions', 'POST');
        $this->processes = [
            'Warping' => $this->createProcess('Warping', 'WRP'),
            'Weaving' => $this->createProcess('Weaving', 'WEV'),
            'Dyeing' => $this->createProcess('Dyeing', 'DYE'),
            'Printing' => $this->createProcess('Printing', 'PRT'),
            'Coating' => $this->createProcess('Coating', 'COA'),
            'Inspection' => $this->createProcess('Inspection', 'INP'),
            'Packaging' => $this->createProcess('Packaging', 'PKG'),
        ];

        foreach ([
            ['Warping', 'Weaving'],
            ['Weaving', 'Dyeing'],
            ['Dyeing', 'Printing'],
            ['Dyeing', 'Coating'],
            ['Printing', 'Coating'],
            ['Coating', 'Printing'],
            ['Coating', 'Inspection'],
            ['Printing', 'Inspection'],
        ] as [$current, $next]) {
            $this->allow($current, $next);
        }
    }

    public function test_linear_route_resolves_only_the_adjacent_next_step_and_final_step_has_no_next_step(): void
    {
        [, $version] = $this->publishedRoute(['Warping', 'Weaving', 'Dyeing', 'Printing', 'Coating', 'Inspection']);
        $item = $this->itemFor($version);

        $this->assertSame($this->processes['Weaving']->id, $this->rules->resolveNextStep($item, $this->processes['Warping'])->process_id);
        $this->assertSame($this->processes['Dyeing']->id, $this->rules->resolveNextStep($item, $this->processes['Weaving'])->process_id);
        $this->assertSame($this->processes['Printing']->id, $this->rules->validateTransition($item, $this->processes['Dyeing'], $this->processes['Printing'])->process_id);
        $this->assertSame($this->processes['Coating']->id, $this->rules->validateTransition($item, $this->processes['Printing'], $this->processes['Coating'])->process_id);
        $this->assertNull($this->rules->resolveNextStep($item, $this->processes['Inspection']));
    }

    public function test_printing_after_coating_uses_that_versions_adjacent_route(): void
    {
        [, $version] = $this->publishedRoute(['Dyeing', 'Coating', 'Printing']);
        $item = $this->itemFor($version);

        $this->assertSame($this->processes['Coating']->id, $this->rules->validateTransition($item, $this->processes['Dyeing'], $this->processes['Coating'])->process_id);
        $this->assertSame($this->processes['Printing']->id, $this->rules->validateTransition($item, $this->processes['Coating'], $this->processes['Printing'])->process_id);
    }

    public function test_jump_backward_and_processes_outside_the_route_are_rejected(): void
    {
        [, $version] = $this->publishedRoute(['Dyeing', 'Printing', 'Coating', 'Inspection']);
        $item = $this->itemFor($version);

        $this->assertValidation(fn (): mixed => $this->rules->validateTransition($item, $this->processes['Dyeing'], $this->processes['Inspection']), 'next_process_id');
        $this->assertValidation(fn (): mixed => $this->rules->validateTransition($item, $this->processes['Coating'], $this->processes['Printing']), 'next_process_id');
        $this->assertValidation(fn (): mixed => $this->rules->resolveNextStep($item, $this->processes['Packaging']), 'current_process_id');
    }

    public function test_publication_rejects_an_adjacent_edge_that_process_configuration_does_not_allow(): void
    {
        $created = $this->definitions->createDefinition([
            'workflow_code' => 'INVALID-'.bin2hex(random_bytes(3)),
            'workflow_name' => 'Invalid Configuration Route',
            'description' => null,
        ], null, $this->request);
        $definition = $created['definition'];
        $version = $created['version'];
        $this->addRoute($definition, $version, ['Warping', 'Dyeing']);

        $this->assertValidation(
            fn (): mixed => $this->definitions->publishVersion($definition, $version, [], null, $this->request),
            'workflow_version',
        );
    }

    public function test_only_matching_published_versions_can_be_used_for_workflow_controlled_transitions(): void
    {
        $created = $this->definitions->createDefinition([
            'workflow_code' => 'DRAFT-'.bin2hex(random_bytes(3)),
            'workflow_name' => 'Draft Route',
            'description' => null,
        ], null, $this->request);
        $this->addRoute($created['definition'], $created['version'], ['Dyeing', 'Printing']);
        $draftItem = $this->itemFor($created['version']);

        $this->assertValidation(fn (): mixed => $this->rules->resolveNextStep($draftItem, $this->processes['Dyeing']), 'workflow_version');

        [, $publishedVersion] = $this->publishedRoute(['Dyeing', 'Printing']);
        $mismatchedItem = $this->itemFor($publishedVersion, $created['definition']->id);
        $this->assertValidation(fn (): mixed => $this->rules->resolveNextStep($mismatchedItem, $this->processes['Dyeing']), 'workflow_version');
    }

    public function test_assigned_older_published_version_remains_its_own_route_after_a_newer_version_exists(): void
    {
        [$definition, $versionOne] = $this->publishedRoute(['Dyeing', 'Printing', 'Coating']);
        $versionTwo = $this->definitions->createVersion($definition, [], null, $this->request);
        $this->definitions->publishVersion($definition, $versionTwo, [], null, $this->request);
        $item = $this->itemFor($versionOne);

        $this->assertSame($versionOne->id, $item->workflow_version_id);
        $this->assertSame($this->processes['Printing']->id, $this->rules->resolveNextStep($item, $this->processes['Dyeing'])->process_id);
        $this->assertSame('Published', $versionOne->fresh()->status);
        $this->assertFalse($versionOne->fresh()->is_current);
        $this->assertTrue($versionTwo->fresh()->is_current);
    }

    public function test_cross_company_and_inactive_records_are_rejected(): void
    {
        [, $version] = $this->publishedRoute(['Dyeing', 'Printing']);
        $crossCompanyItem = new SaleOrderItem();
        $crossCompanyItem->setRawAttributes([
            'company_id' => $this->createCompany('OTHER'),
            'workflow_definition_id' => $version->workflow_definition_id,
            'workflow_version_id' => $version->id,
            'item_name' => 'Other Company Fabric',
            'status' => 'Active',
        ], true);

        $this->assertValidation(fn (): mixed => $this->rules->resolveNextStep($crossCompanyItem, $this->processes['Dyeing']), 'sale_order_item');

        $item = $this->itemFor($version);
        $this->processes['Printing']->update(['status' => 'Inactive']);
        $this->assertValidation(fn (): mixed => $this->rules->resolveNextStep($item, $this->processes['Dyeing']), 'workflow_version');
    }

    /** @return array{WorkflowDefinition, WorkflowVersion} */
    private function publishedRoute(array $processNames): array
    {
        $created = $this->definitions->createDefinition([
            'workflow_code' => 'ROUTE-'.bin2hex(random_bytes(3)),
            'workflow_name' => 'Transition Route',
            'description' => null,
        ], null, $this->request);
        $this->addRoute($created['definition'], $created['version'], $processNames);
        $this->definitions->publishVersion($created['definition'], $created['version'], [], null, $this->request);

        return [$created['definition'], $created['version']];
    }

    /** @param list<string> $processNames */
    private function addRoute(WorkflowDefinition $definition, WorkflowVersion $version, array $processNames): void
    {
        foreach ($processNames as $index => $name) {
            $this->definitions->addStep($definition, $version, [
                'process_id' => $this->processes[$name]->id,
                'sequence' => $index + 1,
                'step_label' => null,
                'description' => null,
            ], $this->request);
        }
    }

    private function itemFor(WorkflowVersion $version, ?int $definitionId = null): SaleOrderItem
    {
        return SaleOrderItem::query()->create([
            'company_id' => $this->companyId,
            'workflow_definition_id' => $definitionId ?? $version->workflow_definition_id,
            'workflow_version_id' => $version->id,
            'item_name' => 'Transition Fabric',
            'status' => 'Active',
        ]);
    }

    private function allow(string $current, string $next): void
    {
        ProcessItemAllowedNext::query()->create([
            'company_id' => $this->companyId,
            'process_item_id' => $this->processes[$current]->id,
            'next_process_item_id' => $this->processes[$next]->id,
        ]);
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

    private function createCompany(string $prefix): int
    {
        return DB::table('companies')->insertGetId([
            'company_code' => $prefix.'-'.bin2hex(random_bytes(4)),
            'name' => $prefix.' Workflow Transition Company',
            'status' => 'Active',
        ]);
    }

    private function assertValidation(callable $operation, string $field): void
    {
        try {
            $operation();
            $this->fail('Expected transition validation to fail.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey($field, $exception->errors());
        }
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
