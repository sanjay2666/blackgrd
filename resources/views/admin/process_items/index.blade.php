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
										<h1>Process Master</h1><small>Reusable manufacturing process identities</small>
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
												<div class="col-sm-3"><input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Name or code"></div>
												<div class="col-sm-3"><select name="department_id" class="form-control"><option value="">All Departments</option>@foreach($departments as $department)<option value="{{ $department->id }}" @selected((string) request('department_id') === (string) $department->id)>{{ $department->department_name }}</option>@endforeach</select></div>
												<div class="col-sm-2"><select name="status" class="form-control"><option value="">All Statuses</option>@foreach($statusOptions as $value => $label)<option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>@endforeach</select></div>
												<div class="col-sm-2"><button class="btn btn-add">Filter</button></div>
										</form>
									<div class="col-sm-2"><a href="{{ route('admin.process-items.create') }}" class="btn btn-add"><i class="fa fa-plus"></i> Add Process</a></div>
								</div>
								<div class="table-responsive">
									<table class="table table-bordered table-striped table-hover">
										<thead>
											<tr class="info">
														<th>Process Name</th>
														<th>Code</th>
														<th>Department</th>
														<th>Output Name</th>
														<th>Order</th>
												<th>Status</th>
												<th>Action</th>
												<th>Delete</th>
											</tr>
										</thead>
										<tbody>
											@forelse ($processItems as $processItem)
											<tr id="process-row-{{ $processItem->id }}">
														<td>{{ $processItem->process_name }}</td>
														<td>{{ $processItem->short_code }}</td>
														<td>{{ $processItem->department?->department_name ?? 'Reusable / unassigned' }}</td>
														<td>{{ $processItem->output_name }}</td>
														<td>{{ $processItem->display_order ?? '-' }}</td>
												<td>{{ $processItem->status }}</td>
												<td><a href="{{ route('admin.process-items.edit', enc($processItem->id)) }}" title="Edit metadata"><i class="fa fa-pencil"></i></a> @can('processes.view')<a href="{{ route('admin.process-items.configuration', enc($processItem->id)) }}" class="btn btn-xs btn-info">Configure</a>@endcan</td>
														<td><form method="POST" action="{{ route($processItem->status === 'Active' ? 'admin.process-items.deactivate' : 'admin.process-items.activate', enc($processItem->id)) }}">@csrf @method('PATCH')<button class="btn btn-xs {{ $processItem->status === 'Active' ? 'btn-warning' : 'btn-success' }}">{{ $processItem->status === 'Active' ? 'Deactivate' : 'Activate' }}</button></form></td>
											</tr>
											@empty
											<tr>
													<td colspan="8" class="text-center">No records found.</td>
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
</body>

</html>
