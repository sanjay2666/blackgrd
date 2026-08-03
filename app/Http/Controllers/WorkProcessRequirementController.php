<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\ItemType;
use App\Models\UnitType;
use App\Models\User;
use App\Models\Individual; 
use App\Models\PurchaseItem;
use App\Models\Warehouse;
use App\Models\WarehouseItem;
use App\Models\WarehouseCompartment;
use App\Models\WorkOrder;
use App\Models\Item;
use App\Models\WorkProcessRequirement; 
use App\Models\WarehouseItemStock;
use App\Models\Company; 
use App\Models\ProcessItem;  
use App\Models\WarehouseOutItem;
use App\Models\DepartmentReturnRequest;
use App\Models\PackagingOrder;
use App\Models\WarehouseBalanceItem;
use App\Models\PackagingProcessRequirement; 
use App\Models\WorkPrintProcessRequirement;
use App\Models\StockMillDispatch;
use App\Models\WorkOrderItem;
use App\Models\Machine;
use App\Models\WorkInspectionDetail;
use App\Models\DyeingPlanningItem;
use App\Models\SaleOrderItem; 
use App\Models\PackagingOrderItem;
use Validator, Auth, Session, Hash;
use Illuminate\Support\Facades\Log; 

use Illuminate\Support\Facades\Cache;  
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;   

 

class WorkProcessRequirementController extends Controller
{
    
	public function index(Request $request)
	{ 
		 

		$userId = Auth::id();
		$userD = User::find($userId);	 
		$userIndId = (int) ($userD->individual_id ?? 0); 


		$search_process_id  = array_filter((array) $request->search_process_id);
		$qsearch          	= trim($request->qsearch);
		$fromDate           = $request->from_date;
		$toDate             = $request->to_date;
		$req_type           = $request->req_type;
		$itemName           = $request->itemName;
		$lotno           	= $request->lotno;
		$dyeingColor        = $request->dyeing_color;
		  
		 
		$hasReqLotNoColumn = Schema::hasColumn('work_process_requirements', 'req_lot_no');
		$query = WorkProcessRequirement::where('status', 'Active')->with(['Item', 'ItemType', 'UnitType', 'WorkOrder', 'CreatedBy', 'ModifiedBy'])->orderByDesc('id');

		// 🔹 Request Type Filter
		if ($req_type !== null && $req_type !== '') {
			$query->where('is_accept', $req_type); 
		}
		if (!empty($lotno) && $hasReqLotNoColumn) {
			$query->where('req_lot_no', $lotno); 
		}
		
		if (!empty($dyeingColor)) {
			$query->where('dyeing_color', $dyeingColor); 
		}

		 
		if (!empty($search_process_id)) 
		{
			 
			$query->whereIn('process_type_id', $search_process_id);
			 
		}

		// 🔹 Date Filter
		if (!empty($fromDate) && !empty($toDate)) 
		{
			$query->whereBetween('created_at', [
				date('Y-m-d 00:00:00', strtotime($fromDate)), 
				date('Y-m-d 23:59:59', strtotime($toDate))
			]);
		}

		// 🔹 Search by Individual Name
		if (!empty($qsearch)) 
		{
			$individualids = Individual::where('name', 'LIKE', '%' . $qsearch . '%')
				->where('status', 'Active')
				->pluck('id')
				->toArray(); 

			if (!empty($individualids)) {
				$query->whereIn('created_by', $individualids);
			}
		}	

		// 🔹 Search by Item Name
		if (!empty($itemName)) 
		{
			$itemIds = Item::where('item_name', 'LIKE', '%' . $itemName . '%')
				->where('status', 'Active')
				->pluck('item_id')
				->toArray(); 

			if (!empty($itemIds)) {
				$query->whereIn('item_id', $itemIds);
			}
		}

		 
		$dataWPR = $query->paginate(20)->appends($request->all());		

		// 🔹 Fetch Process Items
		$processI = ProcessItem::where('status', 'Active')->get();

		return view('frontend.workprocessrequirement.show-warehouse-item-requirement', 
			compact("dataWPR", "itemName", "processI", "fromDate", "toDate", "search_process_id", "qsearch", "req_type", "lotno", "dyeingColor"));
	}
	
	public function acceptWarehouseItemRequirement($id)
	{	
	
		$wprId 			= dec($id);		
		$wprData 		= WorkProcessRequirement::with(['WorkOrder.WorkOrderItem', 'Item', 'ItemType', 'UnitType'])
			->whereKey($wprId)
			->where('status', 'Active')
			->first();		 

		if (!$wprData) {
			Session::put('message', 'Work requirement not found.');
            Session::put("messageClass", "errorClass");
            return redirect()->route('show-warehouse-item-requirement');
		}

		$workOrderId 	= $wprData->work_order_id;		
		$workOrder 		= $wprData->WorkOrder;		
		if(!$workOrder || empty($workOrder->process_type_id) || $workOrder->status === 'Deleted')
		{			
			Session::put('message', 'Work Order not found.');
            Session::put("messageClass", "errorClass");
            return redirect()->route('show-warehouse-item-requirement');
		}
		
		$processTypeId    = $workOrder->process_type_id;	
		$itemTypeId  	  = $wprData->item_type_id; 
		$reqFabricTypeId  = $wprData->req_fabric_type ?: 1;
  		 
		
		$dataWPR = WorkProcessRequirement::where('id', '=', $wprId)->with('WorkOrderItem')->with('Item')->where('status', '=', 'Active')->where('is_accept', '=', '0')->get();		
		// echo "<pre>"; print_r($dataWPR); exit;
		
		$dataWPR2 		= collect();		
		if($reqFabricTypeId =='1')
		{	
			$query 			= WorkProcessRequirement::where('id', '=', $wprId)->where('work_order_id', $workOrderId)
				->with('Item')
				->where('item_type_id', $itemTypeId)
				->where('status', 'Active')
				->where('is_accept', 0);
				
			if ($itemTypeId == 4) 
			{
				$query->where('dyeing_color', $workOrder->WorkOrderItem->first()->dyeing_color ?? null);
				if (Schema::hasColumn('work_process_requirements', 'req_lot_no')) {
					$query->orderBy('req_lot_no', 'asc');
				}
			}

			$dataWPR2 = $query->get();

			$dataWPR2->each(function ($row) use ($workOrder) {
				$stockQuery = WarehouseItemStock::where('item_id', $row->item_id)
					->where('item_type_id', $row->item_type_id)
					->where('is_allotted_stock', 'No')
					->where('status', 'Active')
					->with('Item', 'WarehouseItem.Warehouse', 'WarehouseItem.WarehouseCompartment')
					->orderBy('id');

				if ($row->item_type_id == 4) {
					$stockQuery->where('dyeing_color', $workOrder->WorkOrderItem->first()->dyeing_color ?? $row->dyeing_color);
				}

				$row->setRelation('WarehouseItemStock', $stockQuery->get());
			});
		}
		
		if($reqFabricTypeId =='2')
		{	
			$itemId   	= $wprData->item_id; 
			$query 		= WarehouseItemStock::where('item_id', $itemId)
			->where('item_type_id', 4)
			->where('is_allotted_stock', 'No')
			->where('status', 'Active')
			->select(['id as wis_id', 'item_id', 'insp_taka_number', 'insp_bal_quan_size', 'dyeing_color', 'warehouse_item_id'])
			->with([
				'Item' => function ($query) {
					$query->select('item_id', 'item_name', 'internal_item_name');  
				},
				'WarehouseItem' => function ($query) {
					$query->select('id', 'warehouse_id');
				},
				'WarehouseItem.Warehouse' => function ($query) {
					$query->select('id', 'warehouse_name');
				},
				'WarehouseItem.WarehouseCompartment' => function ($query) {
					$query->select('id', 'warehousename');
				}
			]); 

			$dataWPR2 = $query->get();
		}
		
		 
		/* 	$sql = $query->toSql();
			$bindings = $query->getBindings();
			$fullSql = vsprintf(str_replace(['?'], ['\'%s\''], $sql), $bindings); 
			echo $fullSql;
		  */
		 
		$result 				= [];
		$result['workOrdId'] 	= $workOrderId;
		$viewData 				= compact('dataWPR', 'dataWPR2', 'workOrderId', 'result', 'wprData');
		if ($processTypeId == '1') 
		{
			return view('frontend.workprocessrequirement.accept-warehouse-item-requirement-for-yarn', $viewData);
		} 
		else if ($processTypeId == '2') 
		{
			return view('frontend.workprocessrequirement.accept-warehouse-item-requirement-for-yarn-beam', $viewData);	
		} 
		else if ($processTypeId == '3' && $reqFabricTypeId =='1') 
		{
			return view('frontend.workprocessrequirement.accept-warehouse-item-requirement-for-grey-and-color', $viewData);
		} 
		else if ($processTypeId == '3' && $reqFabricTypeId =='2') 
		{
			return view('frontend.workprocessrequirement.accept-warehouse-item-requirement-for-rfdying-department', $viewData);
		} 
		else 
		{
			return view('frontend.workprocessrequirement.accept-warehouse-item-requirement', $viewData);
		}
	}
 
	public function getWorkProcessAllotmentView(Request $request)
	{
		 
		$FId 			= $request->FId;
		$dataWPR 		= WorkProcessRequirement::find($FId);
		$workOrdId 		= $dataWPR->work_order_id;
		$dataWk 		= WorkOrder::where('work_order_id', '=', $workOrdId)->where('status', '=', '1')->first();
		$itemId 		= $dataWk->item_id;
		$itemName 		= $dataWk->item_name;
		$procesTypeId 	= $dataWk->process_type_id;

		$dataIT 		= ItemType::where('item_type_id', '=', $dataWPR->item_type_id)->where('status', '=', '1')->first();
		$itemTypeName 	= $dataIT->item_type_name;
		$quanTity 		= $dataWPR->quantity;

	
		// echo $workOrdId; exit;
		$orderCounts = WarehouseItemStock::select('item_id', 'item_type_id', 'alloted_remark', DB::raw('count(work_pro_req_id) as req_count'))
			->groupBy('item_id')
			->where('work_order_id', '=', $workOrdId) 
			->with('Item', 'Item.UnitType') // Eager loading
			->get();
			
		if(!empty($orderCounts[0]->item_id)) 
		{
			$str = "";			 
			$str .= '<table class="table table-bordered"><tbody>';			 
			foreach ($orderCounts as $count) {
				$itemtypeId = $count->item_type_id;
				$itemType = ItemType::where('item_type_id', '=', $itemtypeId)->value('item_type_name');
				$str .= '<tr>
							<td>' . $itemType . '</td>
							<td>Quantity</td>
						</tr>';

				if ($itemtypeId == '2') {
					$unitTypeName = 'Beam';
				} else {
					$unitTypeName = $count->Item->UnitType->unit_type_name;
				}

				$str .= "<tr>";
				$str .= "<td> {$count->Item->item_name} </td>";
				$str .= "<td> {$count->req_count}  {$unitTypeName} </td>";
				$str .= "</tr>";
			}

			$str .= "</tbody></table>";
			$str .= ' <strong> Allotment Remark </strong> =  ' . $orderCounts[0]->alloted_remark . ' ';
		} 
		else 
		{
			$str = "";
			$str .= '<table class="table table-bordered"><tbody>';
			$str .= '<tr> <td>Record Not Found!</td> </tr>';
			$str .= "</tbody></table>";
		}
		
		$result = [
			'itemId' => $itemId,
			'ItemName' => $itemName,
			'workOrdId' => $workOrdId,
			'itemTypeName' => $itemTypeName,
			'quanTity' => $quanTity,
			'work_process_req_id' => $dataWPR->id,
		];	 

		$result['stock_allot_arr'] = $str;
		return response()->json($result);
	}
	
	public function getWorkProcessRequirement(Request $request)
    {
		$FId 		= $request->FId; 
		$wprData 	= WorkProcessRequirement::with(['WorkOrder', 'Item', 'UnitType'])->find($FId); 

		if (!$wprData) {
			return response()->json([
				'wprDetails' => '<div class="alert alert-danger">Work requirement not found.</div>',
				'WorkItemName' => '',
			], 404);
		}

		$workOrdId = $wprData->work_order_id;
		$dataWk = $wprData->WorkOrder;
		$WorkItemName = $wprData->Item->item_name ?? $dataWk->item_name ?? '';

		$dataWPR = WorkProcessRequirement::where('id', '=', $FId)
			->with(['Item', 'UnitType'])
			->where('status', '=', 'Active')
			->where('is_accept', '=', '0')
			->get();

		$str ="";
		$str.='<input type="hidden" name="work_order_id" id="work_order_id" value="'.$workOrdId.'" class="form-control">';
		$str.='<table class="table table-bordered purchase-request-items">
				  <tr>
                    <th>Item Name</th>
                    <th>Quantity</th>
                  </tr>';
		foreach($dataWPR as $row)
		{
			$itemName = $row->Item->item_name ?? '';
			$quantity = $row->required_quantity;
			$unitName = $row->UnitType->unit_type_name ?? '';


			$str.='<tr>
			<td>
				<input type="hidden" name="wpr_id[]" value="'.e($row->id).'" class="form-control">
				<input type="hidden" name="item_id[]" value="'.e($row->item_id).'" class="form-control">
				<input type="hidden" name="item_name[]" value="'.e($itemName).'" class="form-control">
				<input type="hidden" name="item_quan[]" value="'.e($quantity).'" class="form-control">
				'.e($itemName).'
			</td>
			<td> '.e($quantity).' '.e($unitName).' </td>
			</tr>';

		}

		$str.='</table>';

		return response()->json([
			'wprDetails' => $str,
			'WorkItemName' => $WorkItemName,
		]);
    }

