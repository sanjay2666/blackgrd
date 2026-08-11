<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RecordStatus;
use App\Http\Controllers\Controller;
use App\Models\GstRate;
use App\Models\HsnCode;
use App\Models\Item;
use App\Models\ItemType;
use App\Models\ItemYarnRequirement;
use App\Models\UnitType;
use App\Services\ItemMasterService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ItemController extends Controller
{
    public function __construct(private readonly ItemMasterService $items)
    {
        // The Item master service owns mutation policy and auditing.
    }

    public function index(Request $request)
    {
        $query = Item::with('itemType', 'unitType', 'hsnCode', 'gstRate');
        $query->notDeleted();

        $qsearch = trim($request->input('qsearch', ''));
        if ($qsearch == '' && $request->filled('search')) {
            $qsearch = trim($request->input('search', ''));
        }

        $item_type_id = $request->input('item_type_id', '');
        $status = $request->input('status', '');
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

        if (in_array($status, [RecordStatus::Active->value, RecordStatus::Inactive->value], true)) {
            $query->where('status', $status);
        }

        if ($itemId != '') {
            $query->where('item_id', $itemId);
        }

        $items = $query->orderBy('item_id', 'desc')->paginate(config('app.pagination_limit'))->withQueryString();
        $itemTypes = ItemType::active()->orderBy('item_type_id', 'asc')->get();

        return view('admin.items.index', compact('items', 'itemTypes', 'qsearch', 'item_type_id', 'status', 'itemId'));
    }

    public function create()
    {
        $itemTypes = ItemType::active()->orderBy('item_type_id', 'asc')->get();
        $unitTypes = UnitType::active()->orderBy('unit_type_id', 'asc')->get();

        $hsnCodes = HsnCode::active()->orderBy('hsn_code')->get();
        $gstRates = GstRate::active()->orderBy('gst_rate')->get();

        return view('admin.items.create', compact('itemTypes', 'unitTypes', 'hsnCodes', 'gstRates'))->with('statusOptions', RecordStatus::formOptions());
    }

    public function store(Request $request)
    {
        $this->validateRequest($request);
        $this->ensureCodeUnique($request);
        try {
            $this->items->create($request->only($this->fields()), $request);

            return redirect()->route('admin.items.index')->with('message', 'Item created successfully.')->with('messageClass', 'successClass');
        } catch (Exception $e) {
            return back()->withInput()->withErrors(['item' => $e->getMessage()]);
        }
    }

    public function edit($id)
    {
        $id = dec($id);
        $item = Item::where('item_id', $id)->firstOrFail();
        if ($item->status === 'Deleted') {
            abort(404);
        }

        $itemTypes = ItemType::active()->orderBy('item_type_id', 'asc')->get();
        $unitTypes = UnitType::query()->whereIn('unit_type_id', [$item->unit_type_id])->orWhere(fn ($q) => $q->active())->orderBy('unit_type_id')->get();
        $itemTypes = ItemType::query()->whereIn('item_type_id', [$item->item_type_id])->orWhere(fn ($q) => $q->active())->orderBy('item_type_id')->get();
        $hsnCodes = HsnCode::query()->whereIn('hsn_code_id', [$item->hsn_code_id])->orWhere(fn ($q) => $q->active())->orderBy('hsn_code')->get();
        $gstRates = GstRate::query()->whereIn('gst_rate_id', [$item->gst_rate_id])->orWhere(fn ($q) => $q->active())->orderBy('gst_rate')->get();

        return view('admin.items.edit', compact('item', 'itemTypes', 'unitTypes', 'hsnCodes', 'gstRates'))->with('statusOptions', RecordStatus::formOptions());
    }

    public function update(Request $request, $id)
    {
        $id = dec($id);
        $item = Item::where('item_id', $id)->firstOrFail();
        if ($item->status === 'Deleted') {
            abort(404);
        }

        try {
            $this->validateRequest($request);
            $this->ensureCodeUnique($request, $item);
            $this->items->update($item, $request->only($this->fields()), $request);

            return redirect()->route('admin.items.index');
        } catch (Exception $e) {
            return back()->withInput()->withErrors(['item' => $e->getMessage()]);
        }
    }

    public function destroy($id)
    {
        $id = dec($id);
        $item = Item::where('item_id', $id)->firstOrFail();
        if ($item->status === 'Deleted') {
            abort(404);
        }

        try {
            $status = $this->items->deleteOrDeactivate($item, request());

            return response()->json(['success' => true, 'status' => $status]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    private function fields(): array
    {
        return ['item_name', 'item_code', 'internal_item_name', 'unit_price', 'hsncode', 'hsn_code_id', 'gst_rate_id', 'item_type_id', 'unit_type_id', 'clr_category', 'cut', 'pur_rate', 'sale_rate', 'igst', 'sgst', 'cgst', 'sale_igst', 'sale_cgst', 'sale_sgst', 'item_gsm', 'item_final_gsm', 'item_width', 'item_final_width', 'remarks', 'is_conusmable', 'is_outsourced', 'is_jobwork', 'is_lab_test_required', 'status'];
    }

    private function validateRequest(Request $request): void
    {
        Validator::make($request->all(), [
            'item_name' => ['required', 'string', 'max:255'], 'item_code' => ['nullable', 'string', 'max:100', 'regex:/^[A-Za-z0-9._-]+$/'],
            'item_type_id' => ['required', 'integer'], 'unit_type_id' => ['required', 'integer'], 'hsn_code_id' => ['nullable', 'integer'], 'gst_rate_id' => ['nullable', 'integer'],
            'is_lab_test_required' => ['required', 'in:Yes,No'], 'status' => ['required', 'in:Active,Inactive'],
        ])->validate();
    }

    private function ensureCodeUnique(Request $request, ?Item $item = null): void
    {
        $code = strtoupper(trim((string) $request->input('item_code')));
        if ($code !== '' && Item::query()->where('item_code', $code)->notDeleted()->when($item, fn ($q) => $q->where('item_id', '!=', $item->getKey()))->exists()) {
            throw ValidationException::withMessages(['item_code' => 'This Item Code already exists.']);
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

        $yarnTypeIds = ItemType::active()
            ->where('item_type_name', 'like', '%Yarn%')
            ->pluck('item_type_id');

        $yarnItems = Item::active();

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

                $itemYarnRequirement = new ItemYarnRequirement;
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
