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
					<h1>Edit Item Yarn Requirements</h1><small>Item Yarn Requirements</small>
				</div>
			</section>
			<section class="content">
				<div class="row">
					<div class="col-sm-12">
						<div class="panel panel-bd lobidrag">
							<div class="panel-heading"><a href="{{ route('admin.item-yarn-requirements.index') }}" class="btn btn-add"><i class="fa fa-list"></i> Item Yarn Requirements List</a></div>
							<div class="panel-body">
								{!! display_message('message') !!}
								<form method="POST" action="{{ route('admin.item-yarn-requirements.update', enc($itemYarnRequirement->id)) }}">
									@csrf
									@method('PUT')
									<div class="row">
										<div class="col-sm-4"><div class="form-group"><label>Target Item <span class="required">*</span></label><select name="item_id" class="form-control" required><option value="">Select Item</option>@foreach ($items as $item)<option value="{{ $item->item_id }}" @selected(old('item_id', $itemYarnRequirement->item_id) == $item->item_id)>{{ $item->item_name }} ({{ $item->item_code }})</option>@endforeach</select></div></div>
										<div class="col-sm-4"><div class="form-group"><label>Yarn <span class="required">*</span></label><select name="yarn_id" id="yarn_id" class="form-control" required><option value="">Select Yarn</option>@foreach ($yarns as $yarn)<option value="{{ $yarn->item_id }}" data-unit="{{ $yarn->unitType?->unit_type_name }}" @selected(old('yarn_id', $itemYarnRequirement->yarn_id) == $yarn->item_id)>{{ $yarn->item_name }} ({{ $yarn->item_code }})</option>@endforeach</select></div></div>
										<div class="col-sm-4"><div class="form-group"><label>Process <span class="required">*</span></label><select name="process_id" class="form-control" required><option value="">Select Process</option>@foreach ($processes as $process)<option value="{{ $process->id }}" @selected(old('process_id', $itemYarnRequirement->process_id) == $process->id)>{{ $process->process_name }}</option>@endforeach</select></div></div>
										<div class="col-sm-4">
											<div class="form-group"><label>Reed Peak <span class="required">*</span></label><input type="number" name="reed_peak" value="{{ old('reed_peak', $itemYarnRequirement->reed_peak) }}" class="form-control" step="any" required></div>
										</div>
										<div class="col-sm-4">
											<div class="form-group"><label>Yarn Quantity <span class="required">*</span></label><input type="number" name="yarn_quantity" value="{{ old('yarn_quantity', $itemYarnRequirement->yarn_quantity) }}" class="form-control" min="0" step="0.01" required></div>
										</div>
										<div class="col-sm-4">
											<div class="form-group"><label>Yarn Unit <span class="required">*</span></label><input type="text" name="unit" id="yarn_unit" value="{{ old('unit', $itemYarnRequirement->unit) }}" class="form-control" maxlength="22" readonly required></div>
										</div>
										<div class="col-sm-4">
											<div class="form-group"><label>Status</label><select name="status" class="form-control">
													<option value="Active" @selected(old('status', $itemYarnRequirement->status) === 'Active')>Active</option>
													<option value="Inactive" @selected(old('status', $itemYarnRequirement->status) === 'Inactive')>Inactive</option>
												</select></div>
										</div>
									</div>
									<div class="reset-button"><a href="{{ route('admin.item-yarn-requirements.index') }}" class="btn btn-warning">Cancel</a> <button type="submit" class="btn btn-success">Save</button></div>
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
<script>$('#yarn_id').on('change', function () { $('#yarn_unit').val($(this).find(':selected').data('unit') || ''); }).trigger('change');</script>
</body>

</html>
