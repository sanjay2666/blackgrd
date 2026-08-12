<?php
	use \App\Http\Controllers\CommonController;	 
?>
<!DOCTYPE html>
<html lang="en">
<head>@include('common.head')
</head>
<body class="hold-transition sidebar-mini workorder-inspection-page">
<!--preloader-->
 
<!-- Site wrapper -->
<div class="wrapper"> @include('common.header')
  <div class="content-wrapper">
    <!-- Main content -->

    <section class="content">
      <div class="row">
        <div class="col-sm-12">
		{!! CommonController::display_message('message') !!}
          <div class="panel panel-bd lobidrag inspection-report-panel">
            <div class="panel-heading inspection-report-heading">
              <div class="btn-group" id="buttonexport"><a href="{{ route('show-workorder-inspection') }}"><h4>Inspected Stock Inward</h4></a></div>
            </div>
            <div class="panel-body">
              <div class="inspection-filter-panel">
                <form action="{{ route('show-workorder-inspection') }}" method="GET" role="search" autocomplete="off">
					@csrf
					<div class="row inspection-filter-row">

					<div class="col-sm-2 col-xs-12">
						<input type="text" class="form-control input-sm" name="cus_search" id="cus_search" value="{{ $cusSearch }}" placeholder="Customer Name">
						<input type="hidden" id="customer_id" class="form-control" name="customer_id" value="">
					</div>

					<div class="col-sm-2 col-xs-12">
						<input type="text" class="form-control input-sm" name="qsearch" id="qsearch" value="{{ $qsearch }}" placeholder="Item Name">
					</div> 
					
					<div class="col-sm-2 col-xs-12">
						<input type="text" class="form-control input-sm" name="LotNumSearch" id="LotNumSearch" value="{{ $LotNumSearch }}" placeholder="Lot Number"> 
					</div> 
					
					<div class="col-sm-1 col-xs-12">
						<select name="process_type_id" class="form-control input-sm" id="process_type_id">
							<option value="" {{ empty($processTypeId) ? 'selected' : '' }}>Select Process</option>
							@foreach($dataPI as $rowP)
								<option value="{{ $rowP->id }}" {{ $rowP->id == $processTypeId ? 'selected' : '' }}>{{ $rowP->process_name }}</option>
							@endforeach
						</select>
					</div> 
					<div class="col-sm-1 col-xs-12">
						<select name="isAccepted" class="form-control input-sm" id="isAccepted">
							<option value="" {{ empty($isAccepted) ? 'selected' : '' }}>Select Type</option>

							<option value="No" {{ 'No' == $isAccepted ? 'selected' : '' }}> Pending</option>
							<option value="Yes" {{ 'Yes' == $isAccepted ? 'selected' : '' }}> Accepted</option>
						 
						</select>
					</div> 
					<div class="col-sm-1 col-xs-12">
						<input type="text" id="receiver_name" class="form-control input-sm" name="receiver_name" placeholder="Receiver">
						<input type="hidden" id="receiver_id" class="form-control" name="receiver_id" value="">
					</div>

					<div class="col-sm-1 col-xs-12">
						<input type="text" id="sender_name" class="form-control input-sm" name="sender_name" placeholder="Sender">
						<input type="hidden" id="sender_id" class="form-control" name="sender_id" value="">
					</div>

					<div class="col-sm-1 col-xs-12">
						<?php  $dateText = !empty($recvWhDate) ? 'value="' . $recvWhDate . '"' : 'placeholder="Received Item Date"'; ?>
						<input type="text" class="form-control input-sm" id="receive_date" name="receive_date" {!! $dateText !!}>
					</div>


					<div class="col-sm-1 col-xs-12">
						<button type="submit" name="sbtSearch" class="btn btn-success btn-sm btn-block" value="Search"><i class="fa fa-search"></i> Search</button>
					</div>
					</div>
				</form>

              </div>
              <div class="table-responsive inspection-table-wrap">
                <table id="dataTableExample1" class="table table-bordered table-striped table-hover inspection-report-table">
                  <thead>
                    <tr class="info">
					  <th class="wir-col-wo">WOId</th>
                      <th class="wir-col-item">Item</th>
                      <th class="wir-col-lot">Lot</th>
                      <th class="wir-col-color">Color</th>
                      <th class="wir-col-customer">Customer Name</th>
                      <th class="wir-col-process">Process Type</th>
                      <th class="wir-col-priority">Priority</th>
                      <th class="wir-col-date">Received Date </th>
                      <th class="wir-col-person">Receiver </th>
                      <th class="wir-col-person">Sender</th>
					  <th class="wir-col-date">Added</th>
                      <th class="wir-col-action">Action</th>
                    </tr>
                  </thead>
                  <tbody>
					<?php 
					foreach($dataWI as $data) 
					{				    
						$Id 	= $data->id;
						$WoId 	= $data->work_order_id;	
						$created 	= date('d-m-Y', strtotime($data->created));										
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
						<td><span class="inspection-wo-id">#{{ $WoId }}</span></td>
						<td class="inspection-item-cell">{{ $data['WorkOrder']->item_name }}</td>
						<td> {{ $data->dyeing_lot_number }}  </td>
						<td> {{ $data->dyeing_color }}  </td>
                      <td> 
							<?php 
								$priority = '';
								foreach ($data->WorkOrder->WorkOrderItem as $item) 
								{   
									$cusId 		= $item->customer_id;						   
									$priority 	= $item->order_item_priority . '  <br/>';  
								}	
								?>  
								
							 
							<p class="inspection-customer-name"><?=CommonController::getEmpName($cusId);?></p> 
					  </td>
                      <td><span class="inspection-process-pill">{{ CommonController::getProcessName($data['WorkOrder']->process_type_id) }}</span></td>
                      <td><span class="inspection-priority"><?php echo rtrim($priority, '<br/>');?></span></td>
                      <td class="center"><?=$data->item_received_in_warehouse_date;?></td>
                      <td class="center">{{ $ReceivedBy }}</td>
                      <td class="center">{{ $inspectedBy }}</td>
					  <td class="center">{{ $created }}</td>
                      <td class="center"><?php if($isWarehouseAccepted =='No') { ?>   
					  
                        <p> <a type="button" data-toggle="modal" data-target=".bs-example-modal-lg<?=$Id;?>" class="btn btn-success btn-xs"><i class="fa fa-check"></i> Accept</a></p>
					    <p> <a href="javascript:void(0);" onclick="DenyWorkInspection({{ $Id }})" class="btn btn-danger btn-xs"><i class="fa fa-times"></i> Deny</a></p>
					
						<?php } else { ?>
                        <p><span class="inspection-status-pill accepted"><i class="fa fa-check-circle"></i> Accepted by {{ $acceptedBy }} on {{ date('d-m-Y', strtotime($data->warehouse_accept_date)) }}</span></p>
                        <?php if($isItemRcvdInWarehouse =='No') { ?>
							<?php if($process_type_id > 1) { ?>
							<p><a href="{{ route('receive-work-item', enc($Id)) }}" class="btn btn-success btn-xs"><i class="fa fa-archive"></i> Store In Warehouse</a></p>
							<p> <a href="javascript:void(0);" onclick="DenyWorkInspection({{ $Id }})" class="btn btn-danger btn-xs"><i class="fa fa-times"></i> Deny</a></p>
							<?php } ?>
                        <?php } elseif($isItemRcvdInWarehouse == 'Yes') { ?>
                        <p class="inspection-received-note">Item received by {{ $ReceivedBy }} and entered by {{ $InterredBy }} on {{ date('d-m-Y', strtotime($data->item_received_in_warehouse_date)) }}</p>
                        <?php } ?>
                        <?php } ?>
                      </td>
                    </tr>
                  <?php } ?>
                  <tr class="center text-center">
                    <td class="center" colspan="12"><div class="pagination"> {{ $dataWI->links('vendor.pagination.bootstrap-4')}}</div></td>
                  </tr>
                  </tbody>

                </table>
              </div>
              <?php foreach($dataWI as $data) { 
                $Id = $data->id;
                $WoId = $data->work_order_id;
                $workOrderItemName = $data['WorkOrder']->item_name ?? '';
              ?>
              <div class="modal fade bs-example-modal-lg<?=$Id;?> inspection-accept-modal" tabindex="-1" role="dialog" aria-labelledby="inspectionAcceptLabel<?=$Id;?>" aria-hidden="true">
                <form name="del_cat" method="post" action="{{ route('accept_work_item_in_warehouse')}}">
                  @csrf
                  <div class="modal-dialog inspection-accept-dialog">
                    <div class="modal-content">
                      <div class="modal-header inspection-accept-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">&times;</button>
                        <h4 class="modal-title" id="inspectionAcceptLabel<?=$Id;?>"><i class="fa fa-check-circle"></i> Confirm Warehouse Acceptance</h4>
                      </div>
                      <div class="modal-body">
                        <div class="inspection-accept-box">
                          <span class="inspection-accept-icon"><i class="fa fa-inbox"></i></span>
                          <div>
                            <h3>Accept this inspected item in warehouse?</h3>
                            <p>This will mark the inspection as accepted for warehouse inward processing.</p>
                          </div>
                        </div>
                        <div class="inspection-accept-meta">
                          <span>WO: #{{ $WoId }}</span>
                          <strong>{{ $workOrderItemName }}</strong>
                        </div>
                        <input name="FId" id="FId<?=$Id;?>" value="<?=$Id;?>" type="hidden">
                      </div>
                      <div class="modal-footer inspection-accept-footer">
                        <button type="button" data-dismiss="modal" aria-label="Close" class="btn btn-default"><i class="fa fa-times"></i> Cancel</button>
                        <button type="submit" class="btn btn-success"><i class="fa fa-check"></i> Yes, Accept It</button>
                      </div>
                    </div>
                  </div>
                </form>
              </div>
              <?php } ?>
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
function DenyWorkInspection(id) {
    const siteUrl = "{{ url('/') }}";
    const csrfToken = "{{ csrf_token() }}";

    if (confirm("Do you really want to deny this item?")) {
        jQuery.ajax({
            type: "GET",
            url: `${siteUrl}/ajax_script/denyWorkInspection`,
            data: {
                "_token": csrfToken,
                "FId": id,
            },
            cache: false,
            success: function(response) {
                if (response.success) {
                    alert(response.message);
                    $("#Mid" + id).hide();
                } else {
                    alert("Error: " + response.message);
                }
            },
            error: function(xhr) {
                alert("An error occurred. Please try again.");
                console.error(xhr.responseText);
            }
        });
    }
}
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
