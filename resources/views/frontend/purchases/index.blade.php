<!DOCTYPE html>
<html lang="en">
<head>
@include('frontend.common.head', ['pageTitle' => 'Received Purchases | Loomexa'])
</head>
<body class="hold-transition sidebar-mini">
<div id="preloader"><div id="status"></div></div>
<div class="wrapper">
@include('frontend.common.header')
<div class="content-wrapper">
    <section class="content">
        {!! display_message('message') !!}
        <div class="row">
            <div class="col-sm-12">
                <div class="panel panel-bd lobidrag">
                    <div class="panel-heading">
                        <div class="btn-group"><h4>Received Purchases</h4></div>
                        <div class="pull-right">
                            <a class="btn btn-add" href="{{ route('add-received-item-in-warehouse') }}"><i class="fa fa-plus"></i> Receive Item</a>
                        </div>
                    </div>
                    <div class="panel-body">
                        <form action="{{ route('show-purchases') }}" method="GET" role="search" autocomplete="off">
                            <div class="row">
                                <div class="col-sm-3 col-xs-12 form-group">
                                    <input type="text" class="form-control" name="vendorName" id="vendor_search" value="{{ $vendorName }}" placeholder="Vendor Name">
                                </div>
                                <div class="col-sm-2 col-xs-12 form-group">
                                    <input type="text" class="form-control" name="invoice_number" value="{{ $invoiceNumber }}" placeholder="Invoice No.">
                                </div>
                                <div class="col-sm-2 col-xs-12 form-group">
                                    <input type="text" class="form-control loomexa-datepicker" data-datepicker-max-date="0" name="from_date" value="{{ $fromDate }}" placeholder="From Date">
                                </div>
                                <div class="col-sm-2 col-xs-12 form-group">
                                    <input type="text" class="form-control loomexa-datepicker" data-datepicker-max-date="0" name="to_date" value="{{ $toDate }}" placeholder="To Date">
                                </div>
                                <div class="col-sm-1 col-xs-6 form-group">
                                    <button type="submit" class="btn btn-success btn-block"><i class="fa fa-search"></i></button>
                                </div>
                                <div class="col-sm-1 col-xs-6 form-group">
                                    <a href="{{ route('show-purchases') }}" class="btn btn-default btn-block"><i class="fa fa-refresh"></i></a>
                                </div>
                            </div>
                        </form>

                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-hover">
                                <thead>
                                    <tr class="info">
                                        <th>#</th>
                                        <th>PO</th>
                                        <th>Vendor</th>
                                        <th>Invoice</th>
                                        <th>Challan</th>
                                        <th>Receiving Date</th>
                                        <th>Items</th>
                                        <th>Total Qty</th>
                                        <th>Total Meter</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($dataP as $row)
                                        <tr>
                                            <td>{{ $row->id }}</td>
                                            <td>#{{ $row->purchase_order_id }}</td>
                                            <td>{{ $row->vendor->name ?? $row->vendor_name ?? '-' }}</td>
                                            <td>{{ $row->invoice_number }}</td>
                                            <td>{{ $row->challan_number ?: '-' }}</td>
                                            <td>{{ !empty($row->receiving_date) ? date('d-m-Y', strtotime($row->receiving_date)) : '-' }}</td>
                                            <td>{{ $row->items->count() }}</td>
                                            <td>{{ number_format((float) $row->total_qty, 2) }}</td>
                                            <td>{{ number_format((float) $row->total_meter, 2) }}</td>
                                            <td>
                                                <a href="{{ route('show-purchase', enc($row->id)) }}" class="btn btn-primary btn-xs" title="View"><i class="fa fa-eye"></i></a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="10" class="text-center text-muted">No received purchases found.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="pagination text-center">
                            {{ $dataP->links('vendor.pagination.bootstrap-4') }}
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
<script>
var siteUrl = "{{ url('/') }}";

$("#vendor_search").autocomplete({
    minLength: 0,
    source: siteUrl + "/list_individual?type=vendors",
    focus: function(event, ui) {
        $("#vendor_search").val(ui.item.name || ui.item.company_name);
        return false;
    },
    select: function(event, ui) {
        $("#vendor_search").val(ui.item.name || ui.item.company_name);
        return false;
    }
}).autocomplete("instance")._renderItem = function(ul, item) {
    return $("<li>").append($("<div>").text((item.name || item.company_name) + (item.gstin ? " - " + item.gstin : ""))).appendTo(ul);
};
</script>
</body>
</html>
