<!DOCTYPE html>
<html lang="en">
<head>@include('frontend.common.head', ['pageTitle' => 'Create Sales Challan | Loomexa'])</head>
<body class="hold-transition sidebar-mini">
<div class="wrapper">
    @include('frontend.common.header')
    <div class="content-wrapper"><section class="content"><div class="row"><div class="col-sm-12">
        {!! display_message('message') !!}
        <form method="post" action="{{ route('sales-challans.store') }}" id="salesChallanForm">
            @csrf
            <input type="hidden" name="submission_key" value="{{ \Illuminate\Support\Str::uuid() }}">
            <div class="row"><div class="col-sm-8"><div class="panel panel-bd lobidrag">
                <div class="panel-heading"><h4>Ready for Dispatch: Packaging / Lot / Roll / Taka</h4></div>
                <div class="panel-body table-responsive"><table class="table table-bordered table-condensed">
                    <thead><tr class="info"><th></th><th>Customer / SO</th><th>Item / Detail</th><th>Lot</th><th>Roll / Taka</th><th>Packed</th><th>Dispatched</th><th>Available</th><th>Dispatch Mtr</th></tr></thead>
                    <tbody>@forelse($allocations as $allocation)<tr data-customer="{{ enc($allocation->packagingOrder->customer_id) }}" data-customer-name="{{ $allocation->packagingOrder->customer?->name }}">
                        <td><input type="checkbox" class="dispatch-check"></td>
                        <td>{{ $allocation->packagingOrder->customer?->name ?: '-' }}<br><small>{{ $allocation->packagingOrderItem->saleOrderItem->saleOrder->sale_order_number ?? '-' }}</small></td>
                        <td>{{ $allocation->packagingOrderItem->item_name }}<br><small>{{ $allocation->packagingOrderItem->grey_quality }} | {{ $allocation->packagingOrderItem->dyeing_color }} | {{ $allocation->packagingOrderItem->coating_type }}</small></td>
                        <td>{{ $allocation->dyeing_lot_number ?: '-' }}</td>
                        <td>{{ $allocation->packet_number ?: 'ROL-'.$allocation->warehouse_item_stock_id }}<br><small>{{ $allocation->insp_taka_number ?: '-' }}</small></td>
                        <td>{{ number_format((float) $allocation->packed_quantity, 2) }}</td><td>{{ number_format((float) $allocation->dispatched_quantity, 2) }}</td>
                        <td class="available">{{ number_format((float) $allocation->available_to_dispatch, 2) }}</td>
                        <td><input disabled class="form-control input-sm dispatch-qty" type="number" min="0.01" max="{{ $allocation->available_to_dispatch }}" step="0.01" value="{{ $allocation->available_to_dispatch }}" data-id="{{ enc($allocation->id) }}"></td>
                    </tr>@empty<tr><td colspan="9" class="text-center text-muted">No packed Packaging Roll/Taka is available for dispatch.</td></tr>@endforelse</tbody>
                </table></div>
            </div></div>
            <div class="col-sm-4"><div class="panel panel-success"><div class="panel-heading"><h4>Dispatch Cart</h4></div><div class="panel-body">
                <p><strong>Customer:</strong> <span id="cartCustomer">-</span></p>
                <p><strong>Sale Orders:</strong> <span id="cartOrders">0</span> | <strong>Lots:</strong> <span id="cartLots">0</span> | <strong>Rolls:</strong> <span id="cartRolls">0</span></p>
                <h3>Total Meter: <span id="cartTotal">0.00</span></h3>
                <p class="text-muted">One customer per Challan. Exact meter is revalidated under database locks when it is created and posted.</p>
            </div></div></div></div>
            <div class="panel panel-bd lobidrag"><div class="panel-heading"><h4>Challan, Delivery and Transport Details</h4></div><div class="panel-body">
                <div class="row"><div class="col-sm-3 form-group"><label>Challan Date *</label><input class="form-control" type="date" name="challan_date" value="{{ old('challan_date', now()->toDateString()) }}" required></div>
                    <div class="col-sm-3 form-group"><label>Dispatch Date</label><input class="form-control" type="date" name="dispatch_date" value="{{ old('dispatch_date', now()->toDateString()) }}"></div>
                    @php($selectedTransporterId = old('transporter_id') ? (int) dec((string) old('transporter_id')) : null)<div class="col-sm-3 form-group"><label>Transporter</label><select class="form-control" name="transporter_id"><option value="">Self / not specified</option>@foreach($transporters as $transporter)<option value="{{ enc($transporter->id) }}" @selected($selectedTransporterId === (int) $transporter->id)>{{ $transporter->name }}{{ $transporter->phone ? ' - '.$transporter->phone : '' }}</option>@endforeach</select></div>
                    <div class="col-sm-3 form-group"><label>Parcel Count</label><input class="form-control" type="number" min="0" name="parcel_count" value="{{ old('parcel_count') }}"></div></div>
                <div class="row"><div class="col-sm-3 form-group"><label>From Station</label><input class="form-control" name="from_station" value="{{ old('from_station') }}"></div><div class="col-sm-3 form-group"><label>To Station</label><input class="form-control" name="to_station" value="{{ old('to_station') }}"></div><div class="col-sm-3 form-group"><label>LR / Bilty / GR No.</label><input class="form-control" name="lr_number" value="{{ old('lr_number') }}"></div><div class="col-sm-3 form-group"><label>LR Date</label><input class="form-control" type="date" name="lr_date" value="{{ old('lr_date') }}"></div></div>
                <div class="row"><div class="col-sm-4 form-group"><label>Vehicle Number</label><input class="form-control" name="vehicle_number" value="{{ old('vehicle_number') }}"></div><div class="col-sm-4 form-group"><label>Driver Name</label><input class="form-control" name="driver_name" value="{{ old('driver_name') }}"></div><div class="col-sm-4 form-group"><label>Driver Contact</label><input class="form-control" name="driver_contact" value="{{ old('driver_contact') }}"></div></div>
                <div class="row"><div class="col-sm-6 form-group"><label>Billing Address Snapshot</label><textarea class="form-control" rows="3" name="billing_address">{{ old('billing_address') }}</textarea></div><div class="col-sm-6 form-group"><label>Shipping / Delivery Address Snapshot</label><textarea class="form-control" rows="3" name="shipping_address">{{ old('shipping_address') }}</textarea></div></div>
                <div class="form-group"><label>Remarks</label><textarea class="form-control" rows="2" name="remarks">{{ old('remarks') }}</textarea></div>
                <button class="btn btn-success" id="createChallan" disabled><i class="fa fa-file-text"></i> Create Draft Sales Challan</button>
                <a class="btn btn-default" href="{{ route('sales-challans.index') }}">Back</a>
            </div></div>
        </form>
    </div></div></section></div>
    @include('frontend.common.footer')
