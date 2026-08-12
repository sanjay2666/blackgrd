<?php
	use \App\Http\Controllers\CommonController; 
	$WorkWarehouseId 	= $dataWO->insp_work_warehouse_id;
	$workOrderId 		= $dataWO->work_order_id;  
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta name="csrf-token" content="{{ csrf_token() }}">
@include('common.head')
</head>
<body class="hold-transition sidebar-mini receive-work-item-page">
 
<!-- Site wrapper -->
<div class="wrapper"> @include('common.header')
    <div class="content-wrapper">
    <section class="content">
      <div class="row">
        <!-- Form controls -->
        <div class="col-sm-12">
		 {!! CommonController::display_message('message') !!}
          <div class="panel panel-bd lobidrag receive-work-panel">
            <div class="panel-heading receive-work-heading">
              <div class="btn-group" id="buttonlist"><a href="javascript:void(0);"><h4>Received Work Item In Warehouse</h4></a></div>
            </div> 
			<div class="panel-body">
			  <form method="POST" action="{{ route('receive_work_item_in_warehouse') }}">
				@csrf
				<div class="row">
				  <div class="col-md-12">
				  <div class="receive-work-form-wrap">
					<?php if (!empty($dataWI['GatePass']->fabric_fault_reason_id)) { ?> 
					<p class="receive-work-alert">
					  <span>
						The items you are storing in your warehouse are defective.
					  </span>
					</p>
					<?php } ?>
					
					<div class="receive-work-section-title">
					  <span class="glyphicon glyphicon-inbox"></span> Warehouse Receiving Details
					</div>
					<div class="table-responsive receive-work-table-wrap">
					<table class="table table-bordered table-striped receive-work-table">
					  <tr>
						<th>Gate Pass No.</th>
						<th>Work Order Number</th>
						<th>Receiver Name</th>
						<th>Receiving Date</th>
						<th>Warehouse</th>
						<th>Warehouse Compartment</th>
						<th>Receiver Employee Name</th>
					  </tr>
					  <tr>
						<td>
						<input type="number" name="gate_pass_no[]" id="gate_pass_no" class="form-control" value="<?= $dataWI['GatePass']->id; ?>">
						<input type="hidden" id="machine_id" name="machine_id" value="<?=$dataWI->machine_id;?>">
						<input type="hidden" id="fabric_fault_reason_id" name="fabric_fault_reason_id" value="<?=$dataWI['GatePass']->fabric_fault_reason_id;?>">
                        <input type="hidden" name="insp_id" id="insp_id" value="<?=$inspId;?>">
						</td>
						<td><input type="text" id="work_order_id" class="form-control" required name="work_order_id" value="<?= $workOrderId; ?>"></td>
						<td>
						  <input type="text" id="receiver_name" required class="form-control" name="receiver_name" value="<?= $userD->name; ?>">
						  <input type="hidden" id="receiver_id" value="<?= $userD->individual_id; ?>" name="receiver_id">
						</td>
						<td>
						  <input type="text" id="receiving_date" required data-date-format="yyyy-mm-dd" value="<?= date('d-m-Y'); ?>" name="receiving_date" class="form-control">
						</td>
						<td>
						  <select class="form-control" name="warehouseId" required id="warehouseId" onChange="selectCompartment(this.value);">
							<option value="">Please Select Warehouse</option>
							<?php foreach ($dataW as $val) { ?>
							<option value="<?= $val->id; ?>" <?php if ($val->id == $WorkWarehouseId) echo "selected"; ?>>
							  <?= $val->warehouse_name; ?>
							</option>
							<?php } ?>
						  </select>
						</td>
						<td>
						  <div id="warehouseCompIdDiv"></div>
						</td>
						
						<td>
						  <input type="text" id="emp_name" class="form-control" required name="emp_name">
						  <input type="hidden" id="ind_emp_id" name="ind_emp_id">
						  <span id="warehouseEmpDiv"></span>
						</td>
					  </tr>
					</table>
					</div>

					<div class="receive-work-section-title">
					  <span class="glyphicon glyphicon-list-alt"></span> Work Item Details
					</div>
					<div class="table-responsive receive-work-table-wrap">
					<table class="table table-bordered table-striped receive-work-table">
					  <tr>
						<th>Process Type</th>
						<th>Item Type</th>
						<th>Item Name</th>
						<th>Dyeing Color</th>
						<th>Coated PVC</th>
						<th>Extra Job</th>
						<th>Print Job</th>
					  </tr>
					  <tr>
						<td>
						  <select name="process_type_id" id="process_type_id" required class="form-control">
							<option value="">Select Process Type</option>
							<?php foreach ($dataPI as $rowp) { ?>
							<option value="<?= $rowp->id; ?>" <?php if ($rowp->id == $ProcessTypeId) echo "selected"; ?>>
							  <?= $rowp->process_name; ?>
							</option>
							<?php } ?>
						  </select>
						</td>
						<td>
						  <select name="item_type_id" required id="item_type_id" class="form-control">
							<option value="">Select Item Type</option>
							<?php foreach ($dataIT as $row) { ?>
							<option value="<?= $row->item_type_id; ?>" <?php if ($row->item_type_id == $ItemTypeId) echo "selected"; ?>>
							  <?= $row->item_type_name; ?>
							</option>
							<?php } ?>
						  </select>
						</td>
						<td>
						  <input type="text" id="item_name" class="form-control" value="<?= $dataWO->item_name; ?>" name="item_name" required>
						  <input type="hidden" id="item_id" value="<?= $dataWO->item_id; ?>" name="item_id">
						</td>
						<td>
						  <input type="text" id="dyeing_color" class="form-control" readonly value="<?= $dataWI->dyeing_color; ?>" name="dyeing_color">
						</td>
						<td>
						  <input type="text" id="coated_pvc" class="form-control" readonly value="<?= $dataWI->coated_pvc ?? $dataWI->coating_type ?? ''; ?>" name="coated_pvc">
						</td>
						<td>
						  <input type="text" id="extra_job" class="form-control" readonly value="<?= $dataWI->extra_job; ?>" name="extra_job">
						</td>
						<td>
						  <input type="text" id="print_job" class="form-control" readonly value="<?= $dataWI->print_job; ?>" name="print_job">
						</td>
					  </tr>
					</table>
					</div>

					<div class="receive-work-section-title">
					  <span class="glyphicon glyphicon-th-list"></span> Inspection Stock Details
					</div>
					<div class="table-responsive receive-work-table-wrap">
					<table class="table table-bordered table-striped receive-work-table receive-work-detail-table" id="myTable">
					  <thead>
						<tr>
						  <th>Greige Taka No.</th>
						  <th>Dyieng Lot No.</th>
						  <th>Dyieng Taka No.</th>
						  <th>EPI</th> 
						  <th>PPI</th> 
						  <th>Width</th> 
						  <th>GSM</th>  
						  <th>Quantity</th>
						  <th>Size (Meter)</th>
						  <th>Is Fault</th>
						   
						 
						</tr>
					  </thead>
							<tbody>
							<?php foreach($dataWI['WorkInspectionDetail'] as $index => $tblRow) {   // echo "<pre>"; print_r($tblRow['FabricFaultReason']);     ?>
							<tr>
							<td><input type="text" readonly id="taka_number_<?= $index; ?>" class="form-control" name="taka_number[]" value="<?=$tblRow->insp_taka_number;?>"></td>
							<td><input type="text" readonly name="dyeing_lot_number[]" class="form-control" value="<?=$tblRow->dyeing_lot_number;?>"> </td>
							<td> <input type="text" id="dyeing_taka_number_<?= $index; ?>" readonly class="form-control"  name="dyeing_taka_number[]" value="<?=$tblRow->dyeing_taka_number;?>"> </td>
							<td> <input type="text" id="insp_epi_<?=$index;?>" readonly name="insp_epi[]" class="form-control" value="<?=$tblRow->insp_epi;?>"> </td>
							<td> <input type="text" id="insp_ppi_<?=$index;?>" readonly name="insp_ppi[]" class="form-control" value="<?=$tblRow->insp_ppi;?>"> </td>
							<td> <input type="text" id="insp_width_<?=$index;?>" readonly name="insp_width[]" class="form-control"  value="<?=$tblRow->insp_width;?>"> </td>
							<td> <input type="text" id="insp_gsm_<?=$index;?>" readonly name="insp_gsm[]" class="form-control" value="<?=$tblRow->insp_gsm;?>"> </td>
							
							<td>1</td>
							<td> <input type="number" min="0" readonly name="quan_size[]" class="form-control" id="quan_size_<?= $index; ?>" value="<?=$tblRow->output_quan_size;?>"> </td>
							
							<td> 
							 <?php if(!empty($tblRow['FabricFaultReason']->id)) { ?>
							<input type="hidden" name="fault_reason_id[]" id="fault_reason_<?= $index; ?>" value="<?=$tblRow['FabricFaultReason']->id;?>"> 
							<span><?=$tblRow['FabricFaultReason']->reason;?></span>
							 <?php } else { ?> 
							 
							 No
							 <input type="hidden" name="fault_reason_id[]" id="fault_reason_<?= $index; ?>" value="0"> 
							 <?php } ?>
							</td> 
							 
							</tr>
							
					<tr>
						<th>Comment</th>
						<td colspan="10"><input type="text" min="0" class="form-control" name="item_remark[]" id="item_remark_<?=$index;?>" value="<?=$dataWI->insp_comment;?>"> </td>
					</tr>
							
							<?php } ?>
							</tbody>
					</table>				  
					</div>
				  
				  </div>
				  </div>
				  <div class="col-md-12 receive-work-actions" id="main_div">
					<button id="submitBtn" type="submit" class="btn btn-success"><i class="fa fa-check"></i> Confirm</button>
				  </div>
				</div>
			  </form>
			  
			  
			</div>          
			 
          </div>
        </div>
      </div>
    </section>
    <!-- /.content -->
  </div>
  <!-- /.content-wrapper -->
  @include('common.footer') </div>
