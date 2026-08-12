<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProcessItem;
use App\Models\WorkflowDefinition;
use App\Models\WorkflowVersion;
use App\Models\WorkflowVersionStep;
use App\Services\WorkflowDefinitionService;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

    public function store(Request $request, WorkflowDefinitionService $service): RedirectResponse
    {
        $attributes = $request->validate([
            'workflow_code' => 'required|string|max:80|alpha_dash',
            'workflow_name' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
        ]);
        try {
            $created = $service->createDefinition($attributes, auth('admin')->id(), $request);
        } catch (QueryException) {
            throw ValidationException::withMessages(['workflow_code' => 'This Workflow Code already exists.']);
        }

        return redirect()->route('admin.workflow-definitions.show', [$created['definition'], $created['version']])
            ->with('message', 'Workflow Definition created successfully.')
            ->with('messageClass', 'successClass');
    }

    public function update(Request $request, WorkflowDefinition $workflow_definition, WorkflowDefinitionService $service): RedirectResponse
    {
        abort_if($workflow_definition->status === 'Deleted', 404);
        $attributes = $request->validate([
            'workflow_code' => 'required|string|max:80|alpha_dash',
            'workflow_name' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'status' => 'required|in:Active,Inactive',
        ]);
        $service->updateDefinition($workflow_definition, $attributes, auth('admin')->id(), $request);

        return back()->with('message', 'Workflow Definition updated successfully.')->with('messageClass', 'successClass');
    }

    public function show(WorkflowDefinition $workflow_definition, WorkflowVersion $workflow_version): View
    {
        abort_if($workflow_definition->status === 'Deleted' || (int) $workflow_version->workflow_definition_id !== (int) $workflow_definition->id, 404);

        return view('admin.workflow_definitions.manage', [
            'definition' => $workflow_definition->load(['versions' => fn ($query) => $query->orderByDesc('version_number')]),
            'version' => $workflow_version->load('steps.process'),
            'processes' => ProcessItem::query()->where('status', 'Active')->orderBy('display_order')->orderBy('process_name')->get(),
        ]);
    }

    public function createVersion(Request $request, WorkflowDefinition $workflow_definition, WorkflowDefinitionService $service): RedirectResponse
    {
        $attributes = $request->validate([
            'effective_from' => 'nullable|date',
            'remarks' => 'nullable|string|max:5000',
        ]);
        $version = $service->createVersion($workflow_definition, $attributes, auth('admin')->id(), $request);

        return redirect()->route('admin.workflow-definitions.show', [$workflow_definition, $version])
            ->with('message', 'New Draft version created from the latest published version.')
            ->with('messageClass', 'successClass');
    }

    public function publishVersion(Request $request, WorkflowDefinition $workflow_definition, WorkflowVersion $workflow_version, WorkflowDefinitionService $service): RedirectResponse
    {
        $attributes = $request->validate([
            'effective_from' => 'nullable|date',
            'remarks' => 'nullable|string|max:5000',
        ]);
        $service->publishVersion($workflow_definition, $workflow_version, $attributes, auth('admin')->id(), $request);

        return back()->with('message', 'Workflow Version published and locked.')->with('messageClass', 'successClass');
    }

    public function storeStep(Request $request, WorkflowDefinition $workflow_definition, WorkflowVersion $workflow_version, WorkflowDefinitionService $service): RedirectResponse
    {
        $attributes = $request->validate(['process_id' => 'required|integer', 'sequence' => 'required|integer|min:1', 'step_label' => 'nullable|string|max:255', 'description' => 'nullable|string|max:5000']);
        $service->addStep($workflow_definition, $workflow_version, $attributes, $request);

        return back()->with('message', 'Workflow step added.')->with('messageClass', 'successClass');
    }

    public function updateStep(Request $request, WorkflowDefinition $workflow_definition, WorkflowVersion $workflow_version, WorkflowVersionStep $workflow_version_step, WorkflowDefinitionService $service): RedirectResponse
    {
        $attributes = $request->validate(['process_id' => 'required|integer', 'sequence' => 'required|integer|min:1', 'step_label' => 'nullable|string|max:255', 'description' => 'nullable|string|max:5000']);
        $service->updateStep($workflow_definition, $workflow_version, $workflow_version_step, $attributes, $request);

        return back()->with('message', 'Workflow step updated.')->with('messageClass', 'successClass');
    }

    public function destroyStep(Request $request, WorkflowDefinition $workflow_definition, WorkflowVersion $workflow_version, WorkflowVersionStep $workflow_version_step, WorkflowDefinitionService $service): RedirectResponse
    {
        $service->removeStep($workflow_definition, $workflow_version, $workflow_version_step, $request);

        return back()->with('message', 'Workflow step removed.')->with('messageClass', 'successClass');
    }

    public function destroyVersion(Request $request, WorkflowDefinition $workflow_definition, WorkflowVersion $workflow_version, WorkflowDefinitionService $service): RedirectResponse
    {
        $service->deleteVersion($workflow_definition, $workflow_version, $request);

        $fallback = $workflow_definition->versions()->orderByDesc('version_number')->first();
        if ($fallback !== null) {
            return redirect()->route('admin.workflow-definitions.show', [$workflow_definition, $fallback])
                ->with('message', 'Draft version removed.')
                ->with('messageClass', 'successClass');
        }

        return redirect()->route('admin.workflow-definitions.index')
            ->with('message', 'Draft version removed.')
            ->with('messageClass', 'successClass');
    }
}
