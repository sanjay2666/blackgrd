<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Department;
use App\Models\FinancialYear;
use App\Models\Individual;
use App\Models\PackagingOrder;
use App\Models\PackagingOrderItem;
use App\Models\PackagingRollAllocation;
use App\Models\SaleOrderItem;
use App\Models\SalesChallan;
use App\Models\SalesChallanItem;
use App\Models\SalesChallanRollAllocation;
use App\Services\CurrentOrganizationContext;
use App\Services\DepartmentAccessService;
use App\Services\FinancialYearResolver;
use App\Services\NumberSeriesService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SalesChallanController extends Controller
{
    public function index(Request $request, DepartmentAccessService $departmentAccess)
    {
        $context = $request->attributes->get(CurrentOrganizationContext::class);
        abort_unless($context instanceof CurrentOrganizationContext, 403);
        $department = Department::where('company_id', $context->companyId())->where('status', 'Active')->where('department_name', 'Packaging')->first();
        abort_unless($department && $departmentAccess->mayAccess($department->id), 403);

        $challans = SalesChallan::with('customer')->where('company_id', $context->companyId())->where('record_status', 'Active')
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            ->when($request->filled('challan_number'), fn ($query) => $query->where('challan_number', 'like', '%'.trim($request->challan_number).'%'))
            ->when($request->filled('financial_year_id'), fn ($query) => $query->where('financial_year_id', (int) dec((string) $request->financial_year_id)))
            ->orderByDesc('id')->paginate(config('app.pagination_limit'));
        $financialYears = FinancialYear::where('company_id', $context->companyId())->where('status', 'Active')
            ->orderByDesc('start_date')->get(['id', 'display_name']);

        return view('frontend.sales_challans.index', compact('challans', 'financialYears'));
    }

    public function create(Request $request, DepartmentAccessService $departmentAccess)
    {
        $context = $request->attributes->get(CurrentOrganizationContext::class);
        abort_unless($context instanceof CurrentOrganizationContext, 403);
        $department = Department::where('company_id', $context->companyId())->where('status', 'Active')->where('department_name', 'Packaging')->first();
        abort_unless($department && $departmentAccess->mayAccess($department->id), 403);

        $allocations = PackagingRollAllocation::with(['packagingOrder.customer', 'packagingOrderItem.saleOrderItem.saleOrder', 'packagingOrderItem.packagingType'])
            ->where('company_id', $context->companyId())->where('status', 'Active')->whereIn('allocation_status', ['accepted', 'packed'])
            ->where('packed_quantity', '>', 0)->orderBy('packaging_order_id')->orderBy('packaging_order_item_id')->orderBy('dyeing_lot_number')->orderBy('id')->get()
            ->map(function (PackagingRollAllocation $allocation) {
                $allocation->available_to_dispatch = max(0, round((float) $allocation->packed_quantity - (float) $allocation->dispatched_quantity, 2));

                return $allocation;
            })->filter(fn (PackagingRollAllocation $allocation) => $allocation->available_to_dispatch > 0)->values();
        $transporters = Individual::where('company_id', $context->companyId())->where('type', 'transport')->where('status', 'Active')->orderBy('name')->get();

        return view('frontend.sales_challans.create', compact('allocations', 'transporters'));
    }

    public function store(Request $request, NumberSeriesService $numberSeries, FinancialYearResolver $financialYears, DepartmentAccessService $departmentAccess)
    {
        $validator = Validator::make($request->all(), [
            'packaging_roll_allocation_ids' => 'required|array|min:1', 'packaging_roll_allocation_ids.*' => 'required|string|distinct',
            'dispatch_quantities' => 'required|array|min:1', 'dispatch_quantities.*' => 'required|numeric|gt:0',
            'submission_key' => 'required|uuid', 'challan_date' => 'required|date', 'dispatch_date' => 'nullable|date',
            'transporter_id' => 'nullable|string', 'billing_address' => 'nullable|string', 'shipping_address' => 'nullable|string',
            'from_station' => 'nullable|string|max:100', 'to_station' => 'nullable|string|max:100', 'parcel_count' => 'nullable|integer|min:0',
            'lr_number' => 'nullable|string|max:100', 'lr_date' => 'nullable|date', 'vehicle_number' => 'nullable|string|max:50',
            'driver_name' => 'nullable|string|max:100', 'driver_contact' => 'nullable|string|max:25', 'remarks' => 'nullable|string|max:1000',
        ]);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $allocationIds = array_map(fn ($id) => (int) dec((string) $id), array_values($request->packaging_roll_allocation_ids));
        $transporterId = $request->filled('transporter_id') ? (int) dec((string) $request->transporter_id) : null;
        if (count($allocationIds) !== count(array_unique($allocationIds))) {
            return back()->withInput()->with('message', 'Packaging allocations must be unique.')->with('messageClass', 'errorClass');
        }

        DB::beginTransaction();
        try {
            $context = $request->attributes->get(CurrentOrganizationContext::class);
            if (! $context instanceof CurrentOrganizationContext) {
                throw new \Exception('An active organization context is required.');
            }
            $companyId = $context->companyId();
            $department = Department::where('company_id', $companyId)->where('status', 'Active')->where('department_name', 'Packaging')->lockForUpdate()->first();
            if (! $department || ! $departmentAccess->mayAccess($department->id)) {
                throw new \Exception('Packaging department dispatch access is required.');
            }
            if (SalesChallan::where('company_id', $companyId)->where('submission_key', $request->submission_key)->exists()) {
                throw new \Exception('This dispatch submission was already recorded.');
            }
            $quantities = array_map(fn ($quantity) => round((float) $quantity, 2), array_values($request->dispatch_quantities));
            if (count($allocationIds) !== count($quantities)) {
                throw new \Exception('Each selected Roll/Taka requires an exact dispatch meter.');
            }
            $sortedIds = $allocationIds;
            sort($sortedIds);
            $allocations = PackagingRollAllocation::with(['packagingOrder.customer', 'packagingOrderItem.saleOrderItem.saleOrder', 'packagingOrderItem.packagingType'])
                ->where('company_id', $companyId)->where('status', 'Active')->whereIn('id', $sortedIds)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            if ($allocations->count() !== count($sortedIds)) {
                throw new \Exception('One or more selected packaging Roll/Taka records are unavailable.');
            }
            $customerIds = $allocations->map(fn (PackagingRollAllocation $row) => (int) ($row->packagingOrder?->customer_id ?? 0))->filter()->unique()->values();
            if ($customerIds->count() !== 1 || $allocations->contains(fn (PackagingRollAllocation $row) => ! $row->packagingOrderItem || ! $row->packagingOrder || ! in_array($row->allocation_status, ['accepted', 'packed'], true))) {
                throw new \Exception('A Sales Challan can contain packed material for one valid customer only.');
            }
            $totalMeter = 0;
            foreach ($allocationIds as $index => $allocationId) {
                $allocation = $allocations->get($allocationId);
                $available = round((float) $allocation->packed_quantity - (float) $allocation->dispatched_quantity, 2);
                if ($quantities[$index] > $available + 0.0001) {
                    throw new \Exception('Dispatch meter exceeds packed quantity available for the selected Roll/Taka.');
                }
                $totalMeter += $quantities[$index];
            }
            $customer = Individual::where('company_id', $companyId)->where('type', 'customers')->where('status', 'Active')->whereKey($customerIds->first())->lockForUpdate()->first();
            if (! $customer) {
                throw new \Exception('The selected customer is unavailable.');
            }
            $transporter = null;
            if ($request->filled('transporter_id')) {
                $transporter = Individual::where('company_id', $companyId)->where('type', 'transport')->where('status', 'Active')->whereKey($transporterId)->lockForUpdate()->first();
                if (! $transporter) {
                    throw new \Exception('Selected transporter is unavailable.');
                }
            }
            $firstSaleOrder = $allocations->first()->packagingOrderItem->saleOrderItem?->saleOrder;
            $financialYear = $financialYears->current($companyId);
            $user = Auth::user();
            $userId = $user->individual_id ?? Auth::id() ?? null;
            $challan = SalesChallan::create([
                'company_id' => $companyId, 'department_id' => $department->id, 'customer_id' => $customer->id, 'financial_year_id' => $financialYear->id,
                'challan_number' => $numberSeries->next('sales-challan', $financialYear), 'status' => 'Draft', 'challan_date' => $request->challan_date, 'dispatch_date' => $request->dispatch_date,
                'customer_name' => $customer->name, 'customer_gstin' => $customer->gstin, 'customer_phone' => $customer->phone,
                'billing_address' => $request->billing_address ?: $firstSaleOrder?->billing_address, 'shipping_address' => $request->shipping_address ?: $firstSaleOrder?->shipping_address,
                'transporter_id' => $transporter?->id, 'transporter_name' => $transporter?->name, 'transporter_phone' => $transporter?->phone, 'transporter_email' => $transporter?->email, 'transporter_gstin' => $transporter?->gstin,
                'from_station' => $request->from_station, 'to_station' => $request->to_station, 'lr_number' => $request->lr_number, 'lr_date' => $request->lr_date,
                'vehicle_number' => $request->vehicle_number, 'driver_name' => $request->driver_name, 'driver_contact' => $request->driver_contact, 'parcel_count' => $request->parcel_count,
                'roll_count' => count($allocationIds), 'lot_count' => $allocations->pluck('dyeing_lot_number')->filter()->unique()->count(), 'total_meter' => round($totalMeter, 2), 'remarks' => $request->remarks,
                'submission_key' => $request->submission_key, 'created_by' => $userId, 'created_at' => now(), 'updated_at' => now(), 'record_status' => 'Active',
            ]);
            $challanItems = [];
            foreach ($allocations->groupBy('packaging_order_item_id') as $packagingItemId => $itemAllocations) {
                $packagingItem = $itemAllocations->first()->packagingOrderItem;
                $saleOrder = $packagingItem->saleOrderItem?->saleOrder;
                $challanItems[$packagingItemId] = SalesChallanItem::create([
                    'company_id' => $companyId, 'financial_year_id' => $financialYear->id, 'sales_challan_id' => $challan->id, 'packaging_order_id' => $packagingItem->packaging_order_id, 'packaging_order_item_id' => $packagingItem->id,
                    'sale_order_id' => $packagingItem->sale_order_id, 'sale_order_item_id' => $packagingItem->sale_order_item_id, 'sale_order_number' => $saleOrder?->sale_order_number,
                    'item_id' => $packagingItem->item_id, 'item_type_id' => $packagingItem->item_type_id, 'unit_type_id' => $packagingItem->unit_type_id, 'packaging_type_id' => $packagingItem->packaging_type_id,
                    'packaging_type_name' => $packagingItem->packagingType?->name, 'item_name' => $packagingItem->item_name, 'grey_quality' => $packagingItem->grey_quality, 'dyeing_color' => $packagingItem->dyeing_color,
                    'coating_type' => $packagingItem->coating_type, 'print_job' => $packagingItem->print_job, 'extra_job' => $packagingItem->extra_job,
                    'final_dispatch_width' => $packagingItem->final_dispatch_width, 'tube_width' => $packagingItem->tube_width, 'dispatched_quantity' => 0, 'created_at' => now(), 'updated_at' => now(), 'record_status' => 'Active',
                ]);
            }
            foreach ($allocationIds as $index => $allocationId) {
                $allocation = $allocations->get($allocationId);
                SalesChallanRollAllocation::create([
                    'company_id' => $companyId, 'financial_year_id' => $financialYear->id, 'sales_challan_id' => $challan->id, 'sales_challan_item_id' => $challanItems[$allocation->packaging_order_item_id]->id,
                    'packaging_order_id' => $allocation->packaging_order_id, 'packaging_order_item_id' => $allocation->packaging_order_item_id, 'packaging_roll_allocation_id' => $allocation->id,
                    'warehouse_item_stock_id' => $allocation->warehouse_item_stock_id, 'warehouse_out_item_id' => $allocation->warehouse_out_item_id, 'dyeing_lot_number' => $allocation->dyeing_lot_number,
                    'packet_number' => $allocation->packet_number, 'insp_taka_number' => $allocation->insp_taka_number, 'packed_quantity_snapshot' => $allocation->packed_quantity,
                    'previously_dispatched_quantity_snapshot' => $allocation->dispatched_quantity, 'dispatched_quantity' => $quantities[$index], 'remarks' => null,
                    'created_at' => now(), 'updated_at' => now(), 'record_status' => 'Active',
                ]);
            }
            DB::commit();

            return redirect()->route('sales-challans.show', enc($challan->id))->with('message', 'Sales Challan draft created. Post it to record customer dispatch.')->with('messageClass', 'successClass');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withInput()->with('message', $e->getMessage())->with('messageClass', 'errorClass');
        }
    }

    public function show(Request $request, string $salesChallan, DepartmentAccessService $departmentAccess)
    {
        $context = $request->attributes->get(CurrentOrganizationContext::class);
        abort_unless($context instanceof CurrentOrganizationContext, 403);
        $salesChallanId = (int) dec($salesChallan);
        $salesChallan = SalesChallan::with(['customer', 'items.rollAllocations'])->where('company_id', $context->companyId())->where('record_status', 'Active')->findOrFail($salesChallanId);
        abort_unless($departmentAccess->mayAccess((int) $salesChallan->department_id), 403);

        return view('frontend.sales_challans.show', compact('salesChallan'));
    }

    public function post(Request $request, string $salesChallan, DepartmentAccessService $departmentAccess)
    {
        $salesChallanId = (int) dec($salesChallan);
        DB::beginTransaction();
        try {
            $context = $request->attributes->get(CurrentOrganizationContext::class);
            if (! $context instanceof CurrentOrganizationContext) {
                throw new \Exception('An active organization context is required.');
            }
            $companyId = $context->companyId();
            $challan = SalesChallan::where('company_id', $companyId)->where('record_status', 'Active')->whereKey($salesChallanId)->lockForUpdate()->first();
            if (! $challan || $challan->status !== 'Draft') {
                throw new \Exception('Only an unposted Sales Challan can be dispatched once.');
            }
            if (! $departmentAccess->mayAccess((int) $challan->department_id)) {
                throw new \Exception('Packaging department dispatch access is required.');
            }
            $rolls = SalesChallanRollAllocation::where('company_id', $companyId)->where('sales_challan_id', $challan->id)->where('record_status', 'Active')->orderBy('id')->lockForUpdate()->get();
            $items = SalesChallanItem::where('company_id', $companyId)->where('sales_challan_id', $challan->id)->where('record_status', 'Active')->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            if ($rolls->isEmpty() || $items->isEmpty()) {
                throw new \Exception('Sales Challan has no dispatch rows.');
            }
            $packagingAllocationIds = $rolls->pluck('packaging_roll_allocation_id')->unique()->sort()->values()->all();
            $packagingAllocations = PackagingRollAllocation::where('company_id', $companyId)->where('status', 'Active')->whereIn('id', $packagingAllocationIds)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            if ($packagingAllocations->count() !== count($packagingAllocationIds)) {
                throw new \Exception('A source Packaging Roll/Taka is unavailable.');
            }
            foreach ($rolls as $roll) {
                $allocation = $packagingAllocations->get($roll->packaging_roll_allocation_id);
                $available = round((float) $allocation->packed_quantity - (float) $allocation->dispatched_quantity, 2);
                if (! in_array($allocation->allocation_status, ['accepted', 'packed'], true) || (float) $roll->dispatched_quantity <= 0 || (float) $roll->dispatched_quantity > $available + 0.0001) {
                    throw new \Exception('Customer dispatch exceeds the currently packed quantity for a Roll/Taka.');
                }
                $allocation->update(['dispatched_quantity' => round((float) $allocation->dispatched_quantity + (float) $roll->dispatched_quantity, 2), 'updated_at' => now()]);
            }
            foreach ($items as $item) {
                $dispatchQuantity = round((float) $rolls->where('sales_challan_item_id', $item->id)->sum('dispatched_quantity'), 2);
                $item->update(['dispatched_quantity' => $dispatchQuantity, 'updated_at' => now()]);
                $packagingItem = PackagingOrderItem::where('company_id', $companyId)->whereKey($item->packaging_order_item_id)->lockForUpdate()->first();
                if (! $packagingItem) {
                    throw new \Exception('Source Packaging line is unavailable.');
                }
                $packagingItem->update(['dispatched_quantity' => round((float) $packagingItem->dispatched_quantity + $dispatchQuantity, 2), 'updated_at' => now()]);
                $saleOrderItem = SaleOrderItem::where('company_id', $companyId)->whereKey($item->sale_order_item_id)->lockForUpdate()->first();
                if (! $saleOrderItem) {
                    throw new \Exception('Source Sale Order Item is unavailable.');
                }
                $delivered = round((float) ($saleOrderItem->delivered_item_mtr ?? 0) + $dispatchQuantity, 2);
                $ordered = round((float) $saleOrderItem->meter, 2);
                if ($delivered > $ordered + 0.0001) {
                    throw new \Exception('Customer dispatch exceeds the Sale Order Item pending quantity.');
                }
                $saleOrderItem->update(['delivered_item_mtr' => $delivered, 'pending_item_mtr' => max(0, round($ordered - $delivered, 2)), 'is_work_final_dlvr_completed' => $delivered >= $ordered ? '1' : '0', 'modified_at' => now()]);
            }
            foreach ($items->pluck('packaging_order_id')->unique() as $packagingOrderId) {
                $packagingOrder = PackagingOrder::where('company_id', $companyId)->whereKey($packagingOrderId)->lockForUpdate()->first();
                $totalDispatched = round((float) PackagingRollAllocation::where('company_id', $companyId)->where('packaging_order_id', $packagingOrderId)->where('status', 'Active')->sum('dispatched_quantity'), 2);
                $totalPacked = round((float) PackagingRollAllocation::where('company_id', $companyId)->where('packaging_order_id', $packagingOrderId)->where('status', 'Active')->sum('packed_quantity'), 2);
                $packagingOrder->update(['dispatched_quantity' => $totalDispatched, 'packaging_status' => $totalPacked > 0 && $totalDispatched + 0.0001 >= $totalPacked ? 'dispatched' : $packagingOrder->packaging_status, 'updated_at' => now()]);
            }
            $user = Auth::user();
            $challan->update(['status' => 'Posted', 'posted_by' => $user->individual_id ?? Auth::id() ?? null, 'posted_at' => now(), 'dispatch_date' => $challan->dispatch_date ?: now()->toDateString(), 'updated_at' => now()]);
            DB::commit();

            return redirect()->route('sales-challans.show', enc($challan->id))->with('message', 'Customer Dispatch posted. Packaging stock was not issued again.')->with('messageClass', 'successClass');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('message', $e->getMessage())->with('messageClass', 'errorClass');
        }
    }

    public function cancel(Request $request, string $salesChallan, DepartmentAccessService $departmentAccess)
    {
        $validator = Validator::make($request->all(), ['cancellation_reason' => 'required|string|max:1000']);
        if ($validator->fails()) {
            return back()->withErrors($validator);
        }
        $salesChallanId = (int) dec($salesChallan);
        DB::beginTransaction();
        try {
            $context = $request->attributes->get(CurrentOrganizationContext::class);
            if (! $context instanceof CurrentOrganizationContext) {
                throw new \Exception('An active organization context is required.');
            }
            $companyId = $context->companyId();
            $challan = SalesChallan::where('company_id', $companyId)->where('record_status', 'Active')->whereKey($salesChallanId)->lockForUpdate()->first();
            if (! $challan || $challan->status !== 'Posted') {
                throw new \Exception('Only a posted Sales Challan can be cancelled once.');
            }
            if (! $departmentAccess->mayAccess((int) $challan->department_id)) {
                throw new \Exception('Packaging department dispatch access is required.');
            }
            $rolls = SalesChallanRollAllocation::where('company_id', $companyId)->where('sales_challan_id', $challan->id)->where('record_status', 'Active')->orderBy('id')->lockForUpdate()->get();
            $items = SalesChallanItem::where('company_id', $companyId)->where('sales_challan_id', $challan->id)->where('record_status', 'Active')->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            foreach ($rolls as $roll) {
                $allocation = PackagingRollAllocation::where('company_id', $companyId)->where('status', 'Active')->whereKey($roll->packaging_roll_allocation_id)->lockForUpdate()->first();
                if (! $allocation || (float) $allocation->dispatched_quantity + 0.0001 < (float) $roll->dispatched_quantity) {
                    throw new \Exception('Packaging dispatch balance cannot be safely reversed.');
                }
                $allocation->update(['dispatched_quantity' => max(0, round((float) $allocation->dispatched_quantity - (float) $roll->dispatched_quantity, 2)), 'updated_at' => now()]);
            }
            foreach ($items as $item) {
                $quantity = round((float) $item->dispatched_quantity, 2);
                $packagingItem = PackagingOrderItem::where('company_id', $companyId)->whereKey($item->packaging_order_item_id)->lockForUpdate()->first();
                $saleOrderItem = SaleOrderItem::where('company_id', $companyId)->whereKey($item->sale_order_item_id)->lockForUpdate()->first();
                if (! $packagingItem || ! $saleOrderItem || (float) $packagingItem->dispatched_quantity + 0.0001 < $quantity || (float) ($saleOrderItem->delivered_item_mtr ?? 0) + 0.0001 < $quantity) {
                    throw new \Exception('Dispatch balances cannot be safely reversed.');
                }
                $packagingItem->update(['dispatched_quantity' => max(0, round((float) $packagingItem->dispatched_quantity - $quantity, 2)), 'updated_at' => now()]);
                $delivered = max(0, round((float) $saleOrderItem->delivered_item_mtr - $quantity, 2));
                $ordered = round((float) $saleOrderItem->meter, 2);
                $saleOrderItem->update(['delivered_item_mtr' => $delivered, 'pending_item_mtr' => max(0, round($ordered - $delivered, 2)), 'is_work_final_dlvr_completed' => $delivered >= $ordered ? '1' : '0', 'modified_at' => now()]);
            }
            foreach ($items->pluck('packaging_order_id')->unique() as $packagingOrderId) {
                $packagingOrder = PackagingOrder::where('company_id', $companyId)->whereKey($packagingOrderId)->lockForUpdate()->first();
                $totalDispatched = round((float) PackagingRollAllocation::where('company_id', $companyId)->where('packaging_order_id', $packagingOrderId)->where('status', 'Active')->sum('dispatched_quantity'), 2);
                $packagingOrder->update(['dispatched_quantity' => $totalDispatched, 'packaging_status' => $packagingOrder->packed_quantity > 0 ? 'packed' : $packagingOrder->packaging_status, 'updated_at' => now()]);
            }
            $user = Auth::user();
            $challan->update(['status' => 'Cancelled', 'cancelled_by' => $user->individual_id ?? Auth::id() ?? null, 'cancelled_at' => now(), 'cancellation_reason' => $request->cancellation_reason, 'updated_at' => now()]);
            DB::commit();

            return redirect()->route('sales-challans.show', enc($challan->id))->with('message', 'Sales Challan cancelled; Packaging dispatch availability and Sale Order pending meter were restored. Warehouse stock was not restored.')->with('messageClass', 'successClass');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('message', $e->getMessage())->with('messageClass', 'errorClass');
        }
    }

    public function print(Request $request, string $salesChallan, DepartmentAccessService $departmentAccess)
    {
        $context = $request->attributes->get(CurrentOrganizationContext::class);
        abort_unless($context instanceof CurrentOrganizationContext, 403);
        $salesChallanId = (int) dec($salesChallan);
        $salesChallan = SalesChallan::with('items.rollAllocations')->where('company_id', $context->companyId())->where('record_status', 'Active')->findOrFail($salesChallanId);
        abort_unless($departmentAccess->mayAccess((int) $salesChallan->department_id), 403);
        $company = Company::whereKey($context->companyId())->firstOrFail();
        $lotTotals = $salesChallan->items->flatMap(fn (SalesChallanItem $item) => $item->rollAllocations)
            ->groupBy(fn (SalesChallanRollAllocation $roll) => $roll->dyeing_lot_number ?: 'Unspecified')
            ->map(fn ($rolls) => ['roll_count' => $rolls->count(), 'meter' => round((float) $rolls->sum('dispatched_quantity'), 2)]);

        return view('frontend.sales_challans.print', compact('salesChallan', 'company', 'lotTotals'));
    }

    public function incrementPrint(Request $request, string $salesChallan, DepartmentAccessService $departmentAccess)
    {
        $salesChallanId = (int) dec($salesChallan);
        DB::beginTransaction();
        try {
            $context = $request->attributes->get(CurrentOrganizationContext::class);
            if (! $context instanceof CurrentOrganizationContext) {
                throw new \Exception('An active organization context is required.');
            }
            $challan = SalesChallan::where('company_id', $context->companyId())->where('record_status', 'Active')->whereKey($salesChallanId)->lockForUpdate()->firstOrFail();
            if (! $departmentAccess->mayAccess((int) $challan->department_id)) {
                throw new \Exception('Packaging department dispatch access is required.');
            }
            $challan->update(['print_count' => (int) $challan->print_count + 1, 'first_printed_at' => $challan->first_printed_at ?: now(), 'updated_at' => now()]);
            DB::commit();

            return response()->json(['print_count' => $challan->print_count]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
