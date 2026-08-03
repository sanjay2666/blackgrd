<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RecordStatus;
use App\Http\Controllers\Controller;
use App\Models\Warehouse;
use App\Rules\RecordStatusRule;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;

class WarehouseController extends Controller
{
    public function index(Request $request)
    {
        $query = Warehouse::query();
        $query->notDeleted();

        if ($request->filled('search')) {
            $query->where(function ($query) use ($request) {
                $query->where('warehouse_name', 'like', '%'.$request->search.'%');
                $query->orWhere('location', 'like', '%'.$request->search.'%');
                $query->orWhere('supervisor_id', 'like', '%'.$request->search.'%');
                $query->orWhere('contact_number', 'like', '%'.$request->search.'%');
            });
        }

        $warehouses = $query->orderBy('id', 'desc')->paginate(config('app.pagination_limit'))->withQueryString();

        return view('admin.warehouses.index', compact('warehouses'));
    }

    public function create()
    {
        return view('admin.warehouses.create', ['statusOptions' => RecordStatus::formOptions()]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'warehouse_name' => 'required',
            'location' => 'required',
            'capacity' => 'required',
            'supervisor_id' => 'nullable',
            'contact_number' => 'required',
            'process_type_id' => 'nullable',
            'status' => ['required', new RecordStatusRule],
        ], [
            'warehouse_name.required' => 'Please enter Warehouse Name.',
            'location.required' => 'Please enter Location.',
            'capacity.required' => 'Please enter Capacity.',
            'contact_number.required' => 'Please enter Contact Number.',
            'status.required' => 'Please select Status.',
        ]);

        if ($validator->fails()) {
            Session::put('message', $validator->errors()->first());
            Session::put('messageClass', 'errorClass');

            return redirect()->back()->withInput();
        }

        DB::beginTransaction();
        try {
            $warehouse = new Warehouse;
            $warehouse->warehouse_name = $request->warehouse_name;
            $warehouse->location = $request->location;
            $warehouse->capacity = $request->capacity;
            $warehouse->supervisor_id = $request->supervisor_id;
            $warehouse->financial_year = currentFinancialYear();
            $warehouse->created_by = Auth::id();
            $warehouse->modified_by = Auth::id();
            $warehouse->contact_number = $request->contact_number;
            $warehouse->process_type_id = $request->has('process_type_id') ? 1 : 0;
            $warehouse->status = $request->status;
            $warehouse->created_at = now();
            $warehouse->updated_at = now();
            $warehouse->save();

            DB::commit();
            Session::put('message', 'Warehouses added successfully.');
            Session::put('messageClass', 'successClass');

            return redirect()->route('admin.warehouses.index');
        } catch (Exception $e) {
            DB::rollBack();
            Session::put('message', 'Failed to save Warehouses. Error: '.$e->getMessage());
            Session::put('messageClass', 'errorClass');

            return back()->withInput();
        }
    }

    public function edit($id)
    {
        $id = dec($id);
        $warehouse = Warehouse::where('id', $id)->firstOrFail();
        if ($warehouse->status === 'Deleted') {
            abort(404);
        }

        return view('admin.warehouses.edit', compact('warehouse'))->with('statusOptions', RecordStatus::formOptions());
    }

    public function update(Request $request, $id)
    {
        $id = dec($id);
        $warehouse = Warehouse::where('id', $id)->firstOrFail();
        if ($warehouse->status === 'Deleted') {
            abort(404);
        }

        $validator = Validator::make($request->all(), [
            'warehouse_name' => 'required',
            'location' => 'required',
            'capacity' => 'required',
            'supervisor_id' => 'nullable',
            'contact_number' => 'required',
            'process_type_id' => 'nullable',
            'status' => ['required', new RecordStatusRule],
        ], [
            'warehouse_name.required' => 'Please enter Warehouse Name.',
            'location.required' => 'Please enter Location.',
            'capacity.required' => 'Please enter Capacity.',
            'contact_number.required' => 'Please enter Contact Number.',
            'status.required' => 'Please select Status.',
        ]);

        if ($validator->fails()) {
            Session::put('message', $validator->errors()->first());
            Session::put('messageClass', 'errorClass');

            return redirect()->back()->withInput();
        }

        DB::beginTransaction();
        try {
            $warehouse->warehouse_name = $request->warehouse_name;
            $warehouse->location = $request->location;
            $warehouse->capacity = $request->capacity;
            $warehouse->supervisor_id = $request->supervisor_id;
            $warehouse->modified_by = Auth::id();
            $warehouse->contact_number = $request->contact_number;
            $warehouse->process_type_id = $request->has('process_type_id') ? 1 : 0;
            $warehouse->status = $request->status;
            $warehouse->updated_at = now();
            $warehouse->save();

            DB::commit();
            Session::put('message', 'Warehouses updated successfully.');
            Session::put('messageClass', 'successClass');

            return redirect()->route('admin.warehouses.index');
        } catch (Exception $e) {
            DB::rollBack();
            Session::put('message', 'Failed to update Warehouses. Error: '.$e->getMessage());
            Session::put('messageClass', 'errorClass');

            return back()->withInput();
        }
    }

    public function destroy($id)
    {
        $id = dec($id);
        $warehouse = Warehouse::where('id', $id)->firstOrFail();
        if ($warehouse->status === 'Deleted') {
            abort(404);
        }

        DB::beginTransaction();
        try {
            $warehouse->status = 'Deleted';
            $warehouse->modified_by = Auth::id();
            $warehouse->updated_at = now();
            $warehouse->save();

            DB::commit();
            Session::put('message', 'Warehouses deleted successfully.');
            Session::put('messageClass', 'successClass');

            return response()->json(['success' => true]);
        } catch (Exception $e) {
            DB::rollBack();
            Session::put('message', 'Failed to delete Warehouses. Error: '.$e->getMessage());
            Session::put('messageClass', 'errorClass');

            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
