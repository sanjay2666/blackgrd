<?php

namespace App\Http\Controllers;

use App\Models\ProcessItem;
use App\Models\ProcessRequirement;
use App\Models\SaleOrder;
use App\Models\SaleOrderItem;
use App\Models\WorkOrder;
use App\Models\WorkOrderItem;
use App\Models\Individual;
use App\Models\User;
use App\Models\Item;
use App\Models\ItemType;
use App\Models\UnitType;
use App\Models\Machine;
use App\Models\Warehouse;
use App\Models\WarehouseBalanceItem;
use App\Models\WarehouseItem;
use App\Models\WarehouseOutItem;
use App\Models\WarehouseItemStock;
use App\Models\WorkProcessRequirement;
use App\Models\WorkInspection;
use App\Models\WorkInspectionDetail;
use App\Models\ItemYarnRequirement;
use App\Models\Company;
use App\Models\GatePass;
use App\Models\FabricFaultReason;
use Dompdf\Dompdf;
use Dompdf\Options;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;

class WorkOrderController extends Controller
{
    
	public function index(Request $request)
	{ 
		$workOrderPageStartedAt = microtime(true);
		$workOrderQueryCount = 0;
		$workOrderQueryTime = 0;
		$workOrderSlowQueries = [];

		DB::listen(function ($query) use (&$workOrderQueryCount, &$workOrderQueryTime, &$workOrderSlowQueries) {
			$workOrderQueryCount++;
			$workOrderQueryTime += $query->time;

			if ($query->time >= 100) {
				$workOrderSlowQueries[] = [
					'time_ms' => round($query->time, 2),
					'sql' => $query->sql,
					'bindings' => $query->bindings,
				];
			}
		});

		app()->terminating(function () use ($request, $workOrderPageStartedAt, &$workOrderQueryCount, &$workOrderQueryTime, &$workOrderSlowQueries) {
			Log::info('Work order index page timing', [
				'url' => $request->fullUrl(),
				'user_id' => Auth::id(),
				'total_ms' => round((microtime(true) - $workOrderPageStartedAt) * 1000, 2),
				'db_query_count' => $workOrderQueryCount,
				'db_total_ms' => round($workOrderQueryTime, 2),
				'php_render_ms' => round(((microtime(true) - $workOrderPageStartedAt) * 1000) - $workOrderQueryTime, 2),
				'memory_peak_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
				'slow_queries' => array_slice($workOrderSlowQueries, 0, 10),
			]);
		});

		Session::put('previous_url', url()->full());
		if (!Session::has('workorders_return_url')) 
		{
			Session::put('workorders_return_url', $request->fullUrl());
		}
		 
		$userId 		= Auth::id();
		$userD 			= User::find($userId);	 
		$userIndId 		= $userD->individual_id;
		 
		
		$cusSearch 		= trim((string) $request->input('cus_search', ''));
		$individualId 	= trim((string) $request->input('individual_id', ''));
		if (empty($cusSearch)) {
			$individualId = '';
		}
		$itemSearch 	= trim((string) $request->input('item_search', ''));
		$ordNumSearch 	= trim((string) $request->input('ordNumSearch', ''));		
		$colorSearch 	= trim((string) $request->input('colorSearch', ''));
		$LotNumSearch 	= trim((string) $request->input('LotNumSearch', ''));
		$recLotNumSerch	= trim((string) $request->input('recLotNumSearch', ''));
		$priority 		= trim((string) $request->input('priority', ''));
		$workStatus 	= trim((string) $request->input('work_status', ''));
		$search_process_id = $request->search_process_id;
		$sbtSearch      = $request->sbtSearch;
        $fromDate 		= $request->from_date;
		$toDate 		= $request->to_date;
		$proceStatus 	= trim((string) $request->input('process_status', ''));  
		
		 
		
		$yearRecord = trim((string) $request->year_record);
		if ($yearRecord === '') {
			$yearRecord = (int) date('n') >= 4 ? (string) date('Y') : (string) ((int) date('Y') - 1);
		}

		if (preg_match('/^\d{4}$/', $yearRecord) && (int) $yearRecord > 2100) {
			$selectedFinancialYear = $yearRecord;
			$yearRecord = '20'.substr($selectedFinancialYear, 0, 2);
		} else {
			$yearStart = (int) $yearRecord;
			$selectedFinancialYear = substr((string) $yearStart, -2).substr((string) ($yearStart + 1), -2);
			$yearRecord = (string) $yearStart;
		}
		
		
		$processTypeId  = Individual::where('id', $userIndId)->where('type', 'master')->where('status', 'Active')->value('process_type_id');
		
		 
		$arrr = 0;
		if (!empty($search_process_id)) 
		{
			$search_process_filter =  array_filter($search_process_id);
			$arrr = count($search_process_filter);
		}  
		
		$query = WorkOrder::where('status', 'Active')
			->where('financial_year', $selectedFinancialYear)
			->with(['WorkOrderItem' => function ($query) {
				$query->select(
					'id', 'work_order_id', 'customer_id', 'sale_order_id',
					'sale_order_item_id',
					'item_id', 'meter', 'grey_quality', 'dyeing_color', 'coating_type',
					'extra_job', 'print_job', 'expect_delivery_date', 'order_item_priority'
				);

				$query->with(['SaleOrder' => function ($q) {
					$q->select('id', 'sale_order_date', 'sale_order_number')->where('status', 'Active');
				}]);

				$query->with(['SaleOrderItem' => function ($q) {
					$q->select('id', 'sale_order_id', 'dlvr_cleared_by', 'dlvr_clear_date', 'is_work_completed', 'pending_item_mtr', 'dlvr_cleared_reason', 'remarks')
					  ->where('status', 'Active');
				}]);
			}])
			->with('ProcessType') 
			->with('Item:item_id,item_name,item_code,internal_item_name,item_type_id,unit_type_id,hsncode,status,is_jobwork,is_lab_test_required,item_width,remarks')
			->with('WorkReqSend:id,process_type_id,name,type,phone,nick_name,whatsapp,email,status')
			// ->with('WorkInspection')
			->with('GatepassGenratedByWarehouseUser')
			->with('WorkMachine:id,name')
			->with(['WorkProcessRequirement' => function ($query) {
				$query->select(
					'id',
					'work_order_id',
					'req_lot_no',
					'item_id',
					'process_type_id',
					'req_fabric_type',
					'lab_req_status',
					'is_lab_test_complete',
					'dyeing_machine_id',
					DB::raw('created_at as created'),
					'status'
				)->orderBy('id');
			}])
			->with(['WarehouseOutItem' => function ($query) {
				$query->select('id', 'warehouse_item_id', 'work_order_id', 'work_pro_req_id', 'item_type_id', 'insp_taka_number', 'item_qty', 'is_item_return_whouse')
					->with(['WarehouseItem:id,beam_meter']);
			}])
			->with(['GatePass' => function ($query) {
				$query->with(['WorkInspection:id,insp_comment,status,is_deleted']);
			}])
			->with('WorkInspectionOne')
			->with('DepartmentReturnRequest')
			->orderByDesc('id');
			
		 
		$permissions = [
			'2' => [1, 2],
			'4' => [4, 6, 7],
		];
		if ($userId != 2) 
		{
			if ($userId == 21) {
				$query->whereIn('process_type_id', [3]); // special case
			} 
			elseif ($userId == 11) {
				$query->whereIn('process_type_id', [1, 2, 3, 4, 6, 7]); // special case
			} 
			elseif ($userId == 13 && $processTypeId == '4') {
				$query->whereIn('process_type_id', [4, 6, 7]); // special case
			} 
			elseif ($userId == 26 && $processTypeId == '4') {
				$query->whereIn('process_type_id', [4, 6, 7]); // special case
			} 
			elseif (isset($permissions[$processTypeId])) {
				$query->whereIn('process_type_id', $permissions[$processTypeId]);
			} 
			else {
				$query->where('process_type_id', $processTypeId);
			}
		}	
		
		if ($workStatus =='1' || $workStatus =='') 	
		{ 
			$query->where('insp_status', '=', 'Pending');
		}
		 
		if ($workStatus =='2') 	
		{ 
			$query->where('insp_status', '=', 'Complete');
		}
		
		if (!empty($proceStatus)) 	
		{ 
			$query->whereNull('master_ind_id')->where('is_item_received_from_warehouse', '=', 'Yes')->where('is_work_require_request_accepted', '=', 'Yes')->where('insp_status', '=', 'Pending')->where('work_status', '=', 'Pending');
			 
		}			
		
		if (!empty($cusSearch)) 
		{ 
			$individualIds = array_filter(explode(',', $individualId));
			if (!empty($individualIds)) {
				$query->whereHas('WorkOrderItem', function ($q) use ($individualIds) {
					$q->whereIn('customer_id', $individualIds)->where('status', 'Active');
				});
			} else {				 
				$query->whereRaw('0=1');  
			}
			
		}
		if (!empty($colorSearch)) 
		{
			$query->whereHas('WorkOrderItem', function ($q) use ($colorSearch) {
					$q->where('dyeing_color', '=', $colorSearch)->where('status', 'Active');
			});
		}
 
		 
		if (!empty($itemSearch)) 
		{
			$query->whereHas('Item', function ($itemQuery) use ($itemSearch) {
					$itemQuery->whereRaw("CONCAT(COALESCE(item_name, ''), COALESCE(internal_item_name, ''), COALESCE(item_code, '')) LIKE ?", ['%' . $itemSearch . '%']);
				})
				->where('status', 'Active');
		}
		
		if(!empty($LotNumSearch)) 
	    {
			if (Schema::hasColumn('work_process_requirements', 'req_lot_no')) {
				$query->whereHas('WorkProcessRequirement', function ($q) use ($LotNumSearch) {
					$q->where('req_lot_no', '=', $LotNumSearch)->where('status', 'Active')->where('is_accept', '1');
				});
			} else {
				$query->whereRaw('0=1');
			}
		}
		
		if (!empty($recLotNumSerch)) 
		{ 
			$workorderids = GatePass::where('dyeing_lot_number', '=', $recLotNumSerch)
					->where('status', '=', 'Active')
					->pluck('work_order_id')
					->implode(',');

			if (!empty($workorderids)) {
				$query->whereIn('parent_work_order_id', explode(',', $workorderids));
			}
		}

        if(!empty($ordNumSearch)) 
	    {
			$query->whereHas('WorkOrderItem.SaleOrder', function ($q) use ($ordNumSearch) {
				$q->where('sale_order_number', '=', $ordNumSearch);
			});
		}

		if (!empty($priority)) 
		{
			$query->whereHas('WorkOrderItem', function ($q) use ($priority) {
				$q->where('order_item_priority', 'LIKE', '%' . $priority . '%')->where('status', 'Active');
			});
		}
		if (!empty($arrr)) 
		{
			$query->whereIn('process_type_id', ProcessItem::whereIn('id', array_filter((array) $search_process_id))->where('status', 'Active')->select('id'));
		}
        if (!empty($fromDate) && !empty($toDate)) 
		{
			$fromDate 		= date('Y-m-d', strtotime($request->from_date));
			$toDate 		= date('Y-m-d', strtotime($request->to_date));		
			$query->where('created_at', '>=',  $fromDate)->where('created_at', '<=',  $toDate);
		} 
		$query->where('status', '=',  'Active')->orderBy('id', 'desc');   
		 // 
		if ($sbtSearch == 'ExportToExcel') 
		{
			try {
				$dataWI = $query->get();
				return Excel::download(new WorkOrderItemExport($dataWI), 'work_order_item_list.xlsx');
			} catch (\Exception $e) {
				\Log::error('Exception: ' . $e->getMessage());
				return response('Error generating Excel', 500);
			}
		} 
		
		if ($sbtSearch == 'ExportToPdf') 
		{
			try {
				$dataWI = $query->get();

				$dompdf = new Dompdf();
				$options = new Options();
				$options->set('isHtml5ParserEnabled', true);
				$options->set('isPhpEnabled', true);
				$dompdf->setOptions($options);
				$dompdf->loadHtml(view('frontend.workorder.work_order_item_list-pdf', compact('dataWI', 'qsearch')));
				 
				$dompdf->setPaper('A4', 'portrait');
				$dompdf->render();
				return $dompdf->stream('export_work_order_item_list.pdf');
			} catch (\Exception $e) {
				Log::error('Exception: ' . $e->getMessage());
				return response('Error generating PDF', 500);
			}
		}		
		
		 
		$totSumMtr = 0;
		 
		
		$eagerLoads = $query->getEagerLoads();
		$query->setEagerLoads([]);
		$dataWI 	= $query->paginate(10)->appends(request()->except('_token'));
		$dataWI->getCollection()->load($eagerLoads);
		  // echo "<pre>"; print_r($dataWI); exit; 

		$pageWorkOrders = $dataWI->getCollection();
		$pageWorkOrderIds = $pageWorkOrders->pluck('id')->filter()->unique()->values();
		$pageParentWorkOrderIds = $pageWorkOrders->pluck('parent_work_order_id')->filter()->unique()->values();
		$itemTypeByWorkOrder = $pageWorkOrders->pluck('item_type_id', 'id');

		$childLotNumbersByWorkOrder = $pageParentWorkOrderIds->isEmpty()
			? collect()
			: WarehouseItemStock::whereIn('work_order_id', $pageParentWorkOrderIds)
				->where('status', 'Active')
				->whereNotNull('dyeing_lot_number')
				->select('id', 'work_order_id', 'dyeing_lot_number')
				->groupBy('id', 'work_order_id', 'dyeing_lot_number')
				->get()
				->groupBy('work_order_id');

		$totalChildWorkByWorkOrder = $pageWorkOrderIds->isEmpty()
			? collect()
			: WorkOrder::whereIn('parent_work_order_id', $pageWorkOrderIds)
				->where('status', 'Active')
				->select('parent_work_order_id', DB::raw('COUNT(*) as total'))
				->groupBy('parent_work_order_id')
				->pluck('total', 'parent_work_order_id');

		$customerIds = $pageWorkOrders
			->flatMap(function ($workOrder) {
				return $workOrder->WorkOrderItem->pluck('customer_id');
			})
			->filter()
			->unique()
			->values();

		$customerNamesById = $customerIds->isEmpty()
			? collect()
			: Individual::whereIn('id', $customerIds)->pluck('name', 'id');

		$wprAllottedRows = $pageWorkOrderIds->isEmpty()
			? collect()
			: DB::table('work_process_requirements')
				->select('work_order_id', 'item_id', 'item_type_id', 'dyeing_color', DB::raw('SUM(quantity) as tot'), DB::raw('SUM(alloted_quantity) as alttot'))
				->whereIn('work_order_id', $pageWorkOrderIds)
				->where('status', 'Active')
				->where('is_accept', '1')
				->groupBy('work_order_id', 'item_id', 'item_type_id', 'dyeing_color')
				->get()
				->filter(function ($row) use ($itemTypeByWorkOrder) {
					return (string) ($itemTypeByWorkOrder[$row->work_order_id] ?? '') === (string) $row->item_type_id;
				});

		$allottedItemIds = $wprAllottedRows->pluck('item_id')->filter()->unique()->values();
		$allottedItemTypeIds = $wprAllottedRows->pluck('item_type_id')->filter()->unique()->values();

		$itemNamesById = $allottedItemIds->isEmpty()
			? collect()
			: Item::whereIn('item_id', $allottedItemIds)
				->where('status', 'Active')
				->pluck('item_name', 'item_id');

		$itemTypeNamesById = $allottedItemTypeIds->isEmpty()
			? collect()
			: ItemType::whereIn('item_type_id', $allottedItemTypeIds)
				->pluck('item_type_name', 'item_type_id');

		$itemBalanceByKey = $allottedItemIds->isEmpty()
			? collect()
			: WarehouseBalanceItem::whereIn('item_id', $allottedItemIds)
				->whereIn('item_type_id', $allottedItemTypeIds)
				->where('balance_status', 1)
				->select('item_id', 'item_type_id', DB::raw('SUM(item_qty) as total'))
				->groupBy('item_id', 'item_type_id')
				->get()
				->mapWithKeys(function ($row) {
					return [$row->item_id.'|'.$row->item_type_id => $row->total ?: 0];
				});

		$dyeingBalanceRows = $wprAllottedRows->filter(function ($row) {
			return !empty($row->dyeing_color);
		});

		$dyeingBalanceByKey = $dyeingBalanceRows->isEmpty()
			? collect()
			: WarehouseBalanceItem::where(function ($query) use ($dyeingBalanceRows) {
				foreach ($dyeingBalanceRows as $row) {
					$query->orWhere(function ($q) use ($row) {
						$q->where('item_id', $row->item_id)
							->where('item_type_id', $row->item_type_id)
							->where('dyeing_color', $row->dyeing_color);
					});
				}
			})
				->where('balance_status', '1')
				->select('item_id', 'item_type_id', 'dyeing_color', DB::raw('SUM(item_qty) as total'))
				->groupBy('item_id', 'item_type_id', 'dyeing_color')
				->get()
				->mapWithKeys(function ($row) {
					return [$row->item_id.'|'.$row->item_type_id.'|'.$row->dyeing_color => $row->total ?: 0];
				});

		$allotedStocksByWorkOrder = $wprAllottedRows->groupBy('work_order_id')->map(function ($rows) use ($itemNamesById, $itemTypeNamesById, $itemBalanceByKey, $dyeingBalanceByKey) {
			return $rows->map(function ($row) use ($itemNamesById, $itemTypeNamesById, $itemBalanceByKey, $dyeingBalanceByKey) {
				$itemId = $row->item_id;
				$itemTypeId = $row->item_type_id;
				$balanceKey = $itemId.'|'.$itemTypeId;

				if (!empty($row->dyeing_color)) {
					$balanceKey .= '|'.$row->dyeing_color;
					$itemBalance = $dyeingBalanceByKey[$balanceKey] ?? 0;
				} else {
					$itemBalance = $itemBalanceByKey[$balanceKey] ?? 0;
				}

				$allowedKgItemTypes = ['1', '2', '7', '9'];

				return [
					'ItemName' => $itemNamesById[$itemId] ?? '',
					'iTName' => $itemTypeNamesById[$itemTypeId] ?? '',
					'Itembalance' => $itemBalance,
					'RequestQTY' => $row->tot,
					'AllotedQTY' => $row->alttot,
					'unitTName' => in_array((string) $itemTypeId, $allowedKgItemTypes, true) ? 'Kg' : 'Meter',
				];
			})->values()->all();
		});

		$inspectionIds = $dataWI->getCollection()
			->flatMap(function ($workOrder) {
				return $workOrder->GatePass->pluck('inspection_id');
			})
			->filter()
			->unique()
			->values();

		$availableTakaCounts = $inspectionIds->isEmpty()
			? collect()
			: WarehouseItemStock::select('insp_id', DB::raw('COUNT(*) as total'))
				->whereIn('insp_id', $inspectionIds)
			->where('status', 'Active')
				->where('is_allotted_stock', 'No')
				->groupBy('insp_id')
				->pluck('total', 'insp_id');
		 
		$dataMas = Individual::where('type', 'master')
					->where('status', 'Active')
					->when(!empty($processTypeId), function ($q) use ($processTypeId) {
						$q->where('process_type_id', $processTypeId);
					})
					->get(); 
					
		$machine 	= Machine::where('status', 'Active')
						->when(!empty($processTypeId), function ($q) use ($processTypeId) {
							$q->where('process_wise', $processTypeId);
						})
						->when(empty($processTypeId) && !empty($search_process_id), function ($q) use ($search_process_id) {
							$q->whereIn('process_wise', array_filter((array) $search_process_id));
						})
						->get();

		

		
		$processI 	= ProcessItem::where('status', '=', 'Active')->get();
		$dataW 		= Warehouse::where('status', '=', 'Active')->orderBy('warehouse_name', 'asc')->get();
		$dataF 		= FabricFaultReason::where('status', '=', 'Active')->orderByDesc('id')->get();
		$dataIT  	= ItemType::where('status', '=', 'Active')->where('is_work', '=', '1')->get();
		$dataITP  	= ItemType::where('status', '=', 'Active')->where('is_purchase', '=', '1')->get();
		$dataI  	= Item::where('status', '=', 'Active')->get();
		$priorityArr = config('global.priorityArr');
	 
		 
		return view('frontend.workorder.show-workorders', compact("dataWI", "totSumMtr", "cusSearch", "individualId", "itemSearch", "ordNumSearch", "priority", "dataMas", "machine", "processI", "dataW", "dataF", "dataIT", "dataI", "dataITP", "priorityArr", "search_process_id","fromDate","toDate","workStatus","colorSearch","LotNumSearch","userIndId","proceStatus","recLotNumSerch","yearRecord","availableTakaCounts","childLotNumbersByWorkOrder","totalChildWorkByWorkOrder","customerNamesById","allotedStocksByWorkOrder"));
		 
		 
		
	
	}
 		
    public function store(Request $request)
    {
        $saleOrderItemIds = $request->input('chk_sale_order_item_id', []);
        $workSubmit = $request->input('WorkSubmit', '');

        if (empty($saleOrderItemIds) || $workSubmit == '') {
            Session::put('message', 'Please select sale order items and process.');
            Session::put('messageClass', 'errorClass');
            return redirect()->back();
        }

        DB::beginTransaction();
        try {
            $productionValues = array_values(array_filter($request->input('sale_order_production_value', []), fn ($value) => $value !== null && $value !== ''));

            $processCode = 'V';
            $fallbackProcessId = 1;
            $processItemTypeId = 3;

            if ($workSubmit === 'Dyeing') {
                $processCode = 'D';
                $fallbackProcessId = 2;
                $processItemTypeId = 4;
            } elseif ($workSubmit === 'Coating') {
                $processCode = 'C';
                $fallbackProcessId = 3;
                $processItemTypeId = 5;
            }

            foreach (array_values($saleOrderItemIds) as $index => $saleOrderItemId) {
                $saleOrderItem = SaleOrderItem::with('saleOrder.customer')
                    ->where('id', $saleOrderItemId)
                    ->where('status', 'Active')
                    ->firstOrFail();

                $meter = isset($productionValues[$index]) ? (float) $productionValues[$index] : (float) $saleOrderItem->pending_item_mtr;
                if ($meter <= 0) {
                    $meter = max(0, (float) $saleOrderItem->meter - (float) $saleOrderItem->delivered_item_mtr);
                }

                if ($workSubmit === 'Packaging') {
                    $saleOrderItem->is_packaging_done = '1';
                    $saleOrderItem->in_packaging_send_by = Auth::id();
                    $saleOrderItem->in_packaging_send_date = now();
                    $saleOrderItem->modified_by = Auth::id();
                    $saleOrderItem->modified_at = now();
                    $saleOrderItem->save();
                    continue;
                }

                $processItem = ProcessItem::where('status', 'Active')
                    ->where(function ($query) use ($workSubmit) {
                        $query->where('process_name', 'like', '%'.$workSubmit.'%')
                            ->orWhere('entry_name', 'like', '%'.$workSubmit.'%');
                    })
                    ->first();

                $processSlNo = 1;
                if (!empty($processItem)) {
                    $processItem->process_sl_no_last = ((int) $processItem->process_sl_no_last) + 1;
                    $processItem->modified = now();
                    $processItem->save();
                    $processSlNo = (int) $processItem->process_sl_no_last;
                }

                $workOrder = new WorkOrder();
                $workOrder->process_type = $processCode;
                $workOrder->process_sl_no = $processSlNo;
                $workOrder->user_id = Auth::id() ?? 0;
                $workOrder->process_type_id = $processItem->id ?? $fallbackProcessId;
                $workOrder->item_type_id = $processItemTypeId;
                $workOrder->item_id = $saleOrderItem->item_id;
                $workOrder->item_name = $saleOrderItem->item_name ?? '';
                $workOrder->pcs = (int) ($saleOrderItem->pcs ?? 0);
                $workOrder->cut = (int) ($saleOrderItem->cut ?? 0);
                $workOrder->meter = (int) round($meter);
                $workOrder->process_started_by = 0;
                $workOrder->process_ended_by = 0;
                $workOrder->process_inspected_by = 0;
                $workOrder->process_started_remarks = '';
                $workOrder->process_ended_remarks = '';
                $workOrder->financial_year = currentFinancialYear();
                $workOrder->created_by = Auth::id() ?? 0;
                $workOrder->created_at = now();
                $workOrder->status = 'Active';
                $workOrder->save();

                $workOrderItem = new WorkOrderItem();
                $workOrderItem->work_order_id = $workOrder->id;
                $workOrderItem->customer_id = $saleOrderItem->saleOrder->customer_id ?? null;
                $workOrderItem->sale_order_id = $saleOrderItem->sale_order_id;
                $workOrderItem->sale_order_item_id = $saleOrderItem->id;
                $workOrderItem->item_type_id = $workOrder->item_type_id;
                $workOrderItem->unit_type_id = $saleOrderItem->unit_type_id;
                $workOrderItem->item_id = $saleOrderItem->item_id;
                $workOrderItem->pcs = (int) ($saleOrderItem->pcs ?? 0);
                $workOrderItem->cut = (int) ($saleOrderItem->cut ?? 0);
                $workOrderItem->meter = $meter;
                $workOrderItem->grey_quality = $saleOrderItem->grey_quality;
                $workOrderItem->dyeing_color = $saleOrderItem->dyeing_color;
                $workOrderItem->coating_type = $saleOrderItem->coating_type;
                $workOrderItem->extra_job = $saleOrderItem->extra_job;
                $workOrderItem->print_job = $saleOrderItem->print_job;
                $workOrderItem->expect_delivery_date = $saleOrderItem->expect_delivery_date;
                $workOrderItem->order_item_priority = $saleOrderItem->order_item_priority ?? '';
                $workOrderItem->financial_year = currentFinancialYear();
                $workOrderItem->created_by = Auth::id();
                $workOrderItem->created_at = now();
                $workOrderItem->status = 'Active';
                $workOrderItem->save();

                $saleOrderItem->is_work_order_created = 1;
                $saleOrderItem->modified_by = Auth::id();
                $saleOrderItem->modified_at = now();
                $saleOrderItem->save();
            }

            DB::commit();
            Session::put('message', 'Work order created successfully.');
            Session::put('messageClass', 'successClass');
            return redirect()->back();
        } catch (Exception $e) {
            DB::rollBack();
            Session::put('message', 'Failed to create work order. Error: '.$e->getMessage());
            Session::put('messageClass', 'errorClass');
            return redirect()->back()->withInput();
        }
    }

