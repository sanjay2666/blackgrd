<?php

namespace App\Http\Controllers;

use App\Enums\InventoryAllocationStatus;
use App\Enums\InventoryMovementStatus;
use App\Models\PackagingOrder;
use App\Models\PackagingOrderItem;
use App\Models\PackagingRollAllocation;
use App\Models\PackagingType;
use App\Models\SaleOrderItem;
use App\Models\Warehouse;
use App\Models\WarehouseBalanceItem;
use App\Models\WarehouseItem;
use App\Models\WarehouseItemStock;
use App\Models\WarehouseOutItem;
use App\Services\CurrentOrganizationContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;

class PackagingController extends Controller
{
    public function index(Request $request)
    {
        $context = $request->attributes->get(CurrentOrganizationContext::class);
        abort_unless($context instanceof CurrentOrganizationContext, 403);

        $companyId = $context->companyId();
        $worklist = SaleOrderItem::with(['saleOrder.customer', 'item', 'itemType'])
            ->where('company_id', $companyId)
            ->where('status', 'Active')
            ->where('is_packaging_done', '1')
            ->orderByDesc('in_packaging_send_date')
            ->orderByDesc('id')
            ->get();
        $packagingItems = PackagingOrderItem::with('packagingOrder')
            ->where('company_id', $companyId)
            ->whereIn('sale_order_item_id', $worklist->pluck('id'))
            ->where('status', 'Active')
            ->get()
            ->groupBy('sale_order_item_id');

        return view('frontend.packaging.index', compact('worklist', 'packagingItems'));
    }