	public function getProcessRequirementItems(Request $request)
	{
		$workOrdId = $request->FId;
		$dataWk = WorkOrder::where('work_order_id', '=', $workOrdId)->where('status', '=', '1')->first();
		$itemId 		= $dataWk->item_id;
		$WorkItemName 	= $dataWk->item_name;
		$procesTypeId 	= $dataWk->process_type_id;
		$dataWPR = WorkProcessRequirement::where('work_order_id', '=', $workOrdId)->where('status', '=', '1')->where('is_accept', '!=', '2')->get();
		$str = "";			
		if($procesTypeId =='2') { 
			$str .= '<table class="table table-bordered">';
			$str .= '<tr> <th>Beam for Yarn</th> </tr></table>';		
		}		
		$str .= '<table class="table table-bordered">';			 
		$str .= '<tr>
					<th>Item Name</th>
					<th>Quantity</th>
				</tr>';

		foreach ($dataWPR as $row) {
			$dataI = Item::where('item_id', '=', $row->item_id)->where('status', '=', '1')->first();
			$itemName = $dataI->item_name;
			$quantity = $row->quantity;
			$unit_type_id = $dataI->unit_type_id;
			if ($row->item_type_id == '2') {
				$unitTName = 'Kg Beam';
			} else {
				$unitTName = CommonController::getUnitTypeName($unit_type_id);
			}
			$str .= '<tr>
				<td>' . $itemName . '</td>
				<td>' . $quantity . ' ' . $unitTName . '</td>
			</tr>';
		}

		$str .= '</table>';
		$result = [
			'wprDetails' 	=> $str,
			'WorkItemName' 	=> $WorkItemName,
		];
		return response()->json($result);
	}
 
	public function print_warehouse_item_requirement_gatepass($id)
	{
		 
		$wprId 	 		= dec($id);
		$dataWPR 		= WorkProcessRequirement::whereKey($wprId)
			->where('status', '!=', 'Deleted')
			->where('is_accept', '=', '1')
			->first();

		if (!$dataWPR) {
			abort(404, 'Work Process Requirement not found.');
		}

		$workOrderId 	= $dataWPR->work_order_id; 
		
		$query = WorkProcessRequirement::whereKey($wprId)->where('is_accept', '=', '1')->where('status', '!=', 'Deleted')->with('Item', 'ItemType', 'UnitType')->orderByDesc('id');
		$dataWPR2 = $query->get(); 
		
		$userId 		= Auth::id();
		$userD 			= User::find($userId);
		$IndividualId 	= $userD->individual_id ?? Auth::id();
		$dataInd 		= Individual::where('id', '=', $IndividualId)->where('status', '!=', 'Deleted')->first();
		$currentDate 	= date('Y-m-d');
		$compData 		= Company::find(1);
		$data 			= WorkOrder::whereKey($workOrderId)->with('WarehouseItem')->first();

		if (!$data) {
			abort(404, 'Work Order not found.');
		}

		$isGatepassGenratedByWarehouse = $data->is_gatepass_genrated_by_warehouse;
		
		$fromDepartment = $data->process_type_id;
		$itemId 		= $data->item_id;
		$toDepartment 	= $fromDepartment+1;
		
		DB::beginTransaction();

		try {
			if($isGatepassGenratedByWarehouse == 'No')
			{
				WorkOrder::whereKey($workOrderId)->update([
					'is_gatepass_genrated_by_warehouse' => 'Yes',
					'gatepass_genrated_by_warehouse_user' => $IndividualId,
				]);
			}

			DB::commit();
		} catch (\Throwable $e) {
			DB::rollBack();
			report($e);
			abort(500, 'Gatepass could not be updated.');
		}

		$dataWOI =  WarehouseOutItem::where('work_pro_req_id', $wprId)
					->where('item_id', $itemId)->with('WarehouseItem')
					->where('status', '!=', 'Deleted')
					->get();

		if ($dataWOI->isEmpty()) 
		{ 
			$dataWOI = WarehouseOutItem::where('work_order_id', $workOrderId)
				->where('item_id', $itemId)->with('WarehouseItem')
				->where('status', '!=', 'Deleted')
				->get();
		} 

		return view('frontend.workprocessrequirement.print-warehouse-item-requirement-gatepass',compact("data","dataWPR","toDepartment","compData","dataInd","dataWPR2","dataWOI","wprId"));
			
	}
 
	 
	public function printJobCardGatepass($id)
	{		
		$userId = Auth::id();
		$user = User::find($userId);
		$individualId = $user->individual_id ?? null;		
		$individual = Individual::where('id', $individualId)->where('status', '1')->first();		
		$currentDate = date('Y-m-d');
		$compData = Company::find(1);		
		$wprId = dec($id); 
			 
		$dataRow = WorkProcessRequirement::where('id', $wprId)->where('status', '1')->where('is_accept', '1')->first();	

		if (!$dataRow) {
			abort(404, 'Work Process Requirement not found.');
		}

		$processTypeId = $dataRow->process_type_id;
		$reqLotNo = $dataRow->req_lot_no;
		$reqFabricType = $dataRow->req_fabric_type;
		$mainWorkOrderId = $dataRow->work_order_id;
			
		$dataRow->update([
			'is_jw_generated_by_warehouse' => 'Yes'
		]);

		$dataWPR = WorkProcessRequirement::where('req_lot_no', $reqLotNo)->where('work_order_id', $mainWorkOrderId)->where('req_fabric_type', $reqFabricType)->where('process_type_id', $processTypeId)->where('status', '1')->where('is_accept', '1')->get();		

		if ($dataWPR->isEmpty()) {			 
			abort(404, 'Work Process Requirements not found.');
		}		

		$workOrderIds = $dataWPR->pluck('work_order_id')->unique();		
		$toDepartment = null;
			 
		foreach ($workOrderIds as $workOrderId) {			 
			$workOrder = WorkOrder::where('work_order_id', $workOrderId)->with('WarehouseItem')->first();			

			if (!$workOrder) {				 
				abort(404, 'Work Order not found.');
			}
				
			$isGatepassGeneratedByWarehouse = $workOrder->is_gatepass_generated_by_warehouse;
			$fromDepartment = $workOrder->process_type_id;
			$itemId = $workOrder->item_id;
			$toDepartment = $fromDepartment + 1;
				
			if ($isGatepassGeneratedByWarehouse === 'No') {
				$workOrder->update([
					'is_gatepass_generated_by_warehouse' => 'Yes',
					'gatepass_generated_by_warehouse_user' => $individualId
				]);
			}
		}	

		$query = WorkProcessRequirement::where('req_lot_no', $reqLotNo)
			->where('req_fabric_type', $reqFabricType)
			->where('is_accept', '1')
			->where('process_type_id', $processTypeId)
			// ->where('work_order_id', $mainWorkOrderId)
			->where('status', '1')
			->with([
				'WarehouseOutItem' => function ($query) {
					$query->where('is_item_return_whouse', '0')->select(['id', 'wis_id', 'warehouse_item_id', 'item_id', 'item_type_id', 'item_qty', 'insp_taka_number', 'dyeing_lot_number', 'dyeing_taka_number', 'fabric_fault_reason_id', 'individual_id', 'receiver_id', 'work_pro_req_id', 'work_order_id', 'item_remark', 'grey_quality', 'dyeing_color', 'coated_pvc', 'is_item_return_whouse', 'status'])->with(['WarehouseItemStock' => function ($subQuery) {
						$subQuery->select(['wis_id', 'insp_id', 'item_id', 'insp_quan_size', 'insp_allot_quan_size', 'insp_bal_quan_size', 'beam_meter', 'vendor_id', 'invoice_number', 'dyeing_lot_number', 'item_type_id', 'unit_type_id', 'dyeing_taka_number', 'insp_taka_number', 'status']);
					}]);
				},
				'Item', 'ItemType', 'UnitType'
			])
			->orderBy('id', 'asc');

		$dataWPR2 = $query->get();

		if ($dataWPR2->isEmpty()) {
			abort(404, 'Printable Work Process Requirements not found.');
		}

		$totalAllotedQuantity = $dataWPR2->flatMap->WarehouseOutItem->sum('item_qty');
		$data = WorkOrder::where('work_order_id', $mainWorkOrderId)->with('WorkOrderItem', 'WarehouseItem')->first();

		if (!$data) {
			abort(404, 'Work Order details not found.');
		}
					
		return view('frontend.workprocessrequirement.print-job-card-gatepass', compact('workOrderIds', 'dataWPR', 'totalAllotedQuantity', 'dataWPR2', 'toDepartment', 'compData', 'individual', 'data', 'wprId', 'dataRow'));
	}
   
   
	public function print_warehouse_item_requirement_gatepass_by_lot($id)
	{		
		$userId 		= Auth::id();
		$user 			= User::find($userId);
		$individualId 	= $user->individual_id;		
		$individual 	= Individual::where('id', $individualId)->where('status', '1')->first();		
		$currentDate 	= date('Y-m-d');
		$compData 		= Company::find(1);		
		$wprId 			= dec($id); 
		 
		$dataRow 		= WorkProcessRequirement::where('id', $wprId)->where('status', '1')->where('is_accept', '1')->first();		
		$processTypeId 	= $dataRow->process_type_id;
		$reqLotNo 		= $dataRow->req_lot_no;
		//$reqLotNo 		= $dataRow->req_lot_no;
		
		$dataWPR = WorkProcessRequirement::where('req_lot_no', $reqLotNo)->where('process_type_id', $processTypeId)->where('status', '1')->where('is_accept', '1')->get();		
		if ($dataWPR->isEmpty()) 
		{			 
			abort(404, 'Work Process Requirements not found.');
		}		
		$workOrderIds = $dataWPR->pluck('work_order_id')->unique();		
		 
		foreach ($workOrderIds as $workOrderId) 
		{			 
			  
			$workOrder = WorkOrder::where('work_order_id', $workOrderId)->with('WarehouseItem')->first();			
			if (!$workOrder) 
			{				 
				abort(404, 'Work Order not found.');
			}
			
			$isGatepassGeneratedByWarehouse = $workOrder->is_gatepass_generated_by_warehouse;
			$fromDepartment 				= $workOrder->process_type_id;
			$itemId 						= $workOrder->item_id;
			$toDepartment 					= $fromDepartment + 1;
			
			if ($isGatepassGeneratedByWarehouse === 'No') 
			{
				$workOrder->update([
					'is_gatepass_generated_by_warehouse' 	=> 'Yes',
					'gatepass_generated_by_warehouse_user' 	=> $individualId
				]);
			}
		}	
		 
		$query = WorkProcessRequirement::where('req_lot_no', '=', $reqLotNo)
				->where('is_accept', '=', '1')
				->where('process_type_id', '=', $processTypeId)
				->where('status', '=', '1')
				->whereHas('WarehouseOutItem', function ($query) {
					$query->where('is_item_return_whouse', '=', '0');
				})
				->with(['WarehouseOutItem.WarehouseItem', 'Item', 'ItemType', 'UnitType'])
				->orderByDesc('id');
		 $dataWPR2 = $query->get();
		
		$workOrderId 	= $dataWPR2->first()->work_order_id; 
		$data 			= WorkOrder::where('work_order_id', $workOrderId)->with('WarehouseItem')->first();
		$totalAllotedQuantity = $dataWPR2->sum('alloted_quantity');
		
		return view('frontend.workprocessrequirement.print-warehouse-item-requirement-gatepass-by-lot', compact('workOrderIds','dataWPR','totalAllotedQuantity','dataWPR2','toDepartment','compData','individual','data','wprId'));
	}
   
	public function getSumWarehouseItemStockValue(Request $request)
	{
		// echo "<pre>"; print_r($request->all()); exit;
		$FId = $request->FId;
		$dataWIS = WarehouseItemStock::whereKey($FId)->where('status', 'Active')->where('is_allotted_stock', 'No')->first(); 
		$inspBalQuanSize = $dataWIS->insp_bal_quan_size ?? 0;	 
		return response()->json(['quantity' => $inspBalQuanSize]);
	}
 
	public function AcceptWarehouseReq(Request $request)
    {
		DB::beginTransaction();

		try {
			$userId = Auth::id();
			$userD 	= User::find($userId);
			$IndId  = $userD->individual_id ?? $userId;
			$FId 	= $request->FId;
			$obj 	= WorkProcessRequirement::find($FId);

			if (!$obj) {
				throw new \RuntimeException('Work process requirement not found.');
			}

			$workOrderId = $obj->work_order_id;
			$obj->is_accept = '1';
			$obj->modified_by = $IndId;
			$obj->modified_at = now();
			$obj->save();

			WorkOrder::whereKey($workOrderId)->update(['is_work_require_request_accepted'=> 'Yes']);

			DB::commit();
			return $FId;
		} catch (\Throwable $e) {
			DB::rollBack();
			report($e);
			return response()->json(['message' => 'Warehouse request could not be accepted.'], 500);
		}
    }

