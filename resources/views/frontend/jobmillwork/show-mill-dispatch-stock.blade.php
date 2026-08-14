<?php
use App\Http\Controllers\CommonController;

?>
<!DOCTYPE html>
<html lang="en">
<head>@include('frontend.common.head')
<meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body class="hold-transition sidebar-mini jobmillwork-page jobmill-chalan-page">


<div class="wrapper"> @include('frontend.common.header')
  <div class="content-wrapper">
    <section class="content">
      <div class="row">
        <div class="col-sm-12"> {!! display_message('message') !!}
          <div class="panel panel-bd lobidrag">
            <div class="panel-heading jobmill-page-heading">
              <div class="btn-group" id="buttonexport"> <a href="javascript:void(0);">
                <h4>Mill Dispatch Challan</h4>
                </a> </div>
            </div>
            <div class="panel-body">
              <div class="jobmill-filter-box">
                <form action="{{ route('show-mill-chalan') }}" method="GET" role="search" class="jobmill-filter-form">
                  @csrf
                  <div class="col-sm-2 col-xs-12">
                    <input type="text" class="form-control" name="itemName" id="itemName" value="{{ old('itemName', $itemName ?? '') }}" placeholder="Item Name">
                  </div>
                  <div class="col-sm-2 col-xs-12">
                    <input type="text" class="form-control" name="vendorName" id="cus_search" value="{{ old('vendorName', $qsearch ?? '') }}" placeholder="Vendor Name">
                  </div>
                  <div class="col-sm-2 col-xs-12">
                    <input type="text" class="form-control loomexa-datepicker" data-datepicker-max-date="0" name="from_date" id="from_date" placeholder="From Date" value="{{ old('from_date', $fromDate ?? '') }}">
                  </div>
                  <div class="col-sm-2 col-xs-12">
                    <input type="text" class="form-control loomexa-datepicker" data-datepicker-max-date="0" name="to_date" id="to_date" placeholder="To Date" value="{{ old('to_date', $toDate ?? '') }}">
                  </div>
				  
                 <div class="col-sm-2 col-xs-12">
					<select class="form-control" name="is_tot_mtr_received" id="is_tot_mtr_received">
						<option value="1" {{ request('is_tot_mtr_received') == '1' ? 'selected' : '' }}> Pending </option>
						<option value="2" {{ request('is_tot_mtr_received') == '2' ? 'selected' : '' }}> Completed </option>
						<option value="0" {{ request('is_tot_mtr_received') == '0' ? 'selected' : '' }}> All </option>
					</select>
				</div>
                  <div class="col-sm-1 col-xs-12">
                    <input type="submit" name="sbtSearch" class="btn btn-success" value="Search">
                  </div>
				  <div class="col-sm-2 col-xs-12 jobmill-filter-action-wide"> 
					<button type="submit" name="sbtSearch" class="btn btn-success" value="ExportToExcel">Export to Excel</button>
                  </div> 
                </form>
              </div>
              <div class="table-responsive jobmill-card-table">
                
                <table class="table table-bordered table-striped table-hover jobmill-summary-table">
                  <thead>
                    <tr class="info">
                      <th>Total</th>
                      <th>Received</th>
                      <th>Shortage</th>
                      <th>Remaining</th>
                      <?php if (! empty($extraReceivedMeter)) { ?> <th>Extra Received</th> <?php } ?>
                    </tr>
                  </thead>
                  <tbody>
                    <tr>
                      <td class="summary-total" style="color: #007bff;"><?php echo number_format($totalMeter, 2); ?> Meter</td>
                      <td class="summary-received" style="color: #28a745;"><?php echo number_format($totalReceivedMeter, 2); ?> Meter</td>
                      <td class="summary-shortage" style="color: #c05621;"><?php echo number_format($totalShortageMeter, 2); ?> Meter</td>
                      <td class="summary-remaining" style="color: #000000;"><?php echo number_format($remainingMeter ?? 0, 2); ?> Meter</td>
                      <?php if (! empty($extraReceivedMeter)) { ?>
                        <td class="summary-extra" style="color: #c0392b;"><?php echo number_format($extraReceivedMeter, 2); ?> Meter (Excess Received)</td>
                      <?php } ?>
                    </tr>
                  </tbody>
                </table>

				<table id="dataTableExample1" class="table table-bordered table-striped table-hover jobmill-data-table">
                  <thead>
                    <tr class="info">
						<th>Voucher </th>
						<th>Chalan </th>
						<th>Vendor</th>
						<th>Work</th>
						<th>Item</th>
						<th>Process</th>
						<th>Status</th>
						<th>Total </th>
						<th>Received </th>
						<th>Balance </th>
						<th>Billing </th>
						<th>Remark</th>
						<th>Date</th>
						<th class="text-center">Action</th>
					</tr>
                  </thead>
                  <tbody>
                  
			<?php
            foreach ($dataWI as $data) {

                $getProcessName = CommonController::getProcessName($data->process_type);
                $remainingMeter = max(0, $data->tot_meter - $data->tot_receive_mtr);

                ?>
				<tr id="Mid<?php echo $data->id; ?>">
					<td><?php echo $data->voucher_number; ?> <?php echo $data->id; ?></td>
					<td><?php echo $data->chalan_no; ?></td>
					<td>
						<a href="javascript:void(0);"
						   class="open-vendor-modal"
						   data-id="<?php echo $data->id; ?>"
						   data-vendor_id="<?php echo $data->vendor_id; ?>"
						   data-chalan_no="<?php echo $data->chalan_no; ?>"
						   data-vendor_name="<?php echo htmlspecialchars($data->vendor_name, ENT_QUOTES); ?>"
						   data-mobile="<?php echo htmlspecialchars($data->mobile, ENT_QUOTES); ?>"
						   data-email="<?php echo htmlspecialchars($data->email, ENT_QUOTES); ?>"
						   data-billing_address="<?php echo htmlspecialchars($data->billing_address, ENT_QUOTES); ?>"
						   data-shipping_address="<?php echo htmlspecialchars($data->shipping_address, ENT_QUOTES); ?>">
							<?php echo $data->vendor_name; ?>
						</a>
					</td>
					<td><?php echo $data->work_name; ?></td>
					<td><?php echo CommonController::getItemName($data->item_id); ?></td>
					<td><?php echo $getProcessName; ?></td>
					<td><span class="label label-info"><?php echo e($data->job_work_status?->label() ?? 'Unmapped'); ?></span></td>
					<td><?php echo $data->tot_meter; ?></td>
					<td><?php echo $data->tot_receive_mtr; ?></td>
					<td><?php echo number_format($remainingMeter, 2); ?></td>
					<td><?php echo $data->billing_address; ?></td>
					<td><?php echo $data->remark; ?></td>
					<td><?php echo date('d-m-Y', strtotime($data->chalan_date)); ?></td>
					
					<td class="text-center">
						<div class="btn-group btn-group-xs" role="group" style="margin-bottom: 6px;">
							<a href="<?php echo route('print-mill-dispatch-chalan', enc($data->id)); ?>"
							   class="btn btn-primary"
							   title="Print Dispatch Chalan"
							   style="margin-right: 20px;">
								<i class="fa fa-print"></i>
							</a>

							<a href="<?php echo route('print-mill-dispatch-received-chalan', enc($data->id)); ?>"
							   class="btn btn-success"
							   title="Print Received Chalan">
								<i class="fa fa-file-text"></i>
							</a>
						</div>

						<?php if (empty($data->is_tot_mtr_received)) { ?>

							<?php if ($data->dispatch_item_type_id == 1 || $data->dispatch_item_type_id == 2) { ?>
								<div style="margin-bottom: 5px;">
									<a href="<?php echo route('mill_dispatch_received_weaving_items_in_warehouse', enc($data->id)); ?>"
									   class="btn btn-info btn-xs btn-block"
									   style="white-space: nowrap;">
										<i class="fa fa-inbox"></i> Receive Weaving Items
									</a>
								</div>
							<?php } ?>

							<?php if ($data->process_type > 2) { ?>
								<div style="margin-bottom: 5px;">
									<a href="<?php echo route('mill_dispatch_received_items_in_warehouse', enc($data->id)); ?>"
									   class="btn btn-warning btn-xs btn-block"
									   style="white-space: nowrap;">
										<i class="fa fa-truck"></i> Receive Items
									</a>
								</div>
							<?php } ?>

							<button type="button"
									class="btn btn-danger btn-xs btn-block open-status-modal"
									data-id="<?php echo $data->id; ?>"
									data-voucher="<?php echo $data->voucher_number; ?>"
									data-chalan="<?php echo $data->chalan_no; ?>"
									style="white-space: nowrap;">
								<i class="fa fa-check-circle"></i> Mark Completed
							</button>

						<?php } else { ?>

							<span class="label label-danger" style="display:inline-block; padding:6px 10px; font-size:11px;">
								<i class="fa fa-check"></i> All Item Received
							</span>

						<?php } ?>
					</td>
					
					
				</tr>
			<?php } ?>

					<tr>
						<tr>
								<td colspan="15" class="text-center">
									<div class="pagination" style="margin: 0;">
										<?php echo $dataWI->appends(request()->except('_token'))->links('vendor.pagination.bootstrap-4'); ?>
									</div>
								</td>
							</tr>
					</tr>
				  
				  
                  </tbody>
                  
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
    
  </div>
   