    public function create(Request $request, int $saleOrderItem)
    {
        $context = $request->attributes->get(CurrentOrganizationContext::class);
        abort_unless($context instanceof CurrentOrganizationContext, 403);

        $companyId = $context->companyId();
        $saleOrderItem = SaleOrderItem::with(['saleOrder.customer', 'item', 'itemType', 'unitType'])
            ->where('company_id', $companyId)
            ->where('status', 'Active')
            ->where('is_packaging_done', '1')
            ->findOrFail($saleOrderItem);
        $warehouseId = $request->filled('warehouse_id') ? (int) $request->warehouse_id : null;
        $stocks = WarehouseItemStock::with(['Warehouse', 'WarehouseCompartment'])
            ->where('company_id', $companyId)
            ->where('status', 'Active')
            ->where('entry_type', 'IN')
            ->where('item_id', $saleOrderItem->item_id)
            ->where('item_type_id', $saleOrderItem->item_type_id)
            ->where('dyeing_color', $saleOrderItem->dyeing_color)
            ->where('coating_type', $saleOrderItem->coating_type)
            ->where('print_job', $saleOrderItem->print_job)
            ->where('extra_job', $saleOrderItem->extra_job)
            ->where('insp_bal_quan_size', '>', 0)
            ->when($warehouseId, fn ($query) => $query->where('warehouse_id', $warehouseId))
            ->orderBy('warehouse_id')
            ->orderBy('id')
            ->get();
        $reserved = PackagingRollAllocation::where('company_id', $companyId)
            ->whereIn('warehouse_item_stock_id', $stocks->pluck('id'))
            ->where('status', 'Active')
            ->where('allocation_status', 'proposed')
            ->selectRaw('warehouse_item_stock_id, SUM(allocated_quantity) as reserved_quantity')
            ->groupBy('warehouse_item_stock_id')
            ->pluck('reserved_quantity', 'warehouse_item_stock_id');
        $stocks = $stocks->map(function (WarehouseItemStock $stock) use ($reserved) {
            $stock->packaging_available_quantity = max(0, round((float) $stock->insp_bal_quan_size - (float) ($reserved[$stock->id] ?? 0), 2));

            return $stock;
        })->filter(fn (WarehouseItemStock $stock) => $stock->packaging_available_quantity > 0);
        $alreadyAllocated = (float) PackagingOrderItem::where('company_id', $companyId)
            ->where('sale_order_item_id', $saleOrderItem->id)
            ->where('status', 'Active')
            ->whereHas('packagingOrder', fn ($query) => $query->where('packaging_status', '!=', 'cancelled')->where('status', 'Active'))
            ->sum('allocated_quantity');
        $warehouses = Warehouse::where('company_id', $companyId)->where('status', 'Active')->orderBy('warehouse_name')->get();
        $packagingTypes = PackagingType::where('company_id', $companyId)->where('status', 'Active')->orderBy('name')->get();
        $remainingQuantity = max(0, round((float) $saleOrderItem->meter - $alreadyAllocated, 2));

        return view('frontend.packaging.create', compact('saleOrderItem', 'stocks', 'warehouses', 'warehouseId', 'packagingTypes', 'remainingQuantity'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'sale_order_item_id' => 'required|integer',
            'packaging_type_id' => 'required|integer',
            'warehouse_item_stock_ids' => 'required|array|min:1',
            'warehouse_item_stock_ids.*' => 'required|integer|distinct',
            'quantities' => 'required|array|min:1',
            'quantities.*' => 'required|numeric|gt:0',
            'remarks' => 'nullable|string|max:1000',
        ]);
        if ($validator->fails()) {
            Session::put('message', $validator->errors()->first());
            Session::put('messageClass', 'errorClass');

            return back()->withInput();
        }

        DB::beginTransaction();
        try {
            $context = $request->attributes->get(CurrentOrganizationContext::class);
            if (! $context instanceof CurrentOrganizationContext) {
                throw new \Exception('An active organization context is required.');
            }
            $companyId = $context->companyId();
            $stockIds = array_map('intval', array_values($request->warehouse_item_stock_ids));
            $quantities = array_map(fn ($quantity) => round((float) $quantity, 2), array_values($request->quantities));
            if (count($stockIds) !== count($quantities)) {
                throw new \Exception('Each selected Roll/Taka must have one packaging quantity.');
            }
            $saleOrderItem = SaleOrderItem::with('saleOrder')->where('company_id', $companyId)->where('status', 'Active')
                ->where('is_packaging_done', '1')->whereKey((int) $request->sale_order_item_id)->lockForUpdate()->first();
            if (! $saleOrderItem) {
                throw new \Exception('This sale-order item is not available for packaging.');
            }
            $packagingType = PackagingType::where('company_id', $companyId)->where('status', 'Active')
                ->whereKey((int) $request->packaging_type_id)->lockForUpdate()->first();
            if (! $packagingType) {
                throw new \Exception('Selected packaging type is not available.');
            }
            $existingItems = PackagingOrderItem::where('company_id', $companyId)->where('sale_order_item_id', $saleOrderItem->id)
                ->where('status', 'Active')->lockForUpdate()->get();
            $alreadyAllocated = $existingItems->filter(function (PackagingOrderItem $item) {
                return $item->packagingOrder && $item->packagingOrder->packaging_status !== 'cancelled';
            })->sum('allocated_quantity');
            $requestedTotal = round(array_sum($quantities), 2);
            $remainingQuantity = round((float) $saleOrderItem->meter - (float) $alreadyAllocated, 2);
            if ($requestedTotal > $remainingQuantity + 0.0001) {
                throw new \Exception('Requested packaging quantity exceeds the sale-order item remaining quantity.');
            }
            $sortedStockIds = $stockIds;
            sort($sortedStockIds);
            $stocks = WarehouseItemStock::where('company_id', $companyId)->where('status', 'Active')->where('entry_type', 'IN')
                ->whereIn('id', $sortedStockIds)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            if ($stocks->count() !== count($sortedStockIds)) {
                throw new \Exception('One or more selected Roll/Taka records are unavailable.');
            }
            foreach ($stockIds as $index => $stockId) {
                $stock = $stocks->get($stockId);
                $quantity = $quantities[$index];
                if ((int) $stock->item_id !== (int) $saleOrderItem->item_id || (int) $stock->item_type_id !== (int) $saleOrderItem->item_type_id
                    || (string) $stock->dyeing_color !== (string) $saleOrderItem->dyeing_color || (string) $stock->coating_type !== (string) $saleOrderItem->coating_type
                    || (string) $stock->print_job !== (string) $saleOrderItem->print_job || (string) $stock->extra_job !== (string) $saleOrderItem->extra_job) {
                    throw new \Exception('Selected Roll/Taka does not match the sale-order item material specification.');
                }
                $reserved = PackagingRollAllocation::where('company_id', $companyId)->where('warehouse_item_stock_id', $stockId)
                    ->where('status', 'Active')->where('allocation_status', 'proposed')->lockForUpdate()->sum('allocated_quantity');
                $available = round((float) $stock->insp_bal_quan_size - (float) $reserved, 2);
                if ($available < 0 || $quantity > $available + 0.0001) {
                    throw new \Exception("Requested packaging quantity exceeds available stock for Roll/Taka {$stockId}.");
                }
            }
            $user = Auth::user();
            $individualId = $user->individual_id ?? Auth::id() ?? 0;
            $packagingOrder = PackagingOrder::create([
                'company_id' => $companyId,
                'customer_id' => $saleOrderItem->saleOrder?->customer_id,
                'packaging_status' => 'draft',
                'allocated_quantity' => $requestedTotal,
                'remaining_quantity' => $requestedTotal,
                'remarks' => $request->remarks,
                'created_by' => $individualId,
                'created_at' => now(),
                'updated_at' => now(),
                'status' => 'Active',
            ]);
            $packagingOrderItem = PackagingOrderItem::create([
                'company_id' => $companyId,
                'packaging_order_id' => $packagingOrder->id,
                'sale_order_id' => $saleOrderItem->sale_order_id,
                'sale_order_item_id' => $saleOrderItem->id,
                'item_id' => $saleOrderItem->item_id,
                'item_type_id' => $saleOrderItem->item_type_id,
                'unit_type_id' => $saleOrderItem->unit_type_id,
                'packaging_type_id' => $packagingType->id,
                'allocated_quantity' => $requestedTotal,
                'remaining_quantity' => $requestedTotal,
                'created_at' => now(),
                'updated_at' => now(),
                'status' => 'Active',
            ]);
            foreach ($stockIds as $index => $stockId) {
                $stock = $stocks->get($stockId);
                PackagingRollAllocation::create([
                    'company_id' => $companyId,
                    'packaging_order_id' => $packagingOrder->id,
                    'packaging_order_item_id' => $packagingOrderItem->id,
                    'warehouse_item_stock_id' => $stock->id,
                    'packet_number' => $stock->packet_number,
                    'insp_taka_number' => $stock->insp_taka_number,
                    'dyeing_lot_number' => $stock->dyeing_lot_number,
                    'allocated_quantity' => $quantities[$index],
                    'remaining_quantity' => $quantities[$index],
                    'allocation_status' => 'proposed',
                    'created_at' => now(),
                    'updated_at' => now(),
                    'status' => 'Active',
                ]);
            }
            DB::commit();
            Session::put('message', 'Packaging order created. Warehouse acceptance is required before stock is issued.');
            Session::put('messageClass', 'successClass');

            return redirect()->route('packaging.show', $packagingOrder->id);
        } catch (\Exception $e) {
            DB::rollBack();
            report($e);
            Session::put('message', 'Packaging order could not be created. '.$e->getMessage());
            Session::put('messageClass', 'errorClass');

            return back()->withInput();
        }
    }

