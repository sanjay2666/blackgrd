<!DOCTYPE html>
<html lang="en">

<head>
	@include('admin.common.head')
</head>

<body class="hold-transition sidebar-mini">
	<div id="preloader">
		<div id="status"></div>
	</div>
	<div class="wrapper">
		@include('admin.common.header')
		@include('admin.common.sidebar')
		<div class="content-wrapper">
			<section class="content-header">
				<div class="header-icon"><i class="fa fa-list"></i></div>
				<div class="header-title">
					<h1>Couriers</h1><small>Couriers list</small>
				</div>
			</section>
			<section class="content">
				{!! display_message('message') !!}
				<div class="row">
					<div class="col-sm-12">
						<div class="panel panel-bd lobidrag">
							<div class="panel-heading">
								<div class="btn-group">
									<h4>Couriers List</h4>
								</div>
							</div>
							<div class="panel-body">
								<div class="row" style="margin-bottom:5px">
									<form action="{{ route('admin.couriers.index') }}" method="GET" role="search">
										<div class="col-sm-4 col-xs-12"><input type="text" name="qsearch" value="{{ $qsearch }}" class="form-control" placeholder="Search by Customer, Item, Courier, Tracking"></div>
										<div class="col-sm-2 col-xs-12"><button type="submit" class="btn btn-success">Search</button></div>
									</form>
									<div class="col-sm-3 col-xs-12"><a href="{{ route('admin.couriers.create') }}" class="btn btn-add"><i class="fa fa-plus"></i> Add Courier</a></div>
								</div>
								<div class="table-responsive">
									<table class="table table-bordered table-striped table-hover">
										<thead>
											<tr class="info">
												<th>ID</th>
												<th>Customer</th>
												<th>Item</th>
												<th>Packing details</th>
												<th>Pack</th>
												<th>Courier</th>
												<th>Tracking #</th>
												<th>Date</th>
												<th>Status</th>
												<th>Action</th>
												<th>Delete</th>
											</tr>
										</thead>
										<tbody>
											@forelse ($couriers as $row)
											<tr id="couriers-row-{{ $row->id }}">
												<td>{{ $row->id }}</td>
												<td>{{ $row->cus_name }}</td>
												<td>{{ $row->item_name }}</td>
												<td>{{ $row->tot_mtr }}</td>
												<td>{{ $row->tot_pack }}</td>
												<td>{{ $row->courier_name }}</td>
												<td>{{ $row->tracking_number }}</td>
												<td>{{ $row->created ? \Carbon\Carbon::parse($row->created)->format('d-m-Y H:i:s') : '' }}</td>
												<td>{{ $row->status }}</td>
												<td><a href="{{ route('admin.couriers.edit', enc($row->id)) }}"><i class="fa fa-pencil"></i></a></td>
												<td><button type="button" class="btn btn-danger btn-xs" onclick="deleteRecord('{{ enc($row->id) }}', {{ $row->id }})"><i class="fa fa-trash-o"></i></button></td>
											</tr>
											@empty
											<tr>
												<td colspan="11" class="text-center">No records found.</td>
											</tr>
											@endforelse
										</tbody>
									</table>
								</div>
								<div class="pagination text-center" style="display:block">
									<span class="pagination-links">{{ $couriers->appends(['qsearch' => $qsearch])->links('vendor.pagination.bootstrap-4') }}</span>
									@if ($couriers->lastPage() > 1)
									<span class="manual-page-input" style="margin-left:15px">
										<label for="manualPageInput">Go to page:</label>
										<input type="number" id="manualPageInput" min="1" max="{{ $couriers->lastPage() }}" value="{{ $couriers->currentPage() }}" style="width:70px">
										<button type="button" class="btn btn-sm btn-success" id="goToPageButton">Go</button>
									</span>
									@endif
								</div>
							</div>
						</div>
					</div>
				</div>
			</section>
		</div>
		@include('admin.common.footer')
	</div>
	@include('admin.common.formfooterscript')
	<script>
		$("#goToPageButton").on("click", function() {
			var pageInput = $("#manualPageInput").val();
			var lastPage = {
				{
					$couriers - > lastPage()
				}
			};

			if (pageInput > 0 && pageInput <= lastPage) {
				var baseUrl = window.location.href.split("?")[0];
				var params = new URLSearchParams(window.location.search);
				params.set("page", pageInput);
				window.location.href = baseUrl + "?" + params.toString();
			}
		});

		function deleteRecord(id, rowId) {
			if (!confirm('Do you really want to delete this record?')) {
				return;
			}
			$.ajax({
				type: 'DELETE'
				, url: '{{ url(' / admin / couriers ') }}/' + encodeURIComponent(id)
				, data: {
					_token: '{{ csrf_token() }}'
				}
				, success: function() {
					$('#couriers-row-' + rowId).hide();
				}
				, error: function() {
					alert('Record delete nahi ho paya. Please try again.');
				}
			});
		}

	</script>
</body>

</html>
