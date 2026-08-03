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
				<div class="header-icon"><i class="fa fa-wifi"></i></div>
				<div class="header-title">
					<h1>Edit Office IP</h1><small>Office IP list</small>
				</div>
			</section>
			<section class="content">
				<div class="row">
					<div class="col-sm-12">
						<div class="panel panel-bd lobidrag">
							<div class="panel-heading"><a href="{{ route('admin.office-ips.index') }}" class="btn btn-add"><i class="fa fa-list"></i> Office IP List</a></div>
							<div class="panel-body">
								{!! display_message('message') !!}
								<form method="POST" action="{{ route('admin.office-ips.update', enc($office_ip->id)) }}">
									@csrf
									@method('PUT')
									<div class="row">
										<div class="col-sm-6">
											<div class="form-group"><label>IP Address <span class="required">*</span></label><input type="text" name="ip_address" value="{{ old('ip_address', $office_ip->ip_address) }}" class="form-control" required></div>
										</div>
										<div class="col-sm-6">
											<div class="form-group"><label>Label</label><input type="text" name="label" value="{{ old('label', $office_ip->label) }}" class="form-control"></div>
										</div>
										<div class="col-sm-3">
											<div class="form-group"><label>Status</label><select name="is_active" class="form-control">
													<option value="1" @selected(old('is_active', $office_ip->is_active) == '1')>Active</option>
													<option value="0" @selected(old('is_active', $office_ip->is_active) == '0')>Inactive</option>
												</select></div>
										</div>
									</div>
									<div class="reset-button"><a href="{{ route('admin.office-ips.index') }}" class="btn btn-warning">Cancel</a> <button type="submit" class="btn btn-success">Save</button></div>
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
