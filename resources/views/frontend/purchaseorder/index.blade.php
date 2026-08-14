<!DOCTYPE html>
<html lang="en">
<head>
@include('frontend.common.head', ['pageTitle' => 'Purchase Orders | Loomexa'])
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
						<div class="btn-group"><h4>Purchase Order List</h4></div>
						<div class="pull-right">
							<a class="btn btn-add" href="{{ route('add-purchaseorder') }}"><i class="fa fa-plus"></i> Add Purchase Order</a>
						</div>
					</div>
					<div class="panel-body">
						<form action="{{ route('show-purchaseorders') }}" method="GET" role="search" autocomplete="off">
							<div class="row">
								<div class="col-sm-3 col-xs-12 form-group">
									<input type="text" class="form-control" name="vendorName" id="vendor_search" value="{{ $vendorName }}" placeholder="Vendor Name">
								</div>
								<div class="col-sm-2 col-xs-12 form-group">
									<input type="text" class="form-control loomexa-datepicker" data-datepicker-max-date="0" name="from_date" value="{{ $fromDate }}" placeholder="From Date">
								</div>
								<div class="col-sm-2 col-xs-12 form-group">
									<input type="text" class="form-control loomexa-datepicker" data-datepicker-max-date="0" name="to_date" value="{{ $toDate }}" placeholder="To Date">
								</div>
								<div class="col-sm-1 col-xs-12 form-group">
									<button type="submit" class="btn btn-success btn-block"><i class="fa fa-search"></i></button>
								</div>
							</div>
						</form>

						<div class="table-responsive">
							<table class="table table-bordered table-striped table-hover">
								<thead>
									<tr class="info">
										<th>#</th>
										<th>Vendor</th>
										<th>Date</th>
										<th>Item Types</th>
										<th>Items</th>
										<th>Total</th>
										<th>Status</th>
										<th>Action</th>
									</tr>
								</thead>
								<tbody>
									@forelse ($dataP as $row)
									<tr id="Mid{{ $row->id }}">
										<td>{{ $row->id }}</td>
										<td>{{ $row->vendor->name ?? $row->vendor->company_name ?? '-' }}</td>
										<td>{{ !empty($row->purchased_on) ? $row->purchased_on->format('d-m-Y') : '-' }}</td>
										<td>
											@foreach ($row->PurchaseOrderItem->pluck('ItemType.item_type_name')->filter()->unique() as $typeName)
												<p>{{ $typeName }}</p>
											@endforeach
										</td>
										<td>{{ $row->PurchaseOrderItem->count() }}</td>
										<td>{{ number_format((float) $row->subtotal, 2) }}</td>
										<td>
											<span class="label label-info">{{ $row->document_status?->label() ?? 'Unmapped' }}</span>
										</td>
										<td>
											<a href="{{ route('edit-purchaseorder', enc($row->id)) }}" class="btn btn-primary btn-xs" title="Edit"><i class="fa fa-pencil"></i></a>
											<a target="_blank" href="{{ route('print-purchaseorder', enc($row->id)) }}" class="btn btn-default btn-xs" title="Print"><i class="fa fa-print"></i></a>
											<a href="javascript:void(0);" onclick="deletePurchaseOrder('{{ enc($row->id) }}', '{{ $row->id }}')" class="btn btn-danger btn-xs" title="Delete"><i class="fa fa-trash-o"></i></a>
										</td>
									</tr>
									@empty
									<tr>
										<td colspan="8" class="text-center text-muted">No purchase orders found.</td>
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

function deletePurchaseOrder(id, rowId) {
	if (!confirm("Do you really want to delete this purchase order?")) {
		return;
	}

	$.ajax({
		type: "POST",
		url: siteUrl + "/ajax_script/deletePurchaseOrder",
		data: {
			_token: "{{ csrf_token() }}",
			FId: id
		},
		success: function() {
			$("#Mid" + rowId).hide();
		}
	});
}
</script>
</body>
</html>
