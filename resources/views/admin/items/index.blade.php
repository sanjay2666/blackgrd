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
					<h1>Items</h1><small>Items list</small>
				</div>
			</section>
			<section class="content">
				{!! display_message('message') !!}
				<div class="row">
					<div class="col-sm-12">
						<div class="panel panel-bd lobidrag">
							<div class="panel-heading">
								<div class="btn-group">
									<h4>Items List</h4>
								</div>
							</div>
							<div class="panel-body">
								<div class="row" style="margin-bottom:5px">
									<form action="{{ route('admin.items.index') }}" method="GET">
										<div class="col-sm-3 col-xs-12">
											<input type="text" name="qsearch" id="qsearch" value="{{ $qsearch }}" class="form-control" placeholder="Search by Name, Item Code, Internal Name etc..">
											<input type="hidden" name="itemId" id="itemId" value="{{ $itemId }}">
										</div>
										<div class="col-sm-2 col-xs-12">
											<select name="item_type_id" id="item_type_id" class="form-control">
												<option value="">Select Item Type</option>
												@foreach ($itemTypes as $itemType)
												<option value="{{ $itemType->item_type_id }}" @selected((string) $item_type_id===(string) $itemType->item_type_id)>{{ $itemType->item_type_name }}</option>
												@endforeach
											</select>
										</div>
										<div class="col-sm-2 col-xs-12"><select name="status" class="form-control"><option value="">All Statuses</option><option value="Active" @selected($status === 'Active')>Active</option><option value="Inactive" @selected($status === 'Inactive')>Inactive</option></select></div>
										<div class="col-sm-1 col-xs-12"><button type="submit" class="btn btn-success">Search</button></div>
									</form>
									<div class="col-sm-3"><a href="{{ route('admin.items.create') }}" class="btn btn-add"><i class="fa fa-plus"></i> Add Items</a></div>
								</div>
								<div class="table-responsive">
									<table class="table table-bordered table-striped table-hover">
										<thead>
											<tr class="info">
												<th>Item Name</th>
												<th>Item Code</th>
												<th>Internal Item Name</th>
												<th>Item Type</th>
												<th>Unit Type</th>
																				<th>HSN Code</th>
																				<th>GST</th>
												<th>Manage</th>
												<th>Status</th>
												<th>Action</th>
												<th>Delete</th>
											</tr>
										</thead>
										<tbody>
											@forelse ($items as $row)
											<tr id="items-row-{{ $row->item_id }}">
												<td>{{ $row->item_name }}</td>
												<td>{{ $row->item_code }}</td>
												<td>{{ $row->internal_item_name }}</td>
												<td>{{ $row->itemType->item_type_name ?? '' }}</td>
												<td>{{ $row->unitType->unit_type_name ?? '' }}</td>
																				<td>{{ $row->hsncode }}</td>
																				<td>{{ $row->gstRate->gst_rate ?? '' }}{{ $row->gstRate ? '%' : '' }}</td>
												<td>
													@if($row->item_type_id == '8')
													<a href="{{ route('admin.items.manage-yarn', enc($row->item_id)) }}" class="btn btn-success btn-xs">Manage Yarn</a>
													@else
													<span>N/A</span>
													@endif
												</td>
												<td>{{ $row->status }}</td>
												<td><a href="{{ route('admin.items.edit', enc($row->item_id)) }}"><i class="fa fa-pencil"></i></a></td>
												<td><button type="button" class="btn btn-danger btn-xs" onclick="deleteRecord('{{ enc($row->item_id) }}', {{ $row->item_id }})"><i class="fa fa-trash-o"></i></button></td>
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
									<span class="pagination-links">
										{{ $items->appends(['qsearch' => $qsearch, 'itemId' => $itemId, 'item_type_id' => $item_type_id, 'status' => $status])->links('vendor.pagination.bootstrap-4') }}
									</span>
									@if ($items->lastPage() > 1)
									<span class="manual-page-input" style="margin-left:15px">
										<label for="manualPageInput">Go to page:</label>
										<input type="number" id="manualPageInput" min="1" max="{{ $items->lastPage() }}" value="{{ $items->currentPage() }}" style="width:70px">
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
		var siteUrl = "{{ url('/') }}";

		$("#qsearch").autocomplete({
			minLength: 0,
			source: function(request, response) {
				$.ajax({
					url: siteUrl + "/fabric_list_item",
					dataType: "json",
					data: {
						term: request.term,
						item_type_id: $("#item_type_id").val()
					},
					success: function(data) {
						response(data);
					}
				});
			},
			focus: function(event, ui) {
				$("#qsearch").val(ui.item.item_name);
				return false;
			},
			select: function(event, ui) {
				$("#qsearch").val(ui.item.item_name);
				$("#itemId").val(ui.item.item_id);
				return false;
			}
		}).autocomplete("instance")._renderItem = function(ul, item) {
			return $("<li>")
				.append($("<div>").text(item.item_name))
				.appendTo(ul);
		};

		$("#goToPageButton").on("click", function() {
			var pageInput = $("#manualPageInput").val();
			var lastPage = {
				{
					$items - > lastPage()
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
				type: 'DELETE',
				url: '{{ url(' / admin / items ') }}/' + encodeURIComponent(id),
				data: {
					_token: '{{ csrf_token() }}'
				},
				success: function() {
					$('#items-row-' + rowId).hide();
				},
				error: function() {
					alert('Record delete nahi ho paya. Please try again.');
				}
			});
		}
	</script>
</body>

</html>
