@php
	$isEdit = !empty($purchaseOrder);
	$pageTitle = $isEdit ? 'Edit Purchase Order' : 'Add Purchase Order';
	$formAction = $isEdit ? route('update_purchaseorder', enc($purchaseOrder->id)) : route('store_purchaseorder');
	$vendorNameValue = old('vendor_name', $isEdit ? ($purchaseOrder->vendor->name ?? $purchaseOrder->vendor->company_name ?? '') : '');
	$vendorIdValue = old('vendor_id', $isEdit ? $purchaseOrder->vendor_id : '');
	$vendorPhoneValue = old('vendor_phone', $isEdit ? ($purchaseOrder->vendor->phone ?? '') : '');
	$vendorGstinValue = old('vendor_gstin', $isEdit ? ($purchaseOrder->vendor->gstin ?? '') : '');
	$billingIdValue = old('billing_id', $isEdit ? $purchaseOrder->billing_id : '');
	$billingAddressValue = old('billing_address', $isEdit ? $purchaseOrder->billing_address : '');
	$shippingIdValue = old('shiping_id', $isEdit ? $purchaseOrder->shiping_id : '');
	$shippingAddressValue = old('shiping_address', $isEdit ? $purchaseOrder->shiping_address : '');
	$purchasedOnValue = old('purchased_on', ($isEdit && !empty($purchaseOrder->purchased_on)) ? $purchaseOrder->purchased_on->format('d-m-Y') : '');
	$orderRemarkValue = old('order_remark', $isEdit ? $purchaseOrder->order_remark : '');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
