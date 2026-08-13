<!DOCTYPE html>
<html lang="en">
<head>
    @include('frontend.common.head', ['pageTitle' => 'Packaged Orders | Loomexa'])
    <style>.packaging-filters .form-group { margin-bottom: 8px; } .packaging-filters label { display: block; font-size: 11px; margin-bottom: 2px; }</style>
</head>
<body class="hold-transition sidebar-mini">
<div class="wrapper">
    @include('frontend.common.header')

    <div class="content-wrapper">
        <section class="content">
            <div class="row"><div class="col-sm-12">
                {!! display_message('message') !!}
                <div class="panel panel-bd lobidrag">
                    <div class="panel-heading">
                        <div class="btn-group"><a href="{{ route('packaging.show-packaged-orders') }}"><h4>Packaged Orders / History</h4></a></div>
                        <a class="btn btn-success btn-sm pull-right" href="{{ route('packaging.show-available-orders') }}"><i class="fa fa-plus"></i> Packaging Available</a>
                    </div>
                    <div class="panel-body">
                        <form method="get" action="{{ route('packaging.show-packaged-orders') }}" class="packaging-filters">
                            <div class="row">
                                <div class="col-sm-1 form-group"><label for="packaging-number">Packaging No.</label><input class="form-control input-sm" name="packaging_number" id="packaging-number" value="{{ request('packaging_number') }}" placeholder="PKG-#"></div>
                                <div class="col-sm-2 form-group"><label for="packaging-customer-search">Customer</label><input class="form-control input-sm" name="customer_name" id="packaging-customer-search" value="{{ request('customer_name') }}" autocomplete="off"><input type="hidden" name="customer_id" id="packaging-customer-id" value="{{ request('customer_id') }}"></div>
                                <div class="col-sm-2 form-group"><label for="packaging-sale-order-search">Sale Order</label><input class="form-control input-sm" name="sale_order" id="packaging-sale-order-search" value="{{ request('sale_order') }}" autocomplete="off"><input type="hidden" name="sale_order_id" id="packaging-sale-order-id" value="{{ request('sale_order_id') }}"></div>
                                <div class="col-sm-2 form-group"><label for="packaging-item-search">Item</label><input class="form-control input-sm" name="item" id="packaging-item-search" value="{{ request('item') }}" autocomplete="off"><input type="hidden" name="item_id" id="packaging-item-id" value="{{ request('item_id') }}"></div>
                                <div class="col-sm-1 form-group"><label for="packaging-lot-search">Lot</label><input class="form-control input-sm" name="lot" id="packaging-lot-search" value="{{ request('lot') }}" autocomplete="off"></div>
                                <div class="col-sm-1 form-group"><label for="packaging-mode">Mode</label><select class="form-control input-sm" name="packaging_mode" id="packaging-mode"><option value="">All</option>@foreach (['bulk' => 'Bulk', 'sample' => 'Sample'] as $mode => $label)<option value="{{ $mode }}" @selected(request('packaging_mode') === $mode)>{{ $label }}</option>@endforeach</select></div>
                                <div class="col-sm-1 form-group"><label for="packaging-history-status">Status</label><select class="form-control input-sm" name="packaging_status" id="packaging-history-status"><option value="">All</option>@foreach(['draft','accepted','packed','dispatched','cancelled'] as $status)<option value="{{ $status }}" @selected(request('packaging_status') === $status)>{{ ucfirst($status) }}</option>@endforeach</select></div>
                                <div class="col-sm-1 form-group"><label for="packaging-from-date">From</label><input class="form-control input-sm" type="date" name="from_date" id="packaging-from-date" value="{{ request('from_date') }}"></div>
                                <div class="col-sm-1 form-group"><label for="packaging-to-date">To</label><input class="form-control input-sm" type="date" name="to_date" id="packaging-to-date" value="{{ request('to_date') }}"></div>
                            </div>
                            <div class="row">
                                <div class="col-sm-2 form-group"><label for="packaging-item-type">Item Type</label><select class="form-control input-sm" name="item_type_id" id="packaging-item-type"><option value="">All item types</option>@foreach ($itemTypes as $itemType)<option value="{{ $itemType->item_type_id }}" @selected((string) request('item_type_id') === (string) $itemType->item_type_id)>{{ $itemType->item_type_name }}</option>@endforeach</select></div>
                                <div class="col-sm-2 form-group"><label for="packaging-quality">Quality</label><input class="form-control input-sm" name="quality" id="packaging-quality" value="{{ request('quality') }}"></div>
                                <div class="col-sm-2 form-group"><label for="packaging-shade">Dyeing Shade</label><input class="form-control input-sm" name="shade" id="packaging-shade" value="{{ request('shade') }}"></div>
                                <div class="col-sm-2 form-group"><label for="packaging-coating">Coating</label><input class="form-control input-sm" name="coating" id="packaging-coating" value="{{ request('coating') }}"></div>
                                <div class="col-sm-2 form-group"><label for="packaging-priority">Priority</label><select class="form-control input-sm" name="priority" id="packaging-priority"><option value="">All priorities</option>@foreach ($priorities as $priority)<option value="{{ $priority }}" @selected(request('priority') === $priority)>{{ $priority }}</option>@endforeach</select></div>
                                <div class="col-sm-2 form-group"><label>&nbsp;</label><button class="btn btn-primary btn-sm" type="submit"><i class="fa fa-search"></i> Search</button> <a class="btn btn-default btn-sm" href="{{ route('packaging.show-packaged-orders') }}">Reset</a></div>
                            </div>
                        </form>

                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-hover">
                                <thead><tr class="info"><th>Packaging / Mode</th><th>Customer</th><th>Sale Order(s)</th><th>Item(s)</th><th>Packaging Type</th><th>Lots / Rolls / Taka</th><th>Allocated</th><th>Packed</th><th>Dispatched</th><th>Pending</th><th>Status / Created</th><th>Actions</th></tr></thead>
                                <tbody>
                                @forelse($packagingOrders as $order)
                                    <tr>
                                        <td>PKG-{{ $order->id }}<br><small>{{ ucfirst($order->packaging_mode ?: 'bulk') }}</small></td>
                                        <td>{{ $order->customer->name ?? '-' }}</td>
                                        <td>{{ $order->sale_order_numbers->join(', ') ?: '-' }}</td>
                                        <td>{{ $order->item_names->join(', ') ?: '-' }}</td>
                                        <td>{{ $order->packaging_type_names->join(', ') ?: '-' }}</td>
                                        <td>{{ $order->lot_count }} / {{ $order->roll_count }} / {{ $order->taka_count }}</td>
                                        <td>{{ number_format((float) $order->allocated_quantity, 2) }}</td>
                                        <td>{{ number_format((float) $order->packed_quantity, 2) }}</td>
                                        <td>{{ number_format((float) $order->dispatched_quantity, 2) }}</td>
                                        <td>{{ number_format((float) $order->remaining_quantity, 2) }}</td>
                                        <td><span class="label label-{{ $order->packaging_status === 'cancelled' ? 'danger' : ($order->packaging_status === 'packed' || $order->packaging_status === 'dispatched' ? 'success' : 'warning') }}">{{ ucfirst($order->packaging_status) }}</span><br><small>{{ optional($order->created_at)->format('d-m-Y') ?: '-' }} / {{ $order->created_by ?: '-' }}</small></td>
                                        <td><a class="btn btn-primary btn-xs" href="{{ route('packaging.show-order-details', $order->id) }}">View</a> <a class="btn btn-default btn-xs" target="_blank" href="{{ route('packaging.print-packaging-slip', $order->id) }}">Print Slip</a>@if(in_array($order->packaging_status, ['accepted','packed'])) <a class="btn btn-info btn-xs" href="{{ route('packaging.show-order-details', $order->id) }}#pack-quantity">{{ $order->packaging_status === 'accepted' ? 'Pack Quantity' : 'Continue' }}</a>@endif @if($order->dispatchable_quantity > 0 && !in_array($order->packaging_status, ['cancelled'])) <a class="btn btn-success btn-xs" href="{{ route('sales-challans.create') }}">Sales Challan</a>@endif</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="12" class="text-center">No Packaging Order found.</td></tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                        {{ $packagingOrders->withQueryString()->links('vendor.pagination.bootstrap-4') }}
                    </div>
                </div>
            </div></div>
        </section>
    </div>
    @include('frontend.common.footer')
</div>
@include('frontend.common.footerscript')
@include('frontend.packaging.partials.filter-autocomplete')
</body>
</html>
