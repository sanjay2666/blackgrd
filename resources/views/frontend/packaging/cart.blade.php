<!DOCTYPE html>
<html lang="en">
<head>@include('frontend.common.head', ['pageTitle' => 'Packaging Cart | Loomexa'])</head>
<body class="hold-transition sidebar-mini">
<div class="wrapper">
    @include('frontend.common.header')
    <div class="content-wrapper">
        <section class="content">
            <div class="row"><div class="col-sm-12">{!! display_message('message') !!}</div></div>
            <form method="post" action="{{ route('packaging.store-packaging-order') }}" id="packaging-cart-form">
                @csrf
                <input type="hidden" name="packaging_mode" value="{{ $packagingMode }}">
                @foreach ($saleOrderItems as $saleOrderItem)<input type="hidden" name="sale_order_item_ids[]" value="{{ enc($saleOrderItem->id) }}">@endforeach
                <div class="row">
                    <div class="col-md-3">
                        <div class="panel panel-bd lobidrag">
                            <div class="panel-heading"><h4>Pending Orders / Items</h4></div>
                            <div class="panel-body" style="max-height:620px;overflow:auto">
                                <p><strong>Customer:</strong><br>{{ $customer->name ?? $customer->individual_name ?? '-' }}</p>
                                @foreach ($saleOrderItems as $saleOrderItem)
                                    <div class="well well-sm" style="margin-bottom:8px">
                                        <strong>{{ $saleOrderItem->saleOrder->sale_order_number ?? '-' }}</strong> <span class="label label-info">{{ $saleOrderItem->development_type }}</span><br>
                                        {{ $saleOrderItem->item->item_name ?? $saleOrderItem->item_name }}<br>
                                        <small>{{ $saleOrderItem->grey_quality ?: '-' }} | {{ $saleOrderItem->dyeing_color ?: '-' }} | {{ $saleOrderItem->coating_type ?: '-' }}</small><br>
                                        <strong>Still packable: {{ number_format((float) $saleOrderItem->packaging_remaining_quantity, 2) }}</strong>
                                    </div>
                                @endforeach
                                <a class="btn btn-default btn-sm" href="{{ route('packaging.show-available-orders') }}">Change selection</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="panel panel-bd lobidrag">
                            <div class="panel-heading"><h4>Available Lot / Roll / Taka Stock</h4></div>
                            <div class="panel-body">
                                <div class="row" style="margin-bottom:12px"><div class="col-sm-12"><label>Warehouse filter</label><select class="form-control input-sm" id="warehouse-filter"><option value="">All matching warehouses</option>@foreach ($warehouses as $warehouse)<option value="{{ enc($warehouse->id) }}" @selected((int) $warehouseId === (int) $warehouse->id)>{{ $warehouse->warehouse_name }}</option>@endforeach</select></div></div>
                                <p class="text-muted">{{ ucfirst($packagingMode) }} mode. Roll/Taka can be partially packed; a Roll cannot be used twice in this cart.</p>
                                @forelse ($saleOrderItems as $saleOrderItem)
                                    @php($encryptedSaleOrderItemId = enc($saleOrderItem->id))
                                    <div class="panel panel-default packaging-source-group" data-sale-order-item="{{ $encryptedSaleOrderItemId }}"><div class="panel-heading"><strong>{{ $saleOrderItem->saleOrder->sale_order_number }} — {{ $saleOrderItem->item->item_name ?? $saleOrderItem->item_name }}</strong></div><div class="panel-body" style="padding:8px">
                                        @forelse (($stockGroups->get($saleOrderItem->id, collect())) as $lotNumber => $lotStocks)
                                            <h5 style="margin:8px 0">Lot: {{ $lotNumber }} <span class="lot-running-total text-muted" data-lot="{{ $encryptedSaleOrderItemId }}-{{ $lotNumber }}">0.00 Mtr</span></h5>
                                            <div class="table-responsive"><table class="table table-bordered table-condensed"><thead><tr class="info"><th>Add</th><th>Warehouse</th><th>Roll</th><th>Taka</th><th>Available</th><th>Pack Meter</th></tr></thead><tbody>
                                            @foreach ($lotStocks as $stock)
                                                <tr class="stock-row" data-warehouse="{{ enc($stock->warehouse_id) }}" data-stock="{{ enc($stock->id) }}" data-source="{{ $encryptedSaleOrderItemId }}" data-lot="{{ $lotNumber }}" data-roll="{{ $stock->packet_number ?: 'ROL-'.$stock->id }}" data-taka="{{ $stock->insp_taka_number ?: '-' }}" data-available="{{ $stock->packaging_available_quantity }}">
                                                    <td><input type="checkbox" class="add-roll"></td><td>{{ $stock->Warehouse->warehouse_name ?? '-' }}<br><small>{{ $stock->WarehouseCompartment->warehouse_compartment_name ?? '' }}</small></td><td>{{ $stock->packet_number ?: 'ROL-'.$stock->id }}</td><td>{{ $stock->insp_taka_number ?: '-' }}</td><td class="available-meter">{{ number_format((float) $stock->packaging_available_quantity, 2) }}</td><td><input class="form-control input-sm pack-meter" type="number" min="0.01" max="{{ $stock->packaging_available_quantity }}" step="0.01" disabled></td>
                                                </tr>
                                            @endforeach
                                            </tbody></table></div>
                                        @empty
                                            <div class="alert alert-warning">No matching available Roll/Taka stock for this item.</div>
                                        @endforelse
                                    </div></div>
                                @empty
                                    <div class="alert alert-info">No selected Sale Order Item is available.</div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="panel panel-success" style="position:sticky;top:10px">
                            <div class="panel-heading"><h4>Packaging Cart</h4></div>
                            <div class="panel-body">
                                <p><strong>Customer:</strong> {{ $customer->name ?? $customer->individual_name ?? '-' }}<br><strong>Mode:</strong> {{ ucfirst($packagingMode) }}</p>
                                <div class="form-group"><label>Packaging Type</label><select class="form-control input-sm" name="packaging_type_id" required><option value="">Select</option>@foreach ($packagingTypes as $type)<option value="{{ enc($type->id) }}">{{ $type->name }}</option>@endforeach</select></div>
                                <div class="form-group"><label>Parcels</label><input class="form-control input-sm" type="number" name="parcel_count" min="1"></div>
                                <div class="form-group"><label>Remarks</label><textarea class="form-control input-sm" name="remarks" maxlength="1000" rows="2"></textarea></div>
                                <div id="cart-lines" style="max-height:330px;overflow:auto"><p class="text-muted">No Roll/Taka added.</p></div>
                                <hr>
                                <table class="table table-condensed"><tbody><tr><th>Orders</th><td id="cart-order-count">0</td></tr><tr><th>Items</th><td id="cart-item-count">0</td></tr><tr><th>Lots</th><td id="cart-lot-count">0</td></tr><tr><th>Rolls</th><td id="cart-roll-count">0</td></tr><tr><th>Total Meter</th><td id="cart-total-meter">0.00</td></tr></tbody></table>
                                <button class="btn btn-success btn-block" type="submit">Save Packaging</button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </section>
    </div>
    @include('frontend.common.footer')
