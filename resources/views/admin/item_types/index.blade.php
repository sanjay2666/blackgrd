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
					<h1>Item Types</h1><small>Item Types list</small>
				</div>
			</section>
			<section class="content">
				{!! display_message('message') !!}
				<div class="row">
					<div class="col-sm-12">
						<div class="panel panel-bd lobidrag">
							<div class="panel-heading">
								<div class="btn-group">
									<h4>Item Types List</h4>
								</div>
							</div>
							<div class="panel-body">
								<div class="row" style="margin-bottom:5px">
									<form action="{{ route('admin.item-types.index') }}" method="GET">
										<div class="col-sm-4"><input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search"></div>
										<div class="col-sm-2"><button class="btn btn-add">Search</button></div>
									</form>
									<div class="col-sm-3"><a href="{{ route('admin.item-types.create') }}" class="btn btn-add"><i class="fa fa-plus"></i> Add Item Types</a></div>
								</div>
								<div class="table-responsive">
									<table class="table table-bordered table-striped table-hover">
										<thead>
											<tr class="info">
												<th>Item Type Name</th>
												<th>Unit Type</th>
												<th>Is Purchase</th>
												<th>Is Work</th>
												<th>Is Department</th>
												<th>Status</th>
												<th>Action</th>
												<th>Delete</th>
											</tr>
										</thead>
										<tbody>
											@forelse ($itemTypes as $row)
											<tr id="item_types-row-{{ $row->item_type_id }}">
												<td>{{ $row->item_type_name }}</td>
												<td>{{ $row->unitType->unit_type_name ?? '' }}</td>
												<td>{{ $row->is_purchase }}</td>
												<td>{{ $row->is_work }}</td>
												<td>{{ $row->is_department }}</td>
												<td>{{ $row->status }}</td>
												<td><a href="{{ route('admin.item-types.edit', enc($row->item_type_id)) }}"><i class="fa fa-pencil"></i></a></td>
												<td><button type="button" class="btn btn-danger btn-xs" onclick="deleteRecord('{{ enc($row->item_type_id) }}', {{ $row->item_type_id }})"><i class="fa fa-trash-o"></i></button></td>
											</tr>
											@empty
											<tr>
												<td colspan="8" class="text-center">No records found.</td>
											</tr>
											@endforelse
										</tbody>
									</table>
								</div>
								<div class="pagination">{{ $itemTypes->links() }}</div>
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
		function deleteRecord(id, rowId) {
			if (!confirm('Do you really want to delete this record?')) {
				return;
			}
			$.ajax({
				type: 'DELETE'
				, url: '{{ url(' / admin / item - types ') }}/' + encodeURIComponent(id)
				, data: {
					_token: '{{ csrf_token() }}'
				}
				, success: function() {
					$('#item_types-row-' + rowId).hide();
				}
				, error: function() {
					alert('Record delete nahi ho paya. Please try again.');
				}
			});
		}

	</script>
</body>

</html>
