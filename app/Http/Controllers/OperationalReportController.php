<?php

namespace App\Http\Controllers;

use App\Models\FinancialYear;
use App\Models\Individual;
use App\Models\Item;
use App\Models\PackagingOrder;
use App\Models\Purchase;
use App\Models\SaleOrder;
use App\Models\SaleOrderItem;
use App\Models\SalesChallan;
use App\Models\StockMillDispatch;
use App\Models\WarehouseBalanceItem;
use App\Models\WorkInspection;
use App\Models\WorkOrder;
use App\Services\CurrentOrganizationContext;
use App\Services\DepartmentAccessService;
use Illuminate\Http\Request;

class OperationalReportController extends Controller
{
    public function show(Request $request, string $report)
    {
        $titles = [
            'pending-orders' => 'Pending Sale Order Report',
            'production-status' => 'Work Order / Production Status Report',
            'stock-movement' => 'Stock Movement Report',
            'packaging' => 'Packaging Report',
            'customer-dispatch' => 'Sales Challan / Customer Dispatch Report',
            'purchase-receiving' => 'Purchase / Receiving Report',
            'job-work' => 'Job Work Dispatch / Receive / Pending Report',
            'inspection-rejection' => 'Inspection / Rejection Report',
        ];
        abort_unless(array_key_exists($report, $titles), 404);

        $context = $request->attributes->get(CurrentOrganizationContext::class);
        abort_unless($context instanceof CurrentOrganizationContext, 403);
        $companyId = $context->companyId();
        $perPage = (int) config('app.pagination_limit', 20);
        $customerId = $request->filled('customer_id') ? (int) dec((string) $request->customer_id) : null;
        $itemId = $request->filled('item_id') ? (int) dec((string) $request->item_id) : null;
        $saleOrderId = $request->filled('sale_order_id') ? (int) dec((string) $request->sale_order_id) : null;
        $workOrderId = $request->filled('work_order_id') ? (int) dec((string) $request->work_order_id) : null;
        $vendorId = $request->filled('vendor_id') ? (int) dec((string) $request->vendor_id) : null;
        $financialYearId = $request->filled('financial_year_id') ? (int) dec((string) $request->financial_year_id) : null;
        $financialYear = $financialYearId ? FinancialYear::where('company_id', $companyId)->where('status', 'Active')->find($financialYearId) : null;
        $fromDate = $request->date('from_date')?->toDateString();
        $toDate = $request->date('to_date')?->toDateString();
        $rows = collect();
        $totals = [];

        if ($report === 'pending-orders') {
            $query = SaleOrderItem::with(['saleOrder.customer', 'item'])
                ->where('company_id', $companyId)->where('status', 'Active')
                ->where(function ($pending) {
                    $pending->where('pending_item_mtr', '>', 0)->orWhere('is_work_final_dlvr_completed', '0');
                });
            if ($financialYear) {
                $query->where('financial_year_id', $financialYear->id);
            }
            if ($customerId) {
                $query->whereHas('saleOrder', fn ($order) => $order->where('customer_id', $customerId));
            }
            if ($itemId) {
                $query->where('item_id', $itemId);
            }
            if ($saleOrderId) {
                $query->where('sale_order_id', $saleOrderId);
            }
            if ($fromDate) {
                $query->whereDate('expect_delivery_date', '>=', $fromDate);
            }
            if ($toDate) {
                $query->whereDate('expect_delivery_date', '<=', $toDate);
            }
            $totals = ['Ordered Meter' => (clone $query)->sum('meter'), 'Pending Meter' => (clone $query)->sum('pending_item_mtr')];
            $rows = $query->orderBy('expect_delivery_date')->orderByDesc('id')->paginate($perPage)->withQueryString();
        }

        if ($report === 'production-status') {
            $query = WorkOrder::with(['ProcessType', 'Item', 'WorkOrderItem.Customer', 'WorkOrderItem.SaleOrder', 'WorkInspection'])
                ->where('company_id', $companyId)->where('status', 'Active');
            $allowedProcesses = app(DepartmentAccessService::class)->allowedProcessIds();
            $query->whereIn('process_type_id', $allowedProcesses);
            if ($financialYear) {
                $query->where('financial_year_id', $financialYear->id);
            }
            if ($customerId) {
                $query->whereHas('WorkOrderItem', fn ($item) => $item->where('customer_id', $customerId));
            }
            if ($itemId) {
                $query->where('item_id', $itemId);
            }
            if ($saleOrderId) {
                $query->whereHas('WorkOrderItem', fn ($item) => $item->where('sale_order_id', $saleOrderId));
            }
            if ($workOrderId) {
                $query->whereKey($workOrderId);
            }
            if ($request->filled('status')) {
                $query->where('execution_status', $request->status);
            }
            if ($fromDate) {
                $query->whereDate('created_at', '>=', $fromDate);
            }
            if ($toDate) {
                $query->whereDate('created_at', '<=', $toDate);
            }
            $totals = ['Work Orders' => (clone $query)->count(), 'Planned Meter' => (clone $query)->sum('meter'), 'Output Meter' => (clone $query)->sum('output_quantity')];
            $rows = $query->orderByDesc('id')->paginate($perPage)->withQueryString();
        }

        if ($report === 'stock-movement') {
            $query = WarehouseBalanceItem::with(['Item', 'UnitType', 'Warehouse', 'WarehouseOutItem', 'WarehouseItem'])
                ->where('company_id', $companyId)->where('status', 'Active');
            if ($financialYear) {
                $query->where('financial_year_id', $financialYear->id);
            }
            if ($itemId) {
                $query->where('item_id', $itemId);
            }
            if ($fromDate) {
                $query->whereDate('receive_date', '>=', $fromDate);
            }
            if ($toDate) {
                $query->whereDate('receive_date', '<=', $toDate);
            }
            $movementType = $request->input('movement_type');
            if ($movementType === 'in') {
                $query->where('in_item_qty', '>', 0);
            }
            if ($movementType === 'out') {
                $query->where('out_item_qty', '>', 0);
            }
            if ($movementType === 'allotment') {
                $query->whereHas('WarehouseOutItem', fn ($out) => $out->whereNull('mill_dispatch_id')->whereNull('packaging_ord_id'));
            }
            if ($movementType === 'return') {
                $query->whereHas('WarehouseOutItem', fn ($out) => $out->where('is_item_return_whouse', '1'));
            }
            if ($movementType === 'job_dispatch') {
                $query->whereHas('WarehouseOutItem', fn ($out) => $out->whereNotNull('mill_dispatch_id'));
            }
            if ($movementType === 'packaging') {
                $query->whereHas('WarehouseOutItem', fn ($out) => $out->whereNotNull('packaging_ord_id'));
            }
            $totals = ['IN' => (clone $query)->sum('in_item_qty'), 'OUT' => (clone $query)->sum('out_item_qty'), 'Balance' => (clone $query)->sum('item_qty')];
            $rows = $query->orderByDesc('receive_date')->orderByDesc('id')->paginate($perPage)->withQueryString();
            $rows->getCollection()->transform(function (WarehouseBalanceItem $row) {
                $out = $row->WarehouseOutItem;
                $row->movement_label = (float) $row->in_item_qty > 0 ? 'IN' : 'OUT';
                if ($out?->mill_dispatch_id) {
                    $row->movement_label = 'Job Work Dispatch';
                } elseif ($out?->packaging_ord_id) {
                    $row->movement_label = 'Packaging';
                } elseif ($out?->is_item_return_whouse === '1') {
                    $row->movement_label = 'Return';
                } elseif ($out) {
                    $row->movement_label = 'Allotment';
                }

                return $row;
            });
        }

        if ($report === 'packaging') {
            $query = PackagingOrder::with(['customer', 'items.saleOrderItem.saleOrder', 'items.rollAllocations'])
                ->where('company_id', $companyId)->where('status', 'Active');
            if ($financialYear) {
                $query->where('financial_year_id', $financialYear->id);
            }
            if ($customerId) {
                $query->where('customer_id', $customerId);
            }
            if ($itemId) {
                $query->whereHas('items', fn ($item) => $item->where('item_id', $itemId));
            }
            if ($saleOrderId) {
                $query->whereHas('items', fn ($item) => $item->where('sale_order_id', $saleOrderId));
            }
            if ($request->filled('status')) {
                $query->where('packaging_status', $request->status);
            }
            if ($fromDate) {
                $query->whereDate('created_at', '>=', $fromDate);
            }
            if ($toDate) {
                $query->whereDate('created_at', '<=', $toDate);
            }
            $totals = ['Allocated' => (clone $query)->sum('allocated_quantity'), 'Packed' => (clone $query)->sum('packed_quantity'), 'Dispatched' => (clone $query)->sum('dispatched_quantity'), 'Balance' => (clone $query)->sum('remaining_quantity')];
            $rows = $query->orderByDesc('id')->paginate($perPage)->withQueryString();
        }

        if ($report === 'customer-dispatch') {
            $query = SalesChallan::with(['customer', 'financialYear', 'items'])
                ->where('company_id', $companyId)->where('record_status', 'Active');
            if ($financialYear) {
                $query->where('financial_year_id', $financialYear->id);
            }
            if ($customerId) {
                $query->where('customer_id', $customerId);
            }
            if ($saleOrderId) {
                $query->whereHas('items', fn ($item) => $item->where('sale_order_id', $saleOrderId));
            }
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }
            if ($fromDate) {
                $query->whereDate('dispatch_date', '>=', $fromDate);
            }
            if ($toDate) {
                $query->whereDate('dispatch_date', '<=', $toDate);
            }
            $totals = ['Challans' => (clone $query)->count(), 'Rolls/Takas' => (clone $query)->sum('roll_count'), 'Dispatched Meter' => (clone $query)->sum('total_meter')];
            $rows = $query->orderByDesc('id')->paginate($perPage)->withQueryString();
        }

