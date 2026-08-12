<?php
	use \App\Http\Controllers\CommonController;	 
	$canSendRequisition = $canSendRequisition ?? true;
	$dataICH = $dataICH ?? collect();
	$dataIC = $dataIC ?? collect();
	$item_name = $workOrderItemName ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>@include('frontend.common.head', ['pageTitle' => 'Start Requisition | Loomexa'])
</head>
<body class="hold-transition sidebar-mini requisition-page">
<!--preloader-->
<!-- Site wrapper -->
<div class="wrapper"> @include('frontend.common.header')
  <div class="content-wrapper">
    <section class="content">
      <div class="row">
        <div class="col-sm-12"> {!! display_message('message') !!}
          <div class="panel panel-bd lobidrag">
            <div class="panel-heading warehouse-page-heading">
              <div>
                <h4><i class="fa fa-list-alt"></i> Start Requisition For Dyeing Process</h4>
                <span>Prepare greige material requisition for dyeing.</span>
              </div>
            </div>
            <div class="panel-body">
              <form method="post" action="{{ route('addWorkRequisitionForRfDyeing') }}" onSubmit="disableSubmitButton(this)" class="form-horizontal" autocomplete="off">
                @csrf
                <div class="wh-section-title">
                  <span class="glyphicon glyphicon-list-alt"></span> Required Greige Item
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
								foreach($data['WorkOrderItem'] as $rowArr) 
								{  
								$item_name = CommonController::getItemName($rowArr->item_id);
								$ItemInternalName = CommonController::getItemInternalName($rowArr->item_id);
							?>
                            <tr>
                              <td><?=$item_name;?></td>
                              <td><?=$rowArr->pcs;?></td>
                              <td><?=$rowArr->cut;?></td>
                              <td><?=$rowArr->meter;?></td>
                              <td><?=$ItemInternalName;?></td>
                              <td><?=$rowArr->dyeing_color;?></td>
                              <td><?=$rowArr->coated_pvc ?? $rowArr->coating_type ?? '';?></td>
                              <td><?=$rowArr->extra_job;?></td>
                              <td><?=$rowArr->print_job;?></td>
                            </tr>
                            <?php } ?>
                  </tbody>
                </table>
				
                 
				<input type="hidden" id="ext_item_type_id" name="ext_item_type_id" value="<?=$itemTypeId;?>">
				<?php 
					$unitTypeId = 2;
					$balData 	= CommonController::getWarehouseItemTypeBalanceId($itemId,$itemTypeId,$unitTypeId);
					$balanceQ 	= is_object($balData) ? ($balData->tot ?? 0) : (float) $balData;
					$balanceId 	= is_object($balData) ? ($balData->id ?? 0) : 0;
					$flag 		= !empty($balanceQ) ? 1 : 0;			
				?>
                <input type="hidden" id="itemIdReq" name="itemIdReq" value="<?=$itemId;?>">
                <input type="hidden" id="work_order_id_req" name="work_order_id_req" value="<?=$workOrderId;?>">
                <div class="wh-section-title">
                  <span class="glyphicon glyphicon-scale"></span> Requisition Quantity
                </div>
                <table class="table table-bordered" id="myTable">
                  <tbody>
                    <tr> <span id="ReqProductrrr"></span>
                      <th>Avaliable Unit </th>
                      <td><span id="balance_qty_{{ $balanceId }}">
                        <?=$balanceQ;?>
                        </span> Meter <a class="btn btn-info btn-xs refresh-warehouse-item" data-balance-id="{{ $balanceId }}" onClick="RefreshWarehouseItem({{ $balanceId }})" href="javascript:void(0);"> <i class="fa fa-refresh"></i> </a> </td>
                    </tr>
                    <tr>
                      <th>Required Quantity</th>
                      <td><input type="number" step="0.01" onBlur="calculateBalanceQuantity(this.value)" oninput="updateRequisitionSubmitState()" id="tot_req_quantity" name="tot_req_quantity" required>
                        &nbsp; Meter</td>
                    </tr>
                    <tr>
                      <th>Balance Quantity</th>
                      <td><span id="balace_gty"> </span> &nbsp; </td>
                    </tr>
                    <tr>
                      <th>Lot No</th>
                      <td><input type="text" id="req_lot_no" name="req_lot_no" value=""></td>
                    </tr>
                    <tr>
                      <th>Febric Type</th>
                      <td><input type="radio" id="req_fabric_type" name="req_fabric_type" value="1" checked>
                        <label for="req_fabric_type">Fresh</label>
                        &nbsp;
                        <input type="radio" id="req_fabric_type" name="req_fabric_type" value="2">
                        <label for="req_fabric_type">RF</label>
                      </td>
                    </tr>
                  </tbody>
                </table>
                <?php if (empty($flag)) { ?>
                <p> Note: <b style="color: red;">
                  <?=$item_name;?>
                  Greige Item Not Available in Warehouse.</b></p>
                <?php } ?>
				
				<?php if($canSendRequisition && !empty($flag)){ ?>
					<div class="requisition-actions">
						<button type="submit" id="sendRequisitionButton" class="btn btn-success" disabled>Send Requisition</button>
						<div class="clearfix"></div>
					</div>
				<?php } elseif(!$canSendRequisition) { ?>
					<div class="alert alert-danger" style="margin-top:10px;">
						Received greige material has exceeded the required meter quantity by 10%. Further material cannot be accepted against the same PO. A new PO is required to continue.
					</div>
				<?php } ?>

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

