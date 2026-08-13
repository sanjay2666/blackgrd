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
                        <div class="panel-heading"><div class="btn-group"><a href="{{ route('packaging.index') }}"><h4>Packaging Order #{{ $packagingOrder->id }}</h4></a></div></div>
                        <div class="panel-body">
                            <div class="row">
                                <div class="col-sm-3"><strong>Status:</strong> {{ ucfirst($packagingOrder->packaging_status) }}</div>
                                <div class="col-sm-3"><strong>Allocated:</strong> {{ number_format((float) $packagingOrder->allocated_quantity, 2) }}</div>
                                <div class="col-sm-2"><strong>Packed:</strong> {{ number_format((float) $packagingOrder->packed_quantity, 2) }}</div>
                                <div class="col-sm-2"><strong>Dispatched:</strong> {{ number_format((float) $packagingOrder->dispatched_quantity, 2) }}</div>
                                <div class="col-sm-2"><strong>Remaining:</strong> {{ number_format((float) $packagingOrder->remaining_quantity, 2) }}</div>
                            </div>
                            @if ($packagingOrder->remarks)<p class="text-muted" style="margin-top:10px">{{ $packagingOrder->remarks }}</p>@endif
                            <hr>
                            @foreach ($packagingOrder->items as $item)
                                <h4>{{ $item->saleOrderItem->item->item_name ?? '-' }} <small>{{ $item->packagingType->name ?? '-' }}</small></h4>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped">
                                        <thead><tr class="info"><th>Warehouse</th><th>Roll</th><th>Taka</th><th>Allocated</th><th>Accepted</th><th>Packed</th><th>Dispatched</th><th>Remaining</th><th>Warehouse OUT</th><th>Status</th></tr></thead>
                                        <tbody>
                                            @foreach ($item->rollAllocations as $allocation)
                                                <tr>
                                                    <td>{{ $allocation->warehouseItemStock->Warehouse->warehouse_name ?? '-' }}</td>
                                                    <td>{{ $allocation->packet_number ?: 'ROL-'.$allocation->warehouse_item_stock_id }}</td>
                                                    <td>{{ $allocation->insp_taka_number ?: '-' }}</td>
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
                                <form method="post" action="{{ route('packaging.accept', $packagingOrder->id) }}" class="form-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-success">Accept and Issue Warehouse Stock</button>
                                </form>
                            @endif
                            @if (in_array($packagingOrder->packaging_status, ['accepted', 'packed']))
                                <hr>
                                <h4>Record Packed Quantity</h4>
                                <form method="post" action="{{ route('packaging.pack', $packagingOrder->id) }}">
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
                                <form method="post" action="{{ route('packaging.reverse', $packagingOrder->id) }}" class="form-inline">
                                    @csrf
                                    <label for="reversal_reason">Cancellation / unpack reason</label>
                                    <input class="form-control" id="reversal_reason" name="reversal_reason" maxlength="1000" required>
                                    <button type="submit" class="btn btn-danger">Cancel / Return to Warehouse</button>
                                </form>
                            @endif
                            <p class="text-muted" style="margin-top:15px">Sales Challan, Transport and Invoice remain outside this module. No sale delivery quantity is changed here.</p>
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