	public function checkIteminWarehouse(Request $request)
    {
        $saleOrderItemIds = collect(explode(',', (string) $request->input('FId', '')))->map(fn ($id) => (int) trim($id))->filter()->values();

        $flag = 0;
        $gFlag = 0;
        $dFlag = 0;
        $cFlag = 0;

        $saleOrderItems = SaleOrderItem::whereIn('id', $saleOrderItemIds)->where('status', 'Active')->get();

        foreach ($saleOrderItems as $saleOrderItem) 
		{
            $itemId = $saleOrderItem->item_id;
            $dyeingColor = trim((string) $saleOrderItem->dyeing_color);
            $coatingType = trim((string) $saleOrderItem->coating_type);
            $normalizedCoating = strtolower($coatingType);

            $beamQty = (float) trim(str_ireplace('meter', '', (string) CommonController::getWarehouseAvailableItemStockBeam($itemId, 2)));
            if ($beamQty > 0) {
                $flag = 1;
            }

            if ($dyeingColor !== '') {
                $greigeQty = (float) trim(str_ireplace('meter', '', (string) CommonController::check_warehouse_greige_type_balance($itemId, 3)));
                if ($greigeQty > 0) {
                    $gFlag = 1;
                }
            }

            if ($dyeingColor === '' && ($coatingType === '' || in_array($normalizedCoating, ['0', 'no', 'not', 'none'], true))) {
                $greigeQty = (float) trim(str_ireplace('meter', '', (string) CommonController::check_warehouse_greige_type_balance($itemId, 3)));
                if ($greigeQty > 0) {
                    $cFlag = 1;
                }
            }

            if ($coatingType !== '' && !in_array($normalizedCoating, ['0', 'no', 'not', 'none'], true)) {
                $dyeingQty = (float) trim(str_ireplace('meter', '', (string) CommonController::check_warehouse_dyeing_type_balance($itemId, 4, $dyeingColor)));
                if ($dyeingQty > 0) {
                    $dFlag = 1;
                }
            }

            $coatingQty = (float) trim(str_ireplace('meter', '', (string) CommonController::check_warehouse_coating_type_balance($itemId, 5, $dyeingColor, $coatingType)));
            if ($coatingQty > 0) {
                $cFlag = 1;
            }
        }

        return response(json_encode([
            'weavingwrk' => $flag,
            'dyeingwrk' => $gFlag,
            'coatingwrk' => $dFlag,
            'packagingwrk' => $cFlag,
        ]), 200, ['Content-Type' => 'text/plain']);
    }
	
	public function shiftWorkOrderToWarping(Request $request)
	{
		$workId = (int) $request->input('FId');
		if ($workId <= 0) {
			return response()->json(['success' => false, 'message' => 'Invalid Work Order ID'], 422);
		}

		$user = Auth::user();
		if (!$user) {
			return response()->json(['success' => false, 'message' => 'User not found'], 404);
		}

		$individualId = $user->individual_id ?? 0;

		DB::beginTransaction();
		try {
			$workData = WorkOrder::where('id', $workId)->lockForUpdate()->first();
			if (!$workData) {
				DB::rollBack();
				return response()->json(['success' => false, 'message' => 'Work Order not found'], 404);
			}

			if ((string) $workData->status !== 'Active') {
				DB::rollBack();
				return response()->json(['success' => false, 'message' => 'This Work Order is already shifted or inactive.'], 409);
			}

			if ((int) $workData->process_type_id !== 2) {
				DB::rollBack();
				return response()->json(['success' => false, 'message' => 'Only Weaving Work Orders can be shifted to Warping.'], 422);
			}

			$alreadyShifted = WorkOrder::where('parent_work_order_id', $workId)
				->where('process_type_id', 1)
				->where('status', 'Active')
				->exists();

			if ($alreadyShifted) {
				DB::rollBack();
				return response()->json(['success' => false, 'message' => 'This Work Order has already been shifted to Warping.'], 409);
			}

			$processType = 1;
			$itemTypeId = 1;
			$processTypeData = CommonController::getProcessTypeName($processType);
			$processCode = $processTypeData['shortcode'] ?? 'W';
			$processItem = ProcessItem::where('id', $processType)->lockForUpdate()->first();
			if (!$processItem) {
				DB::rollBack();
				return response()->json(['success' => false, 'message' => 'Warping process not found'], 404);
			}

			$processItem->process_sl_no_last = ((int) $processItem->process_sl_no_last) + 1;
			$processItem->modified = now();
			$processItem->save();

			$itemId = $workData->item_id;
			$itemName = Item::where('item_id', $itemId)->value('item_name') ?? $workData->item_name ?? '';

			$newWorkOrder = new WorkOrder();
			$newWorkOrder->parent_work_order_id = $workData->id;
			$newWorkOrder->process_type = $processCode;
			$newWorkOrder->process_sl_no = (int) $processItem->process_sl_no_last;
			$newWorkOrder->item_id = $itemId;
			$newWorkOrder->item_name = $itemName;
			$newWorkOrder->pcs = (int) ($workData->pcs ?? 0);
			$newWorkOrder->cut = (int) ($workData->cut ?? 0);
			$newWorkOrder->meter = (int) ($workData->meter ?? 0);
			$newWorkOrder->process_type_id = $processType;
			$newWorkOrder->item_type_id = $itemTypeId;
			$newWorkOrder->user_id = $individualId;
			$newWorkOrder->process_started_by = 0;
			$newWorkOrder->process_ended_by = 0;
			$newWorkOrder->process_inspected_by = 0;
			$newWorkOrder->process_started_remarks = '';
			$newWorkOrder->process_ended_remarks = '';
			$newWorkOrder->financial_year = currentFinancialYear();
			$newWorkOrder->created_by = Auth::id() ?? 0;
			$newWorkOrder->created_at = now();
			$newWorkOrder->status = 'Active';
			$newWorkOrder->save();

			$woItemArr = WorkOrderItem::where('work_order_id', $workId)
				->where('status', 'Active')
				->get();

			foreach ($woItemArr as $woItem) {
				$saleOrderItem = SaleOrderItem::with('saleOrder')
					->where('id', $woItem->sale_order_item_id)
					->first();

				$newWorkOrderItem = new WorkOrderItem();
				$newWorkOrderItem->work_order_id = $newWorkOrder->id;
				$newWorkOrderItem->customer_id = $saleOrderItem->saleOrder->customer_id ?? $woItem->customer_id;
				$newWorkOrderItem->sale_order_id = $woItem->sale_order_id;
				$newWorkOrderItem->sale_order_item_id = $woItem->sale_order_item_id;
				$newWorkOrderItem->item_type_id = $saleOrderItem->item_type_id ?? $woItem->item_type_id;
				$newWorkOrderItem->unit_type_id = $saleOrderItem->unit_type_id ?? $woItem->unit_type_id;
				$newWorkOrderItem->item_id = $saleOrderItem->item_id ?? $woItem->item_id;
				$newWorkOrderItem->grey_quality = $saleOrderItem->grey_quality ?? $woItem->grey_quality;
				$newWorkOrderItem->dyeing_color = $saleOrderItem->dyeing_color ?? $woItem->dyeing_color;
				$newWorkOrderItem->coating_type = $saleOrderItem->coating_type ?? $woItem->coating_type;
				$newWorkOrderItem->extra_job = $saleOrderItem->extra_job ?? $woItem->extra_job;
				$newWorkOrderItem->print_job = $saleOrderItem->print_job ?? $woItem->print_job;
				$newWorkOrderItem->expect_delivery_date = $saleOrderItem->expect_delivery_date ?? $woItem->expect_delivery_date;
				$newWorkOrderItem->order_item_priority = $saleOrderItem->order_item_priority ?? $woItem->order_item_priority ?? '';
				$newWorkOrderItem->pcs = (int) ($woItem->pcs ?? 0);
				$newWorkOrderItem->cut = (int) ($woItem->cut ?? 0);
				$newWorkOrderItem->meter = $woItem->meter ?? 0;
				$newWorkOrderItem->financial_year = currentFinancialYear();
				$newWorkOrderItem->created_by = Auth::id() ?? 0;
				$newWorkOrderItem->created_at = now();
				$newWorkOrderItem->status = 'Active';
				$newWorkOrderItem->save();

				$woItem->status = 'Inactive';
				$woItem->modified_by = Auth::id() ?? 0;
				$woItem->updated_at = now();
				$woItem->save();

				if ($saleOrderItem) {
					$saleOrderItem->is_work_order_created = 1;
					$saleOrderItem->modified_by = Auth::id() ?? 0;
					$saleOrderItem->modified_at = now();
					$saleOrderItem->save();
				}
			}

			$workData->status = 'Inactive';
			$workData->work_shifted_by = $individualId;
			$workData->modified_by = Auth::id() ?? 0;
			$workData->modified_at = now();
			$workData->save();

			DB::commit();
			return response()->json([
				'success' => true,
				'message' => 'Work order shifted successfully.',
				'work_order_id' => $newWorkOrder->id,
			]);
		} catch (Exception $e) {
			DB::rollBack();
			Log::error('Failed to shift Work Order to Warping', [
				'work_order_id' => $workId,
				'user_id' => Auth::id(),
				'error' => $e->getMessage(),
			]);

			return response()->json(['success' => false, 'message' => 'Failed to shift Work Order. '.$e->getMessage()], 500);
		}
	}

	public function start_requisition_process(string $id)
	{
		$workOrderId = dec($id);

		$data = WorkOrder::with(['WorkOrderItem', 'Item'])->findOrFail($workOrderId);

		$itemId            = $data->item_id;
		$itemTypeId        = $data->item_type_id;
		$processId         = $data->process_type_id ?? 1;
		$workOrderNumber   = trim(($data->process_type ?? '') . ($data->process_sl_no ?? '') . ' ' . $data->id);
		$workOrderItemName = $data->item_name ?: ($data->Item->item_name ?? '');

		$yarnIds 			= ItemYarnRequirement::where('item_id', $itemId)->where('process_id', $processId)->where('status', 'Active')->distinct()->pluck('yarn_id')->toArray();

		$dataWIS 			= WarehouseItemStock::select('id', 'item_id', 'work_order_id', 'insp_bal_quan_size', 'quan_size_unit', 'dyeing_color', 'invoice_number', 'insp_taka_number', 'status')->whereIn('item_id', $yarnIds)->where('item_type_id', 1)->where('is_allotted_stock', 'No')->where('status', 'Active')->with(['Item:item_id,item_name,item_code'])->get();

		$dataWI	 			= WarehouseItem::where('item_id', $itemId)->where('item_type_id', $itemTypeId)->where('item_qty', '!=', 0)->first();

		$resultArray 		= WarehouseItemStock::where('item_id', $itemId)->where('item_type_id', $itemTypeId)->where('entry_type', 'IN')->where('is_allotted_stock', 'No')->where('status', 1)->get();

		$viewName 			= $this->getViewName($processId);

		return view($viewName, compact('data', 'itemId', 'itemTypeId', 'workOrderId', 'dataWIS', 'workOrderNumber', 'workOrderItemName', 'dataWI', 'resultArray'));
	}
 
	// iss function ko touch nahi kerna hai, kisi bhi conditions mein. Codex ko specialy bola ja raha hai.
	private function getViewName($processId)
	{
		switch ($processId) {
			case '5':
				return 'frontend.workorder.start-coating-requisition-process-for-packaging';
			case '4':
				return 'frontend.workorder.start-dyeing-requisition-process-for-coating';
			case '3':
				return 'frontend.workorder.start-greige-requisition-process-for-dyeing';
			case '2':
				return 'frontend.workorder.start-beam-requisition-process-for-weaving';
			default:
				return 'frontend.workorder.start-requisition-process';
		}
	}

	public function add_work_requisition(Request $request)
	{
		$validator = Validator::make($request->all(), [
			"itemIdReq" => "required|integer",
			"work_order_id_req" => "required|integer",
			"req_item_id" => "required|array|min:1",
			"req_item_id.*" => "required|integer",
			"quantity" => "required|array|min:1",
			"quantity.*" => "required|numeric|min:1",
		], [
			"itemIdReq.required" => "Please select Item Name.",
			"work_order_id_req.required" => "Please select your Work type.",
			"req_item_id.required" => "Please select at least one item.",
			"quantity.*.required" => "Please enter the quantity for each item.",
			"quantity.*.numeric" => "Quantity must be a number for each item.",
			"quantity.*.min" => "Quantity must be at least 1 for each item.",
		]);

		if ($validator->fails()) {
			$error = $validator->errors()->first();
			Session::put('message', $validator->messages()->first());
			Session::put("messageClass", "errorClass");
			return redirect()->back()->withInput();
		}

		DB::beginTransaction();

		try {
			$workOrdId = (int) $request->work_order_id_req;
			$workOrder = WorkOrder::where('id', $workOrdId)->where('status', '!=', 'Deleted')->first();

			if (!$workOrder) {
				throw new Exception('Work Order not found.');
			}

			$user = Auth::user();
			$individualId = $user ? $user->individual_id : null;
			$currentDate = now();
			$reqItemIdArr = $request->input('req_item_id', []);
			$qtyArr = $request->input('quantity', []);
			$rows = [];

			foreach ($reqItemIdArr as $index => $stockId) 
			{
				$stock = WarehouseItemStock::where('id', $stockId)->where('is_allotted_stock', 'No')->where('status', 'Active')->first();

				if (!$stock) {
					throw new Exception('Selected stock item not found.');
				}

				$quantity = (float) ($qtyArr[$index] ?? 0);
				if ($quantity <= 0) {
					throw new Exception('Please enter valid quantity for selected stock.');
				}

				if ($stock->insp_bal_quan_size !== null && $quantity > (float) $stock->insp_bal_quan_size) {
					throw new Exception('Requested quantity cannot be greater than available stock.');
				}

				$item = Item::where('item_id', $stock->item_id)->where('status', '!=', 'Deleted')->first();
				if (!$item) {
					throw new Exception('Selected item not found.');
				}

				$rows[] = [
					'work_order_id' => $workOrdId,
					'item_id' => $stock->item_id,
					'wis_id' => $stockId,
					'process_type_id' => $workOrder->process_type_id,
					'item_type_id' => $item->item_type_id,
					'unit_type_id' => $item->unit_type_id,
					'quantity' => $quantity,
					'financial_year' => currentFinancialYear(),
					'created_by' => $individualId,
					'created_at' => $currentDate,
					'status' => 'Active',
				];
			}

			if (empty($rows)) {
				throw new Exception('Please select at least one stock item.');
			}

			WorkProcessRequirement::insert($rows);

			 
			$workOrder->update([
				'work_req_send_by' => $individualId,
				'is_work_require_request_accepted' => null,
				'work_req_send_date' => $currentDate,
			]);

			DB::commit();

			Session::put('message', 'Work Requirement Send to Warehouse successfully.');
			Session::put("messageClass", "successClass");

			$redirectUrl = Session::pull('workorders_return_url', '/show-workorders');
			return redirect()->to($redirectUrl);
		} catch (Exception $e) {
			DB::rollBack();
			Session::put('message', $e->getMessage());
			Session::put("messageClass", "errorClass");
			return redirect()->back()->withInput();
		}
	}
  
	public function add_work_requisition_for_weaving(Request $request)
	{
		  // echo "<pre>"; print_r($request->all()); exit;
		$validator = Validator::make($request->all(), [
			"itemIdReq"          => "required",   
			"work_order_id_req"  => "required", 
			"quantity"           => "required|array|min:1",
			"quantity.*"         => "required|numeric|min:1", 
			"req_item_id"        => "required|array|min:1",
			"req_item_id.*"      => "required|numeric|min:1",			 
		], [
			"itemIdReq.required"             => "Please select Item Name.",
			"work_order_id_req.required"     => "Please select your Work type.", 			
			"quantity.required"              => "Please enter your work Quantity.",
			"quantity.*.required"            => "Each quantity must not be empty.",
			"quantity.*.numeric"             => "Each quantity must be a number.",
			"quantity.*.min"                 => "Each quantity must be at least 1.",			
			"req_item_id.required"           => "Please enter required item ID.",
			"req_item_id.min"                => "Please select at least one required item.",
			"req_item_id.*.required"         => "Each required item ID must not be empty.",
			"req_item_id.*.numeric"          => "Each required item ID must be a number.",
			"req_item_id.*.min"              => "Each required item ID must be at least 1.",			 
		]); 
		
		if ($validator->fails()) {
			$error = $validator->errors()->first();
			Session::put('message', $error);
			Session::put("messageClass", "errorClass");
			return redirect()->back()->withInput();
		} 
		DB::beginTransaction();
		try {
			$workOrdId 		= (int) $request->input('work_order_id_req');
			$reqItemIdArr 	= $request->input('req_item_id', []);
			$wisIdArr 		= array_slice(array_values(array_filter((array) $request->input('wis_id', []))), 0, 1);
			$quantity 		= $request->input('quantity', []);
			 
			$WitemId 		= $request->input('itemIdReq');
			$extItemTypeId 	= $request->input('ext_item_type_id');
			$curDate 		= now();
			$workSizeMeter  = array_sum(array_filter($request->input('work_size_meter', [])));
			
			$userId 		= Auth::id();
			$userD 			= User::find($userId);
			$IndividualId 	= $userD->individual_id ?? null;

			$dataWk = WorkOrder::where('id', $workOrdId)->with('WorkOrderItem','WorkInspectionOne')->where('status', 'Active')->first();
			if (!$dataWk) {
				DB::rollBack();
				return redirect()->back()->withErrors(['message' => 'Work Order not found']);
			}

			$alootedBeamNumber = $dataWk->WorkInspectionOne->insp_taka_number ?? null;
			$useExistingWorkOrder = false;
			$requestedBeamNumber = null;

			//  echo "<pre>"; print_r($wisIdArr); exit;
			if (!empty($wisIdArr)) 
			{
				$selectedBeamStockId = $wisIdArr[0];

				$result = WarehouseItemStock::where('id', $selectedBeamStockId)->where('is_allotted_stock', 'No')->where('status', 'Active')->first();

				$requestedBeamNumber 	= $result->insp_taka_number ?? null;
				$allottedBeamNo 		= trim((string) $alootedBeamNumber);
				$requestedBeamNo 		= trim((string) $requestedBeamNumber);

				if ($allottedBeamNo != '' && $allottedBeamNo == $requestedBeamNo) {
					$useExistingWorkOrder = true;
				}
			}
			
			// mughe condition kuch aisa lagana hai ki $alootedBeamNumber and $requestedBeamNumber same hoga toh new WorkOrder(); genrate nahi hoga sara kam iss WorkOrder::where('id', $workOrdId) ke liya hoga. ager match nahi kerta hai to new WorkOrder() genrate hoga and abhi jaise kam ho raha hai waise hoga
			
			$procesTypeId = $dataWk->process_type_id; 

			if (!empty(array_filter($wisIdArr)) && !$useExistingWorkOrder) 
			{ 
				$processType 	= 2;
				$itemTypeId 	= 2;
				$dataPI 		= ProcessItem::where('id', $processType)->first(); 
				$proSNo 		= $dataPI->process_sl_no_last; 
				$proType 		= CommonController::getProcessTypeName($processType);
				$shortcode 		= $proType['shortcode'];

				$workOrder = new WorkOrder();
				$workOrder->process_type 	= $shortcode;
				$workOrder->process_sl_no 	= $proSNo + 1;
				$workOrder->item_id 		= $dataWk->item_id;
				$workOrder->item_name 		= $dataWk->item_name;
				$workOrder->pcs 			= $dataWk->pcs;
				$workOrder->cut 			= $dataWk->cut;
				// $workOrder->meter 			= $dataWk->meter;
				$workOrder->meter 			= $workSizeMeter;
				$workOrder->process_type_id = $processType;
				$workOrder->item_type_id 	= $itemTypeId;
				$workOrder->user_id 		= $userId ?? 0;
				$workOrder->parent_work_order_id = $dataWk->id;
				$workOrder->process_started_by = 0;
				$workOrder->process_ended_by = 0;
				$workOrder->process_inspected_by = 0;
				$workOrder->process_started_remarks = '';
				$workOrder->process_ended_remarks = '';
				$workOrder->financial_year = currentFinancialYear();
				$workOrder->created_by = $IndividualId;
				$workOrder->created_at = $curDate;
				$workOrder->status 			= 'Active';
				$is_saved 					= $workOrder->save();
				$newworkOrderId 			= $workOrder->getKey();

				if ($is_saved) 
				{
					foreach ($dataWk->WorkOrderItem as $woiArr) {
						$workOrderItem = new WorkOrderItem();
						$workOrderItem->work_order_id 		= $newworkOrderId;
						$workOrderItem->customer_id 		= $woiArr->customer_id;
						$workOrderItem->sale_order_id 		= $woiArr->sale_order_id;
						$workOrderItem->sale_order_item_id 	= $woiArr->sale_order_item_id;
						$workOrderItem->item_type_id 		= $woiArr->item_type_id;
						$workOrderItem->unit_type_id 		= $woiArr->unit_type_id;
						$workOrderItem->item_id 			= $woiArr->item_id;
						$workOrderItem->grey_quality 		= $woiArr->grey_quality;
						$workOrderItem->dyeing_color 		= $woiArr->dyeing_color;
						$workOrderItem->coating_type 		= $woiArr->coating_type;
						$workOrderItem->extra_job 			= $woiArr->extra_job;
						$workOrderItem->print_job 			= $woiArr->print_job;
						$workOrderItem->expect_delivery_date 	= $woiArr->expect_delivery_date;
						$workOrderItem->order_item_priority 	= $woiArr->order_item_priority;
						$workOrderItem->pcs 				= $woiArr->pcs;
						$workOrderItem->cut 				= $woiArr->cut;
						$workOrderItem->meter 				= $woiArr->meter;
						$workOrderItem->financial_year 		= currentFinancialYear();
						$workOrderItem->created_by 			= $IndividualId;
						$workOrderItem->created_at 			= $curDate;
						$workOrderItem->status 				= 'Active';
						$workOrderItem->save();
					}
				}   
			} else {
				$newworkOrderId = $workOrdId;
			}

			if (!empty(array_filter($wisIdArr))) 
			{
				$dataWPR = [];
				foreach ($wisIdArr as $wisId) 
				{
					$result = WarehouseItemStock::where('id', $wisId)->where('is_allotted_stock', 'No')->where('status', 'Active')->first();
					if (!$result) {
						continue;
					}
					$dataIT = ItemType::where('item_type_id', $extItemTypeId)->where('status', 'Active')->first();        
					$UnitTypeIdd 	= $dataIT->unit_type_id ?? $result->unit_type_id;
					$itemIdQty 		= $result ? $result->insp_bal_quan_size : null;

					$dataWPR[] = [
						'work_order_id' 	=> $newworkOrderId,
						'item_id' 			=> $WitemId,
						'wis_id' 			=> $wisId,
						'process_type_id' 	=> $procesTypeId,
						'item_type_id' 		=> $extItemTypeId,
						'unit_type_id' 		=> $UnitTypeIdd,
						'quantity' 			=> $itemIdQty,
						'financial_year' 	=> currentFinancialYear(),
						'created_by' 		=> $IndividualId,
						'created_at' 		=> $curDate,
						'status' 			=> 'Active',
					];
				}
				if (!empty($dataWPR)) {
					WorkProcessRequirement::insert($dataWPR);
				}
			}
			
			if (!empty(array_filter($reqItemIdArr))) 
			{	
				$data = [];
				foreach ($reqItemIdArr as $index => $wisId) 
				{
					$result 	= WarehouseItemStock::where('id', $wisId)->where('is_allotted_stock', 'No')->where('status', 'Active')->first();
					if (!$result) {
						continue;
					}
					$itemId     = $result->item_id;
					$dataI 		= Item::where('item_id', $itemId)->where('status', 'Active')->first();
					$ItemTypeId = $dataI->item_type_id ?? $result->item_type_id;
					$UnitTypeId = $dataI->unit_type_id ?? $result->unit_type_id;
					$qty 		= $quantity[$index];
					$data[] = [
						'work_order_id' 	=> $newworkOrderId,
						'item_id' 			=> $itemId,
						'wis_id' 			=> $wisId,
						'process_type_id' 	=> $procesTypeId,
						'item_type_id' 		=> $ItemTypeId,
						'unit_type_id' 		=> $UnitTypeId,
						'quantity' 			=> $qty,
						'financial_year' 	=> currentFinancialYear(),
						'created_by' 		=> $IndividualId,
						'created_at' 		=> $curDate,
						'status' 			=> 'Active',
					];
				}
				if (!empty($data)) {
					$res = WorkProcessRequirement::insert($data);
				}
			}

			WorkOrder::where('id', $newworkOrderId)->update([
				'work_req_send_by' => $IndividualId,
				'is_work_require_request_accepted' => null, 
				'work_req_send_date' => $curDate,
			]); 

			DB::commit();        
			Session::put('message', 'Work Requirement sent to Warehouse successfully.');
			Session::put('messageClass', 'successClass');
			return redirect('/show-workorders');
		} catch (\Throwable $e) {
			DB::rollBack();
			Session::put('message', 'Something went wrong: ' . $e->getMessage());
			Session::put("messageClass", "errorClass");
			return redirect()->back()->withInput();
		}
	}
  
