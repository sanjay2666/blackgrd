<?php
error_reporting(0);
	use \App\Http\Controllers\CommonController;	  
	$itemId 	= $data->item_id; 
	
	// echo "<pre>"; print_r($dataWIS); exit;
	
?>
<!DOCTYPE html>
<html lang="en">
<head>@include('frontend.common.head', ['pageTitle' => 'Start Requisition | Loomexa'])
</head>
<body class="hold-transition sidebar-mini">
<!--preloader-->
<!-- Site wrapper -->
<div class="wrapper"> @include('frontend.common.header')
  <div class="content-wrapperd">
    <section class="content">
      <div class="row">
        <div class="col-sm-12"> {!! display_message('message') !!}
          <div class="panel panel-bd lobidrag">
            <div class="panel-heading">
              <div class="btn-group" id="buttonexport"><a href="javascript:void(0);">
                <h4><?=$printName;?> Fabric Requisition for Printing</h4>
                </a></div>
            </div>
            <div class="panel-body">
              
			<form method="post" action="{{ route('add-dyed-fabric-printing-requisition') }}" onSubmit="disableSubmitButton(this)" class="form-horizontal">

                @csrf
                <table class="table table-bordered">
                  <tbody>
                    <tr>
                      <th>Required Dyeing Item</th>
                      <td><table class="table table-bordered">
                          <tbody>
                            <tr>
                              <th>Item</th>
                              <th>Cut</th>
                              <th>Pic</th>
                              <th>Meter</th>
                              <th>Greige Quality</th>
                              <th>Dyeing Color</th>
                              <th>Coated Pvc</th>
                              <th>Extra Job</th>
                              <th>Print Job</th>
                            </tr>
                            <?php 
								$dyingColor ='';
								$requiredMtr = 0;
								foreach($data['WorkOrderItem'] as $rowArr)
								{   
									$item_name   		= CommonController::getItemName($rowArr->item_id);
									$ItemInternalName 	= CommonController::getItemInternalName($rowArr->item_id);
									$dyingColor  		= $rowArr->dyeing_color;
									$requiredMtr 		+= $rowArr->meter;
							?>
                            <tr>
                              <td><?=$item_name;?></td>
                              <td><?=$rowArr->pcs;?> </td>
                              <td><?=$rowArr->cut;?> </td>
                              <td><?=$rowArr->meter;?> </td>
                              <td><?=$ItemInternalName;?> </td>
                              <td><?=$rowArr->dyeing_color;?>  </td>
                              <td><?=$rowArr->coated_pvc ?? $rowArr->coating_type ?? '';?>  </td>
                              <td><?=$rowArr->extra_job;?> </td>
                              <td><?=$rowArr->print_job;?> </td>
                            </tr>
                            <?php } ?>
                          </tbody>
                        </table></td>
                    </tr>
                  </tbody>
                </table>
               
				<table class="table table-bordered">
                  <tbody>
                    <tr>
                      <th>Item Name</th>
                      <th>Greige Taka Number</th>
                      <th>Dyieng Lot Number</th>
                      <th>Dyieng Taka Number</th>
                      <th>Dyieng</th>
                      <th>Coating</th>
                      <th>Avaliable</th>
                      <th>Taka Select</th>
                    </tr>
                    <?php  
					
						$getWRWisIds  	= CommonController::getPrintWorkProcessRequirementWisIds($workOrderId);  
						$wrWisIdsArray  = explode(',', $getWRWisIds);
						 
						foreach ($dataWIS as $result) 
						{ 
							// echo "<pre>"; print_r($result['Item']->item_name); 
							$totalItemQty 		= $result->insp_bal_quan_size;
							$stockTblId 		= $result->wis_id;
							$insp_taka_number 	= $result->insp_taka_number;
							$dyeing_lot_number 	= $result->dyeing_lot_number;
							$dyeing_taka_number = $result->dyeing_taka_number;
							$dyeingColor 		= $result->dyeing_color;
							$coatedPvc 			= $result->coated_pvc ?? $result->coating_type ?? '';
							$itemName 			= $result['Item']->item_name;
							$item_type_id 		= $result->item_type_id; 
							$isDisabled 		= in_array($stockTblId, $wrWisIdsArray) ? 'disabled' : '';
					?>
                    <tr>
                      <td><?=$itemName;?></td>
                      <td><?=$insp_taka_number;?> </td>
                      <td><?=$dyeing_lot_number;?> </td>
                      <td><?=$dyeing_taka_number;?></td>
                      <td><?=$dyeingColor;?></td>
                      <td><?=$coatedPvc;?></td>
                      <td><?=$totalItemQty;?> Meter <input type="text" id="req_grey_qty_<?=$stockTblId?>" name="req_grey_qty[]"></td>                      
                      <td><input type="checkbox" id="wis_id_<?=$stockTblId?>" name="wis_id[]" value="<?=$stockTblId;?>" <?=$isDisabled;?> data-dyeing-lot-number="<?=$dyeing_lot_number;?>" data-quantity="<?=$totalItemQty;?>">
                      </td>
                    </tr>
                    <?php } ?>
                  <input type="hidden" id="ext_item_type_id" name="ext_item_type_id" value="<?=$item_type_id;?>">
                  </tbody>
                  
                </table>
				
				<?php  
					$unitTypeId 	= 2; 
					$balData	  	= CommonController::getStockBalanceId($itemId, $itemTypeId, $dyeingColor, $coatedPvc);			 
					$balanceQ 		= $balData->tot;
					$balanceId 		= $balData->id; 
					$flag 			= !empty($balanceQ) ? 1 : 0;	
					$TotrequiredMtr = $requiredMtr + ($requiredMtr * 0.50);   		
				?>
			
				<?php $ptype = ($printType == 'cp') ? 3 : 2; ?>
                <input type="hidden" id="printType" name="printType" value="<?=$ptype;?>">
				
				
                <input type="hidden" id="itemIdReq" name="itemIdReq" value="<?=$itemId;?>">
                <input type="hidden" id="work_order_id_req" name="work_order_id_req" value="<?=$workOrderId;?>">
                <input type="hidden" id="dyeingColor" name="dyeingColor" value="<?=$dyeingColor;?>">
                <input type="hidden" id="coatedPvc" name="coatedPvc" value="<?=$coatedPvc;?>">
				
                <table class="table table-bordered" id="myTable">
                  <tbody>
                    <tr>
                      <th>Available Unit</th>
                      <td><strong> <span id="balance_qty_{{ $balanceId }}"> <?= $balanceQ; ?> </span> Meter </strong> <a class="btn btn-sm btn-info ml-2" onClick="RefreshWarehouseItem({{ $balanceId }})" href="javascript:void(0);"> <i class="fa fa-refresh"></i> </a> </td>
                    </tr>
                    <tr>
                      <th>Required Quantity</th>
						<td>
						<div class="form-inline">
						<input type="number" class="form-control mr-2" max="<?= $TotrequiredMtr; ?>" step="0.01" id="tot_req_quantity" name="tot_req_quantity" required onChange="validateQuantity(this)"> <span>Meter</span> 
						</div>
						<small id="error_msg" class="form-text mt-1" style="display: none;"> 
						<span style="color: #b30000;"> ❌ You can't allocate more than <strong style="color: #800000;">50%</strong> of the required fabric. </span><br>
						<span style="color: #004080;"> 🎯 Only <strong style="color: #001f4d;"> <?= $requiredMtr; ?> meters</strong> of dyed fabric is required. </span> </small> </td>
                    </tr>
                    <tr>
                      <th>Lot No</th>
                      <td><input type="text" required id="req_lot_no" name="req_lot_no" value=""></td>
                    </tr>
                  </tbody>
                </table>
                <?php  if(!empty($balanceQ)) { ?>
                <button type="submit" class="btn btn-success pull-left">Send Requisition </button>
                <?php }  if(empty($balanceQ)) { ?>
                <p> Note: <b style="color: red;">Some Dyed Item Not Available in Warehouse.</b></p>
                <?php } ?>
                 
                
               
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
    function disableSubmitButton(form) {
        const submitButton = form.querySelector('button[type="submit"]');
        submitButton.disabled = true;
        submitButton.innerHTML = 'Submitting...'; // Optional: Change button text while submitting
    }
