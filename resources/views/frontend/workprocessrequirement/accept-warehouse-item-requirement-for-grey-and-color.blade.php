<?php
	use \App\Http\Controllers\CommonController; 	
	 // echo "<pre>"; print_r($dataWPR2); exit;	
?>
<!DOCTYPE html>
<html lang="en">
<head>@include('common.head')
</head>
<body class="hold-transition sidebar-mini">
<!--preloader-->
 
<!-- Site wrapper -->
<div class="wrapper"> @include('common.header')
  <div class="content-wrapper accept-warehouse-allotment-page">
    <section class="content">
      <div class="row">
		{!! CommonController::display_message('message') !!}
        <div class="col-sm-12">
          <div class="panel panel-bd lobidrag accept-allotment-panel">
            <div class="panel-heading accept-allotment-heading">
              <div class="btn-group" id="buttonexport"><h4><i class="fa fa-plus m-r-5"></i>Greige Stock Allotment for Dyeing Process </h4></div>
            </div>
            <div class="panel-body accept-allotment-body"> 
              <div class="accept-allotment-form-wrap">			  
			   <form method="post" action="{{ route('StoreWarehouseGreyAndColorStockAllotment') }}" class="form-horizontal" id="stockAllotFormYarn" autocomplete="off">
			   
			    <div class="col-sm-12">
					<div class="accept-allotment-section-title"><span class="glyphicon glyphicon-list-alt"></span> Requirement Details</div>
					<div class="table-responsive accept-allotment-table-wrap">
					<table class="table table-bordered table-striped table-hover accept-allotment-table accept-allotment-summary-table">
					<thead>
						<tr class="info">
							<th>Item Name</th>
							<th>Internal Name</th>
							<th>Available Qty</th>	 
							<th>Needed Qty</th>
							<th>Warehouse</th>
							<th>W.Compartment</th>							 
						</tr>
					</thead>
					<tbody>						 
						@csrf				
						<?php
						$flag = 0;
						$i = 0;
						$anyZeroStock = false; 
						foreach ($dataWPR as $wprArr) 
						{							 
							$wbitemId 			= $wprArr->warehouse_balance_item_id;
							$wisId 				= $wprArr->wis_id;
							$itemTypeId 		= $wprArr->item_type_id;
							$wprId 				= $wprArr->id;
							$itemId 			= $wprArr->item_id;					 
							$processId 			= $wprArr->process_type_id;
							$Quantity 			= $wprArr->quantity;							 
							if(!empty($wisId))
							{
								$warehouseName 	= CommonController::getWareHouseNameByItemStockId($wisId);
							} else 
							{
								$warehouseName 	= CommonController::getWareHouseNameByItemStock($itemId, $processId);
							}					
							$unitType 			= CommonController::getUnitTypeName($wprArr['Item']->unit_type_id);
							$ItemTypeName 		= CommonController::getItemTypeName($itemTypeId);
							$totAvlStock 		= null;
							if (!empty($wbitemId)) {
								$totAvlStock 	= CommonController::getBalanceStockById($wbitemId);
							} elseif (!empty($wisId)) {
								$totAvlStock 	= CommonController::getWarehouseItemStockById($wisId);
							} else {
								$totAvlStock 	= CommonController::getTotalAvailableItemStock($itemId, $itemTypeId);
							}
							if (empty($totAvlStock)) {
								$anyZeroStock = true;
							}							
							// $flag 			= !empty($totAvlStock) ? 1 : 0;			 
							$itemName 		= $wprArr['Item']->item_name;
							$itemCode 		= $wprArr['Item']->item_code;
							$itemInterName 	= $wprArr['Item']->internal_item_name;
						?>							 
							<tr>
								<td class="text-left"><?=$itemName; ?></td> 
								<td class="text-left"><?=$itemInterName; ?></td> 
								<td class="text-left"><?=$totAvlStock;?> <?=$unitType;?> <?=$ItemTypeName;?></td> 
								<td class="text-left"><?=$Quantity;?> <?=$unitType;?> <?=$ItemTypeName;?></td> 
								<td class="text-left"><?=$warehouseName['Warehouse']; ?></td> 
								<td class="text-left"><?=$warehouseName['WarehouseCompartment']; ?></td> 
								<input type="hidden" name="received_quantities[]" value="<?=$Quantity;?>" class="form-control"> 
								<input type="hidden" name="work_process_req_ids[]" value="<?=$wprId;?>" class="form-control"> 
							</tr>									 
						<?php $i++;
						} ?>
					</tbody>							 
					</table>
					</div>
				</div>
				
				   <div class="col-sm-12">
					<div class="accept-allotment-section-title"><span class="glyphicon glyphicon-tasks"></span> Available Stock List</div>
					<div class="table-responsive accept-allotment-table-wrap">
					<table class="table table-bordered table-striped table-hover accept-allotment-table accept-allotment-stock-table">
					<thead>
						<tr class="info">
							<th>Stock Id</th>
							<th>Item Name</th>
							<th>Internal Name</th>
							<th>Taka No.</th>
							<th>Available Qty</th>								
							<th>Warehouse</th>
							<th>W.Compartment</th>	
							<th> &nbsp;</th>	 
							<th>Select</th>							 
						</tr>
					</thead>
					<tbody>	
						<?php 
							foreach ($dataWPR2 as $rowArr) 
							{ 
								// echo "<pre>"; print_r($rowArr);  exit;
								
								$balanceQ = $rowArr->quantity;
							?>
							 <input type="hidden" id="wprId" name="wprId" value="<?=$rowArr->id;?>"> 
							 <?php 
								$warehouseItemStock = $rowArr->WarehouseItemStock; 
								if ($warehouseItemStock)
								{  
									foreach ($warehouseItemStock as $itemStock) 
									{
										// echo "<pre>"; print_r($itemStock['WarehouseItem']['WarehouseCompartment']->warehousename);  exit;
										  $stockTblId = $itemStock->id;
						?>				

										 
						    <tr>
								<td class="text-left"><?=$stockTblId?></td> 
								<td class="text-left"><?=$itemStock['Item']->item_name ?? '';?></td> 	
								<td class="text-left"><?=$itemStock['Item']->internal_item_name ?? '';?></td> 	
								<td class="text-left"><?=$itemStock->insp_taka_number;?></td> 	
								<td class="text-left"><?=$itemStock->insp_bal_quan_size;?></td> 
								<td class="text-left"><?=$itemStock['WarehouseItem']['Warehouse']->warehouse_name ?? '';?></td> 	
								<td class="text-left"><?=$itemStock['WarehouseItem']['WarehouseCompartment']->warehousename ?? '';?></td> 
								
								<td class="accept-allotment-qty-cell">    
								<input type="number" id="req_grey_qty_<?=$stockTblId?>" step="0.01" readonly name="req_grey_qty[]" data-max-quantity="<?=$itemStock->insp_bal_quan_size;?>" class="form-control accept-allotment-qty-input" onchange="updateTotalQuantity()">
								<div class="accept-allotment-field-error" id="req_grey_qty_error_<?=$stockTblId?>"></div> 
								</td> 
								<td>  
								<input type="checkbox" id="wis_id_<?=$stockTblId?>" name="wis_id[]" value="<?=$stockTblId;?>" data-quantity="<?= e($itemStock->insp_bal_quan_size) ?>" onClick="addRequisition(this.value)"> </td>
							</tr>		
										
										
						<?php		
									}
								}
							} 

						?>
									
						 
					</tbody>							 
					</table>
					</div>
					  <table class="table table-bordered accept-allotment-total-table" id="myTable">
						<tbody>
							
							<tr>                  
							<th>Requested Quantity</th>
							<td><?=@$wprData->quantity;?>  &nbsp; Meter </td>                    
						  </tr>  	   							
						  <tr>                  
							<th>Required Quantity</th>
							<td><input type="number" max="<?=@$balanceQ;?>" step="0.01" id="tot_req_quantity" name="tot_req_quantity" readonly class="form-control accept-allotment-total-input"> &nbsp; Meter </td>                    
						  </tr>  
						  
												  
						</tbody>
					  </table>	 
				
				
				
				<table class="table table-bordered accept-allotment-remark-table">
					<input type="hidden" name="work_order_id" id="work_order_id" value="<?= $result['workOrdId']; ?>" class="form-control">
					<tr>
						<th>Remark Comment <span class="required" aria-required="true">*</span></th>
						<td><input type="text" name="allotment_remark" id="allotment_remark" required class="form-control"></td>
					</tr>
				</table>
				</div>
				<?php
					$flag = $anyZeroStock ? 0 : 1;
				?>
				
				<?php if (empty($flag)) { ?>
					<p class="accept-allotment-note"> Note: <b>Some Item Not Available in Warehouse.</b></p>
				<?php } ?>
				<?php if (!empty($flag)) { ?>
					<div class="accept-allotment-actions"><button type="submit" id="submitBtnYarn" class="btn btn-success pull-left">Update Allotment</button></div>
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
 <script>
    var siteUrl = "{{ url('/') }}";
    var csrfToken = "{{ csrf_token() }}";
	
	 function updateTotalQuantity() {
        var totalQuantity = 0;
        $("input[name='req_grey_qty[]']").each(function() {
            var quantity = parseFloat($(this).val()) || 0;
            totalQuantity += quantity;
        });
        $("#tot_req_quantity").val(totalQuantity.toFixed(2));
    }

	function formatMaxQuantity(maxQuantity) {
		var numericMax = parseFloat(maxQuantity);

		if (isNaN(numericMax)) {
			return maxQuantity;
		}

		return numericMax % 1 === 0 ? parseInt(numericMax, 10).toString() : numericMax.toString();
	}

	function validateRequisitionQuantity(input) {
		var $input = $(input);
		var maxQuantityValue = $input.attr("data-max-quantity") || $input.attr("max");
		var maxQuantity = parseFloat(maxQuantityValue);
		var enteredQuantity = parseFloat($input.val());
		var errorId = input.id.replace("req_grey_qty_", "req_grey_qty_error_");
		var $error = $("#" + errorId);
		var message = "";

		$input.removeClass("accept-allotment-input-error");
		$error.text("").hide();

		if ($input.val() !== '' && !isNaN(maxQuantity) && !isNaN(enteredQuantity) && enteredQuantity > maxQuantity) {
			message = "Please enter a value less than or equal to " + formatMaxQuantity(maxQuantity) + ".";
			$input.addClass("accept-allotment-input-error");
			$error.text(message).show();
			return false;
		}

		return true;
	}

	function validateAllRequisitionQuantities($form) {
		var firstInvalidInput = null;

		$form.find("input[name='req_grey_qty[]']").each(function() {
			if (!validateRequisitionQuantity(this) && firstInvalidInput === null) {
				firstInvalidInput = this;
			}
		});

		if (firstInvalidInput) {
			firstInvalidInput.focus();
			return false;
		}

		return true;
	}
	
	function setRequisitionQuantity(value, quantity) {
		var numericResponse = parseFloat(quantity) || 0;
		var inputId = "req_grey_qty_" + value;
		var reqGreyQtyInput = $("#" + inputId);
		var checkbox = $("#wis_id_" + value);

		if (checkbox.is(":checked")) {
			reqGreyQtyInput.val(numericResponse.toFixed(2)).removeAttr("readonly");
		 } else {
			reqGreyQtyInput.val('').attr("readonly", true);
		}

		if (reqGreyQtyInput.length) {
			validateRequisitionQuantity(reqGreyQtyInput[0]);
		}

		updateTotalQuantity();
	}

    function addRequisition(value) {
		var checkbox = $("#wis_id_" + value);
		var localQuantity = checkbox.data("quantity");

		if (localQuantity !== undefined && localQuantity !== '') {
			setRequisitionQuantity(value, localQuantity);
			return;
		}

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
                    setRequisitionQuantity(value, response.quantity);
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
	$(document).on("change", "input[name='wis_id[]']", function() {
		addRequisition($(this).val());
	});

    $(document).on("input change", "input[name='req_grey_qty[]']", function() {
		validateRequisitionQuantity(this);
        updateTotalQuantity();
    });
	 
</script>
 
 <script>
$(document).ready(function() {
    var $form = $('#stockAllotFormYarn');
    var $btn  = $('#submitBtnYarn');

    // अगर आपने पहले से $form.validate({...}) कहीं लागू किया हुआ है तो यह लाइन हटाना,
    // वरना यह ensure करेगा .valid() उपलब्ध हो।
    if (typeof $form.validate === 'function') {
        $form.validate(); 
    }

    $form.on('submit', function(e) {
		if (!validateAllRequisitionQuantities($form)) {
			e.preventDefault();
			return false;
		}

        // अगर validate() मौजूद है तो पहले वैलिडेट करें
        if (typeof $form.valid === 'function') {
            if (!$form.valid()) {
                // validation failed — don't disable button
                return;
            }
        }

        // अगर पहले से सबमिट हो चुका है, तो रोक दो
        if ($btn.data('submitted') === true) {
            e.preventDefault();
            return;
        }

        // mark as submitted and disable button + change text for UX
        $btn.data('submitted', true);
        $btn.prop('disabled', true).addClass('disabled').text('Please wait...');
        // form will continue to submit
    });

    // safety: अगर कोई दूसरा तरीका से बटन दबाया जाए (double click), click handler भी रखें
    $btn.on('click', function(e){
        if ($(this).data('submitted') === true) {
            e.preventDefault();
            return false;
        }
        // allow click to submit — actual disabling handled in submit handler
    });
});
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

        if (!validateAllRequisitionQuantities($form)) {
            e.preventDefault();
            return false;
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
