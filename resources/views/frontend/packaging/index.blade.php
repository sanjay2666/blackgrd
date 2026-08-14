<!DOCTYPE html>
<html lang="en">
<head>
    @include('frontend.common.head', ['pageTitle' => 'Packaging Available | Loomexa'])
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
                        <div class="btn-group"><a href="{{ route('packaging.show-available-orders') }}"><h4>Packaging Available / Pending Orders</h4></a></div>
                        <a class="btn btn-default btn-sm pull-right" href="{{ route('packaging.show-packaged-orders') }}"><i class="fa fa-list"></i> Packaged Orders</a>
                    </div>
                    <div class="panel-body">
                        <div class="workorder-filter-wrap">
                            <div class="workorder-filter-box">
                                <form method="get" action="{{ route('packaging.show-available-orders') }}" role="search" autocomplete="off">
                                    <div class="row" style="margin-bottom:8px">
                                        <div class="col-md-3 col-sm-6"><div class="input-group input-group-sm"><span class="input-group-addon"><i class="fa fa-user"></i></span><input class="form-control" name="customer_name" id="packaging-customer-search" value="{{ request('customer_name') }}" placeholder="Customer"></div><input type="hidden" name="customer_id" id="packaging-customer-id" value="{{ request('customer_id') }}"></div>
                                        <div class="col-md-2 col-sm-6"><div class="input-group input-group-sm"><span class="input-group-addon"><i class="fa fa-cube"></i></span><input class="form-control" name="item" id="packaging-item-search" value="{{ request('item') }}" placeholder="Item"></div><input type="hidden" name="item_id" id="packaging-item-id" value="{{ request('item_id') }}"></div>
                                        <div class="col-md-3 col-sm-6"><div class="input-group input-group-sm"><span class="input-group-addon"><i class="fa fa-file-text-o"></i></span><input class="form-control" name="sale_order" id="packaging-sale-order-search" value="{{ request('sale_order') }}" placeholder="S.O. Number"></div><input type="hidden" name="sale_order_id" id="packaging-sale-order-id" value="{{ request('sale_order_id') }}"></div>
                                        <div class="col-md-2 col-sm-6"><select class="form-control input-sm" name="item_type_id" id="packaging-item-type"><option value="">All Item Types</option>@foreach ($itemTypes as $itemType)<option value="{{ enc($itemType->item_type_id) }}" @selected(request('item_type_id') && (int) dec((string) request('item_type_id')) === (int) $itemType->item_type_id)>{{ $itemType->item_type_name }}</option>@endforeach</select></div>
                                        <div class="col-md-2 col-sm-6"><select class="form-control input-sm" name="development_type" id="packaging-development-type"><option value="">All Order Types</option>@foreach (['Bulk', 'Sample', 'JobWork'] as $type)<option value="{{ $type }}" @selected(request('development_type') === $type)>{{ $type }}</option>@endforeach</select></div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-2 col-sm-6"><select class="form-control input-sm" name="financial_year_id" id="packaging-financial-year"><option value="">All Financial Years</option>@foreach ($financialYears as $financialYear)<option value="{{ enc($financialYear->id) }}" @selected(request('financial_year_id') && (int) dec((string) request('financial_year_id')) === (int) $financialYear->id)>{{ $financialYear->display_name }}</option>@endforeach</select></div>
                                        <div class="col-md-2 col-sm-6"><div class="input-group input-group-sm"><span class="input-group-addon"><i class="fa fa-tint"></i></span><input class="form-control" name="shade" id="packaging-shade" value="{{ request('shade') }}" placeholder="Dyeing Shade"></div></div>
                                        <div class="col-md-2 col-sm-6"><div class="input-group input-group-sm"><span class="input-group-addon"><i class="fa fa-paint-brush"></i></span><input class="form-control" name="coating" id="packaging-coating" value="{{ request('coating') }}" placeholder="Coating"></div></div>
                                        <div class="col-md-2 col-sm-6"><div class="input-group input-group-sm"><span class="input-group-addon"><i class="fa fa-calendar"></i></span><input class="form-control loomexa-datepicker" type="text" data-datepicker-max-date="0" name="from_date" id="packaging-from-date" value="{{ request('from_date') }}" title="Delivery From Date"></div></div>
                                        <div class="col-md-2 col-sm-6"><div class="input-group input-group-sm"><span class="input-group-addon"><i class="fa fa-calendar"></i></span><input class="form-control loomexa-datepicker" type="text" data-datepicker-max-date="0" name="to_date" id="packaging-to-date" value="{{ request('to_date') }}" title="Delivery To Date"></div></div>
                                        <div class="col-md-2 col-sm-6 filter-action-buttons"><div class="btn-group btn-group-sm" role="group"><button class="btn btn-success" type="submit"><i class="fa fa-search"></i> Search</button><a class="btn btn-default" href="{{ route('packaging.show-available-orders') }}"><i class="fa fa-refresh"></i> Reset</a></div></div>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <form method="get" action="{{ route('packaging.show-order-cart') }}" id="packaging-worklist-form">
                            <div class="text-right" style="margin-bottom:12px"><button class="btn btn-success btn-sm" type="submit">Open Packaging Cart</button></div>
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped table-hover">
                                    <thead><tr class="info"><th>Select</th><th>Customer / Sale Order</th><th>Type / Priority</th><th>Item / Quality</th><th>Shade / Coating</th><th>Required</th><th>Already Packed</th><th>Pending</th><th>Packaging Status</th><th>Actions</th></tr></thead>
                                    <tbody>
                                    @forelse ($worklist as $saleOrderItem)
                                        @php($orders = $packagingItems->get($saleOrderItem->id, collect()))
                                        <tr data-customer="{{ isset($saleOrderItem->saleOrder->customer_id) ? enc($saleOrderItem->saleOrder->customer_id) : '' }}">
                                            <td>@if((float) $saleOrderItem->packaging_remaining_quantity > 0)<input type="checkbox" class="packaging-worklist-item" name="sale_order_item_ids[]" value="{{ enc($saleOrderItem->id) }}">@endif</td>
                                            <td>{{ $saleOrderItem->saleOrder->customer->name ?? $saleOrderItem->saleOrder->customer->individual_name ?? '-' }}<br><small>{{ $saleOrderItem->saleOrder->sale_order_number ?? '-' }}</small></td>
                                            <td>{{ $saleOrderItem->development_type }}<br><span class="label label-default">{{ $saleOrderItem->order_item_priority ?: '-' }}</span></td>
                                            <td>{{ $saleOrderItem->item->item_name ?? $saleOrderItem->item_name ?? '-' }}<br><small>{{ $saleOrderItem->grey_quality ?: ($saleOrderItem->itemType->item_type_name ?? '-') }}</small></td>
                                            <td>{{ $saleOrderItem->dyeing_color ?: '-' }}<br><small>{{ $saleOrderItem->coating_type ?: '-' }}</small></td>
                                            <td>{{ number_format((float) $saleOrderItem->meter, 2) }}</td><td>{{ number_format((float) $saleOrderItem->packaging_packed_quantity, 2) }}</td><td>{{ number_format((float) $saleOrderItem->packaging_remaining_quantity, 2) }}</td>
                                            <td><span class="label label-{{ $saleOrderItem->packaging_state === 'packed' ? 'success' : ($saleOrderItem->packaging_state === 'partial' ? 'warning' : 'default') }}">{{ ucfirst($saleOrderItem->packaging_state) }}</span></td>
                                            <td>@if((float) $saleOrderItem->packaging_remaining_quantity > 0)<a class="btn btn-success btn-xs" href="{{ route('packaging.open-cart-for-sale-order-item', ['saleOrderItem' => enc($saleOrderItem->id), 'packaging_mode' => strtolower($saleOrderItem->development_type) === 'sample' ? 'sample' : 'bulk']) }}">{{ $saleOrderItem->packaging_state === 'partial' ? 'Add Partial' : 'Start' }}</a>@endif @forelse($orders as $orderItem)<a class="btn btn-primary btn-xs" href="{{ route('packaging.show-order-details', enc($orderItem->packaging_order_id)) }}">View PKG-{{ $orderItem->packaging_order_id }}</a>@empty @if((float) $saleOrderItem->packaging_remaining_quantity <= 0)-@endif @endforelse</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="10" class="text-center">No Sale Order Item is currently sent to Packaging.</td></tr>
                                    @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </form>
                        {{ $worklist->withQueryString()->links('vendor.pagination.bootstrap-4') }}
                    </div>
                </div>
            </div></div>
        </section>
    </div>
    @include('frontend.common.footer')
</div>
@include('frontend.common.footerscript')
<script>$('#packaging-worklist-form').on('submit', function (event) { var customers = {}; $(this).find('.packaging-worklist-item:checked').each(function () { customers[$(this).closest('tr').data('customer')] = true; }); if (!Object.keys(customers).length) { event.preventDefault(); alert('Select at least one Sale Order Item for Packaging.'); return; } if (Object.keys(customers).length !== 1) { event.preventDefault(); alert('One Packaging Order can contain Sale Order Items for one customer only.'); return; } $(this).find('button[type="submit"]').prop('disabled', true).text('Opening...'); });</script>
@include('frontend.packaging.partials.filter-autocomplete')
</body>
</html>
