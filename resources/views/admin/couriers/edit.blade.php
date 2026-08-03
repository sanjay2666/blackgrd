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
					<h1>Edit Couriers</h1><small>Couriers</small>
				</div>
			</section>
			<section class="content">
				<div class="row">
					<div class="col-sm-12">
						<div class="panel panel-bd lobidrag">
							<div class="panel-heading"><a href="{{ route('admin.couriers.index') }}" class="btn btn-add"><i class="fa fa-list"></i> Couriers List</a></div>
							<div class="panel-body">
								{!! display_message('message') !!}
								<form method="POST" action="{{ route('admin.couriers.update', enc($courier->id)) }}" class="col-sm-6" id="courierForm">
									@csrf
									@method('PUT')
									<div class="row">
										<div class="col-sm-12">
											<div class="form-group"><label>Customer Name <span class="required">*</span></label><input type="text" name="cus_name" id="cus_search" value="{{ old('cus_name', $courier->cus_name) }}" class="form-control" required><input type="hidden" name="cus_id" id="cus_id" value="{{ old('cus_id', $courier->cus_id) }}"><input type="hidden" name="phone" id="phone" value="{{ old('phone', $courier->phone) }}"></div>
										</div>
										<div class="col-sm-12">
											<div class="form-group"><label>Item Name <span class="required">*</span></label><input type="text" name="item_name" id="item_search" value="{{ old('item_name', $courier->item_name) }}" class="form-control" required><input type="hidden" name="item_id" id="item_id" value="{{ old('item_id', $courier->item_id) }}"></div>
										</div>
										<div class="col-sm-12">
											<div class="form-group"><label>Packing details <span class="required">*</span></label><input type="text" name="tot_mtr" id="tot_mtr" value="{{ old('tot_mtr', $courier->tot_mtr) }}" class="form-control" required></div>
										</div>
										<div class="col-sm-12">
											<div class="form-group"><label>Total Pack <span class="required">*</span></label><input type="number" name="tot_pack" id="tot_pack" value="{{ old('tot_pack', $courier->tot_pack) }}" class="form-control" required></div>
										</div>
										<div class="col-sm-12">
											<div class="form-group"><label>Courier Name <span class="required">*</span></label><input type="text" name="courier_name" id="courier_name" value="{{ old('courier_name', $courier->courier_name) }}" class="form-control" required></div>
										</div>
										<div class="col-sm-12">
											<div class="form-group"><label>Tracking Number</label><input type="text" name="tracking_number" id="tracking_number" value="{{ old('tracking_number', $courier->tracking_number) }}" class="form-control"></div>
										</div>
										<div class="col-sm-12">
											<div class="form-group"><label>Track URL</label><input type="text" name="track_url" id="track_url" value="{{ old('track_url', $courier->track_url) }}" class="form-control"></div>
										</div>
										<div class="col-sm-12">
											<div class="form-group"><label>WhatsApp Number</label><input type="text" name="whatsapp" id="whatsapp" value="{{ old('whatsapp', $courier->whatsapp) }}" class="form-control"></div>
										</div>
										<div class="col-sm-12">
											<div class="form-group"><label>Status</label><select name="status" class="form-control">
													<option value="Active" @selected(old('status', $courier->status) === 'Active')>Active</option>
													<option value="Inactive" @selected(old('status', $courier->status) === 'Inactive')>Inactive</option>
												</select></div>
										</div>
									</div>
									<div class="reset-button"><a href="{{ route('admin.couriers.index') }}" class="btn btn-warning">Cancel</a> <button type="submit" class="btn btn-success">Save</button></div>
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
	<script>
		var siteUrl = "{{ url('/') }}";

		$("#cus_search").autocomplete({
			minLength: 0
			, source: siteUrl + "/list_customer"
			, focus: function(event, ui) {
				$("#cus_search").val(ui.item.name);
				return false;
			}
			, select: function(event, ui) {
				$("#cus_search").val(ui.item.name);
				$("#cus_id").val(ui.item.id);
				$("#phone").val(ui.item.phone);
				$("#whatsapp").val(ui.item.whatsapp);
				return false;
			}
		}).autocomplete("instance")._renderItem = function(ul, item) {
			return $("<li>").append("<div>" + item.name + "</div>").appendTo(ul);
		};

		$("#item_search").autocomplete({
			minLength: 0
			, source: siteUrl + "/fabric_list_item"
			, focus: function(event, ui) {
				$("#item_search").val(ui.item.item_name);
				return false;
			}
			, select: function(event, ui) {
				$("#item_search").val(ui.item.item_name);
				$("#item_id").val(ui.item.item_id);
				return false;
			}
		}).autocomplete("instance")._renderItem = function(ul, item) {
			return $("<li>").append("<div>" + item.item_name + "</div>").appendTo(ul);
		};

	</script>
</body>

</html>
