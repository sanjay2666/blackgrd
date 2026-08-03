<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RecordStatus;
use App\Http\Controllers\Controller;
use App\Models\ItemType;
use App\Models\UnitType;
use App\Rules\RecordStatusRule;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;

class ItemTypeController extends Controller
{
    public function index(Request $request)
    {
        $query = ItemType::with('unitType');
        $query->notDeleted();

        if ($request->filled('search')) {
            $query->where(function ($query) use ($request) {
                $query->where('item_type_name', 'like', '%'.$request->search.'%');
            });
        }

        $itemTypes = $query->orderBy('item_type_id', 'desc')->paginate(config('app.pagination_limit'))->withQueryString();

        return view('admin.item_types.index', compact('itemTypes'));
    }

    public function create()
    {
        $unitTypes = UnitType::active()->orderBy('unit_type_id', 'asc')->get();

        return view('admin.item_types.create', compact('unitTypes'))->with('statusOptions', RecordStatus::formOptions());
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'item_type_name' => 'required',
            'unit_type_id' => 'nullable',
            'is_purchase' => 'required',
            'is_work' => 'required',
            'is_department' => 'required',
            'status' => ['required', new RecordStatusRule],
        ], [
            'item_type_name.required' => 'Please enter Item Type Name.',
            'is_purchase.required' => 'Please enter Is Purchase.',
            'is_work.required' => 'Please enter Is Work.',
            'is_department.required' => 'Please enter Is Department.',
            'status.required' => 'Please select Status.',
        ]);

        if ($validator->fails()) {
            Session::put('message', $validator->errors()->first());
            Session::put('messageClass', 'errorClass');

            return redirect()->back()->withInput();
        }

        DB::beginTransaction();
        try {
            $itemType = new ItemType;
            $itemType->item_type_name = $request->item_type_name;
            $itemType->unit_type_id = $request->unit_type_id;
            $itemType->is_purchase = $request->is_purchase;
            $itemType->is_work = $request->is_work;
            $itemType->is_department = $request->is_department;
            $itemType->status = $request->status;
            $itemType->created = now();
            $itemType->modified = now();
            $itemType->save();

            DB::commit();
            Session::put('message', 'Item Types added successfully.');
            Session::put('messageClass', 'successClass');

            return redirect()->route('admin.item-types.index');
        } catch (Exception $e) {
            DB::rollBack();
            Session::put('message', 'Failed to save Item Types. Error: '.$e->getMessage());
            Session::put('messageClass', 'errorClass');

            return back()->withInput();
        }
    }

    public function edit($id)
    {
        $id = dec($id);
        $itemType = ItemType::where('item_type_id', $id)->firstOrFail();
        if ($itemType->status === 'Deleted') {
            abort(404);
        }

        $unitTypes = UnitType::active()->orderBy('unit_type_id', 'asc')->get();

        return view('admin.item_types.edit', compact('itemType', 'unitTypes'))->with('statusOptions', RecordStatus::formOptions());
    }

    public function update(Request $request, $id)
    {
        $id = dec($id);
        $itemType = ItemType::where('item_type_id', $id)->firstOrFail();
        if ($itemType->status === 'Deleted') {
            abort(404);
        }

        $validator = Validator::make($request->all(), [
            'item_type_name' => 'required',
            'unit_type_id' => 'nullable',
            'is_purchase' => 'required',
            'is_work' => 'required',
            'is_department' => 'required',
            'status' => ['required', new RecordStatusRule],
        ], [
            'item_type_name.required' => 'Please enter Item Type Name.',
            'is_purchase.required' => 'Please enter Is Purchase.',
            'is_work.required' => 'Please enter Is Work.',
            'is_department.required' => 'Please enter Is Department.',
            'status.required' => 'Please select Status.',
        ]);

        if ($validator->fails()) {
            Session::put('message', $validator->errors()->first());
            Session::put('messageClass', 'errorClass');

            return redirect()->back()->withInput();
        }

        DB::beginTransaction();
        try {
            $itemType->item_type_name = $request->item_type_name;
            $itemType->unit_type_id = $request->unit_type_id;
            $itemType->is_purchase = $request->is_purchase;
            $itemType->is_work = $request->is_work;
            $itemType->is_department = $request->is_department;
            $itemType->status = $request->status;
            $itemType->modified = now();
            $itemType->save();

            DB::commit();
            Session::put('message', 'Item Types updated successfully.');
            Session::put('messageClass', 'successClass');

            return redirect()->route('admin.item-types.index');
        } catch (Exception $e) {
            DB::rollBack();
            Session::put('message', 'Failed to update Item Types. Error: '.$e->getMessage());
            Session::put('messageClass', 'errorClass');

            return back()->withInput();
        }
    }

    public function destroy($id)
    {
        $id = dec($id);
        $itemType = ItemType::where('item_type_id', $id)->firstOrFail();
        if ($itemType->status === 'Deleted') {
            abort(404);
        }

        DB::beginTransaction();
        try {
            $itemType->status = 'Deleted';
            $itemType->modified = now();
            $itemType->save();

            DB::commit();
            Session::put('message', 'Item Types deleted successfully.');
            Session::put('messageClass', 'successClass');

            return response()->json(['success' => true]);
        } catch (Exception $e) {
            DB::rollBack();
            Session::put('message', 'Failed to delete Item Types. Error: '.$e->getMessage());
            Session::put('messageClass', 'errorClass');

            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
