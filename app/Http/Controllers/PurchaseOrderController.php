<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\ItemType;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\UnitType;
use App\Services\DocumentSettingsService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;

class PurchaseOrderController extends Controller
{
    public function index(Request $request)
    {
        $vendorName = trim($request->input('vendorName', ''));
        $fromDate = trim($request->input('from_date', ''));
        $toDate = trim($request->input('to_date', ''));

        $query = PurchaseOrder::with(['vendor', 'PurchaseOrderItem.ItemType'])
            ->where('status', '!=', 'Deleted')
            ->where('is_deleted', 'No');

        if ($vendorName !== '') {
            $query->whereHas('vendor', function ($vendorQuery) use ($vendorName) {
                $vendorQuery->where('name', 'like', '%'.$vendorName.'%')
                    ->orWhere('company_name', 'like', '%'.$vendorName.'%')
                    ->orWhere('phone', 'like', '%'.$vendorName.'%');
            });
        }

        if ($fromDate !== '') {
            $fromDateSearch = $fromDate;
            if (strpos($fromDateSearch, '-') !== false) {
                $fromDateParts = explode('-', $fromDateSearch);
                if (count($fromDateParts) == 3 && strlen($fromDateParts[2]) == 4) {
                    $fromDateSearch = $fromDateParts[2].'-'.$fromDateParts[1].'-'.$fromDateParts[0];
                }
            }
            $query->whereDate('purchased_on', '>=', $fromDateSearch);
        }

        if ($toDate !== '') {
            $toDateSearch = $toDate;
            if (strpos($toDateSearch, '-') !== false) {
                $toDateParts = explode('-', $toDateSearch);
                if (count($toDateParts) == 3 && strlen($toDateParts[2]) == 4) {
                    $toDateSearch = $toDateParts[2].'-'.$toDateParts[1].'-'.$toDateParts[0];
                }
            }
            $query->whereDate('purchased_on', '<=', $toDateSearch);
        }

        $dataP = $query->orderBy('id', 'desc')
            ->paginate(config('app.pagination_limit', 15))
            ->withQueryString();

        return view('frontend.purchaseorder.index', compact('dataP', 'vendorName', 'fromDate', 'toDate'));
    }

    public function create()
    {
        $dataIT = ItemType::where('status', '=', 'Active')->where('is_purchase', '=', '1')->get();
        $dataUT = UnitType::where('status', 'Active')->orderBy('unit_type_id')->get();
        $totalPO = (PurchaseOrder::max('id') ?? 0) + 1;

        return view('frontend.purchaseorder.add', compact('dataIT', 'dataUT', 'totalPO'));
    }

