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
				<div class="header-icon"><i class="fa fa-cogs"></i></div>
				<div class="header-title">
					<h1>Process Items</h1><small>Process list</small>
				</div>
			</section>
			<section class="content">
				{!! display_message('message') !!}
				<div class="row">
					<div class="col-sm-12">
						<div class="panel panel-bd lobidrag">
							<div class="panel-heading">
								<div class="btn-group">
									<h4>Process Item List</h4>
								</div>
							</div>
							<div class="panel-body">
								<div class="row" style="margin-bottom:5px">
									<form action="{{ route('admin.process-items.index') }}" method="GET">
										<div class="col-sm-4"><input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search process"></div>
										<div class="col-sm-2"><button class="btn btn-add">Search</button></div>
									</form>
									<div class="col-sm-3"><a href="{{ route('admin.process-items.create') }}" class="btn btn-add"><i class="fa fa-plus"></i> Add Process</a></div>
								</div>
								<div class="table-responsive">
									<table class="table table-bordered table-striped table-hover">
										<thead>
											<tr class="info">
												<th>Entry Name</th>
												<th>Process Name</th>
												<th>Output Name</th>
												<th>Last Sl No</th>
												<th>Status</th>
												<th>Action</th>
												<th>Delete</th>
											</tr>
										</thead>
										<tbody>
											@forelse ($processItems as $processItem)
											<tr id="process-row-{{ $processItem->id }}">
												<td>{{ $processItem->entry_name ?? '-' }}</td>
												<td>{{ $processItem->process_name }}</td>
												<td>{{ $processItem->output_name }}</td>
												<td>{{ $processItem->process_sl_no_last }}</td>
												<td>{{ $processItem->status }}</td>
												<td><a href="{{ route('admin.process-items.edit', enc($processItem->id)) }}"><i class="fa fa-pencil"></i></a></td>
												<td><button type="button" class="btn btn-danger btn-xs" onclick="deleteProcess('{{ enc($processItem->id) }}', {{ $processItem->id }})"><i class="fa fa-trash-o"></i></button></td>
											</tr>
											@empty
											<tr>
												<td colspan="7" class="text-center">No records found.</td>
											</tr>
											@endforelse
										</tbody>
									</table>
								</div>
								<div class="pagination">{{ $processItems->links() }}</div>
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
		function deleteProcess(id, rowId) {
			if (!confirm('Do you really want to delete this record?')) {
				return;
			}
			$.ajax({
				type: 'DELETE'
				, url: '{{ url(' / admin / process - items ') }}/' + encodeURIComponent(id)
				, data: {
					_token: '{{ csrf_token() }}'
				}
				, success: function() {
					$('#process-row-' + rowId).hide();
				}
				, error: function() {
					alert('Record delete nahi ho paya. Please try again.');
				}
			});
		}

	</script>
</body>

</html>