</script> 

<script>
document.addEventListener('DOMContentLoaded', function () {
    let ctrlPressed = false;
    let lastCheckedIndex = -1;
    let totalQuantity = 0;

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Control') {
            ctrlPressed = true;
        }
    });

    document.addEventListener('keyup', function (e) {
        if (e.key === 'Control') {
            ctrlPressed = false;
        }
    });

    let checkboxes = Array.from(document.querySelectorAll('input[type="checkbox"][id^="wis_id_"]'));

    checkboxes.forEach(function (checkbox, index) {
        checkbox.addEventListener('click', function (e) {
            let isChecked = this.checked;

            updateQuantity(this, isChecked);

            if (ctrlPressed && lastCheckedIndex !== -1) {
                let start = Math.min(lastCheckedIndex, index);
                let end = Math.max(lastCheckedIndex, index);

                for (let i = start; i <= end; i++) {
                    let checkboxInRange = checkboxes[i];
                    let isAlreadyChecked = checkboxInRange.checked;

                    if (isChecked && !isAlreadyChecked) {
                        checkboxInRange.checked = true;
                        updateQuantity(checkboxInRange, true);
                    } else if (!isChecked && isAlreadyChecked) {
                        checkboxInRange.checked = false;
                        updateQuantity(checkboxInRange, false);
                    }
                }
            }

            lastCheckedIndex = index;
        });
    });

    function updateQuantity(checkbox, isChecked) {
        let quantity = parseFloat(checkbox.getAttribute('data-quantity'));
        let dyeingLotNumber =  checkbox.getAttribute('data-dyeing-lot-number');
        let inputField = document.getElementById(`req_grey_qty_${checkbox.value}`);

        if (isChecked) {
            totalQuantity += quantity;
            inputField.value = quantity; // Update individual input field
        } else {
            totalQuantity -= quantity;
            inputField.value = ""; // Clear value if unchecked
        }
			
		const maxAllowed = parseFloat(document.getElementById("tot_req_quantity").getAttribute("max"));
		const errorMsg = document.getElementById("error_msg");	

        document.getElementById("tot_req_quantity").value = totalQuantity.toFixed(2);
        document.getElementById("req_lot_no").value = dyeingLotNumber;
		
		if (totalQuantity > maxAllowed) {
			errorMsg.style.display = "inline";
		} else {
			errorMsg.style.display = "none";
		}
    }
});
</script>
<script>
$(document).on("change", "input[name='req_grey_qty[]']", function() {
	updateTotalQuantity();
});

