<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\ItemType;
use App\Models\ItemYarnRequirement;
use App\Models\UnitType;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;

class ItemController extends Controller
{
    public function index(Request $request)
    {
        $query = Item::with('itemType', 'unitType');
        $query->where('status', '!=', 'Deleted');

        $qsearch = trim($request->input('qsearch', ''));
        if ($qsearch == '' && $request->filled('search')) {
            $qsearch = trim($request->input('search', ''));
        }

        $item_type_id = $request->input('item_type_id', '');
        $itemId = $request->input('itemId', '');

        if ($qsearch != '') {
            $query->where(function ($query) use ($qsearch) {
                $query->where('item_name', 'like', '%'.$qsearch.'%');
                $query->orWhere('item_code', 'like', '%'.$qsearch.'%');
                $query->orWhere('internal_item_name', 'like', '%'.$qsearch.'%');
                $query->orWhere('hsncode', 'like', '%'.$qsearch.'%');
            });
        }

        if ($item_type_id != '') {
            $query->where('item_type_id', $item_type_id);
        }

        if ($itemId != '') {
            $query->where('item_id', $itemId);
        }

        $items = $query->orderBy('item_id', 'desc')->paginate(config('app.pagination_limit'))->withQueryString();
        $itemTypes = ItemType::where('status', 'Active')->orderBy('item_type_id', 'asc')->get();

        return view('admin.items.index', compact('items', 'itemTypes', 'qsearch', 'item_type_id', 'itemId'));
    }

    public function create()
    {
        $itemTypes = ItemType::where('status', 'Active')->orderBy('item_type_id', 'asc')->get();
        $unitTypes = UnitType::where('status', 'Active')->orderBy('unit_type_id', 'asc')->get();

        return view('admin.items.create', compact('itemTypes', 'unitTypes'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'item_name' => 'nullable',
            'item_code' => 'nullable',
            'internal_item_name' => 'nullable',
            'unit_price' => 'nullable',
            'hsncode' => 'nullable',
            'item_type_id' => 'required',
            'unit_type_id' => 'required',
            'clr_category' => 'nullable',
            'cut' => 'nullable',
            'pur_rate' => 'nullable',
            'sale_rate' => 'nullable',
            'igst' => 'nullable',
            'sgst' => 'nullable',
            'cgst' => 'nullable',
            'sale_igst' => 'nullable',
            'sale_cgst' => 'nullable',
            'sale_sgst' => 'nullable',
            'item_gsm' => 'nullable',
            'item_final_gsm' => 'nullable',
            'item_width' => 'nullable',
            'item_final_width' => 'nullable',
            'remarks' => 'nullable',
            'is_conusmable' => 'nullable',
            'is_outsourced' => 'nullable',
            'is_jobwork' => 'nullable',
            'is_lab_test_required' => 'required',
            'status' => 'required|in:Active,Inactive',
        ], [
            'item_type_id.required' => 'Please select Item Type.',
            'unit_type_id.required' => 'Please select Unit Type.',
            'is_lab_test_required.required' => 'Please enter Lab Test Required.',
            'status.required' => 'Please select Status.',
        ]);

        if ($validator->fails()) {
            Session::put('message', $validator->errors()->first());
            Session::put('messageClass', 'errorClass');

            return redirect()->back()->withInput();
        }

        DB::beginTransaction();
        try {
            $item = new Item();
            $item->item_name = $request->item_name;
            $item->item_code = $request->item_code;
            $item->internal_item_name = $request->internal_item_name;
            $item->unit_price = $request->unit_price;
            $item->hsncode = $request->hsncode;
            $item->item_type_id = $request->item_type_id;
            $item->unit_type_id = $request->unit_type_id;
            $item->clr_category = $request->clr_category;
            $item->cut = $request->cut;
            $item->pur_rate = $request->pur_rate;
            $item->sale_rate = $request->sale_rate;
            $item->igst = $request->igst;
            $item->sgst = $request->sgst;
            $item->cgst = $request->cgst;
            $item->sale_igst = $request->sale_igst;
            $item->sale_cgst = $request->sale_cgst;
            $item->sale_sgst = $request->sale_sgst;
            $item->item_gsm = $request->item_gsm;
            $item->item_final_gsm = $request->item_final_gsm;
            $item->item_width = $request->item_width;
            $item->item_final_width = $request->item_final_width;
            $item->remarks = $request->remarks;
            $item->is_conusmable = $request->has('is_conusmable') ? 1 : 0;
            $item->is_outsourced = $request->has('is_outsourced') ? 1 : 0;
            $item->is_jobwork = $request->has('is_jobwork') ? 1 : 0;
            $item->is_lab_test_required = $request->is_lab_test_required;
            $item->status = $request->status;
            $item->created = now();
            $item->modified = now();
            $item->created_by = Auth::guard('admin')->id();
            $item->modified_by = Auth::guard('admin')->id();
            $item->save();

            DB::commit();
            Session::put('message', 'Items added successfully.');
            Session::put('messageClass', 'successClass');

            return redirect()->route('admin.items.index');
        } catch (Exception $e) {
            DB::rollBack();
            Session::put('message', 'Failed to save Items. Error: '.$e->getMessage());
            Session::put('messageClass', 'errorClass');

            return back()->withInput();
        }
    }

