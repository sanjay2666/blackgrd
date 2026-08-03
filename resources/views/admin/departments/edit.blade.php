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
					<h1>Edit Department</h1><small>Department list</small>
				</div>
			</section>
			<section class="content">
				<div class="row">
					<div class="col-sm-12">
						<div class="panel panel-bd lobidrag">
							<div class="panel-heading"><a href="{{ route('admin.departments.index') }}" class="btn btn-add"><i class="fa fa-list"></i> Department List</a></div>
							<div class="panel-body">
								{!! display_message('message') !!}
								@if ($errors->any())<div class="alert alert-danger"><strong>Please fix the errors below.</strong></div>@endif
								<form method="POST" action="{{ route('admin.departments.update', enc($department->id)) }}">
									@csrf
									@method('PUT')
									<div class="row">
										<div class="col-sm-6">
											<div class="form-group"><label>Department Name <span class="required">*</span></label><input type="text" name="department_name" value="{{ old('department_name', $department->department_name) }}" class="form-control" required>@error('department_name')<span class="text-danger small">{{ $message }}</span>@enderror</div>
										</div>
										<div class="col-sm-3">
											<div class="form-group"><label>Financial Year</label><input type="text" name="financial_year" value="{{ old('financial_year', $department->financial_year) }}" class="form-control" maxlength="4"></div>
										</div>
										<div class="col-sm-3">
											<div class="form-group"><label>Status</label>
												<select name="status" class="form-control">
													@include('admin.common.status-options', ['selectedStatus' => $department->status])
												</select>
												</div>
										</div>
									</div>
									<div class="reset-button"><a href="{{ route('admin.departments.index') }}" class="btn btn-warning">Cancel</a> <button type="submit" class="btn btn-success">Save</button></div>
								</form>
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
