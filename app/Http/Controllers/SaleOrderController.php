<?php

namespace App\Http\Controllers;

use App\Exports\SaleOrderItemExport;
use App\Models\Coting;
use App\Models\Individual;
use App\Models\Item;
use App\Models\SaleOrder;
use App\Models\SaleOrderItem;
use App\Models\Reason;
use App\Models\UnitType;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderItem;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;

class SaleOrderController extends Controller
{
    public function index(Request $request)
    {
        $qsearch = trim($request->input('qsearch', ''));
        $qnamesearch = trim($request->input('qnamesearch', ''));
        $ordNumSearch = trim($request->input('ordNumSearch', ''));
        $priority = $request->input('priority', '');
        $fromDate = $request->input('from_date', '');
        $toDate = $request->input('to_date', '');
        $createDate = $request->input('create_date', '');
        $sale_order_type = $request->input('sale_order_type', '');

        $query = SaleOrder::with(['customer', 'employee', 'agent', 'saleOrderItems.item', 'saleOrderItems.unitType']);
        $query->where('status', '!=', 'Deleted');

        if ($qsearch != '') {
            $query->whereHas('customer', function ($customerQuery) use ($qsearch) {
                $customerQuery->where('name', 'like', '%'.$qsearch.'%');
                $customerQuery->orWhere('company_name', 'like', '%'.$qsearch.'%');
                $customerQuery->orWhere('phone', 'like', '%'.$qsearch.'%');
            });
        }

        if ($qnamesearch != '') {
            $query->whereHas('saleOrderItems', function ($itemQuery) use ($qnamesearch) {
                $itemQuery->where('item_name', 'like', '%'.$qnamesearch.'%');
                $itemQuery->orWhere('grey_quality', 'like', '%'.$qnamesearch.'%');
                $itemQuery->orWhere('dyeing_color', 'like', '%'.$qnamesearch.'%');
            });
        }

        if ($ordNumSearch != '') {
            $query->where('sale_order_number', 'like', '%'.$ordNumSearch.'%');
        }

        if ($priority != '') {
            $query->where('order_priority', $priority);
        }

        if ($sale_order_type != '') {
            $query->where('sale_order_type', $sale_order_type);
        }

        $fromDateSearch = $fromDate;
        if ($fromDateSearch != '' && strpos($fromDateSearch, '-') !== false) {
            $fromDateParts = explode('-', $fromDateSearch);
            if (count($fromDateParts) == 3) {
                $fromDateSearch = $fromDateParts[2].'-'.$fromDateParts[1].'-'.$fromDateParts[0];
            }
        }

        $toDateSearch = $toDate;
        if ($toDateSearch != '' && strpos($toDateSearch, '-') !== false) {
            $toDateParts = explode('-', $toDateSearch);
            if (count($toDateParts) == 3) {
                $toDateSearch = $toDateParts[2].'-'.$toDateParts[1].'-'.$toDateParts[0];
            }
        }

        $createDateSearch = $createDate;
        if ($createDateSearch != '' && strpos($createDateSearch, '-') !== false) {
            $createDateParts = explode('-', $createDateSearch);
            if (count($createDateParts) == 3) {
                $createDateSearch = $createDateParts[2].'-'.$createDateParts[1].'-'.$createDateParts[0];
            }
        }

        if ($fromDateSearch != '') {
            $query->whereDate('sale_order_date', '>=', $fromDateSearch);
        }

        if ($toDateSearch != '') {
            $query->whereDate('sale_order_date', '<=', $toDateSearch);
        }

        if ($createDateSearch != '') {
            $query->whereDate('created_at', $createDateSearch);
        }

        $saleOrders = $query->orderBy('id', 'desc')->paginate(config('app.pagination_limit'))->withQueryString();
        $priorityArr = ['Low', 'Medium', 'High', 'Urgent'];
        $unitTypes = UnitType::where('status', 'Active')->orderBy('unit_type_id', 'asc')->get();
        $coatings = Coting::where('status', 'Active')->orderBy('id', 'asc')->get();

        return view('frontend.saleorders.index', compact('saleOrders', 'qsearch', 'qnamesearch', 'ordNumSearch', 'priority', 'fromDate', 'toDate', 'createDate', 'sale_order_type', 'priorityArr', 'unitTypes', 'coatings'));
    }

    public function create()
    {
        $unitTypes = UnitType::where('status', 'Active')->orderBy('unit_type_id', 'asc')->get();
        $priorityArr = ['Low', 'Medium', 'High', 'Urgent'];
        $lotNumber = SaleOrder::max('id') + 1;
		
		$dataI = Individual::where('type', '=', 'agents')->where('status', '=', 'Active')->get();
		$dataE = Individual::where('type', '=', 'employee')->where('status', '=', 'Active')->get();

        $coatings = Coting::where('status', 'Active')->orderBy('id', 'asc')->get();

        return view('frontend.saleorders.create', compact('unitTypes', 'coatings', 'priorityArr', 'lotNumber', 'dataI', 'dataE'));
    }

