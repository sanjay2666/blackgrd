<?php
	use \App\Http\Controllers\CommonController;  
	if(!empty($ItemTypeId)) $ItemTypeId = $ItemTypeId;
	else $ItemTypeId = '0';
	if(!empty($saleordId)) $saleordId = $saleordId;
	else $saleordId = '0';
	if(!empty($ItemId))
	{
		$ItemId = $ItemId;
		$ItemName = $dataI->item_name;
	}	
	else 
	{
		$ItemId 	= '0';
		$ItemName 	= '';
	} 	 
?>
<!DOCTYPE html>
<html lang="en">
<meta name="csrf-token" content="{{ csrf_token() }}">
<head>@include('common.head')
</head>
<body class="hold-transition sidebar-mini">
<!--preloader-->
<div id="preloader">
  <div id="status"></div>
</div>
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
              <div class="btn-group" id="buttonlist"> <a class="btn btn-add " href="show-workorders"> <i class="fa fa-list"></i> Work Order </a> </div>
            </div>
            <div class="panel-body">
              <form method="post" action="{{ route('store_workorder')}}" onSubmit="return check_form();" autocomplete="off">
                @csrf
                <div class="row">
                  <div class="col-md-12"> </div>
                  <div class="col-md-12">
                    <table class="table table-bordered">
                      <tbody>
                        <tr>
                          <td><label for="sale_order_id">Sale Order</label>
                            <input type="text" id="sale_order_id"  class="form-control" name="sale_order_id" value="<?=$saleordId;?>" required>
                          </td>
                          <td><label for="item_name">Item Name</label>
                            <input type="text" id="item_name"  class="form-control" value="<?=$ItemName;?>" name="item_name" required>
                            <input type="hidden" id="item_id" value="<?=$ItemId;?>" class="form-control" name="item_id">
                          </td>
                          <td><label for="item_type_id">Item Type </label>
                            <select class="form-control" name="item_type_id" id="item_type_id" required>
                              <option>Please Select Item Type </option>
                              <?php foreach($dataIT as $row) { ?>
                              <option value="<?=$row->item_type_id;?>" <?php if($row->item_type_id == $ItemTypeId) echo"selected"; ?> >
                              <?=$row->item_type_name;?>
                              </option>
                              <?php }  ?>
                            </select>
                          </td>
                          <?php  
							$pTypeArr = array('Warping','Weaving','Dyeing','Coating','Job Work','Package');
						?>
                          <td><label for="process_type">Process Type </label>
                            <select class="form-control" name="process_type" id="process_type" required>
                              <option>Please Select Process Type </option>
                              <?php foreach($pTypeArr as $val) { ?>
                              <option value="<?=$val;?>">
                              <?=$val;?>
                              </option>
                              <?php }  ?>
                            </select>
                          </td>
                          <td><label for="priority">Order Priority </label>
                            <select id="order_priority" name="order_priority" class="form-control" required>
                              <option value="">Please Select  Order Priority</option>
                              <option value="Extreme">Extreme</option>
                              <option value="High">High</option>
                              <option value="Normal">Normal</option>
                              <option value="Low">Low</option>
                            </select>
                          </td>
                          <td><label for="customer_name">Customer Name</label>
                            <input type="text" id="customer_name" class="form-control" name="customer_name" required>
                            <input type="hidden" id="customer_id" class="form-control" name="customer_id" value="">
                          </td>
                        </tr>
                        <tr>
                          <td><label for="process_started">Process Started By </label>
                            <input type="text" id="process_started"  class="form-control" name="process_started" required>
                            <input type="hidden" id="process_started_by" class="form-control" name="process_started_by" value="">
                          </td>
                          <td><label for="process_ended">Process Ended By </label>
                            <input type="text" id="process_ended"  class="form-control" name="process_ended" required>
                            <input type="hidden" id="process_ended_by"  class="form-control" name="process_ended_by">
                          </td>
                          <td><label for="process_inspected">Process Inspected By </label>
                            <input type="text" id="process_inspected"  class="form-control" name="process_inspected" required>
                            <input type="hidden" id="process_inspected_by"  class="form-control" name="process_inspected_by">
                          </td>
                          <td><label for="gatepass_print">Gatepass Print By </label>
                            <input type="text" id="gatepass_print"  class="form-control" name="gatepass_print">
                            <input type="hidden" id="gatepass_print_by"  class="form-control" name="gatepass_print_by" required>
                          </td>
                          <td><label for="gatepass_print_date">Gatepass Print Date </label>
                            <input type="text" id="gatepass_print_date" class="form-control" name="gatepass_print_date" required>
                          </td>
                        </tr>
                        <tr>
                          <td><label for="process_started_date">Process Started Date </label>
                            <input type="text" id="process_started_date" data-date-format="yyyy-mm-dd" class="form-control" name="process_started_date" required>
                          </td>
                          <td><label for="process_ended_date">Process Ended Date </label>
                            <input type="text" id="process_ended_date" data-date-format="yyyy-mm-dd" class="form-control" name="process_ended_date" required>
                          </td>
                          <td><label for="process_inspected_date">Process Inspected Date </label>
                            <input type="text" id="process_inspected_date" data-date-format="yyyy-mm-dd" class="form-control" name="process_inspected_date" required>
                          </td>
                          <td><label for="gatepass_genrated_by_name">Gatepass Genrated By </label>
                            <input type="text" id="gatepass_genrated_by_name" class="form-control" name="gatepass_genrated_by_name" required>
                            <input type="hidden" id="gatepass_genrated_by" class="form-control" name="gatepass_genrated_by">
                          </td>
                          <td><label for="gatepass_genrated_to_name">Gatepass Genrated To </label>
                            <input type="text" id="gatepass_genrated_to_name" class="form-control" name="gatepass_genrated_to_name" required>
                            <input type="hidden" id="gatepass_genrated_to" class="form-control" name="gatepass_genrated_to">
                          </td>
                        </tr>
                        <tr>
                          <td><label for="process_started_remarks">Process Started Remarks </label>
                            <input type="text" id="process_started_remarks"  class="form-control" name="process_started_remarks" required>
                          </td>
                          <td><label for="process_ended_remarks">Process Ended Remarks </label>
                            <input type="text" id="process_ended_remarks" class="form-control" name="process_ended_remarks" required>
                          </td>
                          <td><label for="process_inspected_remark">Process Inspected Remarks </label>
                            <input type="text" id="process_inspected_remark"  class="form-control" name="process_inspected_remark" required>
                          </td>
                          <td><label for="getapass_to_depart">Getapass To Department </label>
                            <input type="text" id="getapass_to_depart"  class="form-control" name="getapass_to_depart" required>
                            <input type="hidden" id="getapass_to_department"  class="form-control" name="getapass_to_department" required>
                          </td>
                          <td><label for="getapass_from_depart">Getapass From Department </label>
                            <input type="text" id="getapass_from_depart"  class="form-control" name="getapass_from_depart" required>
                            <input type="hidden" id="getapass_from_department"  class="form-control" name="getapass_from_department" required>
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                  <div class="col-md-12" id="main_div">
                    <div class="row">
                      <div class="col-xs-12">
                        <!--table part start-->
                        <div class="table-responsive table-responsive-custom"> <span id="showPurchaseItems"> </span> </div>
                        <!--table part end-->
                      </div>
                    </div>
                    <button type="submit" class="btn btn-primary pull-left">Confirm</button>
                    <button type="button" class="btn btn-danger" ><i class="fa fa-times"></i> Discard</button>
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
<link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
<script type="text/javascript"> 