function updateTotalQuantity() {
	var totalQuantity = 0;
	$("input[name='req_grey_qty[]']").each(function() {
		var quantity = parseFloat($(this).val()) || 0;
		totalQuantity += quantity;
	});
	$("#tot_req_quantity").val(totalQuantity.toFixed(2));
}
</script>
<script>  
 function selectSameDyeingLot(checkbox, dyeingLotNumber) 
 {
    var isChecked = checkbox.checked;
    var totalQuantity = 0;

    // Unselect all checkboxes and clear quantities
    $("input[type=checkbox][name='wis_id[]']").each(function () {
        $(this).prop('checked', false); // Uncheck all checkboxes
        var stockId = $(this).val();
        var inputId = "req_grey_qty_" + stockId;
        $("#" + inputId).val('').attr("readonly", true); // Clear quantities
    });

    if (isChecked) {
        // Find all checkboxes with the same dyeing_lot_number
        $("input[type=checkbox][data-dyeing-lot-number='" + dyeingLotNumber + "']").each(function () {
            var stockId = $(this).val();
            var inputId = "req_grey_qty_" + stockId;

            // Simulate the addRequisition logic
            $.ajax({
                type: "GET",
                url: siteUrl + '/ajax_script/getSumWarehouseItemStockValue',
                data: { 
                    "FId": stockId,
                },
                cache: false,
                success: function (response) {
                    try {
                        var numericResponse = parseFloat(response.quantity);
                        totalQuantity += numericResponse;
                        $("#" + inputId).val(numericResponse).removeAttr("readonly");
                        
                        // Update the tot_req_quantity field
                        $("#tot_req_quantity").val(totalQuantity.toFixed(2));
                    } catch (error) {
                        console.error("Error parsing JSON response:", error);
                    }
                },
                error: function (xhr, status, error) {
                    console.error("AJAX request failed:", error);
                }
            });

            $(this).prop('checked', true); // Check the current checkbox
        });

        // Update the req_lot_no field with the selected dyeing lot number
        $("#req_lot_no").val(dyeingLotNumber);
    } else {
        // If unselected, clear the req_lot_no field
        $("#req_lot_no").val('');
        $("#tot_req_quantity").val(totalQuantity.toFixed(2));
    }
}

</script>
 
<script type="text/javascript">

var siteUrl = "{{ url('/') }}";
function RefreshWarehouseItem(id) {
    if(confirm("Do you really want to refresh this record?")) {
        jQuery.ajax({
            type: "GET",
            url: siteUrl + '/ajax_script/RefreshWarehouseItem',
            data: {
                "_token": "{{ csrf_token() }}",
                "FId": id
            },
            cache: false,
            beforeSend: function() {
                // Adding a spinning effect
                $("a[onclick='RefreshWarehouseItem(" + id + ")'] i").addClass("fa-spin");
            },
            success: function(response) {
                if (response.success) {
                    $("#balance_qty_" + id).text(response.new_qty); // Updated value dynamically
                } else {
                    alert("Failed to refresh data");
                }
            },
            error: function() {
                alert("Something went wrong!");
            },
            complete: function() {
                // Remove spinning effect after request completes
                $("a[onclick='RefreshWarehouseItem(" + id + ")'] i").removeClass("fa-spin");
            }
        });
    }
}
</script>
</body>
</html>
