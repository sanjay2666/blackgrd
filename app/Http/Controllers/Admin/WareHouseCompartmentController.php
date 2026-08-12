<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RecordStatus;
use App\Http\Controllers\Controller;
use App\Models\WarehouseCompartment;
use App\Rules\RecordStatusRule;
use App\Services\WarehouseCompartmentMasterService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class WareHouseCompartmentController extends Controller
{
    public function index(Request $request, WarehouseCompartmentMasterService $service)
    {
        $wareHouseCompartments = $service->query()
            ->when($request->filled('search'), fn ($query) => $query->where('compartment_name', 'like', '%'.$request->string('search').'%'))
            ->when($request->filled('warehouse_id'), fn ($query) => $query->where('warehouse_id', $request->integer('warehouse_id')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->latest('id')->paginate(config('app.pagination_limit'))->withQueryString();

        return view('admin.ware_house_compartments.index', ['wareHouseCompartments' => $wareHouseCompartments, 'warehouses' => $service->availableWarehouses(), 'statusOptions' => RecordStatus::formOptions()]);
    }

    public function create(WarehouseCompartmentMasterService $service)
    {
        return view('admin.ware_house_compartments.create', ['warehouses' => $service->availableWarehouses(), 'statusOptions' => RecordStatus::formOptions()]);
    }

    public function store(Request $request, WarehouseCompartmentMasterService $service)
    {
        try {
            $service->save(new WarehouseCompartment, $request->validate(['compartment_name' => 'required|string|max:255', 'warehouse_id' => 'required|integer', 'ind_emp_id' => 'nullable|integer', 'status' => ['required', new RecordStatusRule]]), $request);
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors())->withInput();
        }

        return redirect()->route('admin.ware-house-compartments.index')->with('message', 'Warehouse Compartment added successfully.')->with('messageClass', 'successClass');
    }

    public function edit($id, WarehouseCompartmentMasterService $service)
    {
        $id = dec($id);
        $wareHouseCompartment = $service->findForCurrentCompany($id);
        if ($wareHouseCompartment->status === RecordStatus::Deleted->value) {
            abort(404);
        }

        return view('admin.ware_house_compartments.edit', ['wareHouseCompartment' => $wareHouseCompartment, 'warehouses' => app(WarehouseCompartmentMasterService::class)->availableWarehouses((int) $wareHouseCompartment->warehouse_id)])
            ->with('statusOptions', RecordStatus::formOptions());
    }

    public function update(Request $request, $id, WarehouseCompartmentMasterService $service)
    {
        $id = dec($id);
        $wareHouseCompartment = $service->findForCurrentCompany($id);
        if ($wareHouseCompartment->status === RecordStatus::Deleted->value) {
            abort(404);
        }

        try {
            $service->save($wareHouseCompartment, $request->validate(['compartment_name' => 'required|string|max:255', 'warehouse_id' => 'required|integer', 'ind_emp_id' => 'nullable|integer', 'status' => ['required', new RecordStatusRule]]), $request);
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors())->withInput();
        }

        return redirect()->route('admin.ware-house-compartments.index')->with('message', 'Warehouse Compartment updated successfully.')->with('messageClass', 'successClass');
    }

    public function destroy($id, WarehouseCompartmentMasterService $service)
    {
        $id = dec($id);
        $wareHouseCompartment = $service->findForCurrentCompany($id);
        if ($wareHouseCompartment->status === RecordStatus::Deleted->value) {
            abort(404);
        }

        $service->ensureNotDeletable($wareHouseCompartment);
    }

    public function activate($id, WarehouseCompartmentMasterService $service, Request $request)
    {
        $compartment = $this->find($id);
        $service->transition($compartment, RecordStatus::Active->value, $request);

        return back()->with('message', 'Warehouse Compartment activated successfully.')->with('messageClass', 'successClass');
    }

    public function deactivate($id, WarehouseCompartmentMasterService $service, Request $request)
    {
        $compartment = $this->find($id);
        try {
            $service->transition($compartment, RecordStatus::Inactive->value, $request);
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        return back()->with('message', 'Warehouse Compartment deactivated successfully.')->with('messageClass', 'successClass');
    }

    private function find($id): WarehouseCompartment
    {
        $compartment = app(WarehouseCompartmentMasterService::class)->findForCurrentCompany(dec($id));
        abort_if($compartment->status === RecordStatus::Deleted->value, 404);

        return $compartment;
    }
}
