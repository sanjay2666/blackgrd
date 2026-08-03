<?php
	use \App\Http\Controllers\CommonController; 
	$WorkWarehouseId 	= $dataWO->insp_work_warehouse_id;
	$workOrderId 		= $dataWO->work_order_id;  
?>
<!DOCTYPE html>
<html lang="en">
<meta name="csrf-token" content="{{ csrf_token() }}">
<head>@include('common.head')
</head>
<body class="hold-transition sidebar-mini">
 
<!-- Site wrapper -->
<div class="wrapper"> @include('common.header')
    <div class="content-wrapperd">
	
    <section class="content">
      <div class="row">
        <!-- Form controls -->
        <div class="col-sm-12">
		 {!! CommonController::display_message('message') !!}
          <div class="panel panel-bd lobidrag">
            <div class="panel-heading">
              <div class="btn-group" id="buttonlist"> <a class="btn btn-add" href="javascript:void(0);"> <i class="fa fa-list"></i> Received Work Item In Warehouse </a> </div>
            </div>
            <div class="panel-body">
              <form method="POST" action="{{ route('receiveWorkItemInDepartmentWarehouse')}}" enctype="multipart/form-data">
                @csrf
                <div class="row">
                  
				  
                  <div class="col-md-12">					 
					<?php if(!empty($dataWI['GatePass']->fabric_fault_reason_id)) { ?> 
					<p>
						<span style='color: white; background-color: red; padding: 2px 5px;'>
							The items you are storing in your warehouse are defective.
						</span>
					</p>
					<?php } ?>					 
				     <table class="table table-bordered" id="myTable"> 	 
                      <tbody> 
						  <tr> 
                        <td><label for="work_order_id">GatePass No.</label>
                          <input type="number" name="gate_pass_no" class="form-control" id="gate_pass_no" readonly value="<?=$dataWI['GatePass']->id;?>">
                        </td>
						<td><label for="work_order_id">Work Order Number</label>
                          <input type="text" id="work_order_id"  class="form-control" readonly name="work_order_id" value="<?=$workOrderId;?>">
                        </td>
                        
						<td><label for="receiver_name">Receiver Name</label>
                          <input type="text" id="receiver_name" required class="form-control" name="receiver_name" class="form-control" value="<?=$userD->name;?>">
                          <input type="hidden" id="receiver_id" value="<?=$userD->individual_id;?>" class="form-control" name="receiver_id">
                        </td>                        
						 <td><label>Reciving Date</label> 
						  <input type="text" id="receiving_date" required data-date-format="yyyy-mm-dd" value="<?=date('d-m-Y');?>" name="receiving_date" class="form-control"> 
                        </td>	
						 
					
                        <td><label for="purchase_number">Warehouse</label>
                          <select class="form-control" name="warehouseId" required id="warehouseId" onChange="selectCompartment(this.value);">
                            <option value="">Please Select Warehouse</option>
                            <?php foreach($dataW as $val) { ?>
                            <option value="<?=$val->id;?>"<?php if($val->id == $WorkWarehouseId) echo"selected"; ?>>
                            <?=$val->warehouse_name;?>
                            </option>
                            <?php } ?>
                          </select>
                        </td>
                        <td id="warehouseCompIdDiv"></td> 
					    <td><label for="emp_name">Receiver Employee Name</label>
                          <input type="text" id="emp_name" class="form-control" required name="emp_name">
                          <input type="hidden" id="ind_emp_id" class="form-control" name="ind_emp_id">
                          <table class="table table-bordered">
                            <tbody>
                            <span id="warehouseEmpDiv"></span>
                            </tbody>                            
                          </table>
						</td>  
						
                      </tr> 
                      </tbody>					  
                    </table> 
                    
					<table class="table table-bordered">
					  <thead>
						<tr>
						  <th>Process Type</th>
						  <th>Item Type</th>
						  <th>Item Name</th>
						  <th>Size (Meter)</th>
						  <th>Dyeing Color</th>
						  <th>Coated Pvc</th>
						  <th>Extra Job</th>
						  <th>Print Job</th>
						  <th>Taka Number</th>
						</tr>
					  </thead>
					  <tbody>
						<input type="hidden" name="insp_id" id="insp_id" value="<?=$inspId;?>"> 
						<?php 
						foreach($dataWI['WorkInspectionDetail'] as $rowArr) 
						{ 
						//  echo "<pre>"; print_r($dataWI); exit;
						 
						?>
						<tr>
						  <td>
							<select name="process_type_id[]" id="process_type_id" readonly class="form-control">
							  <option value="">Select Process Type</option>
							  <?php foreach($dataPI as $rowp) { ?>
							  <option value="<?=$rowp->id;?>" <?php if($rowp->id == $ProcessTypeId) echo"selected"; ?>><?=$rowp->process_name;?></option>
							  <?php } ?>
							</select>
						  </td>
						  <td>
							<select name="item_type_id[]" id="item_type_id" readonly class="form-control">
							  <option value="">Select Item Type</option>
							  <?php foreach($dataIT as $row) { ?>
							  <option value="<?=$row->item_type_id;?>" <?php if($row->item_type_id == $ItemTypeId) echo"selected"; ?>><?=$row->item_type_name;?></option>
							  <?php } ?>
							</select>
						  </td>
						  <td>
							<input type="text" id="item_name[]" readonly class="form-control" value="<?=$dataWO->item_name;?>" name="item_name" required>
							<input type="hidden" id="item_id[]" value="<?=$dataWO->item_id;?>" class="form-control" name="item_id">
						  </td>
						  <td>
							<input type="text" name="quan_size[]" id="quan_size" class="form-control" readonly  value="<?=$rowArr->output_quan_size;?>">
						  </td>
						  <td>
							<input type="text" name="dyeing_color[]" id="dyeing_color" class="form-control" readonly value="<?=$dataWI->dyeing_color;?>" >
						  </td>
						  <td>
							<input type="text" name="coated_pvc[]" id="coated_pvc" class="form-control" readonly value="<?=$dataWI->coated_pvc ?? $dataWI->coating_type ?? '';?>" >
						  </td>
						  <td>
							<input type="text" name="extra_job[]" id="extra_job" class="form-control" readonly value="<?=$dataWI->extra_job;?>" >
						  </td>
						  <td>
							<input type="text" name="print_job[]" id="print_job" class="form-control" readonly value="<?=$dataWI->print_job;?>" >
						  </td>
						  <td>
							<input type="text" id="taka_number" class="form-control" readonly name="taka_number[]" value="<?=$rowArr->insp_taka_number;?>">
							<input type="hidden" id="dyeing_lot_number" class="form-control" name="dyeing_lot_number[]" value="<?=$rowArr->dyeing_lot_number;?>">
							<input type="hidden" id="dyeing_taka_number" class="form-control" name="dyeing_taka_number[]" value="<?=$rowArr->dyeing_taka_number;?>">
							<input type="text" id="fabric_fault_reason_id" name="fabric_fault_reason_id[]" value="<?=$rowArr->fabric_fault_reason_id;?>">
							<input type="text" id="machine_id" name="machine_id[]" value="<?=$dataWI['GatePass']->machine_id;?>">
						  </td>
						</tr>
						<?php } ?>
					  </tbody>
					</table>

                 					
                  </div>
				  
                  <div class="col-md-12" id="main_div">
                    <div class="row">
                      <div class="col-xs-12">&nbsp;</div>
                    </div>
                    <button type="submit" class="btn btn-primary pull-left">Confirm</button>
                  </div>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </section>
   
  </div>
  <!-- /.content-wrapper -->
  @include('common.footer') </div>