	public function DenyWarehouseReq(Request $request)
    {
		DB::beginTransaction();

		try {
			$userId = Auth::id();
			$userD 	= User::find($userId);
			$IndId  = $userD->individual_id ?? $userId;
			$FId 	= $request->FId;
			$obj 	= WorkProcessRequirement::find($FId);

			if (!$obj) {
				throw new \RuntimeException('Work process requirement not found.');
			}

			$workOrderId = $obj->work_order_id;
			$obj->is_accept = '2';
			$obj->modified_by = $IndId;
			$obj->alloted_remark = $request->remarks ?? $request->deny_remark ?? $request->remark ?? null;
			$obj->modified_at = now();
			$obj->save();

			WorkOrder::whereKey($workOrderId)->update(['is_work_require_request_accepted'=> 'No']);

			DB::commit();
			return $FId;
		} catch (\Throwable $e) {
			DB::rollBack();
			report($e);
			return response()->json(['message' => 'Warehouse request could not be denied.'], 500);
		}
    }

     
	public function StoreWarehouseStockAllotment(Request $request)
    {
	    //  echo "<pre>"; print_r($request->all());   exit;		 
        $validator = Validator::make($request->all(), [             
            "wis_ids" 				=> "required",
            "warehouse_item_ids" 	=> "required",
            "work_process_req_ids" 	=> "required",
            "work_order_id" 		=> "required",
            "allotment_remark" 		=> "required",
            "received_quantities" 	=> "required",
        ], [            
            "wis_ids.required" 				=> "Warehouse Item Stock Id not Found",
            "warehouse_item_ids.required" 	=> "Warehouse item Id not found",
            "work_process_req_ids.required" => "Work process request not found",
            "work_order_id.required" 		=> "Work order not found",
            "allotment_remark.required" 	=> "Stock Allotment remark not found.",
            "received_quantities.required" 	=> "Received Quantity not found.",
        ]); 		 
        if ($validator->fails()) 
		{
            Session::put('message', $validator->messages()->first());
            Session::put("messageClass", "errorClass");
            return redirect()->back()->withInput();
        }   

		DB::beginTransaction();

		try {
        $user 			= Auth::user();
        $userId 		= $user->id;
        $individualId 	= $user->individual_id;

        $workProcessReqIds 	= $request->work_process_req_ids;
        $workOrderId 		= $request->work_order_id;
        $allotmentRemark 	= $request->allotment_remark;
        $usedItems 			= $request->used_item;
        $wisIds 			= $request->wis_ids;
        $warehouseItemIds 	= $request->warehouse_item_ids;
        $receivedQuantity 	= $request->received_quantities;

        $flag = false;
        foreach ($wisIds as $key => $wisId) 
		{			
            // $usedQuantity 		= $usedItems[$key];
            $warehouseItemId 		= $warehouseItemIds[$key];
            $workProcessReqId 		= $workProcessReqIds[$key];	
            $recvdQuan		 		= $receivedQuantity[$key];		
			//	->where('status', '=', '1')->where('is_accept', '=', '0')		 
			$dataWPR   = WorkProcessRequirement::where('id', '=', $workProcessReqId)->first();
			// echo "<pre>"; print_r($dataWPR); exit;
			
			$usedQuantity 			= $dataWPR->quantity;
			$whbId 					= $dataWPR->warehouse_balance_item_id; 
			$dataWIS = WarehouseItemStock::where('wis_id', '=', $wisId)->where('is_allotted_stock', '=', 'No')->where('status', '=', '1')->first();
			
			if($dataWIS) 
			{				 
				$inspQuanSize 			= $dataWIS->insp_quan_size;
				$inspAllotQuanSize 		= $dataWIS->insp_allot_quan_size;
				$inspBalQuanSize    	= $dataWIS->insp_bal_quan_size;				 
				$totAllotSize 			= $inspAllotQuanSize + $recvdQuan;
				$balanQunSize 			= $inspQuanSize - $totAllotSize;	
				
				WarehouseItemStock::where(['wis_id' => $wisId, 'status' => '1'])
				->update([
					'insp_allot_quan_size'  => $totAllotSize,
					'insp_bal_quan_size'    => $balanQunSize, 
					'is_allotted_stock'     => $inspQuanSize <= 0 ? 'Yes' : 'No',  
					'allot_work_order_id'   => $workOrderId,
					'work_pro_req_id'       => $workProcessReqId,
					'stock_alloted_by'      => $individualId,
					'alloted_remark'        => $allotmentRemark,
				]);	
					 
			} 
            $dataWI = WarehouseItem::find($warehouseItemId);
            if(empty($dataWI)) 
			{
				DB::rollBack();
                Session::put('message', 'Warehouse Item Not Found.');
                Session::put("messageClass", "errorClass");
                return redirect()->back()->withInput();
            } 
			
            $newItem = WarehouseOutItem::create([
                'process_type_id' 	=> $dataWI->process_type_id ?? 0,
				'wis_id' 			=> $wisId,
				'warehouse_item_id' => $warehouseItemId, 
                'warehouse_id' 		=> $dataWI->warehouse_id,
                'ware_comp_id' 		=> $dataWI->ware_comp_id,
                'item_id' 			=> $dataWI->item_id,
                'item_type_id' 		=> $dataWI->item_type_id,
                'unit_type_id' 		=> $dataWI->unit_type_id,
				'receiver_id'       => $dataWI->receiver_id,
                'item_qty' 			=> $usedQuantity,
                'pcs' 				=> $dataWI->pcs ?? 0.00,
                'cut' 				=> $dataWI->cut,
                'meter' 			=> $dataWI->meter ?? 0.00,
                'individual_id' 	=> $individualId,
                'work_order_id' 	=> $workOrderId,
				'work_pro_req_id'   => $workProcessReqId,
                'item_remark' 		=> $allotmentRemark,
                'grey_quality' 		=> $dataWI->grey_quality,
                'dyeing_color' 		=> $dataWI->dyeing_color,
                'coated_pvc' 		=> $dataWI->coated_pvc,
                'print_job' 		=> $dataWI->print_job,
                'extra_job' 		=> $dataWI->extra_job,
                'created' 			=> now(),
                'financial_year' 	=> currentFinancialYear(),
                'status' => 1,
            ]);			 
            
			$query    = WarehouseBalanceItem::where('item_id', $newItem->item_id)
				->where('item_type_id', $newItem->item_type_id)
				->where('dyeing_color', $newItem->dyeing_color)
				->where('coated_pvc', $newItem->coated_pvc)
				->where('print_job', $newItem->print_job)
				->where('extra_job', $newItem->extra_job)
				->orderBy('id', 'desc');
				
			if(!empty($whbId)) 
			{
				$opItemQty = $query->where('id', $whbId)->value('item_qty');
			} 
			else 
			{
				$opItemQty = $query->value('item_qty');
			}	
				 
			$whbId 		  = $dataWPR->warehouse_balance_item_id;			 
			$affectedRows = WarehouseBalanceItem::where('item_id', $newItem->item_id)
				->where('item_type_id', $newItem->item_type_id)
				->where('dyeing_color', $newItem->dyeing_color)
				->where('coated_pvc', $newItem->coated_pvc)
				->where('print_job', $newItem->print_job)
				->where('extra_job', $newItem->extra_job)
				->where('balance_status', 1)
				//->where('id', '<>', $warehouseBalanceItem->id)			 
				->update(['balance_status' => 0]);				
			if (!$affectedRows) 
			{           
				dd('Update failed for WarehouseBalanceItem');
			}
			
			$closingItemQty = $opItemQty - $newItem->item_qty;
            $warehouseBalanceItem = new WarehouseBalanceItem([
                'ware_in_item_id' 	=> 0,
                'ware_out_item_id' 	=> $newItem->id,
                'warehouse_id' 		=> $newItem->warehouse_id,
                'ware_comp_id' 		=> $newItem->ware_comp_id,
                'item_id' 			=> $newItem->item_id,
                'item_type_id' 		=> $newItem->item_type_id,
                'unit_type_id' 		=> $newItem->unit_type_id,
                'op_item_qty' 		=> $opItemQty,
                'in_item_qty' 		=> 0,
                'out_item_qty' 		=> $newItem->item_qty,
                'item_qty' 			=> $closingItemQty,
                'grey_quality' 		=> $newItem->grey_quality,
                'dyeing_color' 		=> $newItem->dyeing_color,
                'coated_pvc' 		=> $newItem->coated_pvc,
                'print_job' 		=> $newItem->print_job,
                'extra_job' 		=> $newItem->extra_job,
                'created' 			=> now(),
				'financial_year' 	=> currentFinancialYear(),
				'balance_status' 	=> 1,
                'status' 			=> 1,
            ]);

            $warehouseBalanceItem->save();	
			
            $totItemQty 	= $dataWI->item_qty;
            $totAllotQty 	= $dataWI->allotted_qty;
            WarehouseItem::where(['id' => $warehouseItemId])
                ->update([
                    'item_qty' 		=> $totItemQty - $usedQuantity,
                    'allotted_qty' 	=> $totAllotQty + $usedQuantity,
                ]);

            WorkProcessRequirement::where('id', '=', $workProcessReqId)
                ->update([
                    'is_pro_acc_by_warehouse' 	=> 'Yes',
                    'is_accept' 				=> '1',
					'alloted_quantity' 			=> $opItemQty,
                    'process_accepted_by' 		=> $individualId,
					'alloted_remark' 			=> $allotmentRemark,
                    'acc_deny_date' 			=> now(),
                ]);
				
			 	
            WorkOrder::where('work_order_id', '=', $workOrderId)->update(['is_work_require_request_accepted' => 'Yes']);

            $flag = true;
        }

        if($flag) 
		{
			DB::commit();
            Session::put('message', 'Stock Alloted successfully.');
            Session::put("messageClass", "successClass");
            return redirect("/show-warehouse-item-requirement");
        }
		else 
		{ 
			DB::rollBack();
            Session::put('message', 'Stock Not Alloted.');
            Session::put("messageClass", "errorClass");
            return redirect("/show-warehouse-item-requirement");		
		}
		} catch (\Throwable $e) {
			DB::rollBack();
			report($e);
			Session::put('message', 'Stock Not Alloted.');
			Session::put("messageClass", "errorClass");
			return redirect()->back()->withInput();
		}
    }
	 