<div id="vendorEditModal" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
	<div class="modal-dialog modal-lg modal-dialog-centered">
		<div class="modal-content"> 
			<form method="GET" action="{{ route('updateVendor') }}" id="vendorEditForm">
			
				<?php echo csrf_field(); ?>

				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>

					<h4 class="modal-title text-center" style="font-weight:600; letter-spacing:0.4px; text-transform:capitalize;">
						<i class="fa fa-pencil-square-o" style="margin-right:8px;"></i>
						UPDATE VENDOR INFORMATION
					</h4>

					<div class="text-center" style="margin-top:6px; color:#f1f1f1; font-size:13px;">
						<i class="fa fa-file-text-o" style="margin-right:5px; opacity:.9;"></i>
						<span style="opacity:.75;">Chalan No : </span>
						<span id="modal_chalan_no"> - </span>
					</div>
				</div>
				
				<div class="modal-body">
					<input type="hidden" name="dispatch_id" id="dispatch_id">
					<input type="hidden" name="vendor_id" id="vendor_id">

					<div class="row">
						<div class="col-md-4">
							<div class="form-group">
								<label>Vendor Name</label>
								<input type="text" name="vendor_name" id="vendor_name" class="form-control input-lg" placeholder="Vendor Name" autocomplete="off">
							</div>
						</div>

						<div class="col-md-4">
							<div class="form-group">
								<label>Mobile</label>
								<input type="text" name="mobile" id="mobile" class="form-control input-lg" placeholder="Mobile">
							</div>
						</div>

						<div class="col-md-4">
							<div class="form-group">
								<label>Email</label>
								<input type="email" name="email" id="email" class="form-control input-lg" placeholder="Email">
							</div>
						</div>

						<div class="col-md-6">
							<div class="info-box-lite">
								<div class="box-title">
									<i class="fa fa-file-text-o"></i> Billing Address
								</div>
								<div class="box-text">
									<span id="address" class="text-muted">No billing address selected.</span>
								</div>
							</div>
						</div>

						<div class="col-md-6">
							<div class="info-box-lite">
								<div class="box-title">
									<i class="fa fa-truck"></i> Shipping Address
								</div>
								<div class="box-text">
									<span id="Shipaddress" class="text-muted">No shipping address selected.</span>
								</div>
							</div>
						</div>
						
							<input type="hidden" name="billing_address" id="billing_address_input">
							<input type="hidden" name="shipping_address" id="shipping_address_input">

							<input type="hidden" name="ind_add_id" id="ind_add_id_input">
							<input type="hidden" name="ind_add_id_ship" id="ind_add_id_ship_input">
						
						
					</div>
				</div>

				<div class="modal-footer">
					<button type="button" class="btn btn-default" data-dismiss="modal">
						<i class="fa fa-times"></i> Close
					</button>
					<button type="submit" class="btn btn-primary">
						<i class="fa fa-save"></i> Update
					</button>
				</div>
			</form>
		</div>
	</div>
