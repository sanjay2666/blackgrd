<?php

namespace App\Http\Controllers;

use App\Domain\OperationalStatus\Actions\TransitionJobWork;
use App\Enums\JobWorkStatus;
use App\Exports\StockMillDispatchItemExport;
use App\Models\GreigeReceiveStockItemFromJobWorks;
use App\Models\Item;
use App\Models\ItemType;
use App\Models\ProcessItem;
use App\Models\StockMillDispatch;
use App\Models\StockMillDispatchItem;
use App\Models\UnitType;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseBalanceItem;
use App\Models\WarehouseItem;
use App\Models\WarehouseItemStock;
use App\Models\WarehouseItemStockFile;
use App\Models\WarehouseOutItem;
use App\Models\WorkOrder;
use App\Models\WorkProcessRequirement;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\ImageManager;
use Maatwebsite\Excel\Facades\Excel;
use Session;
use Validator;

class JobMillWorkController extends Controller
{
    public function showWarehouseItemStock(Request $request)
    {
        $itemName = trim($request->itemName);
        $itemId = trim($request->itemId);
        $item_type = trim($request->item_type);
        $colorSearch = trim($request->colorSearch);
        $LotNumSearch = trim($request->LotNumSearch);
        $query = WarehouseItemStock::with([
            'WarehouseOutItem',
            'WarehouseItem',
            'ReceiverIndividual',
            'User',
            'ItemType',
            'Item',
            'Warehouse',
            'WarehouseCompartment',
            'WarehouseItem.Vendor',
            'WarehouseItem.Warehouse',
            'WarehouseItem.WarehouseCompartment',
        ])
            ->where('status', 'Active')
            ->where('insp_bal_quan_size', '>', '0')
            ->where('is_allotted_stock', '=', 'No')
            ->orderByDesc('id');

        if (empty($itemId)) {
            if (! empty($itemName)) {
                $query->whereIn('item_id', function ($subQuery) use ($itemName) {
                    $subQuery->select('item_id')
                        ->from('items')
                        ->whereRaw("CONCAT(COALESCE(item_name, '')) LIKE ?", ['%'.$itemName.'%'])
                        ->where('status', 'Active');
                });
            }
        }
        if (! empty($itemId)) {
            $query->where('item_id', $itemId);
        }

        if (! empty($colorSearch)) {
            $query->where('dyeing_color', $colorSearch);
        }
        if (! empty($LotNumSearch)) {
            $query->where('dyeing_lot_number', $LotNumSearch);
        }

        if (! empty($item_type)) {
            $itemTypeArray = explode(',', $item_type);
            $query->whereIn('item_type_id', $itemTypeArray);
        }

        $dataWI = $query->paginate(500)->appends($request->all());
        $dataIT = ItemType::where('status', '=', 'Active')->orderBy('item_type_id')->get();

        $dataSO = StockMillDispatch::where('status', 'Active')->max(DB::raw('CAST(voucher_number AS UNSIGNED)'));
        $totChDispach = $dataSO ? $dataSO + 1 : 1;

        $processI = ProcessItem::where('status', '=', 'Active')->whereIn('id', [1, 2, 3, 4])->get();

        return view('frontend.jobmillwork.show-warehouse-item-stock', compact('dataWI', 'processI', 'itemName', 'itemId', 'dataIT', 'item_type', 'colorSearch', 'LotNumSearch', 'totChDispach'));

    }

