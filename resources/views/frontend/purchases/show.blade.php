<!DOCTYPE html>
<html lang="en">
<head>
@include('frontend.common.head', ['pageTitle' => 'Received Purchase Details | Loomexa'])
</head>
<body class="hold-transition sidebar-mini">
<div id="preloader"><div id="status"></div></div>
<div class="wrapper">
@include('frontend.common.header')
<div class="content-wrapper">
    <section class="content">
        <div class="row">
            <div class="col-sm-12">
                <div class="panel panel-bd lobidrag">
                    <div class="panel-heading">
                        <div class="btn-group"><h4>Received Purchase #{{ $purchase->id }}</h4></div>
                        <div class="pull-right">
                            <a href="{{ route('show-purchases') }}" class="btn btn-default"><i class="fa fa-arrow-left"></i> Back</a>
                        </div>
                    </div>
                    <div class="panel-body">
                        <table class="table table-bordered">
                            <tbody>
                                <tr>
                                    <th>Purchase Order</th>
                                    <td>#{{ $purchase->purchase_order_id }}</td>
                                    <th>Vendor</th>
                                    <td>{{ $purchase->vendor->name ?? $purchase->vendor_name ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Invoice</th>
                                    <td>{{ $purchase->invoice_number }}</td>
                                    <th>Challan</th>
                                    <td>{{ $purchase->challan_number ?: '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Receiving Date</th>
                                    <td>{{ !empty($purchase->receiving_date) ? date('d-m-Y', strtotime($purchase->receiving_date)) : '-' }}</td>
                                    <th>Receiver</th>
                                    <td>{{ $purchase->receiver_name ?: '-' }}</td>
                                </tr>
                            </tbody>
                        </table>

                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-hover">
                                <thead>
                                    <tr class="info">
                                        <th>#</th>
                                        <th>Item</th>
                                        <th>Type</th>
                                        <th>Color</th>
                                        <th>Qty</th>
                                        <th>Meter</th>
                                        <th>Taka No.</th>
                                        <th>Warehouse</th>
                                        <th>Compartment</th>
                                        <th>Remark</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($purchase->items as $index => $item)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td>{{ $item->item->item_name ?? $item->item_name ?? '-' }}</td>
                                            <td>{{ $item->purchaseOrderItem->ItemType->item_type_name ?? $item->item_type_id }}</td>
                                            <td>{{ $item->dyeing_color ?: '-' }}</td>
                                            <td>{{ number_format((float) $item->qty, 2) }}</td>
                                            <td>{{ number_format((float) $item->meter, 2) }}</td>
                                            <td>{{ $item->taka_number ?: '-' }}</td>
                                            <td>{{ $item->warehouseItemStock->Warehouse->warehouse_name ?? '-' }}</td>
                                            <td>{{ $item->warehouseItemStock->WarehouseCompartment->compartment_name ?? '-' }}</td>
                                            <td>{{ $item->remarks ?: '-' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="10" class="text-center text-muted">No items found.</td>
                                        </tr>
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
