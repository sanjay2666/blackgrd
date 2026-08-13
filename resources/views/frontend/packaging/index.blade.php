<!DOCTYPE html>
<html lang="en">
<head>@include('frontend.common.head', ['pageTitle' => 'Packaging Available | Loomexa'])</head>
<body class="hold-transition sidebar-mini"><div class="wrapper">
    @include('frontend.common.header')
    <div class="content-wrapper"><section class="content"><div class="row"><div class="col-sm-12">
        {!! display_message('message') !!}
        <div class="panel panel-bd lobidrag"><div class="panel-heading">
            <div class="btn-group"><a href="{{ route('packaging.show-available-orders') }}"><h4>Packaging Available / Pending Orders</h4></a></div>
            <a class="btn btn-default btn-sm pull-right" href="{{ route('packaging.show-packaged-orders') }}"><i class="fa fa-list"></i> Packaged Orders</a>
        </div><div class="panel-body">
            <form method="get" action="{{ route('packaging.show-available-orders') }}" class="row" style="margin-bottom:12px">
                <div class="col-sm-2"><select class="form-control input-sm" name="customer_id"><option value="">Customer</option>@foreach ($customers as $customer)<option value="{{ $customer->id }}" @selected((int) request('customer_id') === (int) $customer->id)>{{ $customer->name ?? $customer->individual_name }}</option>@endforeach</select></div>
                <div class="col-sm-2"><input class="form-control input-sm" name="sale_order" value="{{ request('sale_order') }}" placeholder="Sale Order"></div>
                <div class="col-sm-1"><select class="form-control input-sm" name="development_type"><option value="">Type</option>@foreach (['Bulk', 'Sample', 'JobWork'] as $type)<option value="{{ $type }}" @selected(request('development_type') === $type)>{{ $type }}</option>@endforeach</select></div>
                <div class="col-sm-2"><input class="form-control input-sm" name="item" value="{{ request('item') }}" placeholder="Item"></div>
                <div class="col-sm-1"><input class="form-control input-sm" name="quality" value="{{ request('quality') }}" placeholder="Quality"></div>
                <div class="col-sm-1"><input class="form-control input-sm" name="shade" value="{{ request('shade') }}" placeholder="Shade"></div>
                <div class="col-sm-1"><select class="form-control input-sm" name="packaging_state"><option value="">State</option>@foreach (['pending' => 'Pending', 'partial' => 'Partial', 'packed' => 'Packed'] as $key => $label)<option value="{{ $key }}" @selected(request('packaging_state') === $key)>{{ $label }}</option>@endforeach</select></div>
                <div class="col-sm-2"><button class="btn btn-default btn-sm" type="submit">Filter</button> <a class="btn btn-link btn-sm" href="{{ route('packaging.show-available-orders') }}">Clear</a></div>
            </form>
            <form method="get" action="{{ route('packaging.show-order-cart') }}" id="packaging-worklist-form">
                <div class="row" style="margin-bottom:12px"><div class="col-sm-4"><label class="radio-inline"><input type="radio" name="packaging_mode" value="bulk" checked> Bulk / Lot-wise</label><label class="radio-inline"><input type="radio" name="packaging_mode" value="sample"> Sample multi-order</label></div><div class="col-sm-4 text-muted">Select items for one customer. Sample mode permits multiple Sale Orders in one Packaging Order.</div><div class="col-sm-4 text-right"><button class="btn btn-success btn-sm" type="submit">Open Packaging Cart</button></div></div>
                <div class="table-responsive"><table class="table table-bordered table-striped table-hover"><thead><tr class="info"><th>Select</th><th>Customer / Sale Order</th><th>Type / Priority</th><th>Item / Quality</th><th>Shade / Coating</th><th>Required</th><th>Already Packed</th><th>Pending</th><th>Packaging Status</th><th>Actions</th></tr></thead><tbody>
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
                            <td>@if((float) $saleOrderItem->packaging_remaining_quantity > 0)<a class="btn btn-success btn-xs" href="{{ route('packaging.open-cart-for-sale-order-item', ['saleOrderItem' => $saleOrderItem->id, 'packaging_mode' => strtolower($saleOrderItem->development_type) === 'sample' ? 'sample' : 'bulk']) }}">{{ $saleOrderItem->packaging_state === 'partial' ? 'Add Partial' : 'Start' }}</a>@endif @forelse($orders as $orderItem)<a class="btn btn-primary btn-xs" href="{{ route('packaging.show-order-details', $orderItem->packaging_order_id) }}">PKG-{{ $orderItem->packaging_order_id }}</a>@empty - @endforelse</td>
                        </tr>
                    @empty<tr><td colspan="10" class="text-center">No Sale Order Item is currently sent to Packaging.</td></tr>@endforelse
                </tbody></table></div>
            </form>
            {{ $worklist->withQueryString()->links('vendor.pagination.bootstrap-4') }}
        </div></div>
    </div></div></section></div>
    @include('frontend.common.footer')
</div>
@include('frontend.common.footerscript')
<script>$('#packaging-worklist-form').on('submit', function (event) { var customers = {}; $(this).find('.packaging-worklist-item:checked').each(function () { customers[$(this).closest('tr').data('customer')] = true; }); if (!Object.keys(customers).length) { event.preventDefault(); alert('Select at least one Sale Order Item for Packaging.'); return; } if (Object.keys(customers).length !== 1) { event.preventDefault(); alert('One Packaging Order can contain Sale Order Items for one customer only.'); return; } $(this).find('button[type="submit"]').prop('disabled', true).text('Opening...'); });</script>
</body></html>
