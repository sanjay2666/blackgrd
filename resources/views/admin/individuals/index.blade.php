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
				<div class="header-icon"><i class="fa fa-users"></i></div>
				<div class="header-title">
					<h1>Individuals</h1>
					<small>Customers, vendors, agents, employees and more</small>
				</div>
			</section>
			<section class="content">
				{!! display_message('message') !!}
				<div class="row">
					<div class="col-sm-12">
						<div class="panel panel-bd lobidrag">
							<div class="panel-heading">
								<div class="btn-group" id="buttonexport">
									<h4>Individual List</h4>
								</div>
							</div>
							<div class="panel-body">
								<div class="row" style="margin-bottom:5px">
									<form action="{{ route('admin.individuals.index') }}" method="GET" role="search">
										<div class="col-sm-3 col-xs-12">
											<input type="text" class="form-control" name="search" placeholder="Search..." value="{{ request('search') }}">
										</div>
										<div class="col-sm-3 col-xs-12">
											<select class="form-control" name="type">
												<option value="">All Types</option>
												@foreach ($types as $type)
												<option value="{{ $type }}" @selected(request('type')===$type)>{{ ucfirst($type) }}</option>
												@endforeach
											</select>
										</div>
										<div class="col-sm-2 col-xs-12"><button class="btn btn-add">Search</button></div>
									</form>
									<div class="col-sm-2 col-xs-12">
										<a class="btn btn-add" href="{{ route('admin.individuals.create') }}"><i class="fa fa-plus"></i> Add Individual</a>
									</div>
								</div>
								<div class="table-responsive">
									<table class="table table-bordered table-striped table-hover">
										<thead>
											<tr class="info">
												<th>Name</th>
												<th>Type</th>
												<th>Department</th>
												<th>Process</th>
												<th>Phone</th>
												<th>Email</th>
												<th>Status</th>
												<th>Action</th>
												<th>Delete</th>
											</tr>
										</thead>
										<tbody>
											@forelse ($individuals as $individual)
											<tr id="individual-row-{{ $individual->id }}">
												<td>{{ $individual->name }}</td>
												<td>{{ ucfirst($individual->type) }}</td>
												<td>{{ $individual->department?->department_name ?? '-' }}</td>
												<td>{{ $individual->processItem?->process_name ?? '-' }}</td>
												<td>{{ $individual->phone ?? '-' }}</td>
												<td>{{ $individual->email ?? '-' }}</td>
												<td>{{ $individual->status }}</td>
												<td><a href="{{ route('admin.individuals.edit', enc($individual->id)) }}" class="tooltip-info"><i class="fa fa-pencil"></i></a></td>
												<td class="center"><button type="button" class="btn btn-danger btn-xs" onclick="deleteIndividual('{{ enc($individual->id) }}', {{ $individual->id }})"><i class="fa fa-trash-o"></i></button></td>
											</tr>
											@empty
											<tr>
												<td colspan="9" class="text-center">No records found.</td>
											</tr>
											@endforelse
										</tbody>
									</table>
								</div>
								<div class="pagination">{{ $individuals->links() }}</div>
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
		function deleteIndividual(id, rowId) {
			if (!confirm('Do you really want to delete this record?')) {
				return;
			}

			$.ajax({
				type: 'DELETE'
				, url: '{{ url(' / admin / individuals ') }}/' + encodeURIComponent(id)
				, data: {
					_token: '{{ csrf_token() }}'
				}
				, success: function() {
					$('#individual-row-' + rowId).hide();
				}
				, error: function() {
					alert('Record delete nahi ho paya. Please try again.');
				}
			});
		}

	</script>
</body>

</html>