	public function add_work_requisition_for_dyeing(Request $request)
	{
		$validator = Validator::make($request->all(), [
			"itemIdReq"             => "required",
			"work_order_id_req"     => "required",
			"ext_item_type_id"      => "required",
			"tot_req_quantity"      => "required",
			"req_item_id.*"         => "nullable|numeric",
			"req_quantity.*"        => "nullable|required_with:req_item_id.*|numeric|min:1",
		], [
			"itemIdReq.required"            => "Please select Item Name.",
			"work_order_id_req.required"    => "Please select your Work order type.",
			"ext_item_type_id.required"     => "Item type Id not found.",
			"tot_req_quantity.required"     => "Please enter your Dyed Quantity.",
			"req_item_id.*.numeric"         => "Item ID must be a number for each row.",
			"req_quantity.*.required_with"  => "Please enter Quantity for each row when Item is selected.",
			"req_quantity.*.numeric"        => "Quantity must be a number for each row.",
			"req_quantity.*.min"            => "Quantity must be at least 1 for each row.",
		]);

		if ($validator->fails()) {
			$errors = $validator->errors()->all();
			$errorMessage = implode("<br>", $errors);
			Session::put('message', $errorMessage);
			Session::put("messageClass", "errorClass");
			return redirect()->back()->withInput();
		}

		try {
			DB::beginTransaction();

			$itemTypeId = $request->ext_item_type_id;
			$hasReqLotNo = Schema::hasColumn('work_process_requirements', 'req_lot_no');

			if ($itemTypeId == '4' && $hasReqLotNo) {
				$maxReqLotNoData = DB::table('work_process_requirements')->select(DB::raw('MAX(CAST(req_lot_no AS UNSIGNED)) as max_req_lot_no'))->where('item_type_id', '=', '3')->first();
				$maxReqLotNo = $maxReqLotNoData->max_req_lot_no;
				$requestLotNo = $request->req_lot_no;

				if ($requestLotNo > $maxReqLotNo) {
					DB::rollBack();
					Session::put('message', 'You have entered a wrong lot number.');
					Session::put('messageClass', 'errorClass');
					return redirect()->back()->withInput();
				}
			}

			$workOrderId = (int) $request->work_order_id_req;
			$workOrder = WorkOrder::where('id', $workOrderId)->with('WorkOrderItem')->where('status', 'Active')->first();

			if (empty($workOrder)) {
				DB::rollBack();
				Session::put('message', 'Work order not found.');
				Session::put("messageClass", "errorClass");
				return redirect()->back()->withInput();
			}

			$dyeingColor = !empty($workOrder->WorkOrderItem[0]) ? $workOrder->WorkOrderItem[0]->dyeing_color : null;
			$userId = Auth::id();
			$individualId = User::where('id', $userId)->value('individual_id');
			$currentDate = now();
			$reqItemIdArr = array_values(array_filter((array)$request->req_item_id));
			$qtyArr = $request->req_quantity;
			$wisIdArr = array_values(array_filter((array) $request->wis_id));
			$dataWPR1 = [];
			$dataWPR2 = [];
			$mainWprId = null;

			foreach ($reqItemIdArr as $index => $itemId) {
				$item = Item::where('item_id', $itemId)->where('status', 'Active')->first();

				if ($item) {
					$qty = (float) ($qtyArr[$index] ?? 0);
					$dataWPR1[] = [
						'work_order_id'     => $workOrderId,
						'item_id'           => $itemId,
						'process_type_id'   => $workOrder->process_type_id,
						'item_type_id'      => $item->item_type_id,
						'unit_type_id'      => $item->unit_type_id,
						'quantity' => $qty,
						'financial_year' => currentFinancialYear(),
						'created_by' => $individualId,
						'created_at' => $currentDate,
						'status' => 'Active',
					];
				}
			}

			if (!empty($dataWPR1)) {
				WorkProcessRequirement::insert($dataWPR1);
			}

			if ($request->filled('tot_req_quantity')) {
				$itemTypeId = $request->ext_item_type_id;
				$unitTypeId = ItemType::where('item_type_id', $itemTypeId)->value('unit_type_id');

				$dataWPR2 = [
					'work_order_id'     => $workOrderId,
					'item_id'           => $request->itemIdReq,
					'wis_id'            => $wisIdArr[0] ?? null,
					'process_type_id'   => $workOrder->process_type_id,
					'item_type_id'      => $itemTypeId,
					'unit_type_id'      => $unitTypeId,
					'quantity' => $request->tot_req_quantity,
					'financial_year' => currentFinancialYear(),
					'created_by' => $individualId,
					'created_at' => $currentDate,
					'status' => 'Active',
				];

				if ($itemTypeId == '3' && $hasReqLotNo) {
					$maxReqLotNoData = DB::table('work_process_requirements')->select(DB::raw('MAX(CAST(req_lot_no AS UNSIGNED)) as max_req_lot_no'))->where('item_type_id', '=', '3')->where('is_accept', '!=', '2')->where('status', 'Active')->first();
					$maxReqLotNo = $maxReqLotNoData ? $maxReqLotNoData->max_req_lot_no : 0;

					if (!empty($request->req_lot_no)) {
						$dataWPR2['req_lot_no'] = $request->req_lot_no;
					} else {
						$dataWPR2['req_lot_no'] = $maxReqLotNo ? $maxReqLotNo + 1 : 1;
					}
				}

				if ($itemTypeId == '4') {
					$dataWPR2['dyeing_color'] = $dyeingColor;
					if ($hasReqLotNo) {
						$dataWPR2['req_lot_no'] = $request->req_lot_no;
					}
					if (Schema::hasColumn('work_process_requirements', 'dept_req_ids')) {
						$dataWPR2['dept_req_ids'] = !empty($wisIdArr) ? implode(',', $wisIdArr) : null;
					}
				}

				$dataWPR2 = $dataWPR2;
				$mainWprId = WorkProcessRequirement::insertGetId($dataWPR2);

				 
				 
			}

			if (!empty($dataWPR1) || !empty($dataWPR2)) {
				WorkOrder::where('id', $workOrderId)->update([
					'work_req_send_by' => $individualId,
					'is_work_require_request_accepted' => null,
					'work_req_send_date' => $currentDate
				]);

				DB::commit();

				Session::put('message', 'Work Requirement Send to Warehouse successfully.');
				Session::put("messageClass", "successClass");
			} else {
				DB::rollBack();

				Session::put('message', 'Something went wrong. Work Requirement Not Sent to Warehouse.');
				Session::put("messageClass", "errorClass");
			}
		} catch (\Throwable $e) {
			DB::rollBack();

			Session::put('message', 'Something went wrong. '.$e->getMessage());
			Session::put("messageClass", "errorClass");
			return redirect()->back()->withInput();
		}

		$redirectUrl = Session::pull('workorders_return_url', '/show-workorders');
		return redirect()->to($redirectUrl);
	}

	public function getWorkOrderDetails(Request $request)
	{
		$validator = Validator::make($request->all(), [
			'FId' => 'required|integer',
		]);

		if ($validator->fails()) {
			return response(json_encode([
				'status' => 'error',
				'message' => $validator->messages()->first(),
			]));
		}

		$workOrder = WorkOrder::with(['Item', 'ProcessType', 'WorkMachine', 'WorkMaster', 'WorkInspectionOne'])
			->where('id', (int) $request->FId)
			->where('status', '!=', 'Deleted')
			->first();

		if (!$workOrder) {
			return response(json_encode([
				'status' => 'error',
				'message' => 'Work order not found.',
			]));
		}

		$item 			= $workOrder->Item;
		$itemName 		= $workOrder->item_name ?: ($item->item_name ?? '');
		$itemTypeName 	= ItemType::where('item_type_id', $workOrder->item_type_id)->value('item_type_name') ?? '';
		$unitTypeId 	= $item->unit_type_id ?? null;
		$unitTypeName 	= $unitTypeId ? (UnitType::where('unit_type_id', $unitTypeId)->value('unit_type_name') ?? '') : '';
		$processName 	= $workOrder->ProcessType->process_name ?? $workOrder->process_type ?? '';
		$inspection 	= $workOrder->WorkInspectionOne;
		$machineName 	= $workOrder->WorkMachine->name ?? '';
		$masterName 	= $workOrder->WorkMaster->name ?? '';
		$requirements 	= WorkProcessRequirement::with(['Item', 'ItemType', 'UnitType'])->where('work_order_id', $workOrder->id)->where('status', 'Active')->orderBy('id')->get();
		
		if ($requirements->isEmpty()) {
			$requirementHtml = '<div class="alert alert-info small">No warehouse requirement found for this work order.</div>';
		} else {
			$requirementHtml = '<div class="table-responsive"><table class="table table-bordered table-condensed start-process-req-table">';
			$requirementHtml .= '<thead><tr><th>Required Item</th><th>Qty</th><th>Status</th></tr></thead><tbody>';

			foreach ($requirements as $requirement) 
			{
				$quantity 		= $requirement->required_quantity ?: $requirement->quantity;
				$unitName 		= $requirement->UnitType->unit_type_name ?? '';
				$itemTypeName 	= $requirement->ItemType->item_type_name ?? '';
				$status 		= ((int) $requirement->is_accept === 1) ? 'Accepted' : 'Pending';

				$requirementHtml .= '<tr>';
				$requirementHtml .= '<td>' . e($requirement->Item->item_name ?? 'N/A') . '</td>';
				$requirementHtml .= '<td>' . e(number_format((float) $quantity, 2)) . ' ' . e($unitName) . ' ' . e($itemTypeName) . '</td>';
				$requirementHtml .= '<td>' . e($status) . '</td>';
				$requirementHtml .= '</tr>';
			}

			$requirementHtml .= '</tbody></table></div>';
		}

		$machineOptions = '<option value="">Select Machine</option>';
		$machines = Machine::where('status', 'Active')
			->where(function ($query) use ($workOrder) {
				$query->where('process_wise', $workOrder->process_type_id)
					->orWhere('process_wise', (string) $workOrder->process_type_id);
			})
			->orderBy('name')->get(['id', 'name']);

		if ($machines->isEmpty()) {
			$machines = Machine::where('status', 'Active')->orderBy('name')->get(['id', 'name']);
		}

		foreach ($machines as $machine) {
			$machineOptions .= '<option value="' . e($machine->id) . '">' . e($machine->name) . '</option>';
		}

		$masterOptions = '<option value="">Select Master</option>';
		$masters = Individual::where('type', 'master')
			->where('status', 'Active')
			->where(function ($query) use ($workOrder) {
				$query->where('process_type_id', $workOrder->process_type_id)
					->orWhere('process_type_id', (string) $workOrder->process_type_id);
			})
			->orderBy('name')->get(['id', 'name']);

		foreach ($masters as $master) {
			$masterOptions .= '<option value="' . e($master->id) . '">' . e($master->name) . '</option>';
		}

		$warehouseOptions 	= '<option value="">Select Warehouse</option>';
		$warehouses 		= Warehouse::where('status', 'Active')->orderBy('warehouse_name', 'asc')->get(['id', 'warehouse_name']);

		foreach ($warehouses as $warehouse) 
		{
			$warehouseOptions .= '<option value="' . e($warehouse->id) . '">' . e($warehouse->warehouse_name) . '</option>';
		}

		$payload = [
			'status' => 'success',
			'itemId' => $workOrder->item_id,
			'workOrdId' => $workOrder->id,
			'ItemName' => e($itemName ?: 'N/A'),
			'processName' => e($processName),
			'RequestedItems' => $requirementHtml,
			'workRequirement' => $requirementHtml,
			'options' => $machineOptions,
			'masterOptions' => $masterOptions,
			'warehouses' => $warehouseOptions,
			'outputNextPro' => e($workOrder->ProcessType->output_name ?? ''),
			'outputUnit' => e($unitTypeName),
			'outputUnitType' => e($itemTypeName),
			'processtext' => e($processName),
			'machineId' => $workOrder->machine_id,
			'MachineName' => e($machineName ?: 'Not allocated'),
			'masterId' => $workOrder->master_ind_id,
			'MasterName' => e($masterName ?: 'Not allocated'),
			'inspTakaNumber' => e($inspection->insp_taka_number ?? ''),
			'inspEpi' => e($inspection->insp_epi ?? ''),
			'inspPpi' => e($inspection->insp_ppi ?? ''),
			'inspWidth' => e($inspection->insp_width ?? ''),
			'inspGsm' => e($inspection->insp_gsm ?? ''),
		];

		return response(json_encode($payload));
	}

	public function updateworkorder(Request $request) // checking
	{
		$validator = Validator::make($request->all(), [
			'work_order_id' => 'required|integer',
			'itemId' => 'required|integer',
			'masterId' => 'required|integer',
			'machineId' => 'nullable|integer',
			'process_started_remarks' => 'required|string|max:555',
		], [
			'work_order_id.required' => 'Work order not found.',
			'itemId.required' => 'Item not found.',
			'masterId.required' => 'Please select master.',
			'process_started_remarks.required' => 'Please enter process remarks.',
		]);

		if ($validator->fails()) {
			Session::put('message', $validator->messages()->first());
			Session::put('messageClass', 'errorClass');
			return redirect()->back()->withInput();
		}

		DB::beginTransaction();

		try {
			$user = Auth::user();
			$individualId = $user->individual_id ?? Auth::id();
			$workOrderId = (int) $request->work_order_id;

			$workOrder = WorkOrder::whereKey($workOrderId)->where('item_id', (int) $request->itemId)->where('status', 'Active')->first();

			if (!$workOrder) {
				throw new \RuntimeException('Work order not found.');
			}

			$isProcessMaster = Individual::where('id', (int) $request->masterId)
				->where('type', 'master')->where('status', 'Active')
				->where(function ($query) use ($workOrder) {
					$query->where('process_type_id', $workOrder->process_type_id)
						->orWhere('process_type_id', (string) $workOrder->process_type_id);
				})
				->exists();

			if (!$isProcessMaster) {
				throw new \RuntimeException('Please select process related master.');
			}

			$workOrder->master_ind_id = (int) $request->masterId;
			$workOrder->machine_id = $request->filled('machineId') ? (int) $request->machineId : $workOrder->machine_id;
			$workOrder->process_started_by = $individualId;
			$workOrder->process_started_date = now()->toDateString();
			$workOrder->process_started_remarks = $request->process_started_remarks;
			$workOrder->modified_by = $individualId;
			$workOrder->modified_at = now();
			$workOrder->save();

			DB::commit();

			Session::put('message', 'Process started successfully.');
			Session::put('messageClass', 'successClass');

			return redirect()->back();
		} catch (\Throwable $e) {
			DB::rollBack();
			report($e);
			Session::put('message', $e->getMessage());
			Session::put('messageClass', 'errorClass');
			return redirect()->back()->withInput();
		}
	}

	public function updateMachineWo(Request $request)
	{
		$request->validate([
			'work_order_id' => 'required|integer|exists:work_orders,id',
			'machine_id'    => 'required|integer|exists:machines,id',
		]);

		DB::beginTransaction();

		try {
			$wo = WorkOrder::find($request->input('work_order_id'));

			if (! $wo) {
				DB::rollBack();
				return response()->json(['status' => 'error', 'message' => 'Work order not found'], 404);
			}

			$wo->machine_id = $request->input('machine_id');
			$wo->save();

			$machineName = Machine::where('id', $request->input('machine_id'))->value('name');

			DB::commit();

			return response()->json([
				'status' => 'success',
				'machine_name' => $machineName,
				'machine_id' => (int) $request->input('machine_id'),
			]);
		} catch (\Throwable $e) {
			DB::rollBack();
			report($e);
			return response()->json(['status' => 'error', 'message' => 'Machine could not be updated.'], 500);
		}
	}
	
	public function updateMachine(Request $request)
	{
		$validator = Validator::make($request->all(), [
			'id' => 'required|integer|exists:work_process_requirements,id',
			'dyeing_machine_id' => 'nullable|integer|exists:machines,id',
			'woid' => 'nullable|integer|exists:work_orders,id'
		]);

		if ($validator->fails()) {
			return response()->json([
				'status' => 'error',
				'message' => $validator->errors()->first(),
				'errors' => $validator->errors(),
			], 422);
		}

		$id 		= $request->id;
		$woid 		= $request->woid;
		$machineId 	= $request->dyeing_machine_id ?: null;

		DB::beginTransaction();

		try {
		// fetch WPR and ensure it belongs to given work order (safety)
		$wpr = WorkProcessRequirement::find($id);
		if (!$wpr) {
			DB::rollBack();
			return response()->json(['status' => 'error', 'message' => 'Record not found'], 404);
		}

		$woid = $woid ?: $wpr->work_order_id;

		// fetch workorder
		$workorder = WorkOrder::select('id', 'process_type', 'process_type_id')->whereKey($woid)->first();

		if (!$workorder) {
			DB::rollBack();
			return response()->json(['status' => 'error', 'message' => 'Work order not found'], 404);
		}

		if ($wpr->work_order_id != $woid) {
			DB::rollBack();
			return response()->json(['status' => 'error', 'message' => 'Work order mismatch'], 422);
		}

		if (!Schema::hasColumn('work_process_requirements', 'dyeing_machine_id')) {
			DB::rollBack();
			return response()->json([
				'status' => 'error',
				'message' => 'Machine column is not available in work process requirements.',
			], 422);
		}

		$wpr->dyeing_machine_id = $machineId;
		$wpr->dye_m_set_date = now();

		$wpr->save();

		$savedMachineId = $wpr->dyeing_machine_id;

		$machine = $savedMachineId ? Machine::find($savedMachineId) : null;

		// optionally return a small workorder summary
		$workorderSummary = [
			'work_order_id' => $workorder->id,
			'process_type' => $workorder->process_type,
			'process_type_id' => $workorder->process_type_id
		];

		DB::commit();

		return response()->json([
			'status' => 'success',
			'machine_id' => $savedMachineId,
			'machine_name' => $machine ? $machine->name : '',
			'workorder' => $workorderSummary
		]);
		} catch (\Throwable $e) {
			DB::rollBack();
			report($e);
			return response()->json(['status' => 'error', 'message' => 'Machine could not be updated.'], 500);
		}
	}

	public function accept_item_for_work(Request $request)
	{
		$validator = Validator::make($request->all(), [
			'WarehouseOutItemId.*' => 'required|integer',
			'received_qty.*' => 'required|numeric',
			'work_order_Id' => 'required|integer',
		], [
			'WarehouseOutItemId.*.required' => 'Warehouse Out Item ID is required.',
			'WarehouseOutItemId.*.integer' => 'Warehouse Out Item ID must be an integer.',
			'received_qty.*.required' => 'Received quantity is required.',
			'received_qty.*.numeric' => 'Received quantity must be a number.',
			'work_order_Id.required' => 'Work order ID is required.',
			'work_order_Id.integer' => 'Work order ID must be an integer.',
		]); 

		if ($validator->fails()) 
		{
			Session::put('message', $validator->messages()->first());
			Session::put("messageClass", "errorClass");
			return redirect()->back()->withInput();
		}

		$workOrderId 	= (int) $request->work_order_Id;
		$insp_status 	= 'Complete';
		$user 			= Auth::user();
		$individualId 	= $user->individual_id ?? Auth::id() ?? 0;
		$now 			= now();
		$outItemIds 	= array_values((array) $request->WarehouseOutItemId);

		DB::beginTransaction();

		try {
			$workOrder = WorkOrder::whereKey($workOrderId)->where('status', '!=', 'Deleted')->first();

			if (!$workOrder) {
				throw new \Exception('Work order not found.');
			}

			$outItems = WarehouseOutItem::whereIn('id', $outItemIds)
				->where('work_order_id', $workOrderId)
				->where('status', 'Active')
				->lockForUpdate()
				->get()
				->keyBy('id');

			if ($outItems->count() !== count(array_unique($outItemIds))) {
				throw new \Exception('Invalid Warehouse Out Item ID or status.');
			}

			foreach ($outItemIds as $index => $outItemId) 
			{
				$outItem = $outItems->get((int) $outItemId);
				$receivedQty = (float) ($request->received_qty[$index] ?? 0);

				if (empty($outItem->work_pro_req_id)) {
					throw new \Exception('Invalid Warehouse Out Item ID or status.');
				}

				DB::table('work_process_received_items')->insert([
					'work_order_id' 				=> $workOrderId,
					'work_process_requirement_id' 	=> $outItem->work_pro_req_id,
					'item_id' 						=> $outItem->item_id,
					'item_type_id' 					=> $outItem->item_type_id,
					'process_type_id' 				=> $outItem->process_type_id,
					'received_quantity' 			=> $receivedQty,
					'received_meter' 				=> $receivedQty,
					'received_date' 				=> now()->toDateString(),
					'received_by' 					=> $individualId,
					'created_at' 					=> $now,
					'created_by' 					=> $individualId,
					'financial_year' 				=> currentFinancialYear(),
					'status' 						=> 'Active',
				]);
			}

			if ($insp_status === 'Complete') 
			{
				WorkOrder::whereKey($workOrderId)->update([
					'is_item_received_from_warehouse' => 'Yes',
					'item_received_in_department_by'  => $individualId,
					'modified_by' => $individualId,
					'modified_at' => $now,
				]);
			}

			DB::commit();

			Session::put('message', 'Work Item Accepted successfully.');
			Session::put("messageClass", "successClass");
		} catch (\Throwable $e) {
			DB::rollBack();

			Log::error('accept_item_for_work error: '.$e->getMessage(), [
				'work_order_id' => $workOrderId,
				'warehouse_out_item_ids' => $outItemIds,
			]);

			Session::put('message', 'Something went wrong: ' . $e->getMessage());
			Session::put("messageClass", "errorClass");
		}

		return redirect()->back()->withInput();
	}

