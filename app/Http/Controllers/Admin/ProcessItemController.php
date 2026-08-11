<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RecordStatus;
use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\ProcessItem;
use App\Rules\RecordStatusRule;
use App\Services\CurrentOrganizationContext;
use App\Services\ProcessMasterService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProcessItemController extends Controller
{
    public function index(Request $request): View
    {
        $query = ProcessItem::query()->notDeleted()->with('department');
        $query->when($request->filled('search'), fn ($q) => $q->where(fn ($nested) => $nested
            ->where('process_name', 'like', '%'.$request->string('search').'%')
            ->orWhere('short_code', 'like', '%'.$request->string('search').'%')
            ->orWhere('entry_name', 'like', '%'.$request->string('search').'%')
            ->orWhere('output_name', 'like', '%'.$request->string('search').'%')));
        $query->when($request->filled('department_id'), fn ($q) => $q->where('department_id', $request->integer('department_id')));
        $query->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')));

        return view('admin.process_items.index', [
            'processItems' => $query->orderByRaw('COALESCE(display_order, 999999), id')->paginate(config('app.pagination_limit'))->withQueryString(),
            'departments' => Department::active()->orderBy('department_name')->get(),
            'statusOptions' => RecordStatus::formOptions(),
        ]);
    }

    public function create(CurrentOrganizationContext $organization): View
    {
        return view('admin.process_items.create', $this->formData($organization));
    }

    public function store(Request $request, ProcessMasterService $service): RedirectResponse
    {
        $service->save(new ProcessItem, $this->validated($request));

        return redirect()->route('admin.process-items.index')->with('message', 'Process added successfully.')->with('messageClass', 'successClass');
    }

    public function edit(ProcessItem $process_item, CurrentOrganizationContext $organization): View
    {
        abort_if($process_item->status === 'Deleted', 404);

        return view('admin.process_items.edit', array_merge(['processItem' => $process_item], $this->formData($organization)));
    }

    public function update(Request $request, ProcessItem $process_item, ProcessMasterService $service): RedirectResponse
    {
        abort_if($process_item->status === 'Deleted', 404);
        $service->save($process_item, $this->validated($request));

        return redirect()->route('admin.process-items.index')->with('message', 'Process updated successfully.')->with('messageClass', 'successClass');
    }

    public function activate(ProcessItem $process_item, ProcessMasterService $service): RedirectResponse
    {
        abort_if($process_item->status === 'Deleted', 404);
        $service->transition($process_item, 'Active');

        return back()->with('message', 'Process activated successfully.')->with('messageClass', 'successClass');
    }

    public function deactivate(ProcessItem $process_item, ProcessMasterService $service): RedirectResponse
    {
        abort_if($process_item->status === 'Deleted', 404);
        $service->transition($process_item, 'Inactive');

        return back()->with('message', 'Process deactivated successfully.')->with('messageClass', 'successClass');
    }

    public function destroy(ProcessItem $process_item, ProcessMasterService $service): never
    {
        abort_if($process_item->status === 'Deleted', 404);
        $service->rejectDeletion($process_item);
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        return $request->validate([
            'process_name' => 'required|string|max:255', 'short_code' => 'required|string|max:30|alpha_dash',
            'description' => 'nullable|string|max:5000', 'entry_name' => 'nullable|string|max:255',
            'output_name' => 'required|string|max:255', 'department_id' => 'nullable|integer',
            'display_order' => 'nullable|integer|min:0', 'process_sl_no_last' => 'nullable|integer|min:0',
            'status' => ['required', new RecordStatusRule],
        ]);
    }

    /** @return array<string, mixed> */
    private function formData(CurrentOrganizationContext $organization): array
    {
        return ['departments' => Department::active()->where('company_id', $organization->companyId())->orderBy('department_name')->get(), 'statusOptions' => RecordStatus::formOptions()];
    }
}
