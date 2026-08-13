<!DOCTYPE html>
<html lang="en">
<head>@include('frontend.common.head', ['pageTitle' => 'Allocate Packaging Rolls | Loomexa'])</head>
<body class="hold-transition sidebar-mini">
<div class="wrapper">
    @include('frontend.common.header')
    <div class="content-wrapper">
        <section class="content">
            <div class="row">
                <div class="col-sm-12">
                    {!! display_message('message') !!}
                    <div class="panel panel-bd lobidrag">
                        <div class="panel-heading"><div class="btn-group"><a href="{{ route('packaging.index') }}"><h4>Allocate Roll/Taka for Packaging</h4></a></div></div>
                        <div class="panel-body">
                            <div class="row">
                                <div class="col-sm-3"><strong>Sale Order:</strong> {{ $saleOrderItem->saleOrder->sale_order_number ?? '-' }}</div>
                                <div class="col-sm-3"><strong>Item:</strong> {{ $saleOrderItem->item->item_name ?? $saleOrderItem->item_name ?? '-' }}</div>
                                <div class="col-sm-2"><strong>Colour:</strong> {{ $saleOrderItem->dyeing_color ?: '-' }}</div>
                                <div class="col-sm-2"><strong>Coating:</strong> {{ $saleOrderItem->coating_type ?: '-' }}</div>
                                <div class="col-sm-2"><strong>Still packable:</strong> {{ number_format($remainingQuantity, 2) }}</div>
                            </div>
                            <hr>
                            <form method="get" action="{{ route('packaging.create', $saleOrderItem->id) }}" class="form-inline" style="margin-bottom:15px">
                                <label for="warehouse_id">Warehouse</label>
                                <select class="form-control" id="warehouse_id" name="warehouse_id">
                                    <option value="">All matching warehouses</option>
                                    @foreach ($warehouses as $warehouse)
                                        <option value="{{ $warehouse->id }}" @selected((int) $warehouseId === (int) $warehouse->id)>{{ $warehouse->warehouse_name }}</option>
                                    @endforeach
                                </select>
                                <button class="btn btn-default" type="submit">Filter</button>
                            </form>
                            @if ($remainingQuantity <= 0)
                                <div class="alert alert-info">The Sale Order Item is fully allocated to an active packaging order.</div>
                            @elseif ($packagingTypes->isEmpty())
                                <div class="alert alert-warning">No active Packaging Type is available for this company.</div>
                            @else
                                <form method="post" action="{{ route('packaging.store') }}">
                                    @csrf
                                    <input type="hidden" name="sale_order_item_id" value="{{ $saleOrderItem->id }}">
                                    <div class="row" style="margin-bottom:12px">
                                        <div class="col-sm-4">
                                            <label for="packaging_type_id">Packaging Type</label>
                                            <select class="form-control" name="packaging_type_id" id="packaging_type_id" required>
                                                <option value="">Select Packaging Type</option>
                                                @foreach ($packagingTypes as $packagingType)
                                                    <option value="{{ $packagingType->id }}" @selected((int) old('packaging_type_id') === (int) $packagingType->id)>{{ $packagingType->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-sm-8">
                                            <label for="remarks">Remarks</label>
                                            <input class="form-control" type="text" id="remarks" name="remarks" value="{{ old('remarks') }}" maxlength="1000">
                                        </div>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-striped">
                                            <thead><tr class="info"><th>Select</th><th>Warehouse</th><th>Roll</th><th>Taka</th><th>Lot</th><th>Physical Available</th><th>Available for Packaging</th><th>Meter to Allocate</th></tr></thead>
                                            <tbody>
                                                @forelse ($stocks as $stock)
                                                    <tr>
                                                        <td><input type="checkbox" class="packaging-stock-check" data-target="quantity-{{ $stock->id }}" name="warehouse_item_stock_ids[]" value="{{ $stock->id }}"></td>
                                                        <td>{{ $stock->Warehouse->warehouse_name ?? '-' }}<br><small>{{ $stock->WarehouseCompartment->warehouse_compartment_name ?? '' }}</small></td>
                                                        <td>{{ $stock->packet_number ?: 'ROL-'.$stock->id }}</td>
                                                        <td>{{ $stock->insp_taka_number ?: '-' }}</td>
                                                        <td>{{ $stock->dyeing_lot_number ?: '-' }}</td>
                                                        <td>{{ number_format((float) $stock->insp_bal_quan_size, 2) }}</td>
                                                        <td>{{ number_format((float) $stock->packaging_available_quantity, 2) }}</td>
                                                        <td><input class="form-control packaging-quantity" id="quantity-{{ $stock->id }}" type="number" name="quantities[]" min="0.01" max="{{ $stock->packaging_available_quantity }}" step="0.01" disabled></td>
                                                    </tr>
                                                @empty
                                                    <tr><td colspan="8" class="text-center">No matching available Roll/Taka stock was found.</td></tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                    @if ($stocks->isNotEmpty())
                                        <button class="btn btn-success" type="submit">Create Packaging Order</button>
                                        <a class="btn btn-default" href="{{ route('packaging.index') }}">Back</a>
                                    @endif
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
    @include('frontend.common.footer')
</div>
@include('frontend.common.footerscript')
<script>
document.querySelectorAll('.packaging-stock-check').forEach(function (checkbox) {
    checkbox.addEventListener('change', function () {
        var input = document.getElementById(this.dataset.target);
        input.disabled = !this.checked;
        input.required = this.checked;
        if (!this.checked) input.value = '';
    });
});
</script>
</body>
</html>
