<!DOCTYPE html>
<html lang="en">
<head>@include('frontend.common.head', ['pageTitle' => 'Packaging Worklist | Loomexa'])</head>
<body class="hold-transition sidebar-mini">
<div class="wrapper">
    @include('frontend.common.header')
    <div class="content-wrapper">
        <section class="content">
            <div class="row">
                <div class="col-sm-12">
                    {!! display_message('message') !!}
                    <div class="panel panel-bd lobidrag">
                        <div class="panel-heading">
                            <div class="btn-group"><a href="{{ route('packaging.index') }}"><h4>Packaging Worklist</h4></a></div>
                        </div>
                        <div class="panel-body">
                            <p class="text-muted">Only Sale Order Items sent to Packaging are listed. Creating an allocation does not change delivered or pending sales quantity.</p>
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped table-hover">
                                    <thead>
                                        <tr class="info">
                                            <th>Sale Order</th>
                                            <th>Customer</th>
                                            <th>Item</th>
                                            <th>Colour / Coating</th>
                                            <th>Order Meter</th>
                                            <th>Packaging Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($worklist as $saleOrderItem)
                                            @php($orders = $packagingItems->get($saleOrderItem->id, collect()))
                                            <tr>
                                                <td>{{ $saleOrderItem->saleOrder->sale_order_number ?? '-' }}</td>
                                                <td>{{ $saleOrderItem->saleOrder->customer->name ?? $saleOrderItem->saleOrder->customer->individual_name ?? '-' }}</td>
                                                <td>{{ $saleOrderItem->item->item_name ?? $saleOrderItem->item_name ?? '-' }}<br><small>{{ $saleOrderItem->itemType->item_type_name ?? '' }}</small></td>
                                                <td>{{ $saleOrderItem->dyeing_color ?: '-' }} / {{ $saleOrderItem->coating_type ?: '-' }}</td>
                                                <td>{{ number_format((float) $saleOrderItem->meter, 2) }}</td>
                                                <td>
                                                    @forelse ($orders as $orderItem)
                                                        <a href="{{ route('packaging.show', $orderItem->packaging_order_id) }}">#{{ $orderItem->packaging_order_id }} {{ ucfirst($orderItem->packagingOrder->packaging_status ?? 'draft') }}</a>@if (! $loop->last)<br>@endif
                                                    @empty
                                                        <span class="label label-default">Ready for allocation</span>
                                                    @endforelse
                                                </td>
                                                <td><a class="btn btn-success btn-sm" href="{{ route('packaging.create', $saleOrderItem->id) }}">Allocate Roll/Taka</a></td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="7" class="text-center">No Sale Order Item is currently sent to Packaging.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
    @include('frontend.common.footer')
</div>
@include('frontend.common.footerscript')
</body>
</html>