	public function StoreWarehouseGreyAndColorStockAllotment(Request $request)
    { 
		$validator = Validator::make($request->all(), [             
			"_token"                => "required",
			"received_quantities.*" => "required|numeric",  
			"work_process_req_ids.*" => "required|numeric",
			"wis_id.*" 				=> "required|numeric", 
			"work_order_id"         => "required|numeric",
			"allotment_remark"      => "required|string",
		], [            
			"_token.required"                	=> "Token not found",
			"received_quantities.*.required" 	=> "Received Quantity not found for one or more items",
			"received_quantities.*.numeric"  	=> "Received Quantity must be a number for one or more items",
			"work_process_req_ids.*.required" 	=> "Work process request not found for one or more items",
			"work_process_req_ids.*.numeric"  	=> "Work process request must be a number for one or more items",
			"wis_id.*.required" 				=> "Work process Greige item request not found for one or more items",
			"wis_id.*.numeric"  				=> "Work process Greige item request must be a number for one or more items",
			"work_order_id.required"         	=> "Work order not found",
			"work_order_id.numeric"          	=> "Work order must be a number",
			"allotment_remark.required"      	=> "Stock Allotment remark not found",
			"allotment_remark.string"        	=> "Stock Allotment remark must be a string",
		]); 
        if ($validator->fails()) 
		{
            Session::put('message', $validator->messages()->first());
            Session::put("messageClass", "errorClass");
            return redirect()->back()->withInput();
        }        
		
		 
		DB::beginTransaction();

		try {
		$user 				= Auth::user();
        $userId 			= $user->id;
        $individualId 		= $user->individual_id;
        $workProcessReqIds 	= $request->work_process_req_ids;
        $workOrderId 		= $request->work_order_id;
        $allotmentRemark 	= $request->allotment_remark;
		$receivedQuantity 	= $request->received_quantities;
		$wis_idArr 			= $request->wis_id; 
		$req_grey_qtyArr 	= array_values(array_filter($request->req_grey_qty));	
		 // echo "<pre>"; print_r($req_grey_qtyArr);
		  // echo "<pre>"; print_r($request->all()); exit;				
        $flag = false;
		foreach ($workProcessReqIds as $key => $wprId) 
		{ 
			$dataWPR = WorkProcessRequirement::where('id', '=', $wprId)->whereIn('item_type_id', [7, 9])->first();
			if(!empty($dataWPR->id))
			{
				$flag = true;
				$itemId  				= $dataWPR->item_id;			
				$processTypeId 			= $dataWPR->process_type_id;
				$itemTypeId 			= $dataWPR->item_type_id;
				$unitTypeId 			= $dataWPR->unit_type_id;
				$receivedQuantity 		= $dataWPR->quantity;
				$whbId 					= $dataWPR->warehouse_balance_item_id;

				$dataWISList = WarehouseItemStock::where('item_id', '=', $itemId)
					->where('item_type_id', '=', $itemTypeId)
					->where('is_allotted_stock', '=', 'No')
					->where('status', '=', 'Active')
					->orderBy('id')
					->get();					
				
				foreach ($dataWISList as $dataWIS) 
				{
					$wisId 				 = $dataWIS->id;
					$warehouseItemId 	 = $dataWIS->warehouse_item_id;
					$dataWI = WarehouseItem::find($warehouseItemId);
					$inspQuanSize        = $dataWIS->insp_quan_size;
					$inspTakaNumber      = $dataWIS->insp_taka_number;
					$inspAllotQuanSize   = $dataWIS->insp_allot_quan_size;
					$inspBalQuanSize     = $inspQuanSize - $inspAllotQuanSize;
					$remainingQuantity   = min($receivedQuantity, $inspQuanSize - $inspAllotQuanSize);

					WarehouseItemStock::whereKey($wisId)
						->where('status', 'Active')
						->update([
							'insp_allot_quan_size' => $inspAllotQuanSize + $remainingQuantity,
							'insp_bal_quan_size'   => max(0, $inspQuanSize - ($inspAllotQuanSize + $remainingQuantity)),
							'is_allotted_stock'    => $inspQuanSize <= ($inspAllotQuanSize + $remainingQuantity) ? 'Yes' : 'No',
							'allot_work_order_id'  => $workOrderId,
							'work_pro_req_id'      => $wprId,
							'stock_alloted_by'     => $individualId,
							'alloted_remark'       => $allotmentRemark,
						]);
						
					WorkProcessRequirement::where('id', '=', $wprId)
					->update([
						'is_pro_acc_by_warehouse' 	=> 'Yes',
						'is_accept' 				=> '1',
						'alloted_quantity' 			=> $receivedQuantity,
						'process_accepted_by' 		=> $individualId,
						'alloted_remark' 			=> $allotmentRemark,
						'acc_deny_date' 			=> now(),
					]);		
			
					$totItemQty 		= $dataWI->item_qty;
					$totAllotQty 		= $dataWI->allotted_qty;
					WarehouseItem::where(['id' => $warehouseItemId])
					->update([
						'item_qty' 		=> $totItemQty - $remainingQuantity,
						'allotted_qty' 	=> $totAllotQty + $remainingQuantity,
					]);
					
					$newItem = WarehouseOutItem::create([
						'process_type_id' 	=> $dataWI->process_type_id ?? 0,
						'warehouse_id' 		=> $dataWI->warehouse_id,
						'ware_comp_id' 		=> $dataWI->ware_comp_id,
						'wis_id' 			=> $wisId,
						'warehouse_item_id' => $warehouseItemId, 
						'item_id' 			=> $dataWI->item_id,
						'item_type_id' 		=> $dataWI->item_type_id,
						'unit_type_id' 		=> $dataWI->unit_type_id,
						'receiver_id'       => $dataWI->receiver_id,
						'insp_taka_number'  => $inspTakaNumber,
						'item_qty' 			=> $remainingQuantity,
						'pcs' 				=> $dataWI->pcs ?? 0.00,
						'cut' 				=> $dataWI->cut,
						'meter' 			=> $dataWI->meter ?? 0.00,
						'individual_id' 	=> $individualId,
						'work_pro_req_id'   => $wprId,
						'work_order_id' 	=> $workOrderId,
						'item_remark' 		=> $allotmentRemark,
						'grey_quality' 		=> $dataWI->grey_quality,
						'dyeing_color' 		=> $dataWI->dyeing_color,
						'coated_pvc' 		=> $dataWI->coated_pvc,
						'print_job' 		=> $dataWI->print_job,
						'extra_job' 		=> $dataWI->extra_job,
						'created' 			=> now(),
						'financial_year' 	=> currentFinancialYear(),
						'status' => 1,
					]);	
					
					$query    = WarehouseBalanceItem::where('item_id', $newItem->item_id)
					->where('item_type_id', $newItem->item_type_id)
					->where('dyeing_color', $newItem->dyeing_color)
					->where('coating_type', $newItem->coated_pvc) 
					->orderBy('id', 'desc');
					
					if(!empty($whbId)) 
					{
						$opItemQty = $query->where('id', $whbId)->value('item_qty');
					} 
					else 
					{
						$opItemQty = $query->value('item_qty');
					}	
					
					$affectedRows = WarehouseBalanceItem::where('item_id', $newItem->item_id)
					->where('item_type_id', $newItem->item_type_id)
					->where('dyeing_color', $newItem->dyeing_color)
					->where('coating_type', $newItem->coated_pvc) 
					->where('balance_status', 1)					  
					->update(['balance_status' => 0]);				
					if (!$affectedRows) 
					{           
						dd('Update failed for WarehouseBalanceItem 1');
					}
					
					$closingItemQty = $opItemQty - $newItem->item_qty;
					$warehouseBalanceItem = new WarehouseBalanceItem([
						'ware_in_item_id' 	=> 0,
						'ware_out_item_id' 	=> $newItem->id,
						'warehouse_id' 		=> $newItem->warehouse_id,
						'ware_comp_id' 		=> $newItem->ware_comp_id,
						'item_id' 			=> $newItem->item_id,
						'item_type_id' 		=> $newItem->item_type_id,
						'unit_type_id' 		=> $newItem->unit_type_id,
						'receiver_id'       => $newItem->receiver_id,
						'receive_date' 		=> now(),
						'op_item_qty' 		=> $opItemQty,
						'in_item_qty' 		=> 0,
						'out_item_qty' 		=> $newItem->item_qty,
						'item_qty' 			=> $closingItemQty,
						'grey_quality' 		=> $newItem->grey_quality,
						'dyeing_color' 		=> $newItem->dyeing_color,
						'coated_pvc' 		=> $newItem->coated_pvc,
						'print_job' 		=> $newItem->print_job,
						'extra_job' 		=> $newItem->extra_job,
						'created' 			=> now(),
						'financial_year' 	=> currentFinancialYear(),
						'balance_status' 	=> 1,
						'status' 			=> 1,
					]);
					$warehouseBalanceItem->save();					
					$receivedQuantity -= $remainingQuantity;
					if ($receivedQuantity <= 0) {
						break;
					}
				}
			
			} 
		}  
		
		$wprnId 			= $request->wprId;
		$totAltquantity 	= $request->tot_req_quantity;
		$wis_idArr = $request->input('wis_id', []);
		foreach ($wis_idArr as $keyId => $wisId) 
		{ 	
			if (isset($req_grey_qtyArr[$keyId])) 
			{		
				$reqGreyQty = $req_grey_qtyArr[$keyId];			 
				$dataWIS 	= WarehouseItemStock::whereKey($wisId)->where('is_allotted_stock', '=', 'No')->where('status', '=', 'Active')->first();
				if ($dataWIS) 
				{
					$wisId 	 	= $dataWIS->id; 
					if(!empty($wisId))
					{	        
						$wisId 				 = $dataWIS->id;
						$receivedQuantity 	 = $reqGreyQty;
						$warehouseItemId 	 = $dataWIS->warehouse_item_id;
						$dataWI 			 = WarehouseItem::find($warehouseItemId);
						$inspQuanSize        = $dataWIS->insp_quan_size;
						
						$inspTakaNumber      = $dataWIS->insp_taka_number;
						$dyeingLotNumber     = $dataWIS->dyeing_lot_number;
						$dyeingTakaNumber    = $dataWIS->dyeing_taka_number;
						
						
						$inspAllotQuanSize   = $dataWIS->insp_allot_quan_size;
						$inspBalQuanSize     = $inspQuanSize - $inspAllotQuanSize;
						$remainingQuantity   = min($receivedQuantity, $inspQuanSize - $inspAllotQuanSize);
						
						WarehouseItemStock::whereKey($wisId)
						->where('status', 'Active')
						->update([
							'insp_allot_quan_size' => $inspAllotQuanSize + $remainingQuantity,
							'insp_bal_quan_size'   => max(0, $inspQuanSize - ($inspAllotQuanSize + $remainingQuantity)),
							'is_allotted_stock'    => $inspQuanSize <= ($inspAllotQuanSize + $remainingQuantity) ? 'Yes' : 'No',
							'allot_work_order_id'  => $workOrderId,
							'work_pro_req_id'      => $wprnId,
							'stock_alloted_by'     => $individualId,
							'alloted_remark'       => $allotmentRemark,
						]);
							
						WorkProcessRequirement::where('id', '=', $wprnId)
						->update([
							'is_pro_acc_by_warehouse' 	=> 'Yes',
							'is_accept' 				=> '1',
							'alloted_quantity' 			=> $totAltquantity,
							'process_accepted_by' 		=> $individualId,
							'acc_deny_date' 			=> now(),
						]);		
				
						$totItemQty 		= $dataWI->item_qty;
						$totAllotQty 		= $dataWI->allotted_qty;
						WarehouseItem::where(['id' => $warehouseItemId])
						->update([
							'item_qty' 		=> $totItemQty - $remainingQuantity,
							'allotted_qty' 	=> $totAllotQty + $remainingQuantity,
						]);
						
						$inspTakaNumber      = $dataWIS->insp_taka_number;
						$dyeingLotNumber     = $dataWIS->dyeing_lot_number;
						$dyeingTakaNumber    = $dataWIS->dyeing_taka_number;
						
						$newItem = WarehouseOutItem::create([
							'process_type_id' 	=> $dataWI->process_type_id ?? 0,
							'wis_id' 			=> $wisId,
							'warehouse_item_id' => $warehouseItemId, 
							'warehouse_id' 		=> $dataWI->warehouse_id,
							'ware_comp_id' 		=> $dataWI->ware_comp_id,
							'item_id' 			=> $dataWI->item_id,
							'item_type_id' 		=> $dataWI->item_type_id,
							'unit_type_id' 		=> $dataWI->unit_type_id,
							'receiver_id'       => $dataWI->receiver_id,
							'insp_taka_number'  => $inspTakaNumber,
							'dyeing_lot_number' => $dyeingLotNumber,
							'dyeing_taka_number'=> $dyeingTakaNumber,
							'item_qty' 			=> $remainingQuantity,
							'pcs' 				=> $dataWI->pcs ?? 0.00,
							'cut' 				=> $dataWI->cut,
							'meter' 			=> $dataWI->meter ?? 0.00,
							'individual_id' 	=> $individualId,
							'work_pro_req_id'   => $wprnId,
							'work_order_id' 	=> $workOrderId,
							'item_remark' 		=> $allotmentRemark,
							'grey_quality' 		=> $dataWI->grey_quality,
							'dyeing_color' 		=> $dataWI->dyeing_color,
							'coated_pvc' 		=> $dataWI->coated_pvc,
							'print_job' 		=> $dataWI->print_job,
							'extra_job' 		=> $dataWI->extra_job,
							'created' 			=> now(),
							'financial_year' 	=> currentFinancialYear(),
							'status' => 1,
						]);	
						
						$query    = WarehouseBalanceItem::where('item_id', $newItem->item_id)
						->where('item_type_id', $newItem->item_type_id)
						->where('dyeing_color', $newItem->dyeing_color)
						->where('coating_type', $newItem->coated_pvc)
						->where('print_job', $newItem->print_job)
						->where('extra_job', $newItem->extra_job)
						->orderBy('id', 'desc');
						$opItemQty = $query->value('item_qty');						 
						
						$ItemTypeID  = $dataWI->item_type_id;						
						if($ItemTypeID == '3')
						{
							$affectedRows = WarehouseBalanceItem::where('item_id', $newItem->item_id)
								->where('item_type_id', $newItem->item_type_id)
								->where(function ($query) {
									$query->whereNull('dyeing_color')
										  ->orWhere('dyeing_color', '0');
								})
								->whereNull('coating_type')
								// ->where('print_job', $newItem->print_job)
								// ->where('extra_job', $newItem->extra_job)
								->where('balance_status', '1')
								->update(['balance_status' => 0]);

						} 
						else 
						{
							$affectedRows = WarehouseBalanceItem::where('item_id', $newItem->item_id)
							->where('item_type_id', $newItem->item_type_id)
							->where('dyeing_color', $newItem->dyeing_color)
							->where('coating_type', $newItem->coated_pvc)
							// ->where('print_job', $newItem->print_job)
							// ->where('extra_job', $newItem->extra_job)
							->where('balance_status', 1)					  
							->update(['balance_status' => 0]);			
						}
						if ($affectedRows === 0) {
							Log::warning('Update operation did not affect any rows', [
								'item_id' => $newItem->item_id,
								'item_type_id' => $newItem->item_type_id,
								'dyeing_color' => $newItem->dyeing_color,
								'coated_pvc' => $newItem->coated_pvc,
								'print_job' => $newItem->print_job,
								'extra_job' => $newItem->extra_job
							]);
						}

						
						$closingItemQty = $opItemQty - $newItem->item_qty;
						$warehouseBalanceItem = new WarehouseBalanceItem([
							'ware_in_item_id' 	=> 0,
							'ware_out_item_id' 	=> $newItem->id,
							'warehouse_id' 		=> $newItem->warehouse_id,
							'ware_comp_id' 		=> $newItem->ware_comp_id,
							'item_id' 			=> $newItem->item_id,
							'item_type_id' 		=> $newItem->item_type_id,
							'unit_type_id' 		=> $newItem->unit_type_id,
							'receiver_id'       => $newItem->receiver_id,
							'receive_date' 		=> now(),
							'op_item_qty' 		=> $opItemQty,
							'in_item_qty' 		=> 0,
							'out_item_qty' 		=> $newItem->item_qty,
							'item_qty' 			=> $closingItemQty,
							'grey_quality' 		=> $newItem->grey_quality,
							'dyeing_color' 		=> $newItem->dyeing_color,
							'coated_pvc' 		=> $newItem->coated_pvc,
							'print_job' 		=> $newItem->print_job,
							'extra_job' 		=> $newItem->extra_job,
							'created' 			=> now(),
							'financial_year' 	=> currentFinancialYear(),
							'balance_status' 	=> 1,
							'status' 			=> 1,
						]);
						$warehouseBalanceItem->save();					
						$flag = true;
					} 
							
				}
			}
		}
		 
		if ($flag) 
		{
			// Stock Allotted successfully
			WorkOrder::whereKey($workOrderId)->update(['is_work_require_request_accepted' => 'Yes']);
			DB::commit();
			Session::put('message', 'Stock Allotted successfully.');
			Session::put("messageClass", "successClass");
		} else {
			// Stock Not Allotted
			DB::rollBack();
			Session::put('message', 'Stock Not Allotted.');
			Session::put("messageClass", "errorClass");
		}

		return redirect("/show-warehouse-item-requirement");
		} catch (\Throwable $e) {
			DB::rollBack();
			report($e);
			Session::put('message', 'Stock Not Allotted.');
			Session::put("messageClass", "errorClass");
			return redirect()->back()->withInput();
		}

    }
		