    public function edit($id)
    {
        $purchaseOrder = PurchaseOrder::with(['vendor', 'PurchaseOrderItem.ItemType'])
            ->where('status', '!=', 'Deleted')
            ->where('is_deleted', 'No')
            ->findOrFail(dec($id));

        $dataIT = ItemType::where('status', '=', 'Active')->where('is_purchase', '=', '1')->get();
        $dataUT = UnitType::where('status', 'Active')->orderBy('unit_type_id')->get();
        $totalPO = $purchaseOrder->id;

        return view('frontend.purchaseorder.add', compact('dataIT', 'dataUT', 'totalPO', 'purchaseOrder'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'vendor_id' => 'required|integer',
            'billing_id' => 'nullable|integer',
            'shiping_id' => 'nullable|integer',
            'billing_address' => 'nullable|string',
            'shiping_address' => 'nullable|string',
            'purchased_on' => 'required',
            'frieght' => 'nullable|numeric|min:0',
            'order_remark' => 'nullable|string',
            'count_product' => 'required|integer|min:1',
            'item_id_arr' => 'required|array|min:1',
            'item_id_arr.*' => 'required|integer',
            'item_type_id_arr.*' => 'nullable|integer',
            'quantity_arr.*' => 'required|numeric|min:0.01',
        ], [
            'vendor_id.required' => 'Please select vendor.',
            'purchased_on.required' => 'Please select purchase date.',
            'count_product.required' => 'Please add at least one item.',
            'count_product.min' => 'Please add at least one item.',
            'item_id_arr.required' => 'Please add at least one item.',
        ]);

        if ($validator->fails()) {
            Session::put('message', $validator->errors()->first());
            Session::put('messageClass', 'errorClass');
            return redirect()->back()->withInput();
        }

        try {
            DB::beginTransaction();

            $purchaseOrder = new PurchaseOrder();
            $purchaseOrder->vendor_id = $request->vendor_id;
            $purchaseOrder->billing_id = $request->billing_id;
            $purchaseOrder->shiping_id = $request->shiping_id;
            $purchaseOrder->billing_address = $request->billing_address;
            $purchaseOrder->shiping_address = $request->shiping_address;
            $purchaseOrder->frieght = $request->frieght ?? 0;
            $purchaseOrder->order_remark = $request->order_remark;
            if (strpos($request->purchased_on, '-') !== false) {
                $purchaseDateParts = explode('-', $request->purchased_on);
                if (count($purchaseDateParts) == 3 && strlen($purchaseDateParts[2]) == 4) {
                    $purchaseOrder->purchased_on = Carbon::createFromFormat('d-m-Y', $request->purchased_on);
                } else {
                    $purchaseOrder->purchased_on = Carbon::parse($request->purchased_on);
                }
            } else {
                $purchaseOrder->purchased_on = Carbon::parse($request->purchased_on);
            }
            $purchaseOrder->is_all_item_received = $request->is_all_item_received ?? 'No';
            $purchaseOrder->is_item_received_in_warehouse = $request->is_item_received_in_warehouse ?? 'No';
            $purchaseOrder->is_deleted = 'No';
            $purchaseOrder->is_return = $request->is_return ?? 'No';
            $purchaseOrder->financial_year = currentFinancialYear();
            $purchaseOrder->created_by = Auth::id() ?? 0;
            $purchaseOrder->modified_by = Auth::id() ?? 0;
            $purchaseOrder->created_at = now();
            $purchaseOrder->modified_at = now();
            $purchaseOrder->status = 'Active';
            $purchaseOrder->save();

            $total = 0;
            $cgstrsTotal = 0;
            $sgstrsTotal = 0;
            $igstrsTotal = 0;
            $cessTotal = 0;
            $cessrsTotal = 0;
            $taxrsTotal = 0;

            foreach ($request->input('item_id_arr', []) as $index => $itemId) {
                $quantity = (float) data_get($request->input('quantity_arr', []), $index, 0);
                $receivedQuantity = (float) data_get($request->input('received_quantity_arr', []), $index, 0);
                $cgstrs = (float) data_get($request->input('cgstrs_arr', []), $index, 0);
                $sgstrs = (float) data_get($request->input('sgstrs_arr', []), $index, 0);
                $igstrs = (float) data_get($request->input('igstrs_arr', []), $index, 0);
                $cess = (float) data_get($request->input('cess_arr', []), $index, 0);
                $cessrs = (float) data_get($request->input('cessrs_arr', []), $index, 0);
                $taxrs = (float) data_get($request->input('taxrs_arr', []), $index, 0);
                $totalPrice = (float) data_get($request->input('total_price_arr', []), $index, 0);

                $purchaseOrderItem = new PurchaseOrderItem();
                $purchaseOrderItem->purchase_id = $purchaseOrder->id;
                $purchaseOrderItem->item_id = $itemId;
                $purchaseOrderItem->item_type_id = data_get($request->input('item_type_id_arr', []), $index);
                $purchaseOrderItem->name = data_get($request->input('name_arr', []), $index);
                $purchaseOrderItem->colour_name = data_get($request->input('colour_name_arr', []), $index);
                $purchaseOrderItem->meter = data_get($request->input('meter_arr', []), $index);
                $purchaseOrderItem->quantity = $quantity;
                $purchaseOrderItem->received_quantity = $receivedQuantity;
                $purchaseOrderItem->balance_quantity = max($quantity - $receivedQuantity, 0);
                $purchaseOrderItem->mrp = data_get($request->input('mrp_arr', []), $index, 0);
                $purchaseOrderItem->cgst = data_get($request->input('cgst_arr', []), $index, 0);
                $purchaseOrderItem->sgst = data_get($request->input('sgst_arr', []), $index, 0);
                $purchaseOrderItem->igst = data_get($request->input('igst_arr', []), $index, 0);
                $purchaseOrderItem->cgstrs = $cgstrs;
                $purchaseOrderItem->sgstrs = $sgstrs;
                $purchaseOrderItem->igstrs = $igstrs;
                $purchaseOrderItem->saleprice_wot = data_get($request->input('saleprice_wot_arr', []), $index, 0);
                $purchaseOrderItem->saleprice = data_get($request->input('saleprice_arr', []), $index, 0);
                $purchaseOrderItem->total_price = $totalPrice;
                $purchaseOrderItem->cess = $cess;
                $purchaseOrderItem->cessrs = $cessrs;
                $purchaseOrderItem->taxrs = $taxrs;
                $purchaseOrderItem->hsn = data_get($request->input('hsn_arr', []), $index);
                $purchaseOrderItem->unit = data_get($request->input('unit_arr', []), $index);
                $purchaseOrderItem->is_item_received_in_warehouse = data_get($request->input('is_item_received_in_warehouse_arr', []), $index, '0');
                $purchaseOrderItem->is_deleted = false;
                $purchaseOrderItem->is_return = false;
                $purchaseOrderItem->financial_year = currentFinancialYear();
                $purchaseOrderItem->created_by = Auth::id() ?? 0;
                $purchaseOrderItem->modified_by = Auth::id() ?? 0;
                $purchaseOrderItem->created_at = now();
                $purchaseOrderItem->modified_at = now();
                $purchaseOrderItem->status = 'Active';
                $purchaseOrderItem->save();

                $total += $totalPrice;
                $cgstrsTotal += $cgstrs;
                $sgstrsTotal += $sgstrs;
                $igstrsTotal += $igstrs;
                $cessTotal += $cess;
                $cessrsTotal += $cessrs;
                $taxrsTotal += $taxrs;
            }

            $purchaseOrder->total = $total;
            $purchaseOrder->subtotal = $total + (float) $purchaseOrder->frieght;
            $purchaseOrder->cgstrs = $cgstrsTotal;
            $purchaseOrder->sgstrs = $sgstrsTotal;
            $purchaseOrder->igstrs = $igstrsTotal;
            $purchaseOrder->cess = $cessTotal;
            $purchaseOrder->cessrs = $cessrsTotal;
            $purchaseOrder->taxrs = $taxrsTotal;
            $purchaseOrder->save();

            DB::commit();
            Session::put('message', 'Purchase order added successfully.');
			Session::put('messageClass', 'successClass');
            return redirect()->route('show-purchaseorders');
        } catch (\Exception $e) {
            DB::rollBack();
            Session::put('message', 'Failed to add purchase order. Error: '.$e->getMessage());
            Session::put('messageClass', 'errorClass');
            return redirect()->back()->withInput();
        }
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'vendor_id' => 'required|integer',
            'billing_id' => 'nullable|integer',
            'shiping_id' => 'nullable|integer',
            'billing_address' => 'nullable|string',
            'shiping_address' => 'nullable|string',
            'purchased_on' => 'required',
            'frieght' => 'nullable|numeric|min:0',
            'order_remark' => 'nullable|string',
            'count_product' => 'required|integer|min:1',
            'item_id_arr' => 'required|array|min:1',
            'item_id_arr.*' => 'required|integer',
            'item_type_id_arr.*' => 'nullable|integer',
            'quantity_arr.*' => 'required|numeric|min:0.01',
        ], [
            'vendor_id.required' => 'Please select vendor.',
            'purchased_on.required' => 'Please select purchase date.',
            'count_product.required' => 'Please add at least one item.',
            'count_product.min' => 'Please add at least one item.',
            'item_id_arr.required' => 'Please add at least one item.',
        ]);

        if ($validator->fails()) {
            Session::put('message', $validator->errors()->first());
            Session::put('messageClass', 'errorClass');
            return redirect()->back()->withInput();
        }

        try {
            DB::beginTransaction();

            $purchaseOrder = PurchaseOrder::where('status', '!=', 'Deleted')
                ->where('is_deleted', 'No')
                ->findOrFail(dec($id));

            $purchaseOrder->vendor_id = $request->vendor_id;
            $purchaseOrder->billing_id = $request->billing_id;
            $purchaseOrder->shiping_id = $request->shiping_id;
            $purchaseOrder->billing_address = $request->billing_address;
            $purchaseOrder->shiping_address = $request->shiping_address;
            $purchaseOrder->frieght = $request->frieght ?? 0;
            $purchaseOrder->order_remark = $request->order_remark;
            if (strpos($request->purchased_on, '-') !== false) {
                $purchaseDateParts = explode('-', $request->purchased_on);
                if (count($purchaseDateParts) == 3 && strlen($purchaseDateParts[2]) == 4) {
                    $purchaseOrder->purchased_on = Carbon::createFromFormat('d-m-Y', $request->purchased_on);
                } else {
                    $purchaseOrder->purchased_on = Carbon::parse($request->purchased_on);
                }
            } else {
                $purchaseOrder->purchased_on = Carbon::parse($request->purchased_on);
            }
            $purchaseOrder->modified_by = Auth::id() ?? 0;
            $purchaseOrder->modified_at = now();
            $purchaseOrder->save();

            PurchaseOrderItem::where('purchase_id', $purchaseOrder->id)->update([
                'is_deleted' => true,
                'status' => 'Deleted',
                'modified_by' => Auth::id() ?? 0,
                'modified_at' => now(),
            ]);

            $total = 0;
            $cgstrsTotal = 0;
            $sgstrsTotal = 0;
            $igstrsTotal = 0;
            $cessTotal = 0;
            $cessrsTotal = 0;
            $taxrsTotal = 0;

            foreach ($request->input('item_id_arr', []) as $index => $itemId) {
                $quantity = (float) data_get($request->input('quantity_arr', []), $index, 0);
                $receivedQuantity = (float) data_get($request->input('received_quantity_arr', []), $index, 0);
                $cgstrs = (float) data_get($request->input('cgstrs_arr', []), $index, 0);
                $sgstrs = (float) data_get($request->input('sgstrs_arr', []), $index, 0);
                $igstrs = (float) data_get($request->input('igstrs_arr', []), $index, 0);
                $cess = (float) data_get($request->input('cess_arr', []), $index, 0);
                $cessrs = (float) data_get($request->input('cessrs_arr', []), $index, 0);
                $taxrs = (float) data_get($request->input('taxrs_arr', []), $index, 0);
                $totalPrice = (float) data_get($request->input('total_price_arr', []), $index, 0);

                $purchaseOrderItem = new PurchaseOrderItem();
                $purchaseOrderItem->purchase_id = $purchaseOrder->id;
                $purchaseOrderItem->item_id = $itemId;
                $purchaseOrderItem->item_type_id = data_get($request->input('item_type_id_arr', []), $index);
                $purchaseOrderItem->name = data_get($request->input('name_arr', []), $index);
                $purchaseOrderItem->colour_name = data_get($request->input('colour_name_arr', []), $index);
                $purchaseOrderItem->meter = data_get($request->input('meter_arr', []), $index);
                $purchaseOrderItem->quantity = $quantity;
                $purchaseOrderItem->received_quantity = $receivedQuantity;
                $purchaseOrderItem->balance_quantity = max($quantity - $receivedQuantity, 0);
                $purchaseOrderItem->mrp = data_get($request->input('mrp_arr', []), $index, 0);
                $purchaseOrderItem->cgst = data_get($request->input('cgst_arr', []), $index, 0);
                $purchaseOrderItem->sgst = data_get($request->input('sgst_arr', []), $index, 0);
                $purchaseOrderItem->igst = data_get($request->input('igst_arr', []), $index, 0);
                $purchaseOrderItem->cgstrs = $cgstrs;
                $purchaseOrderItem->sgstrs = $sgstrs;
                $purchaseOrderItem->igstrs = $igstrs;
                $purchaseOrderItem->saleprice_wot = data_get($request->input('saleprice_wot_arr', []), $index, 0);
                $purchaseOrderItem->saleprice = data_get($request->input('saleprice_arr', []), $index, 0);
                $purchaseOrderItem->total_price = $totalPrice;
                $purchaseOrderItem->cess = $cess;
                $purchaseOrderItem->cessrs = $cessrs;
                $purchaseOrderItem->taxrs = $taxrs;
                $purchaseOrderItem->hsn = data_get($request->input('hsn_arr', []), $index);
                $purchaseOrderItem->unit = data_get($request->input('unit_arr', []), $index);
                $purchaseOrderItem->is_item_received_in_warehouse = data_get($request->input('is_item_received_in_warehouse_arr', []), $index, '0');
                $purchaseOrderItem->is_deleted = false;
                $purchaseOrderItem->is_return = false;
                $purchaseOrderItem->financial_year = currentFinancialYear();
                $purchaseOrderItem->created_by = Auth::id() ?? 0;
                $purchaseOrderItem->modified_by = Auth::id() ?? 0;
                $purchaseOrderItem->created_at = now();
                $purchaseOrderItem->modified_at = now();
                $purchaseOrderItem->status = 'Active';
                $purchaseOrderItem->save();

                $total += $totalPrice;
                $cgstrsTotal += $cgstrs;
                $sgstrsTotal += $sgstrs;
                $igstrsTotal += $igstrs;
                $cessTotal += $cess;
                $cessrsTotal += $cessrs;
                $taxrsTotal += $taxrs;
            }

            $purchaseOrder->total = $total;
            $purchaseOrder->subtotal = $total + (float) $purchaseOrder->frieght;
            $purchaseOrder->cgstrs = $cgstrsTotal;
            $purchaseOrder->sgstrs = $sgstrsTotal;
            $purchaseOrder->igstrs = $igstrsTotal;
            $purchaseOrder->cess = $cessTotal;
            $purchaseOrder->cessrs = $cessrsTotal;
            $purchaseOrder->taxrs = $taxrsTotal;
            $purchaseOrder->save();

            DB::commit();
            Session::put('message', 'Purchase order updated successfully.');
            Session::put('messageClass', 'successClass');
            return redirect()->route('show-purchaseorders');
        } catch (\Exception $e) {
            DB::rollBack();
            Session::put('message', 'Failed to update purchase order. Error: '.$e->getMessage());
            Session::put('messageClass', 'errorClass');
            return redirect()->back()->withInput();
        }
    }

    public function delete(Request $request)
    {
        if (empty($request->FId)) {
            return response()->json(['status' => 0, 'message' => 'Invalid purchase order reference.'], 422);
        }

        $purchaseId = dec($request->FId);
        $purchaseOrder = PurchaseOrder::where('status', '!=', 'Deleted')->findOrFail($purchaseId);
        $purchaseOrder->is_deleted = 'Yes';
        $purchaseOrder->status = 'Deleted';
        $purchaseOrder->deleted_by = Auth::id() ?? 0;
        $purchaseOrder->modified_by = Auth::id() ?? 0;
        $purchaseOrder->modified_at = now();
        $purchaseOrder->save();

        PurchaseOrderItem::where('purchase_id', $purchaseOrder->id)->update([
            'is_deleted' => true,
            'status' => 'Deleted',
            'modified_by' => Auth::id() ?? 0,
            'modified_at' => now(),
        ]);

        return response()->json(['success' => true]);
    }

    public function printPurchaseOrder($id, DocumentSettingsService $documentSettings)
    {
        $purchaseId = dec($id);
        $dataPur = PurchaseOrder::with(['vendor', 'billingAddress', 'shippingAddress'])
            ->where('status', '!=', 'Deleted')
            ->where('is_deleted', 'No')
            ->findOrFail($purchaseId);

        $dataPI = PurchaseOrderItem::with(['Item', 'ItemType'])
            ->where('purchase_id', $dataPur->id)
            ->where('status', '!=', 'Deleted')
            ->where('is_deleted', false)
            ->get();

        $dataCom = Company::where('status', 'Active')->orderBy('id', 'asc')->first();

        return view('frontend.purchaseorder.print', ['dataPur' => $dataPur, 'dataPI' => $dataPI, 'dataCom' => $dataCom, 'documentSettings' => $documentSettings->for('purchase_order')]);
    }

}