@include('common.formfooterscript')
<link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
  

 
<script type="text/javascript"> 
var siteUrl = "{{url('/')}}"; 
$( "#item_name" ).autocomplete({		 
	  minLength: 1,
	  source: siteUrl + '/' +"list_item", 
	  focus: function( event, ui ) {
		  
		// alert(ui.item.item_name); 
		$( "#item_name" ).val( ui.item.item_name );
		$( "#item_id" ).val( ui.item.item_id );
		 
		return false;
	  },
	  select: function( event, ui ) { 
	  
		return false;
	  }
	})
	.autocomplete( "instance" )._renderItem = function( ul, item ) {
	  return $( "<li>" )
		.append( "<div>" + item.item_name + "<br> Code - " + item.item_code + "</div>" )
		.appendTo( ul );
	};  
</script>

<script type="text/javascript"> 
var siteUrl = "{{url('/')}}"; 
$( "#receiver_name" ).autocomplete({		 
	minLength: 0,
	source: siteUrl + '/' +"list_employee", 
	focus: function( event, ui ) 
	{ 
		$( "#receiver_name" ).val( ui.item.name );
		$( "#receiver_id" ).val( ui.item.id ); 
		return false;
	},
	select: function( event, ui ) 
	{ 
		// $( "#ind_emp_id" ).val( ui.item.id ); 
		return false;
	}
})
.autocomplete( "instance" )._renderItem = function( ul, item ) {
	return $( "<li>" )
	.append( "<div>" + item.name + "</div>" )
	.appendTo( ul );
}; 
</script>

<script type="text/javascript"> 
var siteUrl = "{{url('/')}}"; 
$( "#emp_name" ).autocomplete({		 
	minLength: 0,
	source: siteUrl + '/' +"list_employee", 
	focus: function( event, ui ) 
	{ 
		$( "#emp_name" ).val( ui.item.name );
		$( "#ind_emp_id" ).val( ui.item.id ); 
		return false;
	},
	select: function( event, ui ) 
	{ 
		// $( "#ind_emp_id" ).val( ui.item.id ); 
		return false;
	}
})
.autocomplete( "instance" )._renderItem = function( ul, item ) {
	return $( "<li>" )
	.append( "<div>" + item.name + "</div>" )
	.appendTo( ul );
}; 
</script>

<!---------search_warehouse_compartment -- getWarehouseCompEmployee-------------->
<script type="text/javascript"> 
 

function selectCompartment(Id)
{ 
	$.ajax({
	type: "GET", 
		url: siteUrl + '/' +"ajax_script/search_warehouse_compartment",
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
		url: siteUrl + '/' +"ajax_script/getWarehouseCompEmployee",
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
<script>
$("#purchase_started,#receiving_date").datepicker({ 
	autoclose: true,
});
</script>


</body>
</html>