@include('frontend.common.head', ['pageTitle' => $pageTitle . ' | Loomexa'])
</head>
<body class="hold-transition sidebar-mini purchase-order-page">
<div id="preloader"><div id="status"></div></div>
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
							<a class="btn btn-add" href="{{ route('show-purchaseorders') }}"><i class="fa fa-list"></i> Purchase Order List</a>
						</div>
					</div>
					<div class="panel-body">
						<form method="post" action="{{ $formAction }}" onsubmit="return checkPurchaseOrderForm();" autocomplete="off">
							@csrf

							<div class="po-section-title">
								<span class="glyphicon glyphicon-list-alt"></span> {{ $pageTitle }} Details
							</div>
							<table class="table table-bordered table-striped po-top-table">
								<thead>
									<tr class="info">
										<th width="15%">P.O. Number <span class="text-danger">*</span></th>
										<th width="15%">Purchase Order Date <span class="text-danger">*</span></th>
										<th width="20%">Vendor Details <span class="text-danger">*</span></th>
										<th width="30%">Address <span class="text-danger">*</span></th>
										<th width="20%">Remark</th>
									</tr>
								</thead>
								<tbody>
									<tr>
										<td>
											<input type="text" id="purchase_number" class="form-control" value="{{ $totalPO }}" readonly>
										</td>
										<td>
											<input type="text" id="purchased_on" name="purchased_on" class="form-control loomexa-datepicker" value="{{ $purchasedOnValue }}" required>
										</td>
										<td>
											<input type="text" id="vendor_name" name="vendor_name" class="form-control" value="{{ $vendorNameValue }}" placeholder="Vendor Name" required autofocus>
											<input type="hidden" id="vendor_id" name="vendor_id" value="{{ $vendorIdValue }}" required>
											<input type="hidden" name="vendor_phone" id="vendor_phone_input" value="{{ $vendorPhoneValue }}">
											<input type="hidden" name="vendor_gstin" id="vendor_gstin_input" value="{{ $vendorGstinValue }}">
											<label>Phone : <span id="vendor_phone">{{ $vendorPhoneValue }}</span></label><br>
											<label>GSTIN : <span id="vendor_gstin">{{ $vendorGstinValue }}</span></label>
										</td>
										<td>
											<input type="hidden" name="billing_id" id="billing_id" value="{{ $billingIdValue }}">
											<input type="hidden" name="billing_address" id="billing_address" value="{{ $billingAddressValue }}">
											<input type="hidden" name="shiping_id" id="shiping_id" value="{{ $shippingIdValue }}">
											<input type="hidden" name="shiping_address" id="shiping_address" value="{{ $shippingAddressValue }}">

											<table class="table table-bordered" style="margin-bottom:8px;">
												<tbody>
													<tr>
														<th class="active" style="width:120px;">Billing</th>
														<td>
															<a href="javascript:void(0);" id="changeBillingAddress" class="btn btn-default btn-xs pull-right" style="display:none;">Edit</a>
															<div id="selectedBillingAddress" class="text-muted">
																{{ $billingAddressValue ?: 'Select vendor to load billing address.' }}
															</div>
														</td>
													</tr>
												</tbody>
											</table>
											<div id="billingAddressEdit" style="display:none;">
												<textarea id="billingAddressText" class="form-control" rows="2">{{ $billingAddressValue }}</textarea>
											</div>

											<table class="table table-bordered" style="margin-bottom:8px;">
												<tbody>
													<tr>
														<th class="active" style="width:120px;">Shipping</th>
														<td>
															<a href="javascript:void(0);" id="changeShippingAddress" class="btn btn-default btn-xs pull-right" style="display:none;">Edit</a>
															<div id="selectedShippingAddress" class="text-muted">
																{{ $shippingAddressValue ?: 'Select vendor to load shipping address.' }}
															</div>
														</td>
													</tr>
												</tbody>
											</table>
											<div id="shippingAddressEdit" style="display:none;">
												<textarea id="shippingAddressText" class="form-control" rows="2">{{ $shippingAddressValue }}</textarea>
											</div>
										</td>
										<td>
											<textarea name="order_remark" id="order_remark" class="form-control" rows="3" placeholder="Remark">{{ $orderRemarkValue }}</textarea>
										</td>
									</tr>
								</tbody>
							</table>

							<div class="po-section-title">
								<span class="glyphicon glyphicon-shopping-cart"></span> Add Purchase Item
							</div>
							<div class="table-responsive table-responsive-custom">
								<table class="table table-bordered po-entry-table">
									<thead>
										<tr class="info">
											<th style="width:6%;">Type <span class="text-danger">*</span></th>
											<th style="width:10%;">Product <span class="text-danger">*</span></th>
											<th style="width:8%;" id="colourNameHead">Color</th>
											<th style="width:6%;">HSN/SAC</th>
											<th style="width:6%;">Prch. P</th>
											<th style="width:6%;" id="quantityHead">Quantity</th>
											<th style="width:5%;">Unit</th>
											<th style="width:8%;">Taxable Amt</th>
											<th style="width:5%;">CGST %</th>
											<th style="width:5%;">SGST %</th>
											<th style="width:5%;">IGST %</th>
											<th style="width:8%;">Tax Amt</th>
											<th style="width:8%;">Net Price</th>
											<th style="width:3%;">Action</th>
										</tr>
									</thead>
									<tbody>
										<tr>
											<td>
												<select class="form-control" id="item_type_id">
													@foreach ($dataIT as $type)
														<option value="{{ $type->item_type_id }}" data-type-name="{{ $type->item_type_name }}">{{ $type->item_type_name }}</option>
													@endforeach
												</select>
											</td>
											<td>
												<input type="text" id="item_name" class="form-control" placeholder="Product Name">
												<input type="hidden" id="item_id">
											</td>
											<td id="colourNameId">
												<input type="text" id="colour_name" class="form-control" placeholder="Colour Name">
											</td>
											<td><input type="text" id="hsn" class="form-control" placeholder="HSN/SAC"></td>
											<td><input type="number" step="0.01" id="mrp" class="form-control" value="0"></td>
											<td>
												<input type="number" step="0.01" id="quantity" class="form-control" placeholder="Quantity" value="1">
												<input type="hidden" id="meter" value="0">
											</td>
											<td>
												<select id="unit" class="form-control">
													@foreach ($dataUT as $unitType)
														<option value="{{ $unitType->unit_type_name }}">{{ $unitType->unit_type_name }}</option>
													@endforeach
												</select>
											</td>
											<td><input type="number" step="0.01" id="saleprice_wot" class="form-control" value="0.00" readonly></td>
											<td>
												<select id="cgst" class="form-control">
													<option value="0">0</option>
													<option value="0.125">0.125</option>
													<option value="1.5">1.5</option>
													<option value="2.5">2.5</option>
													<option value="6">6</option>
													<option value="9">9</option>
													<option value="14">14</option>
												</select>
											</td>
											<td>
												<select id="sgst" class="form-control">
													<option value="0">0</option>
													<option value="0.125">0.125</option>
													<option value="1.5">1.5</option>
													<option value="2.5">2.5</option>
													<option value="6">6</option>
													<option value="9">9</option>
													<option value="14">14</option>
												</select>
											</td>
											<td>
												<select id="igst" class="form-control">
													<option value="0">0</option>
													<option value="0.250">0.25</option>
													<option value="3">3</option>
													<option value="5">5</option>
													<option value="12">12</option>
													<option value="18">18</option>
													<option value="28">28</option>
												</select>
											</td>
											<td><input type="number" step="0.01" id="taxrs" class="form-control" value="0.00" readonly></td>
											<td><input type="number" step="0.01" id="total_price" class="form-control" value="0.00" readonly></td>
											<td><button type="button" id="addItemBtn" class="btn btn-primary"><i class="fa fa-plus"></i></button></td>
										</tr>
									</tbody>
								</table>
							</div>

							<div class="po-section-title">
								<span class="glyphicon glyphicon-th-list"></span> Purchase Item List
							</div>
							<input type="hidden" id="count_product" name="count_product" value="0">
							<table class="table table-bordered table-striped po-added-table" id="addedItemTable">
								<thead>
									<tr class="success">
										<th style="width:4%;">Type</th>
										<th style="width:10%;">Product</th>
										<th style="width:10%;">Colour</th>
										<th style="width:8%;">Prch. P</th>
										<th style="width:8%;">HSN/SAC</th>
										<th style="width:6%;" id="addedQuantityHead">Quantity</th>
										<th style="width:6%;">Unit</th>
										<th style="width:8%;">Taxable Amt</th>
										<th style="width:5%;">CGST</th>
										<th style="width:5%;">SGST</th>
										<th style="width:5%;">IGST</th>
										<th style="width:8%;">Tax Amt</th>
										<th style="width:12%;">Net Price</th>
										<th style="width:2%;">Action</th>
									</tr>
								</thead>
								<tbody id="itemsBody"></tbody>
								<tfoot>
									<tr>
										<th colspan="11"></th>
										<th>Total</th>
										<th><input type="number" name="total" id="total" min="0" value="{{ old('total', 0) }}" step=".01" class="form-control" readonly></th>
										<th></th>
									</tr>
									<tr class="active">
										<th colspan="5"></th>
										<th>Frieght</th>
										<th colspan="2"><input type="number" name="frieght" id="frieght" value="{{ old('frieght', 0) }}" min="0" step=".01" class="form-control"></th>
										<th colspan="3"></th>
										<th>G.Total</th>
										<th><input type="number" name="subtotal" id="subtotal" value="{{ old('subtotal', 0) }}" min="0" step=".01" class="form-control" readonly></th>
										<th></th>
									</tr>
								</tfoot>
							</table>

							<div class="po-actions">
								<button type="button" class="btn btn-danger" id="discardBtn" style="display:none"><i class="fa fa-times"></i> Discard</button>
								<button type="submit" class="btn btn-primary pull-left" id="confirmBtn" style="display:none">{{ $isEdit ? 'Update' : 'Confirm' }}</button>
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
@php
	$editItems = $isEdit ? $purchaseOrder->PurchaseOrderItem : collect();
	$oldPurchaseItems = [
		'item_type_id_arr' => old('item_type_id_arr', $editItems->pluck('item_type_id')->all()),
		'item_type_name_arr' => old('item_type_name_arr', $editItems->map(fn($item) => $item->ItemType->item_type_name ?? $item->item_type_id)->all()),
		'item_id_arr' => old('item_id_arr', $editItems->pluck('item_id')->all()),
		'name_arr' => old('name_arr', $editItems->pluck('name')->all()),
		'colour_name_arr' => old('colour_name_arr', $editItems->pluck('colour_name')->all()),
		'hsn_arr' => old('hsn_arr', $editItems->pluck('hsn')->all()),
		'unit_arr' => old('unit_arr', $editItems->pluck('unit')->all()),
		'meter_arr' => old('meter_arr', $editItems->pluck('meter')->all()),
		'quantity_arr' => old('quantity_arr', $editItems->pluck('quantity')->all()),
		'mrp_arr' => old('mrp_arr', $editItems->pluck('mrp')->all()),
		'cgst_arr' => old('cgst_arr', $editItems->pluck('cgst')->all()),
		'sgst_arr' => old('sgst_arr', $editItems->pluck('sgst')->all()),
		'igst_arr' => old('igst_arr', $editItems->pluck('igst')->all()),
		'cess_arr' => old('cess_arr', $editItems->pluck('cess')->all()),
		'saleprice_wot_arr' => old('saleprice_wot_arr', $editItems->pluck('saleprice_wot')->all()),
		'saleprice_arr' => old('saleprice_arr', $editItems->pluck('saleprice')->all()),
		'cgstrs_arr' => old('cgstrs_arr', $editItems->pluck('cgstrs')->all()),
		'sgstrs_arr' => old('sgstrs_arr', $editItems->pluck('sgstrs')->all()),
		'igstrs_arr' => old('igstrs_arr', $editItems->pluck('igstrs')->all()),
		'cessrs_arr' => old('cessrs_arr', $editItems->pluck('cessrs')->all()),
		'taxrs_arr' => old('taxrs_arr', $editItems->pluck('taxrs')->all()),
		'total_price_arr' => old('total_price_arr', $editItems->pluck('total_price')->all()),
	];