	public function StoreWarehouseYarnBeamStockAllotment(Request $request)
    {
		    // echo "<pre>"; print_r($request->all()); exit;
	    $validator = Validator::make($request->all(), [             
			"_token"                => "required",
			"received_quantities.*" => "required|numeric",  
			"work_process_req_ids.*" => "required|numeric", 
			"work_order_id"         => "required|numeric",
			"allotment_remark"      => "required|string",
		], [            
			"_token.required"                => "Token not found",
			"received_quantities.*.required" => "Received Quantity not found for one or more items",
			"received_quantities.*.numeric"  => "Received Quantity must be a number for one or more items",
			"work_process_req_ids.*.required" => "Work process request not found for one or more items",
			"work_process_req_ids.*.numeric"  => "Work process request must be a number for one or more items",
			"work_order_id.required"         => "Work order not found",
			"work_order_id.numeric"          => "Work order must be a number",
			"allotment_remark.required"      => "Stock Allotment remark not found",
			"allotment_remark.string"        => "Stock Allotment remark must be a string",
		]);

		if ($validator->fails()) {
			Session::put('message', $validator->messages()->first());
			Session::put("messageClass", "errorClass");
			return redirect()->back()->withInput();
		}

		DB::beginTransaction();

		try {
 
        $user 				= Auth::user();
        $userId 			= $user->id;
        $individualId 		= $user->individual_id;
        $workProcessReqIds 	= $request->work_process_req_ids;
        $workOrderId 		= $request->work_order_id;
        $allotmentRemark 	= $request->allotment_remark;
		$receivedQuantity 	= $request->received_quantities;
		 
        $flag = false;

		foreach ($workProcessReqIds as $key => $wprId) 
		{
			$dataWPR = WorkProcessRequirement::where('id', '=', $wprId)->first();
			if (!$dataWPR) {
				continue;
			}

			$itemId = $dataWPR->item_id;
			$wisId  = $dataWPR->wis_id;


				$processTypeId 			= $dataWPR->process_type_id;
				$itemTypeId 			= $dataWPR->item_type_id;
				$unitTypeId 			= $dataWPR->unit_type_id;
			$requiredQuantity 		= (float) $dataWPR->quantity;
			$receivedQuantity 		= $requiredQuantity;
			$whbId 					= $dataWPR->warehouse_balance_item_id;

			// Current warehouse_item_stocks primary stock id is `id`; older code called it wis_id.
			
			if(!empty($wisId))
			{
				$dataWISList = WarehouseItemStock::whereKey($wisId)->where('item_id', '=', $itemId)->where('item_type_id', '=', $itemTypeId)->where('is_allotted_stock', '=', 'No')->where('status', '!=', 'Deleted')->orderBy('id')->get();
			} 
			else 
			{
				$dataWISList = WarehouseItemStock::where('item_id', '=', $itemId)->where('item_type_id', '=', $itemTypeId)->where('is_allotted_stock', '=', 'No')->where('status', '!=', 'Deleted')->orderBy('id')->get();
			}	
			
			foreach ($dataWISList as $dataWIS) 
			{
				$wisId 					= $dataWIS->id;
				$warehouseItemId 		= $dataWIS->warehouse_item_id;
				$dataWI 				= WarehouseItem::find($warehouseItemId);
				$inspQuanSize        	= $dataWIS->insp_quan_size;
				$inspAllotQuanSize   	= $dataWIS->insp_allot_quan_size;
				$inspTakaNumber      	= $dataWIS->insp_taka_number;
				$inspBalQuanSize     	= $inspQuanSize - $inspAllotQuanSize;
				$remainingQuantity   	= min($receivedQuantity, $inspQuanSize - $inspAllotQuanSize);

				WarehouseItemStock::whereKey($wisId)
					->where('status', '!=', 'Deleted')
					->update([
						'insp_allot_quan_size' => $inspAllotQuanSize + $remainingQuantity,
						'insp_bal_quan_size'   => max(0, $inspQuanSize - ($inspAllotQuanSize + $remainingQuantity)),
						'is_allotted_stock'    => $inspQuanSize <= ($inspAllotQuanSize + $remainingQuantity) ? 'Yes' : 'No',
						'allot_work_order_id'  => $workOrderId,
						'work_pro_req_id'      => $wprId,
						'stock_alloted_by'     => $individualId,
						'alloted_remark'       => $allotmentRemark,
					]);
					
				WorkProcessRequirement::where('id', '=', $wprId)
				->update([
					'is_accept' 		=> '1',
					'alloted_quantity' 	=> $requiredQuantity,
					'alloted_remark' 	=> $allotmentRemark,
					'modified_by' 		=> $individualId,
					'modified_at' 		=> now(),
				]);		
		
				$totItemQty 		= $dataWI->item_qty;
				$totAllotQty 		= $dataWI->allotted_qty;
				WarehouseItem::where(['id' => $warehouseItemId])
				->update([
					'item_qty' 		=> $totItemQty - $remainingQuantity,
					'allotted_qty' 	=> $totAllotQty + $remainingQuantity,
				]);
				
				$newItem = WarehouseOutItem::create([
					'process_type_id' 	=> $dataWI->process_type_id ?? 0,
					'wis_id' 			=> $wisId,
					'warehouse_item_id' => $warehouseItemId, 
					'warehouse_id' 		=> $dataWI->warehouse_id,
					'ware_comp_id' 		=> $dataWI->ware_comp_id,
					'item_id' 			=> $dataWI->item_id,
					'item_type_id' 		=> $dataWI->item_type_id,
					'unit_type_id' 		=> $dataWI->unit_type_id,
					'receiver_id'       => $dataWI->receiver_id,
					'insp_taka_number'  => $inspTakaNumber,
					'item_qty' 			=> $remainingQuantity,
					'pcs' 				=> $dataWI->pcs ?? 0.00,
					'cut' 				=> $dataWI->cut,
					'meter' 			=> $dataWI->meter ?? 0.00,
					'individual_id' 	=> $individualId,
					'work_order_id' 	=> $workOrderId,
					'work_pro_req_id'   => $wprId,
					'item_remark' 		=> $allotmentRemark,
					'grey_quality' 		=> $dataWI->grey_quality,
					'dyeing_color' 		=> $dataWI->dyeing_color,
					'coated_pvc' 		=> $dataWI->coated_pvc,
					'print_job' 		=> $dataWI->print_job,
					'extra_job' 		=> $dataWI->extra_job,
					'created' 			=> now(),
					'financial_year' 	=> currentFinancialYear(),
					'status' => 1,
				]);	

				$flag = true;
				
				$query    = WarehouseBalanceItem::where('item_id', $newItem->item_id)
				->where('item_type_id', $newItem->item_type_id)
				->where('dyeing_color', $newItem->dyeing_color)
				->where('coating_type', $newItem->coated_pvc)
				->where('print_job', $newItem->print_job)
				->where('extra_job', $newItem->extra_job)
				->orderBy('id', 'desc');
				
				if(!empty($whbId)) 
				{
					$opItemQty = $query->where('id', $whbId)->value('item_qty');
				} 
				else 
				{
					$opItemQty = $query->value('item_qty');
				}	
				
				$affectedRows = WarehouseBalanceItem::where('item_id', $newItem->item_id)
				->where('item_type_id', $newItem->item_type_id)
				->where('dyeing_color', $newItem->dyeing_color)
				->where('coating_type', $newItem->coated_pvc)
				->where('print_job', $newItem->print_job)
				->where('extra_job', $newItem->extra_job)
				->where('balance_status', 1)					  
				->update(['balance_status' => 0]);				
				if (!$affectedRows) 
				{           
					dd('Update failed for WarehouseBalanceItem');
				}
				
				$closingItemQty = $opItemQty - $newItem->item_qty;
				$warehouseBalanceItem = new WarehouseBalanceItem([
					'ware_in_item_id' 	=> 0,
					'ware_out_item_id' 	=> $newItem->id,
					'warehouse_id' 		=> $newItem->warehouse_id,
					'ware_comp_id' 		=> $newItem->ware_comp_id,
					'item_id' 			=> $newItem->item_id,
					'item_type_id' 		=> $newItem->item_type_id,
					'unit_type_id' 		=> $newItem->unit_type_id,
					'receiver_id'       => $newItem->receiver_id,
					'op_item_qty' 		=> $opItemQty,
					'in_item_qty' 		=> 0,
					'out_item_qty' 		=> $newItem->item_qty,
					'item_qty' 			=> $closingItemQty,
					'grey_quality' 		=> $newItem->grey_quality,
					'dyeing_color' 		=> $newItem->dyeing_color,
					'coated_pvc' 		=> $newItem->coated_pvc,
					'print_job' 		=> $newItem->print_job,
					'extra_job' 		=> $newItem->extra_job,
					'created' 			=> now(),
					'financial_year' 	=> currentFinancialYear(),
					'balance_status' 	=> 1,
					'status' 			=> 1,
				]);
				$warehouseBalanceItem->save();	
				
				
				$receivedQuantity -= $remainingQuantity;
				if ($receivedQuantity <= 0) {
					break;
				}
			}
					
		}   
        
		if($flag) 
		{	
			WorkOrder::whereKey($workOrderId)->update(['is_work_require_request_accepted' => 'Yes']);
			DB::commit();
            Session::put('message', 'Stock Alloted successfully.');
            Session::put("messageClass", "successClass");
            return redirect("/show-warehouse-item-requirement");
        }
		else 
		{ 
			DB::rollBack();
            Session::put('message', 'Stock Not Alloted.');
            Session::put("messageClass", "errorClass");
            return redirect("/show-warehouse-item-requirement");	
			
		}
		} catch (\Throwable $e) {
			DB::rollBack();
			report($e);
			Session::put('message', 'Stock Not Alloted.');
			Session::put("messageClass", "errorClass");
			return redirect()->back()->withInput();
		}
    }
	 
