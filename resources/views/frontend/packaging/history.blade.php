<!DOCTYPE html>
<html lang="en">
<head>
    @include('frontend.common.head', ['pageTitle' => 'Packaged Orders | Loomexa'])
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
                        <div class="workorder-filter-wrap">
                            <div class="workorder-filter-box">
                                <form method="get" action="{{ route('packaging.show-packaged-orders') }}" role="search" autocomplete="off">
                                    <div class="workorder-filter-row">
                                        <div><div class="input-group input-group-sm"><span class="input-group-addon"><i class="fa fa-user"></i></span><input class="form-control" name="customer_name" id="packaging-customer-search" value="{{ request('customer_name') }}" placeholder="Customer"></div><input type="hidden" name="customer_id" id="packaging-customer-id" value="{{ request('customer_id') }}"></div>
                                        <div><div class="input-group input-group-sm"><span class="input-group-addon"><i class="fa fa-cube"></i></span><input class="form-control" name="item" id="packaging-item-search" value="{{ request('item') }}" placeholder="Item"></div><input type="hidden" name="item_id" id="packaging-item-id" value="{{ request('item_id') }}"></div>
                                        <div><div class="input-group input-group-sm"><span class="input-group-addon"><i class="fa fa-file-text-o"></i></span><input class="form-control" name="sale_order" id="packaging-sale-order-search" value="{{ request('sale_order') }}" placeholder="S.O. Number"></div><input type="hidden" name="sale_order_id" id="packaging-sale-order-id" value="{{ request('sale_order_id') }}"></div>
                                        <div><div class="input-group input-group-sm"><span class="input-group-addon"><i class="fa fa-barcode"></i></span><input class="form-control" name="challan_number" id="packaging-challan-number" value="{{ request('challan_number') }}" placeholder="Challan Number"></div></div>
                                        <div><div class="input-group input-group-sm"><span class="input-group-addon"><i class="fa fa-tag"></i></span><input class="form-control" name="lot" id="packaging-lot-search" value="{{ request('lot') }}" placeholder="Lot Number"></div></div>
                                        <div class="input-group input-group-sm"><select class="form-control" name="packaging_mode" id="packaging-mode"><option value="">All Modes</option>@foreach (['bulk' => 'Bulk / Lot-wise', 'sample' => 'Sample'] as $mode => $label)<option value="{{ $mode }}" @selected(request('packaging_mode') === $mode)>{{ $label }}</option>@endforeach</select><select class="form-control" name="packaging_status" id="packaging-history-status"><option value="">All States</option>@foreach(['draft','accepted','packed','dispatched','cancelled'] as $status)<option value="{{ $status }}" @selected(request('packaging_status') === $status)>{{ ucfirst($status) }}</option>@endforeach</select></div>
                                        <div><select class="form-control input-sm" name="financial_year_id" id="packaging-history-financial-year"><option value="">All Financial Years</option>@foreach ($financialYears as $financialYear)<option value="{{ $financialYear->id }}" @selected((string) request('financial_year_id') === (string) $financialYear->id)>{{ $financialYear->display_name }}</option>@endforeach</select></div>
                                    </div>
                                    <div class="workorder-filter-row">
                                        <div><select class="form-control input-sm" name="item_type_id" id="packaging-item-type"><option value="">All Item Types</option>@foreach ($itemTypes as $itemType)<option value="{{ $itemType->item_type_id }}" @selected((string) request('item_type_id') === (string) $itemType->item_type_id)>{{ $itemType->item_type_name }}</option>@endforeach</select></div>
                                        <div><div class="input-group input-group-sm"><span class="input-group-addon"><i class="fa fa-list-alt"></i></span><input class="form-control" name="quality" id="packaging-quality" value="{{ request('quality') }}" placeholder="Quality"></div></div>
                                        <div><div class="input-group input-group-sm"><span class="input-group-addon"><i class="fa fa-tint"></i></span><input class="form-control" name="shade" id="packaging-shade" value="{{ request('shade') }}" placeholder="Dyeing Shade"></div></div>
                                        <div><div class="input-group input-group-sm"><span class="input-group-addon"><i class="fa fa-paint-brush"></i></span><input class="form-control" name="coating" id="packaging-coating" value="{{ request('coating') }}" placeholder="Coating"></div></div>
                                        <div><select class="form-control input-sm" name="priority" id="packaging-priority"><option value="">All Priorities</option>@foreach ($priorities as $priority)<option value="{{ $priority }}" @selected(request('priority') === $priority)>{{ $priority }}</option>@endforeach</select></div>
                                        <div><div class="input-group input-group-sm"><span class="input-group-addon"><i class="fa fa-calendar"></i></span><input class="form-control" type="date" name="from_date" id="packaging-from-date" value="{{ request('from_date') }}" title="From Date"><span class="input-group-addon">to</span><input class="form-control" type="date" name="to_date" id="packaging-to-date" value="{{ request('to_date') }}" title="To Date"></div></div>
                                        <div class="filter-action-buttons"><div class="btn-group btn-group-sm" role="group"><button type="submit" class="btn btn-success"><i class="fa fa-search"></i> Search</button><a class="btn btn-default" href="{{ route('packaging.show-packaged-orders') }}"><i class="fa fa-refresh"></i> Reset</a></div></div>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-hover">
                                <thead><tr class="info"><th>Packaging / Mode</th><th>Customer</th><th>Sale Order(s)</th><th>Item(s)</th><th>Packaging Type</th><th>Lots / Rolls / Taka</th><th>Allocated</th><th>Packed</th><th>Dispatched</th><th>Pending</th><th>Status / Created</th><th>Actions</th></tr></thead>
                                <tbody>
                                @forelse($packagingOrders as $order)
                                    <tr>
                                        <td>PKG-{{ $order->id }}<br><small>{{ $order->challan_numbers->join(', ') ?: 'No Challan' }} / {{ ucfirst($order->packaging_mode ?: 'bulk') }}</small></td>
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
