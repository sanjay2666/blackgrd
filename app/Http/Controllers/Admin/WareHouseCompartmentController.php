<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RecordStatus;
use App\Http\Controllers\Controller;
use App\Models\WarehouseCompartment;
use App\Rules\RecordStatusRule;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;

class WareHouseCompartmentController extends Controller
{
    public function index(Request $request)
    {
        $query = WarehouseCompartment::query();
        $query->notDeleted();

        if ($request->filled('search')) {
            $query->where(function ($query) use ($request) {
                $query->where('compartment_name', 'like', '%'.$request->search.'%');
                $query->orWhere('warehouse_id', 'like', '%'.$request->search.'%');
                $query->orWhere('ind_emp_id', 'like', '%'.$request->search.'%');
            });
        }

        $wareHouseCompartments = $query->orderBy('id', 'desc')->paginate(config('app.pagination_limit'))->withQueryString();

        return view('admin.ware_house_compartments.index', compact('wareHouseCompartments'));
    }

    public function create()
    {
        return view('admin.ware_house_compartments.create', ['statusOptions' => RecordStatus::formOptions()]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'compartment_name' => 'required',
            'warehouse_id' => 'required',
            'ind_emp_id' => 'nullable',
            'status' => ['required', new RecordStatusRule],
        ], [
            'compartment_name.required' => 'Please enter Compartment Name.',
            'warehouse_id.required' => 'Please enter Warehouse Id.',
            'status.required' => 'Please select Status.',
        ]);

        if ($validator->fails()) {
            Session::put('message', $validator->errors()->first());
            Session::put('messageClass', 'errorClass');

            return redirect()->back()->withInput();
        }

        DB::beginTransaction();
        try {
            $wareHouseCompartment = new WarehouseCompartment;
            $wareHouseCompartment->compartment_name = $request->compartment_name;
            $wareHouseCompartment->warehouse_id = $request->warehouse_id;
            $wareHouseCompartment->ind_emp_id = $request->ind_emp_id;
            $wareHouseCompartment->financial_year = currentFinancialYear();
            $wareHouseCompartment->created_by = Auth::id();
            $wareHouseCompartment->modified_by = Auth::id();
            $wareHouseCompartment->status = $request->status;
            $wareHouseCompartment->created_at = now();
            $wareHouseCompartment->updated_at = now();
            $wareHouseCompartment->save();

            DB::commit();
            Session::put('message', 'Warehouse Compartments added successfully.');
            Session::put('messageClass', 'successClass');

            return redirect()->route('admin.ware-house-compartments.index');
        } catch (Exception $e) {
            DB::rollBack();
            Session::put('message', 'Failed to save Warehouse Compartments. Error: '.$e->getMessage());
            Session::put('messageClass', 'errorClass');

            return back()->withInput();
        }
    }

    public function edit($id)
    {
        $id = dec($id);
        $wareHouseCompartment = WarehouseCompartment::where('id', $id)->firstOrFail();
        if ($wareHouseCompartment->status === 'Deleted') {
            abort(404);
        }

        return view('admin.ware_house_compartments.edit', compact('wareHouseCompartment'))
            ->with('statusOptions', RecordStatus::formOptions());
    }

    public function update(Request $request, $id)
    {
        $id = dec($id);
        $wareHouseCompartment = WarehouseCompartment::where('id', $id)->firstOrFail();
        if ($wareHouseCompartment->status === 'Deleted') {
            abort(404);
        }

        $validator = Validator::make($request->all(), [
            'compartment_name' => 'required',
            'warehouse_id' => 'required',
            'ind_emp_id' => 'nullable',
            'status' => ['required', new RecordStatusRule],
        ], [
            'compartment_name.required' => 'Please enter Compartment Name.',
            'warehouse_id.required' => 'Please enter Warehouse Id.',
            'status.required' => 'Please select Status.',
        ]);

        if ($validator->fails()) {
            Session::put('message', $validator->errors()->first());
            Session::put('messageClass', 'errorClass');

            return redirect()->back()->withInput();
        }

        DB::beginTransaction();
        try {
            $wareHouseCompartment->compartment_name = $request->compartment_name;
            $wareHouseCompartment->warehouse_id = $request->warehouse_id;
            $wareHouseCompartment->ind_emp_id = $request->ind_emp_id;
            $wareHouseCompartment->modified_by = Auth::id();
            $wareHouseCompartment->status = $request->status;
            $wareHouseCompartment->updated_at = now();
            $wareHouseCompartment->save();

            DB::commit();
            Session::put('message', 'Warehouse Compartments updated successfully.');
            Session::put('messageClass', 'successClass');

            return redirect()->route('admin.ware-house-compartments.index');
        } catch (Exception $e) {
            DB::rollBack();
            Session::put('message', 'Failed to update Warehouse Compartments. Error: '.$e->getMessage());
            Session::put('messageClass', 'errorClass');

            return back()->withInput();
        }
    }

    public function destroy($id)
    {
        $id = dec($id);
        $wareHouseCompartment = WarehouseCompartment::where('id', $id)->firstOrFail();
        if ($wareHouseCompartment->status === 'Deleted') {
            abort(404);
        }

        DB::beginTransaction();
        try {
            $wareHouseCompartment->status = 'Deleted';
            $wareHouseCompartment->modified_by = Auth::id();
            $wareHouseCompartment->updated_at = now();
            $wareHouseCompartment->save();

            DB::commit();
            Session::put('message', 'Warehouse Compartments deleted successfully.');
            Session::put('messageClass', 'successClass');

            return response()->json(['success' => true]);
        } catch (Exception $e) {
            DB::rollBack();
            Session::put('message', 'Failed to delete Warehouse Compartments. Error: '.$e->getMessage());
            Session::put('messageClass', 'errorClass');

            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
