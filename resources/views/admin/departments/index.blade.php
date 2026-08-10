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
				<div class="header-icon"><i class="fa fa-building"></i></div>
				<div class="header-title">
					<h1>Departments</h1><small>Department list</small>
				</div>
			</section>
			<section class="content">
				{!! display_message('message') !!}
				<div class="row">
					<div class="col-sm-12">
						<div class="panel panel-bd lobidrag">
							<div class="panel-heading">
								<div class="btn-group">
									<h4>Department List</h4>
								</div>
							</div>
							<div class="panel-body">
								<div class="row" style="margin-bottom:5px">
									<form action="{{ route('admin.departments.index') }}" method="GET">
										<div class="col-sm-3"><input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search department"></div>
										<div class="col-sm-3"><select name="factory_id" class="form-control"><option value="">All locations</option>@foreach ($factories as $factory)<option value="{{ $factory->id }}" @selected((string) request('factory_id') === (string) $factory->id)>{{ $factory->name }}</option>@endforeach</select></div>
										<div class="col-sm-2"><select name="status" class="form-control"><option value="">All statuses</option>@foreach ($statusOptions as $value => $label)<option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>@endforeach</select></div>
										<div class="col-sm-2"><button class="btn btn-add">Search</button></div>
									</form>
									<div class="col-sm-3"><a href="{{ route('admin.departments.create') }}" class="btn btn-add"><i class="fa fa-plus"></i> Add Department</a></div>
								</div>
								<div class="table-responsive">
									<table class="table table-bordered table-striped table-hover">
										<thead>
													<tr class="info">
														<th>Department Name</th>
														<th>Code</th>
														<th>Branch / Factory</th>
														<th>Status</th>
														<th>Action</th>
											</tr>
										</thead>
										<tbody>
											@forelse ($departments as $department)
											<tr id="department-row-{{ $department->id }}">
														<td>{{ $department->department_name }}</td>
														<td>-</td>
														<td>{{ $department->factory?->name ?? 'Company-level' }}</td>
														<td>{{ $department->status }}</td>
														<td><a href="{{ route('admin.departments.edit', enc($department->id)) }}"><i class="fa fa-pencil"></i></a></td>
											</tr>
											@empty
											<tr>
													<td colspan="5" class="text-center">No records found.</td>
											</tr>
											@endforelse
										</tbody>
									</table>
								</div>
								<div class="pagination">{{ $departments->links() }}</div>
							</div>
						</div>
					</div>
				</div>
			</section>
		</div>
		@include('admin.common.footer')
	</div>
	@include('admin.common.formfooterscript')
</body>

</html>
