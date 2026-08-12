<?php

namespace App\Services;

use App\Models\ProcessItem;
use App\Models\ProcessItemAllowedNext;
use App\Models\SaleOrderItem;
use App\Models\WorkflowDefinition;
use App\Models\WorkflowVersion;
use App\Models\WorkflowVersionStep;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Resolves and validates the linear, published Workflow Version route assigned
 * to a Sale Order Item. Runtime state changes remain the responsibility of a
 * later transition/execution service.
 */
final class WorkflowStepTransitionRuleService
{
    public function __construct(private readonly CurrentOrganizationContext $organization)
    {
    }

    /**
     * @param  Collection<int, WorkflowVersionStep>  $steps
     */
    public function assertPublishableRoute(
        WorkflowDefinition $definition,
        WorkflowVersion $version,
        Collection $steps,
    ): void {
        $companyId = $this->organization->companyId();

        $this->assertCompany($definition, $companyId, 'workflow_definition');
        $this->assertCompany($version, $companyId, 'workflow_version');

        if ((int) $version->workflow_definition_id !== (int) $definition->id) {
            throw ValidationException::withMessages([
                'workflow_version' => 'The Workflow Version does not belong to this Workflow Definition.',
            ]);
        }

        foreach ($steps as $step) {
            $this->assertCompany($step, $companyId, 'workflow_version');
        }

        $orderedSteps = $steps->values();
        foreach ($orderedSteps as $index => $current) {
            $next = $orderedSteps->get($index + 1);
            if ($next instanceof WorkflowVersionStep) {
                $this->assertAllowedEdge($current, $next, $companyId, 'workflow_version');
            }
        }
    }

    public function currentStep(SaleOrderItem $saleOrderItem, int|ProcessItem $currentProcess): WorkflowVersionStep
    {
        $route = $this->assignedPublishedRoute($saleOrderItem);
        $currentProcessId = $this->processId($currentProcess);
        $step = $route['steps']->firstWhere('process_id', $currentProcessId);

        if (! $step instanceof WorkflowVersionStep) {
            throw ValidationException::withMessages([
                'current_process_id' => 'The current Process is not part of the assigned Workflow Version.',
            ]);
        }

        return $step;
    }

    public function resolveNextStep(SaleOrderItem $saleOrderItem, int|ProcessItem $currentProcess): ?WorkflowVersionStep
    {
        $route = $this->assignedPublishedRoute($saleOrderItem);
        $current = $this->currentStepFromSteps($route['steps'], $this->processId($currentProcess));
        $next = $route['steps']->firstWhere('sequence', ((int) $current->sequence) + 1);

        if (! $next instanceof WorkflowVersionStep) {
            return null;
        }

        $this->assertAllowedEdge($current, $next, $route['company_id'], 'next_process_id');

        return $next;
    }

    public function validateTransition(
        SaleOrderItem $saleOrderItem,
        int|ProcessItem $currentProcess,
        int|ProcessItem $requestedNextProcess,
    ): WorkflowVersionStep {
        $next = $this->resolveNextStep($saleOrderItem, $currentProcess);
        $requestedNextProcessId = $this->processId($requestedNextProcess);

        if ($next === null) {
            throw ValidationException::withMessages([
                'next_process_id' => 'The current Workflow Version Step is final and has no next Process.',
            ]);
        }

        if ((int) $next->process_id !== $requestedNextProcessId) {
            throw ValidationException::withMessages([
                'next_process_id' => 'The requested Process is not the next Workflow Version Step.',
            ]);
        }

        return $next;
    }

    /**
     * @return array{company_id:int,version:WorkflowVersion,steps:Collection<int, WorkflowVersionStep>}
     */
    private function assignedPublishedRoute(SaleOrderItem $saleOrderItem): array
    {
        $companyId = $this->organization->companyId();
        $this->assertCompany($saleOrderItem, $companyId, 'sale_order_item');

        if ($saleOrderItem->workflow_definition_id === null || $saleOrderItem->workflow_version_id === null) {
            throw ValidationException::withMessages([
                'workflow_version' => 'A workflow-controlled transition requires an assigned Workflow Version.',
            ]);
        }

        $version = WorkflowVersion::query()
            ->with(['definition', 'steps.process'])
            ->whereKey($saleOrderItem->workflow_version_id)
            ->first();

        if (! $version instanceof WorkflowVersion
            || $version->status !== 'Published'
            || (int) $version->workflow_definition_id !== (int) $saleOrderItem->workflow_definition_id
            || ! $version->definition instanceof WorkflowDefinition
        ) {
            throw ValidationException::withMessages([
                'workflow_version' => 'The Sale Order Item must reference its matching published Workflow Version.',
            ]);
        }

        $this->assertCompany($version, $companyId, 'workflow_version');
        $this->assertCompany($version->definition, $companyId, 'workflow_definition');

        $steps = $version->steps;
        if ($steps->isEmpty()) {
            throw ValidationException::withMessages([
                'workflow_version' => 'The assigned Workflow Version has no Steps.',
            ]);
        }

        foreach ($steps as $step) {
            $this->assertCompany($step, $companyId, 'workflow_version');
            if (! $step->process instanceof ProcessItem || $step->process->status !== 'Active') {
                throw ValidationException::withMessages([
                    'workflow_version' => 'The assigned Workflow Version contains an unavailable Process.',
                ]);
            }
            $this->assertCompany($step->process, $companyId, 'workflow_version');
        }

        return [
            'company_id' => $companyId,
            'version' => $version,
            'steps' => $steps,
        ];
    }

    /**
     * @param  Collection<int, WorkflowVersionStep>  $steps
     */
    private function currentStepFromSteps(Collection $steps, int $currentProcessId): WorkflowVersionStep
    {
        $step = $steps->firstWhere('process_id', $currentProcessId);
        if (! $step instanceof WorkflowVersionStep) {
            throw ValidationException::withMessages([
                'current_process_id' => 'The current Process is not part of the assigned Workflow Version.',
            ]);
        }

        return $step;
    }

    private function assertAllowedEdge(
        WorkflowVersionStep $current,
        WorkflowVersionStep $next,
        int $companyId,
        string $field,
    ): void {
        if (! ProcessItemAllowedNext::query()
            ->where('company_id', $companyId)
            ->where('process_item_id', $current->process_id)
            ->where('next_process_item_id', $next->process_id)
            ->exists()) {
            throw ValidationException::withMessages([
                $field => sprintf(
                    'Process transition from [%s] to [%s] is not allowed by Process Configuration.',
                    $current->process?->process_name ?? $current->process_id,
                    $next->process?->process_name ?? $next->process_id,
                ),
            ]);
        }
    }

    private function assertCompany(object $model, int $companyId, string $field): void
    {
        if ((int) ($model->company_id ?? 0) !== $companyId) {
            throw ValidationException::withMessages([
                $field => 'All workflow transition records must belong to the current company.',
            ]);
        }
    }

    private function processId(int|ProcessItem $process): int
    {
        $processId = $process instanceof ProcessItem ? (int) $process->getKey() : $process;
        if ($processId <= 0) {
            throw ValidationException::withMessages(['process_id' => 'Please provide a valid Process.']);
        }

        return $processId;
    }
}
