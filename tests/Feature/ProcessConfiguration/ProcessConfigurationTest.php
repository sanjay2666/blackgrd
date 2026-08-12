<?php

namespace Tests\Feature\ProcessConfiguration;

use App\Models\ItemType;
use App\Models\ProcessItem;
use App\Models\ProcessItemAllowedNext;
use App\Models\ProcessItemConfiguration;
use App\Models\ProcessItemMaterialConfiguration;
use App\Models\WorkflowDefinition;
use App\Models\WorkflowVersion;
use App\Models\WorkflowVersionStep;
use App\Services\AuditLogger;
use App\Services\CurrentOrganizationContext;
use App\Services\ProcessConfigurationService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ProcessConfigurationTest extends TestCase
{
    use DatabaseTransactions;

    private int $companyId;

    private ProcessConfigurationService $service;

    private ProcessItem $dyeing;

    private ProcessItem $printing;

    private ItemType $yarn;

    private ItemType $dyed;

    private Request $request;

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::connection()->getDriverName() !== 'mysql') {
            $this->markTestSkipped('Process configuration integration tests require disposable MySQL.');
        }
        if (DB::connection()->getDatabaseName() !== 'blackgrd_schema_testing') {
            $this->fail('Refusing Process Configuration integration tests outside blackgrd_schema_testing.');
        }
        foreach (['process_item_configurations', 'process_item_material_configurations', 'process_item_allowed_next'] as $table) {
            if (! Schema::hasTable($table)) {
                $this->fail("Process Configuration migration must be applied to disposable MySQL: missing [{$table}].");
            }
        }

        $this->companyId = $this->createCompany('PC');
        $organization = $this->useCompany($this->companyId);
        $this->service = new ProcessConfigurationService($organization, app(AuditLogger::class));
        $this->request = Request::create('/admin/process-items/1/configuration', 'PUT');
        $this->dyeing = $this->createProcess('Dyeing', 'DYE');
        $this->printing = $this->createProcess('Printing', 'PRT');
        $this->yarn = $this->createItemType('Yarn');
        $this->dyed = $this->createItemType('Dyed Fabric');
    }

    public function test_configuration_persists_input_output_allowed_next_and_all_execution_modes(): void
    {
        foreach (['Internal', 'External', 'Both'] as $mode) {
            $this->service->save($this->dyeing, [
                'input_item_type_ids' => [$this->yarn->item_type_id],
                'output_item_type_ids' => [$this->dyed->item_type_id],
                'allowed_next_process_ids' => [$this->printing->id],
                'execution_mode' => $mode,
            ], $this->request);

            $this->assertSame($mode, ProcessItemConfiguration::query()->where('process_item_id', $this->dyeing->id)->value('execution_mode'));
            $this->assertSame([$this->yarn->item_type_id], ProcessItemMaterialConfiguration::query()->where('process_item_id', $this->dyeing->id)->where('direction', 'Input')->pluck('item_type_id')->all());
            $this->assertSame([$this->dyed->item_type_id], ProcessItemMaterialConfiguration::query()->where('process_item_id', $this->dyeing->id)->where('direction', 'Output')->pluck('item_type_id')->all());
            $this->assertSame([$this->printing->id], ProcessItemAllowedNext::query()->where('process_item_id', $this->dyeing->id)->pluck('next_process_item_id')->all());
        }
    }

    public function test_duplicate_and_self_next_process_configuration_are_rejected(): void
    {
        try {
            $this->service->save($this->dyeing, [
                'input_item_type_ids' => [$this->yarn->item_type_id, $this->yarn->item_type_id],
                'output_item_type_ids' => [],
                'allowed_next_process_ids' => [],
                'execution_mode' => 'Both',
            ], $this->request);
            $this->fail('Duplicate input Item Types were accepted.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('input_item_type_ids', $exception->errors());
        }

        $this->expectException(ValidationException::class);
        $this->service->save($this->dyeing, [
            'input_item_type_ids' => [],
            'output_item_type_ids' => [],
            'allowed_next_process_ids' => [$this->dyeing->id],
            'execution_mode' => 'Both',
        ], $this->request);
    }

    public function test_cross_company_item_type_and_process_references_are_rejected_and_hidden(): void
    {
        $otherCompany = $this->createCompany('OTHER');
        $this->useCompany($otherCompany);
        $otherItemType = $this->createItemType('Other Company Type');
        $otherProcess = $this->createProcess('Other Process', 'OTH');

        $this->useCompany($this->companyId);
        try {
            $this->service->save($this->dyeing, [
                'input_item_type_ids' => [$otherItemType->item_type_id],
                'output_item_type_ids' => [],
                'allowed_next_process_ids' => [],
                'execution_mode' => 'Internal',
            ], $this->request);
            $this->fail('A cross-company Item Type was accepted.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('input_item_type_ids', $exception->errors());
        }

        try {
            $this->service->save($this->dyeing, [
                'input_item_type_ids' => [],
                'output_item_type_ids' => [],
                'allowed_next_process_ids' => [$otherProcess->id],
                'execution_mode' => 'External',
            ], $this->request);
            $this->fail('A cross-company next Process was accepted.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('allowed_next_process_ids', $exception->errors());
        }

        $this->assertSame([], ProcessItem::query()->whereKey($otherProcess->id)->pluck('id')->all());
    }

    public function test_configuration_does_not_change_workflow_version_step_order(): void
    {
        $definition = WorkflowDefinition::query()->create([
            'company_id' => $this->companyId,
            'workflow_code' => 'PC-'.bin2hex(random_bytes(3)),
            'workflow_name' => 'Process Configuration Compatibility',
            'status' => 'Active',
        ]);
        $version = WorkflowVersion::query()->create([
            'company_id' => $this->companyId,
            'workflow_definition_id' => $definition->id,
            'version_number' => 1,
            'status' => 'Draft',
        ]);
        WorkflowVersionStep::query()->create([
            'company_id' => $this->companyId,
            'workflow_version_id' => $version->id,
            'process_id' => $this->dyeing->id,
            'sequence' => 1,
        ]);

        $this->service->save($this->dyeing, [
            'input_item_type_ids' => [$this->yarn->item_type_id],
            'output_item_type_ids' => [$this->dyed->item_type_id],
            'allowed_next_process_ids' => [$this->printing->id],
            'execution_mode' => 'Both',
        ], $this->request);

        $this->assertSame([$this->dyeing->id], $version->steps()->orderBy('sequence')->pluck('process_id')->all());
        $this->assertSame([1], $version->steps()->orderBy('sequence')->pluck('sequence')->all());
    }

    private function createCompany(string $prefix): int
    {
        return DB::table('companies')->insertGetId([
            'company_code' => $prefix.'-'.bin2hex(random_bytes(4)),
            'name' => $prefix.' Process Configuration Company',
            'status' => 'Active',
        ]);
    }

    private function createProcess(string $name, string $code): ProcessItem
    {
        return ProcessItem::query()->create([
            'company_id' => app(CurrentOrganizationContext::class)->companyId(),
            'entry_name' => $name.' Input',
            'process_name' => $name,
            'short_code' => $code.'-'.bin2hex(random_bytes(2)),
            'output_name' => $name.' Output',
            'process_sl_no_last' => 0,
            'status' => 'Active',
        ]);
    }

    private function createItemType(string $name): ItemType
    {
        return ItemType::query()->create([
            'company_id' => app(CurrentOrganizationContext::class)->companyId(),
            'item_type_name' => $name,
            'is_purchase' => '0',
            'is_work' => '0',
            'is_department' => '0',
            'created' => now(),
            'modified' => now(),
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