</div>
@include('frontend.common.footerscript')
<script>
$(function () {
    function refresh() {
        var customerId = '', customerName = '', orders = {}, lots = {}, rolls = 0, total = 0, valid = true;
        $('.dispatch-check:checked').each(function () {
            var row = $(this).closest('tr'), qty = parseFloat(row.find('.dispatch-qty').val()) || 0, max = parseFloat(row.find('.dispatch-qty').attr('max')) || 0;
            customerId = customerId || row.data('customer'); customerName = customerName || row.data('customer-name');
            if (customerId != row.data('customer') || qty <= 0 || qty > max + 0.0001) valid = false;
            orders[row.find('td:eq(1) small').text()] = 1; lots[row.find('td:eq(3)').text()] = 1; rolls++; total += qty;
        });
        $('#cartCustomer').text(customerName || '-'); $('#cartOrders').text(Object.keys(orders).length); $('#cartLots').text(Object.keys(lots).length); $('#cartRolls').text(rolls); $('#cartTotal').text(total.toFixed(2));
        $('#createChallan').prop('disabled', !valid || !rolls);
    }
    $('.dispatch-check').change(function () { $(this).closest('tr').find('.dispatch-qty').prop('disabled', !this.checked); refresh(); });
    $('.dispatch-qty').on('input', refresh);
    $('#salesChallanForm').submit(function () {
        var form = $(this); form.find('input[name^="packaging_roll_allocation_ids"],input[name^="dispatch_quantities"]').remove();
        $('.dispatch-check:checked').each(function () { var input = $(this).closest('tr').find('.dispatch-qty'); form.append($('<input>', {type: 'hidden', name: 'packaging_roll_allocation_ids[]', value: input.data('id')})); form.append($('<input>', {type: 'hidden', name: 'dispatch_quantities[]', value: input.val()})); });
        $('#createChallan').prop('disabled', true).text('Creating...');
    });
});
</script>
</body></html>