        if ($report === 'purchase-receiving') {
            $query = Purchase::with(['purchaseOrder.vendor', 'vendor', 'items.item'])
                ->where('company_id', $companyId)->where('status', 'Active');
            if ($financialYear) {
                $query->where('financial_year_id', $financialYear->id);
            }
            if ($vendorId) {
                $query->where('vendor_id', $vendorId);
            }
            if ($itemId) {
                $query->whereHas('items', fn ($item) => $item->where('item_id', $itemId));
            }
            if ($fromDate) {
                $query->whereDate('receiving_date', '>=', $fromDate);
            }
            if ($toDate) {
                $query->whereDate('receiving_date', '<=', $toDate);
            }
            $totals = ['Receipts' => (clone $query)->count(), 'Quantity' => (clone $query)->sum('total_qty'), 'Meter' => (clone $query)->sum('total_meter')];
            $rows = $query->orderByDesc('receiving_date')->orderByDesc('id')->paginate($perPage)->withQueryString();
        }

        if ($report === 'job-work') {
            $query = StockMillDispatch::with(['Vendor', 'ProcessType', 'Item', 'StockMillDispatchItem.ReceiveStockMillDispatchItem'])
                ->where('company_id', $companyId)->where('status', 'Active');
            if ($financialYear) {
                $query->where('financial_year_id', $financialYear->id);
            }
            if ($vendorId) {
                $query->where('vendor_id', $vendorId);
            }
            if ($itemId) {
                $query->where(function ($item) use ($itemId) {
                    $item->where('item_id', $itemId)->orWhereHas('StockMillDispatchItem', fn ($row) => $row->where('item_id', $itemId));
                });
            }
            if ($request->input('status') === 'pending') {
                $query->where('is_tot_mtr_received', 0);
            }
            if ($request->input('status') === 'received') {
                $query->where('is_tot_mtr_received', 1);
            }
            if ($fromDate) {
                $query->whereDate('chalan_date', '>=', $fromDate);
            }
            if ($toDate) {
                $query->whereDate('chalan_date', '<=', $toDate);
            }
            $totals = ['Dispatched Meter' => (clone $query)->sum('tot_meter'), 'Received Meter' => (clone $query)->sum('tot_receive_mtr'), 'Pending Meter' => max(0, (float) (clone $query)->sum('tot_meter') - (float) (clone $query)->sum('tot_receive_mtr'))];
            $rows = $query->orderByDesc('chalan_date')->orderByDesc('id')->paginate($perPage)->withQueryString();
        }