</div>
  <div class="modal fade" id="statusConfirmModal" tabindex="-1" role="dialog" aria-labelledby="statusConfirmModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            
            <div class="modal-header bg-danger">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="statusConfirmModalLabel">
                    Confirm Status Update
                </h4>
            </div>

            <div class="modal-body">
                <input type="hidden" id="modal_row_id" value="">

                <div class="alert alert-warning" style="margin-bottom:15px;">
                    <strong>Warning:</strong> This action will mark the dispatch as completed.
                    Once updated, it should not be changed again manually.
                </div>

                <table class="table table-bordered table-condensed">
                    <tr>
                        <th style="width:35%;">Voucher No</th>
                        <td id="modal_voucher"></td>
                    </tr>
                    <tr>
                        <th>Chalan No</th>
                        <td id="modal_chalan"></td>
                    </tr>
                    <tr>
                        <th>New Status</th>
                        <td><span class="label label-success">Completed</span></td>
                    </tr>
                </table>

                <p class="text-danger" style="margin-bottom:0;">
                    Are you sure you want to continue? You cannot undo this action.
                </p>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">No, Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmStatusUpdate">
                    Yes, Update Status
                </button>
            </div>

        </div>
    </div>
</div>
  @include('frontend.common.footer') 
  </div>
@include('frontend.common.footerscript')
<script type="text/javascript" src="{{ asset('js/jquery.validate.js') }}"></script>
<script>
$(document).on('click', '.open-status-modal', function () {
    var id = $(this).data('id');
    var voucher = $(this).data('voucher');
    var chalan = $(this).data('chalan');

    $('#modal_row_id').val(id);
    $('#modal_voucher').text(voucher);
    $('#modal_chalan').text(chalan);

    $('#statusConfirmModal').modal('show');
});