$("#process_inspected_date,#gatepass_print_date").datepicker({ 
	dateFormat: 'yy-mm-dd',
	autoclose: true,
});

</script>
<script>
$(document).ready(function() 
{
	var startDate;
	var endDate;
	$( "#process_started_date" ).datepicker({
		dateFormat: 'yy-mm-dd'
	})
 
	$( "#process_ended_date" ).datepicker({
		dateFormat: 'yy-mm-dd'
	});
 
	$('#process_started_date').change(function() {
		startDate = $(this).datepicker('getDate');
		$("#process_ended_date").datepicker("option", "minDate", startDate );
	}) 
	$('#process_ended_date').change(function() {
		endDate = $(this).datepicker('getDate');
		$("#process_started_date").datepicker("option", "maxDate", endDate );
	})
 
})
</script>
<script type="text/javascript"> 
	var siteUrl = "{{url('/')}}"; 
	$( "#sale_order_id" ).autocomplete({		 
	  minLength: 1,
	  source: siteUrl + '/' +"list_saleOrderNumer", 
	  focus: function( event, ui ) {
		  
		// alert(ui.item.sale_order_id); 
		$( "#sale_order_id" ).val( ui.item.sale_order_id ); 
		 
		return false;
	  },
	  select: function( event, ui ) { 
	  
		return false;
	  }
	})
	.autocomplete( "instance" )._renderItem = function( ul, item ) {
	  return $( "<li>" )
		.append( "<div>" + item.sale_order_id + "<br> Order Number - " + item.sale_order_number + "</div>" )
		.appendTo( ul );
	}; 