<script type="text/javascript">
var siteUrl = "{{ url('/') }}";
var currentAvailableQuantity = parseFloat("{{ (float) $balanceQ }}") || 0;

function getAvailableQuantity() {
    return currentAvailableQuantity;
}

function setAvailableQuantity(id, value) {
    currentAvailableQuantity = parseFloat(value) || 0;
    $("#balance_qty_" + id).text(currentAvailableQuantity.toFixed(2));
}

function RefreshWarehouseItem(id) {
    if (!id) {
        alert("Warehouse balance record not found.");
        return;
    }

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
                $("a.refresh-warehouse-item[data-balance-id='" + id + "'] i").addClass("fa-spin");
            },
            success: function(response) {
                if (response.success) {
                    setAvailableQuantity(id, response.new_qty);
                    calculateBalanceQuantity();
                } else {
                    alert(response.message || "Failed to refresh data");
                }
            },
            error: function() {
                alert("Something went wrong!");
            },
            complete: function() {
                $("a.refresh-warehouse-item[data-balance-id='" + id + "'] i").removeClass("fa-spin");
            }
        });
    }
}
</script>
<script>
    function addChemRow() {
        var newRow = '<tr>' +
            '<td><select class="form-control" name="req_item_id[]"><option value="">Select Item</option><?php foreach($dataICH as $rowArr) { ?><option value="<?=$rowArr->item_id;?>"><?=$rowArr->item_name;?></option><?php } ?></select></td>' +
            '<td><input type="number" min="0.01" class="form-control req-quantity" name="req_quantity[]" required></td>' +
            '<td>Kg</td>' +
            '<td><button type="button" class="btn btn-danger btn-xs" onclick="removeRow(this)">Remove</button></td>' +
            '</tr>';
        
        $('#myTableChem tbody').append(newRow);
    }

    function removeRow(button) {
        $(button).closest('tr').remove();
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
					calculateBalanceQuantity();
                } catch (error) {
                    console.error("Error parsing JSON response:", error);
                }
            },
            error: function (xhr, status, error) {
                console.error("AJAX request failed:", error);
            }
        });
    }
	calculateBalanceQuantity();
</script>
<script>
function calculateBalanceQuantity() 
{
	var availableQuantity = getAvailableQuantity();
	var requiredQuantity = parseFloat($("#tot_req_quantity").val()) || 0;

	var balanceQuantity = availableQuantity - requiredQuantity;
	$("#balace_gty").text(balanceQuantity.toFixed(2) + " Meter");
	updateRequisitionSubmitState();
}

function updateRequisitionSubmitState()
{
	var submitButton = document.getElementById('sendRequisitionButton');
	var requiredQuantity = parseFloat(document.getElementById('tot_req_quantity').value) || 0;
	var availableQuantity = getAvailableQuantity();

	if (!submitButton) {
		return;
	}

	submitButton.disabled = !(availableQuantity > 0 && requiredQuantity > 0);
}
</script>
<script>
    function addRow() {
        var table = document.getElementById("myTable");
        var newRow = table.insertRow(table.rows.length);
        var cell1 = newRow.insertCell(0);
        var cell2 = newRow.insertCell(1);
        var cell3 = newRow.insertCell(2);
        var cell4 = newRow.insertCell(3);

        cell1.innerHTML = '<select class="form-control" name="req_item_id[]" onchange="toggleQuantityValidation(this)"><option value=""> Select Item</option><?php foreach($dataIC as $rowArr) { ?><option value="<?=$rowArr->item_id;?>"><?=$rowArr->item_name;?></option><?php } ?></select> ';

        cell2.innerHTML = '<input type="number" min="0.01" class="form-control req-quantity" name="req_quantity[]" required>'; // Note the added class 'req-quantity'
        cell3.innerHTML = 'Kg';
        cell4.innerHTML = '<button type="button" class="btn btn-danger btn-xs" onclick="deleteRow(this)">Delete</button>';
    }

    function deleteRow(button) {
        var row = button.parentNode.parentNode;
        row.parentNode.removeChild(row);
    }

    function toggleQuantityValidation(selectElement) {
        var quantityInput = selectElement.parentNode.nextElementSibling.firstChild;
        quantityInput.required = !!selectElement.value; // Set required attribute based on whether an item is selected
    }
</script>
</body>
</html>
