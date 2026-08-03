<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use App\Models\Colour;
use App\Models\Coting;
use App\Models\Individual;
use App\Models\IndividualAddress;
use App\Models\Item;
use App\Models\ItemType;
use App\Models\UnitType;
use App\Models\SaleOrder;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseCompartment;
use App\Models\WorkProcessRequirement;
use App\Models\WarehouseBalanceItem;
use App\Models\WarehouseItemStock;
use App\Models\WorkOrder;
use App\Models\WorkOrderItem;
use App\Models\ProcessItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class CommonController extends Controller
{
	public static function display_message($msgvar)
	{
		return display_message($msgvar);
	}

	public static function getEmpName($id)
	{
		if (empty($id)) {
			return '';
		}

		return Individual::where('id', $id)->value('name') ?: '';
	}

	 
	public function find_saleOrderNumer(Request $request)
	{
		$qsearch = trim($request->input('term', ''));

		$query = SaleOrder::query();
		$query->where('status', '!=', 'Deleted');
		$query->whereNotNull('sale_order_number');

		if ($qsearch != '') {
			$query->where('sale_order_number', 'like', '%'.$qsearch.'%');
		}

		$saleOrders = $query->orderBy('id', 'desc')
			->limit(12)
			->get(['id', 'sale_order_number']);

		return response()->json($saleOrders);
	}
  
    public function list_customer(Request $request)
    {
        $qsearch = trim($request->input('term', ''));

        $query = Individual::query();
        $query->where('status', 'Active');
        $query->where('type', 'customers');

        if ($qsearch != '') {
            $query->where(function ($query) use ($qsearch) {
                $query->where('name', 'like', '%'.$qsearch.'%');
                $query->orWhere('company_name', 'like', '%'.$qsearch.'%');
                $query->orWhere('phone', 'like', '%'.$qsearch.'%');
                $query->orWhere('whatsapp', 'like', '%'.$qsearch.'%');
            });
        }

        $customers = $query->orderBy('id', 'asc')->limit(10)->get();

        return response()->json($customers);
    }
	
	public function list_vendor(Request $request)
	{

		$qsearch 	= trim($request->term);
		$dataI 		= Individual::where(function ($query) use ($qsearch) {
			$query->whereRaw("CONCAT(COALESCE(name, ''), COALESCE(whatsapp, ''), COALESCE(email, '')) LIKE ?", ['%' . $qsearch . '%']);
			})
			->where('type', 'vendors')
			->with('IndividualBillingAddress')
			->with('IndividualShipingAddress')
			->where('status', 'Active')
			->limit(10)
			->get();
			echo json_encode($dataI);
	}

	public function list_customerandvendor(Request $request)
	{
		$qsearch = trim($request->input('term', ''));

		$query = Individual::query()
			->whereIn('type', ['customers', 'vendors'])
			->where('status', 'Active');

		if ($qsearch !== '') {
			$query->where(function ($query) use ($qsearch) {
				$query->where('name', 'like', '%'.$qsearch.'%');
				$query->orWhere('company_name', 'like', '%'.$qsearch.'%');
				$query->orWhere('gstin', 'like', '%'.$qsearch.'%');
				$query->orWhere('whatsapp', 'like', '%'.$qsearch.'%');
				$query->orWhere('email', 'like', '%'.$qsearch.'%');
			});
		}

		return response()->json($query->orderBy('name')->limit(10)->get(['id', 'name', 'gstin', 'type']));
	}
	
    public function fabric_list_item(Request $request)
    {
        $qsearch = trim($request->input('term', ''));
        $itemTypeId = $request->input('item_type_id', '');

        $query = Item::with('unitType');
        $query->where('status', 'Active');

        if ($itemTypeId != '' && $itemTypeId !== 'all') {
            $query->where('item_type_id', $itemTypeId);
        } elseif ($itemTypeId !== 'all') {
			$query->where('item_type_id', '=', '8');
		}

        if ($qsearch != '') {
            $query->where(function ($query) use ($qsearch) {
                $query->where('item_name', 'like', '%'.$qsearch.'%');
                $query->orWhere('hsncode', 'like', '%'.$qsearch.'%');
                $query->orWhere('internal_item_name', 'like', '%'.$qsearch.'%');
                $query->orWhere('item_code', 'like', '%'.$qsearch.'%');
            });
        }

        $items = $query->orderBy('item_id', 'asc')->limit(10)->get();

        return response()->json($items);
    }
	
	public function list_warehouse_item_type(Request $request)
	{
		// ->where('is_jobwork', '=', '0')
		$qsearch 	= trim($request->term);
		$type 		= $request->type;
		$query = Item::where('status', '=', 'Active')
			->where(function ($query) use ($qsearch) {
				$query->whereRaw("CONCAT(COALESCE(item_name, ''), COALESCE(hsncode, ''), COALESCE(internal_item_name, ''), COALESCE(item_code, '')) LIKE ?", ['%' . $qsearch . '%']);
			})
			->with('UnitType')
			->with('ItemType')
			->limit(10);
		if ($type == 3 || $type == 4 || $type == 2) 
		{
			$dataI = $query->whereIn('item_type_id', [3, 8])->get();
		} else if($type == 1 || $type == 2){
			$dataI = $query->whereIn('item_type_id', [1, 2])->get();
		} else {
			$dataI = $query->where('item_type_id', '=', $type)->get();
		}
		return response()->json($dataI);
	}

	public function list_warehouse_compartment(Request $request)
	{
		$qsearch = trim($request->input('term', ''));

		$query = WarehouseCompartment::with('warehouse')
			->where('status', '=', 'Active');

		if ($qsearch != '') {
			$query->where('compartment_name', 'like', '%'.$qsearch.'%');
		}

		return response()->json($query->orderBy('compartment_name')->limit(10)->get());
	}


    public function list_individual(Request $request)
    {
        $qsearch = trim($request->input('term', ''));
        $type = $request->input('type', '');

        $query = Individual::query();
        $query->where('status', 'Active');

        if ($type != '') {
            $query->where('type', $type);
        }

        if ($qsearch != '') {
            $query->where(function ($query) use ($qsearch) {
                $query->where('name', 'like', '%'.$qsearch.'%');
                $query->orWhere('company_name', 'like', '%'.$qsearch.'%');
                $query->orWhere('phone', 'like', '%'.$qsearch.'%');
                $query->orWhere('whatsapp', 'like', '%'.$qsearch.'%');
                $query->orWhere('gstin', 'like', '%'.$qsearch.'%');
            });
        }

        $individuals = $query->orderBy('id', 'asc')->limit(10)->get();

        return response()->json($individuals);
    }

    public function list_coating(Request $request)
    {
        $qsearch = trim($request->input('term', ''));

        $query = Coting::query();
        $query->where('status', 'Active');

        if ($qsearch != '') {
            $query->where(function ($query) use ($qsearch) {
                $query->where('name', 'like', '%'.$qsearch.'%');
                $query->orWhere('code', 'like', '%'.$qsearch.'%');
            });
        }

        $coatings = $query->orderBy('id', 'asc')->limit(10)->get();

        return response()->json($coatings);
    }

    public function customer_addresses(Request $request)
    {
        $individualId = $request->input('individual_id', '');

        $billingRows = IndividualAddress::where('individual_id', $individualId)
            ->where('address_type', 'b')
            ->where('status', 'Active')
            ->orderBy('ind_add_id', 'asc')
            ->get();

        $shippingRows = IndividualAddress::where('individual_id', $individualId)
            ->where('address_type', 's')
            ->where('status', 'Active')
            ->orderBy('ind_add_id', 'asc')
            ->get();

        $billingAddresses = [];
        foreach ($billingRows as $row) {
            $addressText = $row->address_1;
            $addressText .= ', '.$row->address_2;
            $addressText .= ', '.$row->city;
            $addressText .= ' '.$row->zip_code;

            $billingAddresses[] = [
                'id' => $row->ind_add_id,
                'address' => $addressText,
                'default_address' => $row->default_address,
            ];
        }

        $shippingAddresses = [];
        foreach ($shippingRows as $row) {
            $addressText = $row->address_1;
            $addressText .= ', '.$row->address_2;
            $addressText .= ', '.$row->city;
            $addressText .= ' '.$row->zip_code;

            $shippingAddresses[] = [
                'id' => $row->ind_add_id,
                'address' => $addressText,
                'default_address' => $row->default_address,
            ];
        }

        if (count($shippingAddresses) == 0) {
            $shippingAddresses = $billingAddresses;
        }

        return response()->json([
            'billing_addresses' => $billingAddresses,
            'shipping_addresses' => $shippingAddresses,
        ]);
    }

    public function individual_addresses(Request $request)
    {
        $individualId = $request->input('individual_id', '');

        $addresses = IndividualAddress::where('individual_id', $individualId)
            ->where('status', 'Active')
            ->orderByDesc('default_address')
            ->orderBy('ind_add_id', 'asc')
            ->get();

        $individualAddresses = [];
        foreach ($addresses as $address) {
            $addressText = collect([$address->address_1, $address->address_2, $address->city, $address->zip_code])
                ->filter()
                ->implode(', ');

            $individualAddresses[] = [
                'id' => $address->ind_add_id,
                'address_type' => $address->address_type,
                'address' => $addressText,
                'state_id' => $address->state_id,
                'default_address' => (bool) $address->default_address,
            ];
        }

        return response()->json($individualAddresses);
    }

    public static function getIndividualName($Id)
    {
        static $cache = [];
        $key = (string) $Id;

        if (! array_key_exists($key, $cache)) {
            $cache[$key] = Individual::where('id', $Id)->value('name') ?: '';
        }

        return $cache[$key];
    }

    public static function getVendorName($Id)
    {
        static $cache = [];
        $key = (string) $Id;

        if (! array_key_exists($key, $cache)) {
            $cache[$key] = Individual::where('id', $Id)->where('type', 'vendors')->value('name') ?: 'N/A';
        }

        return $cache[$key];
    }

    public static function getWarehouseName($Id)
    {
        static $cache = [];
        $key = (string) $Id;

        if (! array_key_exists($key, $cache)) {
            $cache[$key] = Warehouse::where('id', $Id)->value('warehouse_name') ?: '';
        }

        return $cache[$key];
    }
	
	 
	public static function getWarehouseCompartmentName($Id)
	{
		$data = WarehouseCompartment::where('id', $Id)->first();
		if (!empty($data->compartment_name)) return $data->compartment_name;
		else return '';
	}

	public static function getItemName($id)
	{
		if (empty($id)) {
			return '';
		}

		return Item::where('item_id', $id)->where('status', 'Active')->value('item_name') ?? '';
	}

	public static function getItemInternalName($id)
	{
		if (empty($id)) {
			return '';
		}

		return Item::where('item_id', $id)->where('status', 'Active')->value('internal_item_name') ?? '';
	}
	
	
	
	public static function getTotaCreatedMtr($SaleOrditemId)
	{
		// Build query without executing
		$query = WorkOrderItem::where('sale_order_item_id', $SaleOrditemId)
			->whereHas('WorkOrder', function ($q) {
				$q->whereNull('parent_work_order_id');
			});

		// Debug SQL
		// $sql = $query->toSql();
		// $bindings = $query->getBindings();
		// $fullSql = vsprintf(str_replace('?', "'%s'", $sql), $bindings);
		// echo $fullSql; // Prints full SQL with bindings

		// Execute and return sum
		return $query->sum('meter');
	}
	
	public function find_saleDyeingColor(Request $request)
	{ 
		$qsearch 		= trim($request->term);
		$item_search 	= trim($request->item_search);

		$dataI = SaleOrderItem::where('item_name', 'like', '%' . $item_search . '%')->where('dyeing_color', 'like', '%' . $qsearch . '%') 
			->get()
			->groupBy('dyeing_color')
			->map(function ($items, $dyeingColor) {
				return [
					'dyeing_color' => $dyeingColor,
					'items' => $items
				];
			})
			->values();

		return response()->json($dataI);
	}
	
	public static function getWarehouseAvailableItemStockBeam($itemId, $itemTypeId)
	{
		$query = WarehouseItemStock::select(DB::raw('*'), DB::raw('SUM(insp_bal_quan_size) AS total_item_qty'))
		  ->where('item_id', $itemId)
		  ->where('item_type_id', $itemTypeId)
		  ->where('entry_type', 'IN')
		  ->where('is_allotted_stock', 'No')
		  ->where('status', 'Active');

		$dataWIS = $query->first();
		return $dataWIS ? $dataWIS->total_item_qty : 0;
	}

	public static function checkWarehouseBalanceItemStock($itemId, $itemtypeId, $dyeingColor = null, $coatingPvc = null)
	{
		if ($itemtypeId == '8') {
			$itemtypeId = 3;
		}

		$query = WarehouseBalanceItem::where('item_id', $itemId)
			->where('balance_status', '1');

		if (empty($dyeingColor) && empty($coatingPvc)) {
			$query->where('item_type_id', $itemtypeId);

			$query->where(function ($q) {
				$q->where('dyeing_color', '0')->orWhereNull('dyeing_color');
			});

			$query->where(function ($q) {
				$q->where('coating_type', '0')->orWhereNull('coating_type');
			});
		} elseif (!empty($dyeingColor) && empty($coatingPvc)) {
			$query->where('item_type_id', $itemtypeId)
				->where('dyeing_color', $dyeingColor)
				->where('item_qty', '>', 1);
		} elseif (!empty($dyeingColor) && !empty($coatingPvc)) {
			$query->where('item_type_id', $itemtypeId)
				->where('dyeing_color', $dyeingColor)
				->where('coating_type', $coatingPvc);
		}

		return $query->sum('item_qty') ?: 0;
	}
	
	public static function check_warehouse_greige_type_balance($itemId, $itemtypeId)
	{
		return static::checkWarehouseBalanceItemStock($itemId, $itemtypeId);
	}
	
	public static function getWorkOrderGreigeTypeBalance($itemId, $itemtypeId)
	{
		if ($itemtypeId == '8') {
			$itemtypeId = 3;
		} 
		$result = WorkOrder::select(DB::raw('SUM(meter) as tot'))
			->where('item_type_id', '=', $itemtypeId)
			->where('item_id', '=', $itemId)	
			->where('status', '=', 'Active')			 
			->whereNull('work_req_send_by')
			->groupBy('item_id', 'item_type_id')
			->first();

		if (!empty($result->tot)) {
			return $result->tot; // Return just the numeric value
		} else {
			return 0; // Return 0 if there are no results
		}
	}
 
	public static function check_warehouse_dyeing_type_balance($itemId, $itemtypeId, $dyeingColor)
	{
		return static::checkWarehouseBalanceItemStock($itemId, $itemtypeId, $dyeingColor) . ' Meter';
	}
	
	public static function check_warehouse_coating_type_balance($itemId, $itemtypeId, $dyeingColor, $coatingPvc)
	{
		return static::checkWarehouseBalanceItemStock($itemId, $itemtypeId, $dyeingColor, $coatingPvc) . ' Meter';
	}	
	
	
	
	public static function getChildLotNumber($woId)
	{
		static $cache = [];
		$key = (string) $woId;

		if (!array_key_exists($key, $cache)) {
			if (empty($woId) || !Schema::hasTable('warehouse_item_stocks')) {
				$cache[$key] = collect();
			} else {
				$cache[$key] = WarehouseItemStock::where('work_order_id', $woId)
					->where('status', 'Active')
					->whereNotNull('dyeing_lot_number')
					->select('id', 'dyeing_lot_number')
					->groupBy('id', 'dyeing_lot_number')
					->get();
			}
		}

		return $cache[$key];
	}
	
	public static function WorkProcessItemAllotedStock($id)
	{
		static $cache = [];
		$key = (string) $id;

		if (array_key_exists($key, $cache)) {
			return $cache[$key];
		}

		$dataWk  = WorkOrder::where('id', '=', $id)->first(); 
		if (empty($dataWk)) {
			return $cache[$key] = [];
		}

		$itemTypeId = $dataWk->item_type_id;	
		$dataWPR = Schema::hasTable('work_process_requirements')
			? DB::table('work_process_requirements')
				->select('work_order_id', 'item_id', 'item_type_id', 'dyeing_color', DB::raw('SUM(quantity) as tot'), DB::raw('SUM(alloted_quantity) as alttot'))
				->where('work_order_id', '=', $id)
				->where('status', '=', 'Active')
				->where('item_type_id', '=', $itemTypeId)
				->where('is_accept', '=', '1')
				->groupBy('work_order_id', 'item_id', 'item_type_id', 'dyeing_color')
				->get()
			: collect();
			

		$tableData  = [];
		$RequestQTY = 0;
		foreach ($dataWPR as $row) {
			$itemId = $row->item_id;
			$itemTypeId = $row->item_type_id;

			if (!empty($row->dyeing_color)) {
				$dyeingColor = $row->dyeing_color;
				$Itembalance = static::checkWarehouseDyeingBalance($itemId, $itemTypeId, $dyeingColor);
			} else {
				$Itembalance = static::checkWarehouseWorkProcessItemTypeBalance($itemId, $itemTypeId);
			}

			$iTName 	 = static::getItemTypeName($itemTypeId);
			$RequestQTY  = $row->tot;
			$AllotedQTY  = $row->alttot; 
			$allowedKgItemTypes = ['1', '2', '7', '9'];
			$unitTName 	 = in_array($itemTypeId, $allowedKgItemTypes) ? 'Kg' : 'Meter';
			$tableData[] = [
				'ItemName' => static::getItemName($itemId),
				'iTName' => $iTName,
				'Itembalance' => $Itembalance,
				'RequestQTY' => $RequestQTY,
				'AllotedQTY' => $AllotedQTY,
				'unitTName' => $unitTName,
			];
		}
		return $cache[$key] = $tableData;
	}
  
	public static function checkWarehouseWorkProcessItemTypeBalance($itemId, $itemTypeId, $unitTypeId=null)
	{
		static $cache = [];
		$key = $itemId . '|' . $itemTypeId . '|' . $unitTypeId;

		if (!array_key_exists($key, $cache)) {
			$cache[$key] = WarehouseBalanceItem::where('item_type_id', '=', $itemTypeId)
				// ->where('unit_type_id', '=', $unitTypeId)
				->where('item_id', '=', $itemId)
				->where('balance_status', '=', 1)
				->sum('item_qty') ?: 0;
		}

		return $cache[$key];
	}
	
	public static function checkWarehouseDyeingBalance($itemId, $itemtypeId, $dyeingColor)
	{
		static $cache = [];
		$key = $itemId . '|' . $itemtypeId . '|' . $dyeingColor;

		if (!array_key_exists($key, $cache)) {
			$cache[$key] = WarehouseBalanceItem::where('item_type_id', '=', $itemtypeId)
				->where('item_id', '=', $itemId)
				->where('dyeing_color', '=', $dyeingColor)
				->where('balance_status', '=', '1')
				->sum('item_qty') ?: 0;
		}

		return $cache[$key];
	}

	public static function getTotalChildWork($woId)
	{
		static $cache = [];
		$key = (string) $woId;

		if (!array_key_exists($key, $cache)) {
			$cache[$key] = WorkOrder::where('parent_work_order_id', $woId)->where('status', '=', 'Active')->count();
		}

		return $cache[$key];
	}
  
	public static function getJobWorkOrd($sonumId)
	{ 
		if (empty($sonumId) || !Schema::hasTable('job_works')) {
			return null;
		}

		return DB::table('job_works')
			->where('id', $sonumId)
			->where('status', '!=', 'Deleted')
			->first();
	}
  
	public static function getProcessTypeName($id)
	{
	 
		$arrayAProcess = array(
		  '1' => array(
			'input'  => array('Yarn'),
			'output' => 'Beam',
			'process' => 'Warping',
			'id' => '1',
			'shortcode' => 'W',
			'unit' => 'Quantity',
		  ),
		  '2' => array(
			'input'  => array('Beam', 'Water'),
			'output' => 'Greige',
			'process' => 'Weaving',
			'id' => '2',
			'shortcode' => 'V',
			'unit' => 'Meter',
		  ),
		  '3' => array(
			'input'  => array('Greige', 'Chemical', 'Color'),
			'output' => 'Dyed',
			'process' => 'Dyeing',
			'id' => '3',
			'shortcode' => 'D',
			'unit' => 'Meter',
		  ),
		  '4' => array(
			'input'  => array('Dyed', 'Chemical'),
			'output' => 'Coated',
			'process' => 'Coating',
			'id' => '4',
			'shortcode' => 'C',
			'unit' => 'Meter',
		  ),
		  '5' => array(
			'input'  => array('Tape', 'Box'),
			'output' => 'Dispatch',
			'process' => 'Packaging',
			'id' => '4',
			'shortcode' => 'C',
			'unit' => 'Meter',
		  ),
	);


	return $arrayAProcess[$id];
	}


	public static function getProcessName($id)
	{	 
		$arrayAProcess = array(
			'warping' => array(
			'input'  => array('Yarn'),
			'output' => 'Beam',
			'id' => '1',
			'shortcode' => 'B',
		),
		'weaving' => array(
		'input'  => array('Beam', 'Water'),
		'output' => 'Greige',
		'id' => '2',
		'shortcode' => 'W',
		),
		'Dyeing' => array(
		'input'  => array('Greige', 'Chemical', 'Color'),
		'output' => 'Dyed',
		'id' => '3',
		'shortcode' => 'D',
		),
		'Coating' => array(
		'input'  => array('Dyed', 'Chemical'),
		'output' => 'Coated',
		'id' => '4',
		'shortcode' => 'C',
		),
		);

		$dataI = ProcessItem::where('id', '=', $id)->where('status', '=', 'Active')->first();

		return $dataI->process_name ?? '';
	}

	public static function getItemTypeName($itemTypeId)
	{
		 
		static $cache = [];
		$key = (string) $itemTypeId;

		if (!array_key_exists($key, $cache)) {
		$cache[$key] = ItemType::where('item_type_id', $itemTypeId)->value('item_type_name') ?: '';
		}

		return $cache[$key];
	}

	public static function getUnitTypeName($unitTypeId)
	{
		if (empty($unitTypeId)) {
			return '';
		}

		return UnitType::where('unit_type_id', $unitTypeId)->value('unit_type_name') ?: '';
	}

	public static function getWareHouseNameByItemStockId($stockId)
	{
		$stock = WarehouseItemStock::with(['Warehouse', 'WarehouseCompartment'])->find($stockId);

		return [
			'Warehouse' => $stock->Warehouse->warehouse_name ?? '',
			'WarehouseCompartment' => $stock->WarehouseCompartment->warehousename ?? '',
		];
	}

	public static function getWareHouseNameByItemStock($itemId, $processId = null)
	{
		$stock = WarehouseItemStock::with(['Warehouse', 'WarehouseCompartment'])
			->where('item_id', $itemId)
			->where('is_allotted_stock', 'No')
			->where('status', 'Active')
			->orderByDesc('id')
			->first();

		return [
			'Warehouse' => $stock->Warehouse->warehouse_name ?? '',
			'WarehouseCompartment' => $stock->WarehouseCompartment->warehousename ?? '',
		];
	}

	public static function getBalanceStockById($balanceItemId)
	{
		return WarehouseBalanceItem::whereKey($balanceItemId)->value('item_qty') ?: 0;
	}

	public static function getWarehouseItemStockById($stockId)
	{
		return WarehouseItemStock::whereKey($stockId)->value('insp_bal_quan_size') ?: 0;
	}

	public static function getTotalAvailableItemStock($itemId, $itemTypeId)
	{
		return WarehouseItemStock::where('item_id', $itemId)
			->where('item_type_id', $itemTypeId)
			->where('is_allotted_stock', 'No')
			->where('status', 'Active')
			->sum('insp_bal_quan_size');
	}

	public static function getTotalAvailableDyiengItemStock($itemId, $itemTypeId, $dyeingColor)
	{
		return WarehouseItemStock::where('item_id', $itemId)
			->where('item_type_id', $itemTypeId)
			->where('dyeing_color', $dyeingColor)
			->where('is_allotted_stock', 'No')
			->where('status', 'Active')
			->sum('insp_bal_quan_size');
	}
	
	public function list_master_color(Request $request)
	{
		$qsearch = trim($request->term);
		$indId   = trim($request->id);

		// Common Query
		$query = Colour::query()->where('status', 'Active'); 
		if (!empty($qsearch)) 
		{
			$search = trim($qsearch); 
			$query->where('name', 'LIKE', '%' . $search . '%'); 
			$query->orderByRaw(
				"CASE
					WHEN name = ? THEN 1
					WHEN name LIKE ? THEN 2
					WHEN name LIKE ? THEN 3
					ELSE 4
				END",
				[$search, $search . '%', '%' . $search . '%']
			);
		} 
		 
		$query->orderByDesc('id'); 
		 
		$dataI = $query->limit(50)->get();

		return response()->json($dataI);
	}
	
	public static function getWarehouseItemTypeBalanceId($itemId, $itemTypeId, $unitTypeId)
	{
		$query = WarehouseBalanceItem::where('item_type_id', $itemTypeId)
			->where('unit_type_id', $unitTypeId)
			->where('item_id', $itemId)
			->where('balance_status', 1)
			->selectRaw('COALESCE(SUM(item_qty), 0) as tot, MIN(id) as id');

		/*
		$sql = $query->toSql();
		$bindings = $query->getBindings();
		$fullSql = vsprintf(str_replace('?', "'%s'", $sql), $bindings);
		echo "Executing SQL: " . $fullSql . "<br>";
		exit;
		*/

		$result = $query->first();

		return !empty($result->id) ? $result : 0;
	}
	
	public static function getWarehouseDyeingTypeBalanceId($itemId, $itemtypeId, $dyeingColor)
	{
		$itemtypeId = 4;

		$query = WarehouseBalanceItem::where('item_type_id', $itemtypeId)
			->where('item_id', $itemId)
			->where('dyeing_color', $dyeingColor)
			->where('item_qty', '>', 1)
			->where('balance_status', '1')
			->selectRaw('COALESCE(SUM(item_qty), 0) as tot, MIN(id) as id');

		/*
		$sql = $query->toSql();
		$bindings = $query->getBindings();
		$fullSql = vsprintf(str_replace('?', "'%s'", $sql), $bindings);
		echo "Executing SQL: " . $fullSql . "<br>";
		exit;
		*/

		$result = $query->first();

		return !empty($result->id) ? $result : 0;
	}

	public static function getWorkProcessRequirementWisIds($WorkOrdId)
	{
		$dataItems = WorkProcessRequirement::where('work_order_id', '=', $WorkOrdId)
			->where('status', '=', '1')
			->where('is_accept', '=', '0')
			->get();
		$commonDeptReqIds = [];
		foreach ($dataItems as $item) 
		{
			$deptReqIds = explode(',', $item->dept_req_ids); 		
			$commonDeptReqIds = array_merge($commonDeptReqIds, $deptReqIds);
		} 		
		$uniqueDeptReqIds = array_unique($commonDeptReqIds);
		return $uniqueDeptReqIds = implode(',', $uniqueDeptReqIds); 
	}
		
	public static function getWarehouseAvailableDyingItemStockArray($itemId, $itemTypeId, $dyingColor)
	{
		$query = WarehouseItemStock::where('item_id', $itemId)
		  ->where('item_type_id', $itemTypeId)
		  ->where('dyeing_color', $dyingColor)
		  ->where('entry_type', 'IN')
		  ->where('is_allotted_stock', 'No')
		  ->where('status', 1);

		/*
			$sql = $query->toSql();
			$bindings = $query->getBindings();
			$fullSql = vsprintf(str_replace(['?'], ['\'%s\''], $sql), $bindings); 
			echo $fullSql;
			 */
 
		$dataWIS = $query->with('FabricFaultReason')->get();
		return $dataWIS;
	}
	
	
	public static function WorkProcessItemBalanceById($id, $isAccept)
	{
		$dataWPR = WorkProcessRequirement::where('id', $id)
			->whereNull('is_pro_acc_by_warehouse')
			->where('status', 'Active')
			->where('is_accept', $isAccept)
			->with('UnitType')
			->first();

		// Check if dataWPR exists
		if (!$dataWPR) {
			return "Work Process Requirement not found.";
		}
		
		$reqFabricType 	= $dataWPR->req_fabric_type;
		$itemId 		= $dataWPR->item_id;
		$itemTypeId 	= $dataWPR->item_type_id;
		
		if ($reqFabricType =='2') 
		{ 
			$itemTypeId 	= 4;
		    $Itembalance    = static::checkItemDyeingBalance($itemId, $itemTypeId);			
		}
		
		if ($reqFabricType =='1') 
		{
			if (!empty($dataWPR->dyeing_color)) 
			{
				$dyeingColor = $dataWPR->dyeing_color;
				$Itembalance = static::checkWarehouseDyeingBalance($itemId, $itemTypeId, $dyeingColor);
			} else {
				$Itembalance = static::checkWarehouseWorkProcessItemTypeBalance($itemId, $itemTypeId);
			}
		}
		// Check if UnitType exists
		if (!$dataWPR->UnitType) {
			return "Unit type not found.";
		}

		return $Itembalance . ' ' . $dataWPR->UnitType->unit_type_name;
	}
	
	 
	public static function checkItemDyeingBalance($itemId, $itemtypeId)
	{
		$query = WarehouseBalanceItem::select(DB::raw('SUM(item_qty) as tot'))
			->where('item_type_id', '=', $itemtypeId)
			->where('item_id', '=', $itemId)
			->where('dyeing_color', 'NOT LIKE', 'BLACK')
			->where('balance_status', '=', '1');

		/*  $sql = $query->toSql();
		$bindings = $query->getBindings();
		$fullSql = vsprintf(str_replace(['?'], ['\'%s\''], $sql), $bindings); 
		echo $fullSql; */
		  
		 
		$result = $query->first();
		return !empty($result['tot']) ? $result['tot'] : '0';
	}

	 
 


}
 
