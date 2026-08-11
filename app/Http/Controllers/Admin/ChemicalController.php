<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RecordStatus;
use App\Http\Controllers\Controller;
use App\Models\GstRate;
use App\Models\HsnCode;
use App\Models\Item;
use App\Models\UnitType;
use App\Services\ChemicalMasterService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

final class ChemicalController extends Controller
{
    public function __construct(private readonly ChemicalMasterService $chemicals)
    {
    }

    public function index(Request $request)
    {
        $query = Item::query()->notDeleted()->where('item_type_id', $this->chemicals->chemicalItemType()->getKey())->with(['unitType', 'hsnCode', 'gstRate']);
        $search = trim((string) $request->input('search', ''));
        if ($search !== '') {
            $query->where(fn ($q) => $q->where('item_name', 'like', '%'.$search.'%')->orWhere('item_code', 'like', '%'.$search.'%'));
        }
        $status = $request->input('status', '');
        if (in_array($status, [RecordStatus::Active->value, RecordStatus::Inactive->value], true)) {
            $query->where('status', $status);
        }

        $chemicals = $query->orderBy('item_name')->paginate(config('app.pagination_limit'))->withQueryString();

        return view('admin.chemicals.index', compact('chemicals', 'search', 'status'));
    }

    public function create()
    {
        return view('admin.chemicals.form', ['chemical' => null, 'itemTypeId' => $this->chemicals->chemicalItemType()->getKey(), 'unitTypes' => UnitType::active()->orderBy('unit_type_name')->get(), 'hsnCodes' => HsnCode::active()->orderBy('hsn_code')->get(), 'gstRates' => GstRate::active()->orderBy('gst_rate')->get(), 'statusOptions' => RecordStatus::formOptions()]);
    }

    public function store(Request $request)
    {
        $this->validateRequest($request);
        try {
            $this->chemicals->create($request->only($this->fields()), $request);

            return redirect()->route('admin.chemicals.index')->with('message', 'Chemical created successfully.')->with('messageClass', 'successClass');
        } catch (Exception $e) {
            return back()->withInput()->withErrors(['chemical' => $e->getMessage()]);
        }
    }

    public function edit($id)
    {
        $chemical = Item::query()->whereKey(dec($id))->where('item_type_id', $this->chemicals->chemicalItemType()->getKey())->notDeleted()->firstOrFail();

        return view('admin.chemicals.form', ['chemical' => $chemical, 'itemTypeId' => $chemical->item_type_id, 'unitTypes' => UnitType::query()->whereKey($chemical->unit_type_id)->orWhere(fn ($q) => $q->active())->orderBy('unit_type_name')->get(), 'hsnCodes' => HsnCode::query()->whereKey($chemical->hsn_code_id)->orWhere(fn ($q) => $q->active())->orderBy('hsn_code')->get(), 'gstRates' => GstRate::query()->whereKey($chemical->gst_rate_id)->orWhere(fn ($q) => $q->active())->orderBy('gst_rate')->get(), 'statusOptions' => RecordStatus::formOptions()]);
    }

    public function update(Request $request, $id)
    {
        $chemical = Item::query()->whereKey(dec($id))->where('item_type_id', $this->chemicals->chemicalItemType()->getKey())->notDeleted()->firstOrFail();
        $this->validateRequest($request);
        try {
            $this->chemicals->update($chemical, $request->only($this->fields()), $request);

            return redirect()->route('admin.chemicals.index')->with('message', 'Chemical updated successfully.')->with('messageClass', 'successClass');
        } catch (Exception $e) {
            return back()->withInput()->withErrors(['chemical' => $e->getMessage()]);
        }
    }

    public function destroy($id)
    {
        $chemical = Item::query()->whereKey(dec($id))->where('item_type_id', $this->chemicals->chemicalItemType()->getKey())->notDeleted()->firstOrFail();

        return response()->json(['success' => true, 'status' => $this->chemicals->deleteOrDeactivate($chemical, request())]);
    }

    public function activate($id)
    {
        return $this->setStatus($id, RecordStatus::Active);
    }

    public function deactivate($id)
    {
        return $this->setStatus($id, RecordStatus::Inactive);
    }

    public function options(Request $request)
    {
        $search = trim((string) $request->input('q', ''));
        $items = $this->chemicals->activeChemicals()->filter(fn (Item $item): bool => $search === '' || str_contains(strtolower($item->item_name.' '.$item->item_code), strtolower($search)))->take(50)->map(fn (Item $item): array => ['id' => $item->getKey(), 'text' => $item->item_name, 'code' => $item->item_code, 'unit' => $item->unitType?->unit_type_name])->values();

        return response()->json(['results' => $items]);
    }

    private function setStatus($id, RecordStatus $status)
    {
        $chemical = Item::query()->whereKey(dec($id))->where('item_type_id', $this->chemicals->chemicalItemType()->getKey())->notDeleted()->firstOrFail();
        $request = request();
        $this->chemicals->update($chemical, array_merge($chemical->only($this->fields()), ['status' => $status->value]), $request);

        return back()->with('message', 'Chemical status updated successfully.')->with('messageClass', 'successClass');
    }

    private function fields(): array
    {
        return ['item_name', 'item_code', 'unit_type_id', 'hsn_code_id', 'gst_rate_id', 'remarks', 'status', 'is_lab_test_required'];
    }

    private function validateRequest(Request $request): void
    {
        Validator::make($request->all(), ['item_name' => ['required', 'string', 'max:255'], 'item_code' => ['nullable', 'string', 'max:100', 'regex:/^[A-Za-z0-9._-]+$/'], 'item_type_id' => ['required', 'integer'], 'unit_type_id' => ['required', 'integer'], 'hsn_code_id' => ['nullable', 'integer'], 'gst_rate_id' => ['nullable', 'integer'], 'is_lab_test_required' => ['nullable', 'in:Yes,No'], 'status' => ['required', 'in:Active,Inactive']])->validate();
    }
}
