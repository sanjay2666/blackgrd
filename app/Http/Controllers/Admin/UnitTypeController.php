<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UnitType;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;

class UnitTypeController extends Controller
{
    public function index(Request $request)
    {
        $query = UnitType::query();
        $query->where('status', '!=', 'Deleted');

        if ($request->filled('search')) {
            $query->where(function ($query) use ($request) {
                $query->where('unit_type_name', 'like', '%'.$request->search.'%');
            });
        }

        $unitTypes = $query->orderBy('unit_type_id', 'desc')->paginate(config('app.pagination_limit'))->withQueryString();

        return view('admin.unit_types.index', compact('unitTypes'));
    }

    public function create()
    {
        return view('admin.unit_types.create');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'unit_type_name' => 'required',
            'status' => 'required|in:Active,Inactive',
        ], [
            'unit_type_name.required' => 'Please enter Unit Type Name.',
            'status.required' => 'Please select Status.',
        ]);

        if ($validator->fails()) {
            Session::put('message', $validator->errors()->first());
            Session::put('messageClass', 'errorClass');

            return redirect()->back()->withInput();
        }

        DB::beginTransaction();
        try {
            $unitType = new UnitType();
            $unitType->unit_type_name = $request->unit_type_name;
            $unitType->status = $request->status;
            $unitType->created = now();
            $unitType->modified = now();
            $unitType->save();

            DB::commit();
            Session::put('message', 'Unit Types added successfully.');
            Session::put('messageClass', 'successClass');

            return redirect()->route('admin.unit-types.index');
        } catch (Exception $e) {
            DB::rollBack();
            Session::put('message', 'Failed to save Unit Types. Error: '.$e->getMessage());
            Session::put('messageClass', 'errorClass');

            return back()->withInput();
        }
    }

    public function edit($id)
    {
        $id = dec($id);
        $unitType = UnitType::where('unit_type_id', $id)->firstOrFail();
        if ($unitType->status === 'Deleted') {
            abort(404);
        }

        return view('admin.unit_types.edit', compact('unitType'));
    }

    public function update(Request $request, $id)
    {
        $id = dec($id);
        $unitType = UnitType::where('unit_type_id', $id)->firstOrFail();
        if ($unitType->status === 'Deleted') {
            abort(404);
        }

        $validator = Validator::make($request->all(), [
            'unit_type_name' => 'required',
            'status' => 'required|in:Active,Inactive',
        ], [
            'unit_type_name.required' => 'Please enter Unit Type Name.',
            'status.required' => 'Please select Status.',
        ]);

        if ($validator->fails()) {
            Session::put('message', $validator->errors()->first());
            Session::put('messageClass', 'errorClass');

            return redirect()->back()->withInput();
        }

        DB::beginTransaction();
        try {
            $unitType->unit_type_name = $request->unit_type_name;
            $unitType->status = $request->status;
            $unitType->modified = now();
            $unitType->save();

            DB::commit();
            Session::put('message', 'Unit Types updated successfully.');
            Session::put('messageClass', 'successClass');

            return redirect()->route('admin.unit-types.index');
        } catch (Exception $e) {
            DB::rollBack();
            Session::put('message', 'Failed to update Unit Types. Error: '.$e->getMessage());
            Session::put('messageClass', 'errorClass');

            return back()->withInput();
        }
    }

    public function destroy($id)
    {
        $id = dec($id);
        $unitType = UnitType::where('unit_type_id', $id)->firstOrFail();
        if ($unitType->status === 'Deleted') {
            abort(404);
        }

        DB::beginTransaction();
        try {
            $unitType->status = 'Deleted';
            $unitType->modified = now();
            $unitType->save();

            DB::commit();
            Session::put('message', 'Unit Types deleted successfully.');
            Session::put('messageClass', 'successClass');

            return response()->json(['success' => true]);
        } catch (Exception $e) {
            DB::rollBack();
            Session::put('message', 'Failed to delete Unit Types. Error: '.$e->getMessage());
            Session::put('messageClass', 'errorClass');

            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}

