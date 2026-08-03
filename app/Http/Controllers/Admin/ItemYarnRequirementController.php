<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ItemYarnRequirement;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;

class ItemYarnRequirementController extends Controller
{
    public function index(Request $request)
    {
        $query = ItemYarnRequirement::query();
        $query->where('status', '!=', 'Deleted');

        if ($request->filled('search')) {
            $query->where(function ($query) use ($request) {
                $query->where('unit', 'like', '%'.$request->search.'%');
            });
        }

        $itemYarnRequirements = $query->orderBy('id', 'desc')->paginate(config('app.pagination_limit'))->withQueryString();

        return view('admin.item_yarn_requirements.index', compact('itemYarnRequirements'));
    }

    public function create()
    {
        return view('admin.item_yarn_requirements.create');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'item_id' => 'required',
            'yarn_id' => 'required',
            'reed_peak' => 'required',
            'yarn_quantity' => 'nullable',
            'unit' => 'required',
            'process_id' => 'required',
            'status' => 'required|in:Active,Inactive',
        ], [
            'item_id.required' => 'Please enter Item Id.',
            'yarn_id.required' => 'Please enter Yarn Id.',
            'reed_peak.required' => 'Please enter Reed Peak.',
            'unit.required' => 'Please enter Unit.',
            'process_id.required' => 'Please enter Process Id.',
            'status.required' => 'Please select Status.',
        ]);

        if ($validator->fails()) {
            Session::put('message', $validator->errors()->first());
            Session::put('messageClass', 'errorClass');

            return redirect()->back()->withInput();
        }

        DB::beginTransaction();
        try {
            $itemYarnRequirement = new ItemYarnRequirement();
            $itemYarnRequirement->item_id = $request->item_id;
            $itemYarnRequirement->yarn_id = $request->yarn_id;
            $itemYarnRequirement->reed_peak = $request->reed_peak;
            $itemYarnRequirement->yarn_quantity = $request->yarn_quantity;
            $itemYarnRequirement->unit = $request->unit;
            $itemYarnRequirement->process_id = $request->process_id;
            $itemYarnRequirement->financial_year = currentFinancialYear();
            $itemYarnRequirement->status = $request->status;
            $itemYarnRequirement->created_by = Auth::id();
            $itemYarnRequirement->created_at = now();
            $itemYarnRequirement->save();

            DB::commit();
            Session::put('message', 'Item Yarn Requirements added successfully.');
            Session::put('messageClass', 'successClass');

            return redirect()->route('admin.item-yarn-requirements.index');
        } catch (Exception $e) {
            DB::rollBack();
            Session::put('message', 'Failed to save Item Yarn Requirements. Error: '.$e->getMessage());
            Session::put('messageClass', 'errorClass');

            return back()->withInput();
        }
    }

    public function edit($id)
    {
        $id = dec($id);
        $itemYarnRequirement = ItemYarnRequirement::where('id', $id)->firstOrFail();
        if ($itemYarnRequirement->status === 'Deleted') {
            abort(404);
        }

        return view('admin.item_yarn_requirements.edit', compact('itemYarnRequirement'));
    }

    public function update(Request $request, $id)
    {
        $id = dec($id);
        $itemYarnRequirement = ItemYarnRequirement::where('id', $id)->firstOrFail();
        if ($itemYarnRequirement->status === 'Deleted') {
            abort(404);
        }

        $validator = Validator::make($request->all(), [
            'item_id' => 'required',
            'yarn_id' => 'required',
            'reed_peak' => 'required',
            'yarn_quantity' => 'nullable',
            'unit' => 'required',
            'process_id' => 'required',
            'status' => 'required|in:Active,Inactive',
        ], [
            'item_id.required' => 'Please enter Item Id.',
            'yarn_id.required' => 'Please enter Yarn Id.',
            'reed_peak.required' => 'Please enter Reed Peak.',
            'unit.required' => 'Please enter Unit.',
            'process_id.required' => 'Please enter Process Id.',
            'status.required' => 'Please select Status.',
        ]);

        if ($validator->fails()) {
            Session::put('message', $validator->errors()->first());
            Session::put('messageClass', 'errorClass');

            return redirect()->back()->withInput();
        }

        DB::beginTransaction();
        try {
            $itemYarnRequirement->item_id = $request->item_id;
            $itemYarnRequirement->yarn_id = $request->yarn_id;
            $itemYarnRequirement->reed_peak = $request->reed_peak;
            $itemYarnRequirement->yarn_quantity = $request->yarn_quantity;
            $itemYarnRequirement->unit = $request->unit;
            $itemYarnRequirement->process_id = $request->process_id;
            $itemYarnRequirement->status = $request->status;
            $itemYarnRequirement->modified_by = Auth::id();
            $itemYarnRequirement->modified_at = now();
            $itemYarnRequirement->save();

            DB::commit();
            Session::put('message', 'Item Yarn Requirements updated successfully.');
            Session::put('messageClass', 'successClass');

            return redirect()->route('admin.item-yarn-requirements.index');
        } catch (Exception $e) {
            DB::rollBack();
            Session::put('message', 'Failed to update Item Yarn Requirements. Error: '.$e->getMessage());
            Session::put('messageClass', 'errorClass');

            return back()->withInput();
        }
    }

    public function destroy($id)
    {
        $id = dec($id);
        $itemYarnRequirement = ItemYarnRequirement::where('id', $id)->firstOrFail();
        if ($itemYarnRequirement->status === 'Deleted') {
            abort(404);
        }

        DB::beginTransaction();
        try {
            $itemYarnRequirement->status = 'Deleted';
            $itemYarnRequirement->modified_by = Auth::id();
            $itemYarnRequirement->modified_at = now();
            $itemYarnRequirement->save();

            DB::commit();
            Session::put('message', 'Item Yarn Requirements deleted successfully.');
            Session::put('messageClass', 'successClass');

            return response()->json(['success' => true]);
        } catch (Exception $e) {
            DB::rollBack();
            Session::put('message', 'Failed to delete Item Yarn Requirements. Error: '.$e->getMessage());
            Session::put('messageClass', 'errorClass');

            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}

