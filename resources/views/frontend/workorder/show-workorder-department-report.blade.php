<?php
	use \App\Http\Controllers\CommonController;	 
	$cusSearch  		= $filters['cus_search'];
	$qsearch 			= $filters['qsearch'];
	$processTypeId 		= $filters['process_type_id'];
	$receiver_name 		= $filters['receiver_name'];
	$sender_name 		= $filters['sender_name'];
	$recvWhDate 		= $filters['receive_date']; 
?>
<!DOCTYPE html>
<html lang="en">
<head>@include('common.head')
<style>   </style>
</head>
<body class="hold-transition sidebar-mini">
<!--preloader-->
<div id="preloader">
  <div id="status"></div>
</div>
<!-- Site wrapper -->
<div class="wrapper"> @include('common.header')
  <div class="content-wrapper">
    <!-- Main content -->

    <section class="content">
      <div class="row">
        <div class="col-sm-12">
		{!! CommonController::display_message('message') !!}
          <div class="panel panel-bd lobidrag">
            <div class="panel-heading">
              <div class="btn-group" id="buttonexport"> <a href="javascript:void(0);">
                <h4>Sender Report List</h4>
                </a> </div>
            </div>
            <div class="panel-body">
              <div class="row" style="margin-bottom:5px">
                <form action="{{ route('show-workorder-department-report') }}" method="GET" role="search">
					@csrf

					<div class="col-sm-2 col-xs-12">
						<input type="text" class="form-control" name="cus_search" id="cus_search" value="{{ $cusSearch }}" placeholder="Search By Customer Name">
						<input type="hidden" id="customer_id" class="form-control" name="customer_id" value="">
					</div>

					<div class="col-sm-2 col-xs-12">
						<input type="text" class="form-control" name="qsearch" id="qsearch" value="{{ $qsearch }}" placeholder="Search By Item Name">
					</div> 
					
					<div class="col-sm-2 col-xs-12">
						<select name="process_type_id" class="form-control" id="process_type_id">
							<option value="" {{ empty($processTypeId) ? 'selected' : '' }}>Select Process</option>
							@foreach($dataPI as $rowP)
								<option value="{{ $rowP->id }}" {{ $rowP->id == $processTypeId ? 'selected' : '' }}>{{ $rowP->process_name }}</option>
							@endforeach
						</select>
					</div> 

					<div class="col-sm-1 col-xs-12">
						<input type="text" id="receiver_name" class="form-control" name="receiver_name" placeholder="Receiver Name">
						<input type="hidden" id="receiver_id" class="form-control" name="receiver_id" value="">
					</div>

					<div class="col-sm-1 col-xs-12">
						<input type="text" id="sender_name" class="form-control" name="sender_name" placeholder="Sender Name">
						<input type="hidden" id="sender_id" class="form-control" name="sender_id" value="">
					</div>

					<div class="col-sm-1 col-xs-12">
						<?php  $dateText = !empty($recvWhDate) ? 'value="' . $recvWhDate . '"' : 'placeholder="Received Item Date"'; ?>
						<input type="text" class="form-control" id="receive_date" name="receive_date" {!! $dateText !!}>
					</div>


					<div class="col-sm-1 col-xs-12">
						<input type="submit" name="sbtSearch" class="btn btn-success" value="Search">
					</div>
				</form>

              </div>
			  <P>&nbsp;</P>
              <div class="table-responsive">
                <table id="dataTableExample1" class="table table-bordered table-striped table-hover">
                  <thead>
                    <tr class="info">
                      <th>Item</th>
                      <th>Customer Name</th>
                      <th>Process Type</th>
                      <th>Priority</th>
                      <th>Received Date </th>
                      <th>Receiver </th>
                      <th>Sender</th>
                      <th>Action</th>
                    </tr>
                  </thead>
                  <tbody>
					<?php 
					foreach($dataWI as $data) 
					{				    
						$Id 	= $data->id;
						$WoId 	= $data->work_order_id;						 
						$quantity 		= $data['WorkOrder']->quantity;
						$masterIndId 	= $data['WorkOrder']->master_ind_id;
						$machineId 		= $data['WorkOrder']->machine_id;
						$outputQuantity = $data['WorkOrder']->output_quantity;
						$outputProcess 	= $data['WorkOrder']->output_process;						 
						$isWarehouseAccepted 	= $data->is_warehouse_accepted;
						$isItemRcvdInWarehouse 	= $data->is_item_received_in_warehouse;
						$acceptedBy  			= '';
						if(!empty($isWarehouseAccepted)) $acceptedBy  = CommonController::getEmpName($data->warehouse_accepted_by);
						$ReceivedBy   = CommonController::getEmpName($data->item_received_in_warehouse_by);
						$InterredBy   = CommonController::getEmpName($data->item_interred_in_warehouse_by);
						$inspectedBy  = CommonController::getEmpName($data->inspected_by);
						$process_type_id = $data['WorkOrder']->process_type_id;
					?>
                    <tr id="Mid{{ $Id }}">
						<td>  {{ $data['WorkOrder']->item_name }}  {{ $Id }} </td>
                      <td> 
							<?php 
								$priority = '';
								foreach ($data->WorkOrder->WorkOrderItem as $item) 
								{   
									$cusId 		= $item->customer_id;						   
									$priority 	.= $item->order_item_priority . '  <br/>';  
								?>  
								<p><?=CommonController::getEmpName($cusId);?></p> 
							<?php 	}  ?>				  
					  </td>
                      <td> {{ CommonController::getProcessName($data['WorkOrder']->process_type_id) }} </td>
                      <td><?php echo rtrim($priority, '<br/>');?></td>
                      <td class="center"><?=$data->item_received_in_warehouse_date;?></td>
                      <td class="center">{{ $ReceivedBy }}</td>
                      <td class="center">{{ $inspectedBy }}</td>
                      <td class="center"><?php if($isWarehouseAccepted =='No') { ?>                        
                        <a type="button" data-toggle="modal" data-target=".bs-example-modal-lg<?=$Id;?>" class="btn btn-success btn-xs">Accept</a>
                        <?php } else { ?>
                        <p><a href="javascript:void(0);" class="btn btn-primary btn-xs">Accepted By {{ $acceptedBy }} on {{ date('F dS, Y', strtotime($data->warehouse_accept_date)) }}</a></p>
                        <?php if($isItemRcvdInWarehouse =='No') { ?>
							<?php if($process_type_id > 1) { ?>
							<p><a href="{{ route('receive-work-item-in-department', enc($Id)) }}" class="btn btn-success btn-xs">Store In Department</a></p>
							<?php } ?>
                        <?php } elseif($isItemRcvdInWarehouse == 'Yes') { ?>
                        <p class="w-md m-b-5"> Item Received In Warehouse and Received by {{ $ReceivedBy }} and Interred by {{ $InterredBy }}  on {{ date('F dS, Y', strtotime($data->item_received_in_warehouse_date)) }} </p>
                        <?php } ?>
                        <?php } ?>
                      </td>
                    </tr>
					
					 <div class="modal fade bs-example-modal-lg<?=$Id;?>" tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel" aria-hidden="true">
						<form name="del_cat" method="post" action="{{ route('accept_work_item_in_warehouse')}}">
							@csrf
							<div class="modal-dialog modal-lg d-flex align-items-center justify-content-center">
								<div class="modal-content">
									<div class="modal-body">
										<h3>Are you sure you intend to accept this item in your warehouse?</h3>
										<p align="center">You won't be able to revert this!</p>
										<input name="FId" id="FId" value="<?=$Id;?>" type="hidden">
										<button type="submit" class="btn btn-danger" style="width:100%;"> Yes, Accept It!</button>
										<br>
										<br>
										<button type="button" data-dismiss="modal" aria-label="Close" class="btn btn-success" class="close" style="width:100%;">
											Cancel
										</button>
									</div>
								</div>
							</div>
						</form>
					</div>

                  <?php } ?>
                  <tr class="center text-center">
                    <td class="center" colspan="8"><div class="pagination"> {{ $dataWI->links('vendor.pagination.bootstrap-4')}}</div></td>
                  </tr>
                  </tbody>

                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
    <!-- /.content -->
  </div>
  @include('common.footer') </div>
