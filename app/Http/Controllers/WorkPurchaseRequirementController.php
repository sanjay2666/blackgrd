<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\UnitType;
use App\Models\User;
use App\Models\PurchaseItem;
use App\Models\Warehouse;
use App\Models\WarehouseItem;
use App\Models\WarehouseCompartment;
use App\Models\WorkOrder;
use App\Models\WorkProcessRequirement;
use App\Models\WorkPurchaseRequirement;
use Validator, Auth, Session, Hash; 



class WorkPurchaseRequirementController extends Controller
{ 
	 
	public function add_work_purchase_requisition(Request $request)
	{
		$userId = Auth::id();
		$userD = User::find($userId);
		$IndId = $userD->individual_id ?? $userId;

		$request->validate([
		  'work_order_id' => 'required|numeric',
		  'pur_remark' => 'required|string',
		  'wpr_id' => 'required|array|min:1',
		  'item_id' => 'required|array|min:1',
		  'item_name' => 'required|array|min:1',
		  'item_quan' => 'required|array|min:1',
		  'wpr_id.*' => 'required|numeric',
		  'item_id.*' => 'required|numeric',
		  'item_name.*' => 'required|string',
		  'item_quan.*' => 'required|numeric',
		], [
		  'work_order_id.required' => 'Work Order ID is required.',
		  'work_order_id.numeric' => 'Work Order ID must be a number.',
		  'pur_remark.required' => 'Purchase Remark is required.',
		  'pur_remark.string' => 'Purchase Remark must be a string.',
		  'wpr_id.required' => 'WPR ID is required.',
		  'item_id.required' => 'Item ID is required.',
		  'item_name.required' => 'Item Name is required.',
		  'item_quan.required' => 'Item Quantity is required.',
		  'wpr_id.*.required' => 'WPR ID is required for all items.',
		  'wpr_id.*.numeric' => 'WPR ID must be a number for all items.',
		  'item_id.*.required' => 'Item ID is required for all items.',
		  'item_id.*.numeric' => 'Item ID must be a number for all items.',
		  'item_name.*.required' => 'Item Name is required for all items.',
		  'item_name.*.string' => 'Item Name must be a string for all items.',
		  'item_quan.*.required' => 'Item Quantity is required for all items.',
		  'item_quan.*.numeric' => 'Item Quantity must be a number for all items.',
		]);

		$workOrderId   = $request->work_order_id;
		$pur_remark   = $request->pur_remark;
		$wpr_id_arr   = $request->wpr_id;
		$item_id_arr   = $request->item_id;
		$item_name_arr   = $request->item_name;
		$item_quan_arr   = $request->item_quan;

		$data = [];

		DB::beginTransaction();

		try {
			foreach ($wpr_id_arr as $itemidk => $row) 
			{
				$wprId       	= $wpr_id_arr[$itemidk];
				$itemId     	= $item_id_arr[$itemidk];
				$itemqty     	= $item_quan_arr[$itemidk];
				$dataWPR     	= WorkProcessRequirement::whereKey($wprId)
					->where('status', '!=', 'Deleted')
					->first();

				if (!$dataWPR) {
					throw new \RuntimeException('Work process requirement not found.');
				}

				if ((int) $dataWPR->work_order_id !== (int) $workOrderId) {
					throw new \RuntimeException('Work order mismatch.');
				}

				$data[] = [
					'work_order_id' 		=> $dataWPR->work_order_id,
					'work_order_item_id' 	=> $dataWPR->work_order_item_id,
					'item_id' 				=> $dataWPR->item_id ?: $itemId,
					'item_type_id' 			=> $dataWPR->item_type_id,
					'unit_type_id' 			=> $dataWPR->unit_type_id,
					'required_quantity' 	=> $itemqty,
					'purchase_quantity' 	=> 0,
					'balance_quantity' 		=> $itemqty,
					'required_date' 		=> $dataWPR->required_date,
					'remarks' 				=> $pur_remark,
					'financial_year' 		=> currentFinancialYear(),
					'created_by' 			=> $IndId,
					'created_at' 			=> now(),
					'status' 				=> 'Active',
				];

				$dataWPR->is_accept = '2';
				$dataWPR->alloted_remark = $pur_remark;
				$dataWPR->modified_by = $IndId;
				$dataWPR->modified_at = now();
				$dataWPR->save();
			}

			$res = WorkPurchaseRequirement::insert($data);

			if (!$res) {
				throw new \RuntimeException('Purchase request could not be created.');
			}

			WorkOrder::whereKey((int) $workOrderId)->update(['is_work_require_request_accepted' => 'No']);

			DB::commit();

			Session::put('message', 'The purchase request has been successfully sent, and the requisition request has been denied.');
			Session::put("messageClass", "successClass");		
		} catch (\Throwable $e) {
			DB::rollBack();
			report($e);
			Session::put('message', $e->getMessage());
			Session::put("messageClass", "errorClass");		
		}

		return redirect()->back()->withInput();
	}

	 

}
