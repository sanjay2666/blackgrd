<?php

namespace App\Services;

use App\Models\ProcessItem;
use App\Models\WorkflowDefinition;
use App\Models\WorkflowVersion;
use App\Models\WorkflowVersionStep;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class WorkflowDefinitionService
{
    public function __construct(
        private readonly CurrentOrganizationContext $organization,
        private readonly AuditLogger $audit,
        private readonly WorkflowStepTransitionRuleService $transitionRules,
    ) {
    }

    /** @return array{definition: WorkflowDefinition, version: WorkflowVersion} */
    public function createDefinition(array $attributes, ?int $actorId, Request $request): array
    {
        $code = strtoupper(trim((string) $attributes['workflow_code']));
        $name = trim((string) $attributes['workflow_name']);
        $companyId = $this->organization->companyId();

        if (WorkflowDefinition::query()->where('workflow_code', $code)->exists()) {
            throw ValidationException::withMessages(['workflow_code' => 'This Workflow Code already exists.']);
        }

        [$definition, $version] = DB::transaction(function () use ($attributes, $actorId, $code, $companyId, $name): array {
            $definition = WorkflowDefinition::query()->create([
                'company_id' => $companyId,
                'workflow_code' => $code,
                'workflow_name' => $name,
                'description' => $attributes['description'] ?? null,
                'status' => 'Active',
                'created_by' => $actorId,
                'modified_by' => $actorId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $version = $definition->versions()->create([
                'company_id' => $companyId,
                'version_number' => 1,
                'status' => 'Draft',
                'is_current' => false,
                'created_by' => $actorId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return [$definition, $version];
        });

        $this->audit->recordAfterCommit([
            'module' => 'processes',
            'action' => 'create',
            'event' => 'workflow_definition_created',
            'description' => 'Workflow Definition and initial Draft version created.',
            'auditable_type' => $definition->getMorphClass(),
            'auditable_id' => $definition->id,
            'new_values' => ['workflow_code' => $code, 'workflow_name' => $name, 'version_number' => 1],
            'request' => $request,
        ]);

        return ['definition' => $definition, 'version' => $version];
    }

    public function updateDefinition(WorkflowDefinition $definition, array $attributes, ?int $actorId, Request $request): void
    {
        $code = strtoupper(trim((string) $attributes['workflow_code']));
        if (WorkflowDefinition::query()->where('workflow_code', $code)->whereKeyNot($definition->getKey())->exists()) {
            throw ValidationException::withMessages(['workflow_code' => 'This Workflow Code already exists.']);
        }

        $before = $definition->only(['workflow_code', 'workflow_name', 'description', 'status']);
        $definition->fill([
            'workflow_code' => $code,
            'workflow_name' => trim((string) $attributes['workflow_name']),
            'description' => $attributes['description'] ?? null,
            'status' => $attributes['status'],
            'modified_by' => $actorId,
            'updated_at' => now(),
        ])->save();

        $this->audit->recordAfterCommit([
            'module' => 'processes',
            'action' => 'update',
            'event' => 'workflow_definition_updated',
            'description' => 'Workflow Definition metadata/status changed.',
            'auditable_type' => $definition->getMorphClass(),
            'auditable_id' => $definition->id,
            'old_values' => $before,
            'new_values' => $definition->only(['workflow_code', 'workflow_name', 'description', 'status']),
            'request' => $request,
        ]);
    }

    public function createVersion(
        WorkflowDefinition $definition,
        array $attributes,
        ?int $actorId,
        Request $request,
    ): WorkflowVersion {
        if ($definition->status !== 'Active') {
            throw ValidationException::withMessages(['workflow_definition' => 'Inactive Workflow Definitions cannot create new versions.']);
        }

        $version = DB::transaction(function () use ($attributes, $actorId, $definition): WorkflowVersion {
            $lockedDefinition = WorkflowDefinition::query()->whereKey($definition->id)->lockForUpdate()->firstOrFail();
            $source = WorkflowVersion::query()
                ->where('workflow_definition_id', $lockedDefinition->id)
                ->orderByDesc('version_number')
                ->lockForUpdate()
                ->firstOrFail();

            if ($source->status === 'Draft') {
                throw ValidationException::withMessages(['workflow_version' => 'Publish or delete the existing Draft before creating another version.']);
            }

            $version = $lockedDefinition->versions()->create([
                'company_id' => $this->organization->companyId(),
                'version_number' => ((int) $source->version_number) + 1,
                'status' => 'Draft',
                'is_current' => false,
                'effective_from' => $attributes['effective_from'] ?? null,
                'remarks' => $attributes['remarks'] ?? null,
                'created_by' => $actorId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($source->steps as $sourceStep) {
                $version->steps()->create([
                    'company_id' => $this->organization->companyId(),
                    'process_id' => $sourceStep->process_id,
                    'sequence' => $sourceStep->sequence,
                    'step_label' => $sourceStep->step_label,
                    'description' => $sourceStep->description,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            return $version;
        });

        $this->audit->recordAfterCommit([
            'module' => 'processes',
            'action' => 'create',
            'event' => 'workflow_version_created',
            'description' => 'New Draft Workflow Version copied from the previous published version.',
            'auditable_type' => $version->getMorphClass(),
            'auditable_id' => $version->id,
            'new_values' => [
                'workflow_definition_id' => $definition->id,
                'version_number' => $version->version_number,
            ],
            'request' => $request,
        ]);

        return $version;
    }

    public function publishVersion(
        WorkflowDefinition $definition,
        WorkflowVersion $version,
        array $attributes,
        ?int $actorId,
        Request $request,
    ): void {
        $this->assertVersionBelongsToDefinition($definition, $version);
        if ($version->status !== 'Draft') {
            throw ValidationException::withMessages(['workflow_version' => 'Only Draft versions can be published.']);
        }

        DB::transaction(function () use ($actorId, $attributes, $definition, $version): void {
            WorkflowDefinition::query()->whereKey($definition->id)->lockForUpdate()->firstOrFail();
            $lockedVersion = WorkflowVersion::query()->whereKey($version->id)->lockForUpdate()->firstOrFail();
            $steps = $lockedVersion->steps()->orderBy('sequence')->get();
            $expectedSequence = range(1, $steps->count());

            if ($steps->isEmpty() || $steps->pluck('sequence')->map(fn ($sequence): int => (int) $sequence)->all() !== $expectedSequence) {
                throw ValidationException::withMessages(['workflow_version' => 'Published versions require consecutive steps starting at 1.']);
            }
            if ($steps->pluck('process_id')->unique()->count() !== $steps->count()) {
                throw ValidationException::withMessages(['workflow_version' => 'A Process cannot be repeated in the same Workflow Version.']);
            }

            $processIds = $steps->pluck('process_id');
            if (ProcessItem::query()->whereIn('id', $processIds)->where('status', 'Active')->count() !== $processIds->count()) {
                throw ValidationException::withMessages(['workflow_version' => 'A published version cannot contain a missing, inactive, or deleted Process.']);
            }

            $this->transitionRules->assertPublishableRoute($definition, $lockedVersion, $steps);

            WorkflowVersion::query()
                ->where('workflow_definition_id', $definition->id)
                ->where('is_current', true)
                ->update(['is_current' => false, 'updated_at' => now()]);

            $lockedVersion->fill([
                'status' => 'Published',
                'is_current' => true,
                'effective_from' => $attributes['effective_from'] ?? $lockedVersion->effective_from,
                'remarks' => $attributes['remarks'] ?? $lockedVersion->remarks,
                'published_by' => $actorId,
                'published_at' => now(),
                'updated_at' => now(),
            ])->save();
        });

        $version->refresh();
        $this->audit->recordAfterCommit([
            'module' => 'processes',
            'action' => 'update',
            'event' => 'workflow_version_published',
            'description' => 'Workflow Version published, made current, and locked.',
            'auditable_type' => $version->getMorphClass(),
            'auditable_id' => $version->id,
            'new_values' => [
                'status' => $version->status,
                'is_current' => $version->is_current,
                'published_by' => $version->published_by,
                'published_at' => $version->published_at,
            ],
            'request' => $request,
        ]);
    }

    public function addStep(
        WorkflowDefinition $definition,
        WorkflowVersion $version,
        array $attributes,
        Request $request,
    ): WorkflowVersionStep {
        $this->assertDraft($definition, $version);
        $this->validateStep($version, $attributes);

        $step = $version->steps()->create([
            ...$attributes,
            'company_id' => $this->organization->companyId(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->audit->recordAfterCommit([
            'module' => 'processes',
            'action' => 'create',
            'event' => 'workflow_step_created',
            'description' => 'Workflow Version step added.',
            'auditable_type' => $step->getMorphClass(),
            'auditable_id' => $step->id,
            'new_values' => $step->only(['workflow_version_id', 'process_id', 'sequence', 'step_label', 'description']),
            'request' => $request,
        ]);

        return $step;
    }

    public function updateStep(
        WorkflowDefinition $definition,
        WorkflowVersion $version,
        WorkflowVersionStep $step,
        array $attributes,
        Request $request,
    ): void {
        $this->assertDraft($definition, $version);
        if ((int) $step->workflow_version_id !== (int) $version->id) {
            abort(404);
        }
        $this->validateStep($version, $attributes, $step);

        $before = $step->only(['process_id', 'sequence', 'step_label', 'description']);
        $step->fill([...$attributes, 'updated_at' => now()])->save();
        $this->audit->recordAfterCommit([
            'module' => 'processes',
            'action' => 'update',
            'event' => 'workflow_step_updated',
            'description' => 'Workflow Version step changed.',
            'auditable_type' => $step->getMorphClass(),
            'auditable_id' => $step->id,
            'old_values' => $before,
            'new_values' => $step->only(['process_id', 'sequence', 'step_label', 'description']),
            'request' => $request,
        ]);
    }

    public function removeStep(
        WorkflowDefinition $definition,
        WorkflowVersion $version,
        WorkflowVersionStep $step,
        Request $request,
    ): void {
        $this->assertDraft($definition, $version);
        if ((int) $step->workflow_version_id !== (int) $version->id) {
            abort(404);
        }

        $before = $step->only(['workflow_version_id', 'process_id', 'sequence', 'step_label', 'description']);
        $stepId = $step->id;
        $step->delete();
        $this->audit->recordAfterCommit([
            'module' => 'processes',
            'action' => 'delete',
            'event' => 'workflow_step_deleted',
            'description' => 'Draft Workflow Version step removed.',
            'auditable_type' => WorkflowVersionStep::class,
            'auditable_id' => $stepId,
            'old_values' => $before,
            'request' => $request,
        ]);
    }

    public function deleteVersion(
        WorkflowDefinition $definition,
        WorkflowVersion $version,
        Request $request,
    ): void {
        $this->assertDraft($definition, $version);
        if ($version->saleOrderItems()->exists()) {
            throw ValidationException::withMessages(['workflow_version' => 'A Workflow Version assigned to a Sale Order Item cannot be deleted.']);
        }

        $before = $version->only(['workflow_definition_id', 'version_number', 'status']);
        $versionId = $version->id;
        DB::transaction(function () use ($version): void {
            $version->steps()->delete();
            $version->delete();
        });

        $this->audit->recordAfterCommit([
            'module' => 'processes',
            'action' => 'delete',
            'event' => 'workflow_version_deleted',
            'description' => 'Unreferenced Draft Workflow Version removed.',
            'auditable_type' => WorkflowVersion::class,
            'auditable_id' => $versionId,
            'old_values' => $before,
            'request' => $request,
        ]);
    }

    private function validateStep(
        WorkflowVersion $version,
        array $attributes,
        ?WorkflowVersionStep $existing = null,
    ): void {
        if (! ProcessItem::query()->whereKey($attributes['process_id'])->where('status', 'Active')->exists()) {
            throw ValidationException::withMessages(['process_id' => 'Please select a valid active Process.']);
        }

        $duplicateSequence = $version->steps()->where('sequence', $attributes['sequence']);
        $duplicateProcess = $version->steps()->where('process_id', $attributes['process_id']);
        if ($existing !== null) {
            $duplicateSequence->whereKeyNot($existing->id);
            $duplicateProcess->whereKeyNot($existing->id);
        }
        if ($duplicateSequence->exists()) {
            throw ValidationException::withMessages(['sequence' => 'Step numbers must be unique within a version.']);
        }
        if ($duplicateProcess->exists()) {
            throw ValidationException::withMessages(['process_id' => 'A Process cannot be repeated in the same Workflow Version.']);
        }
    }

    private function assertDraft(WorkflowDefinition $definition, WorkflowVersion $version): void
    {
        $this->assertVersionBelongsToDefinition($definition, $version);
        if ($version->status !== 'Draft') {
            throw ValidationException::withMessages(['workflow_version' => 'Published versions are immutable. Create a new version to change the sequence.']);
        }
    }

    private function assertVersionBelongsToDefinition(WorkflowDefinition $definition, WorkflowVersion $version): void
    {
        if ((int) $version->workflow_definition_id !== (int) $definition->id) {
            abort(404);
        }
    }
}
