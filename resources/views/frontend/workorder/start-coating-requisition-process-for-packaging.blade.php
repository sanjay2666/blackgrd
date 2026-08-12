<?php
  use \App\Http\Controllers\CommonController;	  
  $dataIG = $dataIG ?? collect();
  $item_type_id = $itemTypeId;
?>	 
<!DOCTYPE html>
<html lang="en">
<head>@include('frontend.common.head', ['pageTitle' => 'Start Requisition | Loomexa'])
</head>
<body class="hold-transition sidebar-mini requisition-page">
<!--preloader-->
<div id="preloader">
  <div id="status"></div>
</div>
<!-- Site wrapper -->
<div class="wrapper"> @include('frontend.common.header')
  <div class="content-wrapper">
    <section class="content">
      <div class="row">
        <div class="col-sm-12">
		{!! display_message('message') !!}
          <div class="panel panel-bd lobidrag">
            <div class="panel-heading warehouse-page-heading">
              <div>
                <h4><i class="fa fa-list-alt"></i> Start Requisition For Packaging Process</h4>
                <span>Prepare coating stock and general item requisition for packaging.</span>
              </div>
            </div>
            <div class="panel-body">
			<form method="post" action="{{ route('add_work_requisition_for_dyeing')}}" onSubmit="disableSubmitButton(this)" class="form-horizontal" autocomplete="off">
			@csrf
              <div class="wh-section-title">
                <span class="glyphicon glyphicon-list-alt"></span> Required Coating Item
              </div>
              <table class="table table-bordered">
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
									$coated_pvc ='';
									foreach($data['WorkOrderItem'] as $rowArr)
									{  
										// echo "<pre>"; print_r($rowArr);
										$item_name   = CommonController::getItemName($rowArr->item_id);
										$rowCoatingType = $rowArr->coated_pvc ?? $rowArr->coating_type ?? '';
										$coated_pvc .= $rowCoatingType;
								?>
								<tr> 
									<td><?=$item_name;?> </td> 
									<td><?=$rowArr->pcs;?> </td> 
									<td><?=$rowArr->cut;?> </td> 
									<td><?=$rowArr->meter;?> </td> 
									<td><?=$rowArr->grey_quality;?> </td>  
									<td><?=$rowArr->dyeing_color;?> </td> 
									<td><?=$rowCoatingType;?> </td>  
									<td><?=$rowArr->extra_job;?> </td> 
									<td><?=$rowArr->print_job;?> </td>   
								</tr>
								<?php } ?> 
								 
				</tbody>
              </table>
			   
			   <div class="wh-section-title">
                <span class="glyphicon glyphicon-plus-sign"></span> General Item
              </div>
			   <table class="table table-bordered" id="myTable">
                <tbody>
                  <tr>
                    <input type="hidden" id="itemIdReq" name="itemIdReq" value="<?=$itemId;?>">
                    <input type="hidden" id="work_order_id_req" name="work_order_id_req" value="<?=$workOrderId;?>">
                    <th><span id="ReqProduct"></span>General Item </th>
                    <th>Quantity</th>
                    <th>Unit</th>
                  </tr>
                  <tr>
                    <td><select  class="form-control" name="req_item_id[]">
                        <option value=""> Select Item</option>
                        <?php foreach($dataIG as $rowArr) { ?>
							<option value="<?=$rowArr->item_id;?>"><?=$rowArr->item_name;?></option>
                        <?php } ?>
                      </select>
                    </td>
                    <td>
						<input type="number" min="1" class="form-control" id="req_quantity[]" name="req_quantity[]" required>						
					</td>
                    <td>Kg</td>
                    <td><button type="button" class="btn btn-success btn-xs" onClick="addRow()">Add Row</button></td>
                  </tr>				  
                </tbody>
              </table> 
			   
				
              <div class="wh-section-title">
                <span class="glyphicon glyphicon-th-large"></span> Available Coating Stock List
              </div>
              <table class="table table-bordered">
                <tbody>
				
					<tr>
						<th>Item Name</th> 
						<th>Avaliable</th>
						<th>Required</th>
						<th></th>
					</tr>	 
					<?php  
						$resultArray = CommonController::getWarehouseAvailableCoatingItemStockArray($itemId, $itemTypeId, $coated_pvc); 
						$item_type_id = $itemTypeId;
						
						foreach ($resultArray as $result) 
						{
							   // echo "<pre>"; print_r($result);  // exit;
							$totalItemQty 		= $result->insp_bal_quan_size;
							$stockTblId 		= $result->wis_id;
							$item_type_name  	= CommonController::getItemTypeName($result->item_type_id); 
							$item_type_id 		= $result->item_type_id;
					?>	

                  <tr>
                    
                    <td> <?=$item_name;?> </td> 
                    <td> <?=$totalItemQty;?> Meter <?=$item_type_name;?></td>					
					<td> <strong>Required </strong> <input type="number" id="req_grey_qty_<?=$stockTblId?>" readonly name="req_grey_qty[]"> Meter <?=$item_type_name;?></td>
                    <td> <input type="checkbox" id="wis_id_<?=$stockTblId?>" name="wis_id[]" onClick="addRequisition({{ $stockTblId }})" value="<?=$stockTblId;?>"> </td>			
                  </tr>	 
				  <?php } ?>  				  
				  
				   <input type="hidden" id="ext_item_type_id" name="ext_item_type_id" value="<?=$item_type_id;?>">				    
                </tbody>
              </table> 			  
			  
			  
			<?php 
				$unitTypeId = 2;
				$balanceQ = CommonController::check_warehouse_item_type_balance($itemId,$itemTypeId,$unitTypeId);
			?>	 
				<input type="hidden" id="itemIdReq" name="itemIdReq" value="<?=$itemId;?>">
				<input type="hidden" id="work_order_id_req" name="work_order_id_req" value="<?=$workOrderId;?>">
				<input type="hidden" min="1" max="<?=$balanceQ;?>" id="tot_req_quantity" name="tot_req_quantity" required>
			
			<?php /* ?>	
              <table class="table table-bordered" id="myTable">
                <tbody>
					<tr> 
						<span id="ReqProduct"></span>  
						<th>Avaliable Unit </th>
						<td> <?=$balanceQ;?> Meter </td>   
					</tr> 				   
					<tr>                  
						<th>Required Quantity</th>
						<td><input type="number" min="1" max="<?=$balanceQ;?>" id="tot_req_quantity" name="tot_req_quantity" required> &nbsp; Meter </td>                    
					</tr>				  
                </tbody>
              </table>	
			<?php */ ?>         
			  
			  <div class="requisition-actions">
			  	<button type="submit" class="btn btn-success">Send Requisition </button>
			  	<div class="clearfix"></div>
			  </div>
			</form>
			  
			</div> 
			
          </div>
        </div>
      </div>
    </section>
  </div>
  @include('frontend.common.footer') </div>