</div>
@include('frontend.common.footerscript')
<script>
(function ($) {
    function refreshCart() {
        var rows = $('.stock-row .add-roll:checked').closest('.stock-row'), lines = '', orders = {}, items = {}, lots = {}, total = 0;
        $('.lot-running-total').text('0.00 Mtr');
        rows.each(function () {
            var row = $(this), meter = parseFloat(row.find('.pack-meter').val()) || 0, available = parseFloat(row.data('available')) || 0;
            if (meter > available) { row.addClass('danger'); } else { row.removeClass('danger'); }
            orders[row.closest('.packaging-source-group').find('.panel-heading').text()] = true;
            items[row.data('source')] = true; lots[row.data('source') + '-' + row.data('lot')] = true; total += meter;
            var lotTotal = 0; rows.filter('[data-source="' + row.data('source') + '"][data-lot="' + row.data('lot') + '"]').each(function () { lotTotal += parseFloat($(this).find('.pack-meter').val()) || 0; });
            $('.lot-running-total[data-lot="' + row.data('source') + '-' + row.data('lot') + '"]').text(lotTotal.toFixed(2) + ' Mtr');
            lines += '<div class="cart-line"><strong>' + row.data('lot') + '</strong><br>' + row.data('roll') + ' / ' + row.data('taka') + '<br><small>' + meter.toFixed(2) + ' of ' + available.toFixed(2) + ' Mtr</small><input type="hidden" name="warehouse_item_stock_ids[]" value="' + row.data('stock') + '"><input type="hidden" name="allocation_sale_order_item_ids[]" value="' + row.data('source') + '"><input type="hidden" name="quantities[]" value="' + meter.toFixed(2) + '"></div><hr style="margin:6px 0">';
        });
        $('#cart-lines').html(lines || '<p class="text-muted">No Roll/Taka added.</p>');
        $('#cart-order-count').text(Object.keys(orders).length); $('#cart-item-count').text(Object.keys(items).length); $('#cart-lot-count').text(Object.keys(lots).length); $('#cart-roll-count').text(rows.length); $('#cart-total-meter').text(total.toFixed(2));
    }
    $(document).on('change input', '.add-roll, .pack-meter', function () {
        var row = $(this).closest('.stock-row'), checked = row.find('.add-roll').is(':checked'), input = row.find('.pack-meter');
        input.prop('disabled', !checked); if (!checked) input.val(''); refreshCart();
    });
    function refreshAvailableStock() {
        var warehouse = $('#warehouse-filter').val(), itemIds = $('input[name="sale_order_item_ids[]"]').map(function () { return $(this).val(); }).get();
        $.ajax({ url: @json(route('packaging.get-available-stock')), type: 'GET', dataType: 'json', data: {sale_order_item_ids: itemIds, warehouse_id: warehouse} }).done(function (response) {
            var availability = {};
            $.each(response.stocks || [], function (_, stock) { availability[String(stock.sale_order_item_id) + '-' + String(stock.id)] = stock; });
            $('.stock-row').each(function () {
                var row = $(this), key = String(row.data('source')) + '-' + String(row.data('stock')), stock = availability[key], visible = !!stock && (!warehouse || String(row.data('warehouse')) === String(warehouse));
                row.toggle(visible);
                if (!stock) { row.find('.add-roll').prop('checked', false); row.find('.pack-meter').val('').prop('disabled', true); return; }
                row.data('available', stock.available_quantity); row.find('.available-meter').text(Number(stock.available_quantity).toFixed(2)); row.find('.pack-meter').attr('max', stock.available_quantity);
                if ((parseFloat(row.find('.pack-meter').val()) || 0) > stock.available_quantity) { row.find('.pack-meter').val(stock.available_quantity); }
            });
            refreshCart();
        }).fail(function (xhr) { alert((xhr.responseJSON && xhr.responseJSON.message) || 'Current packaging stock could not be refreshed.'); });
    }
    $('#warehouse-filter').on('change', refreshAvailableStock);
    $('#packaging-cart-form').on('submit', function (event) {
        var invalid = false; $('.stock-row .add-roll:checked').each(function () { var row = $(this).closest('.stock-row'), meter = parseFloat(row.find('.pack-meter').val()) || 0, available = parseFloat(row.data('available')) || 0; if (meter <= 0 || meter > available) invalid = true; });
        if (!$('.stock-row .add-roll:checked').length || invalid) { event.preventDefault(); alert('Add at least one Roll/Taka and enter a meter within its available quantity.'); return; }
        $(this).find('button[type="submit"]').prop('disabled', true).text('Saving...');
    });
}(jQuery));
</script>
</body>
</html>