    public function show(Request $request, int $packagingOrder)
    {
        $context = $request->attributes->get(CurrentOrganizationContext::class);
        abort_unless($context instanceof CurrentOrganizationContext, 403);

        $packagingOrder = PackagingOrder::with([
            'customer',
            'items.saleOrderItem.saleOrder',
            'items.saleOrderItem.item',
            'items.packagingType',
            'items.rollAllocations.warehouseItemStock.Warehouse',
            'items.rollAllocations.warehouseItemStock.WarehouseCompartment',
            'items.rollAllocations.warehouseOutItem',
        ])->where('company_id', $context->companyId())->where('status', 'Active')->findOrFail($packagingOrder);

        return view('frontend.packaging.show', compact('packagingOrder'));
    }

    public function accept(Request $request, int $packagingOrder)
    {
        DB::beginTransaction();
        try {
            $context = $request->attributes->get(CurrentOrganizationContext::class);
            if (! $context instanceof CurrentOrganizationContext) {
                throw new \Exception('An active organization context is required.');
            }
            $companyId = $context->companyId();
            $packagingOrder = PackagingOrder::where('company_id', $companyId)->where('status', 'Active')->whereKey($packagingOrder)->lockForUpdate()->first();
            if (! $packagingOrder || $packagingOrder->packaging_status !== 'draft') {
                throw new \Exception('Only a draft packaging order can be accepted once.');
            }
            $items = PackagingOrderItem::where('company_id', $companyId)->where('packaging_order_id', $packagingOrder->id)
                ->where('status', 'Active')->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $allocations = PackagingRollAllocation::where('company_id', $companyId)->where('packaging_order_id', $packagingOrder->id)
                ->where('status', 'Active')->orderBy('id')->lockForUpdate()->get();
            if ($items->isEmpty() || $allocations->isEmpty() || $allocations->contains(fn ($allocation) => $allocation->allocation_status !== 'proposed')) {
                throw new \Exception('Packaging order allocations are not available for acceptance.');
            }
            $stockIds = $allocations->pluck('warehouse_item_stock_id')->map(fn ($id) => (int) $id)->unique()->sort()->values()->all();
            $stocks = WarehouseItemStock::where('company_id', $companyId)->where('status', 'Active')->where('entry_type', 'IN')
                ->whereIn('id', $stockIds)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            if ($stocks->count() !== count($stockIds)) {
                throw new \Exception('One or more selected Roll/Taka records are no longer available.');
            }
            $warehouseItemIds = $stocks->pluck('warehouse_item_id')->map(fn ($id) => (int) $id)->unique()->sort()->values()->all();
            $warehouseItems = WarehouseItem::where('company_id', $companyId)->where('status', 'Active')->whereIn('id', $warehouseItemIds)
                ->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            if ($warehouseItems->count() !== count($warehouseItemIds)) {
                throw new \Exception('The warehouse receipt linked to a selected Roll/Taka is unavailable.');
            }
            $user = Auth::user();
            $individualId = $user->individual_id ?? Auth::id() ?? 0;
            foreach ($allocations as $allocation) {
                $stock = $stocks->get($allocation->warehouse_item_stock_id);
                $item = $items->get($allocation->packaging_order_item_id);
                $warehouseItem = $warehouseItems->get($stock->warehouse_item_id);
                $quantity = round((float) $allocation->allocated_quantity, 2);
                if (! $item || ! $warehouseItem || $quantity <= 0 || (int) $stock->item_id !== (int) $item->item_id || (int) $stock->item_type_id !== (int) $item->item_type_id
                    || (float) $stock->insp_bal_quan_size + 0.0001 < $quantity || (float) $warehouseItem->item_qty + 0.0001 < $quantity) {
                    throw new \Exception('A selected Roll/Taka no longer has enough matching physical warehouse stock.');
                }
                $remainingStock = round((float) $stock->insp_bal_quan_size - $quantity, 2);
                $stock->update([
                    'insp_allot_quan_size' => round((float) $stock->insp_allot_quan_size + $quantity, 2),
                    'insp_bal_quan_size' => max(0, $remainingStock),
                    'is_allotted_stock' => $remainingStock <= 0 ? 'Yes' : 'No',
                    'allocation_status' => $remainingStock <= 0 ? InventoryAllocationStatus::Allocated->value : InventoryAllocationStatus::PartiallyAllocated->value,
                    'packaging_ord_id' => $packagingOrder->id,
                    'stock_alloted_by' => $individualId,
                    'alloted_remark' => 'Packaging order '.$packagingOrder->id,
                    'modified_by' => $individualId,
                    'updated_at' => now(),
                ]);
                $warehouseOutItem = WarehouseOutItem::create([
                    'company_id' => $companyId,
                    'wis_id' => $stock->id,
                    'warehouse_item_id' => $warehouseItem->id,
                    'process_type_id' => $warehouseItem->process_type_id,
                    'warehouse_id' => $warehouseItem->warehouse_id,
                    'ware_comp_id' => $warehouseItem->ware_comp_id,
                    'item_id' => $warehouseItem->item_id,
                    'item_type_id' => $warehouseItem->item_type_id,
                    'unit_type_id' => $warehouseItem->unit_type_id,
                    'receiver_id' => $warehouseItem->receiver_id,
                    'item_qty' => $quantity,
                    'pcs' => $warehouseItem->pcs ?? 0,
                    'cut' => $warehouseItem->cut,
                    'meter' => $warehouseItem->meter ?? 0,
                    'insp_taka_number' => $stock->insp_taka_number,
                    'dyeing_lot_number' => $stock->dyeing_lot_number,
                    'dyeing_taka_number' => $stock->dyeing_taka_number,
                    'individual_id' => $individualId,
                    'packaging_ord_id' => $packagingOrder->id,
                    'item_remark' => 'Packaging order '.$packagingOrder->id,
                    'grey_quality' => $warehouseItem->grey_quality,
                    'dyeing_color' => $warehouseItem->dyeing_color,
                    'coating_type' => $warehouseItem->coating_type,
                    'print_job' => $warehouseItem->print_job,
                    'extra_job' => $warehouseItem->extra_job,
                    'movement_status' => InventoryMovementStatus::Posted->value,
                    'created_at' => now(),
                    'updated_at' => now(),
                    'financial_year' => currentFinancialYear(),
                    'status' => 'Active',
                ]);
                $openBalance = WarehouseBalanceItem::forPhysicalStock($warehouseOutItem)->where('balance_status', 1)
                    ->where('status', 'Active')->orderByDesc('id')->lockForUpdate()->first();
                $physicalBalance = round((float) WarehouseItemStock::where('company_id', $companyId)->where('warehouse_id', $warehouseOutItem->warehouse_id)
                    ->where('ware_comp_id', $warehouseOutItem->ware_comp_id)->where('item_id', $warehouseOutItem->item_id)
                    ->where('item_type_id', $warehouseOutItem->item_type_id)->where('dyeing_color', $warehouseOutItem->dyeing_color)
                    ->where('coating_type', $warehouseOutItem->coating_type)->where('print_job', $warehouseOutItem->print_job)
                    ->where('extra_job', $warehouseOutItem->extra_job)->where('status', 'Active')->sum('insp_bal_quan_size'), 2);
                $openingQuantity = round((float) ($openBalance->item_qty ?? 0), 2);
                if (! $openBalance || $openingQuantity + 0.0001 < $quantity || abs(($openingQuantity - $quantity) - $physicalBalance) > 0.01) {
                    throw new \Exception('Warehouse balance snapshot is out of sync. Reconcile the affected stock before packaging acceptance.');
                }
                WarehouseBalanceItem::forPhysicalStock($warehouseOutItem)->where('balance_status', 1)->where('status', 'Active')
                    ->update(['balance_status' => 0, 'current_balance_key' => null, 'modified_by' => $individualId, 'updated_at' => now()]);
                WarehouseBalanceItem::create([
                    'company_id' => $companyId,
                    'ware_in_item_id' => 0,
                    'ware_out_item_id' => $warehouseOutItem->id,
                    'warehouse_id' => $warehouseOutItem->warehouse_id,
                    'ware_comp_id' => $warehouseOutItem->ware_comp_id,
                    'item_id' => $warehouseOutItem->item_id,
                    'item_type_id' => $warehouseOutItem->item_type_id,
                    'unit_type_id' => $warehouseOutItem->unit_type_id,
                    'op_item_qty' => $openingQuantity,
                    'in_item_qty' => 0,
                    'out_item_qty' => $quantity,
                    'item_qty' => round($openingQuantity - $quantity, 2),
                    'grey_quality' => $warehouseOutItem->grey_quality,
                    'dyeing_color' => $warehouseOutItem->dyeing_color,
                    'coating_type' => $warehouseOutItem->coating_type,
                    'print_job' => $warehouseOutItem->print_job,
                    'extra_job' => $warehouseOutItem->extra_job,
                    'movement_status' => InventoryMovementStatus::Posted->value,
                    'created_by' => $individualId,
                    'created_at' => now(),
                    'financial_year' => currentFinancialYear(),
                    'current_balance_key' => hash('sha256', implode('|', [$companyId, $warehouseOutItem->warehouse_id, $warehouseOutItem->ware_comp_id, $warehouseOutItem->item_id, $warehouseOutItem->item_type_id, $warehouseOutItem->dyeing_color, $warehouseOutItem->coating_type, $warehouseOutItem->print_job, $warehouseOutItem->extra_job])),
                    'balance_status' => 1,
                    'status' => 'Active',
                ]);
                $warehouseItem->update([
                    'item_qty' => round((float) $warehouseItem->item_qty - $quantity, 2),
                    'allotted_qty' => round((float) $warehouseItem->allotted_qty + $quantity, 2),
                    'modified_by' => $individualId,
                    'updated_at' => now(),
                ]);
                $allocation->update([
                    'warehouse_out_item_id' => $warehouseOutItem->id,
                    'accepted_quantity' => $quantity,
                    'remaining_quantity' => $quantity,
                    'allocation_status' => 'accepted',
                    'accepted_by' => $individualId,
                    'accepted_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            $packagingOrder->update([
                'packaging_status' => 'accepted',
                'accepted_by' => $individualId,
                'accepted_at' => now(),
                'updated_at' => now(),
            ]);
            DB::commit();
            Session::put('message', 'Packaging order accepted and physical warehouse stock issued.');
            Session::put('messageClass', 'successClass');

            return redirect()->route('packaging.show', $packagingOrder->id);
        } catch (\Exception $e) {
            DB::rollBack();
            report($e);
            Session::put('message', 'Packaging acceptance could not be completed. '.$e->getMessage());
            Session::put('messageClass', 'errorClass');

            return back();
        }
    }

    public function pack(Request $request, int $packagingOrder)
    {
        $validator = Validator::make($request->all(), [
            'packaging_roll_allocation_ids' => 'required|array|min:1',
            'packaging_roll_allocation_ids.*' => 'required|integer|distinct',
            'packed_quantities' => 'required|array|min:1',
            'packed_quantities.*' => 'required|numeric|gt:0',
        ]);
        if ($validator->fails()) {
            Session::put('message', $validator->errors()->first());
            Session::put('messageClass', 'errorClass');

            return back()->withInput();
        }

        DB::beginTransaction();
        try {
            $context = $request->attributes->get(CurrentOrganizationContext::class);
            if (! $context instanceof CurrentOrganizationContext) {
                throw new \Exception('An active organization context is required.');
            }
            $companyId = $context->companyId();
            $allocationIds = array_map('intval', array_values($request->packaging_roll_allocation_ids));
            $packedQuantities = array_map(fn ($quantity) => round((float) $quantity, 2), array_values($request->packed_quantities));
            if (count($allocationIds) !== count($packedQuantities)) {
                throw new \Exception('Each selected allocation must have one packed quantity.');
            }
            $packagingOrder = PackagingOrder::where('company_id', $companyId)->where('status', 'Active')->whereKey($packagingOrder)->lockForUpdate()->first();
            if (! $packagingOrder || ! in_array($packagingOrder->packaging_status, ['accepted', 'packed'], true)) {
                throw new \Exception('Only an accepted packaging order can be packed.');
            }
            $sortedAllocationIds = $allocationIds;
            sort($sortedAllocationIds);
            $allocations = PackagingRollAllocation::where('company_id', $companyId)->where('packaging_order_id', $packagingOrder->id)
                ->where('status', 'Active')->whereIn('id', $sortedAllocationIds)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            if ($allocations->count() !== count($sortedAllocationIds)) {
                throw new \Exception('One or more packaging allocations are unavailable.');
            }
            $packedTotal = 0;
            foreach ($allocationIds as $index => $allocationId) {
                $allocation = $allocations->get($allocationId);
                $quantity = $packedQuantities[$index];
                $availableToPack = round((float) $allocation->accepted_quantity - (float) $allocation->packed_quantity - (float) $allocation->cancelled_quantity, 2);
                if (! in_array($allocation->allocation_status, ['accepted', 'packed'], true) || $quantity > $availableToPack + 0.0001) {
                    throw new \Exception('Packed quantity exceeds the accepted Roll/Taka quantity.');
                }
                $newPackedQuantity = round((float) $allocation->packed_quantity + $quantity, 2);
                $allocation->update([
                    'packed_quantity' => $newPackedQuantity,
                    'remaining_quantity' => max(0, round((float) $allocation->accepted_quantity - $newPackedQuantity - (float) $allocation->cancelled_quantity, 2)),
                    'allocation_status' => $newPackedQuantity + (float) $allocation->cancelled_quantity >= (float) $allocation->accepted_quantity ? 'packed' : 'accepted',
                    'updated_at' => now(),
                ]);
                $packedTotal += $quantity;
            }
            $allAllocations = PackagingRollAllocation::where('company_id', $companyId)->where('packaging_order_id', $packagingOrder->id)
                ->where('status', 'Active')->orderBy('id')->lockForUpdate()->get();
            $items = PackagingOrderItem::where('company_id', $companyId)->where('packaging_order_id', $packagingOrder->id)
                ->where('status', 'Active')->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            foreach ($allAllocations->groupBy('packaging_order_item_id') as $itemId => $itemAllocations) {
                $item = $items->get($itemId);
                if (! $item) {
                    throw new \Exception('Packaging line item is no longer available.');
                }
                $itemPackedQuantity = round((float) $itemAllocations->sum('packed_quantity'), 2);
                $itemCancelledQuantity = round((float) $itemAllocations->sum('cancelled_quantity'), 2);
                $item->update([
                    'packed_quantity' => $itemPackedQuantity,
                    'cancelled_quantity' => $itemCancelledQuantity,
                    'remaining_quantity' => max(0, round((float) $item->allocated_quantity - $itemPackedQuantity - $itemCancelledQuantity, 2)),
                    'updated_at' => now(),
                ]);
            }
            $newOrderPackedQuantity = round((float) $packagingOrder->packed_quantity + $packedTotal, 2);
            $packagingOrder->update([
                'packed_quantity' => $newOrderPackedQuantity,
                'remaining_quantity' => max(0, round((float) $packagingOrder->allocated_quantity - $newOrderPackedQuantity - (float) $packagingOrder->cancelled_quantity, 2)),
                'packaging_status' => $allAllocations->every(fn ($allocation) => $allocation->allocation_status === 'packed') ? 'packed' : 'accepted',
                'updated_at' => now(),
            ]);
            DB::commit();
            Session::put('message', 'Packaging quantity updated. No sales delivery quantity has been changed.');
            Session::put('messageClass', 'successClass');

            return redirect()->route('packaging.show', $packagingOrder->id);
        } catch (\Exception $e) {
            DB::rollBack();
            report($e);
            Session::put('message', 'Packaging quantity could not be updated. '.$e->getMessage());
            Session::put('messageClass', 'errorClass');

            return back()->withInput();
        }
    }

    public function reverse(Request $request, int $packagingOrder)
    {
        $validator = Validator::make($request->all(), ['reversal_reason' => 'required|string|max:1000']);
        if ($validator->fails()) {
            Session::put('message', $validator->errors()->first());
            Session::put('messageClass', 'errorClass');

            return back()->withInput();
        }

        DB::beginTransaction();
        try {
            $context = $request->attributes->get(CurrentOrganizationContext::class);
            if (! $context instanceof CurrentOrganizationContext) {
                throw new \Exception('An active organization context is required.');
            }
            $companyId = $context->companyId();
            $packagingOrder = PackagingOrder::where('company_id', $companyId)->where('status', 'Active')->whereKey($packagingOrder)->lockForUpdate()->first();
            if (! $packagingOrder || ! in_array($packagingOrder->packaging_status, ['draft', 'accepted', 'packed'], true) || (float) $packagingOrder->dispatched_quantity > 0) {
                throw new \Exception('Only an undispatched packaging order can be safely cancelled or unpacked.');
            }
            $items = PackagingOrderItem::where('company_id', $companyId)->where('packaging_order_id', $packagingOrder->id)
                ->where('status', 'Active')->orderBy('id')->lockForUpdate()->get();
            $allocations = PackagingRollAllocation::where('company_id', $companyId)->where('packaging_order_id', $packagingOrder->id)
                ->where('status', 'Active')->orderBy('id')->lockForUpdate()->get();
            if ($items->isEmpty() || $allocations->isEmpty() || $allocations->contains(fn ($allocation) => $allocation->allocation_status === 'reversed')) {
                throw new \Exception('Packaging order is not available for reversal.');
            }
            $user = Auth::user();
            $individualId = $user->individual_id ?? Auth::id() ?? 0;
            if ($packagingOrder->packaging_status !== 'draft') {
                $stockIds = $allocations->pluck('warehouse_item_stock_id')->map(fn ($id) => (int) $id)->unique()->sort()->values()->all();
                $stocks = WarehouseItemStock::where('company_id', $companyId)->where('status', 'Active')->whereIn('id', $stockIds)
                    ->orderBy('id')->lockForUpdate()->get()->keyBy('id');
                $warehouseItemIds = $stocks->pluck('warehouse_item_id')->map(fn ($id) => (int) $id)->unique()->sort()->values()->all();
                $warehouseItems = WarehouseItem::where('company_id', $companyId)->where('status', 'Active')->whereIn('id', $warehouseItemIds)
                    ->orderBy('id')->lockForUpdate()->get()->keyBy('id');
                $outItemIds = $allocations->pluck('warehouse_out_item_id')->filter()->map(fn ($id) => (int) $id)->unique()->sort()->values()->all();
                $outItems = WarehouseOutItem::where('company_id', $companyId)->where('status', 'Active')->whereIn('id', $outItemIds)
                    ->orderBy('id')->lockForUpdate()->get()->keyBy('id');
                if ($stocks->count() !== count($stockIds) || $warehouseItems->count() !== count($warehouseItemIds) || $outItems->count() !== count($outItemIds)) {
                    throw new \Exception('The original warehouse movement cannot be safely reversed.');
                }
                foreach ($allocations as $allocation) {
                    $stock = $stocks->get($allocation->warehouse_item_stock_id);
                    $warehouseItem = $warehouseItems->get($stock->warehouse_item_id);
                    $outItem = $outItems->get($allocation->warehouse_out_item_id);
                    $returnQuantity = round((float) $allocation->accepted_quantity, 2);
                    if (! $stock || ! $warehouseItem || ! $outItem || $returnQuantity <= 0 || (float) $stock->insp_allot_quan_size + 0.0001 < $returnQuantity) {
                        throw new \Exception('A Roll/Taka allocation cannot be safely returned to warehouse stock.');
                    }
                    $stock->update([
                        'insp_allot_quan_size' => max(0, round((float) $stock->insp_allot_quan_size - $returnQuantity, 2)),
                        'insp_bal_quan_size' => round((float) $stock->insp_bal_quan_size + $returnQuantity, 2),
                        'is_allotted_stock' => (float) $stock->insp_allot_quan_size - $returnQuantity <= 0 ? 'No' : 'Yes',
                        'allocation_status' => (float) $stock->insp_allot_quan_size - $returnQuantity <= 0 ? InventoryAllocationStatus::Released->value : InventoryAllocationStatus::PartiallyAllocated->value,
                        'return_packaging_ord_id' => $packagingOrder->id,
                        'modified_by' => $individualId,
                        'updated_at' => now(),
                    ]);
                    $warehouseItem->update([
                        'item_qty' => round((float) $warehouseItem->item_qty + $returnQuantity, 2),
                        'allotted_qty' => max(0, round((float) $warehouseItem->allotted_qty - $returnQuantity, 2)),
                        'modified_by' => $individualId,
                        'updated_at' => now(),
                    ]);
                    $outItem->update([
                        'qty_returned' => round((float) $outItem->qty_returned + $returnQuantity, 2),
                        'is_item_return_whouse' => '1',
                        'movement_status' => InventoryMovementStatus::Reversed->value,
                        'modified_by' => $individualId,
                        'updated_at' => now(),
                    ]);
                    $openBalance = WarehouseBalanceItem::forPhysicalStock($outItem)->where('balance_status', 1)
                        ->where('status', 'Active')->orderByDesc('id')->lockForUpdate()->first();
                    $physicalBalance = round((float) WarehouseItemStock::where('company_id', $companyId)->where('warehouse_id', $outItem->warehouse_id)
                        ->where('ware_comp_id', $outItem->ware_comp_id)->where('item_id', $outItem->item_id)
                        ->where('item_type_id', $outItem->item_type_id)->where('dyeing_color', $outItem->dyeing_color)
                        ->where('coating_type', $outItem->coating_type)->where('print_job', $outItem->print_job)
                        ->where('extra_job', $outItem->extra_job)->where('status', 'Active')->sum('insp_bal_quan_size'), 2);
                    $openingQuantity = round((float) ($openBalance->item_qty ?? 0), 2);
                    if (! $openBalance || abs(($openingQuantity + $returnQuantity) - $physicalBalance) > 0.01) {
                        throw new \Exception('Warehouse balance snapshot is out of sync. Reconcile the affected stock before reversal.');
                    }
                    WarehouseBalanceItem::forPhysicalStock($outItem)->where('balance_status', 1)->where('status', 'Active')
                        ->update(['balance_status' => 0, 'current_balance_key' => null, 'modified_by' => $individualId, 'updated_at' => now()]);
                    WarehouseBalanceItem::create([
                        'company_id' => $companyId,
                        'ware_in_item_id' => $warehouseItem->id,
                        'ware_out_item_id' => $outItem->id,
                        'warehouse_id' => $outItem->warehouse_id,
                        'ware_comp_id' => $outItem->ware_comp_id,
                        'item_id' => $outItem->item_id,
                        'item_type_id' => $outItem->item_type_id,
                        'unit_type_id' => $outItem->unit_type_id,
                        'op_item_qty' => $openingQuantity,
                        'in_item_qty' => $returnQuantity,
                        'out_item_qty' => 0,
                        'item_qty' => round($openingQuantity + $returnQuantity, 2),
                        'grey_quality' => $outItem->grey_quality,
                        'dyeing_color' => $outItem->dyeing_color,
                        'coating_type' => $outItem->coating_type,
                        'print_job' => $outItem->print_job,
                        'extra_job' => $outItem->extra_job,
                        'movement_status' => InventoryMovementStatus::Posted->value,
                        'created_by' => $individualId,
                        'created_at' => now(),
                        'financial_year' => currentFinancialYear(),
                        'current_balance_key' => hash('sha256', implode('|', [$companyId, $outItem->warehouse_id, $outItem->ware_comp_id, $outItem->item_id, $outItem->item_type_id, $outItem->dyeing_color, $outItem->coating_type, $outItem->print_job, $outItem->extra_job])),
                        'balance_status' => 1,
                        'status' => 'Active',
                    ]);
                    $allocation->update([
                        'cancelled_quantity' => round((float) $allocation->cancelled_quantity + $returnQuantity, 2),
                        'returned_quantity' => round((float) $allocation->returned_quantity + $returnQuantity, 2),
                        'remaining_quantity' => 0,
                        'allocation_status' => 'reversed',
                        'reversed_by' => $individualId,
                        'reversed_at' => now(),
                        'reversal_reason' => $request->reversal_reason,
                        'updated_at' => now(),
                    ]);
                }
            } else {
                foreach ($allocations as $allocation) {
                    $allocation->update([
                        'cancelled_quantity' => $allocation->allocated_quantity,
                        'remaining_quantity' => 0,
                        'allocation_status' => 'reversed',
                        'reversed_by' => $individualId,
                        'reversed_at' => now(),
                        'reversal_reason' => $request->reversal_reason,
                        'updated_at' => now(),
                    ]);
                }
            }
            foreach ($items as $item) {
                $item->update([
                    'cancelled_quantity' => $item->allocated_quantity,
                    'returned_quantity' => $packagingOrder->packaging_status === 'draft' ? 0 : $item->allocated_quantity,
                    'remaining_quantity' => 0,
                    'updated_at' => now(),
                ]);
                $otherOpenItems = PackagingOrderItem::where('company_id', $companyId)->where('sale_order_item_id', $item->sale_order_item_id)
                    ->where('packaging_order_id', '!=', $packagingOrder->id)->where('status', 'Active')->lockForUpdate()->get();
                $hasOtherOpenPackaging = $otherOpenItems->contains(fn ($otherItem) => $otherItem->packagingOrder && $otherItem->packagingOrder->packaging_status !== 'cancelled');
                if (! $hasOtherOpenPackaging) {
                    SaleOrderItem::where('company_id', $companyId)->whereKey($item->sale_order_item_id)->lockForUpdate()->update([
                        'is_packaging_done' => '0',
                        'modified_by' => $individualId,
                        'modified_at' => now(),
                    ]);
                }
            }
            $packagingOrder->update([
                'packaging_status' => 'cancelled',
                'cancelled_quantity' => $packagingOrder->allocated_quantity,
                'returned_quantity' => $packagingOrder->packaging_status === 'draft' ? 0 : $packagingOrder->allocated_quantity,
                'remaining_quantity' => 0,
                'cancelled_by' => $individualId,
                'cancelled_at' => now(),
                'cancellation_reason' => $request->reversal_reason,
                'updated_at' => now(),
            ]);
            DB::commit();
            Session::put('message', 'Packaging order cancelled and every accepted Roll/Taka quantity returned to its original warehouse stock.');
            Session::put('messageClass', 'successClass');

            return redirect()->route('packaging.index');
        } catch (\Exception $e) {
            DB::rollBack();
            report($e);
            Session::put('message', 'Packaging reversal could not be completed. '.$e->getMessage());
            Session::put('messageClass', 'errorClass');

            return back();
        }
    }
}