        if ($report === 'inspection-rejection') {
            $query = WorkInspection::with(['WorkOrder.ProcessType', 'WorkOrder.Item', 'WorkOrder.WorkOrderItem.Customer'])
                ->where('company_id', $companyId)->where('status', 'Active')->where('is_deleted', 0)
                ->where(function ($inspection) {
                    $inspection->whereNotNull('fabric_fault_reason_id')->orWhere('inspection_result', 'rejected')->orWhere('insp_work_status', 'Rejected');
                });
            if ($financialYear) {
                $query->where('financial_year_id', $financialYear->id);
            }
            if ($itemId) {
                $query->where('item_id', $itemId);
            }
            if ($workOrderId) {
                $query->where('work_order_id', $workOrderId);
            }
            if ($customerId) {
                $query->whereHas('WorkOrder.WorkOrderItem', fn ($item) => $item->where('customer_id', $customerId));
            }
            if ($fromDate) {
                $query->whereDate('created_at', '>=', $fromDate);
            }
            if ($toDate) {
                $query->whereDate('created_at', '<=', $toDate);
            }
            $totals = ['Rejected Inspections' => (clone $query)->count(), 'Rejected Meter' => (clone $query)->sum('insp_quan_size'), 'Shrinkage Meter' => (clone $query)->sum('shrinkage_quantity')];
            $rows = $query->orderByDesc('id')->paginate($perPage)->withQueryString();
        }

