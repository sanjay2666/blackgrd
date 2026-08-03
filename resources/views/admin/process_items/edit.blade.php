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
					<h1>Edit Process Item</h1><small>Process list</small>
				</div>
			</section>
			<section class="content">
				<div class="row">
					<div class="col-sm-12">
						<div class="panel panel-bd lobidrag">
							<div class="panel-heading"><a href="{{ route('admin.process-items.index') }}" class="btn btn-add"><i class="fa fa-list"></i> Process List</a></div>
							<div class="panel-body">
								{!! display_message('message') !!}
								@if ($errors->any())<div class="alert alert-danger"><strong>Please fix the errors below.</strong></div>@endif
								<form method="POST" action="{{ route('admin.process-items.update', enc($processItem->id)) }}">
									@csrf
									@method('PUT')
									<div class="row">
										<div class="col-sm-6">
											<div class="form-group"><label>Entry Name</label><input type="text" name="entry_name" value="{{ old('entry_name', $processItem->entry_name) }}" class="form-control"></div>
										</div>
										<div class="col-sm-6">
											<div class="form-group"><label>Process Name <span class="required">*</span></label><input type="text" name="process_name" value="{{ old('process_name', $processItem->process_name) }}" class="form-control" required>@error('process_name')<span class="text-danger small">{{ $message }}</span>@enderror</div>
										</div>
										<div class="col-sm-6">
											<div class="form-group"><label>Output Name <span class="required">*</span></label><input type="text" name="output_name" value="{{ old('output_name', $processItem->output_name) }}" class="form-control" required>@error('output_name')<span class="text-danger small">{{ $message }}</span>@enderror</div>
										</div>
										<div class="col-sm-3">
											<div class="form-group"><label>Last Sl No <span class="required">*</span></label><input type="number" name="process_sl_no_last" value="{{ old('process_sl_no_last', $processItem->process_sl_no_last) }}" class="form-control" required>@error('process_sl_no_last')<span class="text-danger small">{{ $message }}</span>@enderror</div>
										</div>
										<div class="col-sm-3">
											<div class="form-group"><label>Status</label><select name="status" class="form-control">
													@include('admin.common.status-options', ['selectedStatus' => $processItem->status])
												</select></div>
										</div>
									</div>
									<div class="reset-button"><a href="{{ route('admin.process-items.index') }}" class="btn btn-warning">Cancel</a> <button type="submit" class="btn btn-success">Save</button></div>
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
