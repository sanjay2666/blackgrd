<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RecordStatus;
use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Factory;
use App\Models\Machine;
use App\Models\ProcessItem;
use App\Rules\RecordStatusRule;
use App\Services\MachineMasterService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MachineController extends Controller
{
    public function index(Request $request): View
    {
        $query = Machine::with(['processItem', 'department', 'factory'])->notDeleted();
        if ($request->filled('search')) {
            $query->where(fn ($q) => $q->where('name', 'like', '%'.$request->search.'%')->orWhereHas('processItem', fn ($p) => $p->where('process_name', 'like', '%'.$request->search.'%')));
        }
        $query->when($request->filled('process_id'), fn ($q) => $q->where('process_wise', $request->integer('process_id')))->when($request->filled('department_id'), fn ($q) => $q->where('department_id', $request->integer('department_id')))->when($request->filled('factory_id'), fn ($q) => $q->where('factory_id', $request->integer('factory_id')))->when(in_array($request->status, ['Active', 'Inactive'], true), fn ($q) => $q->where('status', $request->status));

        return view('admin.machines.index', ['machines' => $query->latest('id')->paginate(config('app.pagination_limit'))->withQueryString(), 'processItems' => ProcessItem::active()->orderBy('process_name')->get(), 'departments' => Department::active()->orderBy('department_name')->get(), 'factories' => Factory::active()->orderBy('name')->get(), 'statusOptions' => RecordStatus::formOptions()]);
    }

    public function create(): View
    {
        return view('admin.machines.create', array_merge(['processItems' => ProcessItem::active()->orderBy('id')->get()], $this->locations()))->with('statusOptions', RecordStatus::formOptions());
    }

    public function store(Request $request, MachineMasterService $service): RedirectResponse
    {
        $service->save(new Machine(), $this->validated($request));

        return redirect()->route('admin.machines.index')->with('message', 'Machine added successfully.')->with('messageClass', 'successClass');
    }

    public function edit(Machine $machine): View
    {
        abort_if($machine->status === 'Deleted', 404);

        return view('admin.machines.edit', array_merge(compact('machine'), ['processItems' => ProcessItem::active()->orderBy('id')->get()], $this->locations()))->with('statusOptions', RecordStatus::formOptions());
    }

    public function update(Request $request, Machine $machine, MachineMasterService $service): RedirectResponse
    {
        abort_if($machine->status === 'Deleted', 404);
        $service->save($machine, $this->validated($request));

        return redirect()->route('admin.machines.index')->with('message', 'Machine updated successfully.')->with('messageClass', 'successClass');
    }

    public function activate(Machine $machine, MachineMasterService $service): RedirectResponse
    {
        abort_if($machine->status === 'Deleted', 404);
        $service->transition($machine, 'Active');

        return back()->with('message', 'Machine activated successfully.');
    }

    public function deactivate(Machine $machine, MachineMasterService $service): RedirectResponse
    {
        abort_if($machine->status === 'Deleted', 404);
        $service->transition($machine, 'Inactive');

        return back()->with('message', 'Machine deactivated successfully.');
    }

    public function destroy(): never
    {
        abort(422, 'Machines cannot be deleted; deactivate the Machine instead.');
    }

    private function validated(Request $request): array
    {
        return $request->validate(['name' => 'required|string|max:255', 'process_wise' => 'required|integer', 'is_busy' => 'nullable|in:1,0', 'factory_id' => 'nullable|integer', 'department_id' => 'nullable|integer', 'status' => ['required', new RecordStatusRule()]]);
    }

    private function locations(): array
    {
        return ['departments' => Department::active()->orderBy('department_name')->get(), 'factories' => Factory::active()->orderBy('name')->get()];
    }
}
