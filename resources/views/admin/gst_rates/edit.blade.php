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
					<h1>Edit GST Rates</h1><small>GST Rates</small>
				</div>
			</section>
			<section class="content">
				<div class="row">
					<div class="col-sm-12">
						<div class="panel panel-bd lobidrag">
							<div class="panel-heading"><a href="{{ route('admin.gst-rates.index') }}" class="btn btn-add"><i class="fa fa-list"></i> GST Rates List</a></div>
							<div class="panel-body">
								{!! display_message('message') !!}
								<form method="POST" action="{{ route('admin.gst-rates.update', enc($gstRate->gst_rate_id)) }}">
									@csrf
									@method('PUT')
									<div class="row">
											<div class="col-sm-4">
												<div class="form-group"><label>GST Rate <span class="required">*</span></label><input type="number" name="gst_rate" value="{{ old('gst_rate', $gstRate->gst_rate) }}" class="form-control" step="any" required></div>
											</div>
											<div class="col-sm-4"><div class="form-group"><label>Description</label><input name="description" value="{{ old('description', $gstRate->description) }}" class="form-control"></div></div>
										<div class="col-sm-4">
											<div class="form-group"><label>Status</label><select name="status" class="form-control">
													<option value="Active" @selected(old('status', $)==='Active' )>Active</option>
													<option value="Inactive" @selected(old('status', $)==='Inactive' )>Inactive</option>
												</select></div>
										</div>
									</div>
									<div class="reset-button"><a href="{{ route('admin.gst-rates.index') }}" class="btn btn-warning">Cancel</a> <button type="submit" class="btn btn-success">Save</button></div>
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