@include('frontend.common.footerscript')


<script type="text/javascript">
    function disableSubmitButton(form) {
        const submitButton = form.querySelector('button[type="submit"]');
        submitButton.disabled = true;
        submitButton.innerHTML = 'Submitting...'; // Optional: Change button text while submitting
    }
</script> 
  
<script>
    var siteUrl = "{{ url('/') }}";
    var csrfToken = "{{ csrf_token() }}";
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
                } catch (error) {
                    console.error("Error parsing JSON response:", error);
                }
            },
            error: function (xhr, status, error) {
                console.error("AJAX request failed:", error);
            }
        });
    }
</script>


<script>
	function addRow() 
	{
		var table = document.getElementById("myTable");
		var newRow = table.insertRow(table.rows.length);
		var cell1 = newRow.insertCell(0);
		var cell2 = newRow.insertCell(1); 
		var cell3 = newRow.insertCell(2); 
		var cell4 = newRow.insertCell(3); 

		cell1.innerHTML = '<select  class="form-control" name="req_item_id[]"><option value=""> Select Item</option><?php foreach($dataIG as $rowArr) { ?><option value="<?=$rowArr->item_id;?>"><?=$rowArr->item_name;?></option><?php } ?></select> ';
		
		cell2.innerHTML = '<input type="number" min="1" class="form-control" id="req_quantity[]" name="req_quantity[]" required>';		
		cell3.innerHTML = 'Kg'; 
		cell4.innerHTML = '<button type="button" class="btn btn-danger btn-xs" onclick="deleteRow(this)">Delete</button>'; 
	}

	function deleteRow(button) {
		var row = button.parentNode.parentNode;
		row.parentNode.removeChild(row);
	}
</script>
 
</body>
</html>