    public function edit($id)
    {
        $id = dec($id);
        $item = Item::where('item_id', $id)->firstOrFail();
        if ($item->status === 'Deleted') {
            abort(404);
        }

        $itemTypes = ItemType::where('status', 'Active')->orderBy('item_type_id', 'asc')->get();
        $unitTypes = UnitType::where('status', 'Active')->orderBy('unit_type_id', 'asc')->get();

        return view('admin.items.edit', compact('item', 'itemTypes', 'unitTypes'));
    }

    public function update(Request $request, $id)
    {
        $id = dec($id);
        $item = Item::where('item_id', $id)->firstOrFail();
        if ($item->status === 'Deleted') {
            abort(404);
        }

        $validator = Validator::make($request->all(), [
            'item_name' => 'nullable',
            'item_code' => 'nullable',
            'internal_item_name' => 'nullable',
            'unit_price' => 'nullable',
            'hsncode' => 'nullable',
            'item_type_id' => 'required',
            'unit_type_id' => 'required',
            'clr_category' => 'nullable',
            'cut' => 'nullable',
            'pur_rate' => 'nullable',
            'sale_rate' => 'nullable',
            'igst' => 'nullable',
            'sgst' => 'nullable',
            'cgst' => 'nullable',
            'sale_igst' => 'nullable',
            'sale_cgst' => 'nullable',
            'sale_sgst' => 'nullable',
            'item_gsm' => 'nullable',
            'item_final_gsm' => 'nullable',
            'item_width' => 'nullable',
            'item_final_width' => 'nullable',
            'remarks' => 'nullable',
            'is_conusmable' => 'nullable',
            'is_outsourced' => 'nullable',
            'is_jobwork' => 'nullable',
            'is_lab_test_required' => 'required',
            'status' => 'required|in:Active,Inactive',
        ], [
            'item_type_id.required' => 'Please select Item Type.',
            'unit_type_id.required' => 'Please select Unit Type.',
            'is_lab_test_required.required' => 'Please enter Lab Test Required.',
            'status.required' => 'Please select Status.',
        ]);

        if ($validator->fails()) {
            Session::put('message', $validator->errors()->first());
            Session::put('messageClass', 'errorClass');

            return redirect()->back()->withInput();
        }

        DB::beginTransaction();
        try {
            $item->item_name = $request->item_name;
            $item->item_code = $request->item_code;
            $item->internal_item_name = $request->internal_item_name;
            $item->unit_price = $request->unit_price;
            $item->hsncode = $request->hsncode;
            $item->item_type_id = $request->item_type_id;
            $item->unit_type_id = $request->unit_type_id;
            $item->clr_category = $request->clr_category;
            $item->cut = $request->cut;
            $item->pur_rate = $request->pur_rate;
            $item->sale_rate = $request->sale_rate;
            $item->igst = $request->igst;
            $item->sgst = $request->sgst;
            $item->cgst = $request->cgst;
            $item->sale_igst = $request->sale_igst;
            $item->sale_cgst = $request->sale_cgst;
            $item->sale_sgst = $request->sale_sgst;
            $item->item_gsm = $request->item_gsm;
            $item->item_final_gsm = $request->item_final_gsm;
            $item->item_width = $request->item_width;
            $item->item_final_width = $request->item_final_width;
            $item->remarks = $request->remarks;
            $item->is_conusmable = $request->has('is_conusmable') ? 1 : 0;
            $item->is_outsourced = $request->has('is_outsourced') ? 1 : 0;
            $item->is_jobwork = $request->has('is_jobwork') ? 1 : 0;
            $item->is_lab_test_required = $request->is_lab_test_required;
            $item->status = $request->status;
            $item->modified = now();
            $item->modified_by = Auth::guard('admin')->id();
            $item->save();

            DB::commit();
            Session::put('message', 'Items updated successfully.');
            Session::put('messageClass', 'successClass');

            return redirect()->route('admin.items.index');
        } catch (Exception $e) {
            DB::rollBack();
            Session::put('message', 'Failed to update Items. Error: '.$e->getMessage());
            Session::put('messageClass', 'errorClass');

            return back()->withInput();
        }
    }