	public function receive_work_item_in_warehouse(Request $request)
	{
		$validator = Validator::make($request->all(), [
			"warehouseId" => "required|integer",
			"warehouseCompId" => "required|integer",
			"emp_name" => "required|string|max:255",
			"ind_emp_id" => "required|integer",
			"work_order_id" => "required|integer",
			"gate_pass_no" => "required|array|min:1",
			"receiver_id" => "required|integer",
			"receiving_date" => "required",
			"process_type_id" => "required|integer",
			"item_type_id" => "required|integer",
			"item_name" => "required|string",
			"item_id" => "required|integer",
			"taka_number" => "required|array|min:1",
			"quan_size" => "required|array|min:1",
			"quan_size.*" => "required|numeric",
		]);

		if ($validator->fails()) {
			Session::put('message', $validator->messages()->first());
			Session::put("messageClass", "errorClass");
			return redirect()->back()->withInput();
		}


		$valueAt = function (string $key, int $index = 0) use ($request) {
			$value = $request->input($key);
			return is_array($value) ? ($value[$index] ?? ($value[0] ?? null)) : $value;
		};

		$inspId = (int) $request->insp_id;
		$gatePassNos = array_values((array) $request->gate_pass_no);
		$gatePassId = (int) ($gatePassNos[0] ?? 0);
		$workOrderId = (int) $request->work_order_id;
		$itemTypeId = (int) $valueAt('item_type_id');
		$processTypeId = (int) $valueAt('process_type_id');
		$itemId = (int) $request->item_id;
		$quantityRows = array_values((array) $request->quan_size);
		$totalQuantity = array_sum(array_map('floatval', $quantityRows));
		$receiveDate = date('Y-m-d', strtotime($request->receiving_date));
		$now = now();
		$user = Auth::user();
		$individualId = $user->individual_id ?? Auth::id() ?? 0;

		DB::beginTransaction();

		try {
			if (WarehouseItem::where('gate_pass_number', $gatePassId)->where('status', 'Active')->lockForUpdate()->exists()) {
				throw new \Exception('Duplicate entry. This Gatepass item has already been received in the warehouse.');
			}

			if (WarehouseItem::where('insp_id', $inspId)->where('status', 'Active')->lockForUpdate()->exists()) {
				throw new \Exception('Duplicate entry. This Inspected item has already been received in the warehouse.');
			}

			$workOrder = WorkOrder::whereKey($workOrderId)->first();

			if (!$workOrder) {
				throw new \Exception('Work Order details not found.');
			}

			$machineId = $request->filled('machine_id') ? $valueAt('machine_id') : ($workOrder->machine_id ?? null);
			$coatingType = $valueAt('coated_pvc');
			$dyeingColor = $valueAt('dyeing_color');
			$printJob = $valueAt('print_job');
			$extraJob = $valueAt('extra_job');

			$warehouseItemId = DB::table('warehouse_in_items')->insertGetId([
				'work_order_id' => $workOrderId,
				'insp_id' => $inspId,
				'process_type_id' => $processTypeId,
				'warehouse_id' => (int) $request->warehouseId,
				'ware_comp_id' => (int) $request->warehouseCompId,
				'receiver_id' => (int) $request->receiver_id,
				'ind_emp_id' => (int) $request->ind_emp_id,
				'emp_name' => $request->emp_name,
				'receive_date' => $receiveDate,
				'item_id' => $itemId,
				'insp_taka_number' => implode(', ', array_filter((array) $request->taka_number)),
				'dyeing_lot_number' => implode(', ', array_filter((array) $request->dyeing_lot_number)),
				'dyeing_taka_number' => implode(', ', array_filter((array) $request->dyeing_taka_number)),
				'fabric_fault_reason_id' => $valueAt('fabric_fault_reason_id') ?: 0,
				'item_type_id' => $itemTypeId,
				'unit_type_id' => 2,
				'machine_id' => $machineId,
				'master_id' => $workOrder->master_ind_id ?? null,
				'pur_item_name' => $valueAt('item_name'),
				'dyeing_color' => $dyeingColor,
				'coating_type' => $coatingType,
				'extra_job' => $extraJob,
				'print_job' => $printJob,
				'item_qty' => $totalQuantity,
				'gate_pass_number' => $gatePassId,
				'created_at' => $now,
				'financial_year' => currentFinancialYear(),
				'status' => 'Active',
			]);

			$openBalance = DB::table('warehouse_balance_items')
				->where('item_id', $itemId)
				->where('item_type_id', $itemTypeId)
				->where('unit_type_id', 2)
				->where('dyeing_color', $dyeingColor)
				->where('coating_type', $coatingType)
				->where('print_job', $printJob)
				->where('extra_job', $extraJob)
				->where('balance_status', 1)
				->lockForUpdate()
				->first();

			if ($openBalance) {
				DB::table('warehouse_balance_items')->where('id', $openBalance->id)->update(['balance_status' => 0]);
			}

			DB::table('warehouse_balance_items')->insert([
				'ware_in_item_id' => $warehouseItemId,
				'ware_out_item_id' => 0,
				'warehouse_id' => (int) $request->warehouseId,
				'ware_comp_id' => (int) $request->warehouseCompId,
				'receiver_id' => (int) $request->receiver_id,
				'receive_date' => $receiveDate,
				'item_id' => $itemId,
				'item_type_id' => $itemTypeId,
				'unit_type_id' => 2,
				'master_id' => $workOrder->master_ind_id ?? null,
				'machine_id' => $machineId,
				'op_item_qty' => $openBalance ? $openBalance->item_qty : 0,
				'in_item_qty' => $totalQuantity,
				'out_item_qty' => 0,
				'item_qty' => $openBalance ? ((float) $openBalance->item_qty + $totalQuantity) : $totalQuantity,
				'dyeing_color' => $dyeingColor,
				'coating_type' => $coatingType,
				'print_job' => $printJob,
				'extra_job' => $extraJob,
				'created_at' => $now,
				'financial_year' => currentFinancialYear(),
				'status' => 'Active',
				'balance_status' => 1,
			]);

			$stockRows = [];
			foreach ($quantityRows as $index => $quantity) {
				$stockRows[] = [
					'warehouse_item_id' => $warehouseItemId,
					'warehouse_id' => (int) $request->warehouseId,
					'ware_comp_id' => (int) $request->warehouseCompId,
					'work_order_id' => $workOrderId,
					'insp_id' => $inspId,
					'gate_pass_id' => (int) ($gatePassNos[$index] ?? $gatePassId),
					'quantity' => 1,
					'insp_quan_size' => (float) $quantity,
					'insp_allot_quan_size' => 0,
					'insp_bal_quan_size' => (float) $quantity,
					'quan_size_unit' => 'Meter',
					'machine_id' => $machineId,
					'receiver_id' => $individualId,
					'receive_date' => $receiveDate,
					'invoice_number' => $request->invoice_number,
					'fabric_fault_reason_id' => $valueAt('fault_reason_id', $index) ?: ($valueAt('fabric_fault_reason_id', $index) ?: 0),
					'insp_taka_number' => $valueAt('taka_number', $index),
					'dyeing_lot_number' => $valueAt('dyeing_lot_number', $index),
					'dyeing_taka_number' => $valueAt('dyeing_taka_number', $index),
					'insp_epi' => $valueAt('insp_epi', $index),
					'insp_ppi' => $valueAt('insp_ppi', $index),
					'insp_width' => $valueAt('insp_width', $index),
					'insp_gsm' => $valueAt('insp_gsm', $index),
					'purchase_date' => $request->purchase_date,
					'item_type_id' => $itemTypeId,
					'unit_type_id' => 2,
					'item_id' => $itemId,
					'packet_number' => $inspId.'-'.($index + 1),
					'item_remark' => $valueAt('item_remark', $index),
					'dyeing_color' => $dyeingColor,
					'coating_type' => $coatingType,
					'print_job' => $printJob,
					'extra_job' => $extraJob,
					'created_at' => $now,
					'financial_year' => currentFinancialYear(),
					'status' => 'Active',
				];
			}

			DB::table('warehouse_item_stocks')->insert($stockRows);

			WorkInspection::whereKey($inspId)->update([
				'item_interred_in_warehouse_by' => $individualId,
				'item_received_in_warehouse_by' => (int) $request->receiver_id,
				'item_received_in_warehouse_date' => $now,
				'is_item_received_in_warehouse' => 'Yes',
			]);

			GatePass::whereIn('id', array_filter(array_map('intval', $gatePassNos)))->update([
				'is_item_received_in_warehouse' => 'Yes',
			]);

			DB::commit();

			Session::put('message', 'Item Received in Warehouse successfully.');
			Session::put("messageClass", "successClass");
			return Route::has('show-workorder-inspection') ? redirect()->route('show-workorder-inspection') : redirect()->back();
		} catch (\Throwable $e) {
			DB::rollBack();
			Log::error('receive work item in warehouse error: '.$e->getMessage(), [
				'insp_id' => $inspId,
				'gate_pass_id' => $gatePassId,
				'work_order_id' => $workOrderId,
			]);

			Session::put('message', 'Something error to receive items in warehouse. '.$e->getMessage());
			Session::put("messageClass", "errorClass");
			return redirect()->back()->withInput();
		}
	}

	public function receiveWorkItemInDepartmentWarehouse(Request $request)
	{
		$validator = Validator::make($request->all(), [
			"warehouseId" => "required|integer",
			"warehouseCompId" => "required|integer",
			"emp_name" => "required|string|max:255",
			"ind_emp_id" => "required|integer",
			"work_order_id" => "required|integer",
			"gate_pass_no" => "required",
			"receiver_id" => "required|integer",
			"receiving_date" => "required",
			"process_type_id" => "required|array|min:1",
			"process_type_id.*" => "required|integer",
			"item_type_id" => "required|array|min:1",
			"item_type_id.*" => "required|integer",
			"item_id" => "required|integer",
			"quan_size" => "required|array|min:1",
			"quan_size.*" => "required|numeric",
			"taka_number" => "required|array|min:1",
		]);

		if ($validator->fails()) {
			Session::put('message', $validator->messages()->first());
			Session::put("messageClass", "errorClass");
			return redirect()->back()->withInput();
		}


		$valueAt = function (string $key, int $index = 0) use ($request) {
			$value = $request->input($key);
			return is_array($value) ? ($value[$index] ?? ($value[0] ?? null)) : $value;
		};

		$inspId = (int) $request->insp_id;
		$gatePassNos = array_values((array) $request->gate_pass_no);
		$gatePassId = (int) ($gatePassNos[0] ?? 0);
		$workOrderId = (int) $request->work_order_id;
		$itemTypeId = (int) $valueAt('item_type_id');
		$processTypeId = (int) $valueAt('process_type_id');
		$itemId = (int) $request->item_id;
		$quantityRows = array_values((array) $request->quan_size);
		$totalQuantity = array_sum(array_map('floatval', $quantityRows));
		$receiveDate = date('Y-m-d', strtotime($request->receiving_date));
		$now = now();
		$user = Auth::user();
		$individualId = $user->individual_id ?? Auth::id() ?? 0;

		DB::beginTransaction();

		try {
			if (WarehouseItem::where('gate_pass_number', $gatePassId)->where('status', 'Active')->lockForUpdate()->exists()) {
				throw new \Exception('Duplicate entry. This Gatepass item has already been received in the warehouse.');
			}

			if (WarehouseItem::where('insp_id', $inspId)->where('status', 'Active')->lockForUpdate()->exists()) {
				throw new \Exception('Duplicate entry. This Inspected item has already been received in the warehouse.');
			}

			$workOrder = WorkOrder::whereKey($workOrderId)->first();

			if (!$workOrder) {
				throw new \Exception('Work Order details not found.');
			}

			$machineId = $request->filled('machine_id') ? $valueAt('machine_id') : ($workOrder->machine_id ?? null);
			$coatingType = $valueAt('coated_pvc');
			$dyeingColor = $valueAt('dyeing_color');
			$printJob = $valueAt('print_job');
			$extraJob = $valueAt('extra_job');

			$warehouseItemId = DB::table('warehouse_in_items')->insertGetId([
				'work_order_id' => $workOrderId,
				'insp_id' => $inspId,
				'process_type_id' => $processTypeId,
				'warehouse_id' => (int) $request->warehouseId,
				'ware_comp_id' => (int) $request->warehouseCompId,
				'receiver_id' => (int) $request->receiver_id,
				'ind_emp_id' => (int) $request->ind_emp_id,
				'emp_name' => $request->emp_name,
				'receive_date' => $receiveDate,
				'item_id' => $itemId,
				'insp_taka_number' => implode(', ', array_filter((array) $request->taka_number)),
				'dyeing_lot_number' => implode(', ', array_filter((array) $request->dyeing_lot_number)),
				'dyeing_taka_number' => implode(', ', array_filter((array) $request->dyeing_taka_number)),
				'fabric_fault_reason_id' => $valueAt('fabric_fault_reason_id') ?: 0,
				'item_type_id' => $itemTypeId,
				'unit_type_id' => 2,
				'machine_id' => $machineId,
				'master_id' => $workOrder->master_ind_id ?? null,
				'pur_item_name' => $valueAt('item_name'),
				'dyeing_color' => $dyeingColor,
				'coating_type' => $coatingType,
				'extra_job' => $extraJob,
				'print_job' => $printJob,
				'item_qty' => $totalQuantity,
				'gate_pass_number' => $gatePassId,
				'created_at' => $now,
				'financial_year' => currentFinancialYear(),
				'status' => 'Active',
			]);

			$openBalance = DB::table('warehouse_balance_items')
				->where('item_id', $itemId)
				->where('item_type_id', $itemTypeId)
				->where('unit_type_id', 2)
				->where('dyeing_color', $dyeingColor)
				->where('coating_type', $coatingType)
				->where('print_job', $printJob)
				->where('extra_job', $extraJob)
				->where('balance_status', 1)
				->lockForUpdate()
				->first();

			if ($openBalance) {
				DB::table('warehouse_balance_items')->where('id', $openBalance->id)->update(['balance_status' => 0]);
			}

			DB::table('warehouse_balance_items')->insert([
				'ware_in_item_id' => $warehouseItemId,
				'ware_out_item_id' => 0,
				'warehouse_id' => (int) $request->warehouseId,
				'ware_comp_id' => (int) $request->warehouseCompId,
				'receiver_id' => (int) $request->receiver_id,
				'receive_date' => $receiveDate,
				'item_id' => $itemId,
				'item_type_id' => $itemTypeId,
				'unit_type_id' => 2,
				'master_id' => $workOrder->master_ind_id ?? null,
				'machine_id' => $machineId,
				'op_item_qty' => $openBalance ? $openBalance->item_qty : 0,
				'in_item_qty' => $totalQuantity,
				'out_item_qty' => 0,
				'item_qty' => $openBalance ? ((float) $openBalance->item_qty + $totalQuantity) : $totalQuantity,
				'dyeing_color' => $dyeingColor,
				'coating_type' => $coatingType,
				'print_job' => $printJob,
				'extra_job' => $extraJob,
				'created_at' => $now,
				'financial_year' => currentFinancialYear(),
				'status' => 'Active',
				'balance_status' => 1,
			]);

			$stockRows = [];
			foreach ($quantityRows as $index => $quantity) {
				$stockRows[] = [
					'warehouse_item_id' => $warehouseItemId,
					'warehouse_id' => (int) $request->warehouseId,
					'ware_comp_id' => (int) $request->warehouseCompId,
					'work_order_id' => $workOrderId,
					'insp_id' => $inspId,
					'gate_pass_id' => (int) ($gatePassNos[$index] ?? $gatePassId),
					'quantity' => 1,
					'insp_quan_size' => (float) $quantity,
					'insp_allot_quan_size' => 0,
					'insp_bal_quan_size' => (float) $quantity,
					'quan_size_unit' => 'Meter',
					'machine_id' => $machineId,
					'receiver_id' => $individualId,
					'receive_date' => $receiveDate,
					'invoice_number' => $request->invoice_number,
					'fabric_fault_reason_id' => $valueAt('fault_reason_id', $index) ?: ($valueAt('fabric_fault_reason_id', $index) ?: 0),
					'insp_taka_number' => $valueAt('taka_number', $index),
					'dyeing_lot_number' => $valueAt('dyeing_lot_number', $index),
					'dyeing_taka_number' => $valueAt('dyeing_taka_number', $index),
					'insp_epi' => $valueAt('insp_epi', $index),
					'insp_ppi' => $valueAt('insp_ppi', $index),
					'insp_width' => $valueAt('insp_width', $index),
					'insp_gsm' => $valueAt('insp_gsm', $index),
					'purchase_date' => $request->purchase_date,
					'item_type_id' => $itemTypeId,
					'unit_type_id' => 2,
					'item_id' => $itemId,
					'packet_number' => $inspId.'-'.($index + 1),
					'item_remark' => $valueAt('item_remark', $index),
					'dyeing_color' => $dyeingColor,
					'coating_type' => $coatingType,
					'print_job' => $printJob,
					'extra_job' => $extraJob,
					'created_at' => $now,
					'financial_year' => currentFinancialYear(),
					'status' => 'Active',
				];
			}

			DB::table('warehouse_item_stocks')->insert($stockRows);

			WorkInspection::whereKey($inspId)->update([
				'item_interred_in_warehouse_by' => $individualId,
				'item_received_in_warehouse_by' => (int) $request->receiver_id,
				'item_received_in_warehouse_date' => $now,
				'is_item_received_in_warehouse' => 'Yes',
			]);

			GatePass::whereIn('id', array_filter(array_map('intval', $gatePassNos)))->update([
				'is_item_received_in_warehouse' => 'Yes',
			]);

			DB::commit();

			Session::put('message', 'Item Received in Warehouse successfully.');
			Session::put("messageClass", "successClass");
			return Route::has('show-workorder-inspection') ? redirect()->route('show-workorder-inspection') : redirect()->back();
		} catch (\Throwable $e) {
			DB::rollBack();
			Log::error('receive work item in warehouse error: '.$e->getMessage(), [
				'insp_id' => $inspId,
				'gate_pass_id' => $gatePassId,
				'work_order_id' => $workOrderId,
			]);

			Session::put('message', 'Something error to receive items in warehouse. '.$e->getMessage());
			Session::put("messageClass", "errorClass");
			return redirect()->back()->withInput();
		}
	}

	public function closeWorkOrder(Request $request)
	{
		// validate input
		$validator = Validator::make($request->all(), [
			'FId' => 'required|numeric',
		]);

		if ($validator->fails()) {
			return response()->json(['success' => false, 'message' => $validator->messages()->first()], 422);
		}

		$workId = $request->input('FId');

		$user = Auth::user();
		if (!$user) {
			return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
		}

		$individualId = $user->individual_id;

		$workData = WorkOrder::whereKey($workId)->first();
		if (!$workData) {
			return response()->json(['success' => false, 'message' => 'Work Order not found'], 404);
		}

		// If already closed, return friendly message
		if ($workData->work_status === 'Complete') {
			return response()->json(['success' => false, 'message' => 'Work Order already closed'], 409);
		}

		DB::beginTransaction();

		try {
			$isUpdate = WorkOrder::whereKey($workId)
				->update([
					'work_status'       => 'Complete',
					'insp_status'       => 'Complete',
					'process_ended_by'  => $individualId,
				]);

			if ($isUpdate) {
				DB::commit();
				return response()->json(['success' => true]);
			}

			DB::rollBack();
			return response()->json(['success' => false, 'message' => 'Failed to update Work Order'], 500);
		} catch (\Throwable $e) {
			DB::rollBack();
			report($e);
			return response()->json(['success' => false, 'message' => 'Failed to update Work Order'], 500);
		}
	}
	