    public function edit($id)
    {
        $saleOrderId = dec($id);
        $saleOrder = SaleOrder::where('id', $saleOrderId)->where('status', '!=', 'Deleted')->first();

        if (empty($saleOrder)) {
            Session::put('message', 'Sale order not found.');
            Session::put('messageClass', 'errorClass');
            return redirect()->route('sale-orders.index');
        }

        Session::put('message', 'Sale order edit page is not ready yet.');
        Session::put('messageClass', 'errorClass');
        return redirect()->route('sale-orders.index');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customer_id' => 'required',
            'order_by_employee' => 'required',
            'sale_order_number' => 'required',
            'sale_order_date' => 'required',
            'sales_order' => 'required',
            'order_priority' => 'required',
            'development_type' => 'required',
            'count_product' => 'required|integer|min:1',
            'item_id_arr' => 'required|array',
        ], [
            'customer_id.required' => 'Please select Customer.',
            'order_by_employee.required' => 'Please select Order By Employee.',
            'sale_order_number.required' => 'Please enter Sale Order Number.',
            'sale_order_date.required' => 'Please select Sale Order Date.',
            'sales_order.required' => 'Please select Sales Order.',
            'order_priority.required' => 'Please select Sale Order Priority.',
            'development_type.required' => 'Please select Development Type.',
            'count_product.required' => 'Please add at least one item.',
            'count_product.min' => 'Please add at least one item.',
            'item_id_arr.required' => 'Please add at least one item.',
        ]);

        if ($validator->fails()) {
            Session::put('message', $validator->errors()->first());
            Session::put('messageClass', 'errorClass');
            return redirect()->back()->withInput();
        }

        $oldSaleOrder = SaleOrder::where('sale_order_number', $request->sale_order_number)->where('status', '!=', 'Deleted')->first();
        if (!empty($oldSaleOrder)) {
            Session::put('message', 'Sale Order Number already exists.');
            Session::put('messageClass', 'errorClass');
            return redirect()->back()->withInput();
        }

        DB::beginTransaction();
        try {
            $itemIds        = $request->input('item_id_arr', []);
            $itemNames      = $request->input('item_name_arr', []);
            $unitTypeIds    = $request->input('unit_type_id_arr', []);
            $meters         = $request->input('meter_arr', []);
            $rates          = $request->input('rate_arr', []);
            $amounts        = $request->input('amount_arr', []);
            $greyQualities  = $request->input('grey_quality_arr', []);
            $dyeingColors   = $request->input('dyeing_color_arr', []);
            $coatingTypes   = $request->input('coating_type_arr', []);
            $printJobs      = $request->input('print_job_arr', []);
            $extraJobs              = $request->input('extra_job_arr', []);
            $packingRollLengths     = $request->input('packing_roll_length_arr', []);
            $finalDispatchWidths    = $request->input('final_dispatch_width_arr', []);
            $tubeWidths             = $request->input('tube_width_arr', []);
            $expectDeliveryDates    = $request->input('expect_delivery_date_arr', []);
            $pcsList                = $request->input('pcs_arr', []);
            $cutList                = $request->input('cut_arr', []);
            $remarksList            = $request->input('remarks_arr', []);
            $itemPriorities         = $request->input('order_item_priority_arr', []);

            $amount = 0;
            foreach ($amounts as $rowAmount) {
                $amount = $amount + (float) $rowAmount;
            }

            $discountAmount = 0;
            $netAmount = $amount;

            $orderSlipFile = null;
            if ($request->hasFile('order_slip_file')) {
                $file           = $request->file('order_slip_file');
                $fileName       = time().'_'.$file->getClientOriginalName();
                $file->move(public_path('uploads/sale-orders'), $fileName);
                $orderSlipFile  = 'uploads/sale-orders/'.$fileName;
            }

            $saleOrderDate = $request->sale_order_date;
            if ($saleOrderDate != '' && strpos($saleOrderDate, '-') !== false) {
                $saleOrderDateParts = explode('-', $saleOrderDate);
                if (count($saleOrderDateParts) == 3) {
                    $saleOrderDate = $saleOrderDateParts[2].'-'.$saleOrderDateParts[1].'-'.$saleOrderDateParts[0];
                }
            }

            $saleOrder = new SaleOrder();
            $saleOrder->customer_id         = $request->customer_id;
            $saleOrder->billing_id          = $request->billing_id;
            $saleOrder->shipping_id         = $request->shipping_id;
            $saleOrder->sale_order_type     = $request->sale_order_type;
            $saleOrder->sale_order_date     = $saleOrderDate;
            $saleOrder->sale_order_number   = $request->sale_order_number;
            $saleOrder->sales_order         = $request->sales_order;
            $saleOrder->sale_order_from     = $request->sale_order_from;
            $saleOrder->order_priority      = $request->order_priority;
            $saleOrder->development_type    = $request->development_type;
            $saleOrder->order_slip_file     = $orderSlipFile;
            $saleOrder->billing_address     = $request->billing_address;
            $saleOrder->shipping_address    = $request->shipping_address;
            $saleOrder->items               = count($itemIds);
            $saleOrder->lot_number          = $request->lot_number;
            $saleOrder->ind_agent_id        = $request->ind_agent_id;
            $saleOrder->subtotal            = $amount;
            $saleOrder->discount_amount     = $discountAmount;
            $saleOrder->amount              = $netAmount;
            $saleOrder->total               = $netAmount;
            $saleOrder->total_amount_without_roundoff   = $netAmount;
            $saleOrder->roundoff                        = round($netAmount) - $netAmount;
            $saleOrder->total_amount_after_roundoff     = round($netAmount);
            $saleOrder->order_by_employee               = $request->order_by_employee;
            $saleOrder->financial_year                  = currentFinancialYear();
            $saleOrder->created_by          = Auth::id();
            $saleOrder->modified_by         = Auth::id();
            $saleOrder->created_at          = date('Y-m-d H:i:s');
            $saleOrder->modified_at         = date('Y-m-d H:i:s');
            $saleOrder->status              = 'Active';
            $saleOrder->save();

            foreach ($itemIds as $index => $itemId) {
                if (empty($itemId)) {
                    continue;
                }

                $item               = Item::where('item_id', $itemId)->first();
                $unitTypeId         = $unitTypeIds[$index] ?? '';
                $unitType           = UnitType::where('unit_type_id', $unitTypeId)->first();
                $meter              = (float) ($meters[$index] ?? 0);
                $rate               = (float) ($rates[$index] ?? 0);
                $itemAmount         = (float) ($amounts[$index] ?? 0);
                $expectDeliveryDate = $expectDeliveryDates[$index] ?? '';

                if ($expectDeliveryDate != '' && strpos($expectDeliveryDate, '-') !== false) {
                    $expectDeliveryDateParts = explode('-', $expectDeliveryDate);
                    if (count($expectDeliveryDateParts) == 3) {
                        $expectDeliveryDate = $expectDeliveryDateParts[2].'-'.$expectDeliveryDateParts[1].'-'.$expectDeliveryDateParts[0];
                    }
                }

                $saleOrderItem = new SaleOrderItem();
                $saleOrderItem->sale_order_id = $saleOrder->id;
                $saleOrderItem->item_id = $itemId;
                $saleOrderItem->item_type_id = !empty($item) ? $item->item_type_id : null;
                $saleOrderItem->unit_type_id = $unitTypeId;
                $saleOrderItem->item_name = !empty($item) ? $item->item_name : ($itemNames[$index] ?? '');
                $saleOrderItem->discount_type = null;
                $saleOrderItem->unit = !empty($unitType) ? $unitType->unit_type_name : '';
                $saleOrderItem->order_item_priority = $itemPriorities[$index] ?? '';
                $saleOrderItem->pcs = $pcsList[$index] ?? 1;
                $saleOrderItem->cut = $cutList[$index] ?? 1;
                $saleOrderItem->meter = $meter;
                $saleOrderItem->rate = $rate;
                $saleOrderItem->amount = $itemAmount;
                $saleOrderItem->discount = 0;
                $saleOrderItem->discount_amount = 0;
                $saleOrderItem->net_amount = $itemAmount;
                $saleOrderItem->total_price = $itemAmount;
                $saleOrderItem->grey_quality = $greyQualities[$index] ?? '';
                $saleOrderItem->dyeing_color = $dyeingColors[$index] ?? '';
                $saleOrderItem->coating_type = $coatingTypes[$index] ?? '';
                $saleOrderItem->extra_job = $extraJobs[$index] ?? '';
                $saleOrderItem->print_job = $printJobs[$index] ?? '';
                $saleOrderItem->packing_roll_length = $packingRollLengths[$index] ?? '';
                $saleOrderItem->final_dispatch_width = $finalDispatchWidths[$index] ?? '';
                $saleOrderItem->tube_width = $tubeWidths[$index] ?? '';
                $saleOrderItem->development_type = $request->development_type;
                $saleOrderItem->expect_delivery_date = $expectDeliveryDate;
                $saleOrderItem->delivered_item_mtr = 0;
                $saleOrderItem->pending_item_mtr = $meter;
                $saleOrderItem->remarks = $remarksList[$index] ?? '';
                $saleOrderItem->created_by = Auth::id();
                $saleOrderItem->modified_by = Auth::id();
                $saleOrderItem->financial_year = currentFinancialYear();
                $saleOrderItem->created_at = date('Y-m-d H:i:s');
                $saleOrderItem->modified_at = date('Y-m-d H:i:s');
                $saleOrderItem->status = 'Active';
                $saleOrderItem->save();
            }

            DB::commit();
            Session::put('message', 'Sale Order added successfully.');
            Session::put('messageClass', 'successClass');
            return redirect()->route('sale-orders.index');
        } catch (Exception $e) {
            DB::rollBack();
            Session::put('message', 'Failed to save Sale Order. Error: '.$e->getMessage());
            Session::put('messageClass', 'errorClass');
            return back()->withInput();
        }
    }

	public function deleteSaleOrder(Request $request)
	{
		if (empty($request->FId)) {
			return response()->json(['status' => 0, 'message' => 'Invalid sale order reference.'], 422);
		}

		DB::beginTransaction();

		try {
			$saleOrderId = dec($request->FId);

			$saleOrder = SaleOrder::with('saleOrderItems')->where('status', '!=', 'Deleted')->findOrFail($saleOrderId);

			$now = now();
			$userId = Auth::id();

			$saleOrder->status = 'Deleted';
			$saleOrder->modified_by = $userId;
			$saleOrder->modified_at = $now;
			$saleOrder->save();

			foreach ($saleOrder->saleOrderItems as $item) {
				$item->status = 'Deleted';
				$item->modified_by = $userId;
				$item->modified_at = $now;
				$item->save();
			}

			DB::commit();

			return response()->json(['status' => 1, 'message' => 'Sale order deleted successfully.']);
		} catch (\Throwable $e) {
			DB::rollBack();

			return response()->json(['status' => 0, 'message' => 'Failed to delete sale order. '.$e->getMessage()], 500);
		}
	}
	
	public function printSaleOrder($id)
	{
		$saleOrderId = dec($id);

		$saleOrder = SaleOrder::with(['customer', 'saleOrderItems.unitType'])
			->where('status', '!=', 'Deleted')
			->findOrFail($saleOrderId);

		return view('frontend.saleorders.print', compact('saleOrder'));
	}

	public function showSaleOrderWorkOrderDetails($id)
	{
		Session::put('message', 'Work order details page is not ready yet.');
		Session::put('messageClass', 'errorClass');
		return redirect()->route('sale-orders.index');
	}
	
	public function ajaxSaleOrderDetails($id)
	{ 
		$saleOrderId = dec($id);

		$data = SaleOrder::where('id', $saleOrderId)->with(['customer', 'saleOrderItems'])->first();
		// echo "<pre>"; print_r($data); exit;
		return view('frontend.saleorders.partials.sale-order-items-modal', compact('data'));
	}
	
	 
	public function submitSelectedItems(Request $request)
	{
		$validator = Validator::make($request->all(), [
			'sale_order_id' => 'required',
			'dlvr_cleared_reason' => 'required',
		], [
			'sale_order_id.required' => 'Sale order not found.',
			'dlvr_cleared_reason.required' => 'Please enter clear reason.',
		]);

		if ($validator->fails()) {
			Session::put('message', $validator->errors()->first());
			Session::put('messageClass', 'errorClass');
			return redirect()->back()->withInput();
		}

		$items = [];
		$requestItems = $request->input('items', []);

		foreach ($requestItems as $encryptedSaleOrderItemId => $itemData) {
			if (isset($itemData['selected']) && $itemData['selected'] == 1 && !empty($itemData['meter']) && (float) $itemData['meter'] > 0) {
				$saleOrderItemId = dec($encryptedSaleOrderItemId);
				$items[$saleOrderItemId] = $itemData;
			}
		}

		if (empty($items)) {
			Session::put('message', 'Please select at least one item with meter.');
			Session::put('messageClass', 'errorClass');
			return redirect()->back()->withInput();
		}

		DB::beginTransaction();

		try {
			$saleOrderId = dec($request->sale_order_id);

			$oldOrder = SaleOrder::where('id', $saleOrderId)->where('status', '!=', 'Deleted')->first();

			if (empty($oldOrder)) {
				throw new Exception('Sale order not found.');
			}

			$oldSaleOrderNumber = trim($oldOrder->sale_order_number);
			$baseSaleOrderNumber = $oldSaleOrderNumber;
			$currentSeries = '';

			if (preg_match('/^(.*) \(([A-Z])\)$/', $oldSaleOrderNumber, $matches)) {
				$baseSaleOrderNumber = trim($matches[1]);
				$currentSeries = $matches[2];
			}

			if ($currentSeries == '') {
				$nextSeries = 'A';
			} else {
				$nextSeries = chr(ord($currentSeries) + 1);
			}

			$newSaleOrderNumber = $baseSaleOrderNumber.' ('.$nextSeries.')';

			while (SaleOrder::where('sale_order_number', $newSaleOrderNumber)->where('status', '!=', 'Deleted')->exists()) {
				$nextSeries = chr(ord($nextSeries) + 1);
				$newSaleOrderNumber = $baseSaleOrderNumber.' ('.$nextSeries.')';
			}

			$newOrder = $oldOrder->replicate();
			$newOrder->sale_order_number = $newSaleOrderNumber;
			$newOrder->items = count($items);
			$newOrder->created_by = Auth::id();
			$newOrder->modified_by = Auth::id();
			$newOrder->created_at = date('Y-m-d H:i:s');
			$newOrder->modified_at = date('Y-m-d H:i:s');
			$newOrder->status = 'Active';
			$newOrder->save();

			$selectedIds = array_keys($items);
			$oldItems = SaleOrderItem::whereIn('id', $selectedIds)->where('status', '!=', 'Deleted')->get()->keyBy('id');

			foreach ($items as $saleOrderItemId => $itemData) {
				if (!isset($oldItems[$saleOrderItemId])) {
					continue;
				}

				$oldItem = $oldItems[$saleOrderItemId];
				$meter = (float) $itemData['meter'];

				$newItem = $oldItem->replicate();
				$newItem->sale_order_id = $newOrder->id;
				$newItem->meter = $meter;
				$newItem->delivered_item_mtr = 0;
				$newItem->pending_item_mtr = $meter;
				$newItem->is_work_order_created = 0;
				$newItem->is_work_final_completed = '0';
				$newItem->is_work_final_dlvr_completed = '0';
				$newItem->dlvr_cleared_reason = null;
				$newItem->dlvr_clear_date = null;
				$newItem->dlvr_cleared_by = null;
				$newItem->created_by = Auth::id();
				$newItem->modified_by = Auth::id();
				$newItem->created_at = date('Y-m-d H:i:s');
				$newItem->modified_at = date('Y-m-d H:i:s');
				$newItem->status = 'Active';
				$newItem->save();

				$user = User::find(Auth::id());

				$oldItem->dlvr_cleared_by = !empty($user) ? $user->individual_id : null;
				$oldItem->is_work_final_dlvr_completed = '1';
				$oldItem->is_work_final_completed = '1';
				$oldItem->dlvr_cleared_reason = $request->dlvr_cleared_reason;
				$oldItem->dlvr_clear_date = date('Y-m-d H:i:s');
				$oldItem->modified_by = Auth::id();
				$oldItem->modified_at = date('Y-m-d H:i:s');
				$oldItem->save();
			}

			DB::commit();

			Session::put('message', 'Sale order and selected items duplicated successfully.');
			Session::put('messageClass', 'successClass');
			return redirect()->route('sale-orders.index');
		} catch (Exception $e) {
			DB::rollBack();

			Session::put('message', 'Failed to submit selected items. Error: '.$e->getMessage());
			Session::put('messageClass', 'errorClass');
			return redirect()->back()->withInput();
		}
	}


    public function cancelSaleOrderItem(Request $request)
	{
		$validator = Validator::make($request->all(), [
			'FId' => 'required',
			'soItemId' => 'required',
			'cancel_reason' => 'required',
		], [
			'FId.required' => 'Sale order not found.',
			'soItemId.required' => 'Sale order item not found.',
			'cancel_reason.required' => 'Please enter cancellation comment.',
		]);

		if ($validator->fails()) {
			Session::put('message', $validator->errors()->first());
			Session::put('messageClass', 'errorClass');
			return redirect()->back()->withInput();
		}

		DB::beginTransaction();

		try {
			$saleOrderId        = dec($request->FId);
			$saleOrderItemId    = dec($request->soItemId);

			$saleOrderItem = SaleOrderItem::where('sale_order_id', $saleOrderId)
				->where('id', $saleOrderItemId)
				->where('status', 'Active')
				->first();

			if (empty($saleOrderItem)) {
				throw new Exception('Sale order item not found.');
			}

			$saleOrderItem->status = 'Deleted';
			$saleOrderItem->cancel_reason = $request->cancel_reason;
			$saleOrderItem->modified_by = Auth::id();
			$saleOrderItem->modified_at = date('Y-m-d H:i:s');
			$saleOrderItem->save();

			DB::commit();

			Session::put('message', 'Sale order item deleted successfully.');
			Session::put('messageClass', 'successClass');
			return redirect()->back();
		} catch (Exception $e) {
			DB::rollBack();

			Session::put('message', 'Failed to cancel sale order item. Error: '.$e->getMessage());
			Session::put('messageClass', 'errorClass');
			return redirect()->back()->withInput();
		}
	}

    public function clearSaleOrderItem(Request $request)
	{
		$validator = Validator::make($request->all(), [
			'FId' => 'required',
			'soItemId' => 'required',
			'dlvr_cleared_reason' => 'required',
		], [
			'FId.required' => 'Sale order not found.',
			'soItemId.required' => 'Sale order item not found.',
			'dlvr_cleared_reason.required' => 'Please enter clear reason.',
		]);

		if ($validator->fails()) {
			Session::put('message', $validator->errors()->first());
			Session::put('messageClass', 'errorClass');
			return redirect()->back()->withInput();
		}

		DB::beginTransaction();

		try {
			$saleOrderId = dec($request->FId);
			$saleOrderItemId = dec($request->soItemId);

			$saleOrderItem = SaleOrderItem::where('sale_order_id', $saleOrderId)
				->where('id', $saleOrderItemId)
				->where('status', 'Active')
				->first();

			if (empty($saleOrderItem)) {
				throw new Exception('Sale order item not found.');
			}

			$user = User::find(Auth::id());

			$saleOrderItem->is_work_final_dlvr_completed = '1';
			$saleOrderItem->is_work_final_completed = '1';
			$saleOrderItem->dlvr_cleared_reason = $request->dlvr_cleared_reason;
			$saleOrderItem->dlvr_clear_date = date('Y-m-d H:i:s');
			$saleOrderItem->dlvr_cleared_by = !empty($user) ? $user->individual_id : Auth::id();
			$saleOrderItem->modified_by = Auth::id();
			$saleOrderItem->modified_at = date('Y-m-d H:i:s');
			$saleOrderItem->save();

			DB::commit();

			Session::put('message', 'Sale order item cleared successfully.');
			Session::put('messageClass', 'successClass');
			return redirect()->back();
		} catch (Exception $e) {
			DB::rollBack();

			Session::put('message', 'Failed to clear sale order item. Error: '.$e->getMessage());
			Session::put('messageClass', 'errorClass');
			return redirect()->back()->withInput();
		}
	}

    public function updateSaleOrder(Request $request)
	{
		$validator = Validator::make($request->all(), [
			'FId' => 'required',
			'sale_order_number' => 'required',
			'sale_order_date' => 'required',
			'sales_order' => 'required',
			'order_priority' => 'required',
			'development_type' => 'required',
		], [
			'FId.required' => 'Sale order not found.',
			'sale_order_number.required' => 'Please enter Sale Order Number.',
			'sale_order_date.required' => 'Please select Sale Order Date.',
			'sales_order.required' => 'Please select Sales Order.',
			'order_priority.required' => 'Please select Priority.',
			'development_type.required' => 'Please select Development Type.',
		]);

		if ($validator->fails()) {
			Session::put('message', $validator->errors()->first());
			Session::put('messageClass', 'errorClass');
			return redirect()->back()->withInput();
		}

		DB::beginTransaction();

		try {
			$saleOrderId = dec($request->FId);

			$saleOrder = SaleOrder::where('id', $saleOrderId)->where('status', '!=', 'Deleted')->first();

			if (empty($saleOrder)) {
				throw new Exception('Sale order not found.');
			}

			$oldSaleOrder = SaleOrder::where('sale_order_number', $request->sale_order_number)
				->where('id', '!=', $saleOrderId)
				->where('status', '!=', 'Deleted')
				->first();

			if (!empty($oldSaleOrder)) {
				throw new Exception('Sale Order Number already exists.');
			}

			$saleOrderFields = [
				'customer_id', 'billing_id', 'shipping_id', 'sale_order_type',
				'sale_order_date', 'sale_order_number', 'sales_order', 'sale_order_from',
				'order_priority', 'development_type', 'order_slip_file', 'billing_address',
				'shipping_address', 'lot_number', 'ind_agent_id', 'order_by_employee',
			];

			$saleOrderDateFields = ['sale_order_date'];

			foreach ($saleOrderFields as $fieldName) {
				$fieldValue = $request->input($fieldName);

				if (in_array($fieldName, $saleOrderDateFields) && $fieldValue != '' && strpos($fieldValue, '-') !== false) {
					$datePart = substr($fieldValue, 0, 10);
					$timePart = trim(substr($fieldValue, 10));
					$dateParts = explode('-', $datePart);

					if (count($dateParts) == 3) {
						$fieldValue = $dateParts[2].'-'.$dateParts[1].'-'.$dateParts[0];

						if ($timePart != '') {
							$fieldValue = $fieldValue.' '.$timePart;
						}
					}
				}

				$saleOrder->$fieldName = $fieldValue;
			}

			$saleOrder->modified_by = Auth::id();
			$saleOrder->modified_at = date('Y-m-d H:i:s');
			$saleOrder->save();

			DB::commit();

			Session::put('message', 'Sale order updated successfully.');
			Session::put('messageClass', 'successClass');
			return redirect()->back();
		} catch (Exception $e) {
			DB::rollBack();

			Session::put('message', 'Failed to update sale order. Error: '.$e->getMessage());
			Session::put('messageClass', 'errorClass');
			return redirect()->back()->withInput();
		}
	}

    public function updateSaleOrderItem(Request $request)
	{
		$validator = Validator::make($request->all(), [
			'FId' => 'required',
			'soItemId' => 'required',
			'meter' => 'required',
			'rate' => 'required',
		], [
			'FId.required' => 'Sale order not found.',
			'soItemId.required' => 'Sale order item not found.',
			'meter.required' => 'Please enter meter.',
			'rate.required' => 'Please enter rate.',
		]);

		if ($validator->fails()) {
			Session::put('message', $validator->errors()->first());
			Session::put('messageClass', 'errorClass');
			return redirect()->back()->withInput();
		}

		DB::beginTransaction();

		try {
			$saleOrderId = dec($request->FId);
			$saleOrderItemId = dec($request->soItemId);

			$saleOrderItem = SaleOrderItem::where('sale_order_id', $saleOrderId)
				->where('id', $saleOrderItemId)
				->where('status', 'Active')
				->first();

			if (empty($saleOrderItem)) {
				throw new Exception('Sale order item not found.');
			}

			$saleOrderItemFields = [
				'item_id', 'unit_type_id', 'item_name', 'order_item_priority', 'pcs', 'cut', 'meter',
				'rate', 'amount',
				'grey_quality', 'dyeing_color', 'coating_type', 'extra_job', 'print_job',
				'packing_roll_length', 'final_dispatch_width', 'tube_width', 'development_type',
				'expect_delivery_date', 'remarks',
			];

			$saleOrderItemDateFields = ['expect_delivery_date'];

			foreach ($saleOrderItemFields as $fieldName) {
				$fieldValue = $request->input($fieldName);

				if (in_array($fieldName, $saleOrderItemDateFields) && $fieldValue != '' && strpos($fieldValue, '-') !== false) {
					$datePart = substr($fieldValue, 0, 10);
					$timePart = trim(substr($fieldValue, 10));
					$dateParts = explode('-', $datePart);

					if (count($dateParts) == 3) {
						$fieldValue = $dateParts[2].'-'.$dateParts[1].'-'.$dateParts[0];

						if ($timePart != '') {
							$fieldValue = $fieldValue.' '.$timePart;
						}
					}
				}

				$saleOrderItem->$fieldName = $fieldValue;
			}

			if ($request->amount == '') {
				$saleOrderItem->amount = (float) $request->meter * (float) $request->rate;
			}

			if ($request->net_amount == '') {
				$saleOrderItem->net_amount = $saleOrderItem->amount;
			}

			if ($request->total_price == '') {
				$saleOrderItem->total_price = $saleOrderItem->amount;
			}

			$item = Item::where('item_id', $saleOrderItem->item_id)->first();
			$unitType = UnitType::where('unit_type_id', $saleOrderItem->unit_type_id)->first();

			if (!empty($item)) {
				$saleOrderItem->item_type_id = $item->item_type_id;
			}

			if (!empty($unitType)) {
				$saleOrderItem->unit = $unitType->unit_type_name;
			}

			$saleOrderItem->net_amount = $saleOrderItem->amount;
			$saleOrderItem->total_price = $saleOrderItem->amount;
			$saleOrderItem->pending_item_mtr = (float) $saleOrderItem->meter - (float) $saleOrderItem->delivered_item_mtr;
			if ($saleOrderItem->pending_item_mtr < 0) {
				$saleOrderItem->pending_item_mtr = 0;
			}
			$saleOrderItem->edited_by = Auth::id();
			$saleOrderItem->modified_by = Auth::id();
			$saleOrderItem->modified_at = date('Y-m-d H:i:s');
			$saleOrderItem->save();

			$totalAmount = SaleOrderItem::where('sale_order_id', $saleOrderId)->where('status', 'Active')->sum('amount');
			$totalItems = SaleOrderItem::where('sale_order_id', $saleOrderId)->where('status', 'Active')->count();

			$saleOrder = SaleOrder::where('id', $saleOrderId)->where('status', '!=', 'Deleted')->first();

			if (!empty($saleOrder)) {
				$saleOrder->items = $totalItems;
				$saleOrder->subtotal = $totalAmount;
				$saleOrder->amount = $totalAmount;
				$saleOrder->total = $totalAmount;
				$saleOrder->total_amount_without_roundoff = $totalAmount;
				$saleOrder->roundoff = round($totalAmount) - $totalAmount;
				$saleOrder->total_amount_after_roundoff = round($totalAmount);
				$saleOrder->modified_by = Auth::id();
				$saleOrder->modified_at = date('Y-m-d H:i:s');
				$saleOrder->save();
			}

			DB::commit();

			Session::put('message', 'Sale order item updated successfully.');
			Session::put('messageClass', 'successClass');
			return redirect()->back();
		} catch (Exception $e) {
			DB::rollBack();

			Session::put('message', 'Failed to update sale order item. Error: '.$e->getMessage());
			Session::put('messageClass', 'errorClass');
			return redirect()->back()->withInput();
		}
	}

    public function show_sale_order_reports(Request $request)
	{	 
		ini_set('memory_limit', '2048M');
		set_time_limit(300);

		$perPage = (int) config('global.PER_PAGE', 25);
		$priorityArr = config('global.priorityArr', ['Low', 'Medium', 'High', 'Urgent']);
		$qsearch = trim($request->input('qsearch', ''));
		$qnamesearch = trim($request->input('qnamesearch', ''));
		$itemId = trim($request->input('itemId', ''));
		$ordNumSearch = trim($request->input('ordNumSearch', ''));
		$colorSearch = trim($request->input('colorSearch', ''));
		$fromDate = trim($request->input('from_date', ''));
		$toDate = trim($request->input('to_date', ''));
		$priority = $request->input('priority', '');
		$sbtSearch = $request->input('sbtSearch', 'Search');
		$sortingType = $request->input('sortingType', '');

		$formatSearchDate = function ($dateValue) {
			if ($dateValue == '') {
				return '';
			}

			$dateParts = explode('-', $dateValue);
			if (count($dateParts) == 3) {
				return $dateParts[2].'-'.$dateParts[1].'-'.$dateParts[0];
			}

			return date('Y-m-d', strtotime($dateValue));
		};

		$query = SaleOrderItem::with(['saleOrder.customer', 'unitType', 'item'])
			->where('sale_order_items.is_deleted', 0)
			->where('sale_order_items.status', 'Active')
			->whereHas('saleOrder', function ($saleOrderQuery) {
				$saleOrderQuery->where('status', '!=', 'Deleted');
			});

		if ($priority == '1') {
			$query->where('sale_order_items.is_work_final_dlvr_completed', '1');
		} elseif ($priority == '2') {
			$query->where('sale_order_items.is_work_final_dlvr_completed', '0');
		}

		if ($qsearch != '') {
			$query->whereHas('saleOrder.customer', function ($customerQuery) use ($qsearch) {
				$customerQuery->where('name', 'like', '%'.$qsearch.'%');
				$customerQuery->orWhere('company_name', 'like', '%'.$qsearch.'%');
				$customerQuery->orWhere('phone', 'like', '%'.$qsearch.'%');
			});
		}

		if ($ordNumSearch != '') {
			$orderNumbers = array_filter(array_map('trim', explode(',', $ordNumSearch)));
			$query->whereHas('saleOrder', function ($saleOrderQuery) use ($orderNumbers, $ordNumSearch) {
				if (count($orderNumbers) > 1) {
					$saleOrderQuery->whereIn('sale_order_number', $orderNumbers);
				} else {
					$saleOrderQuery->where('sale_order_number', 'like', '%'.$ordNumSearch.'%');
				}
			});
		}

		if ($itemId != '' && $qnamesearch != '') {
			$query->where('sale_order_items.item_id', $itemId);
		} elseif ($qnamesearch != '') {
			$query->where(function ($itemQuery) use ($qnamesearch) {
				$itemQuery->where('sale_order_items.item_name', 'like', '%'.$qnamesearch.'%');
				$itemQuery->orWhere('sale_order_items.grey_quality', 'like', '%'.$qnamesearch.'%');
			});
		}

		if ($colorSearch != '') {
			$query->where('sale_order_items.dyeing_color', 'like', '%'.$colorSearch.'%');
		}

		if ($fromDate != '' || $toDate != '') {
			$fromDateSearch = $formatSearchDate($fromDate);
			$toDateSearch = $formatSearchDate($toDate);

			if ($fromDateSearch != '') {
				$query->whereDate('sale_order_items.expect_delivery_date', '>=', $fromDateSearch);
			}

			if ($toDateSearch != '') {
				$query->whereDate('sale_order_items.expect_delivery_date', '<=', $toDateSearch);
			}
		}

		if ($sortingType == 'AZ') {
			$query->leftJoin('sale_orders', 'sale_orders.id', '=', 'sale_order_items.sale_order_id')
				->leftJoin('individuals', 'individuals.id', '=', 'sale_orders.customer_id')
				->select('sale_order_items.*')
				->orderByRaw('COALESCE(individuals.name, individuals.company_name, "") ASC');
		} elseif ($sortingType == 'ZA') {
			$query->leftJoin('sale_orders', 'sale_orders.id', '=', 'sale_order_items.sale_order_id')
				->leftJoin('individuals', 'individuals.id', '=', 'sale_orders.customer_id')
				->select('sale_order_items.*')
				->orderByRaw('COALESCE(individuals.name, individuals.company_name, "") DESC');
		} else {
			$query->orderBy('sale_order_items.id', 'desc');
		}

		if ($sbtSearch == 'ExportToExcel') {
			try {
				$dataSOI = $query->get();
				return Excel::download(new SaleOrderItemExport($dataSOI), 'sale_order_items.xlsx');
			} catch (Exception $e) {
				Log::error('Excel export error: '.$e->getMessage());
				return response('Error generating Excel', 500);
			}
		}

		if ($sbtSearch == 'ExportToPdf') {
			try {
				$dataSOI = $query
					->whereHas('saleOrder', function ($saleOrderQuery) {
						$saleOrderQuery->whereNotNull('customer_id');
					})
					->get();

				$pdf = Pdf::loadView('frontend.saleorders.sale-order-item-report-list-pdf', compact('dataSOI', 'qsearch', 'fromDate', 'toDate'));
				$pdf->setPaper('A4', 'landscape');

				return $pdf->download('sale_order_items_report.pdf');
			} catch (\Throwable $e) {
				Log::error('PDF export error: '.$e->getMessage());
				Log::error($e->getTraceAsString());

				if (env('APP_DEBUG')) {
					return response()->make(
						nl2br(htmlentities($e->getMessage()."\n\n".$e->getTraceAsString())),
						500
					);
				}

				return response('Error generating PDF', 500);
			}
		}

		$dataSOI = $query->paginate($perPage)->appends($request->except('_token'));
		$itemsData = Item::where('status', 'Active')->where('item_type_id', '8')->select('item_id', 'item_name')->get();
		$unitTypes = UnitType::where('status', 'Active')->orderBy('unit_type_name')->get();
		$coatings = Coting::where('status', 'Active')->orderBy('name')->get();

		return view('frontend.saleorders.show-saleorder-reports', compact('dataSOI', 'qsearch', 'colorSearch', 'qnamesearch', 'fromDate', 'toDate', 'priorityArr', 'ordNumSearch', 'priority', 'itemsData', 'sortingType', 'itemId', 'unitTypes', 'coatings'));
	}

    public function show_sale_order_items(Request $request)
	{	
		$perPage = (int) config('global.PER_PAGE', 25);
		$priorityArr = config('global.priorityArr', ['Low', 'Medium', 'High', 'Urgent']);
		$qsearch = trim($request->input('qsearch', ''));
		$qnamesearch = trim($request->input('qnamesearch', ''));
		$ordNumSearch = trim($request->input('ordNumSearch', ''));
		$colorSearch = trim($request->input('colorSearch', ''));
		$createDate = trim($request->input('create_date', ''));
	  	$fromDate = trim($request->input('from_date', ''));
	  	$toDate = trim($request->input('to_date', ''));
		$priority = $request->input('priority', '');
		$sbtSearch = $request->input('sbtSearch', 'Search');

		$formatSearchDate = function ($dateValue) {
			if ($dateValue == '') {
				return '';
			}

			$dateParts = explode('-', $dateValue);
			if (count($dateParts) == 3) {
				return $dateParts[2].'-'.$dateParts[1].'-'.$dateParts[0];
			}

			return date('Y-m-d', strtotime($dateValue));
		};

		$query = SaleOrderItem::with(['saleOrder.customer', 'unitType', 'item'])
			->where('sale_order_items.is_deleted', 0)
			->where('sale_order_items.is_work_order_created', 0)
			->whereNull('sale_order_items.dlvr_cleared_by')
			->where('sale_order_items.status', 'Active')
			->whereHas('saleOrder', function ($saleOrderQuery) {
				$saleOrderQuery->where('status', '!=', 'Deleted');
			});
		
		if (!empty($qsearch)) 
		{ 
			$query->whereHas('saleOrder.customer', function ($customerQuery) use ($qsearch) {
				$customerQuery->where('name', 'like', '%'.$qsearch.'%');
				$customerQuery->orWhere('company_name', 'like', '%'.$qsearch.'%');
				$customerQuery->orWhere('phone', 'like', '%'.$qsearch.'%');
			});
		}
		
		if (!empty($colorSearch)) 
		{ 
			$query->where('sale_order_items.dyeing_color', 'like', '%'.$colorSearch.'%');
		}
		
		if (!empty($ordNumSearch)) 
		{
			$orderNumbers = array_filter(array_map('trim', explode(',', $ordNumSearch)));
			$query->whereHas('saleOrder', function ($saleOrderQuery) use ($orderNumbers, $ordNumSearch) {
				if (count($orderNumbers) > 1) {
					$saleOrderQuery->whereIn('sale_order_number', $orderNumbers);
				} else {
					$saleOrderQuery->where('sale_order_number', 'like', '%'.$ordNumSearch.'%');
				}
			});
		}
		
		if (!empty($qnamesearch)) 
		{			 
			$query->where(function ($itemQuery) use ($qnamesearch) {
				$itemQuery->where('sale_order_items.item_name', 'like', '%'.$qnamesearch.'%');
				$itemQuery->orWhere('sale_order_items.grey_quality', 'like', '%'.$qnamesearch.'%');
			});
		}
		
		if ($fromDate != '' || $toDate != '')
		{
			$fromDateSearch = $formatSearchDate($fromDate);
			$toDateSearch = $formatSearchDate($toDate);

			if ($fromDateSearch != '') {
				$query->whereDate('sale_order_items.expect_delivery_date', '>=', $fromDateSearch);
			}

			if ($toDateSearch != '') {
				$query->whereDate('sale_order_items.expect_delivery_date', '<=', $toDateSearch);
			}
		}
		
		if (isset($priority) && $priority !== '') {
			$query->where('sale_order_items.order_item_priority', $priority);
		}
		
		if ($createDate != '')
		{			
			$query->whereDate('sale_order_items.created_at', $formatSearchDate($createDate));
		} 
		
		if ($sbtSearch == 'ExportToExcel') 
		{
			try {
				$dataSOI = $query->get();
				return Excel::download(new SaleOrderItemExport($dataSOI), 'sale_order_items.xlsx');
			} catch (\Exception $e) {
				\Log::error('Exception: ' . $e->getMessage());
				return response('Error generating Excel', 500);
			}
		}   
		/*
			$sql = $query->toSql();
			$bindings = $query->getBindings(); 
			$sqlWithValues = vsprintf(str_replace('?', "'%s'", $sql), $bindings); 
			dd($sqlWithValues);
		*/
		
		  
		$dataSOI = $query->orderBy('sale_order_items.id', 'desc')
			->paginate($perPage)
			->appends($request->except('_token'));
		
		return view('frontend.saleorders.show-saleorderitems', compact("dataSOI", "qsearch", "createDate", "qnamesearch", "fromDate", "toDate", "priorityArr", "ordNumSearch", "priority", "colorSearch"));
	}

	public function updateCoatingRequirement(Request $request)
	{
		$saleOrderItem = SaleOrderItem::where('id', $request->input('id'))
			->where('status', '!=', 'Deleted')
			->first();

		if (empty($saleOrderItem)) {
			return response()->json(['success' => false, 'message' => 'Sale order item not found.'], 404);
		}

		$saleOrderItem->coating_type = trim($request->input('selectedValue', ''));
		$saleOrderItem->change_coating_by = Auth::id() ?? 0;
		$saleOrderItem->modified_by = Auth::id();
		$saleOrderItem->modified_at = now();
		$saleOrderItem->save();

		return response()->json(['success' => true]);
	}
 
	public function SetReasonForSaleOrderItem(Request $request)
    {
		// echo "<pre>"; print_r($request->all()); exit;
		$validator = Validator::make($request->all(), [
			"FId" 					=> "required",
			"soItemId" 				=> "required",
			"pending_reason" 		=> "required",			 
		], [
			"FId.required" 			=> "Sale order id not found.",
			"soItemId.required" 	=> "Sale Order item id Not Found",
			"pending_reason.required" => "Reason is required", 		 
		]);

		if ($validator->fails()) {
			$error = $validator->errors()->first();
			Session::put('message', $validator->messages()->first());
			Session::put("messageClass", "errorClass");
			return redirect()->back()->withInput();
		}
		
		$userId = Auth::id();
		 
		
		$saleOrderId       	= $request->FId;
		$soItemId  			= $request->soItemId;
		$clrReason 			= $request->pending_reason;
		
		DB::beginTransaction();
		try {
			 
			$data['reason_from_page'] 		= 'cwo';
			$data['sale_order_id'] 			= $saleOrderId;
			$data['sale_order_item_id'] 	= $soItemId;
			$data['work_order_id'] 			= null;
			$data['reason'] 				= $clrReason;			 
			$data['created_at'] 			= now(); 
			$data['created_by'] 			= $userId;
			Reason::create($data);
			
			DB::commit();
			Session::put('message', 'Reason created successfully..');
			Session::put("messageClass", "successClass");
			return redirect()->back()->withInput();
		} catch (\Exception $e) {
			DB::rollBack();
			Session::put('message', 'Something went wrong: ' . $e->getMessage());
			Session::put("messageClass", "errorClass");
			return redirect()->back()->withInput();
		}
    }	



	public function getReasonHistory($soItemId)
	{
		$reasons = Reason::where('sale_order_item_id', $soItemId)
			->where('reason_from_page', 'cwo')
			->where('status', '!=', 'Deleted')
			->orderBy('created_at', 'desc')
			->get(['reason', 'created_at']);

		return response()->json($reasons->map(function ($item) {
			return [
				'reason' => $item->reason,
				'created' => !empty($item->created_at)
					? date('d-m-Y H:i:s', strtotime($item->created_at))
					: '',
			];
		}));
	}

	public function getWorkReasonHistory($woId)
	{
		$reasons = Reason::where('work_order_id', $woId)
			->where('reason_from_page', 'wo')
			->where('status', '!=', 'Deleted')
			->orderBy('created_at', 'desc')
			->get(['reason', 'created_at']);

		return response()->json($reasons->map(function ($item) {
			return [
				'reason' => $item->reason,
				'created' => !empty($item->created_at)
					? date('d-m-Y H:i:s', strtotime($item->created_at))
					: '',
			];
		}));
	}

	public function SetReasonForWorkOrderItem(Request $request)
    {
		$validator = Validator::make($request->all(), [
			"FId" 					=> "required", 
			"pending_reason" 		=> "required",			 
		], [
			"FId.required" 			=> "Work order id not found.", 
			"pending_reason.required" => "Reason is required", 		 
		]);

		if ($validator->fails()) {
			Session::put('message', $validator->messages()->first());
			Session::put("messageClass", "errorClass");
			return redirect()->back()->withInput();
		}
		
		$userId = Auth::id();
		$userD  = User::find($userId);
		$indId  = $userD->individual_id ?? $userId;
		
		$workOrderId = $request->FId; 
		$clrReason = $request->pending_reason;
		
		DB::beginTransaction();
		try {
			$data['reason_from_page'] = 'wo';
			$data['sale_order_id'] = null;
			$data['sale_order_item_id'] = null;
			$data['work_order_id'] = $workOrderId; 
			$data['reason'] = $clrReason;			 
			$data['created_at'] = now();
			$data['modified_at'] = now();
			$data['created_by'] = $indId;
			Reason::create($data);
			
			DB::commit();
			Session::put('message', 'Reason created successfully..');
			Session::put("messageClass", "successClass");
			return redirect()->back()->withInput();
		} catch (\Exception $e) {
			DB::rollBack();
			Session::put('message', 'Something went wrong: ' . $e->getMessage());
			Session::put("messageClass", "errorClass");
			return redirect()->back()->withInput();
		}
    }

}


