$(document).on('click', '#confirmStatusUpdate', function () {
    var id = $('#modal_row_id').val();

    $.ajax({
        url: "<?php echo route('update_mtr_received_status'); ?>",
        type: "POST",
        data: {
            _token: "<?php echo csrf_token(); ?>",
            id: id
        },
        success: function (response) {
            if (response.success) {
                $('#statusConfirmModal').modal('hide');
                $('#Mid' + id).find('td:last').html('<span class="label label-success">Completed</span>');
                alert('Status updated successfully.');
            } else {
                alert('Status update failed.');
            }
        },
        error: function () {
            alert('Server error.');
        }
    });
});
</script>

<script> 
var siteUrl = "{{url('/')}}";
$(document).on('click', '.open-vendor-modal', function() {
	var dispatchId = $(this).data('id');
	var vendorId = $(this).data('vendor_id');
	var vendorName = $(this).data('vendor_name');
	var mobile = $(this).data('mobile');
	var email = $(this).data('email');
	var shipping_address = $(this).data('shipping_address');
	var billing_address = $(this).data('billing_address');
	var chalanNo = $(this).data('chalan_no');

	$("#modal_chalan_no").text(chalanNo);
	$("#dispatch_id").val(dispatchId);
	$("#vendor_id").val(vendorId);
	$("#vendor_name").val(vendorName);
	$("#mobile").val(mobile);
	$("#email").val(email);
	$("#address").html(billing_address);
	$("#Shipaddress").html(shipping_address);
	
	$("#billing_address_input").val(billing_address);
$("#shipping_address_input").val(shipping_address);

	$("#vendorEditModal").modal('show');
});

$(function() {
	$("#vendorEditModal #vendor_name").autocomplete({
		minLength: 1,
		appendTo: "#vendorEditModal",
		source: siteUrl + "/list_vendor",
		focus: function(event, ui) {
			$("#vendor_name").val(ui.item.name);
			return false;
		},
		select: function(event, ui) {
			 var individualId = ui.item.id;
			getCustomerShipAddress(individualId);
			getCustomerBillAddress(individualId);
			$("#vendor_id").val(ui.item.id);
			$("#vendor_name").val(ui.item.name);
			$("#mobile").val(ui.item.phone);
			$("#email").val(ui.item.email);
			return false;
		}
	})
	.autocomplete("instance")._renderItem = function(ul, item) {
		return $("<li>")
			.append("<div>" + item.name + "<br> GSTIN - " + item.gstin + "</div>")
			.appendTo(ul);
	};
});
</script>
 