        $financialYears = FinancialYear::where('company_id', $companyId)->where('status', 'Active')->orderByDesc('start_date')->get(['id', 'display_name']);

        return view('frontend.reports.operational-report', compact('report', 'titles', 'rows', 'totals', 'financialYears'));
    }

    public function autocomplete(Request $request, string $entity)
    {
        abort_unless(in_array($entity, ['customer', 'item', 'sale-order', 'work-order', 'vendor'], true), 404);
        $context = $request->attributes->get(CurrentOrganizationContext::class);
        abort_unless($context instanceof CurrentOrganizationContext, 403);
        $term = trim((string) $request->input('term', ''));
        $companyId = $context->companyId();

        if ($entity === 'customer' || $entity === 'vendor') {
            $type = $entity === 'customer' ? 'customers' : 'vendor';
            $records = Individual::where('company_id', $companyId)->where('status', 'Active')->where('type', $type)
                ->when($term !== '', fn ($query) => $query->where(fn ($search) => $search->where('name', 'like', '%'.$term.'%')->orWhere('company_name', 'like', '%'.$term.'%')->orWhere('phone', 'like', '%'.$term.'%')))
                ->orderBy('name')->limit(15)->get(['id', 'name', 'company_name']);

            return response()->json($records->map(fn (Individual $record) => ['id' => enc($record->id), 'label' => $record->name ?: $record->company_name, 'value' => $record->name ?: $record->company_name])->values());
        }
        if ($entity === 'item') {
            $records = Item::where('company_id', $companyId)->where('status', 'Active')
                ->when($term !== '', fn ($query) => $query->where(fn ($search) => $search->where('item_name', 'like', '%'.$term.'%')->orWhere('item_code', 'like', '%'.$term.'%')->orWhere('internal_item_name', 'like', '%'.$term.'%')))
                ->orderBy('item_name')->limit(15)->get(['item_id', 'item_name', 'item_code']);

            return response()->json($records->map(fn (Item $record) => ['id' => enc($record->item_id), 'label' => trim($record->item_name.' '.$record->item_code), 'value' => $record->item_name])->values());
        }
        if ($entity === 'sale-order') {
            $records = SaleOrder::where('company_id', $companyId)->where('status', 'Active')->whereNotNull('sale_order_number')
                ->when($term !== '', fn ($query) => $query->where('sale_order_number', 'like', '%'.$term.'%'))
                ->orderByDesc('id')->limit(15)->get(['id', 'sale_order_number']);

            return response()->json($records->map(fn (SaleOrder $record) => ['id' => enc($record->id), 'label' => $record->sale_order_number, 'value' => $record->sale_order_number])->values());
        }
        $records = WorkOrder::where('company_id', $companyId)->where('status', 'Active')
            ->when($term !== '', fn ($query) => $query->where(fn ($search) => $search->where('id', 'like', '%'.$term.'%')->orWhere('item_name', 'like', '%'.$term.'%')))
            ->orderByDesc('id')->limit(15)->get(['id', 'item_name']);

        return response()->json($records->map(fn (WorkOrder $record) => ['id' => enc($record->id), 'label' => 'WO-'.$record->id.' - '.$record->item_name, 'value' => 'WO-'.$record->id])->values());
    }
}
