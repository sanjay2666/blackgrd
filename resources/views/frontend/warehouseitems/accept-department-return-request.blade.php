<?php
	 error_reporting(0);
	 
	// echo "<pre>"; print_r(array($dataDpr)); exit;
	$selectedWarehouseId = isset($WorkWarehouseId) ? $WorkWarehouseId : null;
	$returnItems = !empty($dataDpr['DepartmentReturnRequest']) ? $dataDpr['DepartmentReturnRequest'] : [];
	$totalMeters = 0;
	foreach ($returnItems as $returnItem) {
		$totalMeters += (float) $returnItem->item_qty;
	}
?>
<!DOCTYPE html>
<html lang="en">
<meta name="csrf-token" content="{{ csrf_token() }}">
<head>@include('frontend.common.head', ['pageTitle' => 'Accept Department Return | Loomexa'])
</head>
<body class="hold-transition sidebar-mini accept-department-return-page">
 
<!-- Site wrapper -->
<div class="wrapper"> @include('frontend.common.header')
    <div class="content-wrapperd return-request-page">
    <section class="content">
      <div class="row">
        <!-- Form controls -->
        <div class="col-sm-12">
		 {!! display_message('message') !!}
          <div class="panel panel-bd lobidrag accept-return-panel">
            <div class="panel-heading accept-return-heading">
              <div>
                <h4>Accept Department Return</h4>
                <span>Check returned items and receive stock into the selected warehouse.</span>
              </div>
              <a class="btn btn-default btn-sm" href="{{ route('show-department-return-requests') }}">
                <i class="fa fa-arrow-left"></i> Back
              </a>
            </div> 
			<div class="panel-body">
			  <form method="POST" action="{{ route('storeDepartmentReturnRequest') }}" name="departItemReturn" id="departItemReturn">
				@csrf
				<input type="hidden" id="department_return_id" class="form-control" required name="department_return_id" value="<?=$dataDpr->id;?>">
				<input type="hidden" id="work_order_id" required name="work_order_id" value="<?=$dataDpr->work_order_id;?>">

				<div class="return-request-hero">
					<div class="return-hero-title-wrap">
						<div class="return-hero-icon">
							<i class="fa fa-archive"></i>
						</div>
						<div class="return-hero-copy">
							<h3>Accept Department Return Request</h3>
							<p>Review returned items and receive them into warehouse stock.</p>
						</div>
					</div>
					<div class="return-status-badge">
						<i class="fa fa-clock-o"></i> Pending Approval
					</div>
				</div>

				<div class="return-section">
					<div class="return-compact-summary">
						<div class="return-compact-item">
							<span>Request ID</span>
							<strong>#<?=$dataDpr->id;?></strong>
						</div>
						<div class="return-compact-item">
							<span>Work Order ID</span>
							<strong><?=$dataDpr->work_order_id;?></strong>
						</div>
						<div class="return-compact-item">
							<span>Total Items</span>
							<strong><?=count($returnItems);?></strong>
						</div>
						<div class="return-compact-item">
							<span>Total Meter</span>
							<strong><?=number_format($totalMeters, 2);?></strong>
						</div>
					</div>
					<div class="row">
						<div class="col-md-2 col-sm-6">
							<div class="form-group">
								<label for="receiver_name">Receiver Name</label>
								<input type="text" id="receiver_name" required class="form-control" name="receiver_name" value="<?=$userD->name; ?>" readonly>
								<input type="hidden" id="receiver_id" value="<?=$userD->individual_id;?>" name="receiver_id">
							</div>
						</div>
						<div class="col-md-2 col-sm-6">
							<div class="form-group">
								<label for="receiving_date">Receiving Date</label>
								<input type="text" readonly id="receiving_date" required data-date-format="yyyy-mm-dd" value="<?= date('d-m-Y');?>" name="receiving_date" class="form-control">
							</div>
						</div>
						<div class="col-md-2 col-sm-6">
							<div class="form-group">
								<label for="warehouseId">Warehouse</label>
								<select class="form-control" name="warehouseId" required id="warehouseId" onChange="selectCompartment(this.value);">
									<option value="">Please Select Warehouse</option>
									<?php foreach ($dataW as $val) { ?>
									<option value="<?= $val->id;?>" <?php if ($val->id == $selectedWarehouseId) echo "selected"; ?>>
									  <?=$val->warehouse_name; ?>
									</option>
									<?php } ?>
								</select>
							</div>
						</div>
						<div class="col-md-2 col-sm-6">
							<div class="form-group" id="warehouseCompIdDiv"></div>
						</div>
						<div class="col-md-2 col-sm-6">
							<div class="form-group">
								<label for="emp_name">Receiver Employee Name</label>
								<input type="text" id="emp_name" class="form-control" required name="emp_name">
								<input type="hidden" id="ind_emp_id" name="ind_emp_id">
								<span id="warehouseEmpDiv"></span>
							</div>
						</div>
					</div>
				</div>

				<div class="return-section">
					<h4 class="return-section-title">Returned Items</h4>
					<div class="table-responsive">

					<table class="table table-bordered table-hover return-items-table">
					  <thead>
					  <tr>
						<th>Item Type</th>
						<th>Item Name</th>
						<th>Stock Id</th>  
						<th>WPR Id</th>  
						<th>Lot Number</th>
						<th>Return Date</th>
						<th>Taka Number</th>
						<th>Total Meter</th>
					  </tr>
					  </thead>
					  <tbody>
					  
						<?php 
						foreach($returnItems as $tblRow)
						{  
							$ItemTypeId = $tblRow->item_type_id;							 
						?>
					  <tr>
						 
						<td>
						  <select name="item_type_id[]" readonly id="item_type_id" class="form-control">							 
							<?php foreach ($dataIT as $row) { ?>
							<option value="<?= $row->item_type_id; ?>" <?php if ($row->item_type_id == $ItemTypeId) echo "selected"; ?>>
							  <?= $row->item_type_name; ?>
							</option>
							<?php } ?>
						  </select>
						</td>					
						<td>
						  <input type="text" id="item_name" class="form-control return-item-name" value="<?= $tblRow['Item']->item_name; ?>" name="item_name[]" readonly>
						  <input type="hidden" id="item_id" value="<?= $tblRow->item_id; ?>" name="item_id[]">
						</td>
						
						<td>  <input type="text" id="wis_id" class="form-control" value="<?= $tblRow->wis_id; ?>" name="wis_id[]" readonly> </td>
						<td>  <input type="text" id="work_pro_req_id" class="form-control" value="<?= $tblRow->work_pro_req_id; ?>" name="work_pro_req_id[]" readonly> </td>
						<td>  <input type="text" id="req_lot_number" class="form-control" value="<?= $tblRow->req_lot_number; ?>" name="req_lot_number[]" readonly> </td>
						<td>  <input type="text" id="return_date" class="form-control" value="<?= $tblRow->return_date; ?>" name="return_date[]" readonly> </td>
						<td>  <input type="text" id="insp_taka_number" class="form-control" value="<?= $tblRow->insp_taka_number; ?>" name="insp_taka_number[]" readonly> </td>
						<td>  <input type="text" id="item_qty" class="form-control" value="<?= $tblRow->item_qty; ?>" name="item_qty[]" readonly> </td>
						 
					  </tr>
					  <?php } ?>
					  </tbody>
					</table>
				  </div>
				  <div class="return-actions" id="main_div">
					<div class="return-actions-note">
						<i class="fa fa-info-circle"></i> Confirm will receive these return items into the selected warehouse.
					</div>
					<button type="submit" class="btn btn-primary" id="confirmSubmitBtn">
						<i class="fa fa-check"></i> Confirm
					</button>
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
  @include('frontend.common.footer') </div>
@include('frontend.common.footerscript')
<link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">

<script type="text/javascript">
$(document).ready(function() {
	$('#departItemReturn').on('submit', function(e) {
		var form 		= this;
		var submitBtn 	= $('#confirmSubmitBtn');

		if (form.checkValidity && !form.checkValidity()) {
			return true;
		}

		if ($(form).data('submitted') === true) {
			e.preventDefault();
			return false;
		}

		$(form).data('submitted', true);

		submitBtn.prop('disabled', true);
		submitBtn.html('<i class="fa fa-spinner fa-spin"></i> Please wait...');
	});
});
</script>


<script type="text/javascript"> 
var siteUrl = "{{ url('/') }}";
var searchWarehouseCompartmentUrl = "{{ route('search_warehouse_compartment') }}";
var getWarehouseCompEmployeeUrl = "{{ route('getWarehouseCompEmployee') }}";

<?php if(!empty($selectedWarehouseId)) { ?>
	var Id = {!! $selectedWarehouseId !!};
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
			$( "#emp_name" ).val(''+data[1]+'');
			$( "#ind_emp_id" ).val(''+data[0]+''); 
		}					
	})		
}


</script>
 

 


</body>
</html>