</script>
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
	$( "#customer_name" ).autocomplete({		 
	  minLength: 1,
	  source: siteUrl + '/' +"list_customer", 
	  focus: function( event, ui ) {
		  
		// alert(ui.item.name); 
		$( "#customer_name" ).val( ui.item.name );
		$( "#customer_id" ).val( ui.item.id );
		 
		return false;
	  },
	  select: function( event, ui ) { 
	  
		return false;
	  }
	})
	.autocomplete( "instance" )._renderItem = function( ul, item ) {
	  return $( "<li>" )
		.append( "<div>" + item.name + "<br> Code - " + item.gstin + "</div>" )
		.appendTo( ul );
	};

</script>
<script type="text/javascript"> 
	var siteUrl = "{{url('/')}}"; 
	$( "#process_started" ).autocomplete({		 
	  minLength: 1,
	  source: siteUrl + '/' +"list_employee", 
	  focus: function( event, ui ) {
		  
		// alert(ui.item.name); 
		$( "#process_started" ).val( ui.item.name );
		$( "#process_started_by" ).val( ui.item.id );
		 
		return false;
	  },
	  select: function( event, ui ) { 
	  
		return false;
	  }
	})
	.autocomplete( "instance" )._renderItem = function( ul, item ) {
	  return $( "<li>" )
		.append( "<div>" + item.name + "<br> Code - " + item.gstin + "</div>" )
		.appendTo( ul );
	};

</script>
<script type="text/javascript"> 
	var siteUrl = "{{url('/')}}"; 
	$( "#process_ended" ).autocomplete({		 
	  minLength: 1,
	  source: siteUrl + '/' +"list_employee", 
	  focus: function( event, ui ) {
		  
		// alert(ui.item.name); 
		$( "#process_ended" ).val( ui.item.name );
		$( "#process_ended_by" ).val( ui.item.id );
		 
		return false;
	  },
	  select: function( event, ui ) { 
	  
		return false;
	  }
	})
	.autocomplete( "instance" )._renderItem = function( ul, item ) {
	  return $( "<li>" )
		.append( "<div>" + item.name + "<br> Code - " + item.gstin + "</div>" )
		.appendTo( ul );
	};

</script>
<script type="text/javascript"> 
	var siteUrl = "{{url('/')}}"; 
	$( "#process_inspected" ).autocomplete({		 
	  minLength: 1,
	  source: siteUrl + '/' +"list_employee", 
	  focus: function( event, ui ) {
		  
		// alert(ui.item.name); 
		$( "#process_inspected" ).val( ui.item.name );
		$( "#process_inspected_by" ).val( ui.item.id );
		 
		return false;
	  },
	  select: function( event, ui ) { 
	  
		return false;
	  }
	})
	.autocomplete( "instance" )._renderItem = function( ul, item ) {
	  return $( "<li>" )
		.append( "<div>" + item.name + "<br> Code - " + item.gstin + "</div>" )
		.appendTo( ul );
	};

