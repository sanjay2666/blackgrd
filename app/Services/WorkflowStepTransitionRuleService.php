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
 * Resolves and validates a published Workflow Version route assigned to a Sale
 * Order Item. Runtime state changes remain the responsibility of a later
 * transition/execution service.
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

        $orderedSteps = $steps->sortBy('sequence')->values();
        foreach ($orderedSteps as $index => $current) {
            $this->assertRequiredValue($current);

            foreach ($this->allowedNextStepsFrom($orderedSteps, $index) as $next) {
                $this->assertAllowedEdge($current, $next, $companyId, 'workflow_version');
            }
        }
    }

    public function currentStep(SaleOrderItem $saleOrderItem, int|ProcessItem|WorkflowVersionStep $current): WorkflowVersionStep
    {
        $route = $this->assignedPublishedRoute($saleOrderItem);

        return $this->currentStepFromSteps($route['steps'], $current);
    }

    public function resolveNextStep(SaleOrderItem $saleOrderItem, int|ProcessItem|WorkflowVersionStep $current): ?WorkflowVersionStep
    {
        $route = $this->assignedPublishedRoute($saleOrderItem);
        $currentStep = $this->currentStepFromSteps($route['steps'], $current);
        $next = $route['steps']->firstWhere('sequence', ((int) $currentStep->sequence) + 1);

        if (! $next instanceof WorkflowVersionStep) {
            return null;
        }

        $this->assertAllowedEdge($currentStep, $next, $route['company_id'], 'next_process_id');

        return $next;
    }

    /**
     * Returns the normal adjacent Step plus each later Step reachable by
     * skipping only the consecutive optional occurrences immediately after the
     * current one. The caller still chooses one candidate; this method does not
     * record execution state.
     *
     * @return Collection<int, WorkflowVersionStep>
     */
    public function allowedNextSteps(SaleOrderItem $saleOrderItem, int|ProcessItem|WorkflowVersionStep $current): Collection
    {
        $route = $this->assignedPublishedRoute($saleOrderItem);
        $currentStep = $this->currentStepFromSteps($route['steps'], $current);
        $currentIndex = $route['steps']->search(fn (WorkflowVersionStep $step): bool => (int) $step->id === (int) $currentStep->id);

        if (! is_int($currentIndex)) {
            throw ValidationException::withMessages([
                'current_process_id' => 'The current Workflow Version Step is not part of the assigned route.',
            ]);
        }

        return $this->allowedNextStepsFrom($route['steps'], $currentIndex);
    }

    public function validateTransition(
        SaleOrderItem $saleOrderItem,
        int|ProcessItem|WorkflowVersionStep $current,
        int|ProcessItem|WorkflowVersionStep $requestedNext,
    ): WorkflowVersionStep {
        $route = $this->assignedPublishedRoute($saleOrderItem);
        $currentStep = $this->currentStepFromSteps($route['steps'], $current);
        $candidates = $this->allowedNextSteps($saleOrderItem, $currentStep);
        if ($candidates->isEmpty()) {
            throw ValidationException::withMessages([
                'next_process_id' => 'The current Workflow Version Step is final and has no next Process.',
            ]);
        }

        $next = $this->requestedStepFromCandidates($candidates, $requestedNext);
        $this->assertAllowedEdge($currentStep, $next, $route['company_id'], 'next_process_id');

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
    private function currentStepFromSteps(Collection $steps, int|ProcessItem|WorkflowVersionStep $current): WorkflowVersionStep
    {
        if ($current instanceof WorkflowVersionStep) {
            $step = $steps->firstWhere('id', $current->id);
            if ($step instanceof WorkflowVersionStep) {
                return $step;
            }

            throw ValidationException::withMessages([
                'current_process_id' => 'The current Workflow Version Step is not part of the assigned Workflow Version.',
            ]);
        }

        $currentProcessId = $this->processId($current);
        $matches = $steps->where('process_id', $currentProcessId)->values();
        if ($matches->count() !== 1) {
            throw ValidationException::withMessages([
                'current_process_id' => $matches->isEmpty()
                    ? 'The current Process is not part of the assigned Workflow Version.'
                    : 'The current Process occurs more than once. Provide the Workflow Version Step occurrence.',
            ]);
        }

        $step = $matches->first();

        return $step;
    }

    /**
     * @param  Collection<int, WorkflowVersionStep>  $steps
     * @return Collection<int, WorkflowVersionStep>
     */
    private function allowedNextStepsFrom(Collection $steps, int $currentIndex): Collection
    {
        $candidates = collect();
        $count = $steps->count();

        for ($index = $currentIndex + 1; $index < $count; $index++) {
            $candidate = $steps->get($index);
            if (! $candidate instanceof WorkflowVersionStep) {
                continue;
            }

            if ($index > $currentIndex + 1) {
                $skipped = $steps->get($index - 1);
                if (! $skipped instanceof WorkflowVersionStep || $skipped->is_required) {
                    break;
                }
            }

            $candidates->push($candidate);
        }

        return $candidates;
    }

    /**
     * @param  Collection<int, WorkflowVersionStep>  $candidates
     */
    private function requestedStepFromCandidates(Collection $candidates, int|ProcessItem|WorkflowVersionStep $requested): WorkflowVersionStep
    {
        if ($requested instanceof WorkflowVersionStep) {
            $step = $candidates->firstWhere('id', $requested->id);
            if ($step instanceof WorkflowVersionStep) {
                return $step;
            }

            throw ValidationException::withMessages([
                'next_process_id' => 'The requested Workflow Version Step is not a valid next Step.',
            ]);
        }

        $matches = $candidates->where('process_id', $this->processId($requested))->values();
        if ($matches->count() !== 1) {
            throw ValidationException::withMessages([
                'next_process_id' => $matches->isEmpty()
                    ? 'The requested Process is not a valid next Workflow Version Step.'
                    : 'The requested Process matches multiple next Workflow Version Step occurrences. Provide the Step occurrence.',
            ]);
        }

        return $matches->first();
    }

    private function assertRequiredValue(WorkflowVersionStep $step): void
    {
        if (! in_array($step->getRawOriginal('is_required'), [0, 1, '0', '1', true, false], true)) {
            throw ValidationException::withMessages([
                'workflow_version' => 'Each Workflow Version Step must be Required or Optional.',
            ]);
        }
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
