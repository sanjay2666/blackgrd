<?php
	use \App\Http\Controllers\CommonController; 	
	// echo "<pre>"; print_r($dataWPR); exit;	
?>
<!DOCTYPE html>
<html lang="en">
<head>@include('common.head') </head>

<body class="hold-transition sidebar-mini">
 
<div class="wrapper"> @include('common.header')
  <div class="content-wrapperd"> 
    <section class="content">
      <div class="row">
	  {!! CommonController::display_message('message') !!}
        <div class="col-sm-12">
          <div class="panel panel-bd lobidrag">
            <div class="panel-heading">
              <div class="btn-group" id="buttonexport"><h4><i class="fa fa-plus m-r-5"></i> Stock Allotment for Printing</h4></div>
            </div>
            <div class="panel-body"> 
			
				
				
              <div class="table-responsive">
			  
			   <form method="post" action="{{ route('storeStockForMillDispatch') }}" name="creat_chalan_for_mill_dispatch" id="creat_chalan_for_mill_dispatch" class="form-horizontal" autocomplete="off">
				 <div class="col-sm-12">
					<table class="table table-bordered table-striped table-hover">
					<thead>
						<tr class="info">
							<th>Item Name</th>
							<th>Internal Name</th>
							<th>Color</th>
							<th>Lot No.</th>
							<th>Available Qty</th>	 
							<th>Needed Qty</th>
							<th>Warehouse</th>
							<th>W.Compartment</th>							 
						</tr>
					</thead>
					<tbody>			
				@csrf
				
				<?php
				$flag 	= 0;
				$i 		= 0;
				$anyZeroStock = false; 
				foreach ($dataWPR as $wprArr) 
				{	
					   // echo "<pre>"; print_r($wprArr);   exit;
					$wisId 				= $wprArr->wis_id;
				  	$itemTypeId 		= $wprArr->item_type_id;
					$wprId 				= $wprArr->id;
					$itemId 			= $wprArr->item_id;					 
					$processId 			= $wprArr->process_type_id;
					$Quantity 			= $wprArr->quantity;
					$reqLotNo 			= $wprArr->req_lot_no;	
					$dyeingColor		= $wprArr->dyeing_color;
					$coatedPvc			= $wprArr->coated_pvc;	
					$reqFabricType		= $wprArr->req_fabric_type;					
				 
					$warehouseName 		= CommonController::getWareHouseNameByItemStock($itemId, $processId);				 				
					$unitType 			= CommonController::getUnitTypeName($wprArr['Item']->unit_type_id);
					$ItemTypeName 		= CommonController::getItemTypeName($itemTypeId);
					$totAvlStock 		= null;					 
					 
				    $totAvlStock = CommonController::checkTotalAvailableItemStock($itemId, $itemTypeId, $reqFabricType, $reqLotNo, $dyeingColor, $coatedPvc);
					if (empty($totAvlStock)) 
					{
						$anyZeroStock = true;
					}
					$itemName 		= $wprArr['Item']->item_name;
					$itemCode 		= $wprArr['Item']->item_code;
					$itemInterName 	= $wprArr['Item']->internal_item_name;
				?>
					 
					<tr>								 
						<td class="text-left"><?=$itemName; ?></td>							
						<td class="text-left"><?=$itemInterName; ?></td> 
						<td class="text-left"><?=$dyeingColor; ?></td> 
						<td class="text-left"><?=$reqLotNo; ?></td> 
						<td class="text-left"><?=$totAvlStock;?> <?=$unitType;?> <?=$ItemTypeName;?></td>
						<td class="text-left"><?=$Quantity;?> <?=$unitType;?> <?=$ItemTypeName;?></td>
						<td class="text-left"><?=$warehouseName['Warehouse']; ?></td>
						<input type="hidden" name="received_quantities" value="<?=$Quantity;?>" class="form-control"> 
						<input type="hidden" name="work_process_req_ids" value="<?=$wprId;?>" class="form-control"> 
						<td class="text-left"><?=$warehouseName['WarehouseCompartment']; ?></td>
					</tr>
							 
					 
				<?php $i++;
				} ?>
				</tbody>							 
					</table>
				</div>
				
				<div class="col-sm-12" style="margin-top:15px">
					<input type="hidden" id="itemName"  name="itemName" value="<?=$itemName;?>">
					<input type="hidden" id="itemId" name="itemId" value="<?=$itemId;?>">	
					<table class="table table-bordered custom-table">
						<tbody>
							<!-- Voucher Header -->
							<tr style="background-color: #fceabb;">
								<td colspan="5" class="text-center" style="font-weight: bold; padding: 12px; font-size: 18px; color: #5c4b51;">
									<i class="fa fa-file-text-o"></i> VOUCHER & CHALAN DETAILS
								</td>
							</tr> 
							<tr>
								<td style="width: 20%; background-color: #fff7e6;">
									<label for="voucher_number"><strong>Voucher Number</strong></label>
									<input type="text" id="voucher_number" required class="form-control" name="voucher_number" value="<?=$totChDispach;?>">
								</td>
								<td style="width: 20%; background-color: #fff7e6;">
									<label for="chalan_number"><strong>Chalan Number</strong></label>
									<input type="text" id="chalan_number" required class="form-control" name="chalan_number" value="<?=$totChDispach;?>">
								</td>
								<td style="width: 20%; background-color: #fff7e6;">
									<label for="chalan_date"><strong>Chalan Date</strong></label>
									<input type="text" id="chalan_date" required class="form-control" name="chalan_date" value="{{old('chalan_date')}}">
								</td>
								<td style="width: 20%; background-color: #fff7e6;">
									<label for="process_type"><strong>For Process Type</strong></label>
									<select id="process_type" required class="form-control" name="process_type">
										<?php foreach($processI as $prow) { ?>
										<option value="<?=$prow->id;?>"><?=$prow->process_name;?></option>
										<?php } ?>
									</select>
								 
									<label for="work_type_id"><strong>For Work Type</strong></label>
									<select id="work_type_id" required class="form-control" name="work_type_id"> 
										 
										<option value="">Select Work Type</option>
										<option value="weaving">Weaving</option>
										<option value="dyeing">Dyeing</option>
										<option value="coating">Coating</option>
										<option value="printing">Printing</option>
										<option value="extra">Extra</option> 
									</select>
								</td>
								
								<td style="width: 20%; background-color: #fff7e6;">
									<label for="work_name"><strong>Work Name</strong></label>
									<input type="text" id="work_name" name="work_name" class="form-control" placeholder="Work Name" required value="{{old('work_name')}}"> 
								</td>
								
							</tr>

							<!-- Vendor Header -->
							<tr style="background-color: #d1ecf1;">
								<td colspan="5" class="text-center" style="font-weight: bold; padding: 12px; font-size: 18px; color: #0c5460;">
									<i class="fa fa-user"></i> VENDOR DETAILS
								</td>
							</tr>

							<!-- Vendor Info -->
							<tr>
								<td style="width: 20%; background-color: #eef7f9;">
									<label for="vendor_name"><strong>Vendor Name *</strong></label>
									<input type="text" id="vendor_name" name="vendor_name" class="form-control" placeholder="Vendor Name" required value="{{old('vendor_name')}}"> 
									<input type="hidden" id="individual_id" name="individual_id" required>

									<label style="margin-top: 5px;"><i class="fa fa-phone"></i> Phone: <span id="phone" class="text-primary"></span></label>
									<input type="hidden" name="mobile" id="mobile">
									<input type="hidden" name="email" id="email">

									<label>GSTIN: <span id="gst_label" class="text-primary"></span></label>
								</td>

								<td style="width: 20%; background-color: #eef7f9;">
									<label><strong>Billing Address</strong></label> 
									<p><span id="address" class="text-muted"></span></p>
								</td>

								<td style="width: 20%; background-color: #eef7f9;">
									<label><strong>Shipping Address</strong></label>
									<p><span id="Shipaddress" class="text-muted"></span></p>
								</td>

								<td style="width: 20%; background-color: #eef7f9;">
									<label for="allotment_remark"><strong>Alloted Remark</strong></label>
									<input type="text" id="allotment_remark" name="allotment_remark" class="form-control" placeholder="Alloted Remark" required value="{{old('allotment_remark')}}"> 
								</td>

								<td style="width: 20%; background-color: #eef7f9; vertical-align: bottom;">
									<a class="btn btn-primary btn-sm" id="add_billing_shipping_address" target="_blank" href="{{ route('add-individualaddress') }}">
										<i class="fa fa-plus"></i> Add Address
									</a>
								</td>
							</tr>
						</tbody>
					</table>

				</div>
				 
				<div class="col-sm-12">				 
					<table class="table table-bordered table-striped table-hover">
					<thead>
						<tr class="info">
							<th>Item Name</th>
							<th>Internal Name</th>
							<th>Invoice No.</th>
							<th>Greige Taka No.</th>
							<th>Dyieng Lot No.</th>
							<th>Dyieng Taka No.</th>
							<th>Available Qty</th>								
							<th>Warehouse</th>
							<th>W.Compartment</th>	
							<th>&nbsp;</th>	 
							<th>Select</th>							 
						</tr>
					</thead>
					<tbody>	
					
					
						<?php 
						foreach ($dataWPR2 as $rowArr) 
						{ 
							// echo "<pre>"; print_r($rowArr); exit;
							$stockTblId = $rowArr->wis_id;
						?> 		 
						    <tr>
								<td class="text-left"> <?=$rowArr['Item']->item_name;?> </td> 	
								<td class="text-left"><?=$stockTblId?> <?=$rowArr['Item']->internal_item_name;?></td> 	
								<td class="text-left"><?=$rowArr->invoice_number?>  </td> 	
								<td class="text-left"><?=$rowArr->insp_taka_number;?></td> 	
								<td class="text-left"><?=$rowArr->dyeing_lot_number;?></td>	
								<td class="text-left"><?=$rowArr->dyeing_taka_number;?></td> 	
								<td class="text-left"><?=$rowArr->insp_bal_quan_size;?></td> 
								<td class="text-left"><?=$rowArr['WarehouseItem']['Warehouse']->warehouse_name;?></td> 	
								<td class="text-left"><?= optional($rowArr['WarehouseItem']['WarehouseCompartment'])->warehousename ?? '-'; ?></td>
								<td>    
								<input type="number" id="req_grey_qty_<?=$stockTblId?>" readonly step="0.01" name="req_grey_qty[]" max="<?=$rowArr->insp_bal_quan_size;?>" onchange="updateTotalQuantity()">

								</td> 
								<td><input type="checkbox" id="wis_id_<?=$stockTblId?>" name="wisId[]" onClick="addRequisition({{ $stockTblId }})" value="<?=$stockTblId;?>"> </td>
							</tr> 		
						<?php }  ?> 
					</tbody>							 
					</table>
					
					<table class="table table-bordered" id="myTable">
						<tbody>
							<tr>                  
								<th>Required Quantity</th>
								<td><input type="number" max="" step="0.01" id="tot_req_quantity" name="tot_req_quantity" readonly > &nbsp; Meter </td>                    
							</tr>
						</tbody>
					</table>
					
				</div>
				
				
				<table class="table table-bordered">
					<input type="hidden" name="work_order_id" id="work_order_id" value="<?=$wprData->work_order_id;?>" class="form-control">
					<tr>
						<th>Remark Comment <span class="required" aria-required="true">*</span></th>
						<td><input type="text" name="allotment_remark" id="allotment_remark" required class="form-control"></td>
					</tr>
				</table>
				
				<?php
					$flag = $anyZeroStock ? 0 : 1;
				?>
				<?php if (empty($flag)) { ?>
					<p> Note: <b style="color: red;">Some Item Not Available in Warehouse.</b></p>
				<?php } ?>
				<?php if (!empty($flag)) { ?>
					<button type="submit" class="btn btn-success pull-left">Update Allotment</button>
				<?php } ?>
			</form>

			  </div>
            </div>
          </div>
        </div>
      </div>
    </section>
    <!-- /.content -->
  </div>
  <!-- /.content-wrapper --> 
