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
					<h1>Colours</h1><small>Colours list</small>
				</div>
			</section>
			<section class="content">
				{!! display_message('message') !!}
				<div class="row">
					<div class="col-sm-12">
						<div class="panel panel-bd lobidrag">
							<div class="panel-heading">
								<div class="btn-group">
									<h4>Colours List</h4>
								</div>
							</div>
							<div class="panel-body">
								<div class="row" style="margin-bottom:5px">
									<form action="{{ route('admin.colours.index') }}" method="GET" role="search">
										<div class="col-sm-4 col-xs-12">
											<input type="text" name="qsearch" value="{{ $qsearch }}" class="form-control" placeholder="Search by Colour Name or Code">
										</div>
										<div class="col-sm-2 col-xs-12"><select name="status" class="form-control"><option value="">All statuses</option>@foreach ($statusOptions as $value => $label)<option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>@endforeach</select></div>
										<div class="col-sm-2 col-xs-12"><button type="submit" class="btn btn-success">Search</button></div>
									</form>
									<div class="col-sm-3 col-xs-12"><a href="{{ route('admin.colours.create') }}" class="btn btn-add"><i class="fa fa-plus"></i> Add Colour</a></div>
								</div>
								<div class="table-responsive">
									<table class="table table-bordered table-striped table-hover">
										<thead>
											<tr class="info">
												<th>ID</th>
												<th>Colour Name</th>
												<th>Code</th>
														<th>Visual</th>
												<th>Status</th>
												<th>Action</th>
												<th>Delete</th>
											</tr>
										</thead>
										<tbody>
											@forelse ($colours as $row)
											<tr id="colours-row-{{ $row->id }}">
												<td>{{ $row->id }}</td>
												<td>{{ $row->name }}</td>
														<td>{{ $row->code ?: '-' }}</td>
														<td><span class="label" style="background:#{{ preg_match('/^[0-9A-Fa-f]{6}$/', (string) $row->code) ? $row->code : '777' }}">&nbsp;&nbsp;&nbsp;</span></td>
												<td>{{ $row->status }}</td>
												<td><a href="{{ route('admin.colours.edit', enc($row->id)) }}"><i class="fa fa-pencil"></i></a></td>
														<td><form method="POST" action="{{ route($row->status === 'Active' ? 'admin.colours.deactivate' : 'admin.colours.activate', enc($row->id)) }}" style="display:inline">@csrf @method('PATCH')<button class="btn btn-xs {{ $row->status === 'Active' ? 'btn-warning' : 'btn-success' }}">{{ $row->status === 'Active' ? 'Deactivate' : 'Activate' }}</button></form></td>
											</tr>
											@empty
											<tr>
												<td colspan="7" class="text-center">No records found.</td>
											</tr>
											@endforelse
										</tbody>
									</table>
								</div>
								<div class="pagination text-center" style="display:block">
									<span class="pagination-links">
										{{ $colours->appends(['qsearch' => $qsearch])->links('vendor.pagination.bootstrap-4') }}
									</span>
									@if ($colours->lastPage() > 1)
									<span class="manual-page-input" style="margin-left:15px">
										<label for="manualPageInput">Go to page:</label>
										<input type="number" id="manualPageInput" min="1" max="{{ $colours->lastPage() }}" value="{{ $colours->currentPage() }}" style="width:70px">
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
					$colours - > lastPage()
				}
			};

			if (pageInput > 0 && pageInput <= lastPage) {
				var baseUrl = window.location.href.split("?")[0];
				var params = new URLSearchParams(window.location.search);
				params.set("page", pageInput);
				window.location.href = baseUrl + "?" + params.toString();
			}
		});

	</script>
</body>

</html>
