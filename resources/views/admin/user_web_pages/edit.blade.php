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
					<h1>Edit User Web Pages</h1><small>User Web Pages</small>
				</div>
			</section>
			<section class="content">
				<div class="row">
					<div class="col-sm-12">
						<div class="panel panel-bd lobidrag">
							<div class="panel-heading"><a href="{{ route('admin.user-web-pages.index') }}" class="btn btn-add"><i class="fa fa-list"></i> User Web Pages List</a></div>
							<div class="panel-body">
								{!! display_message('message') !!}
								<form method="POST" action="{{ route('admin.user-web-pages.update', enc($userWebPage->id)) }}">
									@csrf
									@method('PUT')
									<div class="row">
										<div class="col-sm-4">
											<div class="form-group"><label>User Id <span class="required">*</span></label><input type="number" name="user_id" value="{{ old('user_id', $userWebPage->user_id) }}" class="form-control" step="any" required></div>
										</div>
										<div class="col-sm-4">
											<div class="form-group"><label>Page Id <span class="required">*</span></label><input type="number" name="page_id" value="{{ old('page_id', $userWebPage->page_id) }}" class="form-control" step="any" required></div>
										</div>
										<div class="col-sm-4">
											<div class="form-group">
												<label>Status</label>
												<select name="status" class="form-control">
													<option value="Active" @selected(old('status', $)==='Active' )>Active</option>
													<option value="Inactive" @selected(old('status', $)==='Inactive' )>Inactive</option>
												</select>
											</div>
										</div>
									</div>
									<div class="reset-button"><a href="{{ route('admin.user-web-pages.index') }}" class="btn btn-warning">Cancel</a> <button type="submit" class="btn btn-success">Save</button></div>
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