    public function destroy($id)
    {
        $id = dec($id);
        $item = Item::where('item_id', $id)->firstOrFail();
        if ($item->status === 'Deleted') {
            abort(404);
        }

        DB::beginTransaction();
        try {
            $item->status = 'Deleted';
            $item->modified = now();
            $item->modified_by = Auth::guard('admin')->id();
            $item->save();

            DB::commit();
            Session::put('message', 'Items deleted successfully.');
            Session::put('messageClass', 'successClass');

            return response()->json(['success' => true]);
        } catch (Exception $e) {
            DB::rollBack();
            Session::put('message', 'Failed to delete Items. Error: '.$e->getMessage());
            Session::put('messageClass', 'errorClass');

            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function manageYarn($id)
    {
        $id = dec($id);
        $item = Item::where('item_id', $id)->firstOrFail();
        if ($item->status === 'Deleted') {
            abort(404);
        }

        $requirements = ItemYarnRequirement::with('yarnItem')
            ->where('item_id', $item->item_id)
            ->where('status', '!=', 'Deleted')
            ->orderBy('id', 'desc')
            ->get();

        $yarnTypeIds = ItemType::where('status', 'Active')
            ->where('item_type_name', 'like', '%Yarn%')
            ->pluck('item_type_id');

        $yarnItems = Item::where('status', 'Active');

        if ($yarnTypeIds->count() > 0) {
            $yarnItems->whereIn('item_type_id', $yarnTypeIds);
        }

        $yarnItems = $yarnItems->orderBy('item_id', 'asc')->get();
        $processOptions = [1 => 'EPI', 2 => 'PPI'];

        return view('admin.items.manage-yarn', compact('item', 'requirements', 'yarnItems', 'processOptions'));
    }

    public function addManageYarn(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'item_id' => 'required',
            'process_id' => 'required|array',
            'yarn_id' => 'required|array',
            'reed_peak' => 'required|array',
            'yarn_quantity' => 'required|array',
        ], [
            'item_id.required' => 'Item not found.',
            'process_id.required' => 'Please select Process.',
            'yarn_id.required' => 'Please select Yarn.',
            'reed_peak.required' => 'Please enter Reed/Pick.',
            'yarn_quantity.required' => 'Please enter Quantity.',
        ]);

        if ($validator->fails()) {
            Session::put('message', $validator->errors()->first());
            Session::put('messageClass', 'errorClass');

            return redirect()->back()->withInput();
        }

        DB::beginTransaction();
        try {
            $processIds = $request->process_id;
            $yarnIds = $request->yarn_id;
            $reedPeaks = $request->reed_peak;
            $yarnQuantities = $request->yarn_quantity;

            foreach ($processIds as $key => $processId) {
                if ($processId == '' || empty($yarnIds[$key]) || empty($reedPeaks[$key]) || empty($yarnQuantities[$key])) {
                    continue;
                }

                $itemYarnRequirement = new ItemYarnRequirement();
                $itemYarnRequirement->item_id = $request->item_id;
                $itemYarnRequirement->process_id = $processId;
                $itemYarnRequirement->yarn_id = $yarnIds[$key];
                $itemYarnRequirement->reed_peak = $reedPeaks[$key];
                $itemYarnRequirement->yarn_quantity = $yarnQuantities[$key];
                $itemYarnRequirement->unit = 'Kg';
                $itemYarnRequirement->financial_year = currentFinancialYear();
                $itemYarnRequirement->status = 'Active';
                $itemYarnRequirement->created_by = Auth::id();
                $itemYarnRequirement->created_at = now();
                $itemYarnRequirement->save();
            }

            DB::commit();
            Session::put('message', 'Yarn details added successfully.');
            Session::put('messageClass', 'successClass');

            return redirect()->back();
        } catch (Exception $e) {
            DB::rollBack();
            Session::put('message', 'Failed to save yarn details. Error: '.$e->getMessage());
            Session::put('messageClass', 'errorClass');

            return redirect()->back()->withInput();
        }
    }

    public function deleteYarn($id)
    {
        $itemYarnRequirement = ItemYarnRequirement::where('id', $id)->firstOrFail();

        DB::beginTransaction();
        try {
            $itemYarnRequirement->status = 'Deleted';
            $itemYarnRequirement->modified_by = Auth::id();
            $itemYarnRequirement->modified_at = now();
            $itemYarnRequirement->save();

            DB::commit();

            return response()->json(['success' => true]);
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}


