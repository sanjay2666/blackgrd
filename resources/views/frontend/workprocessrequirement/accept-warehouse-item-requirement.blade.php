<?php
	use \App\Http\Controllers\CommonController; 	
	// echo "<pre>"; print_r($dataWPR); exit;	
?>
<!DOCTYPE html>
<html lang="en">
<head>@include('common.head') </head>

<body class="hold-transition sidebar-mini">
 
<div class="wrapper"> @include('common.header')
  <div class="content-wrapper">
    <section class="content">
      <div class="row">
	  {!! CommonController::display_message('message') !!}
        <div class="col-sm-12">
          <div class="panel panel-bd lobidrag">
            <div class="panel-heading">
              <div class="btn-group" id="buttonexport"><h4><i class="fa fa-plus m-r-5"></i> Stock Allotment Coating</h4></div>
            </div>
            <div class="panel-body"> 
              <div class="table-responsive">
			  
			   <form method="post" action="{{ route('StoreWarehouseGreyAndColorStockAllotment') }}" class="form-horizontal" autocomplete="off">
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
					//  echo "<pre>"; print_r($wprArr); // exit;
					$wisId 				= $wprArr->wis_id;
				  	$itemTypeId 		= $wprArr->item_type_id;
					$wprId 				= $wprArr->id;
					$itemId 			= $wprArr->item_id;					 
					$processId 			= $wprArr->process_type_id;
					$Quantity 			= $wprArr->quantity;
					$reqLotNo 			= $wprArr->req_lot_no;	
					$dyeingColor		= $wprArr['WorkOrderItem']->dyeing_color;					
				 
					$warehouseName 		= CommonController::getWareHouseNameByItemStock($itemId, $processId);				 				
					$unitType 			= CommonController::getUnitTypeName($wprArr['Item']->unit_type_id);
					$ItemTypeName 		= CommonController::getItemTypeName($itemTypeId);
					$totAvlStock 		= null;					 
					// $totAvlStock 		= CommonController::getTotalAvailableItemStock($itemId, $itemTypeId);
					if($itemTypeId =='7')
					{
						$totAvlStock 		= CommonController::getTotalAvailableItemStock($itemId, $itemTypeId);
					}						
					if($itemTypeId =='4')
					{
						$totAvlStock 		= CommonController::getTotalAvailableDyiengItemStock($itemId, $itemTypeId, $dyeingColor);
					} 	
					 				 
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
							<input type="hidden" name="received_quantities[]" value="<?=$Quantity;?>" class="form-control"> 
							<input type="hidden" name="work_process_req_ids[]" value="<?=$wprId;?>" class="form-control"> 
							<td class="text-left"><?=$warehouseName['WarehouseCompartment']; ?></td>
					</tr>
							 
					 
				<?php $i++;
				} ?>
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
								$balanceQ = $rowArr->quantity;								
							?>
							 <input type="hidden" id="wprId" name="wprId" value="<?=$rowArr->id;?>"> 
							 <?php 
								$warehouseItemStock = $rowArr->WarehouseItemStock; 
								if ($warehouseItemStock)
								{  
									foreach ($warehouseItemStock as $itemStock) 
									{
										// echo "<pre>"; print_r($itemStock);  exit;
										$stockTblId = $itemStock->wis_id;
						?>				

										 
						    <tr>
								<td class="text-left"> <?=$itemStock['Item']->item_name;?> </td> 	
								<td class="text-left"><?=$stockTblId?> <?=$itemStock['Item']->internal_item_name;?></td> 	
								<td class="text-left"><?=$itemStock->invoice_number?>  </td> 	
								<td class="text-left"><?=$itemStock->insp_taka_number;?></td> 	
								<td class="text-left"><?=$itemStock->dyeing_lot_number;?></td>	
								<td class="text-left"><?=$itemStock->dyeing_taka_number;?></td> 	
								<td class="text-left"><?=$itemStock->insp_bal_quan_size;?></td> 
								<td class="text-left"><?=$itemStock['WarehouseItem']['Warehouse']->warehouse_name;?></td> 	
								<td class="text-left"><?=$itemStock['WarehouseItem']['WarehouseCompartment']->warehousename;?></td> 
								<td>    
								<input type="number" id="req_grey_qty_<?=$stockTblId?>" readonly step="0.01" name="req_grey_qty[]" max="<?=$itemStock->insp_bal_quan_size;?>" onchange="updateTotalQuantity()">

								</td> 
								<td><input type="checkbox" id="wis_id_<?=$stockTblId?>" name="wis_id[]" onClick="addRequisition({{ $stockTblId }})" value="<?=$stockTblId;?>"> </td>
							</tr>		
										
										
						<?php		
									}
								}
							} 

						?>
									
						 
					</tbody>							 
					</table>
					  <table class="table table-bordered" id="myTable">
                <tbody>
					 		   
				  <tr>                  
                    <th>Required Quantity</th>
                    <td><input type="number" max="<?=@$balanceQ;?>" step="0.01" id="tot_req_quantity" name="tot_req_quantity" readonly > &nbsp; Meter </td>                    
                  </tr>  
				  
					 					  
                </tbody>
              </table>	 
				</div>
				
				
				<table class="table table-bordered">
					<input type="hidden" name="work_order_id" id="work_order_id" value="<?= $result['workOrdId']; ?>" class="form-control">
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