	public function deleteWorkOrder(Request $request)
	{
		$workId = $request->input('FId');
		$userId = Auth::id();
		$user 	= User::find($userId);

		if (!$user) {
			return response()->json(['success' => false, 'message' => 'User not found'], 404);
		}

		$individualId 	= $user->individual_id;
		$workData 		= WorkOrder::where('id', $workId)->where('status', '!=', 'Deleted')->first();

		if (!$workData) {
			return response()->json(['success' => false, 'message' => 'Work Order not found'], 404);
		}		 
		DB::beginTransaction();
		try {			 
			$workOrderUpdated = WorkOrder::where('id', $workId)
				->update([
					'status' 		=> 'Deleted',
					'deleted_date' 	=> now(),
					'deleted_by' 	=> $individualId,
					'modified_by' 	=> $individualId,
					'modified_at' 	=> now()
				]);

			if (!$workOrderUpdated) {
				throw new \Exception('Failed to delete Work Order');
			}			 
			$workDataItems = WorkOrderItem::where('work_order_id', $workId)->get();

			foreach ($workDataItems as $woItem) {
				$workOrderItemUpdated = WorkOrderItem::where('id', $woItem->id)
					->update([
						'status' 		=> 'Deleted',
						'deleted_date' 	=> now(),
						'deleted_by' 	=> $individualId,
						'modified_by' 	=> $individualId,
						'updated_at' 	=> now()
					]);

				if (!$workOrderItemUpdated) {
					throw new \Exception('Failed to delete Work Order Item');
				}
			}			 
			DB::commit();

			return response()->json(['success' => true]);

		} catch (\Exception $e) {			 
			DB::rollBack();
			return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
		}
	}
 
	
	 // इस function में `DB::beginTransaction()`, `try-catch`, `DB::commit()` और error आने पर `DB::rollBack()` वाला manual database transaction use करो।

 
	public function updateinspectionworkorder(Request $request)
	{		 
		$validator = Validator::make($request->all(), [
			"ins_item_id" => "required",
			"ins_work_order_id" => "required",
			"output_quan_size" => "required",
			"inspec_comment" => "required",
			"work_status" => "required",
		], [
			"ins_item_id.required" => "Item Not Found.",
			"ins_work_order_id.required" => "Work order Not Found.",
			"output_quan_size.required" => "Please provide us your output quantity.",
			"inspec_comment.required" => "Please enter your inspection comment.",
			"work_status.required" => "Please provide your work status.",
		]);

		if ($validator->fails()) 
		{
			$error = $validator->errors()->first();
			Session::put('message', $validator->messages()->first());
			Session::put("messageClass", "errorClass");
			return redirect()->back()->withInput();
		}

		$itemId 				= $request->ins_item_id;
		$workOrderId 			= $request->ins_work_order_id;
		$comment 				= $request->inspec_comment;
		$workStatus 			= $request->work_status;
		$workStatusProcess 		= $request->insp_work_status_process;
		$pageNumber 			= $request->input('page');
		$inspWeavingProcess 	= $request->insp_weaving_process;
		$weavingOrdmtr 			= $request->weaving_mtr;
		
		$takaNumber  = $request->insp_taka_number;
		$getWInspSql = WorkInspection::where('insp_taka_number', '=', $takaNumber)->first();
		if(!empty($getWInspSql->insp_taka_number)) 
		{
			$errorMessage = "Duplicate entry. This Beam Number item has already been received in the warehouse.";
			Session::put('message', $errorMessage);
			Session::put("messageClass", "errorClass");
			return redirect()->back()->withInput();            
		} 

		DB::beginTransaction();

		try {
		$insp_status 			= ($workStatusProcess == 'Yes') ? 'Complete' : 'Pending';
		$curDate  				= now();
		if($insp_status == 'Complete') 
		{
			WorkOrder::where('id', $workOrderId)->update(['insp_status' => $insp_status]);
			WorkOrder::where('id', $workOrderId)->update(['process_ended_date' => $curDate]);
			WorkOrder::where('id', $workOrderId)->update(['work_status' => $insp_status]);			
		}

		$warehouseId 		    = $request->insp_work_warehouse_id;
		$userId  				= Auth::id();
		$userD   				= User::find($userId);
		$IndividualId 			= $userD->individual_id;

		$dataOrder  = WorkOrder::where('id', '=', $workOrderId)->with('WorkOrderItem')->first();
		$dataPT     = ProcessItem::where('id', '>', $dataOrder->process_type_id)->first();
		$lastPsnl 	= $dataPT->process_sl_no_last + 1;
		$dataPr 	= ProcessRequirement::where('process_type_id', '=', $dataPT->id)->where('status', '=', 'Active')->first();
		$itemTypeId = $dataPr->item_type_id;
		$proTypeId  = $dataPT->id;
		$proType	= CommonController::getProcessTypeName($proTypeId);
		$shortcode  = $proType['shortcode'];
		$curDate  	= now();
		$processTypeId = $dataOrder->process_type_id;

		$output_quan_size 		= $request->output_quan_size;		
		$quantity 				= count($output_quan_size);
		$outputQuanSize 		= array_sum($output_quan_size);
		 

		$objWI = new WorkInspection;
		$objWI->work_order_id  				= $workOrderId;	
		$objWI->item_id  					= $itemId;		
		$objWI->insp_quantity  				= '1';
		$objWI->insp_quan_size  			= $outputQuanSize;
		$objWI->insp_beam_meter  			= $weavingOrdmtr;
		$objWI->insp_dyeing_process  		= $inspWeavingProcess;
		$objWI->insp_taka_number  			= $request->insp_taka_number;
		$objWI->insp_comment  				= $comment;
		$objWI->insp_work_status  			= $workStatus;
		$objWI->insp_work_status_process  	= $workStatusProcess;
		 
		$objWI->insp_work_warehouse_id  	= $warehouseId;
		$objWI->insp_status  				= $insp_status;
		$objWI->inspected_by  				= $IndividualId;

		if ($processTypeId == '1') 
		{
			$objWI->is_warehouse_accepted  				= 'Yes';
			$objWI->created_by  						= $IndividualId;
			$objWI->warehouse_accepted_by  				= $IndividualId;
			$objWI->warehouse_accept_date  				= $curDate;
			$objWI->is_item_received_in_warehouse  		= 'Yes';
			$objWI->item_received_in_warehouse_date  	= $curDate; 
		} 

		$objWI->status  					= 'Active';
		$objWI->financial_year  			= currentFinancialYear();
		$objWI->created_by  				= $IndividualId;
		$objWI->created_at  				= now();
		$is_Insaved							= $objWI->save();
		$lastInsertInspId 					= $objWI->id;
		
		WorkInspectionDetail::create([
			'work_insp_id' 				=> $lastInsertInspId,
			'item_id' 					=> $itemId,
			'work_order_id' 			=> $workOrderId,
			'output_quantity' 			=> $outputQuanSize, 
			'insp_beam_meter' 			=> $weavingOrdmtr, 	
			'insp_taka_number' 			=> $takaNumber,
			'inspection_comment' 		=> $comment,
			'insp_work_status_process' 	=> $workStatusProcess,
			'work_status' 				=> $workStatus,
			'fabric_fault_reason_id' 	=> $request->fabric_fault_id,
			'insp_work_warehouse_id' 	=> $warehouseId,
			'financial_year' 			=> currentFinancialYear(),
			'created_by' 				=> $IndividualId,
			'status' 					=> 'Active',
			'created_at' 				=> now()
		]);

		$processTypeId 	= $dataOrder->process_type_id;
		$curDate  		= now();
		if ($processTypeId == '1') 
		{
			$obj = WorkOrder::where('id', '=', $workOrderId)->update(['is_warehouse_accepted' => 'Yes', 'warehouse_accepted_by' => $IndividualId, 'warehouse_accept_date' => $curDate]);
		}
		if($is_Insaved) 
		{			 
			$objW = new WorkOrder;
			$objW->parent_work_order_id  				= $workOrderId;
			$objW->inspection_id  						= $lastInsertInspId;
			$objW->process_type  						= $shortcode;
			$objW->process_sl_no  						= $lastPsnl;
			$objW->user_id  							= $dataOrder->user_id;
			$objW->item_type_id  						= $itemTypeId;
			$objW->process_type_id  					= $dataPT->id;
			$objW->item_id  							= $dataOrder->item_id;
			$objW->item_name  							= $dataOrder->item_name;
			$objW->pcs  								= $dataOrder->pcs;
			$objW->cut  								= $dataOrder->cut;
			$objW->meter  								= $weavingOrdmtr;
			$objW->process_started_by					= 0;
			$objW->process_ended_by						= 0;
			$objW->process_inspected_by					= 0;
			$objW->process_started_remarks				= '';
			$objW->process_ended_remarks				= '';
			$objW->financial_year						= currentFinancialYear();
			$objW->created_by							= $IndividualId;
			$objW->status  								= 'Active';
			$objW->created_at  							= now();
			$is_savedW 									= $objW->save();
			$neworkOrderId 								= $objW->getKey();
			if($neworkOrderId)
			{
				foreach($dataOrder['WorkOrderItem'] as $soId)
				{
					$soItem 		= SaleOrderItem::where('id', '=', $soId->sale_order_item_id)->first();
					$customerId  	= SaleOrder::where('id', '=', $soItem->sale_order_id)->value('customer_id');
					$unit_type_id 	= Item::where('item_id', '=', $soItem->item_id)->value('unit_type_id');

					$obj2 = new WorkOrderItem;
					$obj2->work_order_id  					= $neworkOrderId;
					$obj2->customer_id  					= $customerId;
					$obj2->sale_order_id  					= $soItem->sale_order_id;
					$obj2->sale_order_item_id  				= $soItem->id;
					$obj2->item_type_id  					= $itemTypeId;
					$obj2->unit_type_id  					= $unit_type_id;
					$obj2->item_id  						= $soItem->item_id;
					$obj2->grey_quality  					= $soItem->grey_quality;
					$obj2->dyeing_color  					= $soItem->dyeing_color;
					$obj2->coating_type  					= $soItem->coating_type;
					$obj2->extra_job  						= $soItem->extra_job;
					$obj2->print_job  						= $soItem->print_job;
					$obj2->expect_delivery_date  			= $soItem->expect_delivery_date;
					$obj2->order_item_priority  			= $soItem->order_item_priority;
					$obj2->pcs  							= $soItem->pcs;
					$obj2->cut  							= $soItem->cut;
					$obj2->meter  							= $weavingOrdmtr;
					$obj2->financial_year					= currentFinancialYear();
					$obj2->created_by						= $IndividualId;
					$obj2->status  							= 'Active';
					$obj2->created_at  						= now();
					$is_saved 								= $obj2->save();
					$obj3  = SaleOrderItem::where('id', '=', $soId->sale_order_item_id)->update(['is_work_order_created'=> '1']);
				}
			}
			 

			$objPI = ProcessItem::where('id', '=', $proTypeId)->update(['process_sl_no_last'=> $lastPsnl]); 	 
			if ($processTypeId == '1') 
			{
				$dataOrder  = WorkOrder::where('id', '=', $workOrderId)->first();
				$dataPT     = ProcessItem::where('id', '>', $dataOrder->process_type_id)->first();				 
				$warehouseItem = new WarehouseItem([
					'warehouse_id' 		=> $warehouseId,
					'item_type_id' 		=> $itemTypeId,
					'insp_id' 			=> $lastInsertInspId,
					'work_order_id' 	=> $workOrderId,
					'insp_taka_number' 	=> $request->insp_taka_number,
					'unit_type_id' 		=> 4,
					'created_at' 		=> now(),
					'status' 			=> 'Active',
					'master_id' 		=> $dataOrder->master_ind_id,
					'machine_id' 		=> $dataOrder->machine_id,
					'pur_item_name' 	=> $dataOrder->item_name,
					'item_qty' 			=> $outputQuanSize,
					'item_id' 			=> $itemId,
					'process_type_id' 	=> $dataPT->id,
					'receive_date' 		=> now(),
					'receiver_id' 		=> $IndividualId,
					'financial_year' 	=> currentFinancialYear(),
					'created_by' 		=> $IndividualId,
				]);
				$isSavedWI 		= $warehouseItem->save();
				$lastInsertId 	= $warehouseItem->getKey();

				$opItemQty  = WarehouseBalanceItem::where('item_id', '=', $warehouseItem->item_id)
					->where('item_type_id', '=', $warehouseItem->item_type_id)
					->where('unit_type_id', '=', $warehouseItem->unit_type_id) 
					->where('balance_status', '=', '1')
					->first();

				if (!empty($opItemQty)) 
				{
					$wbId = $opItemQty->id;
					WarehouseBalanceItem::where('id', $wbId)->update(['balance_status' => '0']);
				}
				WarehouseBalanceItem::create([
					'ware_in_item_id'   => $warehouseItem->id,
					'ware_out_item_id'  => 0,
					'warehouse_id'      => $warehouseItem->warehouse_id,
					'ware_comp_id'      => $warehouseItem->ware_comp_id,
					'receiver_id'       => $warehouseItem->receiver_id,
					'receive_date'      => $warehouseItem->receive_date,
					'item_id'           => $warehouseItem->item_id,
					'item_type_id'      => $warehouseItem->item_type_id,
					'unit_type_id'      => $warehouseItem->unit_type_id,
					'master_id'         => $warehouseItem->master_id,
					'machine_id'        => $warehouseItem->machine_id,
					'op_item_qty'       => $opItemQty ? $opItemQty->item_qty : 0,
					'in_item_qty'       => $warehouseItem->item_qty,
					'out_item_qty'      => 0,
					'item_qty'          => $opItemQty ? ($opItemQty->item_qty + $warehouseItem->item_qty) : $warehouseItem->item_qty,
					'grey_quality'      => $warehouseItem->grey_quality,
					'dyeing_color'      => $warehouseItem->dyeing_color,
					'coating_type'      => $warehouseItem->coating_type,
					'print_job'         => $warehouseItem->print_job,
					'extra_job'         => $warehouseItem->extra_job,
					'financial_year'    => currentFinancialYear(),
					'created_by'        => $IndividualId,
					'created_at'        => now(),
					'status'            => 'Active',
				]);


				$warehouseItemStockData = [];
				$b = 1;
				foreach ($output_quan_size as $quanSize) 
				{
					// $packetNumber 	= CommonController::genrateBeamPacketNumber($itemTypeId);					
					$warehouseItemStockData[] = [
						'warehouse_item_id' 	=> $lastInsertId,
						'quantity' 				=> 1,
						'warehouse_id'      	=> $warehouseItem->warehouse_id,  
						'ware_comp_id'     		=> $warehouseItem->ware_comp_id,  		
						'work_order_id' 		=> $workOrderId,
						'insp_id' 				=> $lastInsertInspId,
						'insp_quan_size' 		=> $quanSize,
						'insp_allot_quan_size' 	=> 0,
						'insp_bal_quan_size' 	=> $quanSize,
						 
						'insp_taka_number' 		=> $request->insp_taka_number,		 						
						'quan_size_unit' 		=> 'Kg',
						'entry_type' 			=> 'IN',
						'insp_comment' 			=> $comment,
						'inspected_by' 			=> $IndividualId,
						'receive_date' 			=> now(),
						'item_id' 				=> $itemId,
						'item_type_id' 			=> $itemTypeId,
						'financial_year' 		=> currentFinancialYear(),
						'created_by' 			=> $IndividualId,
						'created_at' 			=> now(),
						'updated_at' 			=> now(),
						'status' 				=> 'Active',
					];
				$b++;
				}
				$flag = count($warehouseItemStockData);
				if ($flag > 0) {
					$is_savedWIS = WarehouseItemStock::insert($warehouseItemStockData);
				}
			}

			$objG = new GatePass;
			$objG->inspection_id 						= $lastInsertInspId;
			$objG->work_order_id 						= $request->ins_work_order_id;
			$objG->item_id 								= $itemId;
			$objG->item_type_id 						= $itemTypeId;
			$objG->unit_type_id 						= 4;
			$objG->qty_size 							= $outputQuanSize;
			$objG->insp_taka_number  					= $request->insp_taka_number; 
			$objG->insp_beam_meter  					= $weavingOrdmtr; 
			$objG->is_item_received_in_warehouse  		= 'Yes';
			 
			$objG->qty 									= $quantity;
			$objG->to_department 						= $processTypeId;
			$objG->to_warehouse 						= $warehouseId;
			$objG->gatepass_number 						= $lastPsnl;
			$objG->genrated_by 							= $IndividualId;
			$objG->print_date 							= null;
			$objG->inspec_comment 						= $comment;
			$objG->financial_year 						= currentFinancialYear();
			$objG->created_by 							= $IndividualId;
			$objG->status 								= 'Active';
			$objG->created_at 							= now();
			$is_savedG 									= $objG->save();

			DB::commit();

			Session::put('message', 'Work Inspection Updated successfully.');
			Session::put("messageClass", "successClass");
			return redirect("/show-workorders");
		} else {
			DB::rollBack();
			Session::put('message', 'Oops! Something went wrong.');
			Session::put("messageClass", "errorClass");
			return redirect("/show-workorders");
		}
		} catch (\Exception $e) {
			DB::rollBack();
			Session::put('message', 'Something went wrong: ' . $e->getMessage());
			Session::put("messageClass", "errorClass");
			return redirect()->back()->withInput();
		}
	}
	
	 
	public function update_weaving_inspec_process(Request $request)
	{
		$validator = Validator::make($request->all(), [
			"ins_item_id"              => "required|integer",
			"ins_work_order_id"        => "required|integer",
			"insp_taka_number"         => "required",
			
			"output_quan_size"         => "required|array",
			"output_quan_size.*"       => "required|numeric|min:1",
			
			"insp_epi"                 => "required|array",
			"insp_epi.*"               => "required|numeric|min:1",
			
			"insp_ppi"                 => "required|array",
			"insp_ppi.*"               => "required|numeric|min:1",
			
			"insp_width"               => "required|array",
			"insp_width.*"             => "required|numeric|min:1",
			
			"insp_gsm"                 => "required|array",
			"insp_gsm.*"               => "required|numeric|min:1",
			
			"inspec_comment"           => "required|string",
			"insp_work_status_process" => "required|in:Yes,No",
			"insp_dyeing_process"      => "required|in:Yes,No",
			"work_status"              => "required|in:Completed,Defective",
			"insp_work_warehouseId"    => "required|integer",
		], [
			"ins_item_id.required"              => "Item Not Found.",
			"ins_item_id.integer"               => "Item ID must be valid.",
			
			"ins_work_order_id.required"        => "Work order Not Found.",
			"ins_work_order_id.integer"         => "Work order ID must be valid.",
			
			"insp_taka_number.required"         => "Taka Number is required.",
			
			"output_quan_size.required"         => "Please provide output quantity.",
			"output_quan_size.*.required"       => "Each output quantity is required.",
			"output_quan_size.*.numeric"        => "Each output quantity must be numeric.",
			
			"insp_epi.required"                 => "EPI is required.",
			"insp_epi.*.required"               => "Each EPI is required.",
			"insp_epi.*.numeric"                => "Each EPI must be numeric.",
			
			"insp_ppi.required"                 => "PPI is required.",
			"insp_ppi.*.required"               => "Each PPI is required.",
			"insp_ppi.*.numeric"                => "Each PPI must be numeric.",
			
			"insp_width.required"               => "Width is required.",
			"insp_width.*.required"             => "Each Width value is required.",
			"insp_width.*.numeric"              => "Each Width must be numeric.",
			
			"insp_gsm.required"                 => "GSM is required.",
			"insp_gsm.*.required"               => "Each GSM is required.",
			"insp_gsm.*.numeric"                => "Each GSM must be numeric.",
			
			"inspec_comment.required"           => "Please enter your inspection comment.",
			
			"insp_work_status_process.required" => "Work status process is required.",
			"insp_work_status_process.in"       => "Work status process must be Yes or No.",
			
			"insp_dyeing_process.required"      => "Dyeing process is required.",
			"insp_dyeing_process.in"            => "Dyeing process must be Yes or No.",
			
			"work_status.required"              => "Please provide your work status.",
			"work_status.in"                    => "Work status must be Completed or Defective.",
			
			"insp_work_warehouseId.required"    => "Warehouse ID is required.",
			"insp_work_warehouseId.integer"     => "Warehouse ID must be an integer.",
		]);

		if ($validator->fails()) {
			$error = $validator->errors()->first();
			Session::put('message', $error);
			Session::put("messageClass", "errorClass");
			return redirect()->back()->withInput();
		}
		
		// echo "<pre>"; print_r($request->insp_epi[0]); exit;
   
		$takaNumber = $request->insp_taka_number;
		$getWInspSql = WorkInspection::where('insp_taka_number', $takaNumber)->first();

		// if (!empty($getWInspSql->insp_taka_number)) {
			// $errorMessage = "Duplicate entry. This Taka Number item has already been received in the warehouse.";
			// Session::put('message', $errorMessage);
			// Session::put("messageClass", "errorClass");
			// return redirect()->back()->withInput();
		// }

		DB::beginTransaction();

		try {
		$itemId                = $request->ins_item_id;
		$pageNumber            = $request->input('page');
		$inspDyeingProcess     = $request->insp_dyeing_process;
		$workOrderId           = $request->ins_work_order_id;
		$comment               = $request->inspec_comment;
		$workStatus            = $request->work_status;
		$workStatusProcess     = $request->insp_work_status_process;
		$output_quan_size      = $request->output_quan_size;
		$curDate               = now();
		$fabric_fault_reasonId = $workStatus ? $request->fabric_fault_id : 0;
		$quantity              = count($output_quan_size);
		$outputQuanSize        = array_sum($output_quan_size);
		$warehouseId           = $request->insp_work_warehouseId;
		$machineId             = $request->insp_work_machine_id ?: null;
		$insp_status           = ($workStatusProcess == 'Yes') ? 'Complete' : 'Pending';

		if ($insp_status == 'Complete') 
		{
			WorkOrder::where('id', $workOrderId)
				->update(['insp_status' => $insp_status, 'process_ended_date' => $curDate, 'work_status' => $insp_status]);
		}

		$userId      	= Auth::id();
		$userD       	= User::find($userId);
		$IndividualId 	= $userD->individual_id;
		$dataOrder 		= WorkOrder::where('id', $workOrderId)->with('WorkOrderItem')->first();
		$dataPT    		= ProcessItem::where('id', '>', $dataOrder->process_type_id)->first();
		$lastPsnl  		= $dataPT->process_sl_no_last + 1;
		$dataPr    		= ProcessRequirement::where('process_type_id', $dataPT->id)->where('status', 'Active')->first();
		$itemTypeId 	= $dataPr->item_type_id;
		$proTypeId  	= $dataPT->id;
		$proType    	= CommonController::getProcessTypeName($proTypeId);
		$shortcode  	= $proType['shortcode'];
		$processTypeId 	= $dataOrder->process_type_id;

		$objWI = new WorkInspection;
		$objWI->work_order_id             = $workOrderId;
		$objWI->item_id                   = $itemId;
		$objWI->insp_quantity             = '1';
		$objWI->insp_dyeing_process       = $inspDyeingProcess;
		$objWI->insp_quan_size            = $outputQuanSize;
		$objWI->insp_taka_number          = $request->insp_taka_number;
		$objWI->insp_comment              = $comment;
		$objWI->insp_work_status          = $workStatus;
		$objWI->insp_work_status_process  = $workStatusProcess;
		$objWI->fabric_fault_reason_id    = $fabric_fault_reasonId ?: null;
		$objWI->insp_work_warehouse_id    = $warehouseId;
		$objWI->machine_id                = $machineId;
		$objWI->insp_status               = $insp_status;
		$objWI->inspected_by              = $IndividualId;
		$objWI->financial_year            = currentFinancialYear();
		$objWI->created_by                = $IndividualId;
		$objWI->status                    = 'Active';
		$objWI->created_at                = $curDate;
		$is_Insaved                       = $objWI->save();
		$lastInsertInspId                 = $objWI->id;
		 
		$objWid = new WorkInspectionDetail;            
		$objWid->work_insp_id                  	= $lastInsertInspId;
		$objWid->item_id                        	= $itemId;
		$objWid->work_order_id                  	= $workOrderId;
		$objWid->output_quantity                	= $outputQuanSize; 
		$objWid->insp_taka_number              	= $request->insp_taka_number;
		$objWid->insp_epi              			= $request->insp_epi[0];
		$objWid->insp_ppi              			= $request->insp_ppi[0];
		$objWid->insp_width              		= $request->insp_width[0];
		$objWid->insp_gsm              			= $request->insp_gsm[0];
		$objWid->inspection_comment            	= $comment;
		$objWid->insp_work_status_process      	= $workStatusProcess;
		$objWid->insp_coating_process          	= $inspDyeingProcess;
		$objWid->work_status                   	= $workStatus;
		$objWid->fabric_fault_reason_id        	= $fabric_fault_reasonId ?: null;
		$objWid->insp_work_warehouse_id        	= $warehouseId; 
		$objWid->machine_id                    	= $machineId;
		$objWid->financial_year                	= currentFinancialYear();
		$objWid->created_by                    	= $IndividualId;
		$objWid->status                        	= 'Active';
		$objWid->created_at                    	= now();          
		$is_savedG                             	= $objWid->save();
		
		if ($is_Insaved) 
		{    
			if ($inspDyeingProcess == 'Yes') 
			{     
				// if ($workStatus == 'Completed') 
				// {
					$woiSql = WorkOrderItem::selectRaw('dyeing_color, MAX(item_id) AS item_id, MIN(sale_order_item_id) AS sale_order_item_id, SUM(pcs) AS totPcs, SUM(cut) AS totCut, SUM(meter) AS totMeter')->where('work_order_id', $workOrderId)->where('status', 'Active')->groupBy('dyeing_color')->get();
					foreach ($woiSql as $row) 
					{
						$saleOrderItemId = $row->sale_order_item_id; 
						$chkNxtOrd = WorkOrderItem::where('sale_order_item_id', $saleOrderItemId)
							->whereHas('WorkOrder', function ($query) {
								$query->where('process_type_id', 3);
							})
							->with('WorkOrder')->where('status', 'Active')->first(); 
						$chkNxtWoid = $chkNxtOrd->id ?? null;
					 	if (empty($chkNxtWoid)) 
					 	{
							$valueDyeingColor = strtolower(trim($row->dyeing_color));
							if ($valueDyeingColor !== 'no' && $valueDyeingColor !== 'not' && $valueDyeingColor !== '') 
							{                                 
								$objW = new WorkOrder;
								$objW->parent_work_order_id = $workOrderId;
								$objW->inspection_id        = $lastInsertInspId;
								$objW->process_type         = $shortcode;
								$objW->process_sl_no        = $lastPsnl;
								$objW->user_id              = $dataOrder->user_id;
								$objW->item_type_id         = $itemTypeId;
								$objW->process_type_id      = $dataPT->id;
								$objW->item_id              = $row->item_id;
								$objW->item_name            = $dataOrder->item_name;
								$objW->pcs                  = $row->totPcs;
								$objW->cut                  = $row->totCut;
								$objW->meter                = $row->totMeter;
								$objW->process_started_by   = 0;
								$objW->process_ended_by     = 0;
								$objW->process_inspected_by = 0;
								$objW->process_started_remarks = '';
								$objW->process_ended_remarks = '';
								$objW->financial_year       = currentFinancialYear();
								$objW->created_by           = $IndividualId;
								$objW->status               = 'Active';
								$objW->created_at           = now();
								$is_savedW                  = $objW->save();
								$neworkOrderId              = $objW->getKey();
								if ($neworkOrderId) 
								{    
									$dyeingColorRow = $row->dyeing_color;
									$woiSSql = WorkOrderItem::where('work_order_id', $workOrderId)->where('dyeing_color', $dyeingColorRow)->where('status', 'Active')->get();
									$customerNames 	= [];
									$colorNames 	= [];
									$cotingNames 	= [];
									$saleOrdId 		= [];
									$saleOrdItemId 	= [];
									foreach ($woiSSql as $valRow) 
									{                                    
										$objWO = new WorkOrderItem;
										$objWO->work_order_id      = $neworkOrderId;                         
										$objWO->item_type_id       = $itemTypeId;
										$objWO->unit_type_id       = 2;
										$objWO->item_id            = $valRow->item_id;
										$objWO->sale_order_id      = $valRow->sale_order_id;
										$objWO->sale_order_item_id = $valRow->sale_order_item_id;
										$objWO->customer_id        = $valRow->customer_id;
										$objWO->grey_quality       = $valRow->grey_quality;
										$objWO->dyeing_color       = $valRow->dyeing_color;
										$objWO->coating_type       = $valRow->coating_type;
										$objWO->extra_job          = $valRow->extra_job;
										$objWO->print_job          = $valRow->print_job;
										$objWO->expect_delivery_date = $valRow->expect_delivery_date;
										$objWO->order_item_priority = $valRow->order_item_priority;
										$objWO->pcs                = $valRow->pcs;
										$objWO->cut                = $valRow->cut;
										$objWO->meter              = $valRow->meter;
										$objWO->financial_year     = currentFinancialYear();
										$objWO->created_by         = $IndividualId;
										$objWO->status             = 'Active';
										$objWO->created_at         = now();
										$is_saved                  = $objWO->save();                                    
									}                                
								}

								////////////////////////////////////////////////////
								$customerNamesString = implode(', ', $customerNames);
								 
								$NewprocessTypeId = $dataPT->id;
								$masterData = Individual::where('type', 'master')
									->where('process_type_id', $NewprocessTypeId)
									->where('status', 'Active')
									->select('id', 'name', 'phone', 'whatsapp')
									->first();
								
								
								$masterName    = $masterData->name ?? 'Master';
								$masId         = $masterData->id ?? '39';
								$masWtAppNum   = $masterData->whatsapp ?? null; 
								$masPhNum      = $masterData->phone ?? null;  
								$workOrdNum    = $shortcode . '' . $lastPsnl; 
								$neworkOrderId = $objW->getKey();
								$pro_name 	   = $dataOrder->item_name;
								$totSentMtr    = $row->totMeter;							 
								$colorNames 	   = array_unique($colorNames);
								$colorNamesString  = implode(', ', $colorNames);								 
								$cotingNames 	   = array_unique($cotingNames);
								$cotingNamesString = implode(', ', $cotingNames);
								$soId 			   = $valRow->sale_order_item_id;
								$saleOrderId 	   = $valRow->sale_order_id;
								$customerId 	   = $valRow->customer_id;
 							 

								$newtempParams = [
									$masterName,     // {{1}} - Name
									$workOrdNum,     // {{2}} - W.O.No.
									$pro_name,       // {{3}} - Fabric
									$customerNamesString,       // {{4}} - Customer
									$totSentMtr,     // {{5}} - Quantity in Meter
									$colorNamesString,   // {{6}} - Dyeing
									$cotingNamesString,     // {{7}} - Coating
								];

								if (!empty($masWtAppNum)) 
								{
								// CommonController::sendWhatsAppNotificationToMaster($masWtAppNum, $newtempParams, $soId, $saleOrderId, $customerId, $masId, $neworkOrderId);
								}
								
								////////////////////////////////////
								
								$userId 	= Auth::id();
								$modeName 	= 'WorkOrder';
								$urlPage  	= $request->headers->get('referer', url('/show-workorders'));
								$mesg 		= (Auth::user()->name ?? 'User') .' New work order assigned.';
								$pageName 	= 'show-workorders';
								// CommonController::storeNotification($userId,$masId,$modeName,$urlPage,$mesg,$pageName); 
								
								//////////////////////////////////////////////////////		

								
							}                        
					 	}  
					}            
					ProcessItem::where('id', $proTypeId)->update(['process_sl_no_last'=> $lastPsnl]); 
				// }    
			}
			
			$objG = new GatePass;
			$objG->inspection_id               = $lastInsertInspId;
			$objG->work_order_id               = $request->ins_work_order_id;
			$objG->item_id                     = $itemId;
			$objG->item_type_id                = $itemTypeId;
			$objG->unit_type_id                = 2;
			$objG->insp_taka_number            = $request->insp_taka_number;
			$objG->fabric_fault_reason_id      = $fabric_fault_reasonId ?: null;
			$objG->qty_size                    = $outputQuanSize;
			$objG->qty                         = $quantity;
			$objG->to_department               = $processTypeId;
			$objG->to_warehouse                = $warehouseId;
			$objG->gatepass_number             = $lastPsnl;
			$objG->genrated_by                 = $IndividualId;
			$objG->print_date                  = null;
			$objG->inspec_comment              = $comment;
			$objG->financial_year              = currentFinancialYear();
			$objG->created_by                  = $IndividualId;
			$objG->status                      = 'Active';
			$objG->created_at                  = now();
			$is_savedG                         = $objG->save();
			
			Session::put('message', 'Work Inspection Updated successfully.');
			Session::put("messageClass", "successClass");
			// return redirect("/show-workorders?page=$pageNumber"); 
		} else {
			DB::rollBack();
			Session::put('message', 'Oops! Something went wrong.');
			Session::put("messageClass", "errorClass");
			return redirect()->back()->withInput();
		} 

		DB::commit();

		return redirect()->back()->withInput();
		} catch (\Exception $e) {
			DB::rollBack();
			Session::put('message', 'Something went wrong: ' . $e->getMessage());
			Session::put("messageClass", "errorClass");
			return redirect()->back()->withInput();
		}
	}
  	 