    public function storeStockForMillDispatch(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'voucher_number' => 'required',
            'itemId' => 'required',
            'chalan_number' => 'required',
            'chalan_date' => 'required|date_format:d-m-Y',
            'process_type' => 'required',
            'vendor_name' => 'required',
            'individual_id' => 'required',
            'ind_add_id' => 'required',
            'ind_add_id_ship' => 'required',
            'work_name' => 'required',
            'wisId' => 'required|array|min:1',
            'wisId.*' => 'required|distinct',
        ], [
            'voucher_number.required' => 'Voucher Number is required.',
            'itemId.required' => 'Please select properly item name.',
            'chalan_number.required' => 'Chalan Number is required.',
            'chalan_date.required' => 'Chalan Date is required.',
            'chalan_date.date_format' => 'Chalan Date format should be dd-mm-yyyy.',
            'process_type.required' => 'Process Type is required.',
            'vendor_name.required' => 'Vendor Name is required.',
            'individual_id.required' => 'Vendor ID is required.',
            'ind_add_id.required' => 'Billing Address is required.',
            'ind_add_id_ship.required' => 'Shipping Address is required.',
            'work_name.required' => 'Work Name is required.',
            'wisId.required' => 'At least one item must be selected.',
            'wisId.array' => 'wisId should be array.',
            'wisId.min' => 'At least one item is required.',
            'wisId.*.distinct' => 'Duplicate stock item selected. Please refresh and try again.',
        ]);

        if ($validator->fails()) {
            Session::put('message', $validator->messages()->first());
            Session::put('messageClass', 'errorClass');

            return redirect()->back()->withInput();
        }

        $userId = Auth::id();
        $userD = User::find($userId);

        if (empty($userD)) {
            Session::put('message', 'User not found.');
            Session::put('messageClass', 'errorClass');

            return redirect()->back()->withInput();
        }

        $individualId = $userD->individual_id;
        $allotmentRemark = $request->allotment_remark;
        $receivedQuantity = $request->tot_req_quantity;
        $workOrderId = $request->work_order_id;
        $wprId = $request->work_process_req_ids;
        $wisIds = array_values(array_unique($request->wisId));

        $lockName = 'stock_mill_dispatch_'.md5($request->voucher_number.'_'.$request->chalan_number.'_'.$workOrderId.'_'.$request->individual_id.'_'.$request->itemId.'_'.implode('_', $wisIds));
        $lockTaken = false;

        try {
            $lockResult = DB::select('SELECT GET_LOCK(?, 10) as lock_status', [$lockName]);

            if (empty($lockResult) || (int) $lockResult[0]->lock_status !== 1) {
                Session::put('message', 'Request is already processing. Please do not submit again.');
                Session::put('messageClass', 'errorClass');

                return redirect()->back()->withInput();
            }

            $lockTaken = true;

            DB::beginTransaction();

            $voucherNumber = trim((string) $request->voucher_number);
            $chalanNumber = trim((string) $request->chalan_number);

            $voucherExists = StockMillDispatch::where('voucher_number', $voucherNumber)->where('status', 'Active')->exists();

            if (! empty($voucherExists)) {
                $lastVoucherNumber = StockMillDispatch::where('status', 'Active')->selectRaw('MAX(CAST(voucher_number AS UNSIGNED)) as last_no')->value('last_no');
                $voucherNumber = (string) (((int) $lastVoucherNumber) + 1);
            }

            $chalanExists = StockMillDispatch::where('chalan_no', $chalanNumber)->where('status', 'Active')->exists();

            if (! empty($chalanExists)) {
                $lastChalanNumber = StockMillDispatch::where('status', 'Active')->selectRaw('MAX(CAST(chalan_no AS UNSIGNED)) as last_no')->value('last_no');
                $chalanNumber = (string) (((int) $lastChalanNumber) + 1);
            }

            $alreadyDispatchedWis = StockMillDispatchItem::whereIn('wis_id', $wisIds)
                ->where('status', 'Active')
                ->lockForUpdate()
                ->pluck('wis_id')
                ->toArray();

            if (! empty($alreadyDispatchedWis)) {
                throw new \Exception('Some selected stock items are already dispatched. WIS ID: '.implode(', ', $alreadyDispatchedWis));
            }

            $items = Item::select(['item_id', 'item_name', 'item_type_id'])->where('item_id', $request->itemId)->where('status', 'Active')->first();

            if (empty($items)) {
                throw new \Exception('Item not found.');
            }

            $wisRows = WarehouseItemStock::whereIn('id', $wisIds)
                ->where('is_allotted_stock', '=', 'No')
                ->where('status', '=', 'Active')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($wisRows->count() != count($wisIds)) {
                throw new \Exception('Some selected stock items are already allotted or not available. Please refresh the page and try again.');
            }

            $itemName = $items->item_name;
            $dispatchItemTypeId = $items->item_type_id;

            $dispatch = new StockMillDispatch;
            $dispatch->vendor_id = $request->individual_id;
            $dispatch->ind_add_id = $request->ind_add_id;
            $dispatch->ind_add_id_ship = $request->ind_add_id_ship;
            $dispatch->work_name = $request->work_name;
            $dispatch->voucher_number = $voucherNumber;
            $dispatch->chalan_no = $chalanNumber;
            $dispatch->item_id = $request->itemId;

            $dispatch->chalan_date = date('Y-m-d', strtotime($request->chalan_date));
            $dispatch->dispatch_item_name = $itemName;
            $dispatch->dispatch_item_type_id = $dispatchItemTypeId;
            $dispatch->process_type = $request->process_type;
            $dispatch->work_type_id = $request->work_type_id;
            $dispatch->work_order_id = $workOrderId;
            $dispatch->job_work_status = JobWorkStatus::Dispatched;
            $dispatch->state = $request->state;
            $dispatch->vendor_name = $request->vendor_name;
            $dispatch->mobile = $request->mobile;
            $dispatch->email = $request->email;
            $dispatch->billing_address = $request->address;
            $dispatch->shipping_address = $request->shiping_address;
            $dispatch->total_pcs = 0;
            $dispatch->tot_meter = 0;
            $dispatch->remark = $allotmentRemark;
            $dispatch->created_by = auth()->id();
            $dispatch->created_at = now();
            $dispatch->modified_at = now();
            $dispatch->status = 'Active';
            $dispatch->save();

            $stockMillastId = $dispatch->id;
            $total_pcs = 0;
            $totMtrQty = 0;

            foreach ($wisIds as $index => $wisId) {
                $dataWIS = $wisRows[$wisId] ?? null;

                if (! $dataWIS) {
                    throw new \Exception('Selected stock item not available. WIS ID: '.$wisId);
                }

                $warehouseItemId = $dataWIS->warehouse_item_id;
                $dataWI = WarehouseItem::where('id', $warehouseItemId)->lockForUpdate()->first();

                if (empty($dataWI)) {
                    throw new \Exception('Warehouse Item Not Found. Warehouse Item ID: '.$warehouseItemId);
                }

                $itemIdQty = (float) ($dataWIS->insp_bal_quan_size ?? 0);

                if ($itemIdQty <= 0) {
                    throw new \Exception('Stock balance quantity is zero. WIS ID: '.$wisId);
                }

                $millDispatchItem = new StockMillDispatchItem;
                $millDispatchItem->stock_mill_dispatch_id = $dispatch->id;
                $millDispatchItem->wis_id = $wisId;
                $millDispatchItem->warehouse_item_id = $dataWIS->warehouse_item_id;
                $millDispatchItem->item_id = $dataWIS->item_id;
                $millDispatchItem->item_type_id = $dataWIS->item_type_id;
                $millDispatchItem->dyeing_color = $dataWIS->dyeing_color;
                $millDispatchItem->coated_pvc = $dataWIS->coated_pvc;
                $millDispatchItem->extra_job = $dataWIS->extra_job;
                $millDispatchItem->print_job = $dataWIS->print_job;
                $millDispatchItem->work_order_id = $workOrderId;
                $millDispatchItem->insp_quan_size = $itemIdQty;
                $millDispatchItem->insp_taka_number = $dataWIS->insp_taka_number;
                $millDispatchItem->dyeing_lot_number = $dataWIS->dyeing_lot_number;
                $millDispatchItem->dyeing_taka_number = $dataWIS->dyeing_taka_number;
                $millDispatchItem->financial_year = currentFinancialYear();
                $millDispatchItem->created_at = now();
                $millDispatchItem->modified_at = now();
                $millDispatchItem->status = 'Active';
                $millDispatchItem->save();

                $total_pcs++;
                $totMtrQty += $itemIdQty;

                $inspQuanSize = (float) $dataWIS->insp_quan_size;
                $inspAllotQuanSize = (float) $dataWIS->insp_allot_quan_size;
                $inspBalQuanSize = (float) $dataWIS->insp_bal_quan_size;
                $totAllotSize = $inspAllotQuanSize + $inspBalQuanSize;
                $balanQunSize = max(0, $inspQuanSize - $totAllotSize);

                $stockUpdated = WarehouseItemStock::where('id', $wisId)
                    ->where('is_allotted_stock', '=', 'No')
                    ->where('status', '=', 'Active')
                    ->update([
                        'insp_allot_quan_size' => $totAllotSize,
                        'insp_bal_quan_size' => $balanQunSize,
                        'is_allotted_stock' => $balanQunSize <= 0 ? 'Yes' : 'No',
                        'allocation_status' => $balanQunSize <= 0 ? 'allocated' : 'partially_allocated',
                        'mill_dispatch_id' => $dispatch->id,
                        'mill_dispatch_item_id' => $millDispatchItem->id,
                        'allot_work_order_id' => $workOrderId,
                        'work_pro_req_id' => $wprId,
                        'stock_alloted_by' => $individualId,
                        'alloted_remark' => $allotmentRemark,
                    ]);

                if (! $stockUpdated) {
                    throw new \Exception('Stock update failed. WIS ID: '.$wisId);
                }

                $newItem = WarehouseOutItem::create([
                    'process_type_id' => $dataWI->process_type_id ?? 0,
                    'wis_id' => $wisId,
                    'warehouse_item_id' => $warehouseItemId,
                    'warehouse_id' => $dataWI->warehouse_id,
                    'ware_comp_id' => $dataWI->ware_comp_id,
                    'item_id' => $dataWI->item_id,
                    'item_type_id' => $dataWI->item_type_id,
                    'unit_type_id' => $dataWI->unit_type_id,
                    'receiver_id' => $dataWI->receiver_id,
                    'item_qty' => $itemIdQty,
                    'pcs' => $dataWI->pcs ?? 0.00,
                    'cut' => $dataWI->cut,
                    'meter' => $dataWI->meter ?? 0.00,
                    'individual_id' => $individualId,
                    'work_pro_req_id' => $wprId,
                    'work_order_id' => $workOrderId,
                    'mill_dispatch_id' => $dispatch->id,
                    'mill_dispatch_item_id' => $millDispatchItem->id,
                    'item_remark' => $allotmentRemark,
                    'grey_quality' => $dataWI->grey_quality,
                    'dyeing_color' => $dataWIS->dyeing_color,
                    'coated_pvc' => $dataWIS->coated_pvc,
                    'print_job' => $dataWIS->print_job,
                    'extra_job' => $dataWIS->extra_job,
                    'insp_taka_number' => $dataWIS->insp_taka_number,
                    'dyeing_lot_number' => $dataWIS->dyeing_lot_number,
                    'dyeing_taka_number' => $dataWIS->dyeing_taka_number,
                    'created' => now(),
                    'financial_year' => currentFinancialYear(),
                    'status' => 'Active',
                ]);

                $query = WarehouseBalanceItem::where('item_id', $newItem->item_id)
                    ->where('item_type_id', $newItem->item_type_id)
                    ->where('dyeing_color', $newItem->dyeing_color)
                    ->where('coating_type', $newItem->coated_pvc)
                    ->where('print_job', $newItem->print_job)
                    ->where('extra_job', $newItem->extra_job)
                    ->where('balance_status', 1)
                    ->where('status', 'Active')
                    ->orderBy('id', 'desc')
                    ->lockForUpdate();

                $opItemQty = (float) ($query->value('item_qty') ?? 0);

                WarehouseBalanceItem::where('item_id', $newItem->item_id)
                    ->where('item_type_id', $newItem->item_type_id)
                    ->where('dyeing_color', $newItem->dyeing_color)
                    ->where('coating_type', $newItem->coated_pvc)
                    ->where('print_job', $newItem->print_job)
                    ->where('extra_job', $newItem->extra_job)
                    ->where('balance_status', 1)
                    ->where('status', 'Active')
                    ->update(['balance_status' => 0]);

                $closingItemQty = max(0, $opItemQty - $newItem->item_qty);

                $warehouseBalanceItem = new WarehouseBalanceItem([
                    'ware_in_item_id' => 0,
                    'ware_out_item_id' => $newItem->id,
                    'warehouse_id' => $newItem->warehouse_id,
                    'ware_comp_id' => $newItem->ware_comp_id,
                    'item_id' => $newItem->item_id,
                    'item_type_id' => $newItem->item_type_id,
                    'unit_type_id' => $newItem->unit_type_id,
                    'op_item_qty' => $opItemQty,
                    'in_item_qty' => 0,
                    'out_item_qty' => $newItem->item_qty,
                    'item_qty' => $closingItemQty,
                    'grey_quality' => $newItem->grey_quality,
                    'dyeing_color' => $newItem->dyeing_color,
                    'coated_pvc' => $newItem->coated_pvc,
                    'print_job' => $newItem->print_job,
                    'extra_job' => $newItem->extra_job,
                    'created' => now(),
                    'financial_year' => currentFinancialYear(),
                    'balance_status' => 1,
                    'status' => 'Active',
                ]);
                $warehouseBalanceItem->save();

                $totItemQty = (float) $dataWI->item_qty;
                $totAllotQty = (float) $dataWI->allotted_qty;

                WarehouseItem::where('id', $warehouseItemId)->update([
                    'item_qty' => max(0, $totItemQty - $itemIdQty),
                    'allotted_qty' => $totAllotQty + $itemIdQty,
                ]);

                if (! empty($workOrderId)) {
                    WorkProcessRequirement::where('id', '=', $wprId)->update([
                        'is_pro_acc_by_warehouse' => 'Yes',
                        'is_accept' => '1',
                        'requirement_status' => 'accepted',
                        'allocation_status' => 'allocated',
                        'alloted_quantity' => $receivedQuantity,
                        'process_accepted_by' => $individualId,
                        'alloted_remark' => $allotmentRemark,
                        'acc_deny_date' => now(),
                    ]);
                }
            }

            if ($total_pcs <= 0) {
                throw new \Exception('No stock item was dispatched.');
            }

            StockMillDispatch::where('id', $stockMillastId)->update([
                'tot_meter' => $totMtrQty,
                'total_pcs' => $total_pcs,
            ]);

            if (! empty($workOrderId)) {
                WorkOrder::where('work_order_id', '=', $workOrderId)->update(['is_work_require_request_accepted' => 'Yes']);
            }

            DB::commit();

            if ($lockTaken) {
                DB::select('SELECT RELEASE_LOCK(?) as lock_status', [$lockName]);
            }

            Session::put('message', 'Chalan generated successfully.');
            Session::put('messageClass', 'successClass');

            return redirect()->route('show-mill-chalan')->withInput();

        } catch (\Exception $e) {
            DB::rollBack();

            if ($lockTaken) {
                DB::select('SELECT RELEASE_LOCK(?) as lock_status', [$lockName]);
            }

            Session::put('message', 'Something went wrong: '.$e->getMessage());
            Session::put('messageClass', 'errorClass');

            return redirect()->back()->withInput();
        }
    }

    public function showMillChalan(Request $request)
    {
        $query = StockMillDispatch::where('status', 'Active')->with('Item');

        $status = $request->is_tot_mtr_received;
        if ($status == '1') {
            $query->where('is_tot_mtr_received', '0');
        } elseif ($status == '2') {
            $query->where('is_tot_mtr_received', '1');
        }

        if (! empty($request->itemName)) {
            $query->whereHas('item', function ($q) use ($request) {
                $q->where('item_name', 'like', '%'.$request->itemName.'%');
            });
        }
        if (! empty($request->vendorName)) {
            $query->where('vendor_name', 'like', '%'.$request->vendorName.'%');
        }

        if (! empty($request->from_date)) {
            $query->whereDate('chalan_date', '>=', date('Y-m-d', strtotime($request->from_date)));
        }

        if (! empty($request->to_date)) {
            $query->whereDate('chalan_date', '<=', date('Y-m-d', strtotime($request->to_date)));
        }

        $sbtSearch = $request->sbtSearch;
        if ($sbtSearch == 'ExportToExcel') {
            try {
                $dataWI = $query->get();

                return Excel::download(new StockMillDispatchItemExport($dataWI), 'job_mill_dispatch_item_export.xlsx');
            } catch (\Exception $e) {
                \Log::error('Exception: '.$e->getMessage());

                return response('Error generating Excel', 500);
            }
        }

        $cloneForTotal = clone $query;
        $allData = $cloneForTotal->get();
        $totalMeter = $allData->sum('tot_meter');
        $totalReceivedMeter = $allData->sum('tot_receive_mtr');

        $totalShortageMeter = $allData->where('is_tot_mtr_received', 1)->sum(function ($row) {
            return max(0, $row->tot_meter - $row->tot_receive_mtr);
        });
        $remainingMeter = max(0, $totalMeter - $totalReceivedMeter - $totalShortageMeter);
        $extraReceivedMeter = max(0, $totalReceivedMeter - $totalMeter);

        $dataWI = $query->orderByDesc('id')
            ->paginate(20)
            ->appends(request()->except('_token'));
        $itemName = $request->itemName;
        $qsearch = $request->vendorName;
        $fromDate = $request->from_date;
        $toDate = $request->to_date;
        $is_tot_mtr_received = $request->is_tot_mtr_received;

        return view('frontend.jobmillwork.show-mill-dispatch-stock', compact(
            'dataWI', 'totalMeter', 'totalReceivedMeter', 'totalShortageMeter',
            'remainingMeter', 'extraReceivedMeter',
            'itemName', 'qsearch', 'fromDate', 'toDate', 'is_tot_mtr_received'
        ));
    }

    public function updateVendor(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'dispatch_id' => 'required|integer|min:1',
            'vendor_id' => 'required|integer|min:1',
            'vendor_name' => 'required|string|max:255',
            'mobile' => ['required', 'regex:/^[0-9]{10,15}$/'],
            'email' => 'nullable|email|max:255',
            'billing_address' => 'nullable|string|max:1000',
            'shipping_address' => 'nullable|string|max:1000',
            'ind_add_id' => 'nullable|integer|min:1',
            'ind_add_id_ship' => 'nullable|integer|min:1',
        ], [
            'dispatch_id.required' => 'Dispatch ID is required.',
            'dispatch_id.integer' => 'Dispatch ID must be a number.',
            'vendor_id.required' => 'Vendor is required.',
            'vendor_name.required' => 'Vendor name is required.',
            'mobile.required' => 'Mobile number is required.',
            'mobile.regex' => 'Mobile number must be 10 to 15 digits only.',
            'email.email' => 'Please enter a valid email address.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 0,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = StockMillDispatch::find($request->dispatch_id);

        if (! $data) {
            return response()->json([
                'status' => 0,
                'message' => 'Record not found',
            ], 404);
        }

        $data->vendor_id = $request->vendor_id;
        $data->vendor_name = $request->vendor_name;
        $data->mobile = $request->mobile;
        $data->email = $request->email;
        $data->billing_address = $request->billing_address;
        $data->shipping_address = $request->shipping_address;
        $data->ind_add_id = $request->ind_add_id;
        $data->ind_add_id_ship = $request->ind_add_id_ship;

        $data->save();

        return response()->json([
            'status' => 'Active',
            'message' => 'Updated successfully',
            'data' => [
                'id' => $data->id,
                'vendor_id' => $data->vendor_id,
                'vendor_name' => $data->vendor_name,
                'mobile' => $data->mobile,
                'email' => $data->email,
                'billing_address' => $data->billing_address,
                'shipping_address' => $data->shipping_address,
            ],
        ]);
    }

    public function updateMtrReceivedStatus(Request $request)
    {
        $id = $request->id;
        $update = StockMillDispatch::where('id', $id)->update([
            'is_tot_mtr_received' => '1',
            'changed_status_by' => auth()->id(),
            'changed_status_at' => date('Y-m-d H:i:s'),
        ]);

        if ($update) {
            return response()->json(['success' => true]);
        } else {
            return response()->json(['success' => false]);
        }
    }

    public function printMillDispatchChalan($Id)
    {
        $mdId = dec($Id);
        $smData = StockMillDispatch::where('id', $mdId)->with('Vendor', 'ProcessType', 'Item', 'StockMillDispatchItem')->where('status', 'Active')->firstOrFail();

        return view('frontend.jobmillwork.print-mill-dispatch-chalan', compact('smData'));
    }

    public function printMillDispatchReceivedChalan($Id)
    {
        $mdId = dec($Id);
        $smData = StockMillDispatch::where('id', $mdId)->with('Vendor', 'ProcessType', 'StockMillDispatchItem.ReceiveStockMillDispatchItem', 'Item')->where('status', 'Active')->firstOrFail();

        return view('frontend.jobmillwork.print-receive-mill-dispatch-chalan', compact('smData'));
    }

    public function millDispatchReceivedItemInWarehouse($id)
    {

        $Id = dec($id);

        $dataP = StockMillDispatch::where('id', $Id)
            ->with(['Vendor',
                'StockMillDispatchItem' => function ($query) {
                    $query->where('status', 'Active')
                        ->where('is_item_received_in_warehouse', '0');
                },
                'StockMillDispatchItem.Item',
                'StockMillDispatchItem.ItemType',
            ])
            ->where('status', 'Active')
            ->firstOrFail();
        $dataIT = ItemType::where('status', 'Active')->where('is_work', '1')->whereIn('item_type_id', [3, 4, 5, 6])->orderBy('item_type_id')->get();
        $dataUT = UnitType::where('status', 'Active')->orderByDesc('unit_type_id')->get();
        $dataW = Warehouse::where('status', 'Active')->orderByDesc('id')->get();

        return view('frontend.jobmillwork.mill-dispatch-received-in-stock', compact('dataP', 'Id', 'dataIT', 'dataUT', 'dataW'));

    }

    public function storeMillDispatchReceivedItemInWarehouse(Request $request)
    {
        $validator = Validator::make($request->all(), [
            '_token' => 'required',
            'chalan_no' => 'required',
            'stock_mill_dispatch_id' => 'required|integer',
            'work_name' => 'required|string',
            'vendor_name' => 'required|string',
            'vendor_ind_id' => 'required|integer',
            'receiving_date' => 'required|date_format:d-m-Y',
            'stock_mill_dispatch_item_id' => 'required|array',
            'stock_mill_dispatch_item_id.*' => 'integer',
            'item_type_id' => 'required|array',
            'item_type_id.*' => 'integer',
            'item_name_arr' => 'required|array',
            'item_name_arr.*' => 'string',
            'item_id_arr' => 'required|array',
            'item_id_arr.*' => 'integer',
            'item_dyeing_color_arr' => 'required|array',
            'item_dyeing_color_arr.*' => 'string',
            'qty_arr' => 'required|array',
            'qty_arr.*' => 'numeric',
            'rec_qty_arr' => 'required|array',
            'rec_qty_arr.*' => 'integer',
            'unit_arr' => 'required|array',
            'unit_arr.*' => 'integer',
            'meter_arr' => 'required|array',
            'taka_number_arr' => 'required|array',
            'warehouseId' => 'required|array',
            'warehouseId.*' => 'integer',
            'warehouseCompId' => 'required|array',
            'warehouseCompId.*' => 'integer',
            'remarks' => 'nullable|array',
            'remarks.*' => 'string',
            'bill_front_img' => 'required|file|mimes:jpeg,jpg,png,pdf,webp|max:2048',
        ], [
            '_token.required' => 'The _token field is required.',
            'chalan_no.required' => 'The chalan number is required.',
            'stock_mill_dispatch_id.required' => 'The stock mill dispatch ID is required.',
            'stock_mill_dispatch_id.integer' => 'The stock mill dispatch ID must be an integer.',
            'work_name.required' => 'The work name is required.',
            'work_name.string' => 'The work name must be a string.',
            'vendor_name.required' => 'The vendor name is required.',
            'vendor_name.string' => 'The vendor name must be a string.',
            'vendor_ind_id.required' => 'The vendor individual ID is required.',
            'vendor_ind_id.integer' => 'The vendor individual ID must be an integer.',
            'receiving_date.required' => 'The receiving date is required.',
            'receiving_date.date_format' => 'The receiving date must be in the format d-m-Y.',
            'stock_mill_dispatch_item_id.required' => 'The stock mill dispatch item ID is required.',
            'stock_mill_dispatch_item_id.array' => 'The stock mill dispatch item ID must be an array.',
            'stock_mill_dispatch_item_id.*.integer' => 'Each stock mill dispatch item ID must be an integer.',
            'item_type_id.required' => 'The item type ID is required.',
            'item_type_id.array' => 'The item type ID must be an array.',
            'item_type_id.*.integer' => 'Each item type ID must be an integer.',
            'item_name_arr.required' => 'The item name array is required.',
            'item_name_arr.array' => 'The item name array must be an array.',
            'item_name_arr.*.string' => 'Each item name must be a string.',
            'item_id_arr.required' => 'The item ID array is required.',
            'item_id_arr.array' => 'The item ID array must be an array.',
            'item_id_arr.*.integer' => 'Each item ID must be an integer.',
            'item_dyeing_color_arr.required' => 'The item dyeing color array is required.',
            'item_dyeing_color_arr.array' => 'The item dyeing color array must be an array.',
            'item_dyeing_color_arr.*.string' => 'Each item dyeing color must be a string.',
            'qty_arr.required' => 'The quantity array is required.',
            'qty_arr.array' => 'The quantity array must be an array.',
            'qty_arr.*.numeric' => 'Each quantity must be a number.',
            'rec_qty_arr.required' => 'The received quantity array is required.',
            'rec_qty_arr.array' => 'The received quantity array must be an array.',
            'rec_qty_arr.*.integer' => 'Each received quantity must be an integer.',
            'unit_arr.required' => 'The unit array is required.',
            'unit_arr.array' => 'The unit array must be an array.',
            'unit_arr.*.integer' => 'Each unit must be an integer.',
            'meter_arr.required' => 'The meter array is required.',
            'meter_arr.array' => 'The meter array must be an array.',

            'taka_number_arr.required' => 'The taka number array is required.',
            'taka_number_arr.array' => 'The taka number array must be an array.',

            'warehouseId.required' => 'The warehouse ID array is required.',
            'warehouseId.array' => 'The warehouse ID array must be an array.',
            'warehouseId.*.integer' => 'Each warehouse ID must be an integer.',
            'warehouseCompId.required' => 'The warehouse compartment is required.',
            'warehouseCompId.array' => 'The warehouse compartment must be an array.',
            'warehouseCompId.*.integer' => 'Each warehouse compartment must be an integer.',
            'remarks.array' => 'The remarks must be an array.',
            'remarks.*.string' => 'Each remark must be a string.',
            'bill_front_img.required' => 'The bill front image is required.',
            'bill_front_img.file' => 'The bill front image must be a valid file.',
            'bill_front_img.mimes' => 'The bill front image must be a file of type: jpeg, jpg, png, pdf, or webp.',
            'bill_front_img.max' => 'The bill front image must not be greater than 2MB.',

        ]);

        if ($validator->fails()) {
            Session::put('message', $validator->messages()->first());
            Session::put('messageClass', 'errorClass');

            return redirect()->back()->withInput();
        }

        $userId = Auth::id();
        $user = User::find($userId);
        $individualId = $user->individual_id;
        $receiving_date = date('Y-m-d', strtotime($request->receiving_date));

        $uploadPath = public_path('uploads/mill_jobs_file');
        if (! file_exists($uploadPath)) {
            mkdir($uploadPath, 0775, true);
        }

        if ($request->hasFile('bill_front_img')) {
            $billFrontImgPath = $this->handleFileUpload($request->file('bill_front_img'), $uploadPath);
        }
        if ($request->hasFile('bill_back_img')) {
            $billBackImgPath = $this->handleFileUpload($request->file('bill_back_img'), $uploadPath);
        }

        DB::beginTransaction();
        try {
            $dispatchId = DB::table('receive_stock_mill_dispatches')->insertGetId([
                'stock_mill_dispatch_id' => $request->stock_mill_dispatch_id,
                'invoice_number' => $request->chalan_no,
                'vendor_name' => $request->vendor_name,
                'vendor_ind_id' => $request->vendor_ind_id,
                'receiving_date' => $receiving_date,
                'receiver_emp_name' => $user->name,
                'receiver_emp_ind_id' => $user->individual_id,
                'bill_front_img' => $billFrontImgPath,
                'bill_back_img' => $billBackImgPath,
                'created_at' => now(),
                'modified_at' => now(),
                'financial_year' => currentFinancialYear(),
                'status' => 'Active',
            ]);

            $totRcvMtr = 0;

            foreach ($request->stock_mill_dispatch_item_id as $index => $smditemId) {
                $itemId = $request->item_id_arr[$index];
                $warehouseId = $request->warehouseId['0'];
                $warehouseCompId = $request->warehouseCompId['0'];
                $itemTypeId = $request->item_type_id[$index];
                $takaNumber = $request->taka_number_arr[$index];
                $recQty = $request->rec_qty_arr[$index];
                $dyeingLotNumber = $request->dyeing_lot_number_arr[$index];
                $dyeingTakaNumber = $request->dyeing_taka_number_arr[$index];

                $recMeterRaw = trim($request->meter_arr[$index]);
                $meterParts = preg_split('/\+/', $recMeterRaw);

                foreach ($meterParts as $singleMeter) {
                    $singleMeter = floatval(trim($singleMeter));
                    if (! is_numeric($singleMeter)) {
                        throw new \Exception("Invalid meter value: '{$singleMeter}' at index {$index}");
                    }
                    $totRcvMtr += $singleMeter;

                    $smdItem = StockMillDispatchItem::findOrFail($smditemId);
                    $receivedQuantity = $smdItem->received_quantity + $singleMeter;
                    $balanceQuantity = $smdItem->insp_quan_size - $receivedQuantity;
                    $isItemFullyReceived = ($receivedQuantity >= 0.95 * $smdItem->insp_quan_size) ? '1' : '0';

                    $smdItem->update([
                        'received_quantity' => $receivedQuantity,
                        'balance_quantity' => $balanceQuantity,
                        'is_item_received_in_warehouse' => $isItemFullyReceived,
                    ]);

                    $rsmdiD = DB::table('receive_stock_mill_dispatch_items')->insertGetId([
                        'stock_mill_dispatch_id' => $request->stock_mill_dispatch_id,
                        'receive_mill_dispatch_id' => $dispatchId,
                        'stock_mill_dispatch_item_id' => $smditemId,
                        'item_type_id' => $itemTypeId,
                        'item_id' => $itemId,
                        'item_name' => $request->item_name_arr[$index],
                        'dyeing_color' => $request->item_dyeing_color_arr[$index] ?? null,
                        'coated_pvc' => $request->item_coating_arr[$index] ?? null,
                        'extra_job' => $request->item_extra_job_arr[$index] ?? null,
                        'print_job' => $request->item_print_arr[$index] ?? null,
                        'hsn' => $request->hsn_arr[$index] ?? null,
                        'qty' => $recQty,
                        'unit_type_id' => $request->unit_arr[$index],
                        'received_mtr' => $singleMeter,
                        'meter' => $singleMeter,
                        'taka_number' => $takaNumber,
                        'dyeing_lot_number' => $dyeingLotNumber ?? null,
                        'dyeing_taka_number' => $dyeingTakaNumber ?? null,
                        'remarks' => $request->remarks[$index] ?? null,
                        'created_at' => now(),
                        'modified_at' => now(),
                        'financial_year' => currentFinancialYear(),
                        'status' => 'Active',
                    ]);

                    $warehouseItem = new WarehouseItem([
                        'warehouse_id' => $warehouseId,
                        'ware_comp_id' => $warehouseCompId,
                        'ind_emp_id' => $user->individual_id,
                        'receive_date' => $receiving_date,
                        'grey_quality' => $request->item_name_arr[$index],
                        'pur_item_name' => $request->item_name_arr[$index],
                        'dyeing_color' => $request->item_dyeing_color_arr[$index] ?? null,
                        'coated_pvc' => $request->item_coating_arr[$index] ?? null,
                        'extra_job' => $request->item_extra_job_arr[$index] ?? null,
                        'print_job' => $request->item_print_arr[$index] ?? null,
                        'item_remark' => $request->remarks[$index] ?? null,
                        'item_id' => $itemId,
                        'item_type_id' => $itemTypeId,
                        'unit_type_id' => $request->unit_arr[$index],
                        'insp_taka_number' => $takaNumber,
                        'item_qty' => $singleMeter,
                        'entry_type' => 'IN',
                        'created' => now(),
                        'financial_year' => currentFinancialYear(),
                        'status' => 'Active',
                    ]);
                    $warehouseItem->save();

                    $existingBalance = WarehouseBalanceItem::where('warehouse_id', $warehouseItem->warehouse_id)
                        ->where('ware_comp_id', $warehouseItem->ware_comp_id)
                        ->where('item_id', $itemId)
                        ->where('item_type_id', $itemTypeId)
                        ->where('dyeing_color', $warehouseItem->dyeing_color)
                        ->where('balance_status', '1')
                        ->first();

                    if (! empty($existingBalance)) {
                        $wbId = $existingBalance->id;
                        WarehouseBalanceItem::where('id', $wbId)->update(['balance_status' => '0']);
                    }

                    WarehouseBalanceItem::create([
                        'ware_in_item_id' => $warehouseItem->id,
                        'ware_out_item_id' => 0,
                        'warehouse_id' => $warehouseItem->warehouse_id,
                        'ware_comp_id' => $warehouseItem->ware_comp_id,
                        'receive_date' => $warehouseItem->receive_date,
                        'receiver_id' => $warehouseItem->receiver_id,
                        'item_id' => $itemId,
                        'item_type_id' => $itemTypeId,
                        'unit_type_id' => $warehouseItem->unit_type_id,
                        'master_id' => $warehouseItem->master_id,
                        'machine_id' => $warehouseItem->machine_id,
                        'op_item_qty' => $existingBalance->item_qty ?? 0,
                        'in_item_qty' => $singleMeter,
                        'out_item_qty' => 0,
                        'item_qty' => ($existingBalance->item_qty ?? 0) + $singleMeter,
                        'grey_quality' => $warehouseItem->grey_quality,
                        'dyeing_color' => $warehouseItem->dyeing_color,
                        'coated_pvc' => $warehouseItem->coated_pvc,
                        'print_job' => $warehouseItem->print_job,
                        'extra_job' => $warehouseItem->extra_job,
                        'created' => now(),
                        'financial_year' => currentFinancialYear(),
                        'balance_status' => '1',
                        'status' => 'Active',
                    ]);

                    $wisId = $smdItem->wis_id;
                    $dataWIS = WarehouseItemStock::where('id', '=', $wisId)->where('status', '=', 'Active')->first();

                    WarehouseItemStock::create([
                        'warehouse_item_id' => $warehouseItem->id,
                        'receive_mill_dispatch_id' => $dispatchId,
                        'receive_mill_dispatch_item_id' => $rsmdiD,
                        'warehouse_id' => $warehouseItem->warehouse_id,
                        'ware_comp_id' => $warehouseItem->ware_comp_id,
                        'item_remark' => $warehouseItem->item_remark,
                        'dyeing_color' => $warehouseItem->dyeing_color,
                        'coated_pvc' => $warehouseItem->coated_pvc,
                        'print_job' => $warehouseItem->print_job,
                        'extra_job' => $warehouseItem->extra_job,
                        'quantity' => $recQty,
                        'insp_quan_size' => $singleMeter,
                        'insp_allot_quan_size' => 0,
                        'insp_bal_quan_size' => $singleMeter,
                        'quan_size_unit' => 'Meter',
                        'entry_type' => 'IN',
                        'receive_date' => $receiving_date,
                        'receiver_id' => $individualId,
                        'vendor_id' => $request->vendor_ind_id,
                        'item_id' => $itemId,
                        'item_type_id' => $warehouseItem->item_type_id,
                        'unit_type_id' => $warehouseItem->unit_type_id,
                        'work_order_id' => $dataWIS->work_order_id ?? null,
                        'allot_work_order_id' => $dataWIS->allot_work_order_id ?? null,
                        'packaging_ord_id' => $dataWIS->packaging_ord_id ?? null,
                        'ppr_id' => $dataWIS->ppr_id ?? null,
                        'insp_id' => $dataWIS->insp_id ?? null,
                        'gate_pass_id' => $dataWIS->gate_pass_id ?? null,
                        'work_pro_req_id' => $dataWIS->work_pro_req_id ?? null,
                        'beam_meter' => $dataWIS->beam_meter ?? null,
                        'master_id' => $dataWIS->master_id ?? null,
                        'machine_id' => $dataWIS->machine_id ?? null,
                        'invoice_number' => $dataWIS->invoice_number ?? null,
                        'purchase_date' => $dataWIS->purchase_date ?? null,
                        'packet_number' => $dataWIS->packet_number ?? null,
                        'is_allotted_stock' => 'No',
                        'allocation_status' => 'unallocated',
                        'stock_alloted_by' => 0,
                        'alloted_remark' => $request->remarks[$index] ?? null,
                        'insp_taka_number' => $takaNumber ?? null,
                        'dyeing_lot_number' => $dyeingLotNumber ?? null,
                        'dyeing_taka_number' => $dyeingTakaNumber ?? null,
                        'fabric_fault_reason_id' => $fabricFaultReasonId ?? null,
                        'grey_quality' => $dataWIS->grey_quality ?? null,
                        'inspected_by' => $dataWIS->inspected_by ?? null,
                        'insp_comment' => $dataWIS->insp_comment ?? null,
                        'mill_dispatch_id' => $dataWIS->mill_dispatch_id ?? null,
                        'mill_dispatch_item_id' => $dataWIS->mill_dispatch_item_id ?? null,
                        'return_packaging_ord_id' => $dataWIS->return_packaging_ord_id ?? null,
                        'is_deleted' => '0',
                        'created' => now(),
                        'financial_year' => currentFinancialYear(),
                        'status' => 'Active',
                    ]);
                }
            }
            StockMillDispatch::where('id', $request->stock_mill_dispatch_id)->update([
                'tot_receive_mtr' => $totRcvMtr,
            ]);

            $stockMillDispatchId = $request->stock_mill_dispatch_id;
            $datasmD = StockMillDispatchItem::where('stock_mill_dispatch_id', '=', $stockMillDispatchId)->where('status', '=', 'Active')->where('is_item_received_in_warehouse', '=', '0')->first();

            if (empty($datasmD->id)) {
                StockMillDispatch::where('id', $request->stock_mill_dispatch_id)->update([
                    'is_tot_mtr_received' => '1',
                ]);
            }
            app(TransitionJobWork::class)->execute(
                StockMillDispatch::findOrFail($request->stock_mill_dispatch_id),
                empty($datasmD->id) ? JobWorkStatus::Received : JobWorkStatus::PartiallyReceived,
            );

            DB::commit();

            Session::put('message', 'Data saved successfully.');
            Session::put('messageClass', 'successClass');

            return redirect()->route('show-mill-chalan')->withInput();

        } catch (\Exception $e) {
            DB::rollBack();
            Session::put('message', 'Something went wrong: '.$e->getMessage().' on line '.$e->getLine());
            Session::put('messageClass', 'errorClass');

            return redirect()->back()->withInput();
        }

    }

    private function handleFileUpload($file, $uploadPath)
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $filename = time().'_'.uniqid().'_mill_jobs_file_'.preg_replace('/\s+/', '_', $file->getClientOriginalName());
        $fullPath = $uploadPath.'/'.$filename;

        if (in_array($extension, ['jpg', 'jpeg', 'png']) && $file->getSize() > 1024 * 1024) {
            $manager = new ImageManager(new GdDriver);
            $image = $manager->read($file);
            if ($image->width() > 2000) {
                $image->scale(width: 2000);
            }
            $image->toJpeg(quality: 75)->save($fullPath);
        } else {
            $file->move($uploadPath, $filename);
        }

        return 'uploads/mill_jobs_file/'.$filename;
    }

    public function breakMeter(Request $request)
    {
        $request->validate([
            'wis_id' => 'required|integer',
            'parts' => 'required|string',
        ]);

        $wisId = $request->wis_id;
        $partsStr = $request->parts;

        $partsArr = array_map('trim', explode('+', $partsStr));
        if (count($partsArr) !== 2) {
            return response()->json(['success' => false, 'message' => 'Enter exactly two parts separated by + (e.g. 65+35).'], 422);
        }

        $p1 = preg_replace('/[^0-9.\-]/', '', $partsArr[0]);
        $p2 = preg_replace('/[^0-9.\-]/', '', $partsArr[1]);

        if (! is_numeric($p1) || ! is_numeric($p2)) {
            return response()->json(['success' => false, 'message' => 'Parts must be numeric.'], 422);
        }

        $p1 = number_format((float) $p1, 2, '.', '');
        $p2 = number_format((float) $p2, 2, '.', '');

        $orig = WarehouseItemStock::find($wisId);
        if (! $orig) {
            return response()->json(['success' => false, 'message' => 'Original stock not found.'], 404);
        }

        $orig_bal = is_null($orig->insp_bal_quan_size) ? '0.00' : number_format((float) $orig->insp_bal_quan_size, 2, '.', '');

        \Log::info('bcmath installed? '.(function_exists('bcadd') ? 'yes' : 'no'));

        if (function_exists('bcadd')) {
            $sum = \bcadd($p1, $p2, 2);
        } else {
            $sumFloat = round((float) $p1 + (float) $p2, 2);
            $sum = number_format($sumFloat, 2, '.', '');
        }

        $equal = false;
        if (function_exists('bccomp')) {
            $equal = (\bccomp($sum, $orig_bal, 2) === 0);
        } else {
            $sumF = round((float) $sum, 2);
            $origF = round((float) $orig_bal, 2);
            $equal = (abs($sumF - $origF) < 0.005);
        }

        if (! $equal) {
            return response()->json([
                'success' => false,
                'message' => "Sum of parts ({$sum}) must equal current balance ({$orig_bal}).",
            ], 422);
        }

        DB::beginTransaction();
        try {
            $orig->insp_quan_size = $p1;
            $orig->insp_allot_quan_size = 0.00;
            $orig->insp_bal_quan_size = $p1;
            $orig->save();

            $new = $orig->replicate();
            $new->insp_quan_size = $p2;
            $new->insp_allot_quan_size = 0.00;
            $new->insp_bal_quan_size = $p2;
            $new->created = now();
            $new->status = $orig->status;
            $new->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Stock split saved.',
                'first_wis_id' => $orig->id,
                'second_wis_id' => $new->id,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('BreakMeter error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to split stock: '.$e->getMessage(),
            ], 500);
        }
    }

    public function millDispatchReceivedWeavingItemInWarehouse($id)
    {
        $Id = dec($id);
        $dataP = StockMillDispatch::where('id', $Id)
            ->with([
                'Vendor',
                'StockMillDispatchItem' => function ($query) {
                    $query->where('status', 'Active')
                        ->where('is_item_received_in_warehouse', '0');
                },
                'StockMillDispatchItem.Item' => function ($q) {
                    $q->select([
                        'item_id', 'item_name', 'item_code', 'unit_type_id', 'item_gsm', 'item_width',
                    ]);
                },
                'StockMillDispatchItem.ItemType',
            ])->where('status', 'Active')->firstOrFail();

        $vendorId = $dataP->vendor_id;

        $availableSources = StockMillDispatchItem::with([
            'Item' => function ($q) {
                $q->select(['item_id', 'item_name', 'item_code', 'unit_type_id', 'item_gsm', 'item_width']);
            },
        ])
            ->whereIn('item_type_id', [1, 2])
            ->where('status', 'Active')
            ->where(function ($q) {
                $q->where('balance_quantity', '>', 0)
                    ->orWhereRaw('(COALESCE(insp_quan_size,0) - COALESCE(received_quantity,0)) > 0');
            })
            ->whereHas('StockMillDispatch', function ($q) use ($vendorId) {
                $q->where('vendor_id', $vendorId)
                    ->where('process_type', 1)
                    ->where('status', 'Active');
            })
            ->orderByDesc('id')
            ->get();

        $dataIT = ItemType::where('status', 'Active')->where('is_work', '1')->whereIn('item_type_id', [3, 4, 5, 6])->orderBy('item_type_id')->get();
        $dataUT = UnitType::where('status', 'Active')->orderByDesc('unit_type_id')->get();
        $dataW = Warehouse::where('status', 'Active')->orderByDesc('id')->get();

        return view(
            'frontend.jobmillwork.mill-dispatch-received-in-weaving-stock',
            compact('dataP', 'Id', 'dataIT', 'dataUT', 'dataW', 'availableSources')
        );
    }

    public function storeMillDispatchReceivedWeavingItemInWarehouse(Request $request)
    {
        $validator = Validator::make($request->all(), [
            '_token' => 'required',
            'chalan_no' => 'required',
            'stock_mill_dispatch_id' => 'required|integer',
            'work_name' => 'required|string',
            'vendor_name' => 'required|string',
            'vendor_ind_id' => 'required|integer',
            'receiving_date' => 'required|date_format:d-m-Y',
            'stock_mill_dispatch_item_id' => 'required|array',
            'stock_mill_dispatch_item_id.*' => 'integer',
            'pur_type_arr' => 'required|array',
            'pur_type_arr.*' => 'integer',
            'item_name_arr' => 'required|array',
            'item_name_arr.*' => 'string',
            'item_id_arr' => 'required|array',
            'item_id_arr.*' => 'integer',
            'item_dyeing_color_arr' => 'required|array',
            'item_dyeing_color_arr.*' => 'string',
            'qty_arr' => 'required|array',
            'qty_arr.*' => 'numeric',
            'rec_qty_arr' => 'required|array',
            'rec_qty_arr.*' => 'integer',
            'unit_arr' => 'required|array',
            'unit_arr.*' => 'integer',
            'meter_arr' => 'required|array',
            'taka_number_arr' => 'required|array',
            'warehouseId' => 'required|array',
            'warehouseId.*' => 'integer',
            'warehouseCompId' => 'required|array',
            'warehouseCompId.*' => 'integer',
            'remarks' => 'nullable|array',
            'remarks.*' => 'string',
            'bill_front_img' => 'required|file|mimes:jpeg,jpg,png,pdf,webp|max:2048',
        ], [
            '_token.required' => 'The _token field is required.',
            'chalan_no.required' => 'The chalan number is required.',
            'stock_mill_dispatch_id.required' => 'The stock mill dispatch ID is required.',
            'stock_mill_dispatch_id.integer' => 'The stock mill dispatch ID must be an integer.',
            'work_name.required' => 'The work name is required.',
            'work_name.string' => 'The work name must be a string.',
            'vendor_name.required' => 'The vendor name is required.',
            'vendor_name.string' => 'The vendor name must be a string.',
            'vendor_ind_id.required' => 'The vendor individual ID is required.',
            'vendor_ind_id.integer' => 'The vendor individual ID must be an integer.',
            'receiving_date.required' => 'The receiving date is required.',
            'receiving_date.date_format' => 'The receiving date must be in the format d-m-Y.',
            'stock_mill_dispatch_item_id.required' => 'The stock mill dispatch item ID is required.',
            'stock_mill_dispatch_item_id.array' => 'The stock mill dispatch item ID must be an array.',
            'stock_mill_dispatch_item_id.*.integer' => 'Each stock mill dispatch item ID must be an integer.',
            'pur_type_arr.required' => 'The item type ID is required.',
            'pur_type_arr.array' => 'The item type ID must be an array.',
            'pur_type_arr.*.integer' => 'Each item type ID must be an integer.',
            'item_name_arr.required' => 'The item name array is required.',
            'item_name_arr.array' => 'The item name array must be an array.',
            'item_name_arr.*.string' => 'Each item name must be a string.',
            'item_id_arr.required' => 'The item ID array is required.',
            'item_id_arr.array' => 'The item ID array must be an array.',
            'item_id_arr.*.integer' => 'Each item ID must be an integer.',
            'item_dyeing_color_arr.required' => 'The item dyeing color array is required.',
            'item_dyeing_color_arr.array' => 'The item dyeing color array must be an array.',
            'item_dyeing_color_arr.*.string' => 'Each item dyeing color must be a string.',
            'qty_arr.required' => 'The quantity array is required.',
            'qty_arr.array' => 'The quantity array must be an array.',
            'qty_arr.*.numeric' => 'Each quantity must be a number.',
            'rec_qty_arr.required' => 'The received quantity array is required.',
            'rec_qty_arr.array' => 'The received quantity array must be an array.',
            'rec_qty_arr.*.integer' => 'Each received quantity must be an integer.',
            'unit_arr.required' => 'The unit array is required.',
            'unit_arr.array' => 'The unit array must be an array.',
            'unit_arr.*.integer' => 'Each unit must be an integer.',
            'meter_arr.required' => 'The meter array is required.',
            'meter_arr.array' => 'The meter array must be an array.',
            'taka_number_arr.required' => 'The taka number array is required.',
            'taka_number_arr.array' => 'The taka number array must be an array.',
            'warehouseId.required' => 'The warehouse ID array is required.',
            'warehouseId.array' => 'The warehouse ID array must be an array.',
            'warehouseId.*.integer' => 'Each warehouse ID must be an integer.',
            'warehouseCompId.required' => 'The warehouse compartment is required.',
            'warehouseCompId.array' => 'The warehouse compartment must be an array.',
            'warehouseCompId.*.integer' => 'Each warehouse compartment must be an integer.',
            'remarks.array' => 'The remarks must be an array.',
            'remarks.*.string' => 'Each remark must be a string.',
            'bill_front_img.required' => 'The bill front image is required.',
            'bill_front_img.file' => 'The bill front image must be a valid file.',
            'bill_front_img.mimes' => 'The bill front image must be a file of type: jpeg, jpg, png, pdf, or webp.',
            'bill_front_img.max' => 'The bill front image must not be greater than 2MB.',
        ]);
        if ($validator->fails()) {
            Session::put('message', $validator->messages()->first());
            Session::put('messageClass', 'errorClass');

            return redirect()->back()->withInput();
        }
        $flag = true;
        $errors = [];

        $user = Auth::user();
        $userId = Auth::id();
        $userD = User::find($userId);
        $IndId = $userD->individual_id ?? null;

        if (empty($request->stock_mill_dispatch_item_id) || ! is_array($request->stock_mill_dispatch_item_id)) {
            $flag = false;
            $errors[] = 'No dispatch items provided.';
        }

        if (empty($request->product_name_arr) || ! is_array($request->product_name_arr)) {
            $flag = false;
            $errors[] = 'No products provided.';
        }

        if (empty($request->warehouseId) || ! isset($request->warehouseId[0])) {
            $flag = false;
            $errors[] = 'Warehouse not selected.';
        }

        if (! $flag) {
            Session::put('message', 'Validation failed: '.implode(' | ', $errors));
            Session::put('messageClass', 'errorClass');

            return redirect()->back()->withInput();
        }

        $receiving_date = date('Y-m-d', strtotime($request->receiving_date ?? now()));
        $invoice_number = $request->chalan_no ?? null;

        $uploadPath = public_path('uploads/mill_jobs_file');
        if (! file_exists($uploadPath)) {
            mkdir($uploadPath, 0775, true);
        }

        if ($request->hasFile('bill_front_img')) {
            $billFrontImgPath = $this->handleFileUpload($request->file('bill_front_img'), $uploadPath);
        }
        if ($request->hasFile('bill_back_img')) {
            $billBackImgPath = $this->handleFileUpload($request->file('bill_back_img'), $uploadPath);
        }

        DB::beginTransaction();
        try {
            $dispatchId = DB::table('receive_stock_mill_dispatches')->insertGetId([
                'stock_mill_dispatch_id' => $request->stock_mill_dispatch_id,
                'invoice_number' => $invoice_number,
                'vendor_name' => $request->vendor_name,
                'vendor_ind_id' => $request->vendor_ind_id,
                'receiving_date' => $receiving_date,
                'receiver_emp_name' => $user->name ?? null,
                'receiver_emp_ind_id' => $user->individual_id ?? null,
                'bill_front_img' => $billFrontImgPath ?? null,
                'bill_back_img' => $billBackImgPath ?? null,
                'created_at' => now(),
                'modified_at' => now(),
                'financial_year' => currentFinancialYear(),
                'status' => 'Active',
            ]);

            if (! empty($request->stock_mill_dispatch_item_id) && is_array($request->stock_mill_dispatch_item_id)) {
                $updatedDispatchIds = [];
                foreach ($request->stock_mill_dispatch_item_id as $index => $smditemId) {
                    $itemId = $request->item_id_arr[$index] ?? null;
                    $dispatchItem = Item::select(['item_id', 'item_name', 'item_type_id'])->where('item_id', $itemId)->where('status', 'Active')->first();
                    $dispatchItemTypeId = (int) ($dispatchItem->item_type_id ?? 0);
                    $dispatchItemName = $dispatchItem->item_name ?? null;
                    $currentRcvdMeter = floatval($request->current_rcvd_meter_arr[$index] ?? 0);

                    $smdItem = StockMillDispatchItem::lockForUpdate()->find($smditemId);
                    $StockMillDispatchId = $smdItem->stock_mill_dispatch_id;

                    $newReceivedQuantity = floatval($smdItem->received_quantity ?? 0) + $currentRcvdMeter;
                    $inspQuanSize = floatval($smdItem->insp_quan_size ?? 0);
                    $newBalanceQuantity = $inspQuanSize - $newReceivedQuantity;
                    if ($newBalanceQuantity < 0) {
                        $newBalanceQuantity = 0;
                    }
                    $isItemFullyReceived = ($newReceivedQuantity >= (0.95 * $inspQuanSize) && $inspQuanSize > 0) ? 1 : 0;

                    $smdItem->update([
                        'received_quantity' => $newReceivedQuantity,
                        'balance_quantity' => $newBalanceQuantity,
                        'is_item_received_in_warehouse' => $isItemFullyReceived,
                    ]);

                    DB::table('receive_stock_mill_dispatch_items')->insertGetId([
                        'stock_mill_dispatch_id' => $StockMillDispatchId,
                        'receive_mill_dispatch_id' => $dispatchId,
                        'stock_mill_dispatch_item_id' => $smditemId,
                        'item_type_id' => $request->pur_type_arr[$index] ?? ($request->item_type_id[$index] ?? null),
                        'item_id' => $itemId,
                        'item_name' => $dispatchItemName,
                        'hsn' => $request->hsn_arr[$index] ?? null,
                        'qty' => $request->qty_arr[$index] ?? 1,
                        'unit_type_id' => $request->unit_arr[$index] ?? null,
                        'received_mtr' => $currentRcvdMeter,
                        'meter' => $currentRcvdMeter,

                        'taka_number' => $request->taka_number_arr[$index] ?? null,
                        'dyeing_lot_number' => $request->dyeing_lot_number_arr[$index] ?? null,
                        'dyeing_taka_number' => $request->dyeing_taka_number_arr[$index] ?? null,
                        'remarks' => $request->remarks_arr[$index] ?? ($request->remarks[$index] ?? null),
                        'created_at' => now(),
                        'modified_at' => now(),
                        'financial_year' => currentFinancialYear(),
                        'status' => 'Active',
                    ]);

                    $used_yarn_id = null;
                    $used_yarn_qty = null;
                    $used_beam_id = null;
                    $used_beam_qty = null;

                    if ($dispatchItemTypeId === 1) {
                        $used_yarn_id = $request->used_yarn_id[$index] ?? null;
                        $used_yarn_qty = $currentRcvdMeter;
                    } elseif ($dispatchItemTypeId === 2) {
                        $used_beam_id = $request->used_beam_id[$index] ?? null;
                        $used_beam_qty = $currentRcvdMeter;
                    }

                    GreigeReceiveStockItemFromJobWorks::create([
                        'greige_receive_id' => $dispatchId,
                        'stock_mill_dispatch_item_id' => $smditemId,
                        'received_item_id' => $itemId,
                        'received_mtr' => $currentRcvdMeter,
                        'used_yarn_id' => $used_yarn_id,
                        'used_yarn_qty' => $used_yarn_qty,
                        'used_beam_id' => $used_beam_id,
                        'used_beam_qty' => $used_beam_qty,
                        'unit_type_id' => $request->unit_arr[$index] ?? null,
                        'taka_no' => $request->taka_number_arr[$index] ?? null,
                        'lot_no' => $request->dyeing_lot_number_arr[$index] ?? null,
                        'remarks' => $request->remarks_arr[$index] ?? null,
                        'created_at' => now(),
                        'modified_at' => now(),
                        'financial_year' => currentFinancialYear(),
                        'status' => 'Active',
                    ]);
                    $updatedDispatchIds[] = $StockMillDispatchId;

                }

            }

            $updatedDispatchIds = array_unique($updatedDispatchIds);

            foreach ($updatedDispatchIds as $did) {
                StockMillDispatch::where('id', $did)->update([
                    'tot_receive_mtr' => DB::table('receive_stock_mill_dispatch_items')
                        ->where('stock_mill_dispatch_id', $did)
                        ->sum('received_mtr'),
                ]);
            }

            if (! $flag) {
                throw new \Exception('Processing error: '.implode(' | ', $errors));
            }

            $chalan_no = $request->chalan_no;
            $stock_mill_dispatch_id = $request->stock_mill_dispatch_id;
            $warehouseId = $request->warehouseId[0] ?? null;
            $warehouseCompId = $request->warehouseCompId[0] ?? null;
            $product_name_arr = $request->product_name_arr ?? [];
            $pro_id_arr = $request->pro_id_arr ?? [];
            $hsn_arr = $request->hsn_arr ?? [];
            $qty_arr = $request->qty_arr ?? [];
            $unit_arr = $request->unit_arr ?? [];
            $meter_arr = $request->meter_arr ?? [];
            $beam_meter_arr = $request->beam_meter_arr ?? [];
            $remarks_arr = $request->remarks_arr ?? [];
            $pur_type_arr = $request->pur_type_arr ?? [];
            $taka_number_arr = $request->taka_number_arr ?? [];

            foreach ($product_name_arr as $proidk => $pro_name) {
                $itemId = $pro_id_arr[$proidk] ?? null;
                if (empty($itemId)) {
                    $flag = false;
                    $errors[] = "Product item id missing for product index {$proidk}.";
                    break;
                }

                $itemVal = Item::where('item_id', $itemId)->where('status', 'Active')->first();
                if (! $itemVal) {
                    $flag = false;
                    $errors[] = "Product item not found or inactive for item id {$itemId}.";
                    break;
                }

                $ItemQty = $qty_arr[$proidk] ?? 0;
                $meterQty = $meter_arr[$proidk] ?? 0;
                $beamMeterQty = $beam_meter_arr[$proidk] ?? 0;
                $remarksArr = $remarks_arr[$proidk] ?? null;
                $itemTypeId = $pur_type_arr[$proidk] ?? null;
                $unitTypeId = $unit_arr[$proidk] ?? null;
                $takaNumber = $taka_number_arr[$proidk] ?? null;

                if (empty($ItemQty)) {
                    $flag = false;
                    $errors[] = "Product quantity missing or zero for product index {$proidk}.";
                    break;
                }

                $warehouseItem = new WarehouseItem([
                    'warehouse_id' => $warehouseId,
                    'ware_comp_id' => $warehouseCompId,
                    'ind_emp_id' => $IndId,
                    'emp_name' => $userD->name ?? null,
                    'receiver_id' => $IndId ?? null,
                    'item_id' => $itemId,
                    'receive_date' => $receiving_date,
                    'grey_quality' => $product_name_arr[$proidk],
                    'invoice_number' => $invoice_number ?? null,
                    'pur_item_name' => $product_name_arr[$proidk],
                    'dyeing_color' => null,
                    'item_remark' => $remarksArr,
                    'item_type_id' => $itemTypeId,
                    'unit_type_id' => $unitTypeId,
                    'insp_taka_number' => $takaNumber,
                    'item_qty' => ! empty($meterQty) ? $meterQty : $ItemQty,
                    'beam_meter' => $beamMeterQty,
                    'entry_type' => 'IN',
                    'created' => now(),
                    'financial_year' => currentFinancialYear(),
                    'status' => 'Active',
                ]);
                $is_saved = $warehouseItem->save();
                $lastInsertId = $warehouseItem->getKey();

                $opItemQty = WarehouseBalanceItem::where('item_id', $warehouseItem->item_id)
                    ->where('item_type_id', $warehouseItem->item_type_id)
                    ->where('dyeing_color', $warehouseItem->dyeing_color)
                    ->where('coating_type', $warehouseItem->coated_pvc)
                    ->where('print_job', $warehouseItem->print_job)
                    ->where('extra_job', $warehouseItem->extra_job)
                    ->where('balance_status', '1')
                    ->first();

                if (! empty($opItemQty)) {
                    $wbId = $opItemQty->id;
                    WarehouseBalanceItem::where('id', $wbId)->update(['balance_status' => '0']);
                }

                WarehouseBalanceItem::create([
                    'ware_in_item_id' => $warehouseItem->id,
                    'ware_out_item_id' => 0,
                    'warehouse_id' => $warehouseItem->warehouse_id,
                    'ware_comp_id' => $warehouseItem->ware_comp_id,
                    'receiver_id' => $warehouseItem->receiver_id,
                    'receive_date' => $warehouseItem->receive_date,
                    'item_id' => $warehouseItem->item_id,
                    'item_type_id' => $warehouseItem->item_type_id,
                    'unit_type_id' => $warehouseItem->unit_type_id,
                    'master_id' => $warehouseItem->master_id,
                    'machine_id' => $warehouseItem->machine_id,
                    'op_item_qty' => $opItemQty ? $opItemQty->item_qty : 0,
                    'in_item_qty' => $warehouseItem->item_qty,
                    'out_item_qty' => 0,
                    'item_qty' => $opItemQty ? ($opItemQty->item_qty + $warehouseItem->item_qty) : $warehouseItem->item_qty,
                    'grey_quality' => $warehouseItem->grey_quality,
                    'dyeing_color' => $warehouseItem->dyeing_color,
                    'coated_pvc' => $warehouseItem->coated_pvc,
                    'print_job' => $warehouseItem->print_job,
                    'extra_job' => $warehouseItem->extra_job,
                    'created' => now(),
                    'financial_year' => currentFinancialYear(),
                    'balance_status' => '1',
                    'status' => 'Active',
                ]);

                $vendorId = $request->vendor_ind_id ?? null;
                $obj2 = new WarehouseItemStock;
                $obj2->warehouse_item_id = $lastInsertId;
                $obj2->warehouse_id = $warehouseItem->warehouse_id;
                $obj2->ware_comp_id = $warehouseItem->ware_comp_id;
                $obj2->item_remark = $warehouseItem->item_remark;
                $obj2->dyeing_color = $warehouseItem->dyeing_color;
                $obj2->quantity = $ItemQty;
                $obj2->beam_meter = $warehouseItem->beam_meter;
                $obj2->insp_quan_size = $meterQty;
                $obj2->insp_allot_quan_size = 0;
                $obj2->insp_bal_quan_size = $obj2->insp_quan_size - $obj2->insp_allot_quan_size;
                $obj2->quan_size_unit = 'Meter';
                $obj2->entry_type = 'IN';
                $obj2->receive_date = $receiving_date;
                $obj2->receiver_id = $IndId ?? null;
                $obj2->vendor_id = $vendorId;
                $obj2->invoice_number = $invoice_number ?? null;
                $obj2->item_id = $itemId;
                $obj2->item_type_id = $itemTypeId;
                $obj2->unit_type_id = $unitTypeId;
                $obj2->insp_taka_number = $takaNumber;
                $obj2->insp_comment = $remarksArr ?? null;
                $obj2->mill_dispatch_id = $request->stock_mill_dispatch_id ?? null;
                $obj2->financial_year = currentFinancialYear();
                $obj2->created = date('Y-m-d H:i:s');
                $obj2->modified = date('Y-m-d');
                $obj2->status = 1;
                $is_saved2 = $obj2->save();
                $lastInsertedStockId = $obj2->id;

                $stocFArr = new WarehouseItemStockFile;
                $stocFArr->warehouse_item_id = $lastInsertId;
                $stocFArr->wis_id = $lastInsertedStockId;
                $stocFArr->wis_out_id = null;
                $stocFArr->vendor_id = $vendorId;
                $stocFArr->challan_num = $invoice_number ?? null;
                $stocFArr->bill_front_img = $billFrontImgPath ?? null;
                $stocFArr->bill_back_img = $billBackImgPath ?? null;
                $stocFArr->financial_year = currentFinancialYear();
                $stocFArr->created = date('Y-m-d');
                $stocFArr->modified = date('Y-m-d');
                $stocFArr->status = 1;
                $rsArr_saved = $stocFArr->save();
            }

            if (! $flag) {
                throw new \Exception('Processing error: '.implode(' | ', $errors));
            }

            $datasmD = StockMillDispatchItem::where('stock_mill_dispatch_id', $request->stock_mill_dispatch_id)->where('status', 'Active')->where('is_item_received_in_warehouse', '0')->first();

            if (empty($datasmD)) {
                StockMillDispatch::where('id', $request->stock_mill_dispatch_id)->update(['is_tot_mtr_received' => 1]);
            }
            app(TransitionJobWork::class)->execute(
                StockMillDispatch::findOrFail($request->stock_mill_dispatch_id),
                empty($datasmD) ? JobWorkStatus::Received : JobWorkStatus::PartiallyReceived,
            );

            DB::commit();

            Session::put('message', 'Data saved successfully.');
            Session::put('messageClass', 'successClass');

            return redirect()->route('show-mill-chalan')->withInput();
        } catch (\Exception $e) {
            DB::rollBack();
            $message = ! empty($errors) ? implode(' | ', $errors) : $e->getMessage();
            Session::put('message', 'Something went wrong: '.$message.' on line '.$e->getLine());
            Session::put('messageClass', 'errorClass');

            return redirect()->back()->withInput();
        }
    }
}
