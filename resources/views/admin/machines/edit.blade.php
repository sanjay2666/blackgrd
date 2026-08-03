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
					<h1>Edit Machine</h1><small>Machine list</small>
				</div>
			</section>
			<section class="content">
				<div class="row">
					<div class="col-sm-12">
						<div class="panel panel-bd lobidrag">
							<div class="panel-heading"><a href="{{ route('admin.machines.index') }}" class="btn btn-add"><i class="fa fa-list"></i> Machine List</a></div>
							<div class="panel-body">
								{!! display_message('message') !!}
								<form method="POST" action="{{ route('admin.machines.update', enc($machine->id)) }}">
									@csrf
									@method('PUT')
									<div class="row">
										<div class="col-sm-6">
											<div class="form-group"><label>Machine Name <span class="required">*</span></label><input type="text" name="name" value="{{ old('name', $machine->name) }}" class="form-control" required></div>
										</div>
										<div class="col-sm-6">
											<div class="form-group"><label>Process <span class="required">*</span></label><select name="process_wise" class="form-control" required>
													<option value="">Select Process</option>@foreach ($processItems as $processItem)<option value="{{ $processItem->id }}" @selected((string) old('process_wise', $machine->process_wise) === (string) $processItem->id)>{{ $processItem->process_name }}</option>@endforeach
												</select></div>
										</div>
										<div class="col-sm-3">
											<div class="form-group"><label>Busy</label><select name="is_busy" class="form-control">
													<option value="0" @selected(old('is_busy', $machine->is_busy) == '0')>No</option>
													<option value="1" @selected(old('is_busy', $machine->is_busy) == '1')>Yes</option>
												</select></div>
										</div>

										<div class="col-sm-3">
											<div class="form-group"><label>Status</label><select name="status" class="form-control">
													@include('admin.common.status-options', ['selectedStatus' => $machine->status])
												</select></div>
										</div>
									</div>
									<div class="reset-button"><a href="{{ route('admin.machines.index') }}" class="btn btn-warning">Cancel</a> <button type="submit" class="btn btn-success">Save</button></div>
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