@endphp
<script>
var siteUrl = "{{ url('/') }}";
var rowNumber = 0;
var oldItems = @json($oldPurchaseItems);
var existingBillingId = "{{ $billingIdValue }}";
var existingShippingId = "{{ $shippingIdValue }}";

function num(id) {
	return parseFloat($("#" + id).val()) || 0;
}

function cleanText(text) {
	if (text == null) {
		return "";
	}
	return String(text).replace(/&/g, "&amp;").replace(/"/g, "&quot;").replace(/'/g, "&#039;").replace(/</g, "&lt;").replace(/>/g, "&gt;");
}

function calculatePrice() {
	var taxable = num("mrp") * num("quantity");
	var cgstrs = taxable * num("cgst") / 100;
	var sgstrs = taxable * num("sgst") / 100;
	var igstrs = taxable * num("igst") / 100;
	var taxrs = cgstrs + sgstrs + igstrs;
	var total = taxable + taxrs;

	$("#saleprice_wot").val(taxable.toFixed(2));
	$("#taxrs").val(taxrs.toFixed(2));
	$("#total_price").val(total.toFixed(2));
}

function currentQuantityMode() {
	var typeName = ($("#item_type_id option:selected").data("type-name") || $("#item_type_id option:selected").text() || "").toLowerCase();
	var unitName = ($("#unit").val() || "").toLowerCase();

	if (typeName.indexOf("yarn") >= 0 || unitName == "kg") {
		return "quantity";
	}

	if (typeName.indexOf("fabric") >= 0 || typeName.indexOf("greige") >= 0 || typeName.indexOf("grey") >= 0 || typeName.indexOf("dyed") >= 0 || typeName.indexOf("dye") >= 0) {
		return "meter";
	}

	return "quantity";
}

function updateQuantityMode() {
	var mode = currentQuantityMode();
	var label = mode == "meter" ? "Meter" : "Quantity";

	$("#quantityHead,#addedQuantityHead").text(label);
	$("#quantity").attr("placeholder", label);
	$("#meter").val(mode == "meter" ? $("#quantity").val() : "0");
}

function recalculateTotal() {
	var total = 0;
	$(".total_arr").each(function() {
		total += parseFloat($(this).val()) || 0;
	});

	$("#total").val(total.toFixed(2));
	$("#subtotal").val((total + num("frieght")).toFixed(2));

	var rows = $("#itemsBody tr").length;
	$("#count_product").val(rows);
	if (rows > 0) {
		$("#confirmBtn,#discardBtn").show();
	} else {
		$("#confirmBtn,#discardBtn").hide();
	}
}

function setBillingAddress(addressId, addressText) {
	$("#billing_id").val(addressId);
	$("#billing_address").val(addressText);
	$("#billingAddressText").val(addressText);
	$("#selectedBillingAddress").removeClass("text-muted").html(cleanText(addressText));
	$("#billingAddressEdit").hide();
	$("#changeBillingAddress").show();
}

function setShippingAddress(addressId, addressText) {
	$("#shiping_id").val(addressId);
	$("#shiping_address").val(addressText);
	$("#shippingAddressText").val(addressText);
	$("#selectedShippingAddress").removeClass("text-muted").html(cleanText(addressText));
	$("#shippingAddressEdit").hide();
	$("#changeShippingAddress").show();
}

function selectDefaultAddress(rows, selectedId, setAddressCallback, noAddressText, selectedContainerId) {
	var checkedId = selectedId || "";

	if (rows.length == 0) {
		$(selectedContainerId).addClass("text-muted").html(noAddressText);
		return;
	}

	$.each(rows, function(index, row) {
		if (checkedId == "" && index == 0) {
			checkedId = row.id;
		}
		if (row.default_address) {
			checkedId = row.id;
		}
	});

	$.each(rows, function(index, row) {
		if (row.id == checkedId) {
			setAddressCallback(row.id, row.address);
		}
	});
}

function getVendorAddress(vendorId) {
	$("#selectedBillingAddress").addClass("text-muted").html("Loading billing address...");
	$("#selectedShippingAddress").addClass("text-muted").html("Loading shipping address...");
	$("#billingAddressEdit,#shippingAddressEdit").hide();
	$("#changeBillingAddress,#changeShippingAddress").hide();

	$.getJSON(siteUrl + "/individual-addresses", { individual_id: vendorId }, function(rows) {
		var billingRows = [];
		var shippingRows = [];

		$.each(rows, function(index, row) {
			if (row.address_type == "s") {
				shippingRows.push(row);
			} else {
				billingRows.push(row);
			}
		});

		if (billingRows.length == 0) {
			billingRows = rows;
		}
		if (shippingRows.length == 0) {
			shippingRows = billingRows;
		}

		if (billingRows.length == 0) {
			$("#selectedBillingAddress").addClass("text-muted").html("No billing address found.");
		}
		if (shippingRows.length == 0) {
			$("#selectedShippingAddress").addClass("text-muted").html("No shipping address found.");
		}

		selectDefaultAddress(billingRows, existingBillingId, setBillingAddress, "No billing address found.", "#selectedBillingAddress");
		selectDefaultAddress(shippingRows, existingShippingId, setShippingAddress, "No shipping address found.", "#selectedShippingAddress");
	});
}

function changeItemType() {
	var typeName = ($("#item_type_id option:selected").data("type-name") || $("#item_type_id option:selected").text() || "").toLowerCase();

	if (typeName.indexOf("dyed") >= 0 || typeName.indexOf("dye") >= 0) {
		$("#colourNameHead,#colourNameId").show();
	} else {
		$("#colour_name").val("");
		$("#colourNameHead,#colourNameId").hide();
	}
	updateQuantityMode();
}

function addPurchaseItem(item) {
	rowNumber++;
	var rowId = "tr_" + rowNumber;
	var html = "";

	html += "<tr id='" + rowId + "'>";
	html += "<td>" + cleanText(item.item_type_name || item.item_type_id) + "<input type='hidden' name='item_id_arr[]' value='" + cleanText(item.item_id) + "'><input type='hidden' name='item_type_id_arr[]' value='" + cleanText(item.item_type_id) + "'><input type='hidden' name='item_type_name_arr[]' value='" + cleanText(item.item_type_name || item.item_type_id) + "'></td>";
	html += "<td><input type='text' readonly class='form-control' name='name_arr[]' value='" + cleanText(item.name) + "'></td>";
	html += "<td><input type='text' readonly class='form-control' name='colour_name_arr[]' value='" + cleanText(item.colour_name) + "'></td>";
	html += "<td><input type='text' readonly class='form-control' name='mrp_arr[]' value='" + cleanText(item.mrp) + "'></td>";
	html += "<td><input type='text' readonly class='form-control' name='hsn_arr[]' value='" + cleanText(item.hsn) + "'></td>";
	html += "<td><input type='text' readonly class='form-control' name='quantity_arr[]' value='" + cleanText(item.quantity) + "'><input type='hidden' name='meter_arr[]' value='" + cleanText(item.meter) + "'><input type='hidden' name='received_quantity_arr[]' value='0'></td>";
	html += "<td><input type='text' readonly class='form-control' name='unit_arr[]' value='" + cleanText(item.unit) + "'></td>";
	html += "<td><input type='text' readonly class='form-control' name='saleprice_wot_arr[]' value='" + cleanText(item.saleprice_wot) + "'></td>";
	html += "<td><input type='text' readonly class='form-control' name='cgstrs_arr[]' value='" + cleanText(item.cgstrs) + "'><input type='hidden' name='cgst_arr[]' value='" + cleanText(item.cgst) + "'></td>";
	html += "<td><input type='text' readonly class='form-control' name='sgstrs_arr[]' value='" + cleanText(item.sgstrs) + "'><input type='hidden' name='sgst_arr[]' value='" + cleanText(item.sgst) + "'></td>";
	html += "<td><input type='text' readonly class='form-control' name='igstrs_arr[]' value='" + cleanText(item.igstrs) + "'><input type='hidden' name='igst_arr[]' value='" + cleanText(item.igst) + "'></td>";
	html += "<td><input type='text' readonly class='form-control' name='taxrs_arr[]' value='" + cleanText(item.taxrs) + "'><input type='hidden' name='cess_arr[]' value='0'><input type='hidden' name='cessrs_arr[]' value='0'><input type='hidden' name='saleprice_arr[]' value='" + cleanText(item.mrp) + "'></td>";
	html += "<td><input type='text' readonly class='form-control total_arr' name='total_price_arr[]' value='" + cleanText(item.total_price) + "'></td>";
	html += "<td><a href='javascript:void(0);' onclick='$(\"#" + rowId + "\").remove();recalculateTotal();' title='Remove'><span class='glyphicon glyphicon-remove-circle'></span></a></td>";
	html += "</tr>";

	$("#itemsBody").append(html);
	recalculateTotal();
}

function clearItemFields() {
	$("#item_id,#item_name,#colour_name,#hsn").val("");
	$("#mrp,#saleprice_wot,#taxrs,#total_price").val("0.00");
	$("#quantity").val("1");
	updateQuantityMode();
	$("#cgst,#sgst,#igst").val("0");
}

function checkPurchaseOrderForm() {
	if ($("#purchased_on").val() == "") {
		alert("Please select purchase order date.");
		$("#purchased_on").focus();
		return false;
	}
	if ($("#vendor_id").val() == "") {
		alert("Please select a vendor.");
		$("#vendor_name").focus();
		return false;
	}
	if (parseInt($("#count_product").val()) == 0) {
		alert("Please add a product in purchase list.");
		$("#item_name").focus();
		return false;
	}
	return true;
}

function restoreOldItems() {
	$.each(oldItems.item_id_arr || [], function(index, itemId) {
		addPurchaseItem({
			item_id: itemId,
			item_type_id: oldItems.item_type_id_arr[index] || "",
			item_type_name: oldItems.item_type_name_arr[index] || oldItems.item_type_id_arr[index] || "",
			name: oldItems.name_arr[index] || "",
			colour_name: oldItems.colour_name_arr[index] || "",
			hsn: oldItems.hsn_arr[index] || "",
			unit: oldItems.unit_arr[index] || "",
			meter: oldItems.meter_arr[index] || "1",
			quantity: oldItems.quantity_arr[index] || "1",
			mrp: oldItems.mrp_arr[index] || "0.00",
			cgst: oldItems.cgst_arr[index] || "0",
			sgst: oldItems.sgst_arr[index] || "0",
			igst: oldItems.igst_arr[index] || "0",
			cgstrs: oldItems.cgstrs_arr[index] || "0.00",
			sgstrs: oldItems.sgstrs_arr[index] || "0.00",
			igstrs: oldItems.igstrs_arr[index] || "0.00",
			taxrs: oldItems.taxrs_arr[index] || "0.00",
			saleprice_wot: oldItems.saleprice_wot_arr[index] || "0.00",
			total_price: oldItems.total_price_arr[index] || "0.00"
		});
	});
}

$(document).ready(function() {
	restoreOldItems();
	calculatePrice();
	recalculateTotal();

	$("#mrp,#quantity,#cgst,#sgst,#igst").on("input change", calculatePrice);
	$("#quantity").on("input", function() {
		updateQuantityMode();
	});
	$("#unit").on("change", updateQuantityMode);
	$("#frieght").on("input change", recalculateTotal);

	$("#changeBillingAddress").on("click", function() {
		$("#billingAddressEdit").toggle();
	});

	$("#changeShippingAddress").on("click", function() {
		$("#shippingAddressEdit").toggle();
	});

	$("#billingAddressText").on("input", function() {
		var addressText = $("#billingAddressText").val();
		$("#billing_address").val(addressText);
		$("#selectedBillingAddress").removeClass("text-muted").html(cleanText(addressText));
	});

	$("#shippingAddressText").on("input", function() {
		var addressText = $("#shippingAddressText").val();
		$("#shiping_address").val(addressText);
		$("#selectedShippingAddress").removeClass("text-muted").html(cleanText(addressText));
	});

	$("#vendor_name").on("keyup", function() {
		$("#vendor_id,#vendor_phone_input,#vendor_gstin_input,#billing_id,#billing_address,#shiping_id,#shiping_address").val("");
		$("#vendor_phone,#vendor_gstin").html("");
		$("#selectedBillingAddress").addClass("text-muted").html("Select vendor to load billing address.");
		$("#selectedShippingAddress").addClass("text-muted").html("Select vendor to load shipping address.");
		$("#billingAddressEdit,#shippingAddressEdit").hide();
		$("#billingAddressText,#shippingAddressText").val("");
		$("#changeBillingAddress,#changeShippingAddress").hide();
	});

	$("#item_name").on("keyup", function() {
		$("#item_id").val("");
	});

	$("#item_type_id").on("change", function() {
		changeItemType();
		$("#item_id,#item_name,#hsn").val("");
	});

	var vendorAutocomplete = $("#vendor_name").autocomplete({
		minLength: 0,
		source: siteUrl + "/list_individual?type=vendors",
		focus: function(event, ui) {
			$("#vendor_name").val(ui.item.name);
			return false;
		},
		select: function(event, ui) {
			$("#vendor_id").val(ui.item.id);
			$("#vendor_name").val(ui.item.name);
			$("#vendor_phone").html(ui.item.phone || "");
			$("#vendor_gstin").html(ui.item.gstin || "");
			$("#vendor_phone_input").val(ui.item.phone || "");
			$("#vendor_gstin_input").val(ui.item.gstin || "");
			getVendorAddress(ui.item.id);
			return false;
		}
	});
	var vendorAutocompleteInstance = vendorAutocomplete.autocomplete("instance");
	if (vendorAutocompleteInstance) {
		vendorAutocompleteInstance._renderItem = function(ul, item) {
			return $("<li>").append("<div>" + cleanText(item.name || item.company_name) + "<br> GSTIN - " + cleanText(item.gstin || "") + "</div>").appendTo(ul);
		};
	}

	var itemAutocomplete = $("#item_name").autocomplete({
		minLength: 2,
		source: function(request, response) {
			$.ajax({
				url: siteUrl + "/list_warehouse_item_type",
				dataType: "json",
				data: {
					term: request.term,
					type: $("#item_type_id").val()
				},
				success: function(data) {
					response(data);
				}
			});
		},
		focus: function(event, ui) {
			$("#item_name").val(ui.item.item_name);
			return false;
		},
		select: function(event, ui) {
			$("#item_id").val(ui.item.item_id);
			$("#item_name").val(ui.item.item_name);
			changeItemType();
			$("#mrp").val(ui.item.pur_rate || ui.item.unit_price || 0);
			$("#hsn").val(ui.item.hsncode || "");
			var unitType = ui.item.unit_type || ui.item.UnitType;
			if (unitType) {
				$("#unit").val(unitType.unit_type_name);
			}
			$("#cgst").val(ui.item.cgst || 0);
			$("#sgst").val(ui.item.sgst || 0);
			$("#igst").val(ui.item.igst || 0);
			calculatePrice();
			return false;
		}
	});
	var itemAutocompleteInstance = itemAutocomplete.autocomplete("instance");
	if (itemAutocompleteInstance) {
		itemAutocompleteInstance._renderItem = function(ul, item) {
			return $("<li>").append("<div>" + cleanText(item.item_name) + "</div>").appendTo(ul);
		};
	}

	$("#addItemBtn").on("click", function() {
		calculatePrice();
		if ($("#item_id").val() == "") {
			alert("Please select product.");
			$("#item_name").focus();
			return false;
		}
		if (num("quantity") <= 0 || num("total_price") <= 0) {
			alert("Please check quantity, tax and net price.");
			$("#quantity").focus();
			return false;
		}

		var taxable = num("saleprice_wot");
		addPurchaseItem({
			item_id: $("#item_id").val(),
			item_type_id: $("#item_type_id").val(),
			item_type_name: $("#item_type_id option:selected").text(),
			name: $("#item_name").val(),
			colour_name: $("#colour_name").val(),
			hsn: $("#hsn").val(),
			unit: $("#unit").val(),
			meter: currentQuantityMode() == "meter" ? $("#quantity").val() : "0",
			quantity: $("#quantity").val(),
			mrp: $("#mrp").val(),
			cgst: $("#cgst").val(),
			sgst: $("#sgst").val(),
			igst: $("#igst").val(),
			cgstrs: (taxable * num("cgst") / 100).toFixed(2),
			sgstrs: (taxable * num("sgst") / 100).toFixed(2),
			igstrs: (taxable * num("igst") / 100).toFixed(2),
			taxrs: $("#taxrs").val(),
			saleprice_wot: $("#saleprice_wot").val(),
			total_price: $("#total_price").val()
		});

		clearItemFields();
		$("#item_name").focus();
	});

	$("#discardBtn").on("click", function() {
		$("#itemsBody").empty();
		recalculateTotal();
	});

	if ($("#vendor_id").val() != "") {
		getVendorAddress($("#vendor_id").val());
	}
	changeItemType();
});
</script>
</body>
</html>