@include('common.formfooterscript')
 

<script>
document.addEventListener('DOMContentLoaded', function () {
  const form = document.querySelector('form[action="{{ route("receive_work_item_in_warehouse") }}"]');
  const btn  = document.getElementById('submitBtn');

  if (!form || !btn) {
    console.warn('Form or submit button not found');
    return;
  }

  // flag to prevent double action
  let clicked = false;

  // Handle direct button click: disable + force submit
  btn.addEventListener('click', function (e) {
    if (clicked) {
      e.preventDefault();
      return;
    }
    clicked = true;

    // immediate visual feedback
    btn.disabled = true;
    btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Processing...';

    // Force the form to submit on the next tick to avoid interfering with other handlers
    setTimeout(function () {
      try {
        if (typeof form.requestSubmit === 'function') {
          form.requestSubmit(); // modern, respects validation
        } else {
          form.submit(); // fallback
        }
      } catch (err) {
        console.error('Submit failed:', err);
        // allow retry if something goes wrong
        clicked = false;
        btn.disabled = false;
        btn.innerText = 'Confirm';
      }
    }, 10);
  });

  // Also ensure that if form is submitted by other means, we disable the button next tick
  form.addEventListener('submit', function (e) {
    // don't call e.preventDefault() — we want native submit
    setTimeout(function () {
      btn.disabled = true;
      btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Processing...';
      clicked = true;
    }, 10);
  });
});
</script> 