	public function StoreWarehouseYarnStockAllotment(Request $request)
    {
		$validator = Validator::make($request->all(), [             
			"_token"                => "required",
			"received_quantities.*" => "required|numeric",  
			"work_process_req_ids.*" => "required|numeric", 
			"work_order_id"         => "required|numeric",
			"allotment_remark"      => "required|string",
		], [            
			"_token.required"                => "Token not found",
			"received_quantities.*.required" => "Received Quantity not found for one or more items",
			"received_quantities.*.numeric"  => "Received Quantity must be a number for one or more items",
			"work_process_req_ids.*.required" => "Work process request not found for one or more items",
			"work_process_req_ids.*.numeric"  => "Work process request must be a number for one or more items",
			"work_order_id.required"         => "Work order not found",
			"work_order_id.numeric"          => "Work order must be a number",
			"allotment_remark.required"      => "Stock Allotment remark not found",
			"allotment_remark.string"        => "Stock Allotment remark must be a string",
		]);

		if ($validator->fails()) {
			Session::put('message', $validator->messages()->first());
			Session::put("messageClass", "errorClass");
			return redirect()->back()->withInput();
		}
		
		$user 				= Auth::user();
		$userId 			= $user->id;
		$individualId 		= $user->individual_id;
		$workProcessReqIds 	= $request->work_process_req_ids;
		$workOrderId 		= $request->work_order_id;
		$allotmentRemark 	= $request->allotment_remark;

		$flag = false;

		DB::beginTransaction();

		try {
		foreach ($workProcessReqIds as $key => $wprId) 
		{
			$dataWPR = WorkProcessRequirement::where('id', $wprId)->where('status', 'Active')->first();
			if (!$dataWPR) {
				throw new \RuntimeException('Work process requirement not found.');
			}

			$itemId  = $dataWPR->item_id;
			$itemTypeId 			= $dataWPR->item_type_id;
			$receivedQuantity 		= (float) ($request->received_quantities[$key] ?? $dataWPR->balance_quantity ?: $dataWPR->required_quantity);
			$whbId 					= $dataWPR->warehouse_balance_item_id;

			$dataWISList = WarehouseItemStock::where('item_id', $itemId)
				->where('item_type_id', $itemTypeId)
				->where('is_allotted_stock', 'No')
				->where('status', 'Active')
				->where(function ($query) {
					$query->where('insp_bal_quan_size', '>', 0)
						->orWhere(function ($subQuery) {
							$subQuery->whereNull('insp_bal_quan_size')
								->where('insp_quan_size', '>', 0);
						});
				})
				->orderBy('id')
				->get();

			if ($dataWISList->isEmpty()) {
				throw new \RuntimeException('Stock not available for selected item.');
			}

			foreach ($dataWISList as $dataWIS) 
			{
					$wisId = $dataWIS->id;
					$warehouseItemId = $dataWIS->warehouse_item_id;
					$dataWI = WarehouseItem::find($warehouseItemId);
					$sourceItem = $dataWI ?: $dataWIS;
					$inspQuanSize       = (float) ($dataWIS->insp_quan_size ?? $dataWIS->quantity ?? 0);
					$inspTakaNumber      = $dataWIS->insp_taka_number;
					$inspAllotQuanSize   = (float) ($dataWIS->insp_allot_quan_size ?? 0);
					$inspBalQuanSize     = (float) ($dataWIS->insp_bal_quan_size ?? max(0, $inspQuanSize - $inspAllotQuanSize));
					$remainingQuantity   = min($receivedQuantity, $inspBalQuanSize);

					if ($remainingQuantity <= 0) {
						continue;
					}

					$newAllotQuantity = $inspAllotQuanSize + $remainingQuantity;
					$newBalanceQuantity = max(0, $inspBalQuanSize - $remainingQuantity);

					WarehouseItemStock::whereKey($wisId)
						->where('status', 'Active')
						->update([
							'insp_allot_quan_size' => $newAllotQuantity,
							'insp_bal_quan_size'   => $newBalanceQuantity,
							'is_allotted_stock'    => $newBalanceQuantity <= 0 ? 'Yes' : 'No',
							'allot_work_order_id'  => $workOrderId,
							'work_pro_req_id'      => $wprId,
							'stock_alloted_by'     => $individualId,
							'alloted_remark'       => $allotmentRemark,
							'modified_by'          => $individualId,
							'updated_at'           => now(),
						]);
						
					WorkProcessRequirement::where('id', '=', $wprId)
					->update([
						'alloted_quantity' 	=> DB::raw('alloted_quantity + ' . $remainingQuantity),
						'is_accept' 		=> 1,
						'alloted_remark' 	=> $allotmentRemark,
						'modified_by' 		=> $individualId,
						'modified_at' 		=> now(),
					]);		
			
					if ($dataWI) {
						$totItemQty 		= (float) ($dataWI->item_qty ?? 0);
						$totAllotQty 		= (float) ($dataWI->allotted_qty ?? 0);
						WarehouseItem::whereKey($warehouseItemId)
						->update([
							'item_qty' 		=> max(0, $totItemQty - $remainingQuantity),
							'allotted_qty' 	=> $totAllotQty + $remainingQuantity,
							'modified_by' 	=> $individualId,
							'updated_at' 	=> now(),
						]);
					}
					
					$newItem = WarehouseOutItem::create([
						'process_type_id' 	=> $sourceItem->process_type_id ?? 0,
						'wis_id' 			=> $wisId,
						'warehouse_item_id' => $warehouseItemId, 
						'warehouse_id' 		=> $sourceItem->warehouse_id,
						'ware_comp_id' 		=> $sourceItem->ware_comp_id,
						'item_id' 			=> $sourceItem->item_id,
						'item_type_id' 		=> $sourceItem->item_type_id,
						'unit_type_id' 		=> $sourceItem->unit_type_id,
						'receiver_id'       => $sourceItem->receiver_id,
						'insp_taka_number'  => $inspTakaNumber,
						'item_qty' 			=> $remainingQuantity,
						'pcs' 				=> $sourceItem->pcs ?? 0.00,
						'cut' 				=> $sourceItem->cut,
						'meter' 			=> $sourceItem->meter ?? 0.00,
						'individual_id' 	=> $individualId,
						'work_pro_req_id'   => $wprId,
						'work_order_id' 	=> $workOrderId,
						'item_remark' 		=> $allotmentRemark,
						'grey_quality' 		=> $sourceItem->grey_quality,
						'dyeing_color' 		=> $sourceItem->dyeing_color,
						'coating_type' 		=> $sourceItem->coating_type,
						'print_job' 		=> $sourceItem->print_job,
						'extra_job' 		=> $sourceItem->extra_job,
						'financial_year' 	=> currentFinancialYear(),
						'created_by' 		=> $individualId,
						'created_at' 		=> now(),
						'status' 			=> 'Active',
					]);	
					
					$query    = WarehouseBalanceItem::where('item_id', $newItem->item_id)
					->where('item_type_id', $newItem->item_type_id)
					->where('dyeing_color', $newItem->dyeing_color)
					->where('coating_type', $newItem->coating_type)
					->where('print_job', $newItem->print_job)
					->where('extra_job', $newItem->extra_job)
					->orderBy('id', 'desc');
					
					if(!empty($whbId)) 
					{
						$opItemQty = $query->where('id', $whbId)->value('item_qty');
					} 
					else 
					{
						$opItemQty = $query->value('item_qty');
					}	
					$opItemQty = (float) ($opItemQty ?? $inspBalQuanSize);
					
					$affectedRows = WarehouseBalanceItem::where('item_id', $newItem->item_id)
					->where('item_type_id', $newItem->item_type_id)
					->where('dyeing_color', $newItem->dyeing_color)
					->where('coating_type', $newItem->coating_type)
					->where('print_job', $newItem->print_job)
					->where('extra_job', $newItem->extra_job)
					->where('balance_status', 1)					  
					->update(['balance_status' => 0]);				
					
					$closingItemQty = $opItemQty - $newItem->item_qty;
					$warehouseBalanceItem = new WarehouseBalanceItem([
						'ware_in_item_id' 	=> 0,
						'ware_out_item_id' 	=> $newItem->id,
						'warehouse_id' 		=> $newItem->warehouse_id,
						'ware_comp_id' 		=> $newItem->ware_comp_id,
						'item_id' 			=> $newItem->item_id,
						'item_type_id' 		=> $newItem->item_type_id,
						'unit_type_id' 		=> $newItem->unit_type_id,
						'receiver_id'       => $newItem->receiver_id,
						'op_item_qty' 		=> $opItemQty,
						'in_item_qty' 		=> 0,
						'out_item_qty' 		=> $newItem->item_qty,
						'item_qty' 			=> $closingItemQty,
						'grey_quality' 		=> $newItem->grey_quality,
						'dyeing_color' 		=> $newItem->dyeing_color,
						'coating_type' 		=> $newItem->coating_type,
						'print_job' 		=> $newItem->print_job,
						'extra_job' 		=> $newItem->extra_job,
						'financial_year' 	=> currentFinancialYear(),
						'created_by' 		=> $individualId,
						'created_at' 		=> now(),
						'balance_status' 	=> 1,
						'status' 			=> 'Active',
					]);
					$warehouseBalanceItem->save();	
					
					
					$receivedQuantity -= $remainingQuantity;
					if ($receivedQuantity <= 0) {
						break;
					}
				}

			if ($receivedQuantity > 0) {
				throw new \RuntimeException('Full stock not available for selected item.');
			}
			
			$flag = true;
		}

			

		if ($flag) 
		{	
			WorkOrder::whereKey($workOrderId)->update(['is_work_require_request_accepted' => 'Yes']);
			DB::commit();
			Session::put('message', 'Stock Alloted successfully.');
			Session::put("messageClass", "successClass");
			return redirect("/show-warehouse-item-requirement");
		} else {
			DB::rollBack();
			Session::put('message', 'Stock Not Alloted.');
			Session::put("messageClass", "errorClass");
			return redirect("/show-warehouse-item-requirement");
		} 
		} catch (\Throwable $e) {
			DB::rollBack();
			Log::error('Yarn stock allotment failed', [
				'work_order_id' => $workOrderId,
				'work_process_req_ids' => $workProcessReqIds,
				'error' => $e->getMessage(),
			]);
			Session::put('message', $e->getMessage());
			Session::put("messageClass", "errorClass");
			return redirect()->back()->withInput();
		}
		
    }
	
	 	
	 	 
	public function getLotReturnItems(Request $request) 
	{
		  // echo "<pre>"; print_r($request->all()); exit;
		$id 			= $request->input('id');
		$reqLotNo 		= $request->input('req_lot_no');
		$workOrderId 	= $request->input('work_order_id');

		$dataWPR = WorkProcessRequirement::where('id', $id)
										  ->where('work_order_id', $workOrderId)
										  ->where('is_accept', '1')
										  ->where('status', '!=', 'Deleted')
										  ->get();

		if ($dataWPR->isEmpty()) {
			return response()->json(['message' => 'No items found for this lot.'], 404);
		} 

		$reqProIds = $dataWPR->pluck('id')->toArray();
		// $returnItems = WarehouseOutItem::whereIn('work_pro_req_id', $reqProIds)
					// ->where('work_order_id', $workOrderId)
					// ->where('status', '1')
					// ->with(['DepartmentReturnRequest:id,ware_out_item_id'])  
					// ->get(['id', 'wis_id', 'insp_taka_number', 'dyeing_lot_number', 'item_qty']);  
					
		 

		$returnItems = WarehouseOutItem::whereIn('work_pro_req_id', $reqProIds)
			->where('work_order_id', $workOrderId)
			->where('status', '!=', 'Deleted')
			->whereNotExists(function ($query) {
				$query->select(DB::raw(1))
					->from('work_inspection_details')
					->whereColumn('work_inspection_details.work_order_id', 'warehouse_out_items.work_order_id')
					->whereColumn('work_inspection_details.insp_taka_number', 'warehouse_out_items.insp_taka_number');
			})
			->with(['DepartmentReturnRequest:id,ware_out_item_id'])
			->get(['id', 'wis_id', 'insp_taka_number', 'dyeing_lot_number', 'dyeing_taka_number', 'item_qty']);
		
		 		
				
		// echo "<pre>"; print_r($returnItems); exit;
			 	    
	    return response()->json($returnItems); 
	}
			
	public function getBeamReturnItems(Request $request) 
	{
		$id 			= (int) $request->input('id');
		$workOrderId 	= (int) $request->input('work_order_id');

		$dataWPR = WorkProcessRequirement::where('id', $id)
										  ->where('work_order_id', $workOrderId)
										  ->where('is_accept', '1')
										  ->where('status', '!=', 'Deleted')
										  ->get();

		if ($dataWPR->isEmpty()) {
			return response()->json([]);
		} 

		$reqProIds = $dataWPR->pluck('id')->toArray();
		$returnItems = WarehouseOutItem::whereIn('work_pro_req_id', $reqProIds)
					->where('work_order_id', $workOrderId)
					->where('status', '!=', 'Deleted')
					->with(['DepartmentReturnRequest:id,ware_out_item_id'])
					->get(['id', 'wis_id', 'insp_taka_number', 'item_qty']);  
			 	    
		   return response()->json($returnItems);
	}
 
	 
 	public function getWarehouseItemStock(Request $request)
	{
		// Validate required inputs early
		$request->validate([
			'lot_number'                => 'required',
			'dyeing_ins_work_order_id'  => 'required|integer',
		]);

		$lotNumber = $request->input('lot_number');
		$workOrdId = $request->input('dyeing_ins_work_order_id');

		///////////////////////New Code Start///////////////////
		if (!empty($lotNumber)) 
		{
			$dataWOI = WorkOrderItem::where('work_order_id', $workOrdId)
				->where('status', 'Active')
				->whereHas('Customer', function ($q) {
					$q->where('is_lab_test_required', 'Yes');
				})
				->select(['id','work_order_id','customer_id','sale_order_id','sale_order_item_id','meter','created_at','status'])
				->with(['Customer:id,name,is_lab_test_required,status'])
				->get();

			$dataWprChk = WorkProcessRequirement::where('req_lot_no', $lotNumber)
				->where('work_order_id', $workOrdId)
				->where('is_accept', '1')
				->where('status', 'Active')
				->where('is_all_item_returned', '=', 'No')
				->get();

			if ($dataWOI->isNotEmpty() && $dataWprChk->isNotEmpty()) {
				$pending = $dataWprChk->filter(function ($wpr) {
					return empty($wpr->is_lab_test_complete)
						|| strtolower(trim((string)$wpr->is_lab_test_complete)) !== 'yes';
				});

				if ($pending->isNotEmpty()) {
					$customerNames = $dataWOI->pluck('Customer.name')->filter()->unique()->values()->toArray();
					$namesStr = !empty($customerNames) ? implode(', ', $customerNames) : 'Unknown Customer';
					$message_en = "Inspection for customer(s): {$namesStr} is pending. Please ensure the lab test is completed before proceeding.";

					return response()->json([
						'message' => $message_en
					], 422);
				}
			}
		}
		///////////////////////New Code End///////////////////

		// Resolve work process requirement(s)
		$dataWPR = WorkProcessRequirement::where('req_lot_no', $lotNumber)
			->where('work_order_id', $workOrdId)
			->where('is_accept', '1')
			->where('status', 'Active')
			->where('is_all_item_returned', '=', 'No')
			->get();

		if ($dataWPR->isEmpty()) 
		{
			// You were using $lotNumber as an id fallback — keep that behavior but be explicit.
			$singleWPR = WorkProcessRequirement::where('id', $lotNumber)
				->where('work_order_id', $workOrdId)
				->where('is_accept', '1')
				->where('status', 'Active')
				->where('is_all_item_returned', '=', 'No')
				->first();

			if (!$singleWPR) {
				 return response()->json(['message' => 'Work Process Requirement lot number not avaliable.'], 404);
			}

			$reqProIds 	= [$singleWPR->id]; 
			$wpr 		= $singleWPR;
		} else {
			$reqProIds 	= $dataWPR->pluck('id')->toArray(); 
			$wpr 		= $dataWPR->first();
		}

		if (!empty($lotNumber) && $wpr) 
		{
			$processId 		= (int) $wpr->process_type_id;
			// $stockAccDate 	= !empty($wpr->acc_deny_date) ? date('Y-m-d', strtotime($wpr->acc_deny_date)) : null;	
			$stockAccDate 	= '2026-08-01';			 
			$currDate 		=  date('Y-m-d');
			if ($processId === 3 && $stockAccDate === $currDate) 
			{
				return response()->json([
					'message' => 'Greige fabric allotment and dyeing inspection should not occur on the same date. This is currently allowed temporarily. however, in the future, such entries will not be accepted for this process.'
				], 422);
			}
		}
		
		
		 


		// --- Aggregate inspection outputs by insp_taka_number (so multiple inspection rows are summed) ---
		$winspData = WorkInspectionDetail::where('work_order_id', $workOrdId)->where('status', 'Active')
			// limit to current lot if available (prevents pulling unrelated takas)
			->when(!empty($lotNumber), function ($q) use ($lotNumber) {
				$q->where('dyeing_lot_number', $lotNumber);
			})
			->select('insp_taka_number', \DB::raw('SUM(COALESCE(output_quantity,0)) as total_output'))
			->groupBy('insp_taka_number')
			->get();

		// map: insp_taka_number => total_output (float)
		$winspMap = $winspData->pluck('total_output', 'insp_taka_number')
			->map(function ($v) { return (float) $v; })
			->toArray();

		// get stock items (unchanged criteria)
		$stockItems = WarehouseOutItem::whereIn('work_pro_req_id', $reqProIds)
			->where('work_order_id', $workOrdId)
			->where('status', 'Active')
			->where('is_item_return_whouse', '0')
			->get(['insp_taka_number', 'item_qty']);

		 
		$stockItems->transform(function ($item) use ($winspMap) {
			$inspNo = (string) ($item->insp_taka_number ?? '');
			$itemQty = is_numeric($item->item_qty) ? (float) $item->item_qty : 0.0;
			$outputSize = isset($winspMap[$inspNo]) ? (float) $winspMap[$inspNo] : 0.0;

			// remaining = item_qty - total_output
			$remaining = $itemQty - $outputSize;
			if ($remaining < 0) {
				$remaining = 0.0;
				$wasNegative = true;
			} else {
				$wasNegative = false;
			}

			// attach helpful fields to the model instance
			$item->output_quan_size           = $outputSize;
			$item->remaining_qty              = $remaining;
			// show clamped remaining in item_qty so it never becomes negative in JSON/UI
			$item->item_qty                   = $remaining;
			$item->was_output_more_than_item  = $wasNegative;

			return $item;
		});
		

		$workorder = WorkOrder::select('id', 'process_type', 'process_type_id')
			->where('id', $workOrdId)
			->first();

		if (!$workorder) {
			return response()->json(['message' => "Work order {$workOrdId} not found."], 404);
		}

		$machineId = null;
		$procTypeId = (int) $workorder->process_type_id;

		if ($procTypeId === 3) {
			$machineId = $wpr->dyeing_machine_id ?? null;
		} elseif ($procTypeId >= 4) {
			$machineId = $wpr->dyeing_machine_id ?? null;
		}

		$machineName = $machineId ? Machine::where('id', $machineId)->value('name') : null;

		if (empty($machineId)) 
		{ 
			return response()->json([
				'message' => "Please allocate a machine to Lot {$lotNumber} before performing its inspection."
			], 422);
		}
 
		return response()->json([
			'reqProIds' => $reqProIds[0] ?? '',
			'machineId' => $machineId,
			'machineName' => $machineName,
			'stockItems' => $stockItems, 
			'lot_number' => $lotNumber
		], 200);


	}
	