	public function update_dyeing_inspec_process(Request $request)
	{	
		$validator = Validator::make($request->all(), [
			"ins_item_id" 					=> "required",
			"ins_work_order_id" 			=> "required",
			"destination" 					=> "required",
			"output_quan_size" 				=> "required|array",
			"output_quan_size.*" 			=> "numeric|min:0",
			"dyeing_taka_number" 			=> "required|array",
			"insp_taka_number" 				=> "required|array",
			"greige_item_qty" 				=> "required|array",
			"greige_item_qty.*" 			=> "numeric",
			"inspec_comment" 				=> "required",
			"work_status" 					=> "required",
		], [
			"ins_item_id.required" 			=> "Item Not Found.",
			"ins_work_order_id.required" 	=> "Work order Not Found.",
			"destination.required" 			=> "Destination Not Found. Please select destination.",
			"output_quan_size.required" 	=> "Please provide your output quantity.",
			"output_quan_size.*.numeric" 	=> "Output quantity must be numeric.",
			"dyeing_taka_number.required" 	=> "Please provide your dyeing taka number.",
			"insp_taka_number.required" 	=> "Please provide your inspection taka number.",
			"greige_item_qty.required" 		=> "Please provide your greige item quantity.",
			"greige_item_qty.*.numeric" 	=> "Greige item quantity must be numeric.",
			"inspec_comment.required" 		=> "Please enter your inspection comment.",
			"work_status.required" 			=> "Please provide your work status.",
		]);

		$errors = [];		
		if ($validator->fails()) 
		{
			$errors = $validator->messages()->all();
		}

		if (!empty($errors))
		{
			Session::put('message', implode(", ", $errors));
			Session::put("messageClass", "errorClass");
			return redirect()->back()->withInput();
		}

		$token = $request->input('submission_token');

		if (!empty($token) && Cache::has($token)) 
		{
			return redirect()->back()->with('message', 'Duplicate submission detected.')->withInput();
		}

		if (!empty($token)) 
		{
			Cache::put($token, true, 300);
		}

		DB::beginTransaction();

		try {
			$takaNumber 			= $request->insp_taka_number;
			$workOrderId 			= $request->ins_work_order_id; 		 
			$itemId 				= $request->ins_item_id;
			$comment 				= $request->inspec_comment;
			$workStatus 			= $request->work_status;
			$workStatusProcess 		= $request->insp_work_status_process;
			$outputQuanSize 		= $request->output_quan_size;
			$shrinkageQuanSize 		= $request->input('shrinkage_quan_size', []);
			$curDate 				= now();
			$fabricFaultReasonInput = $request->input('fabric_fault_id', []);
			$fabricFaultReasonId 	= 0;
			if ($workStatus) {
				if (is_array($fabricFaultReasonInput)) {
					foreach ($fabricFaultReasonInput as $reasonId) {
						if ($reasonId !== null && $reasonId !== '') {
							$fabricFaultReasonId = $reasonId;
							break;
						}
					}
				} else {
					$fabricFaultReasonId = $fabricFaultReasonInput ?: 0;
				}
			}
			$quantity 				= count($outputQuanSize);
			$outputQuanSum 			= array_sum($outputQuanSize);
			$warehouseId 			= $request->insp_work_warehouseId;
			$inspStatus 			= ($workStatusProcess == 'Yes') ? 'Complete' : 'Pending';

			$dataOrder = WorkOrder::where('id', $workOrderId)->with('WorkOrderItem')->first();

			if (empty($dataOrder)) 
			{
				throw new \Exception("Work order not found.");
			}

			$poType = strtoupper(trim((string) $dataOrder->process_type));

			if ($inspStatus == 'Complete') 
			{
				WorkOrder::where('id', $workOrderId)->update([
					'insp_status' => $inspStatus,
					'process_ended_date' => $curDate,
					'work_status' => $inspStatus
				]);
			}

			$userId 		= Auth::id();
			$user 			= User::find($userId);
			$individualId 	= !empty($user) ? $user->individual_id : 0;

			$dataPT = ProcessItem::where('id', '>', $dataOrder->process_type_id)->first();

			if (empty($dataPT)) 
			{
				throw new \Exception("Next process not found.");
			}

			$lastPsnl = $dataPT->process_sl_no_last + 1;

			$dataPr = ProcessRequirement::where('process_type_id', $dataPT->id)->where('status', 'Active')->first();

			if (empty($dataPr)) 
			{
				throw new \Exception("Process requirement not found.");
			}

			$itemTypeId 	= $dataPr->item_type_id;
			$proTypeId 		= $dataPT->id;
			$proType 		= CommonController::getProcessTypeName($proTypeId);
			$shortcode 		= ($poType == 'JOB') ? 'JOB' : $proType['shortcode'];
			$processTypeId 	= $dataOrder->process_type_id;
			$dyingColor 	= WorkOrderItem::where('work_order_id', $workOrderId)->where('status', 'Active')->value('dyeing_color');
			$inspCoatingProcess = $request->insp_coating_process;

			$workInspection = new WorkInspection;
			$workInspection->fill([
				'work_order_id' 			=> $workOrderId,
				'item_id' 					=> $itemId,
				'insp_quantity' 			=> '1',
				'insp_dyeing_process' 		=> $inspCoatingProcess,
				'insp_quan_size' 			=> $outputQuanSum,
				'work_process_req_id' 		=> $request->insp_work_process_req_id,
				'shrinkage_quantity' 		=> array_sum($shrinkageQuanSize),
				'insp_taka_number' 			=> implode(',', $request->insp_taka_number),
				'dyeing_lot_number' 		=> $request->req_lot_no,
				'dyeing_taka_number' 		=> implode(',', $request->dyeing_taka_number),
				'destination' 				=> $request->destination ?? 'Warehouse',
				'insp_comment' 				=> $comment,
				'insp_work_status' 			=> $workStatus,
				'insp_work_status_process' 	=> $workStatusProcess,
				'fabric_fault_reason_id' 	=> $fabricFaultReasonId ?: null,
				'insp_work_warehouse_id' 	=> $warehouseId,
				'insp_status' 				=> $inspStatus,
				'inspected_by' 				=> $individualId,
				'machine_id' 				=> $request->insp_work_machine_id,
				'dyeing_color' 				=> $dyingColor,
				'financial_year' 			=> currentFinancialYear(),
				'created_by' 				=> $individualId,
				'status' 					=> 'Active',
				'created_at' 				=> now()
			]);
			$workInspection->save();

			$lastInsertInspId = $workInspection->id;

			foreach ($outputQuanSize as $index => $outputQuantity) 
			{
				if (!empty($outputQuantity)) 
				{
					$breakSizeText = !empty($request->output_quan_break_size[$index]) ? $request->output_quan_break_size[$index] : $outputQuantity;
					$breakSizes = explode('+', $breakSizeText);

					foreach ($breakSizes as $breakSize) 
					{
						$rowFabricFaultReasonId = is_array($fabricFaultReasonInput)
							? ($fabricFaultReasonInput[$index] ?? $fabricFaultReasonId)
							: $fabricFaultReasonId;

						if (trim($breakSize) == '') 
						{
							continue;
						}

						$workInspectionDetail = new WorkInspectionDetail;
						$workInspectionDetail->fill([
							'work_insp_id' 				=> $lastInsertInspId,
							'item_id' 					=> $itemId,
							'work_order_id' 			=> $workOrderId,
							'output_quantity' 			=> $breakSize,
							'dyeing_lot_number' 		=> $request->req_lot_no,
							'greige_item_qty' 			=> $request->greige_item_qty[$index],
							'shrinkage_quantity' 		=> !empty($shrinkageQuanSize[$index]) ? $shrinkageQuanSize[$index] : 0,
							'dyeing_taka_number' 		=> $request->dyeing_taka_number[$index],
							'insp_taka_number' 			=> $request->insp_taka_number[$index],
							'machine_id' 				=> $request->insp_work_machine_id,
							'insp_width' 				=> $request->insp_width,
							'insp_gsm' 					=> $request->insp_gsm,
							'inspection_comment' 		=> $comment,
							'insp_work_status_process' 	=> $workStatusProcess,
							'insp_coating_process' 		=> $inspCoatingProcess,
							'work_status' 				=> $workStatus,
							'fabric_fault_reason_id' 	=> $rowFabricFaultReasonId ?: null,
							'insp_work_warehouse_id' 	=> $warehouseId,
							'financial_year' 			=> currentFinancialYear(),
							'created_by' 				=> $individualId,
							'status' 					=> 'Active',
							'created_at' 				=> now()
						]);
						$workInspectionDetail->save();
					}
				}
			}

			$rejectQuanSize = $request->input('reject_quan_size', []);

			if (!empty(array_filter($rejectQuanSize))) 
			{
				foreach ($rejectQuanSize as $indexRej => $quanRej)
				{
					if (!empty($quanRej))
					{
						$rowRejectFabricFaultReasonId = is_array($fabricFaultReasonInput)
							? ($fabricFaultReasonInput[$indexRej] ?? $fabricFaultReasonId)
							: $fabricFaultReasonId;

						$workInspRejDetail = new WorkInspectionDetail;
						$workInspRejDetail->fill([
							'work_insp_id' 				=> $lastInsertInspId,
							'item_id' 					=> $itemId,
							'work_order_id' 			=> $workOrderId,
							'output_quantity' 			=> $quanRej,
							'dyeing_lot_number' 		=> $request->req_lot_no,
							'greige_item_qty' 			=> !empty($request->greige_item_qty[$indexRej]) ? $request->greige_item_qty[$indexRej] : 0,
							'dyeing_taka_number' 		=> !empty($request->dyeing_taka_number[$indexRej]) ? $request->dyeing_taka_number[$indexRej] : '',
							'insp_taka_number' 			=> !empty($request->insp_taka_number[$indexRej]) ? $request->insp_taka_number[$indexRej] : '',
							'machine_id' 				=> $request->insp_work_machine_id,
							'insp_width' 				=> $request->insp_width,
							'insp_gsm' 					=> $request->insp_gsm,
							'inspection_comment' 		=> $comment,
							'insp_work_status_process' 	=> $workStatusProcess,
							'insp_coating_process' 		=> $inspCoatingProcess,
							'work_status' 				=> $workStatus,
							'fabric_fault_reason_id' 	=> $rowRejectFabricFaultReasonId ?: null,
							'insp_work_warehouse_id' 	=> $warehouseId,
							'financial_year' 			=> currentFinancialYear(),
							'created_by' 				=> $individualId,
							'status' 					=> 'Active',
							'created_at' 				=> now()
						]);
						$workInspRejDetail->save();
					}
				}
			}

			if ($inspCoatingProcess == 'Yes') 
			{
				$woiSql = WorkOrderItem::selectRaw('coating_type, MAX(item_id) AS item_id, SUM(pcs) AS totPcs, SUM(cut) AS totCut, SUM(meter) AS totMeter')->where('work_order_id', $workOrderId)->where('status', 'Active')->groupBy('coating_type')->get();

				foreach ($woiSql as $row) 
				{
					$coatedVal 	= strtolower(trim((string) $row->coating_type));
					$isUnCoated = in_array($coatedVal, ['no', 'not', 'uncoated', 'un-coated', ''], true);

					$woiSSql = WorkOrderItem::where('work_order_id', $workOrderId)->where('coating_type', $row->coating_type)->where('status', 'Active')->get();

					if ($woiSSql->isEmpty()) 
					{
						continue;
					}

					$chkNxtOrd = null;

					$saleOrderItemIds = $woiSSql->pluck('sale_order_item_id')->filter()->unique()->values()->toArray();

					if (!empty($saleOrderItemIds)) 
					{
						$chkNxtOrd = WorkOrderItem::whereIn('sale_order_item_id', $saleOrderItemIds)->whereHas('WorkOrder', function ($query) use ($workOrderId) { $query->where('process_type_id', '=', 4); $query->where('parent_work_order_id', '=', $workOrderId); })->with('WorkOrder')->where('status', '=', 'Active')->first();
					}

					$chkNxtWoid = $chkNxtOrd ? $chkNxtOrd->id : null;

					if (!$isUnCoated && empty($chkNxtWoid)) 
					{
						$workOrder = new WorkOrder;
						$workOrder->fill([
							'parent_work_order_id' 				=> $workOrderId,
							'inspection_id' 					=> $lastInsertInspId,
							'process_type' 						=> $shortcode,
							'process_sl_no' 					=> $lastPsnl,
							'user_id' 							=> $dataOrder->user_id,
							'item_type_id' 						=> $itemTypeId,
							'process_type_id' 					=> $dataPT->id,
							'item_id' 							=> $row->item_id,
							'item_name' 						=> $dataOrder->item_name,
							'pcs' 								=> $row->totPcs,
							'cut' 								=> $row->totCut,
							'meter' 							=> $row->totMeter,
							'is_item_received_from_warehouse' 	=> 'Yes',
							'process_started_by' 				=> 0,
							'process_ended_by' 					=> 0,
							'process_inspected_by' 				=> 0,
							'process_started_remarks' 			=> '',
							'process_ended_remarks' 			=> '',
							'financial_year' 					=> currentFinancialYear(),
							'created_by' 						=> $individualId,
							'status' 							=> 'Active',
							'created_at' 						=> now()
						]);
						$workOrder->save();

						$newWorkOrderId = $workOrder->id;

						foreach ($woiSSql as $valRow) 
						{
							$workOrderItem = new WorkOrderItem;
							$workOrderItem->fill([
								'work_order_id' 		=> $newWorkOrderId,
								'item_type_id' 			=> $itemTypeId,
								'unit_type_id' 			=> 2,
								'item_id' 				=> $valRow->item_id,
								'sale_order_id' 		=> $valRow->sale_order_id,
								'sale_order_item_id' 	=> $valRow->sale_order_item_id,
								'customer_id' 			=> $valRow->customer_id,
								'grey_quality' 			=> $valRow->grey_quality,
								'dyeing_color' 			=> $valRow->dyeing_color,
								'coating_type' 			=> $valRow->coating_type,
								'extra_job' 			=> $valRow->extra_job,
								'print_job' 			=> $valRow->print_job,
								'expect_delivery_date' 	=> $valRow->expect_delivery_date,
								'order_item_priority' 	=> $valRow->order_item_priority,
								'pcs' 					=> $valRow->pcs,
								'cut' 					=> $valRow->cut,
								'meter' 				=> $valRow->meter,
								'financial_year' 		=> currentFinancialYear(),
								'created_by' 			=> $individualId,
								'status' 				=> 'Active',
								'created_at' 			=> now()
							]);
							$workOrderItem->save();
						}
					}
				}

				ProcessItem::where('id', $proTypeId)->update(['process_sl_no_last' => $lastPsnl]);
			}

			$completeWoiSql = WorkOrderItem::where('work_order_id', $workOrderId)->where('status', 'Active')->get();

			foreach ($completeWoiSql as $completeRow) 
			{
				$coatedVal 			= strtolower(trim((string) $completeRow->coating_type));
				$isUnCoated 		= in_array($coatedVal, ['no', 'not', 'uncoated', 'un-coated', ''], true);
				$saleOrderItemId 	= $completeRow->sale_order_item_id;

				if ($isUnCoated || !empty($saleOrderItemId)) 
				{
					if (!empty($saleOrderItemId)) 
					{
						SaleOrderItem::where('id', $saleOrderItemId)->update(['is_work_completed' => '1']);
						WorkOrderItem::where('id', $completeRow->id)->update(['is_work_completed' => '1']);
					}
				}
			}

			$gatePass = new GatePass;
			$gatePass->fill([
				'inspection_id' 			=> $lastInsertInspId,
				'work_order_id' 			=> $request->ins_work_order_id,
				'item_id' 					=> $itemId,
				'item_type_id' 				=> $itemTypeId,
				'unit_type_id' 				=> 2,
				'insp_taka_number' 			=> implode(',', array_filter((array) $request->insp_taka_number)),
				'dyeing_lot_number' 		=> $request->req_lot_no,
				'dyeing_taka_number' 		=> implode(',', array_filter((array) $request->dyeing_taka_number)),
				'fabric_fault_reason_id' 	=> $fabricFaultReasonId ?: null,
				'qty_size' 					=> $outputQuanSum,
				'qty' 						=> $quantity,
				'to_department' 			=> $processTypeId,
				'to_warehouse' 				=> $warehouseId,
				'gatepass_number' 			=> $lastPsnl,
				'genrated_by' 				=> $individualId,
				'dyeing_color' 				=> $dyingColor,
				'print_date' 				=> null,
				'inspec_comment' 			=> $comment,
				'financial_year' 			=> currentFinancialYear(),
				'created_by' 				=> $individualId,
				'status' 					=> 'Active',
				'created_at' 				=> now()
			]);
			$gatePass->save();		 

			DB::commit();

			if (!empty($token)) 
			{
				Cache::forget($token);
			}

			Session::put('message', 'Work Inspection Updated successfully.');
			Session::put('messageClass', 'successClass');
			 
		} catch (\Exception $e) {
			DB::rollBack();

			if (!empty($token)) 
			{
				Cache::forget($token);
			}

			Session::put('message', 'An unexpected error occurred. Message: ' . $e->getMessage() . ' | Line: ' . $e->getLine() . ' | Trace: ' . $e->getTraceAsString());
			Session::put('messageClass', 'errorClass');
		}

		return redirect()->back()->withInput();
	}
 
	public function update_coating_inspec_process(Request $request)
	{ 
		// echo "<pre>"; print_r($request->all()); exit;

		$validator = Validator::make($request->all(), [
			"ins_item_id" 			=> "required",
			"ins_work_order_id" 	=> "required",
			"dyeing_taka_number" 	=> "required|array|min:1",
			"insp_taka_number" 		=> "required|array|min:1",
			"greige_item_qty" 		=> "required|array|min:1",
			"output_quan_size" 		=> "required|array|min:1",
			"inspec_comment" 		=> "required",
			"work_status" 			=> "required",
			"insp_work_warehouseId" => "required|integer",
		], [
			"ins_item_id.required" 				=> "Item Not Found.",
			"ins_work_order_id.required" 		=> "Work order Not Found.",
			"dyeing_taka_number.required" 		=> "Dyeing taka number is required.",
			"dyeing_taka_number.array" 			=> "Dyeing taka number must be an array.",
			"dyeing_taka_number.min" 			=> "Dyeing taka number must have at least one item.",
			"insp_taka_number.required" 		=> "Inspection taka number is required.",
			"insp_taka_number.array" 			=> "Inspection taka number must be an array.",
			"insp_taka_number.min" 				=> "Inspection taka number must have at least one item.",
			"greige_item_qty.required" 			=> "Greige item quantity is required.",
			"greige_item_qty.array" 			=> "Greige item quantity must be an array.",
			"greige_item_qty.min" 				=> "Greige item quantity must have at least one item.",
			"output_quan_size.required" 		=> "Output quantity size is required.",
			"output_quan_size.array" 			=> "Output quantity size must be an array.",
			"output_quan_size.min" 				=> "Output quantity size must have at least one item.",
			"inspec_comment.required" 			=> "Please enter your inspection comment.",
			"work_status.required" 				=> "Please provide your work status.",
			"insp_work_warehouseId.required" 	=> "Please provide the warehouse ID.",
			"insp_work_warehouseId.integer" 	=> "Warehouse ID must be an integer.",
		]);

		if ($validator->fails()) 
		{
			Session::put('message', $validator->messages()->first());
			Session::put("messageClass", "errorClass");
			return redirect()->back()->withInput();
		} 
			
		DB::beginTransaction();

		try { 
			$userId 			 = Auth::id();
			$user 				 = User::find($userId);
			$individualId 		 = !empty($user) ? $user->individual_id : 0;
			
			$itemId              = $request->ins_item_id;
			$workOrderId         = $request->ins_work_order_id;
			$comment             = $request->inspec_comment;
			$workStatus          = $request->work_status;
			$workStatusProcess   = $request->insp_work_status_process;
			$outputQuanSize      = $request->output_quan_size ?? [];
			$curDate             = now();
			$fabricFaultReasonId = $request->fabric_fault_id ?: null;
			$quantity            = count($outputQuanSize);
			$outputQuanSum       = array_sum(array_filter($outputQuanSize, 'is_numeric'));
			$warehouseId         = $request->insp_work_warehouseId;
			$inspStatus          = ($workStatusProcess == 'Yes') ? 'Complete' : 'Pending';
	 
			$dataOrder = WorkOrder::where('id', $workOrderId)->with('WorkOrderItem')->first();

			if (empty($dataOrder)) 
			{
				throw new \Exception("Work order not found.");
			}

			$poType 		 = strtoupper(trim((string) $dataOrder->process_type));
			$processTypeId	 = $dataOrder->process_type_id;
			$printPosition	 = $dataOrder->print_position;
			
			$dataPT 	  	 = ProcessItem::where('id', '>', $dataOrder->process_type_id)->orderBy('id', 'asc')->first(); 
			$lastPsnl 	  	 = $dataPT ? $dataPT->process_sl_no_last + 1 : 1;
			$proTypeId 	  	 = $dataPT->id ?? null; 
			
			$dataPr       	 = ProcessRequirement::where('process_type_id', $proTypeId ?? null)->where('status', 'Active')->first();
			$itemTypeId   	 = $dataPr->item_type_id ?? null; 
			
			$processType  	 = 7;
			$dataPI       	 = ProcessItem::where('id', $processType)->first();
			$proSNo       	 = $dataPI->process_sl_no_last ?? 0; 
			$shortcode    	 = ($poType == 'JOB') ? 'JOB' : 'CP';

			if ($inspStatus == 'Complete') 
			{
				WorkOrder::where('id', $workOrderId)->update([
					'insp_status'       	=> $inspStatus,
					'process_ended_date'	=> $curDate,
					'work_status'       	=> $inspStatus
				]);
			}
			
			if ($workStatus == 'Completed') 
			{
				$woiData = WorkOrderItem::where('work_order_id', $workOrderId)->where('status', 'Active')->get();

				foreach ($woiData as $rowVal) 
				{
					if (!empty($rowVal->sale_order_item_id)) 
					{
						SaleOrderItem::where('id', $rowVal->sale_order_item_id)->update(['is_work_completed' => 1]);
					}
				}
			}

			$coatedPvc   = WorkOrderItem::where('work_order_id', $workOrderId)->where('status', 'Active')->value('coating_type');
			$dyeingColor = WorkOrderItem::where('work_order_id', $workOrderId)->where('status', 'Active')->value('dyeing_color');

			$workInspection = new WorkInspection();
			$workInspection->fill([
				'work_order_id'            => $workOrderId,
				'item_id'                  => $itemId,
				'insp_quantity'            => 1,
				'insp_quan_size'           => $outputQuanSum,
				'work_process_req_id' 	   => $request->insp_work_process_req_id,
				'shrinkage_quantity'       => array_sum($request->shrinkage_quan_size ?? []),
				'insp_taka_number'         => implode(',', $request->insp_taka_number ?? []),
				'dyeing_lot_number'        => $request->req_lot_no,
				'dyeing_taka_number'       => implode(',', $request->dyeing_taka_number ?? []),
				'destination'              => $request->destination ?? 'Warehouse',
				'insp_comment'             => $comment,
				'insp_work_status'         => $workStatus,
				'insp_work_status_process' => $workStatusProcess,
				'fabric_fault_reason_id'   => $fabricFaultReasonId,
				'insp_work_warehouse_id'   => $warehouseId,
				'insp_status'              => $inspStatus,
				'inspected_by'             => $individualId,
				'machine_id'               => $request->insp_work_machine_id,
				'dyeing_color'             => $dyeingColor,
				'coated_type'              => $coatedPvc,
				'financial_year'           => currentFinancialYear(),
				'created_by'               => $individualId,
				'status'                   => 'Active',
				'created_at'               => $curDate
			]);
			$workInspection->save();

			$lastInsertInspId = $workInspection->id;
			
			$inspTakaNumbers 	= [];
			$greigeItemQtys 	= [];
			$dyeingTakaNumbers 	= [];

			foreach ($outputQuanSize as $index => $quantity) 
			{
				if (!empty($quantity)) 
				{
					$inspTaka       = $request->insp_taka_number[$index] ?? null;
					$greigeQty      = $request->greige_item_qty[$index] ?? null;
					$dyeingTaka     = $request->dyeing_taka_number[$index] ?? null;
					$shrinkageQty   = $request->shrinkage_quan_size[$index] ?? null;
					$breakSizeStr   = $request->output_quan_break_size[$index] ?? '';

					$inspTakaNumbers[]   = $inspTaka;
					$greigeItemQtys[]    = $greigeQty;
					$dyeingTakaNumbers[] = $dyeingTaka;

					$breakSizes = explode('+', $breakSizeStr);

					foreach ($breakSizes as $breakSize) 
					{
						if (trim($breakSize) == '') 
						{
							continue;
						}

						$workInspectionDetail = new WorkInspectionDetail();
						$workInspectionDetail->fill([
							'work_insp_id'              => $lastInsertInspId,
							'item_id'                   => $itemId,
							'work_order_id'             => $workOrderId,
							'output_quantity'           => trim($breakSize),
							'dyeing_lot_number'         => $request->req_lot_no,
							'greige_item_qty'           => $greigeQty,
							'shrinkage_quantity'        => $shrinkageQty,
							'dyeing_taka_number'        => $dyeingTaka,
							'insp_width' 				=> $request->insp_width,
							'insp_gsm' 					=> $request->insp_gsm,
							'insp_taka_number'          => $inspTaka,
							'machine_id'                => $request->insp_work_machine_id,
							'inspection_comment'        => $comment,
							'insp_work_status_process'  => $workStatusProcess,
							'work_status'               => $workStatus,
							'fabric_fault_reason_id'    => $fabricFaultReasonId,
							'insp_work_warehouse_id'    => $warehouseId,
							'financial_year'            => currentFinancialYear(),
							'created_by'                => $individualId,
							'status'                    => 'Active',
							'created_at'                => now(),
						]);
						$workInspectionDetail->save();
					}
				}
			}

			if ($printPosition == 'after') 
			{
				$woiSql = WorkOrderItem::selectRaw('print_job, MAX(item_id) AS item_id, MIN(sale_order_item_id) AS sale_order_item_id, SUM(pcs) AS totPcs, SUM(cut) AS totCut, SUM(meter) AS totMeter')->where('work_order_id', $workOrderId)->groupBy('print_job')->where('status', 'Active')->get();

				foreach ($woiSql as $row) 
				{
					$printJobVal 		= strtolower(trim((string) $row->print_job));
					$saleOrderItemId 	= $row->sale_order_item_id;
					
					$chkNxtOrd = null;

					if (!empty($saleOrderItemId)) 
					{
						$chkNxtOrd = WorkOrderItem::where('sale_order_item_id', '=', $saleOrderItemId)->whereHas('WorkOrder', function ($query) use ($workOrderId) { $query->where('parent_work_order_id', '=', $workOrderId); })->with('WorkOrder')->where('status', '=', 'Active')->first();
					}

					$chkNxtWoid = $chkNxtOrd ? $chkNxtOrd->id : null;
					
					if ($printJobVal !== '' && empty($chkNxtWoid)) 
					{
						$workOrder = new WorkOrder;
						$workOrder->fill([
							'parent_work_order_id' 				=> $workOrderId,
							'inspection_id' 					=> $lastInsertInspId,
							'process_type' 						=> $shortcode,
							'process_sl_no' 					=> $lastPsnl,
							'user_id' 							=> $individualId,
							'item_type_id' 						=> $itemTypeId,
							'process_type_id' 					=> $processType,
							'item_id' 							=> $row->item_id,
							'item_name' 						=> $dataOrder->item_name,
							'pcs' 								=> $row->totPcs,
							'cut' 								=> $row->totCut,
							'meter' 							=> $row->totMeter,
							'print_position' 					=> 'after',
							'is_item_received_from_warehouse' 	=> 'Yes',
							'process_started_by' 				=> 0,
							'process_ended_by' 					=> 0,
							'process_inspected_by' 				=> 0,
							'process_started_remarks' 			=> '',
							'process_ended_remarks' 			=> '',
							'financial_year' 					=> currentFinancialYear(),
							'created_by' 						=> $individualId,
							'status' 							=> 'Active',
							'created_at' 						=> now()
						]);
						$workOrder->save();

						$newWorkOrderId = $workOrder->id;

						$woiSSql = WorkOrderItem::where('work_order_id', $workOrderId)->where('print_job', $row->print_job)->where('status', 'Active')->get();

						foreach ($woiSSql as $valRow) 
						{
							$workOrderItem = new WorkOrderItem;
							$workOrderItem->fill([
								'work_order_id' 		=> $newWorkOrderId,
								'item_type_id' 			=> $itemTypeId,
								'unit_type_id' 			=> 2,
								'item_id' 				=> $valRow->item_id,
								'sale_order_id' 		=> $valRow->sale_order_id,
								'sale_order_item_id' 	=> $valRow->sale_order_item_id,
								'customer_id' 			=> $valRow->customer_id,
								'grey_quality' 			=> $valRow->grey_quality,
								'dyeing_color' 			=> $valRow->dyeing_color,
								'coating_type' 			=> $valRow->coating_type,
								'extra_job' 			=> $valRow->extra_job,
								'print_job' 			=> $valRow->print_job,
								'expect_delivery_date' 	=> $valRow->expect_delivery_date,
								'order_item_priority' 	=> $valRow->order_item_priority,
								'pcs' 					=> $valRow->pcs,
								'cut' 					=> $valRow->cut,
								'meter' 				=> $valRow->meter,
								'financial_year' 		=> currentFinancialYear(),
								'created_by' 			=> $individualId,
								'status' 				=> 'Active',
								'created_at' 			=> now()
							]);
							$workOrderItem->save();
						}
					} 
					else if ($printJobVal == '' || !empty($saleOrderItemId)) 
					{
						if (!empty($saleOrderItemId)) 
						{
							SaleOrderItem::where('id', $saleOrderItemId)->update(['is_work_completed' => 1]);
						}
					}
				}
				
				$woiSql2 = WorkOrderItem::where('work_order_id', $workOrderId)->where('status', 'Active')->get();

				foreach ($woiSql2 as $row2) 
				{
					$printJobVal 		= strtolower(trim((string) $row2->print_job));
					$saleOrderItemId 	= $row2->sale_order_item_id;
					
					if ($printJobVal == '' || !empty($saleOrderItemId)) 
					{
						if (!empty($saleOrderItemId)) 
						{
							SaleOrderItem::where('id', $saleOrderItemId)->update(['is_work_completed' => 1]);
						}
					}
				}
				
				ProcessItem::where('id', $proTypeId)->update(['process_sl_no_last' => $lastPsnl]);
			} 
			
			$gatePass = new GatePass();
			$gatePass->fill([
				'inspection_id' 			=> $lastInsertInspId,
				'work_order_id' 			=> $workOrderId,
				'item_id' 					=> $itemId,
				'item_type_id' 				=> $itemTypeId,
				'unit_type_id'				=> 2,
				'insp_taka_number' 			=> implode(',', $inspTakaNumbers),
				'dyeing_lot_number' 		=> $request->req_lot_no,
				'dyeing_taka_number' 		=> implode(',', $dyeingTakaNumbers),
				'fabric_fault_reason_id' 	=> $fabricFaultReasonId,
				'qty_size' 					=> $outputQuanSum,
				'qty' 						=> $quantity,
				'to_department' 			=> $processTypeId,
				'to_warehouse' 				=> $warehouseId,
				'gatepass_number' 			=> $lastPsnl,
				'genrated_by' 				=> '',
				'dyeing_color' 				=> $dyeingColor,
				'coated_pvc' 				=> $coatedPvc,
				'print_date' 				=> '',
				'inspec_comment' 			=> $comment,
				'financial_year' 			=> currentFinancialYear(),
				'created_by' 				=> $individualId,
				'status' 					=> 'Active',
				'created_at' 				=> $curDate
			]);
			$gatePass->save(); 

			$workInspection->update([
				'insp_taka_number' 		=> implode(',', $inspTakaNumbers),
				'dyeing_taka_number' 	=> implode(',', $dyeingTakaNumbers),
			]);

			DB::commit();

			Session::put('message', 'Work Inspection Updated successfully.');
			Session::put("messageClass", "successClass");

			return redirect()->back()->withInput();

		} catch (\Exception $e) {
			DB::rollBack();

			Session::put('message', 'Something went wrong: ' . $e->getMessage());
			Session::put("messageClass", "errorClass");

			return redirect()->back()->withInput();
		}
	}
				