@include('common.formfooterscript')
<script type="text/javascript" src="{{ asset('js/jquery.validate.js') }}"></script>
 
<script type="text/javascript">
var siteUrl = "{{url('/')}}";
  $(document).ready(function() {
    $("#qsearch").autocomplete({
      minLength: 0,
      source: siteUrl + '/' + "fabric_list_item",
      focus: function(event, ui) {
        if (ui.item.item_name != '') {
          $("#qsearch").val(ui.item.item_name); 
        }
        return false;
      },
      select: function(event, ui) {
        if (ui.item.item_name != '') {
          $("#qsearch").val(ui.item.item_name); 
        }
        return false;          
      }
    }).autocomplete("instance")._renderItem = function(ul, item) {
      return $("<li>")
        .append("<div>" + item.item_name + "</div>")
        .appendTo(ul);
    };
  });
</script>

<script>
    $(function() {
        $("#receive_date").datepicker({
            dateFormat: "dd-mm-yy", 
            changeYear: true, 
            changeMonth: true, 
            autoclose: true, 
        });
    });
</script>

<script type="text/javascript">
function AcceptWorkOrder(id)
{
	var siteUrl = "{{url('/')}}";
	if(confirm("Do you realy want to Accept this item in your warehouse?"))
	{
		jQuery.ajax({
			type: "GET",
			url: siteUrl + '/' +"ajax_script/acceptWorkOrderInWarehouse",
			data: {
				"_token": "{{ csrf_token() }}",
				"FId":id,
			},
			cache: false,
			success: function(msg)
			{
				$("#Mid"+id).hide();
			}
		});
	}
}
</script>


<script type="text/javascript">
	var siteUrl = "{{url('/')}}";
	$( "#cus_search" ).autocomplete({
	  minLength: 1,
	  source: siteUrl + '/' +"list_customer",
	  focus: function( event, ui ) {

		// alert(ui.item.name);
		$( "#cus_search" ).val( ui.item.name );
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
	$( "#receiver_name" ).autocomplete({
	  minLength: 1,
	  source: siteUrl + '/' +"list_employee",
	  focus: function( event, ui ) {

		// alert(ui.item.name);
		$( "#receiver_name" ).val( ui.item.name );
		$( "#receiver_id" ).val( ui.item.id );

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
	$( "#sender_name" ).autocomplete({
	  minLength: 1,
	  source: siteUrl + '/' +"list_employee",
	  focus: function( event, ui ) {

		// alert(ui.item.name);
		$( "#sender_name" ).val( ui.item.name );
		$( "#sender_id" ).val( ui.item.id );

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
	$( "#sender_name" ).autocomplete({
	  minLength: 1,
	  source: siteUrl + '/' +"list_employee",
	  focus: function( event, ui ) {

		// alert(ui.item.name);
		$( "#sender_name" ).val( ui.item.name );
		$( "#sender_id" ).val( ui.item.id );

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



</body>
</html>
