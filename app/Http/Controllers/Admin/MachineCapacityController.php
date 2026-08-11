<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RecordStatus;
use App\Http\Controllers\Controller;
use App\Models\Machine;
use App\Models\MachineCapacity;
use App\Models\UnitType;
use App\Services\MachineCapacityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MachineCapacityController extends Controller
{
    public function index(Request $request): View
    {
        $query = MachineCapacity::query()->with(['machine.processItem', 'unitType'])->notDeleted()->when($request->filled('search'), fn ($q) => $q->whereHas('machine', fn ($m) => $m->where('name', 'like', '%'.$request->string('search').'%')));

        return view('admin.machine-capacities.index', ['capacities' => $query->latest('id')->paginate(config('app.pagination_limit'))->withQueryString()]);
    }

    public function create(): View
    {
        return view('admin.machine-capacities.create', $this->formData());
    }

    public function store(Request $request, MachineCapacityService $service): RedirectResponse
    {
        $service->save(new MachineCapacity(), $this->validated($request), $request);

        return redirect()->route('admin.machine-capacities.index')->with('message', 'Machine capacity saved successfully.')->with('messageClass', 'successClass');
    }

    public function edit(MachineCapacity $machineCapacity): View
    {
        abort_if($machineCapacity->status === RecordStatus::Deleted->value, 404);

        return view('admin.machine-capacities.edit', array_merge(['capacity' => $machineCapacity], $this->formData()));
    }

    public function update(Request $request, MachineCapacity $machineCapacity, MachineCapacityService $service): RedirectResponse
    {
        abort_if($machineCapacity->status === RecordStatus::Deleted->value, 404);
        $service->save($machineCapacity, $this->validated($request), $request);

        return redirect()->route('admin.machine-capacities.index')->with('message', 'Machine capacity updated successfully.')->with('messageClass', 'successClass');
    }

    public function destroy(Request $request, MachineCapacity $machineCapacity, MachineCapacityService $service): RedirectResponse
    {
        abort_if($machineCapacity->status === RecordStatus::Deleted->value, 404);
        $service->remove($machineCapacity, $request);

        return back()->with('message', 'Machine capacity removed successfully.')->with('messageClass', 'successClass');
    }

    private function validated(Request $request): array
    {
        return $request->validate(['machine_id' => 'required|integer', 'unit_type_id' => 'required|integer', 'capacity_value' => 'required|numeric|gt:0']);
    }

    /** @return array<string, mixed> */
    private function formData(): array
    {
        return ['machines' => Machine::active()->with('processItem')->orderBy('name')->get(), 'units' => UnitType::active()->orderBy('unit_type_name')->get()];
    }
}
