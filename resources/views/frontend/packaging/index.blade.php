<!DOCTYPE html>
<html lang="en">
<head>@include('frontend.common.head', ['pageTitle' => 'Packaging Worklist | Loomexa'])</head>
<body class="hold-transition sidebar-mini">
<div class="wrapper">
    @include('frontend.common.header')
    <div class="content-wrapper">
        <section class="content">
            <div class="row"><div class="col-sm-12">
                {!! display_message('message') !!}
                <div class="panel panel-bd lobidrag">
                    <div class="panel-heading"><div class="btn-group"><a href="{{ route('packaging.index') }}"><h4>Packaging Worklist</h4></a></div></div>
                    <div class="panel-body">
                        <form method="get" action="{{ route('packaging.index') }}" class="row" style="margin-bottom:12px">
                            <div class="col-sm-2"><select class="form-control input-sm" name="customer_id"><option value="">Customer</option>@foreach ($customers as $customer)<option value="{{ $customer->id }}" @selected((int) request('customer_id') === (int) $customer->id)>{{ $customer->name ?? $customer->individual_name }}</option>@endforeach</select></div>
                            <div class="col-sm-2"><input class="form-control input-sm" name="sale_order" value="{{ request('sale_order') }}" placeholder="Sale Order"></div>
                            <div class="col-sm-1"><select class="form-control input-sm" name="development_type"><option value="">Type</option>@foreach (['Bulk', 'Sample', 'JobWork'] as $type)<option value="{{ $type }}" @selected(request('development_type') === $type)>{{ $type }}</option>@endforeach</select></div>
                            <div class="col-sm-2"><input class="form-control input-sm" name="item" value="{{ request('item') }}" placeholder="Item"></div>
                            <div class="col-sm-1"><input class="form-control input-sm" name="quality" value="{{ request('quality') }}" placeholder="Quality"></div>
                            <div class="col-sm-1"><input class="form-control input-sm" name="shade" value="{{ request('shade') }}" placeholder="Shade"></div>
                            <div class="col-sm-1"><select class="form-control input-sm" name="packaging_state"><option value="">State</option>@foreach (['pending' => 'Pending', 'partial' => 'Partial', 'packed' => 'Packed'] as $key => $label)<option value="{{ $key }}" @selected(request('packaging_state') === $key)>{{ $label }}</option>@endforeach</select></div>
                            <div class="col-sm-2"><button class="btn btn-default btn-sm" type="submit">Filter</button> <a class="btn btn-link btn-sm" href="{{ route('packaging.index') }}">Clear</a></div>
                        </form>
                        <form method="get" action="{{ route('packaging.cart') }}" id="packaging-worklist-form">
                            <div class="row" style="margin-bottom:12px">
                                <div class="col-sm-3"><label class="radio-inline"><input type="radio" name="packaging_mode" value="bulk" checked> Bulk / Lot-wise</label><label class="radio-inline"><input type="radio" name="packaging_mode" value="sample"> Sample multi-order</label></div>
                                <div class="col-sm-5 text-muted">Select one customer’s items. Sample mode permits multiple Sale Orders in the same physical package.</div>
                                <div class="col-sm-4 text-right"><button class="btn btn-success btn-sm" type="submit">Build Packaging Cart</button></div>
                            </div>
                            <div class="table-responsive"><table class="table table-bordered table-striped table-hover">
                                <thead><tr class="info"><th>Select</th><th>Customer</th><th>Sale Order</th><th>Type</th><th>Item / Quality</th><th>Shade / Coating</th><th>Order Mtr</th><th>Remaining</th><th>Status</th><th>Existing Package</th></tr></thead>
                                <tbody>
                                @forelse ($worklist as $saleOrderItem)
                                    @php($orders = $packagingItems->get($saleOrderItem->id, collect()))
                                    <tr>
                                        <td><input type="checkbox" class="packaging-worklist-item" name="sale_order_item_ids[]" value="{{ $saleOrderItem->id }}"></td>
                                        <td>{{ $saleOrderItem->saleOrder->customer->name ?? $saleOrderItem->saleOrder->customer->individual_name ?? '-' }}</td>
                                        <td>{{ $saleOrderItem->saleOrder->sale_order_number ?? '-' }}</td>
                                        <td>{{ $saleOrderItem->development_type }}</td>
                                        <td>{{ $saleOrderItem->item->item_name ?? $saleOrderItem->item_name ?? '-' }}<br><small>{{ $saleOrderItem->grey_quality ?: ($saleOrderItem->itemType->item_type_name ?? '') }}</small></td>
                                        <td>{{ $saleOrderItem->dyeing_color ?: '-' }} / {{ $saleOrderItem->coating_type ?: '-' }}</td>
                                        <td>{{ number_format((float) $saleOrderItem->meter, 2) }}</td>
                                        <td>{{ number_format((float) $saleOrderItem->packaging_remaining_quantity, 2) }}</td>
                                        <td><span class="label label-{{ $saleOrderItem->packaging_state === 'packed' ? 'success' : ($saleOrderItem->packaging_state === 'partial' ? 'warning' : 'default') }}">{{ ucfirst($saleOrderItem->packaging_state) }}</span></td>
                                        <td>@forelse ($orders as $orderItem)<a href="{{ route('packaging.show', $orderItem->packaging_order_id) }}">PKG-{{ $orderItem->packaging_order_id }}</a>@if (! $loop->last), @endif @empty - @endforelse</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="10" class="text-center">No Sale Order Item is currently sent to Packaging.</td></tr>
                                @endforelse
                                </tbody>
                            </table></div>
                        </form>
                    </div>
                </div>
            </div></div>
        </section>
    </div>
    @include('frontend.common.footer')
</div>
@include('frontend.common.footerscript')
<script>
$('#packaging-worklist-form').on('submit', function (event) {
    if (!$(this).find('.packaging-worklist-item:checked').length) {
        event.preventDefault();
        alert('Select at least one Sale Order Item for Packaging.');
    }
});
</script>
</body>
</html>
