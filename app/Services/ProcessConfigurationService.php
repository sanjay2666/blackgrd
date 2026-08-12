<?php

namespace App\Services;

use App\Models\ItemType;
use App\Models\ProcessItem;
use App\Models\ProcessItemAllowedNext;
use App\Models\ProcessItemConfiguration;
use App\Models\ProcessItemMaterialConfiguration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class ProcessConfigurationService
{
    public function __construct(
        private readonly CurrentOrganizationContext $organization,
        private readonly AuditLogger $audit,
    ) {
    }

    /** @param array{input_item_type_ids?:list<int|string>, output_item_type_ids?:list<int|string>, allowed_next_process_ids?:list<int|string>, execution_mode:string} $attributes */
    public function save(ProcessItem $process, array $attributes, Request $request): void
    {
        $companyId = $this->organization->companyId();
        $inputIds = $this->normalizeIds($attributes['input_item_type_ids'] ?? [], 'input_item_type_ids');
        $outputIds = $this->normalizeIds($attributes['output_item_type_ids'] ?? [], 'output_item_type_ids');
        $nextIds = $this->normalizeIds($attributes['allowed_next_process_ids'] ?? [], 'allowed_next_process_ids');
        $executionMode = $attributes['execution_mode'];

        if (! in_array($executionMode, ['Internal', 'External', 'Both'], true)) {
            throw ValidationException::withMessages(['execution_mode' => 'Select a valid execution mode.']);
        }
        if (in_array((int) $process->getKey(), $nextIds, true)) {
            throw ValidationException::withMessages(['allowed_next_process_ids' => 'A process cannot be its own allowed next process.']);
        }

        $this->assertActiveItemTypes($inputIds, $companyId, 'input_item_type_ids');
        $this->assertActiveItemTypes($outputIds, $companyId, 'output_item_type_ids');
        $this->assertActiveNextProcesses($nextIds, $companyId);

        $before = $this->snapshot($process);
        DB::transaction(function () use ($process, $companyId, $inputIds, $outputIds, $nextIds, $executionMode): void {
            ProcessItemConfiguration::query()->updateOrCreate(
                ['process_item_id' => $process->getKey()],
                ['company_id' => $companyId, 'execution_mode' => $executionMode],
            );

            ProcessItemMaterialConfiguration::query()->where('process_item_id', $process->getKey())->delete();
            ProcessItemAllowedNext::query()->where('process_item_id', $process->getKey())->delete();

            $now = now();
            $materials = [
                ...array_map(fn (int $id): array => $this->materialRow($companyId, $process, $id, 'Input', $now), $inputIds),
                ...array_map(fn (int $id): array => $this->materialRow($companyId, $process, $id, 'Output', $now), $outputIds),
            ];
            if ($materials !== []) {
                ProcessItemMaterialConfiguration::query()->insert($materials);
            }

            if ($nextIds !== []) {
                ProcessItemAllowedNext::query()->insert(array_map(
                    fn (int $id): array => [
                        'company_id' => $companyId,
                        'process_item_id' => $process->getKey(),
                        'next_process_item_id' => $id,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                    $nextIds,
                ));
            }
        });

        $this->audit->recordAfterCommit([
            'module' => 'processes',
            'action' => 'update',
            'event' => 'process_configuration_updated',
            'auditable_type' => $process->getMorphClass(),
            'auditable_id' => $process->getKey(),
            'description' => 'Process configuration updated. Workflow Versions remain the order-specific route authority.',
            'old_values' => $before,
            'new_values' => $this->snapshot($process->fresh()),
            'request' => $request,
        ]);
    }

    /** @return list<int> */
    private function normalizeIds(array $ids, string $field): array
    {
        $normalized = array_map('intval', $ids);
        if (count($normalized) !== count(array_unique($normalized))) {
            throw ValidationException::withMessages([$field => 'Duplicate selections are not allowed.']);
        }

        return $normalized;
    }

    /** @param list<int> $ids */
    private function assertActiveItemTypes(array $ids, int $companyId, string $field): void
    {
        if ($ids === []) {
            return;
        }
        $count = ItemType::query()->active()->where('company_id', $companyId)->whereIn('item_type_id', $ids)->count();
        if ($count !== count($ids)) {
            throw ValidationException::withMessages([$field => 'Select only active Item Types from the current company.']);
        }
    }

    /** @param list<int> $ids */
    private function assertActiveNextProcesses(array $ids, int $companyId): void
    {
        if ($ids === []) {
            return;
        }
        $count = ProcessItem::query()->where('company_id', $companyId)->where('status', 'Active')->whereIn('id', $ids)->count();
        if ($count !== count($ids)) {
            throw ValidationException::withMessages(['allowed_next_process_ids' => 'Select only active processes from the current company.']);
        }
    }

    /** @return array<string, mixed> */
    private function materialRow(int $companyId, ProcessItem $process, int $itemTypeId, string $direction, mixed $now): array
    {
        return [
            'company_id' => $companyId,
            'process_item_id' => $process->getKey(),
            'item_type_id' => $itemTypeId,
            'direction' => $direction,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    /** @return array{execution_mode:?string,input_item_type_ids:list<int>,output_item_type_ids:list<int>,allowed_next_process_ids:list<int>} */
    private function snapshot(ProcessItem $process): array
    {
        return [
            'execution_mode' => $process->configuration?->execution_mode,
            'input_item_type_ids' => $process->materialConfigurations()->where('direction', 'Input')->orderBy('item_type_id')->pluck('item_type_id')->map(fn ($id): int => (int) $id)->all(),
            'output_item_type_ids' => $process->materialConfigurations()->where('direction', 'Output')->orderBy('item_type_id')->pluck('item_type_id')->map(fn ($id): int => (int) $id)->all(),
            'allowed_next_process_ids' => $process->allowedNextProcesses()->orderBy('next_process_item_id')->pluck('next_process_item_id')->map(fn ($id): int => (int) $id)->all(),
        ];
    }
}
