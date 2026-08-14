@include('frontend.common.head', ['pageTitle' => $titles[$report].' | Loomexa'])
<body class="hold-transition sidebar-mini">
<div class="wrapper">
    @include('frontend.common.header')
    <div class="content-wrapper">
        <section class="content-header"><h1>{{ $titles[$report] }}</h1></section>
        <section class="content">
            <div class="panel panel-bd lobidrag">
                <div class="panel-heading"><div class="panel-title"><h4><i class="fa fa-bar-chart"></i> {{ $titles[$report] }}</h4></div></div>
                <div class="panel-body">
                    <form method="get" autocomplete="off" class="form-inline">
                        <div class="row">
                            <div class="col-md-2 col-sm-4 form-group"><select name="financial_year_id" class="form-control input-sm"><option value="">Financial Year</option>@foreach($financialYears as $year)<option value="{{ enc($year->id) }}" @selected(request('financial_year_id') === enc($year->id))>{{ $year->display_name }}</option>@endforeach</select></div>
                            @if(in_array($report, ['pending-orders', 'production-status', 'packaging', 'customer-dispatch', 'inspection-rejection']))
                                <div class="col-md-2 col-sm-4 form-group"><input class="form-control input-sm report-autocomplete" data-entity="customer" data-target="#customer_id" name="customer" value="{{ request('customer') }}" placeholder="Customer"><input type="hidden" id="customer_id" name="customer_id" value="{{ request('customer_id') }}"></div>
                            @endif
                            @if(in_array($report, ['pending-orders', 'production-status', 'stock-movement', 'packaging', 'purchase-receiving', 'job-work', 'inspection-rejection']))
                                <div class="col-md-2 col-sm-4 form-group"><input class="form-control input-sm report-autocomplete" data-entity="item" data-target="#item_id" name="item" value="{{ request('item') }}" placeholder="Item"><input type="hidden" id="item_id" name="item_id" value="{{ request('item_id') }}"></div>
                            @endif
                            @if(in_array($report, ['pending-orders', 'production-status', 'packaging', 'customer-dispatch']))
                                <div class="col-md-2 col-sm-4 form-group"><input class="form-control input-sm report-autocomplete" data-entity="sale-order" data-target="#sale_order_id" name="sale_order" value="{{ request('sale_order') }}" placeholder="Sale Order"><input type="hidden" id="sale_order_id" name="sale_order_id" value="{{ request('sale_order_id') }}"></div>
                            @endif
                            @if(in_array($report, ['production-status', 'inspection-rejection']))
                                <div class="col-md-2 col-sm-4 form-group"><input class="form-control input-sm report-autocomplete" data-entity="work-order" data-target="#work_order_id" name="work_order" value="{{ request('work_order') }}" placeholder="Work Order"><input type="hidden" id="work_order_id" name="work_order_id" value="{{ request('work_order_id') }}"></div>
                            @endif
                            @if(in_array($report, ['purchase-receiving', 'job-work']))
                                <div class="col-md-2 col-sm-4 form-group"><input class="form-control input-sm report-autocomplete" data-entity="vendor" data-target="#vendor_id" name="vendor" value="{{ request('vendor') }}" placeholder="Vendor"><input type="hidden" id="vendor_id" name="vendor_id" value="{{ request('vendor_id') }}"></div>
                            @endif
                            @if($report === 'stock-movement')<div class="col-md-2 col-sm-4 form-group"><select name="movement_type" class="form-control input-sm"><option value="">All movements</option><option value="in" @selected(request('movement_type') === 'in')>IN</option><option value="out" @selected(request('movement_type') === 'out')>OUT</option><option value="allotment" @selected(request('movement_type') === 'allotment')>Allotment</option><option value="return" @selected(request('movement_type') === 'return')>Return</option><option value="job_dispatch" @selected(request('movement_type') === 'job_dispatch')>Job Work Dispatch</option><option value="packaging" @selected(request('movement_type') === 'packaging')>Packaging</option></select></div>@endif
                            @if($report === 'production-status')<div class="col-md-2 col-sm-4 form-group"><select name="status" class="form-control input-sm"><option value="">All statuses</option><option value="created" @selected(request('status') === 'created')>Created</option><option value="started" @selected(request('status') === 'started')>Started</option><option value="partially_completed" @selected(request('status') === 'partially_completed')>Partially Completed</option><option value="completed" @selected(request('status') === 'completed')>Completed</option><option value="inspection_pending" @selected(request('status') === 'inspection_pending')>Inspection Pending</option><option value="rejected" @selected(request('status') === 'rejected')>Rejected</option></select></div>@endif
                            @if($report === 'packaging')<div class="col-md-2 col-sm-4 form-group"><select name="status" class="form-control input-sm"><option value="">All statuses</option><option value="draft" @selected(request('status') === 'draft')>Draft</option><option value="accepted" @selected(request('status') === 'accepted')>Accepted</option><option value="packed" @selected(request('status') === 'packed')>Packed</option><option value="dispatched" @selected(request('status') === 'dispatched')>Dispatched</option><option value="cancelled" @selected(request('status') === 'cancelled')>Cancelled</option></select></div>@endif
                            @if($report === 'customer-dispatch')<div class="col-md-2 col-sm-4 form-group"><select name="status" class="form-control input-sm"><option value="">All statuses</option><option value="Draft" @selected(request('status') === 'Draft')>Draft</option><option value="Posted" @selected(request('status') === 'Posted')>Posted</option><option value="Cancelled" @selected(request('status') === 'Cancelled')>Cancelled</option></select></div>@endif
                            @if($report === 'job-work')<div class="col-md-2 col-sm-4 form-group"><select name="status" class="form-control input-sm"><option value="">Dispatch / receive status</option><option value="pending" @selected(request('status') === 'pending')>Pending</option><option value="received" @selected(request('status') === 'received')>Received</option></select></div>@endif
                            <div class="col-md-2 col-sm-4 form-group"><input type="date" class="form-control input-sm" name="from_date" value="{{ request('from_date') }}"></div>
                            <div class="col-md-2 col-sm-4 form-group"><input type="date" class="form-control input-sm" name="to_date" value="{{ request('to_date') }}"></div>
                            <div class="col-md-1 col-sm-2 form-group"><button class="btn btn-success btn-sm"><i class="fa fa-search"></i> Search</button></div>
                            <div class="col-md-1 col-sm-2 form-group"><a href="{{ url()->current() }}" class="btn btn-default btn-sm">Reset</a></div>
                        </div>
                    </form>
                    <hr>
                    <div class="row">@foreach($totals as $label => $total)<div class="col-md-2 col-sm-4"><div class="alert alert-info"><strong>{{ $label }}</strong><br>{{ is_numeric($total) ? number_format((float) $total, 2) : $total }}</div></div>@endforeach</div>
                    <div class="table-responsive"><table class="table table-bordered table-striped table-hover small"><thead><tr class="info">
                        @if($report === 'pending-orders')<th>Sale Order</th><th>Customer</th><th>Item</th><th>Ordered</th><th>Delivered</th><th>Pending</th><th>Due Date</th>@endif
                        @if($report === 'production-status')<th>Work Order</th><th>Sale Order</th><th>Customer</th><th>Process</th><th>Item</th><th>Planned</th><th>Output</th><th>Status</th>@endif
                        @if($report === 'stock-movement')<th>Date</th><th>Movement</th><th>Warehouse</th><th>Item</th><th>IN</th><th>OUT</th><th>Balance</th><th>Lot / Taka</th>@endif
                        @if($report === 'packaging')<th>Packaging No.</th><th>Customer</th><th>Sale Orders</th><th>Allocated</th><th>Packed</th><th>Dispatched</th><th>Balance</th><th>Status</th>@endif
                        @if($report === 'customer-dispatch')<th>Challan</th><th>Date</th><th>Customer</th><th>Sale Orders</th><th>Rolls / Lots</th><th>Meter</th><th>Status</th>@endif
                        @if($report === 'purchase-receiving')<th>Receipt</th><th>Date</th><th>Vendor</th><th>Invoice</th><th>Items</th><th>Quantity</th><th>Meter</th>@endif
                        @if($report === 'job-work')<th>Challan</th><th>Date</th><th>Vendor</th><th>Process</th><th>Item</th><th>Dispatch</th><th>Received</th><th>Pending</th><th>Status</th>@endif
                        @if($report === 'inspection-rejection')<th>Inspection</th><th>Work Order</th><th>Customer</th><th>Process</th><th>Item</th><th>Lot / Taka</th><th>Rejected</th><th>Shrinkage</th><th>Comment</th>@endif
                    </tr></thead><tbody>
                    @forelse($rows as $row)<tr>
                        @if($report === 'pending-orders')<td>{{ $row->saleOrder?->sale_order_number }}</td><td>{{ $row->saleOrder?->customer?->name ?: $row->saleOrder?->customer?->company_name }}</td><td>{{ $row->item?->item_name ?: $row->item_name }}</td><td>{{ number_format((float)$row->meter, 2) }}</td><td>{{ number_format((float)$row->delivered_item_mtr, 2) }}</td><td>{{ number_format((float)$row->pending_item_mtr, 2) }}</td><td>{{ $row->expect_delivery_date }}</td>@endif
                        @if($report === 'production-status')@php($line = $row->WorkOrderItem->first())<td>WO-{{ $row->id }}</td><td>{{ $line?->SaleOrder?->sale_order_number }}</td><td>{{ $line?->Customer?->name ?: $line?->Customer?->company_name }}</td><td>{{ $row->ProcessType?->process_name }}</td><td>{{ $row->Item?->item_name ?: $row->item_name }}</td><td>{{ number_format((float)$row->meter, 2) }}</td><td>{{ number_format((float)$row->output_quantity, 2) }}</td><td>{{ $row->execution_status?->value ?: $row->work_status }}</td>@endif
                        @if($report === 'stock-movement')<td>{{ $row->receive_date }}</td><td>{{ $row->movement_label }}</td><td>{{ $row->Warehouse?->warehouse_name }}</td><td>{{ $row->Item?->item_name }}</td><td>{{ number_format((float)$row->in_item_qty, 2) }}</td><td>{{ number_format((float)$row->out_item_qty, 2) }}</td><td>{{ number_format((float)$row->item_qty, 2) }}</td><td>{{ $row->WarehouseOutItem?->dyeing_lot_number ?: $row->WarehouseItem?->dyeing_lot_number }} / {{ $row->WarehouseOutItem?->insp_taka_number ?: $row->WarehouseItem?->insp_taka_number }}</td>@endif
                        @if($report === 'packaging')<td>PKG-{{ $row->id }}</td><td>{{ $row->customer?->name ?: $row->customer?->company_name }}</td><td>{{ $row->items->pluck('saleOrderItem.saleOrder.sale_order_number')->filter()->unique()->join(', ') }}</td><td>{{ number_format((float)$row->allocated_quantity, 2) }}</td><td>{{ number_format((float)$row->packed_quantity, 2) }}</td><td>{{ number_format((float)$row->dispatched_quantity, 2) }}</td><td>{{ number_format((float)$row->remaining_quantity, 2) }}</td><td>{{ ucfirst($row->packaging_status) }}</td>@endif
                        @if($report === 'customer-dispatch')<td>{{ $row->challan_number }}</td><td>{{ $row->dispatch_date ?: $row->challan_date }}</td><td>{{ $row->customer?->name ?: $row->customer_name }}</td><td>{{ $row->items->pluck('sale_order_number')->filter()->unique()->join(', ') }}</td><td>{{ $row->roll_count }} / {{ $row->lot_count }}</td><td>{{ number_format((float)$row->total_meter, 2) }}</td><td>{{ $row->status }}</td>@endif
                        @if($report === 'purchase-receiving')<td>REC-{{ $row->id }}</td><td>{{ $row->receiving_date }}</td><td>{{ $row->vendor?->name ?: $row->vendor_name }}</td><td>{{ $row->invoice_number }}</td><td>{{ $row->items->pluck('item.item_name')->filter()->unique()->join(', ') }}</td><td>{{ number_format((float)$row->total_qty, 2) }}</td><td>{{ number_format((float)$row->total_meter, 2) }}</td>@endif
                        @if($report === 'job-work')<td>{{ $row->chalan_no }}</td><td>{{ $row->chalan_date }}</td><td>{{ $row->Vendor?->name ?: $row->vendor_name }}</td><td>{{ $row->ProcessType?->process_name }}</td><td>{{ $row->Item?->item_name ?: $row->dispatch_item_name }}</td><td>{{ number_format((float)$row->tot_meter, 2) }}</td><td>{{ number_format((float)$row->tot_receive_mtr, 2) }}</td><td>{{ number_format(max(0, (float)$row->tot_meter - (float)$row->tot_receive_mtr), 2) }}</td><td>{{ $row->is_tot_mtr_received ? 'Received' : 'Pending' }}</td>@endif
                        @if($report === 'inspection-rejection')@php($line = $row->WorkOrder?->WorkOrderItem->first())<td>INSP-{{ $row->id }}</td><td>WO-{{ $row->work_order_id }}</td><td>{{ $line?->Customer?->name ?: $line?->Customer?->company_name }}</td><td>{{ $row->WorkOrder?->ProcessType?->process_name }}</td><td>{{ $row->WorkOrder?->Item?->item_name }}</td><td>{{ $row->dyeing_lot_number }} / {{ $row->insp_taka_number }}</td><td>{{ number_format((float)$row->insp_quan_size, 2) }}</td><td>{{ number_format((float)$row->shrinkage_quantity, 2) }}</td><td>{{ $row->insp_comment }}</td>@endif
                    </tr>@empty<tr><td colspan="10" class="text-center text-muted">No report records found.</td></tr>@endforelse
                    </tbody></table></div>
                    @if(method_exists($rows, 'links'))<div class="text-center">{{ $rows->links('vendor.pagination.bootstrap-4') }}</div>@endif
                </div>
            </div>
        </section>
    </div>
    @include('frontend.common.footer')
</div>
<script>
$(function () {
    $('.report-autocomplete').each(function () {
        var input = $(this), target = $(input.data('target'));
        input.autocomplete({ minLength: 1, source: function (request, response) {
            $.get('{{ url('/reports/autocomplete') }}/' + input.data('entity'), { term: request.term }, response);
        }, select: function (event, ui) { input.val(ui.item.value); target.val(ui.item.id); return false; }, change: function () { if (!input.val()) target.val(''); } });
    });
});
</script>
</body>
</html>