	public function updateCoatingPrintInspecProcess(Request $request)
	{ 
		// echo "<pre>"; print_r($request->all()); exit;
		$validator = Validator::make($request->all(), [
			"ins_item_id" 			=> "required",
			"ins_work_order_id" 	=> "required",
			"dyeing_taka_number" 	=> "required|array|min:1",
			"insp_taka_number" 		=> "required|array|min:1",
			"greige_item_qty" 		=> "required|array|min:1",
			"output_quan_size" 		=> "required|array|min:1",
			"inspec_comment" 		=> "required",
			"work_status" 			=> "required",
			"insp_work_warehouseId" => "required|integer",
		], [
			"ins_item_id.required" 			=> "Item Not Found.",
			"ins_work_order_id.required" 	=> "Work order Not Found.",
			"dyeing_taka_number.required" 	=> "Dyeing taka number is required.",
			"dyeing_taka_number.array" 		=> "Dyeing taka number must be an array.",
			"dyeing_taka_number.min" 		=> "Dyeing taka number must have at least one item.",
			"insp_taka_number.required" 	=> "Inspection taka number is required.",
			"insp_taka_number.array" 		=> "Inspection taka number must be an array.",
			"insp_taka_number.min" 			=> "Inspection taka number must have at least one item.",
			"greige_item_qty.required" 		=> "Greige item quantity is required.",
			"greige_item_qty.array" 		=> "Greige item quantity must be an array.",
			"greige_item_qty.min" 			=> "Greige item quantity must have at least one item.",
			"output_quan_size.required" 	=> "Output quantity size is required.",
			"output_quan_size.array" 		=> "Output quantity size must be an array.",
			"output_quan_size.min" 			=> "Output quantity size must have at least one item.",
			"inspec_comment.required" 		=> "Please enter your inspection comment.",
			"work_status.required" 			=> "Please provide your work status.",
			"insp_work_warehouseId.required" => "Please provide the warehouse ID.",
			"insp_work_warehouseId.integer" => "Warehouse ID must be an integer.",
		]);
		if ($validator->fails()) 
		{
			Session::put('message', $validator->messages()->first());
			Session::put("messageClass", "errorClass");
			return redirect()->back()->withInput();
		} 
			
		DB::beginTransaction();
		try { 
			$userId 			 = Auth::id();
			$individualId 		 = User::find($userId)->individual_id;
			
			$itemId              = $request->ins_item_id;
			$workOrderId         = $request->ins_work_order_id;
			$comment             = $request->inspec_comment;
			$workStatus          = $request->work_status;
			$workStatusProcess   = $request->insp_work_status_process;
			$outputQuanSize      = $request->output_quan_size ?? [];
			$curDate             = now();
			$fabricFaultReasonId = $request->fabric_fault_id ?: null;
			$quantity            = count($outputQuanSize);
			$outputQuanSum       = array_sum(array_filter($outputQuanSize, 'is_numeric'));
			$warehouseId         = $request->insp_work_warehouseId;
			$inspStatus          = ($workStatusProcess == 'Yes') ? 'Complete' : 'Pending';
	 
			$dataOrder 	  		 = WorkOrder::where('id', $workOrderId)->with('WorkOrderItem')->first();
			$processTypeId		 = $dataOrder->process_type_id;  
			
			// echo "<pre>"; print_r($dataOrder);
			
			$printPosition		 = $dataOrder->print_position;
			
			$dataPT 	  		 = ProcessItem::where('id', '>', $dataOrder->process_type_id)->orderBy('id', 'asc')->first(); 
			$lastPsnl 	  		 = $dataPT ? $dataPT->process_sl_no_last + 1 : 1;
			$proTypeId 	  		 = $dataPT->id ?? null; 
			
			
			// echo "<pre>";         print_r($proTypeId); exit;
			
			
			$dataPr       		 = ProcessRequirement::where('process_type_id', $proTypeId ?? null)->where('status', 'Active')->first();
			$itemTypeId   		 = $dataPr->item_type_id ?? null; 
			
			$processType  		 = 7;
			$dataPI       		 = ProcessItem::where('id', $processType)->first();
			$proSNo       		 = $dataPI->process_sl_no_last ?? 0; 
			$shortcode    		 = 'CP';


			if ($inspStatus == 'Complete') 
			{
				WorkOrder::where('id', $workOrderId)->update([
					'insp_status'       => $inspStatus,
					'process_ended_date'=> $curDate,
					'work_status'       => $inspStatus
				]);
			}
			
			if ($workStatus == 'Completed') 
			{
				$woiData = WorkOrderItem::where('work_order_id', $workOrderId)->where('status', 'Active')->get();
				foreach ($woiData as $rowVal) 
				{
					SaleOrderItem::where('id', $rowVal->sale_order_item_id)->update(['is_work_completed' => 1]);
				}
			}

			$coatedPvc   	= WorkOrderItem::where('work_order_id', $workOrderId)->where('status', 'Active')->value('coating_type');
			$dyeingColor 	= WorkOrderItem::where('work_order_id', $workOrderId)->where('status', 'Active')->value('dyeing_color');
			$printJob 		= WorkOrderItem::where('work_order_id', $workOrderId)->where('status', 'Active')->value('print_job');
			$extraJob 		= WorkOrderItem::where('work_order_id', $workOrderId)->where('status', 'Active')->value('extra_job');
			
			$woItemData     = WorkOrderItem::where('work_order_id', $workOrderId)
							->where('status', 'Active')
							->select('coating_type', 'dyeing_color', 'print_job', 'extra_job')
							->first();

			$workInspection = new WorkInspection();
			$workInspection->fill([
				'work_order_id'            => $workOrderId,
				'item_id'                  => $itemId,
				'insp_quantity'            => 1,
				'insp_quan_size'           => $outputQuanSum,
				'shrinkage_quantity'       => array_sum($request->shrinkage_quan_size ?? []),
				'insp_taka_number'         => implode(',', $request->insp_taka_number ?? []),
				'dyeing_lot_number'        => $request->req_lot_no,
				'dyeing_taka_number'       => implode(',', $request->dyeing_taka_number ?? []),
				'destination'              => $request->destination ?? 'Warehouse',
				'insp_comment'             => $comment,
				'insp_work_status'         => $workStatus,
				'insp_work_status_process' => $workStatusProcess,
				'fabric_fault_reason_id'   => $fabricFaultReasonId,
				'insp_work_warehouse_id'   => $warehouseId,
				'insp_status'              => $inspStatus,
				'inspected_by'             => $individualId,
				'machine_id'               => $request->insp_work_machine_id,
				'dyeing_color'             => $woItemData->dyeing_color ?? null,
				'coated_type'              => $woItemData->coating_type ?? null,
				'print_job'                => $woItemData->print_job ?? null,
				'extra_job'                => $woItemData->extra_job ?? null,
				'financial_year'           => currentFinancialYear(),
				'created_by'               => $individualId,
				'status'                   => 'Active',
				'created_at'               => $curDate
			]);
			$workInspection->save();
			$lastInsertInspId = $workInspection->id;
			
				$inspTakaNumbers 	= [];
				$greigeItemQtys 	= [];
				$dyeingTakaNumbers 	= [];			 
				foreach ($outputQuanSize as $index => $quantity) 
				{
					if (!empty($quantity)) 
					{
						$inspTaka       = $request->insp_taka_number[$index]   ?? null;
						$greigeQty      = $request->greige_item_qty[$index]    ?? null;
						$dyeingTaka     = $request->dyeing_taka_number[$index] ?? null;
						$shrinkageQty   = $request->shrinkage_quan_size[$index] ?? null;
						$breakSizeStr   = $request->output_quan_break_size[$index] ?? '';

						$inspTakaNumbers[]   = $inspTaka;
						$greigeItemQtys[]    = $greigeQty;
						$dyeingTakaNumbers[] = $dyeingTaka;

						$breakSizes = explode('+', $breakSizeStr);
						foreach ($breakSizes as $breakSize) {
							$workInspectionDetail = new WorkInspectionDetail();
							$workInspectionDetail->fill([
								'work_insp_id'              => $lastInsertInspId,
								'item_id'                   => $itemId,
								'work_order_id'             => $workOrderId,
								'output_quantity'           => trim($breakSize),
								'dyeing_lot_number'         => $request->req_lot_no,
								'greige_item_qty'           => $greigeQty,
								'shrinkage_quantity'        => $shrinkageQty,
								'dyeing_taka_number'        => $dyeingTaka,
								'insp_taka_number'          => $inspTaka,
								'machine_id'                => $request->insp_work_machine_id,
								'inspection_comment'        => $comment,
								'insp_work_status_process'  => $workStatusProcess,
								'work_status'               => $workStatus,
								'fabric_fault_reason_id'    => $fabricFaultReasonId,
								'insp_work_warehouse_id'    => $warehouseId,
								'financial_year'            => currentFinancialYear(),
								'created_by'                => $individualId,
								'status'                    => 'Active',
								'created_at'                => now(),
							]);
							$workInspectionDetail->save();
						}
					}
				}
	  
				$gatePass = new GatePass();
				$gatePass->fill([
					'inspection_id' 			=> $lastInsertInspId,
					'work_order_id' 			=> $workOrderId,
					'item_id' 					=> $itemId,
					'item_type_id' 				=> $itemTypeId,
					'unit_type_id'				=> 2,
					'insp_taka_number' 			=> implode(',', $inspTakaNumbers),
					'dyeing_lot_number' 		=> $request->req_lot_no,
					'dyeing_taka_number' 		=> implode(',', $dyeingTakaNumbers),
					'fabric_fault_reason_id' 	=> $fabricFaultReasonId,
					'qty_size' 					=> $outputQuanSum,
					'qty' 						=> $quantity,
					'to_department' 			=> $processTypeId,
					'to_warehouse' 				=> $warehouseId,
					'gatepass_number' 			=> $lastPsnl,
					'genrated_by' 				=> '',
					'dyeing_color' 				=> $dyeingColor,
					'coated_pvc' 				=> $coatedPvc,
					'print_date' 				=> '',
					'inspec_comment' 			=> $comment,
					'financial_year' 			=> currentFinancialYear(),
					'created_by' 				=> $individualId,
					'status' 					=> 'Active',
					'created_at' 				=> $curDate
				]);
				$gatePass->save(); 
				$workInspection->update([
					'insp_taka_number' 		=> implode(',', $inspTakaNumbers),
					'dyeing_taka_number' 	=> implode(',', $dyeingTakaNumbers),
				]);

			DB::commit();
			Session::put('message', 'Work Inspection Updated successfully.');
			Session::put("messageClass", "successClass");
			return redirect('/show-workorders');

		} catch (\Exception $e) {
			DB::rollBack();
			Session::put('message', 'Something went wrong: ' . $e->getMessage());
			Session::put("messageClass", "errorClass");
			return redirect()->back()->withInput();
		}
	}
	  