<!---------search_warehouse_compartment -- getWarehouseCompEmployee-------------->
<script type="text/javascript"> 
var siteUrl = "{{ url('/') }}";
var searchWarehouseCompartmentUrl = "{{ route('search_warehouse_compartment') }}";
var getWarehouseCompEmployeeUrl = "{{ route('getWarehouseCompEmployee') }}";

<?php if(!empty($WorkWarehouseId)) { ?>
	var Id = {!! $WorkWarehouseId !!};
	selectCompartment(Id);
<?php } ?>

function selectCompartment(Id)
{ 
	$.ajax({
	type: "GET", 
		url: searchWarehouseCompartmentUrl,
			data: {
				"_token": "{{ csrf_token() }}",
				"Id":Id,
			},	 	
		cache: false, 
		success: function(res){   
			$( "#warehouseCompIdDiv" ).html(res);

			var $warehouseComp = $("#warehouseCompId");
			var selectedCompId = $warehouseComp.val();

			if (!selectedCompId) {
				var firstCompId = $warehouseComp.find('option[value!=""]').first().val();
				if (firstCompId) {
					$warehouseComp.val(firstCompId);
					selectedCompId = firstCompId;
				}
			}

			if (selectedCompId) {
				selectEmployee(selectedCompId);
			} else {
				$("#emp_name").val('');
				$("#ind_emp_id").val('');
			}
		}					
	})	   
	
}

function selectEmployee(Id)
{ 
	$.ajax({
	type: "GET", 
		url: getWarehouseCompEmployeeUrl,
			data: {
				"_token": "{{ csrf_token() }}",
				"Id":Id,
			},	 	
		cache: false, 
		success: function(msg){ 
			var data = msg.split("||");  
			$( "#emp_name" ).val(data[1] || '');
			$( "#ind_emp_id" ).val(data[0] || ''); 
		},
		error: function() {
			$( "#emp_name" ).val('');
			$( "#ind_emp_id" ).val('');
		}					
	})		
}


</script>
 


</body>
</html>
