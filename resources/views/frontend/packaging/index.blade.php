<!DOCTYPE html>
<html lang="en">
<head>
    @include('frontend.common.head', ['pageTitle' => 'Packaging Available | Loomexa'])
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
                        <div class="btn-group"><a href="{{ route('packaging.show-available-orders') }}"><h4>Packaging Available / Pending Orders</h4></a></div>
                        <a class="btn btn-default btn-sm pull-right" href="{{ route('packaging.show-packaged-orders') }}"><i class="fa fa-list"></i> Packaged Orders</a>
                    </div>
                    <div class="panel-body">
                        <form method="get" action="{{ route('packaging.show-available-orders') }}" class="packaging-filters">
                            <div class="row">
                                <div class="col-sm-2 form-group">
                                    <label for="packaging-customer-search">Customer</label>
                                    <input class="form-control input-sm" name="customer_name" id="packaging-customer-search" value="{{ request('customer_name') }}" autocomplete="off">
                                    <input type="hidden" name="customer_id" id="packaging-customer-id" value="{{ request('customer_id') }}">
                                </div>
                                <div class="col-sm-2 form-group">
                                    <label for="packaging-sale-order-search">Sale Order</label>
                                    <input class="form-control input-sm" name="sale_order" id="packaging-sale-order-search" value="{{ request('sale_order') }}" autocomplete="off">
                                    <input type="hidden" name="sale_order_id" id="packaging-sale-order-id" value="{{ request('sale_order_id') }}">
                                </div>
                                <div class="col-sm-2 form-group">
                                    <label for="packaging-item-search">Item</label>
                                    <input class="form-control input-sm" name="item" id="packaging-item-search" value="{{ request('item') }}" autocomplete="off">
                                    <input type="hidden" name="item_id" id="packaging-item-id" value="{{ request('item_id') }}">
                                </div>
                                <div class="col-sm-2 form-group">
                                    <label for="packaging-item-type">Item Type</label>
                                    <select class="form-control input-sm" name="item_type_id" id="packaging-item-type"><option value="">All item types</option>@foreach ($itemTypes as $itemType)<option value="{{ $itemType->item_type_id }}" @selected((string) request('item_type_id') === (string) $itemType->item_type_id)>{{ $itemType->item_type_name }}</option>@endforeach</select>
                                </div>
                                <div class="col-sm-2 form-group">
                                    <label for="packaging-development-type">Order Type</label>
                                    <select class="form-control input-sm" name="development_type" id="packaging-development-type"><option value="">All order types</option>@foreach (['Bulk', 'Sample', 'JobWork'] as $type)<option value="{{ $type }}" @selected(request('development_type') === $type)>{{ $type }}</option>@endforeach</select>
                                </div>
                                <div class="col-sm-2 form-group">
                                    <label for="packaging-priority">Priority</label>
                                    <select class="form-control input-sm" name="priority" id="packaging-priority"><option value="">All priorities</option>@foreach ($priorities as $priority)<option value="{{ $priority }}" @selected(request('priority') === $priority)>{{ $priority }}</option>@endforeach</select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-2 form-group"><label for="packaging-quality">Quality</label><input class="form-control input-sm" name="quality" id="packaging-quality" value="{{ request('quality') }}"></div>
                                <div class="col-sm-2 form-group"><label for="packaging-shade">Dyeing Shade</label><input class="form-control input-sm" name="shade" id="packaging-shade" value="{{ request('shade') }}"></div>
                                <div class="col-sm-2 form-group"><label for="packaging-coating">Coating</label><input class="form-control input-sm" name="coating" id="packaging-coating" value="{{ request('coating') }}"></div>
                                <div class="col-sm-2 form-group">
                                    <label for="packaging-state">Packaging State</label>
                                    <select class="form-control input-sm" name="packaging_state" id="packaging-state"><option value="">All states</option>@foreach (['pending' => 'Pending', 'partial' => 'Partial', 'packed' => 'Packed'] as $key => $label)<option value="{{ $key }}" @selected(request('packaging_state') === $key)>{{ $label }}</option>@endforeach</select>
                                </div>
                                <div class="col-sm-1 form-group"><label for="packaging-from-date">Delivery From</label><input class="form-control input-sm" type="date" name="from_date" id="packaging-from-date" value="{{ request('from_date') }}"></div>
                                <div class="col-sm-1 form-group"><label for="packaging-to-date">To</label><input class="form-control input-sm" type="date" name="to_date" id="packaging-to-date" value="{{ request('to_date') }}"></div>
                                <div class="col-sm-2 form-group"><label>&nbsp;</label><button class="btn btn-primary btn-sm" type="submit"><i class="fa fa-search"></i> Search</button> <a class="btn btn-default btn-sm" href="{{ route('packaging.show-available-orders') }}">Reset</a></div>
                            </div>
                        </form>

                        <form method="get" action="{{ route('packaging.show-order-cart') }}" id="packaging-worklist-form">
                            <div class="row" style="margin-bottom:12px">
                                <div class="col-sm-4"><label class="radio-inline"><input type="radio" name="packaging_mode" value="bulk" checked> Bulk / Lot-wise</label><label class="radio-inline"><input type="radio" name="packaging_mode" value="sample"> Sample multi-order</label></div>
                                <div class="col-sm-4 text-muted">Select items for one customer. Sample mode permits multiple Sale Orders in one Packaging Order.</div>
                                <div class="col-sm-4 text-right"><button class="btn btn-success btn-sm" type="submit">Open Packaging Cart</button></div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped table-hover">
                                    <thead><tr class="info"><th>Select</th><th>Customer / Sale Order</th><th>Type / Priority</th><th>Item / Quality</th><th>Shade / Coating</th><th>Required</th><th>Already Packed</th><th>Pending</th><th>Packaging Status</th><th>Actions</th></tr></thead>
                                    <tbody>
                                    @forelse ($worklist as $saleOrderItem)
                                        @php($orders = $packagingItems->get($saleOrderItem->id, collect()))
                                        <tr data-customer="{{ $saleOrderItem->saleOrder->customer_id ?? '' }}">
                                            <td>@if((float) $saleOrderItem->packaging_remaining_quantity > 0)<input type="checkbox" class="packaging-worklist-item" name="sale_order_item_ids[]" value="{{ $saleOrderItem->id }}">@endif</td>
                                            <td>{{ $saleOrderItem->saleOrder->customer->name ?? $saleOrderItem->saleOrder->customer->individual_name ?? '-' }}<br><small>{{ $saleOrderItem->saleOrder->sale_order_number ?? '-' }}</small></td>
                                            <td>{{ $saleOrderItem->development_type }}<br><span class="label label-default">{{ $saleOrderItem->order_item_priority ?: '-' }}</span></td>
                                            <td>{{ $saleOrderItem->item->item_name ?? $saleOrderItem->item_name ?? '-' }}<br><small>{{ $saleOrderItem->grey_quality ?: ($saleOrderItem->itemType->item_type_name ?? '-') }}</small></td>
                                            <td>{{ $saleOrderItem->dyeing_color ?: '-' }}<br><small>{{ $saleOrderItem->coating_type ?: '-' }}</small></td>
                                            <td>{{ number_format((float) $saleOrderItem->meter, 2) }}</td><td>{{ number_format((float) $saleOrderItem->packaging_packed_quantity, 2) }}</td><td>{{ number_format((float) $saleOrderItem->packaging_remaining_quantity, 2) }}</td>
                                            <td><span class="label label-{{ $saleOrderItem->packaging_state === 'packed' ? 'success' : ($saleOrderItem->packaging_state === 'partial' ? 'warning' : 'default') }}">{{ ucfirst($saleOrderItem->packaging_state) }}</span></td>
                                            <td>@if((float) $saleOrderItem->packaging_remaining_quantity > 0)<a class="btn btn-success btn-xs" href="{{ route('packaging.open-cart-for-sale-order-item', ['saleOrderItem' => $saleOrderItem->id, 'packaging_mode' => strtolower($saleOrderItem->development_type) === 'sample' ? 'sample' : 'bulk']) }}">{{ $saleOrderItem->packaging_state === 'partial' ? 'Add Partial' : 'Start' }}</a>@endif @forelse($orders as $orderItem)<a class="btn btn-primary btn-xs" href="{{ route('packaging.show-order-details', $orderItem->packaging_order_id) }}">View PKG-{{ $orderItem->packaging_order_id }}</a>@empty @if((float) $saleOrderItem->packaging_remaining_quantity <= 0)-@endif @endforelse</td>
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