@include('common.footer') 
</div>
@include('common.formfooterscript')
<script type="text/javascript" src="{{ asset('js/jquery.validate.js') }}"></script>

<script type="text/javascript">
    var siteUrl = "{{ url('/') }}";
    var csrfToken = "{{ csrf_token() }}";
    
    // Function to update total required quantity
    function updateTotalQuantity() {
        var totalQuantity = 0;
        $("input[name='req_grey_qty[]']").each(function() {
            var quantity = parseFloat($(this).val()) || 0;
            totalQuantity += quantity;
        });
        $("#tot_req_quantity").val(totalQuantity.toFixed(2));
    }

    // AJAX function to add requisition
      function addRequisition(value) {
        jQuery.ajax({
            type: "GET",
            url: siteUrl + '/ajax_script/getSumWarehouseItemStockValue',
            data: {
                "_token": csrfToken,
                "FId": value,
            },
            cache: false,
            success: function (response) {
                try {
                    console.log("Response before parsing:", response);                     
                    var numericResponse = parseFloat(response.quantity);
                    console.log("Parsed response:", numericResponse); 

					 var inputId = "req_grey_qty_" + value;
                    $("#" + inputId).val(numericResponse).removeAttr("readonly");
					
					  
                    var inputId = "req_grey_qty_" + value;
                    var reqGreyQtyInput = $("#" + inputId);

                     
                    var checkbox = $("#wis_id_" + value);
                    var isChecked = checkbox.is(":checked");                     
                    if (isChecked) {
						reqGreyQtyInput.val(numericResponse).removeAttr("readonly");
					} else {
						reqGreyQtyInput.val('').attr("readonly", true);						 
                    }				
                    var currentQuantity = parseFloat($("#tot_req_quantity").val()) || 0;                     
                    var checkbox 	= $("#wis_id_" + value);
                    var isChecked 	= checkbox.is(":checked");                    
                    var newQuantity;
                    if (isChecked) {
                        newQuantity = currentQuantity + numericResponse;
                    } else {
                        newQuantity = currentQuantity - numericResponse;
                    }                    
                    $("#tot_req_quantity").val(newQuantity.toFixed(2));  
					updateTotalQuantity();
                } catch (error) {
                    console.error("Error parsing JSON response:", error);
                }
            },
            error: function (xhr, status, error) {
                console.error("AJAX request failed:", error);
            }
        });
    }

    // Bind change event listener to req_grey_qty[] inputs
    $(document).on("change", "input[name='req_grey_qty[]']", function() {
        updateTotalQuantity();
    });
