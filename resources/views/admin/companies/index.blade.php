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
					<h1>Company</h1><small>Company details</small>
				</div>
			</section>
			<section class="content">
				{!! display_message('message') !!}
				<div class="row">
					<div class="col-sm-12">
						<div class="panel panel-bd lobidrag">
							<div class="panel-heading">
								<div class="btn-group">
									<h4>Company Details</h4>
								</div>
							</div>
							<div class="panel-body">
								@if (empty($company))
								<a href="{{ route('admin.companies.create') }}" class="btn btn-add"><i class="fa fa-plus"></i> Add Company</a>
								@else
								<div class="table-responsive">
									<table class="table table-bordered table-striped table-hover">
										<thead>
											<tr class="info">
												<th>Company Code</th>
												<th>Name</th>
												<th>Email</th>
												<th>Phone</th>
												<th>GSTIN</th>
												<th>Status</th>
												<th>Action</th>
											</tr>
										</thead>
										<tbody>
											<tr>
												<td>{{ $company->company_code }}</td>
												<td>{{ $company->name }}</td>
												<td>{{ $company->email }}</td>
												<td>{{ $company->phone }}</td>
												<td>{{ $company->gstin }}</td>
												<td>{{ $company->status }}</td>
												<td><a href="{{ route('admin.companies.edit', enc($company->id)) }}"><i class="fa fa-pencil"></i></a></td>
											</tr>
										</tbody>
									</table>
								</div>
								@endif
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
