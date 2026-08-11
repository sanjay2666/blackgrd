<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RecordStatus;
use App\Http\Controllers\Controller;
use App\Models\Factory;
use App\Models\Warehouse;
use App\Rules\RecordStatusRule;
use App\Services\CurrentOrganizationContext;
use App\Services\WarehouseMasterService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class WarehouseController extends Controller
{
    public function index(Request $request): View
    {
        $query = Warehouse::query()->notDeleted()
            ->when($request->filled('search'), fn ($q) => $q->where(function ($q) use ($request): void {
                $term = '%'.$request->string('search').'%';
                $q->where('warehouse_name', 'like', $term)->orWhere('location', 'like', $term)->orWhere('contact_number', 'like', $term);
            }))
            ->when($request->filled('factory_id'), fn ($q) => $q->where('factory_id', $request->integer('factory_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')));

        return view('admin.warehouses.index', [
            'warehouses' => $query->with('factory')->withCount('compartments')->latest('id')->paginate(config('app.pagination_limit'))->withQueryString(),
            'factories' => Factory::active()->orderBy('name')->get(),
            'statusOptions' => RecordStatus::formOptions(),
        ]);
    }

    public function create(CurrentOrganizationContext $organization): View
    {
        return view('admin.warehouses.create', $this->formData($organization));
    }

    public function store(Request $request, WarehouseMasterService $service): RedirectResponse
    {
        $service->save(new Warehouse, $this->validated($request));

        return redirect()->route('admin.warehouses.index')->with('message', 'Warehouse added successfully.')->with('messageClass', 'successClass');
    }

    public function edit($id, CurrentOrganizationContext $organization): View
    {
        $id = dec($id);
        $warehouse = Warehouse::where('id', $id)->firstOrFail();
        if ($warehouse->status === 'Deleted') {
            abort(404);
        }

        return view('admin.warehouses.edit', array_merge(['warehouse' => $warehouse], $this->formData($organization)));
    }

    public function update(Request $request, $id, WarehouseMasterService $service): RedirectResponse
    {
        $id = dec($id);
        $warehouse = Warehouse::where('id', $id)->firstOrFail();
        if ($warehouse->status === 'Deleted') {
            abort(404);
        }

        $service->save($warehouse, $this->validated($request));

        return redirect()->route('admin.warehouses.index')->with('message', 'Warehouse updated successfully.')->with('messageClass', 'successClass');
    }

    public function activate($id, WarehouseMasterService $service): RedirectResponse
    {
        $warehouse = $this->find($id);
        $service->transition($warehouse, 'Active');

        return back()->with('message', 'Warehouse activated successfully.')->with('messageClass', 'successClass');
    }

    public function deactivate($id, WarehouseMasterService $service): RedirectResponse
    {
        $warehouse = $this->find($id);
        $service->transition($warehouse, 'Inactive');

        return back()->with('message', 'Warehouse deactivated successfully.')->with('messageClass', 'successClass');
    }

    public function destroy($id, WarehouseMasterService $service): RedirectResponse
    {
        $service->ensureNotDeletable($this->find($id));
    }

    private function find($id): Warehouse
    {
        $warehouse = Warehouse::whereKey(dec($id))->firstOrFail();
        abort_if($warehouse->status === 'Deleted', 404);

        return $warehouse;
    }

    /** @return array<string, mixed> */
    private function formData(CurrentOrganizationContext $organization): array
    {
        return ['factories' => Factory::active()->where('company_id', $organization->companyId())->orderBy('name')->get(), 'statusOptions' => RecordStatus::formOptions()];
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        return $request->validate([
            'warehouse_name' => 'required|string|max:255', 'location' => 'nullable|string|max:255',
            'capacity' => 'nullable|numeric', 'supervisor_id' => 'nullable|integer',
            'contact_number' => 'nullable|string|max:20', 'process_type_id' => 'nullable|integer|min:0',
            'factory_id' => 'nullable|integer', 'status' => ['required', 'in:Active,Inactive', new RecordStatusRule],
        ]);
    }
}