</script>
<script type="text/javascript"> 
	var siteUrl = "{{url('/')}}"; 
	$( "#gatepass_print" ).autocomplete({		 
	  minLength: 1,
	  source: siteUrl + '/' +"list_employee", 
	  focus: function( event, ui ) {
		  
		// alert(ui.item.name); 
		$( "#gatepass_print" ).val( ui.item.name );
		$( "#gatepass_print_by" ).val( ui.item.id );
		 
		return false;
	  },
	  select: function( event, ui ) { 
	  
		return false;
	  }
	})
	.autocomplete( "instance" )._renderItem = function( ul, item ) {
	  return $( "<li>" )
		.append( "<div>" + item.name + "<br> Code - " + item.gstin + "</div>" )
		.appendTo( ul );
	};

</script>
<script type="text/javascript"> 
	var siteUrl = "{{url('/')}}"; 
	$( "#gatepass_genrated_by_name" ).autocomplete({		 
	  minLength: 1,
	  source: siteUrl + '/' +"list_employee", 
	  focus: function( event, ui ) {
		  
		// alert(ui.item.name); 
		$( "#gatepass_genrated_by_name" ).val( ui.item.name );
		$( "#gatepass_genrated_by" ).val( ui.item.id );
		 
		return false;
	  },
	  select: function( event, ui ) { 
	  
		return false;
	  }
	})
	.autocomplete( "instance" )._renderItem = function( ul, item ) {
	  return $( "<li>" )
		.append( "<div>" + item.name + "<br> Code - " + item.gstin + "</div>" )
		.appendTo( ul );
	};

</script>
<script type="text/javascript"> 
	var siteUrl = "{{url('/')}}"; 
	$( "#gatepass_genrated_to_name" ).autocomplete({		 
	  minLength: 1,
	  source: siteUrl + '/' +"list_employee", 
	  focus: function( event, ui ) {
		  
		// alert(ui.item.name); 
		$( "#gatepass_genrated_to_name" ).val( ui.item.name );
		$( "#gatepass_genrated_to" ).val( ui.item.id );
		 
		return false;
	  },
	  select: function( event, ui ) { 
	  
		return false;
	  }
	})
	.autocomplete( "instance" )._renderItem = function( ul, item ) {
	  return $( "<li>" )
		.append( "<div>" + item.name + "<br> Code - " + item.gstin + "</div>" )
		.appendTo( ul );
	};

</script>
<script type="text/javascript"> 
	var siteUrl = "{{url('/')}}"; 
	$( "#getapass_to_depart" ).autocomplete({		 
	  minLength: 1,
	  source: siteUrl + '/' +"list_warehouse_compartment", 
	  focus: function( event, ui ) {
		  
	  
		$( "#getapass_to_depart" ).val( ui.item.compartment_name );
		$( "#getapass_to_department" ).val( ui.item.id );
		 
		return false;
	  },
	  select: function( event, ui ) { 
	  
		return false;
	  }
	})
	.autocomplete( "instance" )._renderItem = function( ul, item ) {
	  return $( "<li>" )
		.append( "<div> Compartment - " + item.compartment_name + "<br> Warehouse - " + item.warehouse.warehouse_name + "</div>" )
		.appendTo( ul );
	};

</script>
<script type="text/javascript"> 
	var siteUrl = "{{url('/')}}"; 
	$( "#getapass_from_depart" ).autocomplete({		 
	  minLength: 1,
	  source: siteUrl + '/' +"list_warehouse_compartment", 
	  focus: function( event, ui ) {
		  
		// alert(ui.item.name); 
		$( "#getapass_from_depart" ).val( ui.item.compartment_name );
		$( "#getapass_from_department" ).val( ui.item.id );
		 
		return false;
	  },
	  select: function( event, ui ) { 
	  
		return false;
	  }
	})
	.autocomplete( "instance" )._renderItem = function( ul, item ) {
	  return $( "<li>" )
		.append( "<div> Compartment - " + item.compartment_name + "<br> Warehouse - " + item.warehouse.warehouse_name + "</div>" )
		.appendTo( ul );
	};

</script>
</body>
</html>