<script>
function getCustomerBillAddress(individualId) 
{
	$.ajax({
		type: "GET",
		url: siteUrl + "/ajax_script/search_customer_bill_address",
		data: {
			individualId: individualId,
		},
		success: function(res) {

			var temp = $("<div>").html(res);

			var address = temp.find('input[name="address"]').val();
			var ind_add_id = temp.find('input[name="ind_add_id"]:checked').val();

			$("#address").text(address);

			$("#billing_address_input").val(address);
			$("#ind_add_id_input").val(ind_add_id);
		}
	});
}
</script>

<script>
function getCustomerShipAddress(individualId) 
{
	$.ajax({
		type: "GET",
		url: siteUrl + "/ajax_script/search_customer_ship_address",
		data: {
			individualId: individualId,
		},
		success: function(res) {

			var temp = $("<div>").html(res);

			var address = temp.find('input[name="shiping_address"]').val();
			var ind_add_id = temp.find('input[name="ind_add_id_ship"]:checked').val();

			$("#Shipaddress").text(address);

			$("#shipping_address_input").val(address);
			$("#ind_add_id_ship_input").val(ind_add_id);
		}
	});
}
</script>
 
<script type="text/javascript">
$(document).on('submit', '#vendorEditForm', function(e){
	e.preventDefault();

	var form = $(this);
	var btn = form.find("button[type=submit]");
	var originalText = btn.html();

	btn.html('<i class="fa fa-spinner fa-spin"></i> Updating...');
	btn.prop("disabled", true);

	$.ajax({
		url: "{{ route('updateVendor') }}",
		type: "GET",
		data: form.serialize(),

		success: function(res){

			console.log(res);

			btn.html(originalText);
			btn.prop("disabled", false);

			alert("Updated successfully");

			var id = $("#dispatch_id").val();
			var vendorName = $("#vendor_name").val();
			var billingAddress = $("#billing_address_input").val();

			var row = $("#Mid" + id);

			row.find("td:eq(2) .open-vendor-modal").text(vendorName);
			row.find("td:eq(9)").text(billingAddress);

			$("#vendorEditModal").modal("hide");
		},

		error: function(xhr){

			btn.html(originalText);
			btn.prop("disabled", false);

			alert("Error! Please try again.");
			console.log(xhr.responseText);
		}
	});
});
</script>
 


<script type="text/javascript">
	var siteUrl = "{{url('/')}}";
	$("#itemName").autocomplete({
		minLength: 0,
		source: siteUrl + '/' + "fabric_list_item",
		focus: function(event, ui) {
		  if (ui.item.part_number != '') {
			$("#itemName").val(ui.item.item_name); 
		  } else {
			$("#itemName").val(ui.item.item_name);
		  }
		  return false;
		},
		select: function(event, ui) {
		  if (ui.item.part_number != '') {
			$("#itemName").val(ui.item.item_name);
			$("#itemId").val(ui.item.item_id);			
		  } else {
			$("#itemName").val(ui.item.item_name);
			$("#itemId").val(ui.item.item_id);
		  }
		  return false;          
		}
	})
	.autocomplete("instance")._renderItem = function(ul, item) {
		return $("<li>") 
		  .append("<div>" + item.item_name + " </div>")
		  .appendTo(ul);
	}; 
</script>

<script type="text/javascript">
var siteUrl = "{{url('/')}}";
$("#cus_search").autocomplete({
	minLength: 0,
	source: siteUrl + '/' + "list_vendor",
	focus: function(event, ui) { 
	  $("#cus_search").val(ui.item.name);
	  return false;
	},
	select: function(event, ui) {
	  $("#cus_search").val(ui.item.name); 
	  return false;
	}
  })
  .autocomplete("instance")._renderItem = function(ul, item) {
	return $("<li>").append("<div>" + item.name + "</div>").appendTo(ul);
  };
</script>
 
</body>
</html>