</script>
  
<script type="text/javascript">
$(function() {
  $("#chalan_date, #to_date").datepicker({
	dateFormat: "dd-mm-yy",
	changeMonth: true,
	changeYear: true,
	autoclose: true,
  });
});
</script> 

<script type="text/javascript">
let totalBalQty = 0;

function updateTotal(checkbox, qty) {
	if (checkbox.checked) {
		totalBalQty += parseFloat(qty);
	} else {
		totalBalQty -= parseFloat(qty);
	}
	document.getElementById('totalBalQty').innerText = totalBalQty;
	document.getElementById('totalBalQty2').innerText = totalBalQty;
}
</script>
  
<script type="text/javascript">
var siteUrl = "{{url('/')}}";
  $(function() {
    $("#vendor_name").autocomplete({
      minLength: 0,
      source: siteUrl + '/' + "list_vendor",
      focus: function(event, ui) {
        $("#vendor_name").val(ui.item.name);
        return false;
      },
      select: function(event, ui) {
        var individualId = ui.item.id;
        getCustomerShipAddress(individualId);
        getCustomerBillAddress(individualId);
        $("#individual_id").val(ui.item.id);
        $("#vendor_name").val(ui.item.name);
        $("#mobile").val(ui.item.phone);
        $("#phone").val(ui.item.phone);
        $("#email").val(ui.item.email);
        $("#gst_label").html(ui.item.gstin);
        $("#phone").html(ui.item.phone);
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

<script type="text/javascript">
function getCustomerShipAddress(individualId) 
{
	var siteUrl = "{{url('/')}}";
	$.ajax({
	  type: "GET",
	  url: siteUrl + '/' + "ajax_script/search_customer_ship_address",
	  data: {
		"_token": "{{ csrf_token() }}",
		"individualId": individualId,
	  },
	  cache: false,
	  success: function(res) {

		$("#Shipaddress").html(res);
		// $( "#Shipaddress" ).html( ui.item.state_name );

	  }
	})
}
</script>

<script type="text/javascript">
function getCustomerBillAddress(individualId) 
{
	var siteUrl = "{{url('/')}}";
	$.ajax({
	  type: "GET",
	  url: siteUrl + '/' + "ajax_script/search_customer_bill_address",
	  data: {
		"_token": "{{ csrf_token() }}",
		"individualId": individualId,
	  },
	  cache: false,
	  success: function(res) 
	  {
		$("#address").html(res); 
	  }
	})
}
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
<script>
$(function () {
    $('form').on('submit', function (e) {
        var $form = $(this);
        var $button = $form.find('button[type="submit"], input[type="submit"]').filter(':visible').first();

        if ($form.data('submitted') === true) {
            e.preventDefault();
            return false;
        }

        if (typeof $form.valid === 'function' && !$form.valid()) {
            return true;
        }

        $form.data('submitted', true);
        $button.prop('disabled', true).addClass('disabled');

        if ($button.is('input')) {
            $button.val('Please wait...');
        } else {
            $button.text('Please wait...');
        }
    });
});
</script>

 

</body>
</html>