	public function workOrderTotals(Request $request)
	{
		$userId    = (int) Auth::id();
		$userIndId = Auth::user()?->individual_id;

		$cusSearch       = trim((string) $request->cus_search);
		$individualId    = trim((string) $request->individual_id);
		$itemSearch      = trim((string) $request->item_search);
		$ordNumSearch    = trim((string) $request->ordNumSearch);
		$colorSearch     = trim((string) $request->colorSearch);
		$LotNumSearch    = trim((string) $request->LotNumSearch);
		$recLotNumSerch  = trim((string) $request->recLotNumSearch);
		$priority        = trim((string) $request->priority);
		$workStatus      = trim((string) $request->work_status);
		$proceStatus     = trim((string) $request->process_status);
		$searchProcessId = array_values(array_filter((array) $request->search_process_id));
		$fromDate        = $request->from_date;
		$toDate          = $request->to_date;

		if ($cusSearch === '') {
			$individualId = '';
		}

		$yearRecord = trim((string) $request->year_record);

		if ($yearRecord === '' || !preg_match('/^\d{4}$/', $yearRecord)) {
			$yearRecord = (int) date('n') >= 4 ? (string) date('Y') : (string) ((int) date('Y') - 1);
		}

		if ((int) $yearRecord > 2100) {
			$selectedFinancialYear = $yearRecord;
			$yearRecord = '20' . substr($selectedFinancialYear, 0, 2);
		} else {
			$yearStart = (int) $yearRecord;
			$selectedFinancialYear = substr((string) $yearStart, -2) . substr((string) ($yearStart + 1), -2);
			$yearRecord = (string) $yearStart;
		}

		$startDate = Carbon::create((int) $yearRecord, 4, 1)->startOfDay();
		$endDate   = Carbon::create((int) $yearRecord + 1, 3, 31)->endOfDay();

		$processTypeId = Individual::where('id', $userIndId)->where('type', 'master')->where('status', 'Active')->value('process_type_id');

		$query = WorkOrder::query()
			->where('work_orders.status', 'Active')
			->whereBetween('work_orders.created_at', [$startDate, $endDate]);

		$permissions = [2 => [1, 2], 4 => [4, 6, 7]];

		if ($userId !== 1) {
			if ($userId === 21) {
				$query->where('work_orders.process_type_id', 3);
			} elseif ($userId === 11) {
				$query->whereIn('work_orders.process_type_id', [1, 2, 3, 4, 6, 7]);
			} elseif (in_array($userId, [13, 26], true) && (int) $processTypeId === 4) {
				$query->whereIn('work_orders.process_type_id', [4, 6, 7]);
			} elseif (isset($permissions[(int) $processTypeId])) {
				$query->whereIn('work_orders.process_type_id', $permissions[(int) $processTypeId]);
			} else {
				$query->where('work_orders.process_type_id', $processTypeId);
			}
		}

		if ($workStatus === '' || $workStatus === '1') {
			$query->where('work_orders.insp_status', 'Pending');
		} elseif ($workStatus === '2') {
			$query->where('work_orders.insp_status', 'Complete');
		}

		if ($proceStatus !== '') {
			$query->whereNull('work_orders.master_ind_id')
				->where('work_orders.is_item_received_from_warehouse', 'Yes')
				->where('work_orders.is_work_require_request_accepted', 'Yes')
				->where('work_orders.insp_status', 'Pending')
				->where('work_orders.work_status', 'Pending');
		}

		if ($cusSearch !== '' && $individualId !== '') {
			$customerIds = array_values(array_filter(array_map('trim', explode(',', $individualId))));

			$query->whereHas('WorkOrderItem', function ($q) use ($customerIds) {
				$q->whereIn('customer_id', $customerIds)->where('status', 'Active');
			});
		}

		if ($colorSearch !== '') {
			$query->whereHas('WorkOrderItem', function ($q) use ($colorSearch) {
				$q->where('dyeing_color', $colorSearch)->where('status', 'Active');
			});
		}

		if ($itemSearch !== '') {
			$query->where('work_orders.item_name', 'LIKE', '%' . $itemSearch . '%');
		}

		if ($LotNumSearch !== '') {
			$query->whereHas('WorkProcessRequirement', function ($q) use ($LotNumSearch) {
				$q->where('req_lot_no', $LotNumSearch)->where('status', 'Active')->where('is_accept', '1');
			});
		}

		if ($recLotNumSerch !== '') {
			$gatePassTable = (new GatePass)->getTable();

			$query->whereExists(function ($q) use ($gatePassTable, $recLotNumSerch) {
				$q->selectRaw('1')
					->from($gatePassTable)
					->whereColumn($gatePassTable . '.work_order_id', 'work_orders.parent_work_order_id')
					->where($gatePassTable . '.dyeing_lot_number', $recLotNumSerch)
					->where($gatePassTable . '.status', 'Active');
			});
		}

		if ($ordNumSearch !== '') {
			$query->whereHas('WorkOrderItem', function ($q) use ($ordNumSearch) {
				$q->where('status', 'Active')->whereHas('SaleOrder', function ($q) use ($ordNumSearch) {
					$q->where('sale_order_number', $ordNumSearch);
				});
			});
		}

		if ($priority !== '') {
			$query->whereHas('WorkOrderItem', function ($q) use ($priority) {
				$q->where('order_item_priority', 'LIKE', '%' . $priority . '%')->where('status', 'Active');
			});
		}

		if (!empty($searchProcessId)) {
			$query->whereIn('work_orders.process_type_id', ProcessItem::query()->select('id')->whereIn('id', $searchProcessId)->where('status', 'Active'));
		}

		if (!empty($fromDate) && !empty($toDate)) {
			$query->whereBetween('work_orders.created_at', [Carbon::parse($fromDate)->startOfDay(), Carbon::parse($toDate)->endOfDay()]);
		}

		$filteredWorkOrders = (clone $query)->select('work_orders.id', 'work_orders.process_type_id');

		$workOrderItemTotals = WorkOrderItem::query()
			->select('work_order_id')
			->selectRaw('SUM(COALESCE(meter, 0)) as total_meter')
			->groupBy('work_order_id');

		$workInspectionTotals = WorkInspection::query()
			->select('work_order_id')
			->selectRaw('SUM(COALESCE(insp_quan_size, 0)) as total_insp_quantity')
			->selectRaw('SUM(COALESCE(insp_beam_meter, 0)) as total_insp_beam_meter')
			->groupBy('work_order_id');

		$totals = DB::query()
			->fromSub($filteredWorkOrders, 'wo')
			->leftJoinSub($workOrderItemTotals, 'woi', 'woi.work_order_id', '=', 'wo.id')
			->leftJoinSub($workInspectionTotals, 'wi', 'wi.work_order_id', '=', 'wo.id')
			->selectRaw("
				COUNT(*) as total_count,
				COALESCE(SUM(COALESCE(woi.total_meter, 0)), 0) as total_meter,
				COALESCE(SUM(
					CASE
						WHEN wo.process_type_id > 2 THEN COALESCE(wi.total_insp_quantity, 0)
						ELSE COALESCE(wi.total_insp_beam_meter, 0)
					END
				), 0) as total_inspection_meter,
				COALESCE(SUM(
					COALESCE(woi.total_meter, 0) -
					CASE
						WHEN wo.process_type_id > 2 THEN COALESCE(wi.total_insp_quantity, 0)
						ELSE COALESCE(wi.total_insp_beam_meter, 0)
					END
				), 0) as total_pending_meter
			")
			->first();

		return response()->json([
			'success'    => true,
			'totMtr'     => (float) ($totals->total_meter ?? 0),
			'totInspMtr' => (float) ($totals->total_inspection_meter ?? 0),
			'totReqMtr'  => (float) ($totals->total_pending_meter ?? 0),
			'count'      => (int) ($totals->total_count ?? 0)
		]);
	}
	
	public function checkingDyedWorkOrder(Request $request)
	{
		$userIndId = Auth::user()?->individual_id;

		$cusSearch      = trim((string) $request->cus_search);
		$individualId   = trim((string) $request->individual_id);
		$itemSearch     = trim((string) $request->item_search);
		$ordNumSearch   = trim((string) $request->ordNumSearch);
		$colorSearch    = trim((string) $request->colorSearch);
		$LotNumSearch   = trim((string) $request->LotNumSearch);
		$recLotNumSerch = trim((string) $request->recLotNumSearch);
		$priority       = trim((string) $request->priority);
		$workStatus     = trim((string) $request->work_status);
		$proceStatus    = trim((string) $request->process_status);
		$fromDateInput  = $request->from_date;
		$toDateInput    = $request->to_date;

		if ($cusSearch === '') {
			$individualId = '';
		}

		$yearRecord = trim((string) $request->year_record);

		if ($yearRecord === '') {
			$yearRecord = (int) date('n') >= 4 ? (string) date('Y') : (string) ((int) date('Y') - 1);
		}

		if (!preg_match('/^\d{4}$/', $yearRecord)) {
			$yearRecord = (int) date('n') >= 4 ? (string) date('Y') : (string) ((int) date('Y') - 1);
		}

		if ((int) $yearRecord > 2100) {
			$selectedFinancialYear = $yearRecord;
			$yearRecord = '20' . substr($selectedFinancialYear, 0, 2);
		} else {
			$yearStart = (int) $yearRecord;
			$selectedFinancialYear = substr((string) $yearStart, -2) . substr((string) ($yearStart + 1), -2);
			$yearRecord = (string) $yearStart;
		}

		$startDate = Carbon::create((int) $yearRecord, 4, 1)->startOfDay();
		$endDate   = Carbon::create((int) $yearRecord + 1, 3, 31)->endOfDay();

		$query = WorkOrder::query()
			->where('status', 'Active')
			->where('process_type_id', 3)
			->whereBetween('created_at', [$startDate, $endDate])
			->with([
				'WorkOrderItem' => function ($q) {
					$q->select(
						'id', 'work_order_id', 'customer_id', 'sale_order_id', 'sale_order_item_id', 'item_id',
						'meter', 'grey_quality', 'dyeing_color', 'coating_type', 'extra_job', 'print_job',
						'expect_delivery_date', 'order_item_priority'
					)->with([
						'SaleOrder' => function ($q) {
							$q->select('id', 'sale_order_date', 'sale_order_number')->where('status', 'Active');
						},
						'SaleOrderItem' => function ($q) {
							$q->select(
								'id', 'sale_order_id', 'dlvr_cleared_by', 'dlvr_clear_date', 'is_work_completed',
								'pending_item_mtr', 'dlvr_cleared_reason', 'remarks'
							)->where('status', 'Active');
						}
					]);
				},

				'WorkProcessRequirement' => function ($q) {
					$q->select(
						'id', 'work_order_id', 'item_id', 'process_type_id', 'req_lot_no', 'req_fabric_type',
						'dyeing_machine_id', 'lab_req_status', 'is_lab_test_complete', 'status'
					)->orderByRaw("CASE WHEN req_lot_no IS NULL OR req_lot_no = '' THEN id ELSE 0 END ASC, req_lot_no ASC, id ASC");
				},

				'WarehouseOutItem' => function ($q) {
					$q->select('id', 'work_order_id', 'item_type_id', 'insp_taka_number', 'item_qty')
						->with([
							'WarehouseItem' => function ($q) {
								$q->select('id', 'beam_meter', 'item_qty', 'allotted_qty', 'invoice_number');
							}
						]);
				},

				'ProcessType', 'GatePass', 'Item', 'WorkReqSend', 'WorkInspection'
			])
			->orderByDesc('id');

		if ($workStatus === '2') {
			$query->where('insp_status', 'Complete');
		} elseif ($workStatus !== '0') {
			$query->where('insp_status', 'Pending');
		}

		if ($cusSearch !== '' && $individualId !== '') {
			$customerIds = array_filter(array_map('trim', explode(',', $individualId)));

			$query->whereHas('WorkOrderItem', function ($q) use ($customerIds) {
				$q->whereIn('customer_id', $customerIds)->where('status', 'Active');
			});
		}

		if ($colorSearch !== '') {
			$query->whereHas('WorkOrderItem', function ($q) use ($colorSearch) {
				$q->where('dyeing_color', $colorSearch)->where('status', 'Active');
			});
		}

		if ($itemSearch !== '') {
			$query->where('item_name', 'LIKE', '%' . $itemSearch . '%');
		}

		if ($LotNumSearch !== '') {
			$query->whereHas('WorkProcessRequirement', function ($q) use ($LotNumSearch) {
				$q->where('req_lot_no', $LotNumSearch)->where('status', 'Active')->where('is_accept', '1');
			});
		}

		if ($ordNumSearch !== '') {
			$query->whereHas('WorkOrderItem', function ($q) use ($ordNumSearch) {
				$q->where('status', 'Active')->whereHas('SaleOrder', function ($q) use ($ordNumSearch) {
					$q->where('sale_order_number', $ordNumSearch);
				});
			});
		}

		if (!empty($fromDateInput) && !empty($toDateInput)) {
			$fromDateTime = Carbon::parse($fromDateInput)->startOfDay();
			$toDateTime   = Carbon::parse($toDateInput)->endOfDay();

			$query->whereBetween('created_at', [$fromDateTime, $toDateTime]);
		}

		$totSumMtr = (clone $query)->sum('meter');
		$dataWI    = $query->paginate(12)->appends($request->except('_token'));

		$fromDate = $fromDateInput;
		$toDate   = $toDateInput;

		return view('frontend.workorders.show-dyed-workorders', compact(
			'dataWI', 'totSumMtr', 'cusSearch', 'individualId', 'itemSearch', 'ordNumSearch',
			'priority', 'fromDateInput', 'toDateInput', 'workStatus', 'colorSearch', 'LotNumSearch',
			'userIndId', 'proceStatus', 'recLotNumSerch', 'yearRecord', 'selectedFinancialYear',
			'fromDate', 'toDate'
		));
	} 
		
	public function accept_work_item_in_warehouse(Request $request)
	{
		$validator = Validator::make($request->all(), [
			'FId' => 'required|integer',
		]);

		if ($validator->fails()) {
			Session::put('message', $validator->messages()->first());
			Session::put('messageClass', 'errorClass');
			return redirect()->back()->withInput();
		}

		$userId = Auth::id();
		$userD = User::where('id', $userId)->first();
		$IndId = $userD->individual_id ?? $userId ?? 0;
		$curDate = now();
		$FId = (int) $request->FId;

		DB::beginTransaction();

		try {
			$inspection = WorkInspection::where('id', $FId)
				->where('status', '!=', 'Deleted')
				->where('is_deleted', 0)
				->first();

			if (!$inspection) {
				throw new \Exception('Inspection record not found.');
			}

			if ($inspection->is_warehouse_accepted == 'Yes') {
				throw new \Exception('This inspection is already accepted.');
			}

			$inspection->is_warehouse_accepted = 'Yes';
			$inspection->warehouse_accepted_by = $IndId;
			$inspection->warehouse_accept_date = $curDate;
			$inspection->modified_by = $IndId;
			$inspection->updated_at = $curDate;
			$inspection->save();

			if (!empty($inspection->work_order_id)) {
				WorkOrder::where('id', $inspection->work_order_id)
					->where('status', '!=', 'Deleted')
					->update([
						'is_warehouse_accepted' => 'Yes',
						'warehouse_accepted_by' => $IndId,
						'warehouse_accept_date' => $curDate,
						'modified_by' => $IndId,
						'modified_at' => $curDate,
					]);
			}

			DB::commit();

			Session::put('message', 'Accepted successfully.');
			Session::put('messageClass', 'successClass');
			return redirect()->back()->withInput();
		} catch (\Throwable $e) {
			DB::rollBack();
			Log::error('accept_work_item_in_warehouse error: '.$e->getMessage(), [
				'inspection_id' => $FId,
				'user_id' => $userId,
			]);

			Session::put('message', 'Sorry, we encountered an error processing your request. ' . $e->getMessage());
			Session::put('messageClass', 'errorClass');
			return redirect()->back()->withInput();
		}
	}
 		

	public function denyWorkInspection(Request $request) 
	{
		$id = (int) $request->input('id', $request->input('FId'));

		DB::beginTransaction();

		try {
			$inspection = WorkInspection::where('id', $id)
				->where('status', '!=', 'Deleted')
				->first();

			if (!$inspection) {
				DB::rollBack();
				return response()->json(['success' => false, 'message' => 'Inspection not found.'], 404);
			}

			$curDate = now();
			$userId = Auth::id();

			$inspection->status = 'Deleted';
			$inspection->is_deleted = 1;
			$inspection->modified_by = $userId;
			$inspection->updated_at = $curDate;
			$inspection->save();

			WorkInspectionDetail::where('work_insp_id', $inspection->id)->update([
				'status' => 'Deleted',
				'modified_by' => $userId,
				'updated_at' => $curDate,
			]);

			GatePass::where('inspection_id', $inspection->id)->update([
				'status' => 'Deleted',
				'is_deleted' => 1,
				'modified_by' => $userId,
				'modified_at' => $curDate,
			]);

			DB::commit();

			return response()->json(['success' => true, 'message' => 'Inspection denied successfully.']);
		} catch (\Throwable $e) {
			DB::rollBack();
			report($e);
			return response()->json(['success' => false, 'message' => 'Inspection could not be denied. ' . $e->getMessage()], 500);
		}
	}

	public function deleteGpInspDetails(Request $request)  // checking
	{
		$gatePassId = (int) $request->input('id', $request->input('FId'));

		DB::beginTransaction();

		try {
			$gatePass = GatePass::where('id', $gatePassId)
				->where('status', '!=', 'Deleted')
				->first();

			if (!$gatePass) {
				DB::rollBack();
				return response()->json(['success' => false, 'message' => 'Gatepass not found.'], 404);
			}

			if ($gatePass->is_item_received_in_warehouse == 'Yes') {
				DB::rollBack();
				return response()->json(['success' => false, 'message' => 'Received gatepass cannot be deleted.'], 422);
			}

			$curDate = now();
			$userId = Auth::id();
			$inspectionId = (int) $gatePass->inspection_id;

			$gatePass->status = 'Deleted';
			$gatePass->is_deleted = 1;
			$gatePass->modified_by = $userId;
			$gatePass->modified_at = $curDate;
			$gatePass->save();

			if ($inspectionId > 0) {
				$activeGatePassCount = GatePass::where('inspection_id', $inspectionId)
					->where('status', '!=', 'Deleted')
					->count();

				if ($activeGatePassCount === 0) {
					WorkInspection::where('id', $inspectionId)->update([
						'status' => 'Deleted',
						'is_deleted' => 1,
						'modified_by' => $userId,
						'updated_at' => $curDate,
					]);

					WorkInspectionDetail::where('work_insp_id', $inspectionId)->update([
						'status' => 'Deleted',
						'modified_by' => $userId,
						'updated_at' => $curDate,
					]);
				}
			}

			DB::commit();

			return response()->json(['success' => true, 'message' => 'Gatepass deleted successfully.']);
		} catch (\Throwable $e) {
			DB::rollBack();
			report($e);
			return response()->json(['success' => false, 'message' => 'Gatepass could not be deleted. ' . $e->getMessage()], 500);
		}
	}

	public function decidePrinting(Request $request)
	{
		DB::beginTransaction();

		try {
			$workOrder = WorkOrder::whereKey((int) $request->input('work_order_id'))->first();

			if (!$workOrder) {
				DB::rollBack();
				return response()->json(['success' => false, 'message' => 'Work order not found.'], 404);
			}

			$position = $request->input('print_position', $request->input('position', 'none'));
			$workOrder->print_position = in_array($position, ['before', 'after', 'none'], true) ? $position : 'none';
			$workOrder->modified_by = Auth::id();
			$workOrder->modified_at = now();
			$workOrder->save();

			DB::commit();

			return response()->json(['success' => true, 'print_position' => $workOrder->print_position]);
		} catch (\Throwable $e) {
			DB::rollBack();
			report($e);
			return response()->json(['success' => false, 'message' => 'Print decision could not be updated.'], 500);
		}
	}


	public function print_workorder_gatepass($id)
	{
		if (ctype_digit((string) $id)) {
			$GpId = (int) $id;
		} else {
			$base64Decoded = base64_decode((string) $id, true);
			if ($base64Decoded !== false && ctype_digit(trim($base64Decoded))) {
				$GpId = (int) trim($base64Decoded);
			} else {
				$GpId = (int) dec((string) $id);
			}
		}
		$userId = Auth::id();
		$userD = User::find($userId);
		$individualId = $userD->individual_id ?? null;
		$dataInd = $individualId
			? Individual::where('id', $individualId)->where('status', 'Active')->first()
			: null;
		$currentDate = now();
		$compData = Company::find(1);
		$dataGp = GatePass::whereKey($GpId)->where('status', '!=', 'Deleted')->first();
		if (!$dataGp) {
			$dataGp = GatePass::where('work_order_id', $GpId)
				->where('status', '!=', 'Deleted')
				->orderByDesc('id')
				->first();
			$GpId = $dataGp->id ?? $GpId;
		}

		if (!$dataGp) {
			Session::put('message', 'Gatepass Not found.');
			Session::put('messageClass', 'errorClass');
			return redirect()->back()->withInput();
		}

		$data = WorkOrder::with(['WarehouseItem', 'WorkOrderItem'])
			->whereKey((int) $dataGp->work_order_id)
			->where('status', '!=', 'Deleted')
			->first();

		if (!$data || empty($data->process_type_id)) {
			Session::put('message', 'Work Order Not found.');
			Session::put('messageClass', 'errorClass');
			return redirect()->back()->withInput();
		}

		$firstWorkOrderItem = $data->WorkOrderItem->first();
		$data->sale_order_item_id = $firstWorkOrderItem->sale_order_item_id ?? '';
		$dataGp->created = $dataGp->created_at ?? $dataGp->print_date ?? $currentDate;
		$dataGp->coated_pvc = $dataGp->coated_pvc ?? $dataGp->coating_type ?? '';

		if ($compData) {
			$compData->another_phone = $compData->another_phone ?? $compData->alternate_phone ?? '';
		}

		DB::beginTransaction();

		try {
			if (empty($dataGp->print_date)) {
				GatePass::whereKey($GpId)->update([
					'print_date' => $currentDate->toDateString(),
					'print_count' => DB::raw('print_count + 1'),
					'modified_at' => $currentDate,
				]);
			} else {
				GatePass::whereKey($GpId)->increment('print_count');
			}

			$dataGp = GatePass::whereKey($GpId)->first();
			$dataGp->created = $dataGp->created_at ?? $dataGp->print_date ?? $currentDate;
			$dataGp->coated_pvc = $dataGp->coated_pvc ?? $dataGp->coating_type ?? '';

			if (Schema::hasTable('gate_pass_print_logs')) {
				DB::table('gate_pass_print_logs')->insert([
					'gate_pass_id' => $GpId,
					'user_id' => $userId,
					'printed_from' => request()->ip(),
					'printed_at' => $currentDate,
					'print_count' => $dataGp->print_count,
					'financial_year' => currentFinancialYear(),
					'created_by' => $userId,
					'created_at' => $currentDate,
					'modified_by' => $userId,
					'modified_at' => $currentDate,
					'status' => 'Active',
				]);
			}

			DB::commit();
		} catch (\Throwable $e) {
			DB::rollBack();
			report($e);
			Session::put('message', 'Gatepass print could not be updated.');
			Session::put('messageClass', 'errorClass');
			return redirect()->back()->withInput();
		}

		$toDepart = '';
		if (!empty($dataGp->to_department)) {
			$toDepart = ProcessItem::whereKey((int) $dataGp->to_department)->value('process_name') ?: '';
		}
		if ($toDepart === '' && !empty($data->process_type_id)) {
			$toDepart = ProcessItem::whereKey((int) $data->process_type_id)->value('process_name') ?: '';
		}
		$warehouseName = CommonController::getWarehouseName($dataGp->to_warehouse);

		return view('frontend.workorder.print-workorder-gatepass', compact('data', 'toDepart', 'compData', 'dataInd', 'GpId', 'dataGp', 'warehouseName'));
	}
	
	public function show_workorder_inspection_report(Request $request)
	{ 	
		  
		$userId 	= Auth::id();
		$userD 		= User::find($userId);	 
		$userIndId 	= (int) ($userD->individual_id ?? 0); 
		
		$currentUrl = $request->fullUrl();
        session(['currentUrl' => $currentUrl]);
		
		$qsearch 		= trim($request->qsearch);
		$processTypeId 	= $request->process_type_id;
		$receiverName 	= $request->receiver_name;
		$cusSearch 		= $request->cus_search;
		$customerId 	= $request->customer_id;
		$receiverId 	= $request->receiver_id;
		$senderId 		= $request->sender_id;
		$senderName 	= $request->sender_name;
		$isAccepted 	= $request->isAccepted;
		$LotNumSearch 	= $request->LotNumSearch;
		
		$allowedProcessIds = [1, 2, 3, 4, 5, 6, 7];   
		
		$recvWhDate = (!empty($request->receive_date)) ? date('Y-m-d', strtotime($request->receive_date)) : '';

		$query = WorkInspection::where('status', 'Active')->where('is_deleted', 0)->with('WorkOrder', 'WorkOrder.WorkOrderItem', 'WorkOrder.Item')->orderByDesc('id');
		
		$query->whereHas('WorkOrder', function ($q) use ($allowedProcessIds) {
			$q->whereIn('process_type_id', $allowedProcessIds);
		});

		
		if (!empty($isAccepted)) {
			$query->where('is_item_received_in_warehouse', '=', $isAccepted)->where('status', 'Active');
		}
		if (!empty($customerId)) {
			$workorderids = WorkOrderItem::where('customer_id', '=', $customerId)->where('status', 'Active')->pluck('work_order_id')->implode(',');
			$query->whereIn('work_order_id', explode(',', $workorderids));
		}
		 
		if (!empty($qsearch)) {			
			$workorderids = WorkOrder::where(DB::raw("concat(item_name)"), 'LIKE', '%' . $qsearch . '%')->where('status', 'Active')->pluck('id')->implode(',');
			$query->whereIn('work_order_id', explode(',', $workorderids));
		}
		/* if (!empty($processTypeId)) {
			$workorderids = WorkOrder::where('process_type_id', '=', $processTypeId)->where('status', '=', '1')->pluck('work_order_id')->implode(',');
			$query->whereIn('work_order_id', explode(',', $workorderids));
		} */
		
		if (!empty($processTypeId) && in_array($processTypeId, $allowedProcessIds)) {
			$query->whereHas('WorkOrder', function ($q) use ($processTypeId) {
				$q->where('process_type_id', $processTypeId);
			});
		}
		
		
		
		
		if (!empty($receiverId)) {
			$query->where('item_received_in_warehouse_by', '=', $receiverId)->where('status', 'Active');
		}
		if (!empty($senderId)) {
			$query->where('inspected_by', '=', $senderId)->where('status', 'Active');
		}
		if (!empty($recvWhDate)) {
			$query->where('item_received_in_warehouse_date', '>=', $recvWhDate)->where('status', 'Active');
		}
		
		if (!empty($LotNumSearch)) {
			$query->where('dyeing_lot_number', '=', $LotNumSearch)->where('status', 'Active');
		}
		
		$dataWI = $query->paginate(20)->appends(request()->except('_token'));

		$dataPI = ProcessItem::where('status', 'Active')->get();
		return view('frontend.workorder.show-workorder-inspection', compact("dataWI", "qsearch", "LotNumSearch", "dataPI", "receiverName", "recvWhDate", "cusSearch", "processTypeId", "isAccepted"));
	}
  
	public function receive_work_item($id)
	{
		$inspId = ctype_digit((string) $id) ? (int) $id : (int) dec((string) $id);

		$dataWI = WorkInspection::where('id', $inspId)->where('status', '!=', 'Deleted')->where('is_deleted', 0)->first();

		if (!$dataWI) {
			Session::put('message', 'Inspection record not found.');
			Session::put('messageClass', 'errorClass');
			return redirect()->back()->withInput();
		}

		$gatePass = GatePass::where('inspection_id', $inspId)->where('status', 'Active')->where('is_deleted', 0)->orderBy('id')->first();

		if (!$gatePass) {
			Session::put('message', 'An error occurred: Gatepass not found. Please check the inspection gatepass and try again.');
			Session::put('messageClass', 'errorClass');
			return redirect()->back()->withInput();
		}

		if ($gatePass->is_item_received_in_warehouse == 'Yes' || $dataWI->is_item_received_in_warehouse == 'Yes') {
			Session::put('message', 'This inspected item has already been received in warehouse.');
			Session::put('messageClass', 'errorClass');
			return redirect()->back()->withInput();
		}

		$wId = (int) $dataWI->work_order_id;
		$dataWO = WorkOrder::where('id', $wId)->where('status', '!=', 'Deleted')->first();

		if (!$dataWO) {
			Session::put('message', 'Work order details not found.');
			Session::put('messageClass', 'errorClass');
			return redirect()->back()->withInput();
		}

		$details = WorkInspectionDetail::select('*', 'output_quantity as output_quan_size')->with('FabricFaultReason')->where('work_insp_id', $inspId)->where('status', 'Active')->orderBy('id')->get();

		if ($details->isEmpty()) {
			Session::put('message', 'Inspection details not found.');
			Session::put('messageClass', 'errorClass');
			return redirect()->back()->withInput();
		}

		$dataWI->setRelation('GatePass', $gatePass);
		$dataWI->setRelation('WorkInspectionDetail', $details);
		$dataWI->coated_pvc = $gatePass->coated_pvc ?? $dataWI->coated_type ?? '';
		$dataWI->extra_job = $gatePass->extra_job ?? $dataWI->extra_job ?? '';
		$dataWI->print_job = $gatePass->print_job ?? $dataWI->print_job ?? '';

		$dataWO->insp_work_warehouse_id = $dataWI->insp_work_warehouse_id;

		$ItemTypeId = $gatePass->item_type_id ?: $dataWO->item_type_id;
		$ProcessTypeId = $dataWO->process_type_id;
		$userId = Auth::id();
		$userD = User::find($userId);

		if (!$userD) {
			Session::put('message', 'User not found.');
			Session::put('messageClass', 'errorClass');
			return redirect()->back()->withInput();
		}

		$dataPI = ProcessItem::where('status', 'Active')->get();
		$dataIT = ItemType::where('status', 'Active')->where('is_work', '1')->get();
		$dataW  = Warehouse::where('status', 'Active')->orderBy('id', 'asc')->get();

		return view('frontend.workorder.receive-work-item', compact('dataW', 'dataWI', 'dataPI', 'dataIT', 'dataWO', 'ItemTypeId', 'ProcessTypeId', 'inspId', 'userD'));
		
	}

	public function addWorkRequisitionForRfDyeing(Request $request)
	{ 
		$validator = Validator::make($request->all(), [
			"itemIdReq"             => "required",
			"work_order_id_req"     => "required",
			"ext_item_type_id"      => "required",
			"tot_req_quantity"      => "required",
			"req_item_id.*"         => "nullable|numeric",			 
		], [
			"itemIdReq.required"            => "Please select Item Name.",
			"work_order_id_req.required"    => "Please select your Work order type.",
			"ext_item_type_id.required"     => "Item type Id not found.",
			"tot_req_quantity.required"     => "Please enter your Dyed Quantity.",
			"req_item_id.*.numeric"         => "Item ID must be a number for each row.",			 
		]);
		if ($validator->fails()) {
			$errors = $validator->errors()->all();
			$errorMessage = implode("<br>", $errors);
			Session::put('message', $errorMessage);
			Session::put("messageClass", "errorClass");
			return redirect()->back()->withInput();
		}
		
		   // echo "<pre>"; print_r($request->all()); 
		$itemTypeId 	= $request->ext_item_type_id;		
		$workOrderId 	= $request->work_order_id_req;
		$workOrder 		= WorkOrder::where('id', $workOrderId)->with('WorkOrderItem')->where('status', 'Active')->first();

		if (!$workOrder) {
			Session::put('message', 'Work Order not found.');
			Session::put("messageClass", "errorClass");
			return redirect()->back()->withInput();
		}

		$dyeingColor 	= $workOrder->WorkOrderItem->first()->dyeing_color ?? null;
		$userId 		= Auth::id();
		$user 			= User::find($userId);
		$individualId 	= $user->individual_id ?? null;
		$currentDate  	= now();
		$reqFabricType 	= $request->req_fabric_type;      
		  
		if ($request->filled('tot_req_quantity')) 
		{
				 
			$itemTypeId = $request->ext_item_type_id;
			$unitTypeId = ItemType::where('item_type_id', $itemTypeId)->value('unit_type_id'); 
			$dataWPR2 = [
				'work_order_id'     => $workOrderId,
				'item_id'           => $request->itemIdReq,
				'process_type_id'   => $workOrder->process_type_id,
				'item_type_id'      => $itemTypeId,
				'unit_type_id'      => $unitTypeId,
				'work_req_send_by'  => $individualId,
				'quantity'          => $request->tot_req_quantity, 	
				'req_fabric_type'   => $request->req_fabric_type, 			
				'financial_year'    => currentFinancialYear(),
				'created_by'        => $individualId,
				'created_at'        => $currentDate,
				'status'            => 'Active',
			];
			if ($itemTypeId == '3') 
			{				 
				$maxReqLotNoData = DB::table('work_process_requirements')
					->select(DB::raw('MAX(CAST(req_lot_no AS UNSIGNED)) as max_req_lot_no'))
					->where('item_type_id', '=', '3')
					->where('is_accept', '!=', '2')
					->where('req_fabric_type', '=', $reqFabricType)
					->where('status', '=', 'Active')
					->first();

			    $maxReqLotNo = $maxReqLotNoData ? $maxReqLotNoData->max_req_lot_no : 0;		
				 
				if(!empty($request->req_lot_no))
				{
					$dataWPR2['req_lot_no'] = $request->req_lot_no;  
				} 
				else 
				{
				  	$dataWPR2['req_lot_no'] = $maxReqLotNo ? $maxReqLotNo + 1 : 1;  
				}
			}
			 
			$inserted = WorkProcessRequirement::insert([$dataWPR2]); 
			if (!$inserted) 
			{
				Session::flash('message', 'Error occurred while adding work requisition.');
				Session::flash("messageClass", "errorClass");
				return redirect()->back()->withInput();
			}
		}

		if (!empty($dataWPR2)) 
		{
			WorkOrder::where('id', $workOrderId)->update([
				'work_req_send_by' => $individualId,
				'is_work_require_request_accepted' => null,
				'work_req_send_date' => $currentDate
			]);

			Session::put('message', 'Work Requirement Send to Warehouse successfully.');
			Session::put("messageClass", "successClass");
		} 
		else 
		{
			Session::put('message', 'Something went wrong. Work Requirement Not Sent to Warehouse.');
			Session::put("messageClass", "errorClass");
		} 

		return redirect()->to(Session::get('previous_url'));
	}
	
	


}




