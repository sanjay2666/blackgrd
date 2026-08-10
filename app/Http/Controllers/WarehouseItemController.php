<?php

namespace App\Http\Controllers;

use App\Enums\InventoryReceiptStatus;
use App\Enums\PurchaseOrderDocumentStatus;
use App\Exports\WarehouseBalanceStockListing;
use App\Exports\WarehouseStockDetailsListing;
use App\Exports\WarehouseStockReportExport;
use App\Models\DepartmentReturn;
use App\Models\DepartmentReturnRequest;
use App\Models\GstRate;
use App\Models\Individual;
use App\Models\Item;
use App\Models\ItemType;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\UnitType;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseBalanceItem;
use App\Models\WarehouseCompartment;
use App\Models\WarehouseItem;
use App\Models\WarehouseItemStock;
use App\Models\WarehouseItemStockFile;
use App\Models\WarehouseOutItem;
use App\Models\WorkOrder;
use App\Models\WorkOrderItem;
use App\Models\WorkProcessRequirement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class WarehouseItemController extends Controller
{
    public function add_item_in_warehouse()
    {
        $dataIT = ItemType::where('status', '=', 'Active')->where('is_purchase', '=', '1')->get();
        $dataI = Individual::where('type', '=', 'agents')->where('status', '=', 'Active')->get();
        $dataUT = UnitType::where('status', '=', 'Active')->orderBy('unit_type_id')->get();
        $dataW = Warehouse::where('status', '=', 'Active')->orderBy('id')->get();
        $IgstAr = config('global.IGST_RATES');
        $CgstAr = config('global.CGST_RATES');
        $SgstAr = config('global.SGST_RATES');
        $dataGst = GstRate::where('status', '=', 'Active')->get();

        return view('frontend.warehouseitems.add-item-in-warehouse', compact('dataW', 'dataIT', 'dataUT', 'IgstAr', 'CgstAr', 'SgstAr', 'dataGst', 'dataI'));
    }

    public function store_item_in_warehouse(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'warehouseId' => 'required',
            'warehouseCompId' => 'required',
            'vendor_id' => 'required',
            'vendor_name' => 'required',
            'emp_name' => 'required',
            'ind_emp_id' => 'required',
            'invoice_number' => 'required',
            'for_stock_type' => 'required',
            'pur_type_arr.*' => 'required',
            'pro_id_arr.*' => 'required',
            'product_name_arr.*' => 'required',
            'hsn_arr.*' => 'required',
            'qty_arr.*' => 'required',
            'unit_arr.*' => 'required',
            'meter_arr.*' => 'required',
            'invoice_copy_file' => 'required|file|mimes:jpeg,png,jpg,pdf|max:2048',
            'packing_slip_file' => 'required|file|mimes:jpeg,png,jpg,pdf|max:2048',
            'eway_bill_file' => 'required|file|mimes:jpeg,png,jpg,pdf|max:2048',
            'lr_copy_file' => 'required|file|mimes:jpeg,png,jpg,pdf|max:2048',
        ], [
            'warehouseId.required' => 'Please select Warehouse.',
            'warehouseCompId.required' => 'Please select Warehouse Compartment.',
            'vendor_id.required' => 'Please select Vendor.',
            'vendor_name.required' => 'Please select Vendor Name.',
            'emp_name.required' => 'Please select Employee Name.',
            'ind_emp_id.required' => 'Something Error, Employee Id Not found.',
            'invoice_number.required' => 'Please select invoice Number.',
            'for_stock_type.required' => 'Please select stock for Job or Home.',
            'pur_type_arr.*.required' => 'Please provide purchase type for all products.',
            'pro_id_arr.*.required' => 'Please provide product ID for all products.',
            'product_name_arr.*.required' => 'Please provide product name for all products.',
            'hsn_arr.*.required' => 'Please provide HSN for all products.',
            'qty_arr.*.required' => 'Please provide quantity for all products.',
            'unit_arr.*.required' => 'Please provide unit for all products.',
            'meter_arr.*.required' => 'Please provide meter for all products.',
            'invoice_copy_file.mimes' => 'Only JPEG, PNG, JPG, or PDF files are allowed for Invoice Copy.',
            'packing_slip_file.mimes' => 'Only JPEG, PNG, JPG, or PDF files are allowed for Packing Slip.',
            'eway_bill_file.mimes' => 'Only JPEG, PNG, JPG, or PDF files are allowed for E-Way Bill.',
            'lr_copy_file.mimes' => 'Only JPEG, PNG, JPG, or PDF files are allowed for LR Copy.',
        ]);

        if ($validator->fails()) {
            Session::put('message', $validator->errors()->first());
            Session::put('messageClass', 'errorClass');

            return redirect()->back()->withInput();
        }

        $invoiceCopyPath = null;
        $packingSlipPath = null;
        $ewayBillPath = null;
        $lrCopyPath = null;
        $transactionStarted = false;
        $lockName = null;
        $lockAcquired = false;
        $transactionStarted = false;

        try {
            $invoice_number = trim($request->invoice_number);
            $vendor_id = $request->vendor_id;
            $vendor_name = $request->vendor_name;
            $receiving_date = date('Y-m-d', strtotime($request->receiving_date));
            $warehouseId = $request->warehouseId;
            $warehouseCompId = $request->warehouseCompId;
            $emp_name = $request->emp_name;
            $ind_emp_id = $request->ind_emp_id;
            $for_stock_type = $request->for_stock_type;

            $pur_type_arr = $request->pur_type_arr ?? [];
            $pro_id_arr = $request->pro_id_arr ?? [];
            $product_name_arr = $request->product_name_arr ?? [];
            $qty_arr = $request->qty_arr ?? [];
            $unit_arr = $request->unit_arr ?? [];
            $meter_arr = $request->meter_arr ?? [];
            $beam_meter_arr = $request->beam_meter_arr ?? [];
            $remarks_arr = $request->remarks_arr ?? [];
            $dyeing_color_arr = $request->item_dyeing_color_arr ?? [];
            $taka_number_arr = $request->taka_number_arr ?? [];

            $userD = Auth::user();

            if (empty($userD)) {
                throw new \Exception('Login user not found.');
            }

            $IndId = $userD->individual_id;
            $now = now();
            $createdDate = date('Y-m-d');
            $createdDateTime = date('Y-m-d H:i:s');

            $lockRows = [];

            foreach ($product_name_arr as $proidk => $pro_name) {
                $lockRows[] = [
                    'item_id' => $pro_id_arr[$proidk] ?? null,
                    'item_type_id' => $pur_type_arr[$proidk] ?? null,
                    'unit_type_id' => $unit_arr[$proidk] ?? null,
                    'qty' => $qty_arr[$proidk] ?? null,
                    'meter' => $meter_arr[$proidk] ?? null,
                    'taka' => $taka_number_arr[$proidk] ?? null,
                ];
            }

            $lockName = 'wh_stock_in_'.md5($invoice_number.'|'.$vendor_id.'|'.$warehouseId.'|'.$warehouseCompId.'|'.json_encode($lockRows));

            $lockResult = DB::select('SELECT GET_LOCK(?, 0) AS locked_status', [$lockName]);
            $lockAcquired = ! empty($lockResult) && isset($lockResult[0]->locked_status) && (int) $lockResult[0]->locked_status === 1;

            if (! $lockAcquired) {
                Session::put('message', 'This stock entry is already being processed. Please do not click submit button again.');
                Session::put('messageClass', 'errorClass');

                return redirect()->back()->withInput();
            }

            $uploadPath = public_path('uploads/documents');

            if (! file_exists($uploadPath)) {
                mkdir($uploadPath, 0775, true);
            }

            if ($request->hasFile('invoice_copy_file')) {
                $file = $request->file('invoice_copy_file');
                if ($file->isValid()) {
                    $filename = time().'_invoice_copy_'.preg_replace('/\s+/', '_', $file->getClientOriginalName());
                    $file->move($uploadPath, $filename);
                    $invoiceCopyPath = 'uploads/documents/'.$filename;
                }
            }

            if ($request->hasFile('packing_slip_file')) {
                $file = $request->file('packing_slip_file');
                if ($file->isValid()) {
                    $filename = time().'_packing_slip_'.preg_replace('/\s+/', '_', $file->getClientOriginalName());
                    $file->move($uploadPath, $filename);
                    $packingSlipPath = 'uploads/documents/'.$filename;
                }
            }

            if ($request->hasFile('eway_bill_file')) {
                $file = $request->file('eway_bill_file');
                if ($file->isValid()) {
                    $filename = time().'_eway_bill_'.preg_replace('/\s+/', '_', $file->getClientOriginalName());
                    $file->move($uploadPath, $filename);
                    $ewayBillPath = 'uploads/documents/'.$filename;
                }
            }

            if ($request->hasFile('lr_copy_file')) {
                $file = $request->file('lr_copy_file');
                if ($file->isValid()) {
                    $filename = time().'_lr_copy_'.preg_replace('/\s+/', '_', $file->getClientOriginalName());
                    $file->move($uploadPath, $filename);
                    $lrCopyPath = 'uploads/documents/'.$filename;
                }
            }

            $itemIds = array_values(array_unique(array_filter($pro_id_arr)));

            if (empty($itemIds)) {
                throw new \Exception('Product item not found.');
            }

            $validItemIds = Item::whereIn('item_id', $itemIds)->where('status', '=', 'Active')->pluck('item_id')->toArray();
            $validItemIds = array_flip($validItemIds);

            DB::beginTransaction();
            $transactionStarted = true;

            $flag = false;
            $postedRowKeys = [];

            foreach ($product_name_arr as $proidk => $pro_name) {
                $itemId = $pro_id_arr[$proidk] ?? null;
                $itemTypeId = $pur_type_arr[$proidk] ?? null;
                $unitTypeId = $unit_arr[$proidk] ?? null;
                $takaNumber = $taka_number_arr[$proidk] ?? null;
                $dyeingColor = strtoupper(str_replace(' ', '', $dyeing_color_arr[$proidk] ?? ''));
                $ItemQty = $qty_arr[$proidk] ?? 0;
                $meterQty = $meter_arr[$proidk] ?? 0;
                $beamMeterQty = $beam_meter_arr[$proidk] ?? null;
                $itemRemark = $remarks_arr[$proidk] ?? null;

                if (empty($itemId) || empty($itemTypeId) || empty($unitTypeId)) {
                    continue;
                }

                if (! isset($validItemIds[$itemId])) {
                    continue;
                }

                if (empty($ItemQty)) {
                    continue;
                }

                $takaNumberForKey = ! empty($takaNumber) ? $takaNumber : 'NULL';
                $postRowKey = md5($invoice_number.'|'.$vendor_id.'|'.$warehouseId.'|'.$warehouseCompId.'|'.$itemId.'|'.$itemTypeId.'|'.$unitTypeId.'|'.$ItemQty.'|'.$meterQty.'|'.$takaNumberForKey);

                if (isset($postedRowKeys[$postRowKey])) {
                    throw new \Exception('Duplicate product row found in this request. Item ID: '.$itemId.', Taka No: '.$takaNumberForKey);
                }

                $postedRowKeys[$postRowKey] = true;

                $duplicateStockQuery = WarehouseItemStock::where('invoice_number', '=', $invoice_number)->where('vendor_id', '=', $vendor_id)->where('warehouse_id', '=', $warehouseId)->where('ware_comp_id', '=', $warehouseCompId)->where('item_id', '=', $itemId)->where('item_type_id', '=', $itemTypeId)->where('unit_type_id', '=', $unitTypeId)->where('quantity', '=', $ItemQty)->where('insp_quan_size', '=', $meterQty)->where('entry_type', '=', 'IN')->where('status', '=', 'Active');

                if (empty($takaNumber)) {
                    $duplicateStockQuery->where(function ($q) {
                        $q->whereNull('insp_taka_number')->orWhere('insp_taka_number', '=', '');
                    });
                } else {
                    $duplicateStockQuery->where('insp_taka_number', '=', $takaNumber);
                }

                if ($duplicateStockQuery->exists()) {
                    throw new \Exception('This stock has already been entered. Invoice No: '.$invoice_number.', Item ID: '.$itemId.', Taka No: '.$takaNumberForKey);
                }

                $warehouseItem = new WarehouseItem([
                    'warehouse_id' => $warehouseId,
                    'ware_comp_id' => $warehouseCompId,
                    'ind_emp_id' => $ind_emp_id,
                    'vendor_id' => $vendor_id,
                    'vendor_name' => $vendor_name,
                    'emp_name' => $emp_name,
                    'receiver_id' => $IndId,
                    'item_id' => $itemId,
                    'receive_date' => $receiving_date,
                    'invoice_number' => $invoice_number,
                    'pur_item_name' => $product_name_arr[$proidk],
                    'dyeing_color' => (! empty($dyeingColor) && $dyeingColor !== '0') ? $dyeingColor : null,
                    'item_remark' => $itemRemark,
                    'item_type_id' => $itemTypeId,
                    'unit_type_id' => $unitTypeId,
                    'insp_taka_number' => $takaNumber,
                    'item_qty' => ! empty($meterQty) ? $meterQty : $ItemQty,
                    'beam_meter' => $beamMeterQty,
                    'created_at' => $now,
                    'updated_at' => $now,
                    'financial_year' => currentFinancialYear(),
                    'status' => 'Active',
                ]);

                if (! $warehouseItem->save()) {
                    throw new \Exception('Warehouse item could not be saved. Item ID: '.$itemId);
                }

                $lastInsertId = $warehouseItem->getKey();

                $balanceQuery = WarehouseBalanceItem::where('item_id', '=', $warehouseItem->item_id)->where('item_type_id', '=', $warehouseItem->item_type_id)->where('balance_status', '=', '1');

                if (is_null($warehouseItem->dyeing_color)) {
                    $balanceQuery->whereNull('dyeing_color');
                } else {
                    $balanceQuery->where('dyeing_color', '=', $warehouseItem->dyeing_color);
                }

                if (is_null($warehouseItem->coating_type)) {
                    $balanceQuery->whereNull('coating_type');
                } else {
                    $balanceQuery->where('coating_type', '=', $warehouseItem->coating_type);
                }

                if (is_null($warehouseItem->print_job)) {
                    $balanceQuery->whereNull('print_job');
                } else {
                    $balanceQuery->where('print_job', '=', $warehouseItem->print_job);
                }

                if (is_null($warehouseItem->extra_job)) {
                    $balanceQuery->whereNull('extra_job');
                } else {
                    $balanceQuery->where('extra_job', '=', $warehouseItem->extra_job);
                }

                $opItemQty = $balanceQuery->lockForUpdate()->first();

                if (! empty($opItemQty)) {
                    WarehouseBalanceItem::where('id', '=', $opItemQty->id)->update(['balance_status' => '0']);
                }

                $warehouseBalanceItem = WarehouseBalanceItem::create([
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
                    'coating_type' => $warehouseItem->coating_type,
                    'print_job' => $warehouseItem->print_job,
                    'extra_job' => $warehouseItem->extra_job,
                    'created_at' => $now,
                    'updated_at' => $now,
                    'financial_year' => currentFinancialYear(),
                    'balance_status' => '1',
                    'status' => 'Active',
                ]);

                if (empty($warehouseBalanceItem)) {
                    throw new \Exception('Warehouse balance item could not be saved. Item ID: '.$itemId);
                }

                $vendorId = $vendor_id;

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
                $obj2->receiver_id = $IndId;
                $obj2->vendor_id = $vendorId;
                $obj2->for_stock_type = $for_stock_type;
                $obj2->invoice_number = $invoice_number;
                $obj2->item_id = $itemId;
                $obj2->item_type_id = $itemTypeId;
                $obj2->unit_type_id = $unitTypeId;
                // $obj2->packet_number 			= $this->generatePacketNumber($itemTypeId);
                $obj2->insp_taka_number = $takaNumber;
                $obj2->created_at = $createdDateTime;
                $obj2->updated_at = $createdDateTime;
                $obj2->financial_year = currentFinancialYear();
                $obj2->status = 'Active';

                if (! $obj2->save()) {
                    throw new \Exception('Warehouse item stock could not be saved. Item ID: '.$itemId);
                }

                $lastInsertedStockId = $obj2->getKey();

                $stocFArr = new WarehouseItemStockFile;
                $stocFArr->warehouse_item_id = $lastInsertId;
                $stocFArr->wis_id = $lastInsertedStockId;
                $stocFArr->wis_out_id = null;
                $stocFArr->vendor_id = $vendorId;
                $stocFArr->invoice_number = $invoice_number;
                $stocFArr->invoice_copy_file = $invoiceCopyPath;
                $stocFArr->packing_slip_file = $packingSlipPath;
                $stocFArr->eway_bill_file = $ewayBillPath;
                $stocFArr->lr_copy_file = $lrCopyPath;
                $stocFArr->created_at = $createdDateTime;
                $stocFArr->updated_at = $createdDateTime;
                $stocFArr->status = 'Active';

                if (! $stocFArr->save()) {
                    throw new \Exception('Warehouse stock file could not be saved. Item ID: '.$itemId);
                }

                $flag = true;
            }

            if (! $flag) {
                throw new \Exception('No valid product found to save.');
            }

            DB::commit();
            $transactionStarted = false;

            if ($lockAcquired && ! empty($lockName)) {
                DB::select('SELECT RELEASE_LOCK(?) AS released_status', [$lockName]);
                $lockAcquired = false;
            }

            Session::put('message', 'Added successfully.');
            Session::put('messageClass', 'successClass');

            return redirect()->route('add-item-in-warehouse');

        } catch (\Throwable $e) {
            if ($transactionStarted) {
                DB::rollBack();
            }

            if ($lockAcquired && ! empty($lockName)) {
                try {
                    DB::select('SELECT RELEASE_LOCK(?) AS released_status', [$lockName]);
                } catch (\Throwable $lockException) {

                }
            }

            if (! empty($invoiceCopyPath) && file_exists(public_path($invoiceCopyPath))) {
                @unlink(public_path($invoiceCopyPath));
            }

            if (! empty($packingSlipPath) && file_exists(public_path($packingSlipPath))) {
                @unlink(public_path($packingSlipPath));
            }

            if (! empty($ewayBillPath) && file_exists(public_path($ewayBillPath))) {
                @unlink(public_path($ewayBillPath));
            }

            if (! empty($lrCopyPath) && file_exists(public_path($lrCopyPath))) {
                @unlink(public_path($lrCopyPath));
            }

            Session::put('message', 'Something went wrong. '.$e->getMessage());
            Session::put('messageClass', 'errorClass');

            return redirect()->back()->withInput();
        }
    }

    public function add_received_item_in_warehouse(Request $request)
    {
        $vendorName = trim((string) $request->cus_name);
        $vendorId = trim((string) $request->individual_id);
        $invoiceNumber = trim((string) $request->invoice_number);
        $challanNumber = trim((string) $request->challan_number);
        $receivingDate = trim((string) $request->receiving_date);
        $receivingDate = $receivingDate !== '' ? $receivingDate : date('d-m-Y');

        $dataIT = ItemType::where('status', '=', 'Active')->where('is_purchase', '=', '1')->get();
        $dataI = Individual::where('type', '=', 'agents')->where('status', '=', 'Active')->get();
        $dataUT = UnitType::where('status', '=', 'Active')->orderByDesc('unit_type_id')->get();
        $dataW = Warehouse::where('status', '=', 'Active')->orderByDesc('id')->get();
        $IgstAr = config('global.IGST_RATES');
        $CgstAr = config('global.CGST_RATES');
        $SgstAr = config('global.SGST_RATES');
        $dataGst = GstRate::where('status', '=', 'Active')->get();

        $dataPO = collect();

        if ($vendorId !== '' || $vendorName !== '') {
            $query = PurchaseOrderItem::where('is_deleted', false)
                ->where('is_item_received_in_warehouse', '0')
                ->where('status', 'Active')
                ->whereHas('PurchaseOrder', function ($poQuery) use ($vendorId, $vendorName) {
                    $poQuery->where('is_deleted', 'No')
                        ->where('status', 'Active')
                        ->where('is_item_received_in_warehouse', 'No');

                    if ($vendorId !== '') {
                        $poQuery->where('vendor_id', $vendorId);
                    } elseif ($vendorName !== '') {
                        $poQuery->whereHas('vendor', function ($vendorQuery) use ($vendorName) {
                            $vendorQuery->where('name', 'like', '%'.$vendorName.'%')
                                ->where('status', 'Active');
                        });
                    }
                })
                ->with(['PurchaseOrder.vendor', 'Item', 'ItemType'])
                ->orderByDesc('id');

            $dataPO = $query->get();
        }

        return view('frontend.warehouseitems.add-received-item-in-warehouse', compact('dataW', 'dataIT', 'dataUT', 'IgstAr', 'CgstAr', 'SgstAr', 'dataGst', 'dataI', 'invoiceNumber', 'vendorName', 'vendorId', 'receivingDate', 'challanNumber', 'dataPO'));
    }

    public function storeReceivedItemsFromInvoice(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'purchase_order_item_id' => 'required|array|min:1',
            'purchase_order_item_id.*' => 'required|integer',
            'invoice_mtr.*' => 'nullable|numeric|min:0.01',
            'pcs.*' => 'nullable|integer|min:1',
            'warehouseId.*' => 'nullable|integer',
            'warehouseCompId.*' => 'nullable|integer',
            'receiving_date' => 'required|date_format:d-m-Y',
            'cus_name' => 'required|string|max:255',
            'individual_id' => 'required|integer',
            'invoice_number' => 'required|string|max:50',
            'challan_number' => 'nullable|string|max:50',
            'invoice_copy_file' => 'required|file|mimes:jpeg,png,jpg,pdf|max:2048',
            'packing_slip_file' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:2048',
            'eway_bill_file' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:2048',
            'lr_copy_file' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:2048',
        ], [
            'purchase_order_item_id.required' => 'Please select at least one purchase order item.',
            'invoice_mtr.*.numeric' => 'Received meter must be a number for each item.',
            'invoice_mtr.*.min' => 'Received meter must be greater than zero for each selected item.',
            'pcs.*.integer' => 'Each pcs value must be an integer.',
            'pcs.*.min' => 'Each pcs value must be at least 1.',
            'receiving_date.date_format' => 'Receiving date must be in the format DD-MM-YYYY.',
            'invoice_copy_file.mimes' => 'Only JPEG, PNG, JPG, or PDF files are allowed for Invoice Copy.',
            'packing_slip_file.mimes' => 'Only JPEG, PNG, JPG, or PDF files are allowed for Packing Slip.',
            'eway_bill_file.mimes' => 'Only JPEG, PNG, JPG, or PDF files are allowed for E-Way Bill.',
            'lr_copy_file.mimes' => 'Only JPEG, PNG, JPG, or PDF files are allowed for LR Copy.',
        ]);

        if ($validator->fails()) {
            Session::put('message', $validator->errors()->first());
            Session::put('messageClass', 'errorClass');

            return redirect()->back()->withInput();
        }

        $selectedItemIds = array_map('intval', $request->purchase_order_item_id ?? []);
        $vendorName = trim((string) $request->cus_name);
        $vendorId = trim((string) $request->individual_id);
        $invoiceNumber = trim((string) $request->invoice_number);
        $challanNumber = trim((string) $request->challan_number);
        $receivingDate = date('Y-m-d', strtotime($request->receiving_date));
        $user = Auth::user();
        $individualId = $user->individual_id ?? null;
        $employeeName = $user->name ?? '';
        $now = now();
        $createdDateTime = date('Y-m-d H:i:s');

        $invoiceCopyPath = null;
        $packingSlipPath = null;
        $ewayBillPath = null;
        $lrCopyPath = null;
        $transactionStarted = false;

        try {
            $uploadPath = public_path('uploads/documents');
            if (! file_exists($uploadPath)) {
                mkdir($uploadPath, 0775, true);
            }

            $filePathMap = [
                'invoice_copy_file' => 'invoiceCopyPath',
                'packing_slip_file' => 'packingSlipPath',
                'eway_bill_file' => 'ewayBillPath',
                'lr_copy_file' => 'lrCopyPath',
            ];

            foreach ($filePathMap as $fileKey => $pathVariable) {
                if ($request->hasFile($fileKey) && $request->file($fileKey)->isValid()) {
                    $file = $request->file($fileKey);
                    $filename = time().'_'.$fileKey.'_'.preg_replace('/\s+/', '_', $file->getClientOriginalName());
                    $file->move($uploadPath, $filename);
                    ${$pathVariable} = 'uploads/documents/'.$filename;
                }
            }

            DB::beginTransaction();
            $transactionStarted = true;
            $isSuccessful = false;
            $purchaseEntries = [];

            foreach ($selectedItemIds as $purchaseOrderItemId) {
                $index = array_search($purchaseOrderItemId, $request->purchase_order_item_id_arr ?? []);
                if ($index === false) {
                    continue;
                }

                $meterQty = (float) ($request->invoice_mtr[$index] ?? 0);
                $pcsQuantity = (float) ($request->pcs[$index] ?? 0);
                $warehouseId = $request->warehouseId[$index] ?? null;
                $warehouseCompId = $request->warehouseCompId[$index] ?? null;

                if ($meterQty <= 0 || $pcsQuantity <= 0 || empty($warehouseId) || empty($warehouseCompId)) {
                    throw new \Exception('Please enter PCS, received meter, warehouse and compartment for all selected items.');
                }

                $purchaseOrderItem = PurchaseOrderItem::where('id', $purchaseOrderItemId)
                    ->where('is_deleted', false)
                    ->where('status', 'Active')
                    ->with(['Item', 'ItemType', 'PurchaseOrder'])
                    ->lockForUpdate()
                    ->first();

                if (! $purchaseOrderItem || ! $purchaseOrderItem->PurchaseOrder) {
                    continue;
                }

                $purchaseOrderId = $purchaseOrderItem->purchase_id;
                $itemTypeId = $purchaseOrderItem->item_type_id;
                $unitTypeId = $purchaseOrderItem->Item->unit_type_id ?? null;
                $itemId = $purchaseOrderItem->item_id;
                $itemName = $purchaseOrderItem->name ?: ($purchaseOrderItem->Item->item_name ?? '');
                $colorName = $purchaseOrderItem->colour_name;
                $hsnCode = $purchaseOrderItem->hsn;
                $remainingQuantity = max(0, (float) $purchaseOrderItem->quantity - (float) $purchaseOrderItem->received_quantity);

                if ($remainingQuantity > 0 && $meterQty > $remainingQuantity) {
                    throw new \Exception('Received meter cannot be greater than balance meter for '.$itemName.'.');
                }

                $stockSplits = [];
                $takaDetailsRaw = $request->taka_details[$index] ?? '';
                if ($takaDetailsRaw !== '') {
                    $takaDetails = json_decode($takaDetailsRaw, true);
                    if (! is_array($takaDetails)) {
                        throw new \Exception('Invalid taka details found for '.$itemName.'.');
                    }

                    $totalSplitMeter = 0;
                    foreach ($takaDetails as $detail) {
                        $splitMeter = (float) ($detail['meter'] ?? 0);
                        if ($splitMeter <= 0) {
                            continue;
                        }

                        $totalSplitMeter += $splitMeter;
                        $stockSplits[] = [
                            'meter' => $splitMeter,
                            'taka_number' => trim((string) ($detail['taka_number'] ?? '')),
                            'remarks' => trim((string) ($detail['remarks'] ?? '')),
                            'quantity' => 1,
                        ];
                    }

                    if (! empty($stockSplits) && abs($totalSplitMeter - $meterQty) > 0.01) {
                        throw new \Exception('Set Taka total meter must match received meter for '.$itemName.'.');
                    }
                }

                if (empty($stockSplits)) {
                    $stockSplits[] = [
                        'meter' => $meterQty,
                        'taka_number' => '',
                        'remarks' => '',
                        'quantity' => $pcsQuantity,
                    ];
                }

                $warehouseItemData = [
                    'warehouse_id' => $request->warehouseId[$index],
                    'ware_comp_id' => $request->warehouseCompId[$index],
                    'emp_name' => $employeeName,
                    'receiver_id' => $individualId,
                    'vendor_id' => $vendorId,
                    'vendor_name' => $vendorName,
                    'item_id' => $itemId,
                    'receive_date' => $receivingDate,
                    'purchase_id' => $purchaseOrderId,
                    'pur_item_id' => $purchaseOrderItemId,
                    'invoice_number' => $invoiceNumber,
                    'challan_number' => $challanNumber,
                    'pur_item_name' => $itemName,
                    'dyeing_color' => $colorName ?? null,
                    'item_type_id' => $itemTypeId,
                    'unit_type_id' => $unitTypeId,
                    'item_qty' => $meterQty,
                    'pcs' => $pcsQuantity,
                    'created_at' => $createdDateTime,
                    'updated_at' => $createdDateTime,
                    'financial_year' => currentFinancialYear(),
                    'status' => 'Active',
                ];
                $warehouseItem = WarehouseItem::create($warehouseItemData);

                if (! isset($purchaseEntries[$purchaseOrderId])) {
                    $purchaseEntries[$purchaseOrderId] = Purchase::create([
                        'purchase_order_id' => $purchaseOrderId,
                        'vendor_id' => $vendorId,
                        'vendor_name' => $vendorName,
                        'invoice_number' => $invoiceNumber,
                        'challan_number' => $challanNumber,
                        'receiving_date' => $receivingDate,
                        'receiver_id' => $individualId,
                        'receiver_name' => $employeeName,
                        'total_qty' => 0,
                        'total_meter' => 0,
                        'invoice_copy_file' => $invoiceCopyPath,
                        'packing_slip_file' => $packingSlipPath,
                        'eway_bill_file' => $ewayBillPath,
                        'lr_copy_file' => $lrCopyPath,
                        'financial_year' => currentFinancialYear(),
                        'created_by' => Auth::id(),
                        'modified_by' => Auth::id(),
                        'created_at' => $createdDateTime,
                        'updated_at' => $createdDateTime,
                        'status' => 'Active',
                    ]);
                }

                $balanceItemQuery = WarehouseBalanceItem::where('item_id', $warehouseItem->item_id)
                    ->where('item_type_id', $warehouseItem->item_type_id)
                    ->where('unit_type_id', $warehouseItem->unit_type_id)
                    ->where('dyeing_color', $warehouseItem->dyeing_color)
                    ->where('balance_status', 1)
                    ->where('status', 'Active');
                $balanceItem = $balanceItemQuery->lockForUpdate()->first();

                if ($balanceItem) {
                    $balanceItem->update(['balance_status' => 0]);
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
                    'op_item_qty' => $balanceItem ? $balanceItem->item_qty : 0,
                    'in_item_qty' => $warehouseItem->item_qty,
                    'out_item_qty' => 0,
                    'item_qty' => $balanceItem ? ($balanceItem->item_qty + $warehouseItem->item_qty) : $warehouseItem->item_qty,
                    'grey_quality' => $warehouseItem->grey_quality,
                    'dyeing_color' => $warehouseItem->dyeing_color,
                    'coating_type' => $warehouseItem->coating_type,
                    'print_job' => $warehouseItem->print_job,
                    'extra_job' => $warehouseItem->extra_job,
                    'created_at' => $createdDateTime,
                    'updated_at' => $createdDateTime,
                    'financial_year' => currentFinancialYear(),
                    'balance_status' => '1',
                    'status' => 'Active',
                ]);

                foreach ($stockSplits as $stockSplit) {
                    $newStock = WarehouseItemStock::create([
                        'warehouse_item_id' => $warehouseItem->id,
                        'warehouse_id' => $warehouseItem->warehouse_id,
                        'ware_comp_id' => $warehouseItem->ware_comp_id,
                        'item_remark' => $stockSplit['remarks'] ?: $warehouseItem->item_remark,
                        'dyeing_color' => $warehouseItem->dyeing_color,
                        'quantity' => $stockSplit['quantity'],
                        'insp_quan_size' => $stockSplit['meter'],
                        'insp_allot_quan_size' => 0,
                        'insp_bal_quan_size' => $stockSplit['meter'],
                        'quan_size_unit' => 'Meter',
                        'entry_type' => 'IN',
                        'vendor_id' => $vendorId,
                        'receive_date' => $receivingDate,
                        'receiver_id' => $individualId,
                        'invoice_number' => $invoiceNumber,
                        'insp_taka_number' => $stockSplit['taka_number'],
                        'item_id' => $itemId,
                        'item_type_id' => $itemTypeId,
                        'unit_type_id' => $unitTypeId,
                        'packet_number' => $this->generatePacketNumber($itemTypeId),
                        'created_at' => $createdDateTime,
                        'updated_at' => $createdDateTime,
                        'financial_year' => currentFinancialYear(),
                        'status' => 'Active',
                    ]);
                    $lastInsertedStockId = $newStock->id;

                    $stocFArr = new WarehouseItemStockFile;
                    $stocFArr->warehouse_item_id = $warehouseItem->id;
                    $stocFArr->wis_id = $lastInsertedStockId;
                    $stocFArr->wis_out_id = null;
                    $stocFArr->vendor_id = $vendorId;
                    $stocFArr->invoice_number = $invoiceNumber;
                    $stocFArr->invoice_copy_file = $invoiceCopyPath;
                    $stocFArr->packing_slip_file = $packingSlipPath;
                    $stocFArr->eway_bill_file = $ewayBillPath;
                    $stocFArr->lr_copy_file = $lrCopyPath;
                    $stocFArr->challan_num = $challanNumber;
                    $stocFArr->created_at = $createdDateTime;
                    $stocFArr->updated_at = $createdDateTime;
                    $stocFArr->financial_year = currentFinancialYear();
                    $stocFArr->status = 'Active';
                    $stocFArr->save();

                    PurchaseItem::create([
                        'purchase_id' => $purchaseEntries[$purchaseOrderId]->id,
                        'purchase_order_id' => $purchaseOrderId,
                        'purchase_order_item_id' => $purchaseOrderItemId,
                        'warehouse_item_id' => $warehouseItem->id,
                        'warehouse_item_stock_id' => $lastInsertedStockId,
                        'item_id' => $itemId,
                        'item_type_id' => $itemTypeId,
                        'unit_type_id' => $unitTypeId,
                        'item_name' => $itemName,
                        'dyeing_color' => $colorName,
                        'hsn' => $hsnCode,
                        'qty' => $stockSplit['quantity'],
                        'meter' => $stockSplit['meter'],
                        'taka_number' => $stockSplit['taka_number'],
                        'remarks' => $stockSplit['remarks'],
                        'warehouse_id' => $warehouseItem->warehouse_id,
                        'ware_comp_id' => $warehouseItem->ware_comp_id,
                        'receiving_date' => $receivingDate,
                        'financial_year' => currentFinancialYear(),
                        'created_by' => Auth::id(),
                        'modified_by' => Auth::id(),
                        'created_at' => $createdDateTime,
                        'updated_at' => $createdDateTime,
                        'status' => 'Active',
                    ]);

                    $purchaseEntries[$purchaseOrderId]->total_qty = (float) $purchaseEntries[$purchaseOrderId]->total_qty + (float) $stockSplit['quantity'];
                    $purchaseEntries[$purchaseOrderId]->total_meter = (float) $purchaseEntries[$purchaseOrderId]->total_meter + (float) $stockSplit['meter'];
                }

                $totalReceived = $purchaseOrderItem->received_quantity + $meterQty;
                $balanceReceived = $purchaseOrderItem->quantity - $totalReceived;

                $purchaseOrderItem->update([
                    'received_quantity' => $totalReceived,
                    'balance_quantity' => $balanceReceived,
                    'is_item_received_in_warehouse' => $balanceReceived <= 0 ? '1' : '0',
                    'receipt_status' => $balanceReceived <= 0
                        ? InventoryReceiptStatus::Received->value
                        : InventoryReceiptStatus::PartiallyReceived->value,
                ]);

                $allItemsReceived = ! PurchaseOrderItem::where('purchase_id', $purchaseOrderId)
                    ->whereColumn('received_quantity', '<', 'quantity')
                    ->where('status', 'Active')
                    ->where('is_deleted', false)
                    ->exists();
                if ($allItemsReceived) {
                    PurchaseOrder::where('id', $purchaseOrderId)->update([
                        'is_item_received_in_warehouse' => 'Yes',
                        'is_all_item_received' => 'Yes',
                        'document_status' => PurchaseOrderDocumentStatus::Received->value,
                    ]);
                } else {
                    PurchaseOrder::where('id', $purchaseOrderId)->update([
                        'is_item_received_in_warehouse' => 'Yes',
                        'document_status' => PurchaseOrderDocumentStatus::PartiallyReceived->value,
                    ]);
                }

                $isSuccessful = true;
            }

            if (! $isSuccessful) {
                throw new \Exception('No valid purchase order item found to receive.');
            }

            foreach ($purchaseEntries as $purchaseEntry) {
                $purchaseEntry->updated_at = $createdDateTime;
                $purchaseEntry->save();
            }

            DB::commit();
            $transactionStarted = false;
            Session::put('message', 'Stock update in warehouse successfully.');
            Session::put('messageClass', 'successClass');

            return redirect()->route('add-received-item-in-warehouse');
        } catch (\Throwable $e) {
            if ($transactionStarted) {
                DB::rollBack();
            }

            foreach ([$invoiceCopyPath, $packingSlipPath, $ewayBillPath, $lrCopyPath] as $path) {
                if (! empty($path) && file_exists(public_path($path))) {
                    @unlink(public_path($path));
                }
            }

            Session::put('message', 'Something went wrong. '.$e->getMessage());
            Session::put('messageClass', 'errorClass');

            return redirect()->back()->withInput();
        }
    }

    // //////////////////Report///////////////////////

    public function index(Request $request)
    {

        $qsearch = trim((string) $request->qsearch);
        $item_type = trim((string) $request->item_type);
        $colorSearch = trim((string) $request->colorSearch);
        $coatingSearch = trim((string) $request->coatingSearch);
        $fromDate = $request->from_date;
        $toDate = $request->to_date;
        $sbtSearch = $request->sbtSearch;
        $dataIT = ItemType::where('status', '=', 'Active')->get();

        $query = WarehouseBalanceItem::where('status', '=', 'Active')
            ->where('balance_status', '=', 1)
            ->where('item_qty', '>', '0')
            ->with('WarehouseItem', 'WarehouseOutItem', 'Warehouse', 'WarehouseCompartment', 'User', 'ReceiverIndividual', 'Item', 'ItemType')
            ->orderByDesc('id');

        if (! empty($qsearch)) {
            $itemIds = Item::where(DB::raw("CONCAT(COALESCE(item_name, ''), ' ', COALESCE(internal_item_name, ''))"), 'LIKE', '%'.$qsearch.'%')->where('status', '=', 'Active')->pluck('item_id')->toArray();
            $query->whereIn('item_id', $itemIds);
        }
        if (! empty($item_type)) {
            $itemType = explode(',', $item_type);
            $query->whereIn('item_type_id', $itemType);
        }

        if (! empty($colorSearch)) {
            $query->where('dyeing_color', 'LIKE', '%'.$colorSearch.'%');
        }

        if (! empty($coatingSearch)) {
            $query->where('coating_type', 'LIKE', '%'.$coatingSearch.'%');
        }

        if (! empty($fromDate) && ! empty($toDate)) {
            $fromDate = date('Y-m-d', strtotime($request->from_date));
            $toDate = date('Y-m-d', strtotime($request->to_date));
            $query->where('receive_date', '>=', $fromDate)->where('receive_date', '<=', $toDate);
        }
        if ($sbtSearch == 'ExportToExcel') {
            try {
                $dataWI = $query->get();

                return Excel::download(new WarehouseBalanceStockListing($dataWI), 'warehouse_balance_stock_listing.xlsx');
            } catch (\Exception $e) {
                \Log::error('Exception: '.$e->getMessage());

                return response('Error generating Excel', 500);
            }
        }

        $dataWI = $query->paginate(20)->appends(request()->except('_token'));

        return view('frontend.warehouseitems.show', compact('dataWI', 'qsearch', 'fromDate', 'toDate', 'item_type', 'colorSearch', 'coatingSearch', 'dataIT'));
    }

    public function stock_details_listing($Id, Request $request)
    {

        $wbId = dec($Id);
        abort_unless(ctype_digit((string) $wbId), 404);

        $dataWB = WarehouseBalanceItem::findOrFail($wbId);
        $itemId = $dataWB->item_id;
        $itemTypeId = $dataWB->item_type_id;
        $dyeing_color = $dataWB->dyeing_color;
        $coating_type = $dataWB->coating_type;
        $qsearch = trim(request('qsearch'));
        $stockType = trim(request('stockType'));
        $workSaleOrd = trim(request('work_sale_ord'));
        $item_type = trim(request('item_type'));
        $warehouseId = trim(request('warehouseId'));
        $warehCompId = trim(request('warehouseCompId'));
        $sbtSearch = $request->sbtSearch;
        $fromDate = request('from_date');
        $toDate = request('to_date');

        $dataIT = ItemType::where('status', '=', 'Active')->get();
        $dataW = Warehouse::where('status', '=', 'Active')->orderByDesc('id')->get();

        $query = WarehouseItemStock::where('item_id', '=', $itemId)
            ->where('item_type_id', '=', $itemTypeId)
            ->where('status', '=', 'Active')// ->whereColumn('insp_quan_size', '>', 'insp_allot_quan_size')
            ->with('WarehouseItem', 'ReceiverIndividual', 'User', 'ItemType', 'Item', 'Warehouse', 'WarehouseCompartment', 'StockFile', 'WarehouseItem.Vendor', 'WarehouseItem.Warehouse', 'WarehouseItem.WarehouseCompartment')
            ->orderByDesc('id');

        if (is_null($dyeing_color)) {
            $query->whereNull('dyeing_color');
        } else {
            $query->where('dyeing_color', '=', $dyeing_color);
        }

        if (is_null($coating_type)) {
            $query->whereNull('coating_type');
        } else {
            $query->where('coating_type', '=', $coating_type);
        }

        $query->when($stockType == 'stockin', function ($query) {
            return $query->where('is_allotted_stock', '=', 'No');
        })
            ->when($stockType == 'stockout', function ($query) {
                return $query->where('is_allotted_stock', '=', 'Yes');
            });
        /*
          $sql = $query->toSql();
            $bindings = $query->getBindings();
            $sqlWithValues = vsprintf(str_replace('?', "'%s'", $sql), $bindings);
            dd($sqlWithValues); exit;
         */

        $query->when(! empty($qsearch), function ($query) use ($qsearch) {
            $itemIds = Item::where(DB::raw("CONCAT(item_name, ' ', internal_item_name)"), 'LIKE', '%'.$qsearch.'%')
                ->where('status', '=', 'Active')
                ->pluck('item_id')
                ->toArray();

            return $query->whereIn('item_id', $itemIds);
        });

        $query->when(! empty($workSaleOrd), function ($query) use ($workSaleOrd) {
            $ordNumSearchArray = explode(',', $workSaleOrd);
            $saleOrderIds = WorkOrderItem::whereIn('work_order_id', $ordNumSearchArray)->pluck('work_order_id');

            return $query->whereIn('work_order_id', $saleOrderIds);
        });

        $query->when(! empty($item_type), function ($query) use ($item_type) {
            $itemType = explode(',', $item_type);

            return $query->whereIn('item_type_id', $itemType);
        });

        $query->when(! empty($warehouseId), function ($query) use ($warehouseId) {
            return $query->where('warehouse_id', '=', $warehouseId);
        });

        $query->when(! empty($warehCompId), function ($query) use ($warehCompId) {
            return $query->where('ware_comp_id', '=', $warehCompId);
        });

        $query->when(! empty($fromDate) && ! empty($toDate), function ($query) use ($fromDate, $toDate) {
            $fromDate = date('Y-m-d', strtotime($fromDate));
            $toDate = date('Y-m-d', strtotime($toDate));

            return $query->where('receive_date', '>=', $fromDate)->where('receive_date', '<=', $toDate);
        });

        if ($sbtSearch == 'ExportToExcel') {
            try {
                $dataWI = $query->get();

                return Excel::download(new WarehouseStockDetailsListing($dataWI), 'warehouse_stock_details_listing.xlsx');
            } catch (\Exception $e) {
                \Log::error('Exception: '.$e->getMessage());

                return response('Error generating Excel', 500);
            }
        }
        $dataWI = $query->paginate(100)->appends(request()->except('_token'));

        return view('frontend.warehouseitems.show_stock_details_listing', compact('dataWI', 'dataW', 'stockType', 'qsearch', 'fromDate', 'toDate', 'item_type', 'warehouseId', 'warehCompId', 'workSaleOrd', 'dataIT', 'wbId'));
    }

    public function stock_details_inline($Id, Request $request)
    {
        $wbId = dec($Id);

        if (! ctype_digit((string) $wbId)) {
            return response('Invalid warehouse balance item.', 422);
        }

        $dataWB = WarehouseBalanceItem::find($wbId);
        if (empty($dataWB)) {
            return response('Warehouse balance item not found.', 404);
        }

        $query = WarehouseItemStock::where('item_id', '=', $dataWB->item_id)
            ->where('item_type_id', '=', $dataWB->item_type_id)
            ->where('status', '=', 'Active')
            ->with('WarehouseItem', 'ReceiverIndividual', 'ItemType', 'Item', 'Warehouse', 'WarehouseCompartment', 'WarehouseItem.Vendor', 'WarehouseItem.Warehouse', 'WarehouseItem.WarehouseCompartment')
            ->orderByDesc('id');

        if (is_null($dataWB->dyeing_color)) {
            $query->whereNull('dyeing_color');
        } else {
            $query->where('dyeing_color', '=', $dataWB->dyeing_color);
        }

        if (is_null($dataWB->coating_type)) {
            $query->whereNull('coating_type');
        } else {
            $query->where('coating_type', '=', $dataWB->coating_type);
        }

        $dataWI = $query->get();
        $fullDetailsUrl = route('show-stock-details-listing', ['id' => enc($dataWB->id)]);

        return view('frontend.warehouseitems.partials.stock-details-inline', compact('dataWI', 'fullDetailsUrl'));
    }

    public function warehouse_stock_report(Request $request)
    {
        $qsearch = trim((string) $request->get('qsearch', ''));
        $itemId = trim((string) $request->get('itemId', ''));
        $itemType = trim((string) $request->get('item_type', ''));
        $stockType = trim((string) $request->get('stockType', ''));
        $forStockType = trim((string) $request->get('for_stock_type', ''));
        $colorSearch = trim((string) $request->get('colorSearch', ''));
        $lotNumSearch = trim((string) $request->get('LotNumSearch', ''));
        $fromDate = trim((string) $request->get('from_date', ''));
        $toDate = trim((string) $request->get('to_date', ''));
        $allotFromDate = trim((string) $request->get('allot_from_date', ''));
        $allotToDate = trim((string) $request->get('allot_to_date', ''));
        $vendorName = trim((string) $request->get('vendor_name', ''));
        $vendorId = trim((string) $request->get('vendor_id', ''));

        $dataIT = ItemType::select('item_type_id', 'item_type_name')->where('status', 'Active')->orderBy('item_type_id')->get();

        $query = WarehouseItemStock::with([
            'Item:item_id,item_name',
            'ItemType:item_type_id,item_type_name',
            'UnitType:unit_type_id,unit_type_name',
            'Vendor:id,name',
            'Warehouse:id,warehouse_name',
            'WarehouseCompartment:id,compartment_name',
            'WarehouseOutItem:id,wis_id,created_at,status',
            'StockFile:id,wis_id,invoice_copy_file,packing_slip_file,eway_bill_file,lr_copy_file',
        ])
            ->where('status', 'Active')
            ->where('entry_type', 'IN')
            ->where('insp_quan_size', '!=', 0)
            ->orderByDesc('id');

        if ($forStockType !== '') {
            $query->where('for_stock_type', $forStockType);
        }

        if ($stockType === 'stockin') {
            $query->where('is_allotted_stock', 'No')->where('insp_bal_quan_size', '>', 0);
        } elseif ($stockType === 'stockout') {
            $query->where('is_allotted_stock', 'Yes');
        } elseif ($stockType === 'rejected') {
            $query->whereNotNull('fabric_fault_reason_id');
        }

        if ($itemId !== '') {
            $query->where('item_id', $itemId);
        } elseif ($qsearch !== '') {
            $query->whereIn('item_id', function ($subQuery) use ($qsearch) {
                $subQuery->select('item_id')
                    ->from('items')
                    ->whereRaw("CONCAT(COALESCE(item_name, ''), COALESCE(hsncode, ''), COALESCE(internal_item_name, ''), COALESCE(item_code, '')) LIKE ?", ['%'.$qsearch.'%'])
                    ->where('status', 'Active');
            });
        }

        if ($vendorId !== '') {
            $query->where('vendor_id', $vendorId);
        } elseif ($vendorName !== '') {
            $query->whereIn('vendor_id', function ($subQuery) use ($vendorName) {
                $subQuery->select('id')
                    ->from('individuals')
                    ->whereRaw("COALESCE(name, '') LIKE ?", ['%'.$vendorName.'%'])
                    ->where('status', 'Active');
            });
        }

        if ($colorSearch !== '') {
            $query->where('dyeing_color', $colorSearch);
        }

        if ($lotNumSearch !== '') {
            $query->where('dyeing_lot_number', $lotNumSearch);
        }

        if ($itemType !== '') {
            $query->whereIn('item_type_id', array_filter(explode(',', $itemType)));
        }

        if ($fromDate !== '' && $toDate !== '') {
            $query->whereBetween('receive_date', [
                date('Y-m-d', strtotime($fromDate)),
                date('Y-m-d', strtotime($toDate)),
            ]);
        }

        if ($allotFromDate !== '' && $allotToDate !== '') {
            $query->whereHas('WarehouseOutItem', function ($q) use ($allotFromDate, $allotToDate) {
                $q->whereBetween('created_at', [
                    date('Y-m-d 00:00:00', strtotime($allotFromDate)),
                    date('Y-m-d 23:59:59', strtotime($allotToDate)),
                ])->where('status', 'Active');
            });
        }

        if ($request->sbtSearch == 'ExportToExcel') {
            try {
                $dataWI = $query->get();

                return Excel::download(new WarehouseStockReportExport($dataWI), 'warehouse_stock_report_'.date('YmdHis').'.xlsx');
            } catch (\Exception $e) {
                \Log::error('Warehouse Stock Export Error: '.$e->getMessage());

                return response('Error generating Excel', 500);
            }
        }

        if ($request->sbtSearch == 'ExcelToBarcode') {
            try {
                $dataWI = $query->get();

                return view('frontend.warehouseitems.show-warehouse-stock-report-barcode', compact(
                    'dataWI',
                    'qsearch',
                    'itemId',
                    'dataIT',
                    'itemType',
                    'stockType',
                    'lotNumSearch',
                    'colorSearch',
                    'fromDate',
                    'toDate',
                    'allotFromDate',
                    'allotToDate',
                    'vendorName',
                    'vendorId',
                    'forStockType'
                ));
            } catch (\Exception $e) {
                \Log::error('Warehouse Stock Barcode Error: '.$e->getMessage());

                return response('Error generating barcode', 500);
            }
        }

        $totalStock = (clone $query)->sum('insp_quan_size');
        $inspStockAllot = (clone $query)->sum('insp_allot_quan_size');
        $totalBalStock = (clone $query)->sum('insp_bal_quan_size');
        $dataWI = $query->paginate(100)->appends($request->except('_token'));

        return view('frontend.warehouseitems.show-warehouse-stock-report', compact(
            'dataWI',
            'qsearch',
            'itemId',
            'dataIT',
            'itemType',
            'stockType',
            'lotNumSearch',
            'colorSearch',
            'fromDate',
            'toDate',
            'allotFromDate',
            'allotToDate',
            'totalStock',
            'inspStockAllot',
            'totalBalStock',
            'forStockType',
            'vendorName',
            'vendorId'
        ));
    }

    public function warehouse_balance_report(Request $request)
    {
        $qsearch = trim((string) $request->get('qsearch', ''));
        $itemId = trim((string) $request->get('itemId', ''));
        $itemType = trim((string) $request->get('item_type', ''));
        $balanceStatus = trim((string) $request->get('balance_status', ''));
        if ($balanceStatus === '1') {
            $balanceStatus = 'current';
        } elseif ($balanceStatus === '0') {
            $balanceStatus = 'history';
        }
        $colorSearch = trim((string) $request->get('colorSearch', ''));
        $fromDate = trim((string) $request->get('from_date', ''));
        $toDate = trim((string) $request->get('to_date', ''));

        $dataIT = ItemType::select('item_type_id', 'item_type_name')->where('status', 'Active')->orderBy('item_type_id')->get();

        $query = WarehouseBalanceItem::with([
            'Warehouse:id,warehouse_name',
            'WarehouseCompartment:id,compartment_name',
            'Item:item_id,item_name,item_code,internal_item_name',
            'ItemType:item_type_id,item_type_name',
            'UnitType:unit_type_id,unit_type_name',
            'ReceiverIndividual:id,name',
            'WarehouseItem:id,invoice_number,insp_taka_number,dyeing_lot_number',
        ])
            ->where('status', 'Active');

        if ($itemId !== '') {
            $query->where('item_id', $itemId);
        } elseif ($qsearch !== '') {
            $query->whereIn('item_id', function ($subQuery) use ($qsearch) {
                $subQuery->select('item_id')
                    ->from('items')
                    ->whereRaw("CONCAT(COALESCE(item_name, ''), COALESCE(hsncode, ''), COALESCE(internal_item_name, ''), COALESCE(item_code, '')) LIKE ?", ['%'.$qsearch.'%'])
                    ->where('status', 'Active');
            });
        }

        if ($itemType !== '') {
            $query->where('item_type_id', $itemType);
        }

        if ($balanceStatus === 'current') {
            $query->where('balance_status', 1);
        }

        if ($colorSearch !== '') {
            $query->where('dyeing_color', $colorSearch);
        }

        if ($fromDate !== '' && $toDate !== '') {
            $query->whereBetween('receive_date', [
                date('Y-m-d', strtotime($fromDate)),
                date('Y-m-d', strtotime($toDate)),
            ]);
        }

        if ($balanceStatus === 'history') {
            $query->orderBy('item_id')
                ->orderBy('item_type_id')
                ->orderBy('receive_date')
                ->orderBy('id');
        } else {
            $query->orderByDesc('id');
        }

        $totalOpeningQty = (clone $query)->sum('op_item_qty');
        $totalInQty = (clone $query)->sum('in_item_qty');
        $totalOutQty = (clone $query)->sum('out_item_qty');
        $totalBalanceQty = (clone $query)->sum('item_qty');
        $dataWBI = $query->paginate(100)->appends($request->except('_token'));

        return view('frontend.warehouseitems.show-warehouse-balance-report', compact(
            'dataWBI',
            'qsearch',
            'itemId',
            'dataIT',
            'itemType',
            'balanceStatus',
            'colorSearch',
            'fromDate',
            'toDate',
            'totalOpeningQty',
            'totalInQty',
            'totalOutQty',
            'totalBalanceQty'
        ));
    }

    public function warehouse_stock_document($id)
    {
        $stockId = dec($id);

        if (! ctype_digit((string) $stockId)) {
            return response()->json(['status' => 'error', 'message' => 'Invalid stock id.'], 422);
        }

        $file = WarehouseItemStockFile::where('wis_id', $stockId)->where('status', 'Active')->first();

        if (empty($file)) {
            return response()->json(['status' => 'error', 'message' => 'No document found for this stock.'], 404);
        }

        $assetUrl = function ($path) {
            return ! empty($path) ? asset($path) : '';
        };

        return response()->json([
            'status' => 'success',
            'data' => [
                'invoice_copy_file' => $assetUrl($file->invoice_copy_file),
                'packing_slip_file' => $assetUrl($file->packing_slip_file),
                'eway_bill_file' => $assetUrl($file->eway_bill_file),
                'lr_copy_file' => $assetUrl($file->lr_copy_file),
            ],
        ]);
    }

    private function generatePacketNumber($itemTypeId): string
    {
        return $itemTypeId.date('ymdHis').random_int(100, 999);
    }

    public function sendItemReturnRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'wprId' => 'required|integer',
            'workOrderId' => 'required|integer',
            'ware_out_item_id' => 'required|array',
            'ware_out_item_id.*' => 'required|integer',
            'is_return' => 'required|array',
            'return_item_qty' => 'required|array',
            'return_item_qty.*' => 'nullable|numeric|min:0',
            'used_item_qty' => 'nullable|array',
            'used_item_qty.*' => 'nullable|numeric|min:0',
        ], [
            'wprId.required' => 'Work requirement not found.',
            'workOrderId.required' => 'Work order not found.',
            'ware_out_item_id.required' => 'Return items not found.',
            'is_return.required' => 'Please select at least one item to return.',
            'return_item_qty.required' => 'Return quantity not found.',
        ]);

        if ($validator->fails()) {
            Session::put('message', $validator->errors()->first());
            Session::put('messageClass', 'errorClass');

            return redirect()->back()->withInput();
        }

        $selectedRows = array_filter((array) $request->input('is_return'), function ($value) {
            return (string) $value === '1';
        });

        if (empty($selectedRows)) {
            Session::put('message', 'Please select at least one item to return.');
            Session::put('messageClass', 'errorClass');

            return redirect()->back()->withInput();
        }

        $workProcessRequirementId = (int) $request->input('wprId');
        $workOrderId = (int) $request->input('workOrderId');
        $warehouseOutItemIds = array_values((array) $request->input('ware_out_item_id'));
        $stockIds = array_values((array) $request->input('return_wis_id', []));
        $takaNumbers = array_values((array) $request->input('return_insp_taka_number', []));
        $receivedQty = array_values((array) $request->input('received_item_qty', []));
        $usedQty = array_values((array) $request->input('used_item_qty', []));
        $returnQty = array_values((array) $request->input('return_item_qty', []));
        $lotNumber = trim((string) $request->input('ReqLotNumber', ''));
        $individualId = Auth::user()->individual_id ?? Auth::id() ?? 0;
        $now = now();
        $createdCount = 0;

        DB::beginTransaction();

        try {
            $wprData = WorkProcessRequirement::whereKey($workProcessRequirementId)
                ->where('work_order_id', $workOrderId)
                ->where('status', '!=', 'Deleted')
                ->lockForUpdate()
                ->first();

            if (! $wprData) {
                throw new \RuntimeException('Work process requirement not found.');
            }

            $processTypeId = (int) $wprData->process_type_id;
            $itemTypeId = (int) $wprData->item_type_id;
            $reqLotNumber = $processTypeId > 2 ? ($lotNumber ?: '0') : '0';
            $detailLotNumber = is_numeric($reqLotNumber) ? (int) $reqLotNumber : null;

            $departmentReturn = DepartmentReturn::create([
                'work_order_id' => $workOrderId,
                'req_lot_number' => $reqLotNumber,
                'employee_id' => $individualId,
                'work_pro_req_id' => $workProcessRequirementId,
                'process_type_id' => $processTypeId,
                'item_type_id' => $itemTypeId,
                'return_date' => $now->toDateString(),
                'status' => 'pending',
                'reason' => $request->input('reason'),
                'financial_year' => currentFinancialYear(),
                'created_by' => $individualId,
                'modified_by' => $individualId,
                'created_at' => $now,
                'modified_at' => $now,
            ]);

            foreach (array_keys($selectedRows) as $index) {
                $warehouseOutItemId = (int) ($warehouseOutItemIds[$index] ?? 0);
                if ($warehouseOutItemId <= 0) {
                    continue;
                }

                $outItem = WarehouseOutItem::whereKey($warehouseOutItemId)
                    ->where('work_order_id', $workOrderId)
                    ->where('work_pro_req_id', $workProcessRequirementId)
                    ->where('status', '!=', 'Deleted')
                    ->lockForUpdate()
                    ->first();

                if (! $outItem) {
                    continue;
                }

                $itemReceivedQty = (float) ($receivedQty[$index] ?? $outItem->item_qty ?? 0);
                $itemUsedQty = (float) ($usedQty[$index] ?? 0);
                $itemReturnQty = (float) ($returnQty[$index] ?? 0);

                if ($itemReturnQty <= 0) {
                    continue;
                }

                DepartmentReturnRequest::create([
                    'depart_reqst_id' => $departmentReturn->id,
                    'work_order_id' => $workOrderId,
                    'employee_id' => $individualId,
                    'ware_out_item_id' => $outItem->id,
                    'wis_id' => $stockIds[$index] ?? $outItem->wis_id,
                    'work_pro_req_id' => $workProcessRequirementId,
                    'item_id' => $outItem->item_id,
                    'return_date' => $now->toDateString(),
                    'received_item_qty' => $itemReceivedQty,
                    'used_item_qty' => $itemUsedQty,
                    'item_qty' => $itemReturnQty,
                    'insp_taka_number' => $takaNumbers[$index] ?? $outItem->insp_taka_number,
                    'req_lot_number' => $detailLotNumber,
                    'reason' => $request->input('reason'),
                    'status' => 'pending',
                    'financial_year' => currentFinancialYear(),
                    'created_by' => $individualId,
                    'modified_by' => $individualId,
                    'created_at' => $now,
                    'modified_at' => $now,
                ]);

                $totalConsumed = (float) DepartmentReturnRequest::where('ware_out_item_id', $outItem->id)
                    ->whereIn('status', ['pending', 'accepted'])
                    ->sum('used_item_qty');
                $totalReturned = (float) DepartmentReturnRequest::where('ware_out_item_id', $outItem->id)
                    ->whereIn('status', ['pending', 'accepted'])
                    ->sum('item_qty');
                $allocatedQty = round((float) ($outItem->item_qty ?? 0), 2);
                $netQty = round($totalConsumed + $totalReturned, 2);

                $outItem->qty_consumed = $totalConsumed;
                $outItem->qty_returned = $totalReturned;
                $outItem->is_item_return_whouse = ($processTypeId >= 3 || ($allocatedQty > 0 && $netQty + 0.0001 >= $allocatedQty)) ? '1' : '0';
                $outItem->modified_by = $individualId;
                $outItem->updated_at = $now;
                $outItem->save();

                $createdCount++;
            }

            if ($createdCount === 0) {
                DB::rollBack();
                Session::put('message', 'No return request created. Please select valid items and enter return quantity.');
                Session::put('messageClass', 'errorClass');

                return redirect()->back()->withInput();
            }

            DB::commit();
            Session::put('message', 'Return request created successfully.');
            Session::put('messageClass', 'successClass');

            return redirect()->back();
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);
            Session::put('message', 'Return request could not be created.');
            Session::put('messageClass', 'errorClass');

            return redirect()->back()->withInput();
        }
    }

    public function ShowDepartmentReturnRequest(Request $request)
    {

        $query = DepartmentReturn::with('DepartmentReturnRequest')->with('Individual')->where('is_deleted', '=', '0')->orderByDesc('id');
        $dataDR = $query->paginate(20)->appends($request->all());

        return view('frontend.workprocessrequirement.show-department-return-requests', compact('dataDR'));
    }

    public function storeDepartmentReturnRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'warehouseId' => 'required|integer',
            'warehouseCompId' => 'required|integer',
            'department_return_id' => 'required|integer',
            'work_order_id' => 'required|integer',
            'receiver_name' => 'required|string|max:255',
            'receiving_date' => 'required|date_format:d-m-Y',
            'emp_name' => 'required|string|max:255',
            'ind_emp_id' => 'required|integer',
            'item_type_id' => 'required|min:1',
            'item_name' => 'required|min:1',
            'item_id' => 'required|min:1',
            'wis_id' => 'required|min:1',
            'work_pro_req_id' => 'required|min:1',
            'req_lot_number' => 'required|min:1',
            'return_date' => 'required|min:1',
            'insp_taka_number' => 'required|min:1',
            'item_qty' => 'required|min:1',
        ], [
            'warehouseId.required' => 'Please select Warehouse.',
            'warehouseCompId.required' => 'Please select Warehouse Compartment.',
            'department_return_id.required' => 'Please select department return id.',
            'work_order_id.required' => 'Work order id is required.',
            'receiver_name.required' => 'Please provide the receiver name.',
            'receiving_date.required' => 'Please provide the receiving date.',
            'emp_name.required' => 'Please provide the employee name.',
            'ind_emp_id.required' => 'Please provide the employee ID.',
            'item_type_id.required' => 'Please select at least one item type.',
            'item_name.required' => 'Please provide at least one item name.',
            'item_id.required' => 'Please provide at least one item ID.',
            'wis_id.required' => 'Please provide at least one WIS ID.',
            'work_pro_req_id.required' => 'Please provide at least one work process requirement ID.',
            'req_lot_number.required' => 'Please provide at least one lot number.',
            'return_date.required' => 'Please provide at least one return date.',
            'insp_taka_number.required' => 'Please provide at least one inspection TAKA number.',
            'item_qty.required' => 'Please provide at least one item quantity.',
        ]);

        if ($validator->fails()) {
            Session::put('message', $validator->messages()->first());
            Session::put('messageClass', 'errorClass');

            return redirect()->back()->withInput();
        }

        $userD = Auth::user();
        $IndividualId = $userD->individual_id ?? Auth::id() ?? 0;
        $workOrdId = (int) $request->work_order_id;
        $dprId = (int) $request->department_return_id;
        $warehouseId = (int) $request->warehouseId;
        $warehouseCompId = (int) $request->warehouseCompId;
        $receiverId = (int) ($request->receiver_id ?: $IndividualId);
        $receiving_date = date('Y-m-d', strtotime($request->receiving_date));
        $now = now();
        $today = date('Y-m-d');

        DB::beginTransaction();

        try {

            $dataDpr = DepartmentReturn::where('id', $dprId)->where('status', 'pending')->lockForUpdate()->first();

            if (! $dataDpr) {
                DB::rollBack();

                Session::put('message', 'Return request not found or already processed.');
                Session::put('messageClass', 'errorClass');

                return redirect()->back()->withInput();
            }

            $departmentReturnRequests = DepartmentReturnRequest::where('depart_reqst_id', $dprId)
                ->where('status', 'pending')
                ->lockForUpdate()
                ->get();

            if ($departmentReturnRequests->isEmpty()) {
                DB::rollBack();

                Session::put('message', 'This return request has already been processed.');
                Session::put('messageClass', 'errorClass');

                return redirect()->back()->withInput();
            }

            $dataOrder = WorkOrder::where('id', $workOrdId)->first();

            if (! $dataOrder) {
                throw new \Exception('Work order not found.');
            }

            $workMachineId = $request->filled('machine_id') ? $request->machine_id : ($dataOrder->machine_id ?? null);
            $requestIds = $departmentReturnRequests->pluck('id')->map(fn ($id) => (int) $id)->all();
            $wprIds = $departmentReturnRequests->pluck('work_pro_req_id')->filter()->unique()->values()->all();
            $sourceStockIds = $departmentReturnRequests->pluck('wis_id')->filter()->unique()->values()->all();

            $alreadyReceivedRequestIds = WarehouseItemStock::whereIn('dept_return_req_id', $requestIds)
                ->where('status', 'Active')
                ->lockForUpdate()
                ->pluck('dept_return_req_id')
                ->map(fn ($id) => (int) $id)
                ->all();

            if (! empty($alreadyReceivedRequestIds)) {
                throw new \Exception('Some return request items have already been received. Return Request ID: '.implode(', ', $alreadyReceivedRequestIds));
            }

            $wprDataMap = WorkProcessRequirement::whereIn('id', $wprIds)
                ->where('status', 'Active')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $sourceStockMap = WarehouseItemStock::whereIn('id', $sourceStockIds)
                ->where('status', 'Active')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $sourceWarehouseItemIds = $sourceStockMap->pluck('warehouse_item_id')->filter()->unique()->values()->all();
            $sourceWarehouseItemMap = WarehouseItem::whereIn('id', $sourceWarehouseItemIds)->get()->keyBy('id');
            $sourceStockFileMap = WarehouseItemStockFile::whereIn('wis_id', $sourceStockIds)
                ->where('status', 'Active')
                ->get()
                ->keyBy('wis_id');

            $wprHasReturnQuantity = DB::getSchemaBuilder()->hasColumn('work_process_requirements', 'return_quantity');
            $wprHasIsItemReturned = DB::getSchemaBuilder()->hasColumn('work_process_requirements', 'is_item_returned');
            $wprHasIsAllItemReturned = DB::getSchemaBuilder()->hasColumn('work_process_requirements', 'is_all_item_returned');
            $wprReturnQtyArr = [];

            foreach ($departmentReturnRequests as $rowArr) {
                $wprId = (int) $rowArr->work_pro_req_id;
                $wprData = $wprDataMap->get($wprId);

                if (! $wprData) {
                    throw new \Exception("Work process requirement not found. WPR ID: {$wprId}");
                }

                $itemTypeId = $wprData->item_type_id ?? ($rowArr->item_type_id ?? null);
                $QuanSize = (float) $rowArr->item_qty;
                $FId = (int) $rowArr->wis_id;
                $ReturnItemQty = $QuanSize;

                if ($ReturnItemQty <= 0) {
                    throw new \Exception("Invalid return quantity. Return Request ID: {$rowArr->id}");
                }

                $wisData = $sourceStockMap->get($FId);

                if (! $wisData) {
                    throw new \Exception("Source Warehouse Item Stock not found. WIS ID: {$FId}");
                }

                $wareItemData = $sourceWarehouseItemMap->get($wisData->warehouse_item_id);

                if (! $wareItemData) {
                    throw new \Exception("Source Warehouse Item not found. Warehouse Item ID: {$wisData->warehouse_item_id}");
                }

                $warehouseItem = WarehouseItem::create([
                    'work_order_id' => $workOrdId,
                    'dept_return_id' => $rowArr->depart_reqst_id,
                    'dept_return_req_id' => $rowArr->id,
                    'insp_id' => $wareItemData->insp_id,
                    'process_type_id' => $wareItemData->process_type_id,
                    'warehouse_id' => $warehouseId,
                    'ware_comp_id' => $warehouseCompId,
                    'receiver_id' => $receiverId,
                    'ind_emp_id' => $request->ind_emp_id,
                    'emp_name' => $request->emp_name,
                    'receive_date' => $receiving_date,
                    'item_id' => $rowArr->item_id,
                    'insp_taka_number' => $rowArr->insp_taka_number,
                    'dyeing_lot_number' => $rowArr->req_lot_number,
                    'dyeing_taka_number' => $wisData->dyeing_taka_number,
                    'fabric_fault_reason_id' => $wisData->fabric_fault_reason_id,
                    'item_type_id' => $itemTypeId,
                    'unit_type_id' => 2,
                    'machine_id' => $workMachineId,
                    'master_id' => $dataOrder->master_ind_id ?? null,
                    'pur_item_name' => $wareItemData->pur_item_name,
                    'dyeing_color' => $wareItemData->dyeing_color,
                    'coated_pvc' => $wareItemData->coated_pvc,
                    'grey_quality' => $wareItemData->grey_quality,
                    'extra_job' => $wareItemData->extra_job,
                    'print_job' => $wareItemData->print_job,
                    'item_qty' => $QuanSize,
                    'created' => $now,
                    'financial_year' => currentFinancialYear(),
                    'status' => 'Active',
                ]);

                $warehouseItemId = $warehouseItem->getKey();

                $opItemQty = WarehouseBalanceItem::where('item_id', $warehouseItem->item_id)
                    ->where('item_type_id', $warehouseItem->item_type_id)
                    ->where('unit_type_id', $warehouseItem->unit_type_id)
                    ->where('dyeing_color', $warehouseItem->dyeing_color)
                    ->where('coating_type', $warehouseItem->coated_pvc)
                    ->where('print_job', $warehouseItem->print_job)
                    ->where('extra_job', $warehouseItem->extra_job)
                    ->where('balance_status', '1')
                    ->lockForUpdate()
                    ->first();

                if ($opItemQty) {
                    WarehouseBalanceItem::where('id', $opItemQty->id)->update(['balance_status' => '0']);
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
                    'item_qty' => $opItemQty ? ((float) $opItemQty->item_qty + $ReturnItemQty) : $ReturnItemQty,
                    'grey_quality' => $warehouseItem->grey_quality,
                    'dyeing_color' => $warehouseItem->dyeing_color,
                    'coated_pvc' => $warehouseItem->coated_pvc,
                    'print_job' => $warehouseItem->print_job,
                    'extra_job' => $warehouseItem->extra_job,
                    'created' => $now,
                    'financial_year' => currentFinancialYear(),
                    'status' => 'Active',
                    'balance_status' => '1',
                ]);

                $objWIS = WarehouseItemStock::create([
                    'warehouse_item_id' => $warehouseItemId,
                    'dept_return_id' => $warehouseItem->dept_return_id,
                    'dept_return_req_id' => $warehouseItem->dept_return_req_id,
                    'warehouse_id' => $warehouseItem->warehouse_id,
                    'ware_comp_id' => $warehouseItem->ware_comp_id,
                    'work_order_id' => $workOrdId,
                    'work_pro_req_id' => $wprId,
                    'quantity' => 1,
                    'insp_quan_size' => $ReturnItemQty,
                    'insp_allot_quan_size' => 0,
                    'insp_bal_quan_size' => $ReturnItemQty,
                    'quan_size_unit' => $wisData->quan_size_unit,
                    'entry_type' => 'IN',
                    'machine_id' => $workMachineId,
                    'receiver_id' => $IndividualId,
                    'receive_date' => $receiving_date,
                    'vendor_id' => $wisData->vendor_id,
                    'invoice_number' => $wisData->invoice_number,
                    'fabric_fault_reason_id' => $wisData->fabric_fault_reason_id,
                    'insp_taka_number' => $rowArr->insp_taka_number,
                    'dyeing_lot_number' => $rowArr->req_lot_number,
                    'dyeing_taka_number' => $wisData->dyeing_taka_number,
                    'purchase_date' => $wisData->purchase_date,
                    'insp_id' => $wisData->insp_id,
                    'gate_pass_id' => $wisData->gate_pass_id,
                    'item_type_id' => $itemTypeId,
                    'unit_type_id' => 2,
                    'item_id' => $rowArr->item_id,
                    'item_remark' => $wisData->item_remark,
                    'grey_quality' => $wisData->grey_quality,
                    'dyeing_color' => $wisData->dyeing_color,
                    'coated_pvc' => $wisData->coated_pvc,
                    'print_job' => $wisData->print_job,
                    'extra_job' => $wisData->extra_job,
                    'created' => $now,
                    'financial_year' => currentFinancialYear(),
                    'status' => 'Active',
                ]);

                $dataWISF = $sourceStockFileMap->get($FId);

                WarehouseItemStockFile::create([
                    'warehouse_item_id' => $warehouseItemId,
                    'wis_id' => $objWIS->id,
                    'wis_out_id' => null,
                    'vendor_id' => $wisData->vendor_id,
                    'invoice_number' => $wisData->invoice_number,
                    'invoice_copy_file' => $dataWISF->invoice_copy_file ?? null,
                    'packing_slip_file' => $dataWISF->packing_slip_file ?? null,
                    'eway_bill_file' => $dataWISF->eway_bill_file ?? null,
                    'lr_copy_file' => $dataWISF->lr_copy_file ?? null,
                    'created' => $today,
                    'modified' => $today,
                    'financial_year' => currentFinancialYear(),
                    'status' => 'Active',
                ]);

                if (! isset($wprReturnQtyArr[$wprId])) {
                    $wprReturnQtyArr[$wprId] = 0;
                }

                $wprReturnQtyArr[$wprId] += $ReturnItemQty;
            }

            DepartmentReturnRequest::whereIn('id', $requestIds)->where('status', 'pending')->update(['status' => 'accepted']);
            DepartmentReturn::where('id', $dprId)->where('status', 'pending')->update(['status' => 'accepted']);

            if (! empty($wprReturnQtyArr)) {
                foreach ($wprReturnQtyArr as $wprId => $returnItemSum) {
                    $wprData = $wprDataMap->get($wprId);

                    if ($wprData) {
                        $existingAllotQty = is_numeric($wprData->alloted_quantity) ? (float) $wprData->alloted_quantity : 0;
                        $updateData = [];

                        if ($wprHasReturnQuantity) {
                            $existingReturnQty = is_numeric($wprData->return_quantity) ? (float) $wprData->return_quantity : 0;
                            $newReturnQty = $existingReturnQty + (float) $returnItemSum;
                            $updateData['return_quantity'] = $newReturnQty;

                            if ($wprHasIsAllItemReturned) {
                                $updateData['is_all_item_returned'] = ($newReturnQty >= $existingAllotQty) ? 'Yes' : 'No';
                            }
                        }

                        if ($wprHasIsItemReturned) {
                            $updateData['is_item_returned'] = 'Yes';
                        }

                        if (! empty($updateData)) {
                            WorkProcessRequirement::where('id', $wprId)->update($updateData);
                        }
                    }
                }
            }

            DB::commit();

            Session::put('message', 'Item Received in Warehouse successfully.');
            Session::put('messageClass', 'successClass');

        } catch (\Exception $e) {

            DB::rollBack();

            \Log::error('storeDepartmentReturnRequest error: '.$e->getMessage());

            Session::put('message', 'Something error to receive items in warehouse. '.$e->getMessage());
            Session::put('messageClass', 'errorClass');
        }

        return redirect('/show-department-return-requests');
    }

    public function acceptDepartmentReturnRequest($id)
    {

        $dprId = $this->decodeDepartmentReturnRequestId((string) $id);
        $dataDpr = DepartmentReturn::where('id', $dprId)->where('status', 'pending')
            ->with(['DepartmentReturnRequest.Item' => function ($q) {
                $q->select('item_id', 'item_name', 'item_code', 'internal_item_name', 'item_final_gsm', 'item_width', 'item_final_width', 'remarks');
            }])->first();

        if (! $dataDpr) {
            Session::put('message', 'Department return request not found or already processed.');
            Session::put('messageClass', 'errorClass');

            return redirect()->route('show-department-return-requests');
        }

        $userId = Auth::id();
        $userD = User::find($userId);
        $userIndId = $userD->individual_id ?? $userId;
        $dataW = Warehouse::where('status', 'Active')->orderBy('warehouse_name', 'asc')->get();

        $itemTypeId = ! empty($dataDpr->item_type_id) ? $dataDpr->item_type_id : 3;
        $dataIT = ItemType::where('status', 'Active')->where('item_type_id', $itemTypeId)->where('is_work', '1')->get();

        $viewData = compact('dataDpr', 'userD', 'dataW', 'dataIT', 'itemTypeId');

        return view('frontend.warehouseitems.accept-department-return-request', $viewData);

    }

    public function showAcceptedDepartmentReturnRequest($id)
    {

        $dprId = $this->decodeDepartmentReturnRequestId((string) $id);
        $dataDpr = DepartmentReturn::where('id', $dprId)->where('status', 'accepted')
            ->with(['AcceptedDepartmentReturnRequest.Item' => function ($q) {
                $q->select('item_id', 'item_name', 'item_code', 'internal_item_name', 'item_final_gsm', 'item_width', 'item_final_width', 'remarks');
            }])->first();

        if (! $dataDpr) {
            Session::put('message', 'Accepted department return request not found.');
            Session::put('messageClass', 'errorClass');

            return redirect()->route('show-department-return-requests');
        }

        $userId = Auth::id();
        $userD = User::find($userId);
        $userIndId = $userD->individual_id ?? $userId;
        $dataW = Warehouse::where('status', 'Active')->orderBy('warehouse_name', 'asc')->get();
        $acceptedWarehouseItems = WarehouseItem::with([
            'Warehouse:id,warehouse_name',
            'WarehouseCompartment:id,compartment_name',
            'ItemType:item_type_id,item_type_name',
        ])
            ->where('dept_return_id', $dprId)
            ->where('status', 'Active')
            ->get()
            ->keyBy('dept_return_req_id');
        $itemTypeIds = $dataDpr->AcceptedDepartmentReturnRequest->pluck('item_type_id')
            ->merge($acceptedWarehouseItems->pluck('item_type_id'))
            ->push($dataDpr->item_type_id)
            ->filter()
            ->unique()
            ->values()
            ->all();
        $dataIT = ItemType::where('status', 'Active')
            ->whereIn('item_type_id', ! empty($itemTypeIds) ? $itemTypeIds : [3])
            ->where('is_work', '1')
            ->get();

        $viewData = compact('dataDpr', 'userD', 'dataW', 'dataIT', 'acceptedWarehouseItems');
        // echo "<pre>"; print_r($dataDpr); exit;

        return view('frontend.warehouseitems.show-accepted-department-return-request', $viewData);

    }

    public function denyDepartmentRequest(Request $request)
    {
        $userId = Auth::id();

        $validator = Validator::make($request->all(), [
            'department_return_id' => 'required|integer',
            'reason' => 'nullable|string|max:1000',
        ], [
            'department_return_id.required' => 'Return request not found.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $dprId = (int) $request->department_return_id;
        $reason = $request->filled('reason') ? trim((string) $request->reason) : null;

        DB::beginTransaction();

        try {
            $dprData = DepartmentReturn::where('id', $dprId)->lockForUpdate()->first();

            if (! $dprData) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'Return request not found.',
                ]);
            }

            if (in_array($dprData->status, ['accepted', 'rejected'], true)) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'This request already processed.',
                ]);
            }

            $drrData = DepartmentReturnRequest::where('depart_reqst_id', $dprId)->lockForUpdate()->get();

            foreach ($drrData as $valArr) {
                if (! empty($valArr->ware_out_item_id)) {
                    $warehouseOutUpdate = [
                        'is_item_return_whouse' => '0',
                        'modified_by' => $userId,
                        'updated_at' => now(),
                    ];

                    if ((int) $dprData->process_type_id < 3) {
                        $warehouseOutUpdate['qty_returned'] = 0;
                        $warehouseOutUpdate['qty_consumed'] = 0;
                    }

                    WarehouseOutItem::where('id', $valArr->ware_out_item_id)->update($warehouseOutUpdate);
                }

                DepartmentReturnRequest::where('id', $valArr->id)->update([
                    'status' => 'rejected',
                    'modified_by' => $userId,
                    'modified_at' => now(),
                ]);
            }

            DepartmentReturn::where('id', $dprId)->update([
                'status' => 'rejected',
                'rejected_by' => $userId,
                'reject_note' => $reason,
                'modified_by' => $userId,
                'modified_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Return request rejected successfully.',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('denyDepartmentRequest error: '.$e->getMessage(), [
                'department_return_id' => $dprId,
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    private function decodeDepartmentReturnRequestId(string $id): int
    {
        try {
            return (int) dec($id);
        } catch (\Throwable $e) {
            $decoded = base64_decode($id, true);

            return is_numeric($decoded) ? (int) $decoded : 0;
        }
    }

    public function search_warehouse_compartment(Request $request)
    {
        $warehouseId = $request->input('Id');
        $compartments = WarehouseCompartment::where('warehouse_id', $warehouseId)
            ->where('status', '=', 'Active')
            ->orderBy('compartment_name')
            ->get();

        $html = '<label for="warehouseCompId">Warehouse Compartment <span class="text-danger">*</span></label>';
        $html .= '<select class="form-control" name="warehouseCompId" id="warehouseCompId" required onchange="selectEmployee(this.value);">';
        $html .= '<option value="">Select Compartment</option>';

        foreach ($compartments as $compartment) {
            $html .= '<option value="'.e($compartment->id).'">'.e($compartment->compartment_name).'</option>';
        }

        $html .= '</select>';

        return response($html);
    }

    public function search_warehouse_compartment_arr(Request $request)
    {
        $warehouseId = $request->input('Id');
        $compartments = WarehouseCompartment::where('warehouse_id', $warehouseId)
            ->where('status', '=', 'Active')
            ->orderBy('compartment_name')
            ->get();

        $html = '<select class="form-control input-sm" name="warehouseCompId[]" required>';
        $html .= '<option value="">Select Compartment</option>';

        foreach ($compartments as $compartment) {
            $html .= '<option value="'.e($compartment->id).'">'.e($compartment->compartment_name).'</option>';
        }

        $html .= '</select>';

        return response($html);
    }

    public function getWarehouseCompEmployee(Request $request)
    {
        $compartment = WarehouseCompartment::where('id', $request->input('Id'))
            ->where('status', '=', 'Active')
            ->first();

        $employee = $compartment
            ? Individual::where('id', $compartment->ind_emp_id)->where('status', '=', 'Active')->first()
            : null;

        return response(($employee->id ?? '').'||'.($employee->name ?? ''));
    }

    public function get_warehouse_compartment_options(Request $request)
    {
        $stock = WarehouseItemStock::where('id', $request->input('Id'))
            ->where('status', 'Active')
            ->first();

        if (empty($stock) || empty($stock->warehouse_id)) {
            return response()->json([]);
        }

        $options = WarehouseCompartment::where('warehouse_id', $stock->warehouse_id)
            ->where('status', 'Active')
            ->orderBy('compartment_name')
            ->get(['id', 'compartment_name']);

        return response()->json($options);
    }

    public function updateWarehouseComp(Request $request)
    {
        $stockId = $request->input('id');
        $compartmentId = $request->input('selectedValue');

        $stock = WarehouseItemStock::where('id', $stockId)->where('status', 'Active')->first();
        $compartment = WarehouseCompartment::where('id', $compartmentId)->where('status', 'Active')->first();

        if (empty($stock) || empty($compartment)) {
            return response()->json(['success' => false], 404);
        }

        $stock->ware_comp_id = $compartment->id;
        $stock->warehouse_id = $compartment->warehouse_id;
        $stock->save();

        if (! empty($stock->warehouse_item_id)) {
            WarehouseItem::where('id', $stock->warehouse_item_id)->update([
                'ware_comp_id' => $compartment->id,
                'warehouse_id' => $compartment->warehouse_id,
            ]);
        }

        return response()->json(['success' => true]);
    }

    public function deleteWarehouseItemStock(Request $request)
    {
        $stock = WarehouseItemStock::where('id', $request->input('FId'))->where('status', 'Active')->first();

        if (empty($stock)) {
            return response()->json(['error' => 'Stock record not found.'], 404);
        }

        $stock->status = 'Deleted';
        $stock->deleted_by = Auth::id();
        $stock->save();

        return response()->json(['success' => 'Deleted successfully.']);
    }

    public function RefreshWarehouseItem(Request $request)
    {
        $wbId = (int) $request->FId;
        $dataWB = WarehouseBalanceItem::where('id', $wbId)
            ->where('status', 'Active')
            ->where('balance_status', 1)
            ->first();

        if (! $dataWB) {
            return response()->json(['success' => false, 'message' => 'Record not found']);
        }

        $itemId = $dataWB->item_id;
        $itemTypeId = $dataWB->item_type_id;
        $dyeing_color = $dataWB->dyeing_color;
        $coatingType = $dataWB->coating_type;

        $query = WarehouseItemStock::where('item_id', $itemId)
            ->where('item_type_id', $itemTypeId)
            ->where('entry_type', 'IN')
            ->where('is_allotted_stock', 'No')
            ->where('status', 'Active');

        if ($itemTypeId == '4' || $itemTypeId == '5') {
            $query->where('dyeing_color', $dyeing_color);
        }

        if ($itemTypeId == '5') {
            $query->where('coating_type', $coatingType);
        }

        if (is_null($dataWB->print_job)) {
            $query->whereNull('print_job');
        } else {
            $query->where('print_job', $dataWB->print_job);
        }

        if (is_null($dataWB->extra_job)) {
            $query->whereNull('extra_job');
        } else {
            $query->where('extra_job', $dataWB->extra_job);
        }

        $SumInspBalQuanSize = $query->sum('insp_bal_quan_size');

        /* $sql = $query->toSql();
        $bindings = $query->getBindings();
        $fullSql = vsprintf(str_replace(['?'], ['\'%s\''], $sql), $bindings);
        echo $fullSql; exit;
        */
        WarehouseBalanceItem::where('id', $wbId)->update([
            'item_qty' => $SumInspBalQuanSize,
            'modified_by' => Auth::id(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'new_qty' => $SumInspBalQuanSize,
            'message' => 'Warehouse item refreshed successfully.',
        ]);
    }

    public function ShowBalanceTableStock(Request $request)
    {

        $qsearch = trim((string) $request->qsearch);
        $workSaleOrd = trim((string) $request->work_sale_ord);
        $item_type = trim((string) $request->item_type);
        $warehouseId = trim((string) $request->warehouseId);
        $warehCompId = trim((string) $request->warehouseCompId);
        $fromDate = $request->from_date;
        $toDate = $request->to_date;
        $sbtSearch = $request->sbtSearch;
        $dataIT = ItemType::where('status', '=', 'Active')->get();
        $dataW = Warehouse::where('status', '=', 'Active')->orderByDesc('id')->get();

        $query = WarehouseBalanceItem::where('status', '=', 'Active')
            ->where('balance_status', '=', '1')
            ->whereIn('item_type_id', [1, 2, 3, 4, 5, 6, 8])
            // ->where('item_qty', '>', '0')
            ->with('WarehouseItem', 'WarehouseOutItem', 'Warehouse', 'WarehouseCompartment', 'User', 'ReceiverIndividual', 'Item', 'ItemType')
            ->orderByDesc('id');

        if (! empty($qsearch)) {
            $itemIds = Item::where(DB::raw("CONCAT(COALESCE(item_name, ''), ' ', COALESCE(internal_item_name, ''))"), 'LIKE', '%'.$qsearch.'%')->where('status', '=', 'Active')->pluck('item_id')->toArray();
            $query->whereIn('item_id', $itemIds);
        }
        if (! empty($item_type)) {
            $itemType = explode(',', $item_type);
            $query->whereIn('item_type_id', $itemType);
        }
        if (! empty($warehouseId)) {
            $query->whereIn('warehouse_id', explode(',', $warehouseId));
        }
        if (! empty($warehCompId)) {
            $query->whereIn('ware_comp_id', explode(',', $warehCompId));
        }

        if (! empty($fromDate) && ! empty($toDate)) {
            $fromDate = date('Y-m-d', strtotime($request->from_date));
            $toDate = date('Y-m-d', strtotime($request->to_date));
            $query->where('receive_date', '>=', $fromDate)->where('receive_date', '<=', $toDate);
        }

        if ($sbtSearch == 'ExportToExcel') {
            try {
                $dataWI = $query->get();

                return Excel::download(new WarehouseBalanceStockListing($dataWI), 'warehouse_balance_stock_listing.xlsx');
            } catch (\Exception $e) {
                \Log::error('Exception: '.$e->getMessage());

                return response('Error generating Excel', 500);
            }
        }

        $dataWB = $query->paginate(1000)->appends($request->all());

        return view('frontend.warehouseitems.show-balance-table-stock', compact('dataWB', 'qsearch', 'fromDate', 'toDate', 'item_type', 'warehouseId', 'warehCompId', 'dataW', 'dataIT'));
    }
}