	public function getWarehouseItemStockPrint(Request $request) 
	{
		$lotNumber  = $request->input('lot_number');
		$workOrdId  = $request->input('dyeing_ins_work_order_id'); 

		$dataWPR = WorkProcessRequirement::where('req_lot_no', $lotNumber)
					->where('work_order_id', $workOrdId)
					->where('is_accept', '1')
					->where('status', '1')
					->get();  

		if ($dataWPR->isEmpty()) {
			$singleWPR = WorkProcessRequirement::where('id', $lotNumber)
						  ->where('work_order_id', $workOrdId)
						  ->where('is_accept', '1')
						  ->where('status', '1')
						  ->first();

			if (!$singleWPR) {
				return response()->json(['message' => 'Work Process Requirement lot number not found.'], 404);
			}

			// Convert to Collection to keep logic consistent
			$dataWPR = collect([$singleWPR]);
		}

		$reqProIds = $dataWPR->pluck('id')->toArray(); 

		$stockItems = WarehouseOutItem::whereIn('work_pro_req_id', $reqProIds)
						->where('work_order_id', $workOrdId)
						->where('status', '1')
						->where('is_item_return_whouse', '0')
						->get(['insp_taka_number', 'item_qty']);

		return response()->json($stockItems);
	}
 
	 
  
	public function updateTotGenrateJw($wprId)
	{
		if (empty($wprId) || !is_numeric($wprId)) {
			return response()->json(['status' => false, 'message' => 'Invalid Work Process Requirement ID.'], 422);
		}

		$dataRow = WorkProcessRequirement::where('id', $wprId)->where('status', '1')->where('is_accept', '1')->first();

		if (!$dataRow) {
			return response()->json(['status' => false, 'message' => 'Work Process Requirement not found.'], 404);
		}

		DB::beginTransaction();

		try {
			$dataRow->tot_genrate_jw = ((int) $dataRow->tot_genrate_jw) + 1;
			$dataRow->save();

			DB::commit();
		} catch (\Throwable $e) {
			DB::rollBack();
			report($e);
			return response()->json(['status' => false, 'message' => 'Print count could not be updated.'], 500);
		}

		return response()->json(['status' => true, 'message' => 'Print count updated successfully.', 'tot_genrate_jw' => $dataRow->tot_genrate_jw], 200);
	}
		
	public function addWorkRequisitionForCoatingAndStockAllotment(Request $request)
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

		if ($validator->fails()) 
		{
			$errors = $validator->errors()->all();
			$errorMessage = implode("<br>", $errors);
			Session::put('message', $errorMessage);
			Session::put("messageClass", "errorClass");
			return redirect()->back()->withInput();
		}
		
		$reqLotNo = trim((string) $request->req_lot_no);
		$wisIdArr = (array) $request->wis_id;		
		foreach ($wisIdArr as $key => $wisId) 
		{
			$dyeingLotNumber = WarehouseItemStock::where('wis_id', $wisId)
				->where('is_allotted_stock', 'No')
				->where('status', '1')
				->value('dyeing_lot_number');

			 
			$dyeingLotNumber = trim((string) ($dyeingLotNumber ?? '0')); 

			if ($reqLotNo !== $dyeingLotNumber) 
			{ 
				Session::put('message', 'Lot number mismatch: required ' . $reqLotNo . ', found ' . $dyeingLotNumber);
				Session::put("messageClass", "errorClass");
				return redirect()->back()->withInput();
			}
		} 

