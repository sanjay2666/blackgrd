<!DOCTYPE html>
<html lang="en">
<head>@include('frontend.common.head', ['pageTitle' => 'Packaging Order | Loomexa'])</head>
<body class="hold-transition sidebar-mini">
<div class="wrapper">
    @include('frontend.common.header')
    <div class="content-wrapper">
        <section class="content">
            <div class="row">
                <div class="col-sm-12">
                    {!! display_message('message') !!}
                    <div class="panel panel-bd lobidrag">
                        <div class="panel-heading"><div class="btn-group"><a href="{{ route('packaging.show-packaged-orders') }}"><h4>Packaging Order PKG-{{ $packagingOrder->id }}</h4></a></div><a class="btn btn-default btn-xs pull-right" target="_blank" href="{{ route('packaging.print-packaging-slip', $packagingOrder->id) }}"><i class="fa fa-print"></i> Print Slip</a><a class="btn btn-default btn-xs pull-right" style="margin-right:5px" href="{{ route('packaging.show-available-orders') }}">Packaging Available</a>@if(in_array($packagingOrder->packaging_status, ['packed', 'dispatched']) && $packagingOrder->dispatchable_quantity > 0)<a class="btn btn-success btn-xs pull-right" style="margin-right:5px" href="{{ route('sales-challans.create') }}">Sales Challan</a>@endif</div>
                        <div class="panel-body">
                            <div class="row">
                                <div class="col-sm-2"><strong>Customer:</strong> {{ $packagingOrder->customer->name ?? $packagingOrder->customer->individual_name ?? '-' }}</div>
                                <div class="col-sm-2"><strong>Mode:</strong> {{ ucfirst($packagingOrder->packaging_mode ?? 'bulk') }}</div>
                                <div class="col-sm-2"><strong>Status:</strong> {{ ucfirst($packagingOrder->packaging_status) }}</div>
                                <div class="col-sm-2"><strong>Parcels / Rolls / Lots:</strong> {{ $packagingOrder->parcel_count ?: '-' }} / {{ $packagingOrder->roll_count }} / {{ $packagingOrder->lot_count }}</div>
                                <div class="col-sm-2"><strong>Allocated / Packed:</strong> {{ number_format((float) $packagingOrder->allocated_quantity, 2) }} / {{ number_format((float) $packagingOrder->packed_quantity, 2) }}</div>
                                <div class="col-sm-2"><strong>Remaining:</strong> {{ number_format((float) $packagingOrder->remaining_quantity, 2) }}</div>
                            </div>
                            @if ($packagingOrder->remarks)<p class="text-muted" style="margin-top:10px">{{ $packagingOrder->remarks }}</p>@endif
                            <hr>
                            @foreach ($packagingOrder->items as $item)
                                <h4>{{ $item->saleOrderItem->saleOrder->sale_order_number ?? '-' }} — {{ $item->item_name ?: ($item->saleOrderItem->item->item_name ?? '-') }} <small>{{ $item->packagingType->name ?? '-' }}</small></h4>
                                <p class="text-muted">Quality: {{ $item->grey_quality ?: '-' }} | Shade: {{ $item->dyeing_color ?: '-' }} | Coating: {{ $item->coating_type ?: '-' }} | Final dispatch width: {{ $item->final_dispatch_width ?: '-' }} | Tube width: {{ $item->tube_width ?: '-' }} | {{ $item->lot_count }} Lot(s), {{ $item->roll_count }} Roll(s)</p>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped">
                                        <thead><tr class="info"><th>Lot</th><th>Warehouse</th><th>Roll</th><th>Taka</th><th>Source Available</th><th>Allocated</th><th>Accepted</th><th>Packed</th><th>Dispatched</th><th>Remaining</th><th>Warehouse OUT</th><th>Status</th></tr></thead>
                                        <tbody>
                                            @foreach ($item->rollAllocations as $allocation)
                                                <tr>
                                                    <td>{{ $allocation->dyeing_lot_number ?: '-' }}</td>
                                                    <td>{{ $allocation->warehouseItemStock->Warehouse->warehouse_name ?? '-' }}</td>
                                                    <td>{{ $allocation->packet_number ?: 'ROL-'.$allocation->warehouse_item_stock_id }}</td>
                                                    <td>{{ $allocation->insp_taka_number ?: '-' }}</td>
                                                    <td>{{ number_format((float) $allocation->source_available_quantity, 2) }}</td>
                                                    <td>{{ number_format((float) $allocation->allocated_quantity, 2) }}</td>
                                                    <td>{{ number_format((float) $allocation->accepted_quantity, 2) }}</td>
                                                    <td>{{ number_format((float) $allocation->packed_quantity, 2) }}</td>
                                                    <td>{{ number_format((float) $allocation->dispatched_quantity, 2) }}</td>
                                                    <td>{{ number_format((float) $allocation->remaining_quantity, 2) }}</td>
                                                    <td>{{ $allocation->warehouse_out_item_id ?: '-' }}</td>
                                                    <td>{{ ucfirst($allocation->allocation_status) }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endforeach
                            @if ($packagingOrder->packaging_status === 'draft')
                                <form method="post" action="{{ route('packaging.accept-warehouse-stock', $packagingOrder->id) }}" class="form-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-success">Accept and Issue Warehouse Stock</button>
                                </form>
                            @endif
                            @if (in_array($packagingOrder->packaging_status, ['accepted', 'packed']))
                                <hr>
                                <h4 id="pack-quantity">Record Packed Quantity</h4>
                                <form method="post" action="{{ route('packaging.update-packed-quantity', $packagingOrder->id) }}" class="packaging-action-form">
                                    @csrf
                                    <div class="table-responsive"><table class="table table-bordered"><thead><tr class="info"><th>Roll / Taka</th><th>Available to Pack</th><th>Pack Now</th></tr></thead><tbody>
                                        @foreach ($packagingOrder->items as $item)
                                            @foreach ($item->rollAllocations as $allocation)
                                                @php($availableToPack = (float) $allocation->accepted_quantity - (float) $allocation->packed_quantity - (float) $allocation->cancelled_quantity)
                                                @if ($availableToPack > 0)
                                                    <tr>
                                                        <td>{{ $allocation->packet_number ?: 'ROL-'.$allocation->warehouse_item_stock_id }} / {{ $allocation->insp_taka_number ?: '-' }}</td>
                                                        <td>{{ number_format($availableToPack, 2) }}</td>
                                                        <td><input type="hidden" name="packaging_roll_allocation_ids[]" value="{{ $allocation->id }}"><input class="form-control" type="number" name="packed_quantities[]" min="0.01" max="{{ $availableToPack }}" step="0.01" required></td>
                                                    </tr>
                                                @endif
                                            @endforeach
                                        @endforeach
                                    </tbody></table></div>
                                    <button type="submit" class="btn btn-primary">Save Packed Quantity</button>
                                </form>
                            @endif
                            @if (in_array($packagingOrder->packaging_status, ['draft', 'accepted', 'packed']))
                                <hr>
                                <form method="post" action="{{ route('packaging.cancel-and-restore-stock', $packagingOrder->id) }}" class="form-inline packaging-action-form">
                                    @csrf
                                    <label for="reversal_reason">Cancellation / unpack reason</label>
                                    <input class="form-control" id="reversal_reason" name="reversal_reason" maxlength="1000" required>
                                    <button type="submit" class="btn btn-danger">Cancel / Return to Warehouse</button>
                                </form>
                            @endif
                            <p class="text-muted" style="margin-top:15px">Packaging issues stock only on Warehouse acceptance. Customer dispatch remains in Sales Challan and does not issue Warehouse stock again.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
    @include('frontend.common.footer')
</div>
@include('frontend.common.footerscript')
<script>$(document).on('submit', '.packaging-action-form', function () { $(this).find('button[type="submit"]').prop('disabled', true).text('Processing...'); });</script>
</body>
</html>
