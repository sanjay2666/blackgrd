<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProcessItem;
use App\Models\WorkflowDefinition;
use App\Models\WorkflowVersion;
use App\Models\WorkflowVersionStep;
use App\Services\AuditLogger;
use App\Services\CurrentOrganizationContext;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class WorkflowDefinitionController extends Controller
{
    public function index(Request $request): View
    {
        $definitions = WorkflowDefinition::query()
            ->where('status', '!=', 'Deleted')
            ->with(['versions' => fn ($query) => $query->orderByDesc('version_number')])
            ->when($request->filled('search'), fn ($query) => $query->where(function ($nested) use ($request): void {
                $search = '%'.$request->string('search').'%';
                $nested->where('workflow_code', 'like', $search)->orWhere('workflow_name', 'like', $search);
            }))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->orderBy('workflow_name')
            ->paginate(config('app.pagination_limit'))
            ->withQueryString();

        return view('admin.workflow_definitions.index', compact('definitions'));
    }

    public function create(): View
    {
        return view('admin.workflow_definitions.create');
    }

    public function store(Request $request, CurrentOrganizationContext $organization, AuditLogger $audit): RedirectResponse
    {
        $attributes = $request->validate([
            'workflow_code' => 'required|string|max:80|alpha_dash',
            'workflow_name' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
        ]);
        $code = strtoupper(trim($attributes['workflow_code']));
        $name = trim($attributes['workflow_name']);

        DB::beginTransaction();
        try {
            if (WorkflowDefinition::query()->where('company_id', $organization->companyId())->where('workflow_code', $code)->exists()) {
                throw ValidationException::withMessages(['workflow_code' => 'This Workflow Code already exists.']);
            }

            $definition = new WorkflowDefinition();
            $definition->company_id = $organization->companyId();
            $definition->workflow_code = $code;
            $definition->workflow_name = $name;
            $definition->description = $attributes['description'] ?? null;
            $definition->status = 'Active';
            $definition->created_by = auth('admin')->id();
            $definition->modified_by = auth('admin')->id();
            $definition->created_at = now();
            $definition->updated_at = now();
            $definition->save();

            $version = new WorkflowVersion();
            $version->company_id = $organization->companyId();
            $version->workflow_definition_id = $definition->id;
            $version->version_number = 1;
            $version->status = 'Draft';
            $version->created_by = auth('admin')->id();
            $version->created_at = now();
            $version->updated_at = now();
            $version->save();

            DB::commit();
            $audit->recordAfterCommit([
                'module' => 'processes', 'action' => 'create', 'event' => 'workflow_definition_created',
                'description' => 'Workflow Definition and initial Draft version created.',
                'auditable_type' => $definition->getMorphClass(), 'auditable_id' => $definition->id,
                'new_values' => ['workflow_code' => $code, 'workflow_name' => $name, 'version_number' => 1], 'request' => $request,
            ]);

            return redirect()->route('admin.workflow-definitions.show', [$definition, $version])->with('message', 'Workflow Definition created successfully.')->with('messageClass', 'successClass');
        } catch (ValidationException $exception) {
            DB::rollBack();
            throw $exception;
        } catch (QueryException $exception) {
            DB::rollBack();
            throw ValidationException::withMessages(['workflow_code' => 'This Workflow Code already exists.']);
        } catch (\Throwable $exception) {
            DB::rollBack();
            throw $exception;
        }
    }

    public function update(Request $request, WorkflowDefinition $workflow_definition, AuditLogger $audit): RedirectResponse
    {
        abort_if($workflow_definition->status === 'Deleted', 404);
        $attributes = $request->validate([
            'workflow_code' => 'required|string|max:80|alpha_dash',
            'workflow_name' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'status' => 'required|in:Active,Inactive',
        ]);
        $code = strtoupper(trim($attributes['workflow_code']));
        if (WorkflowDefinition::query()->where('workflow_code', $code)->where('id', '!=', $workflow_definition->id)->exists()) {
            throw ValidationException::withMessages(['workflow_code' => 'This Workflow Code already exists.']);
        }

        $before = $workflow_definition->only(['workflow_code', 'workflow_name', 'description', 'status']);
        $workflow_definition->workflow_code = $code;
        $workflow_definition->workflow_name = trim($attributes['workflow_name']);
        $workflow_definition->description = $attributes['description'] ?? null;
        $workflow_definition->status = $attributes['status'];
        $workflow_definition->modified_by = auth('admin')->id();
        $workflow_definition->updated_at = now();
        $workflow_definition->save();

        $audit->recordAfterCommit([
            'module' => 'processes', 'action' => 'update', 'event' => 'workflow_definition_updated',
            'description' => 'Workflow Definition metadata/status changed.',
            'auditable_type' => $workflow_definition->getMorphClass(), 'auditable_id' => $workflow_definition->id,
            'old_values' => $before, 'new_values' => $workflow_definition->only(['workflow_code', 'workflow_name', 'description', 'status']), 'request' => $request,
        ]);

        return back()->with('message', 'Workflow Definition updated successfully.')->with('messageClass', 'successClass');
    }

    public function show(WorkflowDefinition $workflow_definition, WorkflowVersion $workflow_version): View
    {
        abort_if($workflow_definition->status === 'Deleted' || (int) $workflow_version->workflow_definition_id !== (int) $workflow_definition->id, 404);

        return view('admin.workflow_definitions.show', [
            'definition' => $workflow_definition,
            'version' => $workflow_version->load('steps.process'),
            'processes' => ProcessItem::query()->where('status', 'Active')->orderBy('process_name')->get(),
        ]);
    }

    public function createVersion(Request $request, WorkflowDefinition $workflow_definition, CurrentOrganizationContext $organization, AuditLogger $audit): RedirectResponse
    {
        abort_if($workflow_definition->status !== 'Active', 422, 'Inactive Workflow Definitions cannot create new versions.');
        DB::beginTransaction();
        try {
            $definition = WorkflowDefinition::query()->whereKey($workflow_definition->id)->lockForUpdate()->firstOrFail();
            $source = WorkflowVersion::query()->where('workflow_definition_id', $definition->id)->orderByDesc('version_number')->lockForUpdate()->firstOrFail();
            $nextNumber = ((int) $source->version_number) + 1;
            $version = new WorkflowVersion();
            $version->company_id = $organization->companyId();
            $version->workflow_definition_id = $definition->id;
            $version->version_number = $nextNumber;
            $version->status = 'Draft';
            $version->created_by = auth('admin')->id();
            $version->created_at = now();
            $version->updated_at = now();
            $version->save();

            foreach ($source->steps()->orderBy('sequence')->get() as $sourceStep) {
                $version->steps()->create([
                    'company_id' => $organization->companyId(), 'process_id' => $sourceStep->process_id,
                    'sequence' => $sourceStep->sequence, 'step_label' => $sourceStep->step_label,
                    'description' => $sourceStep->description, 'created_at' => now(), 'updated_at' => now(),
                ]);
            }
            DB::commit();
            $audit->recordAfterCommit([
                'module' => 'processes', 'action' => 'create', 'event' => 'workflow_version_created',
                'description' => 'New Draft Workflow Version copied from the previous version.',
                'auditable_type' => $version->getMorphClass(), 'auditable_id' => $version->id,
                'new_values' => ['workflow_definition_id' => $definition->id, 'version_number' => $nextNumber, 'source_version_id' => $source->id], 'request' => $request,
            ]);

            return redirect()->route('admin.workflow-definitions.show', [$definition, $version])->with('message', 'New Draft version created.')->with('messageClass', 'successClass');
        } catch (\Throwable $exception) {
            DB::rollBack();
            throw $exception;
        }
    }

    public function finalizeVersion(Request $request, WorkflowDefinition $workflow_definition, WorkflowVersion $workflow_version, AuditLogger $audit): RedirectResponse
    {
        abort_if((int) $workflow_version->workflow_definition_id !== (int) $workflow_definition->id, 404);
        if ($workflow_version->status !== 'Draft') {
            throw ValidationException::withMessages(['workflow_version' => 'Only Draft versions can be finalized.']);
        }
        DB::beginTransaction();
        try {
            $steps = $workflow_version->steps()->orderBy('sequence')->get();
            if ($steps->isEmpty() || $steps->pluck('sequence')->unique()->count() !== $steps->count() || $steps->contains(fn (WorkflowVersionStep $step): bool => (int) $step->sequence < 1)) {
                throw ValidationException::withMessages(['workflow_version' => 'A version must contain valid, uniquely ordered steps before finalization.']);
            }
            $processIds = $steps->pluck('process_id')->unique();
            if (ProcessItem::query()->whereIn('id', $processIds)->where('status', '!=', 'Deleted')->count() !== $processIds->count()) {
                throw ValidationException::withMessages(['workflow_version' => 'A finalized version cannot contain a deleted Process.']);
            }

            $workflow_version->status = 'Finalized';
            $workflow_version->finalized_by = auth('admin')->id();
            $workflow_version->finalized_at = now();
            $workflow_version->updated_at = now();
            $workflow_version->save();
            DB::commit();
        } catch (\Throwable $exception) {
            DB::rollBack();
            throw $exception;
        }
        $audit->recordAfterCommit([
            'module' => 'processes', 'action' => 'update', 'event' => 'workflow_version_finalized',
            'description' => 'Workflow Version finalized and locked.',
            'auditable_type' => $workflow_version->getMorphClass(), 'auditable_id' => $workflow_version->id,
            'new_values' => ['status' => 'Finalized', 'finalized_by' => auth('admin')->id(), 'finalized_at' => $workflow_version->finalized_at], 'request' => $request,
        ]);

        return back()->with('message', 'Workflow Version finalized and locked.')->with('messageClass', 'successClass');
    }

    public function storeStep(Request $request, WorkflowDefinition $workflow_definition, WorkflowVersion $workflow_version, CurrentOrganizationContext $organization, AuditLogger $audit): RedirectResponse
    {
        abort_if((int) $workflow_version->workflow_definition_id !== (int) $workflow_definition->id, 404);
        if ($workflow_version->status !== 'Draft') {
            throw ValidationException::withMessages(['workflow_version' => 'Finalized versions are immutable.']);
        }
        $attributes = $request->validate(['process_id' => 'required|integer', 'sequence' => 'required|integer|min:1', 'step_label' => 'nullable|string|max:255', 'description' => 'nullable|string|max:5000']);
        if (! ProcessItem::query()->whereKey($attributes['process_id'])->where('status', 'Active')->exists()) {
            throw ValidationException::withMessages(['process_id' => 'Please select a valid active Process.']);
        }
        if ($workflow_version->steps()->where('sequence', $attributes['sequence'])->exists()) {
            throw ValidationException::withMessages(['sequence' => 'Sequence numbers must be unique within a version.']);
        }
        $step = $workflow_version->steps()->create(array_merge($attributes, ['company_id' => $organization->companyId(), 'created_at' => now(), 'updated_at' => now()]));
        $audit->recordAfterCommit([
            'module' => 'processes', 'action' => 'create', 'event' => 'workflow_step_created',
            'description' => 'Workflow Version step added.', 'auditable_type' => $step->getMorphClass(), 'auditable_id' => $step->id,
            'new_values' => $step->only(['workflow_version_id', 'process_id', 'sequence', 'step_label', 'description']), 'request' => $request,
        ]);

        return back()->with('message', 'Workflow step added.')->with('messageClass', 'successClass');
    }

    public function updateStep(Request $request, WorkflowDefinition $workflow_definition, WorkflowVersion $workflow_version, WorkflowVersionStep $workflow_version_step, AuditLogger $audit): RedirectResponse
    {
        abort_if((int) $workflow_version->workflow_definition_id !== (int) $workflow_definition->id || (int) $workflow_version_step->workflow_version_id !== (int) $workflow_version->id, 404);
        if ($workflow_version->status !== 'Draft') {
            throw ValidationException::withMessages(['workflow_version' => 'Finalized versions are immutable.']);
        }
        $attributes = $request->validate(['process_id' => 'required|integer', 'sequence' => 'required|integer|min:1', 'step_label' => 'nullable|string|max:255', 'description' => 'nullable|string|max:5000']);
        if (! ProcessItem::query()->whereKey($attributes['process_id'])->where('status', 'Active')->exists()) {
            throw ValidationException::withMessages(['process_id' => 'Please select a valid active Process.']);
        }
        if ($workflow_version->steps()->where('sequence', $attributes['sequence'])->where('id', '!=', $workflow_version_step->id)->exists()) {
            throw ValidationException::withMessages(['sequence' => 'Sequence numbers must be unique within a version.']);
        }
        $before = $workflow_version_step->only(['process_id', 'sequence', 'step_label', 'description']);
        $workflow_version_step->fill($attributes);
        $workflow_version_step->updated_at = now();
        $workflow_version_step->save();
        $audit->recordAfterCommit([
            'module' => 'processes', 'action' => 'update', 'event' => 'workflow_step_updated',
            'description' => 'Workflow Version step changed.', 'auditable_type' => $workflow_version_step->getMorphClass(), 'auditable_id' => $workflow_version_step->id,
            'old_values' => $before, 'new_values' => $workflow_version_step->only(['process_id', 'sequence', 'step_label', 'description']), 'request' => $request,
        ]);

        return back()->with('message', 'Workflow step updated.')->with('messageClass', 'successClass');
    }

    public function destroyStep(Request $request, WorkflowDefinition $workflow_definition, WorkflowVersion $workflow_version, WorkflowVersionStep $workflow_version_step, AuditLogger $audit): RedirectResponse
    {
        abort_if((int) $workflow_version->workflow_definition_id !== (int) $workflow_definition->id || (int) $workflow_version_step->workflow_version_id !== (int) $workflow_version->id, 404);
        if ($workflow_version->status !== 'Draft') {
            throw ValidationException::withMessages(['workflow_version' => 'Finalized versions are immutable.']);
        }
        $before = $workflow_version_step->only(['workflow_version_id', 'process_id', 'sequence', 'step_label', 'description']);
        $workflow_version_step->delete();
        $audit->recordAfterCommit([
            'module' => 'processes', 'action' => 'delete', 'event' => 'workflow_step_deleted',
            'description' => 'Draft Workflow Version step removed.', 'auditable_type' => WorkflowVersionStep::class, 'auditable_id' => $workflow_version_step->id,
            'old_values' => $before, 'request' => $request,
        ]);

        return back()->with('message', 'Workflow step removed.')->with('messageClass', 'successClass');
    }

    public function destroyVersion(Request $request, WorkflowDefinition $workflow_definition, WorkflowVersion $workflow_version, AuditLogger $audit): RedirectResponse
    {
        abort_if((int) $workflow_version->workflow_definition_id !== (int) $workflow_definition->id, 404);
        if ($workflow_version->status !== 'Draft') {
            throw ValidationException::withMessages(['workflow_version' => 'Finalized versions cannot be deleted.']);
        }
        DB::beginTransaction();
        try {
            $before = $workflow_version->only(['workflow_definition_id', 'version_number', 'status']);
            $workflow_version->steps()->delete();
            $workflow_version->delete();
            DB::commit();
            $audit->recordAfterCommit([
                'module' => 'processes', 'action' => 'delete', 'event' => 'workflow_version_deleted',
                'description' => 'Unreferenced Draft Workflow Version removed.', 'auditable_type' => WorkflowVersion::class, 'auditable_id' => $workflow_version->id,
                'old_values' => $before, 'request' => $request,
            ]);
        } catch (\Throwable $exception) {
            DB::rollBack();
            throw $exception;
        }

        return redirect()->route('admin.workflow-definitions.index')->with('message', 'Draft version removed.')->with('messageClass', 'successClass');
    }
}