		DB::beginTransaction();
		try {
			$workOrderId 	= $request->work_order_id_req;
			$workOrder 		= WorkOrder::with('WorkOrderItem')->where('work_order_id', $workOrderId)->where('status', '1')->firstOrFail();
			$dyeingColor 	= $workOrder->WorkOrderItem[0]->dyeing_color ?? null;
			  
			$userId 		= Auth::id();
			$userD 			= User::find($userId);	 
			$individualId 	= $userD->individual_id ?? null;
			$userfullUrl 	= $userD->last_searched_url ?? null;
			
			$currentDate 	= now();
			$itemTypeId 	= $request->ext_item_type_id;

			if ($itemTypeId == '4') 
			{
				if (is_numeric($reqLotNo))   
				{
					$maxReqLotNo = DB::table('work_process_requirements')
						->where('item_type_id', '=', '3')
						->max(DB::raw('CAST(req_lot_no AS UNSIGNED)'));

					if ($reqLotNo > $maxReqLotNo) 
					{
						throw new \Exception('You have entered a wrong lot number.');
					}
				}				
			}
			
			if ($request->filled('tot_req_quantity')) {
				$unitTypeId = ItemType::where('item_type_id', $itemTypeId)->value('unit_type_id');
				$wisIds 	= is_array($request->wis_id) ? implode(',', $request->wis_id) : null;				 
				$workReq = WorkProcessRequirement::create([
					'work_order_id' 	=> $workOrderId,
					'item_id'       	=> $request->itemIdReq,
					'process_type_id' 	=> $workOrder->process_type_id,
					'item_type_id'  	=> $itemTypeId,
					'unit_type_id'  	=> $unitTypeId,
					'work_req_send_by' 	=> $individualId,
					'quantity'      	=> $request->tot_req_quantity,
					'status'        	=> 1,
					'created'       	=> $currentDate,
					'financial_year' 	=> currentFinancialYear(),
					'dyeing_color'      => $dyeingColor,
					'req_lot_no'       	=> $request->req_lot_no,
					'dept_req_ids'      => $wisIds,
				]); 
				
				if (!$workReq) {
					throw new \Exception('Error occurred while adding work requisition.');
				}

				WorkOrder::where('work_order_id', $workOrderId)->update([
					'work_req_send_by' => $individualId,
					'is_work_require_request_accepted' => null,
					'work_req_send_date' => $currentDate,
				]);
			}

			// Stock allotment logic
			$flag 			= false;
			$wisIdArr 		= (array) $request->wis_id;
			$reqGreyQtyArr  = array_values(array_filter((array)$request->req_grey_qty));
			$wprnId = $workReq->id;
			$totAltquantity = $request->tot_req_quantity;

			foreach ($wisIdArr as $key => $wisId) 
			{
				if (isset($reqGreyQtyArr[$key])) 
				{
					$dataWIS = WarehouseItemStock::where('wis_id', $wisId)->where('is_allotted_stock', 'No')->where('status', '1')->first();
					if ($dataWIS) 
					{
						$remainingQuantity = min($reqGreyQtyArr[$key], $dataWIS->insp_quan_size - $dataWIS->insp_allot_quan_size);
						
						WarehouseItemStock::where('wis_id', $wisId)->update([
							'insp_allot_quan_size' 	=> $dataWIS->insp_allot_quan_size + $remainingQuantity,
							'insp_bal_quan_size' 	=> max(0, $dataWIS->insp_quan_size - ($dataWIS->insp_allot_quan_size + $remainingQuantity)),
							'is_allotted_stock' 	=> $dataWIS->insp_quan_size <= ($dataWIS->insp_allot_quan_size + $remainingQuantity) ? 'Yes' : 'No',
							'allot_work_order_id' 	=> $workOrderId,
							'work_pro_req_id' 		=> $wprnId,
							'stock_alloted_by' 		=> $individualId,
							'alloted_remark' 		=> $request->alloted_remark,
						]);

						WorkProcessRequirement::where('id', $wprnId)->update([
							'is_pro_acc_by_warehouse' 	=> 'Yes',
							'is_accept' 				=> '1',
							'alloted_quantity' 			=> $totAltquantity,
							'process_accepted_by' 		=> $individualId,
							'acc_deny_date' 			=> now(),
						]);
						

						WarehouseItem::where('id', $dataWIS->warehouse_item_id)->decrement('item_qty', $remainingQuantity);
						WarehouseItem::where('id', $dataWIS->warehouse_item_id)->increment('allotted_qty', $remainingQuantity);
						$newItem = WarehouseOutItem::create([
							'process_type_id'  => $dataWIS->process_type_id ?? 0,
							'wis_id'           => $wisId,
							'warehouse_item_id' => $dataWIS->warehouse_item_id,
							'warehouse_id'     => $dataWIS->warehouse_id,
							'ware_comp_id'     => $dataWIS->ware_comp_id,
							'item_id'          => $dataWIS->item_id,
							'item_type_id'     => $dataWIS->item_type_id,
							'unit_type_id'     => $dataWIS->unit_type_id,
							'receiver_id'      => $dataWIS->receiver_id,
							'insp_taka_number' => $dataWIS->insp_taka_number,
							'dyeing_lot_number'=> $dataWIS->dyeing_lot_number,
							'dyeing_taka_number'=> $dataWIS->dyeing_taka_number,
							'item_qty'         => $remainingQuantity,
							'pcs'              => $dataWIS->pcs ?? 0.00,
							'cut'              => $dataWIS->cut,
							'meter'            => $dataWIS->meter ?? 0.00,
							'individual_id'    => $individualId,
							'work_pro_req_id'  => $wprnId,
							'work_order_id'    => $workOrderId,
							'item_remark'      => $request->alloted_remark,
							'grey_quality'     => $dataWIS->grey_quality,
							'dyeing_color'     => $dataWIS->dyeing_color,
							'coated_pvc'       => $dataWIS->coated_pvc,
							'print_job'        => $dataWIS->print_job,
							'extra_job'        => $dataWIS->extra_job,
							'created'          => now(),
							'financial_year'   => currentFinancialYear(),
							'status'           => 1,
						]);

						// WarehouseBalanceItem logic
						$query = WarehouseBalanceItem::where('item_id', $newItem->item_id)
							->where('item_type_id', $newItem->item_type_id)
							->where('dyeing_color', $newItem->dyeing_color)
							->where('coated_pvc', $newItem->coated_pvc)
							->where('print_job', $newItem->print_job)
							->where('extra_job', $newItem->extra_job)
							->orderBy('id', 'desc');

						$opItemQty = $query->value('item_qty');

						// Update balance status
						$updateQuery = WarehouseBalanceItem::where('item_id', $newItem->item_id)
							->where('item_type_id', $newItem->item_type_id)
							->where('print_job', $newItem->print_job)
							->where('extra_job', $newItem->extra_job)
							->where('balance_status', '1');

						if ($newItem->item_type_id == '3') {
							$updateQuery->where(function ($query) {
								$query->whereNull('dyeing_color')->orWhere('dyeing_color', '0');
							})->whereNull('coated_pvc');
						} else {
							$updateQuery->where('dyeing_color', $newItem->dyeing_color)
								->where('coated_pvc', $newItem->coated_pvc);
						}

						$affectedRows = $updateQuery->update(['balance_status' => 0]);

						$closingItemQty = $opItemQty - $newItem->item_qty;

						WarehouseBalanceItem::create([
							'ware_in_item_id' => 0,
							'ware_out_item_id' => $newItem->id,
							'warehouse_id' => $newItem->warehouse_id,
							'ware_comp_id' => $newItem->ware_comp_id,
							'item_id' => $newItem->item_id,
							'item_type_id' => $newItem->item_type_id,
							'unit_type_id' => $newItem->unit_type_id,
							'receiver_id' => $newItem->receiver_id,
							'receive_date' => now(),
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
							'status' => 1,
						]);

						$flag = true;
					}
				}
			}

			// Work Order Update Logic with Exception Handling
			if ($flag) 
			{ 
				$updateSuccess = WorkOrder::where('work_order_id', $workOrderId)
					->update([
						'is_work_require_request_accepted' => 'Yes',
						'is_item_received_from_warehouse' => 'Yes'
					]);	

				if (!$updateSuccess) {
					throw new \Exception('Stock allotted, but work order update failed.');
				}

				Session::flash('message', 'Stock Allotted successfully.');
				Session::flash("messageClass", "successClass");

			} else {
				Session::flash('message', 'Stock Not Allotted.');
				Session::flash("messageClass", "errorClass");
			}

			DB::commit();
			
			if (empty($userfullUrl)) 
			{
				return redirect()->back()->withInput();
			}
			return redirect($userfullUrl);
			
			// return redirect()->back()->withInput();
			// return redirect()->to(Session::get('previous_url'));

		} catch (\Exception $e) {
			DB::rollBack();

			Log::error('Error:', [
				'message' => $e->getMessage(),
				'line' => $e->getLine(),
				'file' => $e->getFile()
			]);

			Session::flash('message', 'An error occurred: ' . $e->getMessage());
			Session::flash("messageClass", "errorClass");
			return redirect()->back()->withInput();
		}
	
	}
 
	public function showWarehouseItemForPrintingRequirement(Request $request)
	{
		// ✅ Logged-in user info (if needed for future filtering)
		$userId    = Auth::id();
		$user      = User::find($userId);
		$userIndId = (int) ($user->individual_id ?? 0);

		// ✅ Allowed process IDs
		$allowedProcessIds = [6, 7, 8];

		// ✅ Fetch warehouse printing requirements
		$dataWPR = WorkProcessRequirement::with(['Item', 'ItemType', 'UnitType'])
			->where('status', 1)
			->whereIn('process_type_id', $allowedProcessIds)
			->orderByDesc('id')
			->paginate(20)
			->appends($request->all());

		// ✅ Fetch process items
		$processI = ProcessItem::where('status', 1)->get();

		// ✅ Return view
		return view(
			'frontend.workprocessrequirement.show-warehouse-item-for-printing-requirement',
			compact("dataWPR", "processI")
		);
	}
	
	public function getWorkProcessPrintingRequirement(Request $request)
    {
		 
		$FId 		= $request->FId; 
		$wprData 	= WorkProcessRequirement::find($FId); 
		$workOrdId  = $wprData->work_order_id;
		
		$dataWk 		= WorkOrder::where('work_order_id', '=', $workOrdId)->where('status', '=', '1')->first();
		// echo "<pre>"; print_r($dataWk); exit;
		$itemId  		= $dataWk->item_id;
		$WorkItemName   = $dataWk->item_name;
		$procesTypeId   = $dataWk->process_type_id;

		$dataWPR 		= WorkProcessRequirement::where('id', '=', $FId)->where('status', '=', '1')->where('is_accept', '=', '0')->get();

		$str ="";
		$str.='<input type="hidden" name="work_order_id" id="work_order_id" value="'.$workOrdId.'" class="form-control">';
		$str.='<table class="table table-bordered">
				  <tr>
                    <th>Item Name</th>
                    <th>Quantity</th>
                  </tr>';
		foreach($dataWPR as $row)
		{
			// echo "<pre>"; print_r($row); exit;
			$dataI 		= Item::where('item_id', '=', $row->item_id)->where('status', '=', '1')->first();
			$itemName 		= $dataI->item_name;
			$quantity 		= $row->quantity;


			$str.='<tr>
			<input type="hidden" name="wppr_id[]" id="wppr_id[]" value="'.$row->id.'" class="form-control">
			<input type="hidden" name="item_id[]" id="item_id[]" value="'.$row->item_id.'" class="form-control">
			<input type="hidden" name="item_name[]" id="item_name[]" value="'.$itemName.'" class="form-control">
			<input type="hidden" name="item_quan[]" id="item_quan[]" value="'.$quantity.'" class="form-control">
			<td> '.$itemName.' </td>
			<td> '.$quantity.' </td>';

		}

		$str.='</tr>
		</table>'; 
		
		$result = [];
		$result['wprDetails'] = $str;
		$result['WorkItemName'] = $WorkItemName;

		echo json_encode($result);


    }
	
	public function add_remark_for_deny_requisition(Request $request)
	{
		$userId = Auth::id();
		$userD  = User::find($userId);
		$IndId  = $userD->individual_id; 

		// ✅ Validation
		$validator = Validator::make($request->all(), [
			'work_order_id' => 'required|numeric',
			'deny_remark'   => 'required|string',
			'wppr_id.*'     => 'required|numeric',   // yaha pe wppr_id (request key jaisa)
		], [
			'work_order_id.required' => 'Work Order ID is required.',
			'work_order_id.numeric'  => 'Work Order ID must be a number.',
			'deny_remark.required'   => 'Deny Remark is required.',
			'deny_remark.string'     => 'Deny Remark must be a string.',
			'wppr_id.*.required'     => 'WPR ID is required for all items.',
			'wppr_id.*.numeric'      => 'WPR ID must be a number for all items.',
		]);

		if ($validator->fails()) 
		{
			Session::put('message', $validator->messages()->first());
			Session::put("messageClass", "errorClass");
			return redirect()->back()->withInput();
		} 

		$workOrderId = $request->work_order_id;
		$deny_remark = $request->deny_remark;
		$wppr_id_arr = $request->wppr_id;   // ✅ yaha bhi wppr_id

		$success = false;

		DB::beginTransaction();

		try {
		foreach ($wppr_id_arr as $wprId) 
		{
			$obj2 = WorkPrintProcessRequirement::find($wprId);
			if ($obj2) 
			{
				$obj2->is_pro_acc_by_warehouse = 'No';
				$obj2->process_deny_by         = $IndId;
				$obj2->acc_deny_date           = now();
				$obj2->alloted_remark          = $deny_remark; // aapke column ka naam ye hi hai
				$obj2->is_accept               = 2;
				$obj2->save();
				$success = true;
			}
		}
		if ($success) 
		{
			WorkOrder::where('work_order_id', $workOrderId)->update(['is_work_require_request_accepted' => 'No']);
			DB::commit();
			Session::put('message', 'Printing requisition request has been denied.');
			Session::put("messageClass", "successClass");
		} 
		else 
		{
			DB::rollBack();
			Session::put('message', 'Something went wrong.');
			Session::put("messageClass", "errorClass");
		}

		return redirect()->back()->withInput();
		} catch (\Throwable $e) {
			DB::rollBack();
			report($e);
			Session::put('message', 'Something went wrong.');
			Session::put("messageClass", "errorClass");
			return redirect()->back()->withInput();
		}
	}
  
	public function acceptWarehouseItemRequirementForPrinting($id)
	{
		$wprId 			= dec($id); 
		$wprData 		= WorkProcessRequirement::where('id', $wprId)->where('status', 1)->first();
		$itemId 		= $wprData->item_id;
		$itemTypeId 	= $wprData->item_type_id;
		$dyeingColor    = $wprData->dyeing_color;
		$coatedPvc    	= $wprData->coated_pvc;
		$reqLotNo    	= $wprData->req_lot_no;
		 
		$dataI 			= Item::where('item_id', '=', $itemId)->where('status', '=', '1')->first();
		$itemName 		= $dataI->item_name; 
		
		if (!$wprData) 
		{
			Session::put('message', 'Invalid or inactive requirement.');
			Session::put('messageClass', 'errorClass');
			return redirect()->back()->withInput();
		}
		
		$workOrder = WorkOrder::where('work_order_id', $wprData->work_order_id)->where('status', 1)->with('WorkOrderItem')->first();
		if (!$workOrder || empty($workOrder->process_type_id)) 
		{
			Session::put('message', 'Something went wrong. Work order not found.');
			Session::put('messageClass', 'errorClass');
			return redirect()->back()->withInput();
		}		 
		$dataWPR = WorkProcessRequirement::where('id', $wprId)->where('status', 1)->where('is_accept', 0)->with(['WorkOrderItem', 'Item'])->get();		 
		
		$deptReqIds = $wprData->dept_req_ids; // e.g. "174762,174765,174766"
		// $deptReqIdsArr = array_map('trim', explode(',', $deptReqIds)); // convert to [174762, 174765, 174766]
		$deptReqIdsArr = !empty($deptReqIds) ? array_map('trim', explode(',', $deptReqIds)) : [];


		$query = WarehouseItemStock::where('item_id', $itemId)
			->whereIn('wis_id', $deptReqIdsArr)
			->where('item_type_id', $itemTypeId)
			->where('dyeing_color', $dyeingColor)
			->where('coated_pvc', $coatedPvc)
			->where('dyeing_lot_number', $reqLotNo)
			->where('is_allotted_stock', 'No')
			->where('status', 1)
			->select([
				'wis_id', 
				'item_id', 
				'invoice_number', 
				'insp_taka_number', 
				'dyeing_lot_number', 
				'dyeing_taka_number', 
				'insp_bal_quan_size', 
				'dyeing_color', 
				'warehouse_item_id'
			])
			->with([
				'Item:item_id,item_name,internal_item_name',
				'WarehouseItem:id,warehouse_id',
				'WarehouseItem.Warehouse:id,warehouse_name',
				'WarehouseItem.WarehouseCompartment:id,warehousename',
			]);

		$dataWPR2 = $query->get();

		
		  // echo "<pre>"; print_r($dataWPR2); exit;
		
		$processI 		= ProcessItem::where('status', '=', '1')->whereIn('id', [2, 3, 4])->get();
		$dataSO 		= StockMillDispatch::where('status', '=', '1')->count();
		$totChDispach 	= $dataSO+1;
		
		return view('frontend.workprocessrequirement.accept-warehouse-item-requirement-for-printing', [
			'dataWPR'   => $dataWPR,
			'dataWPR2'  => $dataWPR2,
			'workOrder' => $workOrder,
			'wprData'   => $wprData,
			'itemName'  => $itemName,
			'itemId'  	=> $itemId,
			'totChDispach'  => $totChDispach,
			'processI'  => $processI,
		]);
	}

	public function printJobCardTraceability($id)
	{ 
		$reqLotNo = dec($id); 
		$baseWpr = WorkProcessRequirement::where('req_lot_no', $reqLotNo)
			->where('status', '1')
			->where('req_fabric_type', '1')
			->where('process_type_id', '3')
			->where('is_accept', '1')
			->first();

		if (! $baseWpr) {
			abort(404, 'Work Process Requirement not found.');
		}

		 
		$reqFabricType 	= $baseWpr->req_fabric_type;
		$processTypeId 	= $baseWpr->process_type_id;
		$wprId 			= $baseWpr->id;

		 
		$dataWPR = WorkProcessRequirement::where('req_lot_no', $reqLotNo)
			->where('req_fabric_type', $reqFabricType)
			->where('process_type_id', $processTypeId)
			->where('status', '1')
			->where('is_accept', '1')
			->orderByDesc('id')
			->get();
			
			

		if ($dataWPR->isEmpty()) {
			abort(404, 'Work Process Requirements not found.');
		} 
		 
		$dataWPR2 = WorkProcessRequirement::where('req_lot_no', $reqLotNo)
			->where('req_fabric_type', $reqFabricType)
			->where('process_type_id', $processTypeId)
			->where('status', '1')
			->where('is_accept', '1')
			->with([
				'WarehouseOutItem' => function ($q) {
					$q->where('is_item_return_whouse', '0')
					  ->select([
						  'id',  'wis_id',  'warehouse_item_id',  'item_id',  'item_type_id', 'item_qty',  'insp_taka_number', 'dyeing_lot_number', 'dyeing_taka_number',
						  'fabric_fault_reason_id',   'individual_id', 'receiver_id',  'work_pro_req_id',  'work_order_id',  'item_remark',  'grey_quality',  'dyeing_color',  'coated_pvc',  'is_item_return_whouse', 'status'
					  ])
					  ->with(['WarehouseItemStock' => function ($sq) {					  
						  $sq->select([
								'wis_id', 'vendor_id', 'invoice_number'
						  ]);
					  }]);
				},
				'Item',
				'ItemType',
				'UnitType'
			])
			->orderByDesc('id')
			->get(); 
		 
		$totalAllotedQuantity = $dataWPR2->flatMap->WarehouseOutItem->sum('item_qty'); 
		
		$workOrderId 	= $dataWPR2->first()->work_order_id;
		$data 			= WorkOrder::where('work_order_id', $workOrderId)->with('WorkOrderItem', 'WarehouseItem')->first();

		if (! $data) {
			abort(404, 'Work Order not found.');
		} 
		
		$dataPur = WorkProcessRequirement::where('req_lot_no', $reqLotNo)
		->where('status', '1')
		->with([
			'WorkOrder.WorkMaster',
			'WorkOrder.WorkMachine',
			'WarehouseOutItem.WarehouseItem',
			'Item:item_id,item_name',
			'WorkOrder.WorkOrderItem' => function ($query) {
				$query->select('woi_id', 'work_order_id', 'customer_id', 'sale_order_id', 'sale_order_item_id')
					->where('status', '=', '1')
					->with(['SaleOrderItem' => function ($q) {
						$q->select('sale_order_item_id', 'sale_order_id', 'dyeing_color', 'coated_pvc');
					}]);
			},
		])
		->get();
		
		$allotedQtyTotal 	= $dataPur->where('item_type_id', 3)->sum('alloted_quantity');
		 
		 
		
	 
			
			$pprIds = DB::table('packaging_process_requirements')
				->where('status',1)
				->where('is_delivered',2)
				->where('is_accept',1)
				->where('dyeing_lot_number', $reqLotNo)
				->whereNotNull('packaging_ord_id')
				->groupBy('packaging_ord_id')
				->selectRaw('MAX(ppr_id) as ppr_id')
				->pluck('ppr_id');

			$packOrd = PackagingProcessRequirement::whereIn('ppr_id', $pprIds)
				->with('PackagingOrder.Individual:id,name','Item:item_id,item_name','SaleOrder:sale_order_id,sale_order_number')
				->get();
 
			$packIds = $packOrd->map(function($p){ 
				return $p->packaging_ord_id ?? ($p->PackagingOrder->id ?? null);
			})->filter()->unique()->values()->all();

			$totals = [];
			if (!empty($packIds)) {
				$totals = DB::table('packaging_process_requirements')
					->select('packaging_ord_id', DB::raw('SUM(size_mtr) as totmtr'))
					->whereIn('packaging_ord_id', $packIds)
					->where('dyeing_lot_number', $reqLotNo)   // keep lot filter
					->where('status', 1)
					->where('is_delivered', 2)
					->where('is_accept', 1)
					->groupBy('packaging_ord_id')
					->pluck('totmtr', 'packaging_ord_id') // returns [packaging_ord_id => totmtr]
					->toArray();
			}  
			  
 
		 
		
		return view(
			'frontend.workprocessrequirement.print-job-card-traceability',
			compact('dataWPR', 'dataWPR2', 'totalAllotedQuantity', 'data', 'dataPur', 'packOrd', 'totals', 'reqLotNo')
		);
	
	}
	
	 
			 
	 

}
