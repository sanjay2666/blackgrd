<!DOCTYPE html>
<html lang="en">

<head>
	@include('frontend.common.head', ['pageTitle' => 'Add Sale Order | Loomexa'])
</head>

<body class="hold-transition sidebar-mini sale-order-page">
	<div id="preloader">
		<div id="status"></div>
	</div>
	<div class="wrapper">
		@include('frontend.common.header')
		<div class="content-wrapper">

			<section class="content">
				{!! display_message('message') !!}
				<div class="row">
					<div class="col-sm-12">
						<div class="panel panel-bd lobidrag">
							<div class="panel-heading">
								<div class="btn-group">
									<h4>Add Sale Order</h4>
								</div>
								<div class="pull-right">
									<a href="{{ route('sale-orders.index') }}" class="btn btn-default"><i class="fa fa-list"></i> Sale Order List</a>
								</div>
							</div>
							<div class="panel-body">
								<form method="POST" action="{{ route('sale-orders.store') }}" enctype="multipart/form-data" autocomplete="off">
									@csrf

									<div class="table-responsive">
										<table class="table table-bordered table-condensed table-hover sale-order-main-table">
											<tbody>

												<tr class="info">
													<th colspan="6">
														<span class="glyphicon glyphicon-list-alt"></span> Basic Sale Order Details
													</th>
												</tr>

												<tr>
													<td class="col-md-2">
														<div class="form-group">
															<label class="control-label" for="sale_order_type">Order Type</label>
															<select name="sale_order_type" id="sale_order_type" class="form-control input-sm">
																<option value="">Select Order Type</option>
																<option value="1" @selected(old('sale_order_type')=='1' )>Customer</option>
																<option value="2" @selected(old('sale_order_type')=='2' )>Self</option>
															</select>
														</div>
													</td>

													<td class="col-md-2">
														<div class="form-group">
															<label class="control-label">Loomexa Number</label>
															<div class="input-group input-group-sm">
																<span class="input-group-addon"><span class="glyphicon glyphicon-barcode"></span></span>
																<input type="text" name="lot_number" class="form-control" value="{{ old('lot_number', $lotNumber) }}" readonly>
															</div>
														</div>
													</td>

													<td class="col-md-2">
														<div class="form-group">
															<label class="control-label">Sale Order Number <span class="text-danger">*</span></label>
															<input type="text" name="sale_order_number" class="form-control input-sm" value="{{ old('sale_order_number') }}" placeholder="Sale Order Number">
														</div>
													</td>

													<td class="col-md-2">
														<div class="form-group">
															<label class="control-label">Sale Order Date <span class="text-danger">*</span></label>
															<div class="input-group input-group-sm">
																<span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span>
																<input type="text" name="sale_order_date" class="form-control loomexa-datepicker" value="{{ old('sale_order_date', date('d-m-Y')) }}" placeholder="Sale Order Date">
															</div>
														</div>
													</td>

													<td class="col-md-2">
														<div class="form-group">
															<label class="control-label" for="sales_order">Sales Order <span class="text-danger">*</span></label>
															<select name="sales_order" id="sales_order" onchange="changeSaleOrder();" class="form-control input-sm">
																<option value="">Select Sales Order</option>
																<option value="direct" @selected(old('sales_order')=='direct' )>Direct</option>
																<option value="agent" @selected(old('sales_order')=='agent' )>Agent</option>
																<option value="email" @selected(old('sales_order')=='email' )>Email</option>
																<option value="phone" @selected(old('sales_order')=='phone' )>Phone</option>
																<option value="whatsapp" @selected(old('sales_order')=='whatsapp' )>Whatsapp</option>
															</select>
														</div>
													</td>

													<td class="col-md-2">
														<div id="agentId" class="form-group" style="display:none;">
															<label class="control-label" for="ind_agent_id">Agent Name <span class="text-danger">*</span></label>
															<select name="ind_agent_id" id="ind_agent_id" class="form-control input-sm">
																<option value="">Select Agent</option>
																@foreach ($dataI as $valIT)
																<option value="{{ $valIT->id }}" @selected(old('ind_agent_id')==$valIT->id)>{{ $valIT->name }}</option>
																@endforeach
															</select>
														</div>

														<div id="sale_order_fromId" class="form-group">
															<label class="control-label" for="sale_order_from" id="sale_order_from_label">Sale Order From</label>
															<input type="text" name="sale_order_from" id="sale_order_from" class="form-control input-sm" value="{{ old('sale_order_from') }}" placeholder="Enter Sale Order From">
														</div>
													</td>


												</tr>

												<tr class="info">
													<th colspan="6">
														<span class="glyphicon glyphicon-briefcase"></span> Employee, Customer & Delivery Details
													</th>
												</tr>

												<tr>
													<td>
														<div class="form-group">
															<label class="control-label" for="employee_name">Order By Employee <span class="text-danger">*</span></label>
															<div class="input-group input-group-sm">
																<span class="input-group-addon"><span class="glyphicon glyphicon-user"></span></span>
																<input type="text" name="employee_name" id="employee_name" class="form-control" value="{{ old('employee_name') }}" placeholder="Select Employee">
															</div>
															<input type="hidden" name="order_by_employee" id="order_by_employee" value="{{ old('order_by_employee') }}">
														</div>

														<div class="form-group">
															<label class="control-label">Development Type <span class="text-danger">*</span></label>
															<select name="development_type" class="form-control input-sm">
																<option value="">Select Work Type</option>
																<option value="Bulk" @selected(old('development_type')=='Bulk' )>Bulk</option>
																<option value="Sample" @selected(old('development_type')=='Sample' )>Sample</option>
																<option value="JobWork" @selected(old('development_type')=='JobWork' )>JobWork</option>
															</select>
														</div>
													</td>

													<td>
														<div class="form-group">
															<label class="control-label">Sale Order Priority <span class="text-danger">*</span></label>
															<select name="order_priority" class="form-control input-sm">
																<option value="">Select Priority</option>
																@foreach ($priorityArr as $row)
																<option value="{{ $row }}" @selected(old('order_priority')==$row)>{{ $row }}</option>
																@endforeach
															</select>
														</div>
													</td>

													<td>
														<div class="form-group">
															<label class="control-label" for="cus_name">Customer Name <span class="text-danger">*</span></label>
															<div class="input-group input-group-sm">
																<span class="input-group-addon"><span class="glyphicon glyphicon-search"></span></span>
																<input type="text" name="customer_name" id="cus_name" class="form-control" value="{{ old('customer_name') }}" placeholder="Customer Name">
															</div>

															<input type="hidden" name="customer_id" id="customer_id" value="{{ old('customer_id') }}">
															<input type="hidden" name="mobile" id="mobile" value="{{ old('mobile') }}">
															<input type="hidden" name="email" id="email" value="{{ old('email') }}">
															<input type="hidden" name="cst" id="cst" value="{{ old('cst') }}">
														</div>

														<table class="table table-bordered">
															<tbody>
																<tr>
																	<th class="active" style="width:2%;">Phone</th>
																	<td><span id="phone">{{ old('mobile') ?: '-' }}</span></td>
																</tr>
																<tr>
																	<th class="active">GSTIN</th>
																	<td><span id="gst_label">{{ old('cst') ?: '-' }}</span></td>
																</tr>
															</tbody>
														</table>
													</td>

													<td>
														<div class="form-group">

															<label class="control-label">
																<span class="glyphicon glyphicon-home"></span> Billing Address
																<a href="javascript:void(0);" class="btn btn-primary btn-xs" title="Add Billing & Shipping Address" style="margin-left:5px;">
																	<span class="glyphicon glyphicon-plus"></span>
																</a>
															</label>


															<input type="hidden" name="billing_id" id="billing_id" value="{{ old('billing_id') }}">
															<input type="hidden" name="billing_address" id="billing_address" value="{{ old('billing_address') }}">

															<div id="billingAddressList" class="sale-order-address-list form-control" style="height:auto; min-height:112px; white-space:normal;">
																@if(old('billing_address'))
																{{ old('billing_address') }}
																@else
																<span class="text-muted">Billing address not selected.</span>
																@endif
															</div>
														</div>
													</td>

													<td>
														<div class="form-group">
															<label class="control-label">
																<span class="glyphicon glyphicon-map-marker"></span> Shipping Address
															</label>

															<input type="hidden" name="shipping_id" id="shipping_id" value="{{ old('shipping_id') }}">
															<input type="hidden" name="shipping_address" id="shipping_address" value="{{ old('shipping_address') }}">

															<div id="shippingAddressList" class="sale-order-address-list form-control" style="height:auto; min-height:112px; white-space:normal;">
																@if(old('shipping_address'))
																{{ old('shipping_address') }}
																@else
																<span class="text-muted">Shipping address not selected.</span>
																@endif
															</div>
														</div>
													</td>


													<td>
														<div class="form-group" style="margin-bottom:0;">
															<label class="control-label"><span class="glyphicon glyphicon-paperclip"></span> Order Slip <span class="label label-info">Optional</span></label>
															<input type="file" name="order_slip_file" id="order_slip_file" class="form-control input-sm" accept=".pdf,.jpg,.jpeg,.png">
															<small class="text-muted"><span class="glyphicon glyphicon-info-sign"></span> Allowed: PDF, JPG, JPEG, PNG</small>
														</div>
													</td>

												</tr>

											</tbody>
										</table>
									</div>

									<div class="table-responsive">
										<table class="table table-bordered sale-order-item-table sale-order-entry-table">
											<thead>
												<tr class="info">
													<th>Item Name <span class="text-danger">*</span></th>
													<th>Quality</th>
													<th>Dyeing</th>
													<th>Coating</th>
													<th>Print</th>
													<th>Extra</th>
													<th>Roll Length</th>
													<th>Roll Width</th>
													<th>Tube Width</th>
													<th>Remark</th>
												</tr>
											</thead>
											<tbody>
												<tr>
													<td>
														<input type="text" id="product_name" class="form-control" value="" placeholder="Item Name">
														<input type="hidden" id="item_id" value="">
													</td>
													<td><input type="text" id="grey_quality" class="form-control" value="" placeholder="Quality Name"></td>
													<td><input type="text" id="dyeing_color" class="form-control" value="" placeholder="Color Name"></td>
													<td>
														<select id="coating_type" class="form-control">
															<option value="">Select Coating</option>
															@foreach ($coatings as $coating)
															<option value="{{ $coating->code }}">{{ $coating->name }}</option>
															@endforeach
														</select>
													</td>
													<td><input type="text" id="print_job" class="form-control" value="" placeholder="Print Job"></td>
													<td><input type="text" id="extra_job" class="form-control" value="" placeholder="Extra Job"></td>
													<td><input type="text" id="packing_roll_length" class="form-control" value="" placeholder="Ex. 50-70 Mtr"></td>
													<td><input type="text" id="final_dispatch_width" class="form-control" value="" placeholder="Ex. 60 Inch"></td>
													<td><input type="text" id="tube_width" class="form-control" value="" placeholder="Ex. 61 Inch"></td>
													<td><input type="text" id="remarks" class="form-control" value="" placeholder="Remark"></td>
												</tr>
											</tbody>
										</table>

										<table class="table table-bordered sale-order-item-table sale-order-entry-table">
											<thead>
												<tr class="info">
													<th>Unit <span class="text-danger">*</span></th>
													<th>PCs</th>
													<th>Cut</th>
													<th>Meter <span class="text-danger">*</span></th>
													<th>Rate <span class="text-danger">*</span></th>
													<th>Amount</th>
													<th>Net Amount</th>
													<th>Item Priority</th>
													<th>Delivery <span class="text-danger">*</span></th>
													<th>Action</th>
												</tr>
											</thead>
											<tbody>
												<tr>
													<td>
														<select id="unit_type_id" class="form-control">
															<option value="">Select Unit</option>
															@foreach ($unitTypes as $unitType)
															<option value="{{ $unitType->unit_type_id }}">{{ $unitType->unit_type_name }}</option>
															@endforeach
														</select>
													</td>
													<td><input type="text" id="pcs" class="form-control" value="1" placeholder="PCs"></td>
													<td><input type="text" id="cut" class="form-control" value="1" placeholder="Cut"></td>
													<td><input type="text" id="meter" class="form-control" value="1" placeholder="Meter"></td>
													<td><input type="text" id="rate" class="form-control" value="1" placeholder="Rate"></td>
													<td><input type="text" id="amount" class="form-control" readonly></td>
													<td><input type="text" id="net_amount" class="form-control" readonly></td>
													<td>
														<select id="order_item_priority" class="form-control">
															@foreach ($priorityArr as $row)
															<option value="{{ $row }}" @selected($row=='High' )>{{ $row }}</option>
															@endforeach
														</select>
													</td>
													<td><input type="text" id="expect_delivery_date" class="form-control loomexa-datepicker" value="" placeholder="Expected Date"></td>
													<td><button type="button" id="Add_To_Purchase" class="btn btn-primary sale-order-add-item-btn"><i class="fa fa-plus"></i></button></td>
												</tr>
											</tbody>
										</table>

										<input type="hidden" id="count_product" name="count_product" value="0">
										<table class="table table-bordered table-striped sale-order-added-table" id="addedItemTable">
											<thead>
												<tr class="info">
													<th>Item</th>
													<th>Quality</th>
													<th>Dyeing</th>
													<th>Coating</th>
													<th>Delivery</th>
													<th>Unit</th>
													<th>Meter</th>
													<th>Rate</th>
													<th>Amount</th>
													<th>Priority</th>
													<th>Remove</th>
												</tr>
											</thead>
											<tbody id="addedItemsBody">
												<tr id="noItemRow">
													<td colspan="11" class="text-center sale-order-no-item">No item added.</td>
												</tr>
											</tbody>
										</table>
									</div>
									<div class="sale-order-form-actions">
										<button type="submit" id="confirmBtn" style="display:none" class="btn btn-success"><i class="fa fa-save"></i> Save Sale Order</button>
										<button type="reset" id="resetBtn" style="display:none" class="btn btn-danger"><i class="fa fa-times"></i> Discard</button>
										<a href="{{ route('sale-orders.index') }}" class="btn btn-default">Cancel</a>
									</div>
								</form>
							</div>
						</div>
					</div>
				</div>
			</section>
		</div>
		@include('frontend.common.footer')
	</div>
	@include('frontend.common.footerscript')

	<script type="text/javascript">
		function changeSaleOrder() {
			var salesOrder = $('#sales_order').val();
			var placeholder = 'Enter Sale Order From';
			var labelText = 'Sale Order From';

			if (salesOrder === 'agent') {
				$('#agentId').show();
				$('#sale_order_fromId').hide();
				$('#sale_order_from').val('');
				return;
			}

			$('#agentId').hide();
			$('#sale_order_fromId').show();
			$('#ind_agent_id').val('');

			if (salesOrder === 'direct') {
				placeholder = 'Enter Customer or Contact Person Name';
				labelText = 'Direct Order From';
			} else if (salesOrder === 'email') {
				placeholder = 'Enter Email Address';
				labelText = 'Email Address';
			} else if (salesOrder === 'phone') {
				placeholder = 'Enter Phone Number';
				labelText = 'Phone Number';
			} else if (salesOrder === 'whatsapp') {
				placeholder = 'Enter WhatsApp Number';
				labelText = 'WhatsApp Number';
			}

			$('#sale_order_from').attr('placeholder', placeholder);
			$('#sale_order_from_label').text(labelText);
		}

		$(document).ready(function() {
			changeSaleOrder();
		});

	</script>

	<script>
		var siteUrl = "{{ url('/') }}";
		var saleOrderItemRowNumber = 0;

		function showAgentField() {
			if ($("#sales_order").val() != "agent") {
				$("#agent_name").val("");
				$("#ind_agent_id").val("");
			}
		}

		function selectBillingAddress(addressId, addressText) {
			$("#billing_id").val(addressId);
			$("#billing_address").val(addressText);
		}

		function selectShippingAddress(addressId, addressText) {
			$("#shipping_id").val(addressId);
			$("#shipping_address").val(addressText);
		}

		function getCustomerAddress(individualId) {
			$.ajax({
				type: "GET"
				, url: siteUrl + "/customer-addresses"
				, data: {
					individual_id: individualId
				}
				, dataType: "json"
				, success: function(data) {
					var billingHtml = "";
					var shippingHtml = "";

					$("#billing_id").val("");
					$("#shipping_id").val("");
					$("#billing_address").val("");
					$("#shipping_address").val("");
					$("#billingAddressList").html("");
					$("#shippingAddressList").html("");

					if (data.billing_addresses.length == 0) {
						billingHtml = '<p class="text-muted">No billing address found.</p>';
					}

					var billingSelectedId = "";
					$.each(data.billing_addresses, function(index, row) {
						if (billingSelectedId == "" && index == 0) {
							billingSelectedId = row.id;
						}
						if (row.default_address == 1) {
							billingSelectedId = row.id;
						}
					});

					$.each(data.billing_addresses, function(index, row) {
						var checked = "";
						if (row.id == billingSelectedId) {
							checked = "checked";
							selectBillingAddress(row.id, row.address);
						}

						billingHtml += '<label class="sale-order-address-option">';
						billingHtml += '<input type="radio" name="billing_address_radio" value="' + row.id + '" data-address="' + row.address.replace(/"/g, '&quot;') + '" ' + checked + '> ';
						billingHtml += '<span>' + row.address + '</span>';
						billingHtml += '</label>';
					});

					if (data.shipping_addresses.length == 0) {
						shippingHtml = '<p class="text-muted">No shipping address found.</p>';
					}

					var shippingSelectedId = "";
					$.each(data.shipping_addresses, function(index, row) {
						if (shippingSelectedId == "" && index == 0) {
							shippingSelectedId = row.id;
						}
						if (row.default_address == 1) {
							shippingSelectedId = row.id;
						}
					});

					$.each(data.shipping_addresses, function(index, row) {
						var checked = "";
						if (row.id == shippingSelectedId) {
							checked = "checked";
							selectShippingAddress(row.id, row.address);
						}

						shippingHtml += '<label class="sale-order-address-option">';
						shippingHtml += '<input type="radio" name="shipping_address_radio" value="' + row.id + '" data-address="' + row.address.replace(/"/g, '&quot;') + '" ' + checked + '> ';
						shippingHtml += '<span>' + row.address + '</span>';
						shippingHtml += '</label>';
					});

					$("#billingAddressList").html(billingHtml);
					$("#shippingAddressList").html(shippingHtml);
				}
			});
		}

		function calculateSaleOrderAmount() {



			var pcs = parseFloat($("#pcs").val()) || 0;
			var cut = parseFloat($("#cut").val()) || 0;

			var meter = pcs * cut;

			$("#meter").val(meter.toFixed(2));

			var meter = parseFloat($("#meter").val()) || 0;
			var rate = parseFloat($("#rate").val()) || 0;
			var amount = meter * rate;

			$("#amount").val(amount.toFixed(2));
			$("#net_amount").val(amount.toFixed(2));
		}


		function cleanText(text) {
			if (text == null) {
				return "";
			}

			return String(text).replace(/&/g, "&amp;").replace(/"/g, "&quot;").replace(/'/g, "&#039;").replace(/</g, "&lt;").replace(/>/g, "&gt;");
		}

		function removeSaleOrderItem(rowId) {
			$("#" + rowId).remove();
			var totalRows = $("#addedItemsBody tr").not("#noItemRow").length;
			$("#count_product").val(totalRows);

			if (totalRows == 0) {
				$("#noItemRow").show();
				$("#confirmBtn").hide();
				$("#resetBtn").hide();
			}
		}
		$(document).ready(function() {
			showAgentField();
			calculateSaleOrderAmount();

			$("#sales_order").on("change", function() {
				showAgentField();
			});

			$("#meter, #rate, #pcs, #cut").on("keyup change", function() {
				calculateSaleOrderAmount();
			});

			$("#cus_name").on("keyup", function() {
				$("#customer_id").val("");
				if ($(this).val() == "") {
					$("#phone").html("");
					$("#gst_label").html("");
				}
			});

			$(document).on("change", "input[name='billing_address_radio']", function() {
				selectBillingAddress($(this).val(), $(this).data("address"));
			});

			$(document).on("change", "input[name='shipping_address_radio']", function() {
				selectShippingAddress($(this).val(), $(this).data("address"));
			});

			$("#employee_name").on("keyup", function() {
				$("#order_by_employee").val("");
			});

			$("#agent_name").on("keyup", function() {
				$("#ind_agent_id").val("");
			});

			$("#product_name").on("keyup", function() {
				$("#item_id").val("");
			});

			$("#cus_name").autocomplete({
				minLength: 0
				, source: siteUrl + "/list_customer"
				, focus: function(event, ui) {
					$("#cus_name").val(ui.item.name);
					return false;
				}
				, select: function(event, ui) {
					$("#customer_id").val(ui.item.id);
					$("#cus_name").val(ui.item.name);
					$("#mobile").val(ui.item.phone);
					$("#email").val(ui.item.email);
					$("#cst").val(ui.item.gstin);
					$("#phone").html(ui.item.phone);
					$("#gst_label").html(ui.item.gstin);
					getCustomerAddress(ui.item.id);
					return false;
				}
			}).autocomplete("instance")._renderItem = function(ul, item) {
				return $("<li>").append("<div>" + item.name + "<br> GSTIN - " + (item.gstin || "") + "</div>").appendTo(ul);
			};

			$("#employee_name").autocomplete({
				minLength: 0
				, source: function(request, response) {
					$.ajax({
						url: siteUrl + "/list_individual"
						, dataType: "json"
						, data: {
							term: request.term
							, type: "employee"
						}
						, success: function(data) {
							response(data);
						}
					});
				}
				, focus: function(event, ui) {
					$("#employee_name").val(ui.item.name);
					return false;
				}
				, select: function(event, ui) {
					$("#order_by_employee").val(ui.item.id);
					$("#employee_name").val(ui.item.name);
					return false;
				}
			}).autocomplete("instance")._renderItem = function(ul, item) {
				return $("<li>").append("<div>" + item.name + "</div>").appendTo(ul);
			};

			if ($("#agent_name").length) {
				$("#agent_name").autocomplete({
					minLength: 0
					, source: function(request, response) {
						$.ajax({
							url: siteUrl + "/list_individual"
							, dataType: "json"
							, data: {
								term: request.term
								, type: "agents"
							}
							, success: function(data) {
								response(data);
							}
						});
					}
					, focus: function(event, ui) {
						$("#agent_name").val(ui.item.name);
						return false;
					}
					, select: function(event, ui) {
						$("#ind_agent_id").val(ui.item.id);
						$("#agent_name").val(ui.item.name);
						return false;
					}
				}).autocomplete("instance")._renderItem = function(ul, item) {
					return $("<li>").append("<div>" + item.name + "</div>").appendTo(ul);
				};
			}

			$("#product_name").autocomplete({
				minLength: 0
				, source: siteUrl + "/fabric_list_item"
				, focus: function(event, ui) {
					$("#product_name").val(ui.item.item_name);
					return false;
				}
				, select: function(event, ui) {
					$("#item_id").val(ui.item.item_id);
					$("#product_name").val(ui.item.item_name);
					$("#grey_quality").val(ui.item.internal_item_name);
					$("#unit_type_id").val(ui.item.unit_type_id);

					if (ui.item.sale_rate) {
						$("#rate").val(ui.item.sale_rate);
					} else {
						$("#rate").val(ui.item.unit_price);
					}

					calculateSaleOrderAmount();
					return false;
				}
			}).autocomplete("instance")._renderItem = function(ul, item) {
				return $("<li>").append("<div>" + item.item_name + "<br> Item Code: " + (item.item_code || "") + "<br> Internal Name: " + (item.internal_item_name || "") + "</div>").appendTo(ul);
			};


			$("#Add_To_Purchase").on("click", function() {
				var itemId = $("#item_id").val();
				var itemName = $("#product_name").val();
				var unitTypeId = $("#unit_type_id").val();
				var unitTypeName = $("#unit_type_id option:selected").text();
				var deliveryDate = $("#expect_delivery_date").val();
				var meter = parseFloat($("#meter").val()) || 0;
				var rate = parseFloat($("#rate").val()) || 0;
				var amount = parseFloat($("#amount").val()) || 0;

				if (itemId == "") {
					alert("Please select Item.");
					$("#product_name").focus();
					return false;
				}

				if (unitTypeId == "") {
					alert("Please select Unit.");
					$("#unit_type_id").focus();
					return false;
				}

				if (deliveryDate == "") {
					alert("Please select Expected Delivery Date.");
					$("#expect_delivery_date").focus();
					return false;
				}

				if (meter <= 0) {
					alert("Please enter Meter.");
					$("#meter").focus();
					return false;
				}

				if (rate <= 0) {
					alert("Please enter Rate.");
					$("#rate").focus();
					return false;
				}

				saleOrderItemRowNumber = saleOrderItemRowNumber + 1;
				var rowNumber = saleOrderItemRowNumber;
				var rowId = "item_row_" + rowNumber;
				var coatingValue = $("#coating_type").val();
				var coatingText = $("#coating_type option:selected").text();

				if (coatingValue == "") {
					coatingText = "";
				}

				$("#noItemRow").hide();
				$("#confirmBtn").show();
				$("#resetBtn").show();

				var html = "";
				html += '<tr id="' + rowId + '">';
				html += '<td>' + itemName + '<input type="hidden" name="item_id_arr[]" value="' + itemId + '"><input type="hidden" name="item_name_arr[]" value="' + cleanText(itemName) + '"></td>';
				html += '<td>' + $("#grey_quality").val() + '<input type="hidden" name="grey_quality_arr[]" value="' + cleanText($("#grey_quality").val()) + '"></td>';
				html += '<td>' + $("#dyeing_color").val() + '<input type="hidden" name="dyeing_color_arr[]" value="' + cleanText($("#dyeing_color").val()) + '"></td>';
				html += '<td>' + coatingText + '<input type="hidden" name="coating_type_arr[]" value="' + cleanText(coatingValue) + '"></td>';
				html += '<td>' + deliveryDate + '<input type="hidden" name="expect_delivery_date_arr[]" value="' + deliveryDate + '"></td>';
				html += '<td>' + unitTypeName + '<input type="hidden" name="unit_type_id_arr[]" value="' + unitTypeId + '"></td>';
				html += '<td>' + meter + '<input type="hidden" name="meter_arr[]" value="' + meter + '"></td>';
				html += '<td>' + rate + '<input type="hidden" name="rate_arr[]" value="' + rate + '"></td>';
				html += '<td>' + amount.toFixed(2) + '<input type="hidden" name="amount_arr[]" value="' + amount.toFixed(2) + '"></td>';
				html += '<td>' + $("#order_item_priority").val() + '<input type="hidden" name="order_item_priority_arr[]" value="' + $("#order_item_priority").val() + '"></td>';
				html += '<td>';
				html += '<input type="hidden" name="print_job_arr[]" value="' + cleanText($("#print_job").val()) + '">';
				html += '<input type="hidden" name="extra_job_arr[]" value="' + cleanText($("#extra_job").val()) + '">';
				html += '<input type="hidden" name="packing_roll_length_arr[]" value="' + cleanText($("#packing_roll_length").val()) + '">';
				html += '<input type="hidden" name="final_dispatch_width_arr[]" value="' + cleanText($("#final_dispatch_width").val()) + '">';
				html += '<input type="hidden" name="tube_width_arr[]" value="' + cleanText($("#tube_width").val()) + '">';
				html += '<input type="hidden" name="pcs_arr[]" value="' + cleanText($("#pcs").val()) + '">';
				html += '<input type="hidden" name="cut_arr[]" value="' + cleanText($("#cut").val()) + '">';
				html += '<input type="hidden" name="remarks_arr[]" value="' + cleanText($("#remarks").val()) + '">';
				html += '<button type="button" class="btn btn-danger btn-xs" onclick="removeSaleOrderItem(\'' + rowId + '\')"><i class="fa fa-trash"></i></button>';
				html += '</td>';
				html += '</tr>';

				$("#addedItemsBody").append(html);
				var totalAddedRows = $("#addedItemsBody tr").not("#noItemRow").length;
				$("#count_product").val(totalAddedRows);

				$("#product_name").val("");
				$("#item_id").val("");
				$("#grey_quality").val("");
				$("#dyeing_color").val("");
				$("#coating_type").val("");
				$("#print_job").val("");
				$("#extra_job").val("");
				$("#packing_roll_length").val("");
				$("#final_dispatch_width").val("");
				$("#tube_width").val("");
				$("#expect_delivery_date").val("");
				$("#unit_type_id").val("");
				$("#pcs").val("1");
				$("#cut").val("1");
				$("#meter").val("1");
				$("#rate").val("1");
				$("#remarks").val("");
				calculateSaleOrderAmount();
			});

			$("#resetBtn").on("click", function() {
				$("#addedItemsBody tr").not("#noItemRow").remove();
				$("#noItemRow").show();
				$("#count_product").val("0");
				$("#confirmBtn").hide();
				$("#resetBtn").hide();
			});
		});

	</script>

</body>

</html>
