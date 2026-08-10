<?php
use App\Enums\WorkRequirementStatus;
use App\Http\Controllers\CommonController;

?>
<!DOCTYPE html>
<html lang="en">
<head>@include('frontend.common.head', ['pageTitle' => 'Warehouse Requirement List | Loomexa'])
</head>
<body class="hold-transition sidebar-mini warehouse-requirement-page">
<!--preloader-->
 
<!-- Site wrapper -->
<div class="wrapper"> @include('frontend.common.header')
    <div class="content-wrapperd">
    <section class="content">
      <div class="row">
        <div class="col-sm-12">
		{!! display_message('message') !!}
          <div class="panel panel-bd lobidrag warehouse-requirement-panel">
            <div class="panel-heading warehouse-requirement-heading">
              <div class="btn-group" id="buttonexport"><a href="{{ route('show-warehouse-item-requirement') }}"><h4>Warehouse Requirement List</h4></a></div>
            </div>
            <div class="panel-body">
      
               <div class="warehouse-requirement-filter">
                <form action="{{ route('show-warehouse-item-requirement') }}" method="GET" role="search">
                @csrf
				<div class="row warehouse-requirement-filter-row">

					<div class="col-sm-2 col-xs-12">
						<input type="text" class="form-control" name="qsearch" id="qsearch" value="<?= $qsearch; ?>" placeholder="Customer Name">
					</div>
					
					<div class="col-sm-1 col-xs-12">
						<input type="text" class="form-control" name="itemName" id="itemName" value="<?= $itemName; ?>" placeholder="Item Name">
					</div>
					
					<div class="col-sm-1 col-xs-12">
						<input type="text" class="form-control" name="lotno" id="lotno" value="<?= $lotno; ?>" placeholder="Lot Number">
					</div>	
					
					<div class="col-sm-1 col-xs-12">
						<input type="text" class="form-control" name="dyeing_color" id="dyeing_color" value="<?= $dyeingColor; ?>" placeholder="Color">
					</div>		
					
					<div class="col-sm-1 col-xs-12">
					  <input type="text" class="form-control" name="from_date" id="from_date" placeholder="From Date" value="<?= $fromDate; ?>">
					</div>
						
					<div class="col-sm-1 col-xs-12">
						<input type="text" class="form-control" name="to_date" id="to_date" placeholder="To Date" value="<?= $toDate; ?>">
					</div> 
					  
					<div class="col-sm-2 col-xs-12">
						<div class="dropdown">
							<button class="btn btn-default dropdown-toggle" type="button" id="processDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
								Select Processes
								<span class="caret"></span>
							</button>
							<ul class="dropdown-menu" role="menu" aria-labelledby="processDropdown">
								<?php
                                $search_process_id = is_array($search_process_id) ? array_map('intval', $search_process_id) : [];
foreach ($processI as $process) {
    ?>
								<li role="presentation">
									<label>
										<input type="checkbox" name="search_process_id[]" value="<?php echo $process->id; ?>" <?php echo (! empty($search_process_id) && in_array($process->id, $search_process_id)) ? 'checked' : ''; ?>>
										<?php echo $process->process_name; ?>
									</label>
								</li>
								<?php
}
?>
							</ul>

						</div>				 
					</div> 
						
					<div class="col-sm-1 col-xs-12">
						<select class="form-control" name="req_type" id="req_type">
							<option value="">Type</option>
							<option value="0" @selected((string) $req_type === '0')> Requested </option>
							<option value="1" @selected((string) $req_type === '1')> Accepted </option>
							<option value="2" @selected((string) $req_type === '2')> Denied </option>
						</select>
					</div>
	 
					<div class="col-sm-2 col-xs-12">
						<input type="submit" name="sbtSearch" class="btn btn-success" value="Search">
					</div>
					  
				</div>
                </form>
				
              </div>
              
              <div class="table-responsive warehouse-requirement-table-wrap">
                 <table id="dataTableExample1" class="table table-bordered table-striped table-hover warehouse-requirement-table">
					<thead>
						<tr class="info text-center"> 
							<th class="wpr-col-id">ID</th>
							<th class="wpr-col-wo">WO No.</th>
							<th class="wpr-col-lot">Lot No.</th>
							<th class="wpr-col-process">Process</th>
							<th class="wpr-col-requested">Requested</th>
							<th class="wpr-col-item">Item</th> 
							<th class="wpr-col-type">Type</th>
							<th class="wpr-col-qty">Req. Qty</th>
							<th class="wpr-col-qty">Allot Qty</th>
							<th class="wpr-col-stock">Stock</th>
							<th class="wpr-col-created">Created</th>
							<th class="wpr-col-status">Status</th>
							<th class="wpr-col-action">Allotment</th> 
						</tr>

					</thead>

					<tbody>
						@foreach($dataWPR as $data)
							<?php
   // echo "<pre>"; print_r($data);
$wprId = $data->id;
$reqLotNo = $data->req_lot_no;
$woId = $data->work_order_id;
$isAccept = $data->is_accept;
$itemId = $data->item_id;
$process_accepted_by = $data->process_accepted_by;
$process_deny_by = $data->process_deny_by;
$isProAccByWarehouse = $data->is_pro_acc_by_warehouse;
$processTypeId = $data->process_type_id;
$processAcceptedBy = $data->ModifiedBy->name ?? '';
$processDenyBy = $data->ModifiedBy->name ?? '';
$Itembalance = CommonController::WorkProcessItemBalanceById($wprId, $isAccept);
?>
							
							<tr id="Mid{{ $data->id }}">
								<td>{{ $wprId }} </td>
								<td>{{ ($data->WorkOrder->process_type ?? '') }}{{ ($data->WorkOrder->process_sl_no ?? '') }}</td>
								<td>{{ $data->req_lot_no ?? '' }}</td>	
								<td>{{ CommonController::getProcessName($data->process_type_id) }}</td>
								 
								<td>{{ $data->CreatedBy->name ?? '' }}</td>
								
								<td>
									{{ $data->Item->item_name ?? '' }}
									<div> 
										<?php if (! empty($data->dyeing_color)) { ?>
										<small>
										🎨 <?= htmlspecialchars($data->dyeing_color); ?>    
										</small>
										<?php } ?> 
										<?php if (! empty($data->coated_pvc)) { ?>
										<hr />
										<small>
										🧪 <?= htmlspecialchars($data->coated_pvc); ?>   
										</small>
										<?php } ?> 
										<?php if (! empty($data->extra_job)) { ?>
										<hr />
										<small>
										➕ <?= htmlspecialchars($data->extra_job); ?>  
										</small>
										<?php } ?> 
										<?php if (! empty($data->print_job)) { ?>
										<hr />
										<small>
										<i class="fa fa-print"></i>  <?= htmlspecialchars($data->print_job); ?>    
										</small>
										<?php } ?> 
									</div>

								</td>
								 	
								<td>{{ $data->ItemType->item_type_name ?? '' }}</td>	
								<td>{{ $data->required_quantity }} {{ $data->UnitType->unit_type_name ?? '' }}</td>
								<td>{{ $data->issued_quantity }} {{ $data->UnitType->unit_type_name ?? '' }}</td>	
								<td><?= $Itembalance; ?>  </td>
								<td>{{ $data->created_at }}</td>
								<td class="center" id="Waccepted{{ $data->id }}">
									<p><span class="label label-info">{{ $data->requirement_status?->label() ?? 'Unmapped' }}</span></p>
									<p><small>Allocation: {{ $data->allocation_status?->label() ?? 'Unmapped' }}</small></p>
									<?php if ($data->requirement_status === WorkRequirementStatus::Pending) { ?>
										<p>
										<?php if ($processTypeId == '7') { ?>
										<p><a href="<?php echo route('accept-warehouse-item-requirement-for-printing', enc($wprId)); ?>" class="btn btn-success btn-xs">Accept</a>
										<a href="javascript:void(0);" onclick="DenyWarehouseReq(<?php echo $wprId; ?>)"  class="btn btn-danger btn-xs"> Deny </a>
										
										<?php } else { ?>
										<a href="{{ route('accept-warehouse-item-requirement', enc($wprId)) }}" target="_blank" class="btn btn-success btn-xs">Accept</a> 
										<a href="javascript:void(0);" onClick="DenyWarehouseReq({{ $wprId }})" class="btn btn-danger btn-xs">Deny</a>
										</p>
										<?php } ?>
										
										
									<?php } elseif ($isProAccByWarehouse == 'Yes') { ?>
										<a href="javascript:void(0);" class="btn btn-success btn-xs">Accepted</a>
										<p>Accepted By <?= $processAcceptedBy; ?></p>
									<?php } elseif ($isProAccByWarehouse == 'No') { ?>
										<a href="javascript:void(0);" class="btn btn-success btn-xs">Denied</a>
										<p>Denied By <?= $processDenyBy; ?></p>
									<?php } ?>
								</td>
								
								<td>
									<?php
        // && $data->process_type_id == 3
        if ($isProAccByWarehouse == 'Yes') { ?>
										<!--- <a href="javascript:void(0);" onClick="ViewWarehouseReq({{ $woId }})" class="btn btn-info btn-xs">View</a> --->
										<a target="_blank" href="{{ route('print-warehouse-item-requirement-gatepass', enc($wprId)) }}" class="btn btn-success btn-xs">Gatepass</a>
										<?php if (! empty($reqLotNo)) { ?>
										<a target="_blank" href="{{ route('print-warehouse-item-requirement-gatepass-by-lot', enc($wprId)) }}" class="btn btn-success btn-xs">Lot Gatepass</a>
										
										 
										<?php if ($data->is_all_item_returned == 'No') { ?>
										<a target="_blank" href="{{ route('print-job-card-gatepass', enc($wprId)) }}" class="btn btn-success btn-xs">Job Card</a>
										<?php } elseif ($data->is_all_item_returned == 'Yes') { ?>
										<a class="btn btn-danger btn-xs">All Item Returnd</a>
										<?php } ?>
										
										
										
									<?php } ?>
									
									<?php } ?>
								</td>
								
							</tr>
						@endforeach
						<tr class="center text-center">
							<td class="center" colspan="20"> 
								<div class="pagination"> {{ $dataWPR->links('vendor.pagination.bootstrap-4')}} </div>
							</td>
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
  <!-- /.content-wrapper -->
  
  <!-----------------Model Popup ---------------------->
  <div class="modal fade" id="getProcessRequirementItems" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content"> 
          <div class="modal-header modal-header-primary">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
            <h3><i class="fa fa-plus m-r-5"></i>Required Stock List, for <span id="req_work_item_name"> </span>  </h3>
          </div>
          <div class="modal-body">
            <div class="row">
              <div class="col-md-12"> 
                <table class="table table-bordered">
				<tbody> 
					<tr> 
						<span id="req_stock_allot_arr"> </span> 
					</tr>
				  </tbody>
                </table> 
              </div>
            </div>
          </div>
          <div class="modal-footer"></div> 
      </div>
    </div>
  </div>  
  
  <div class="modal fade" id="StockAllotmentPop" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content"> 
          <div class="modal-header modal-header-primary">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
            <h3><i class="fa fa-plus m-r-5"></i> Stock Allotment Report, for <span id="stock_ItemName_arr"> </span> </h3>
          </div>
          <div class="modal-body">
            <div class="row">
              <div class="col-md-12"> 
                <table class="table table-bordered">
				<tbody> 
					<tr> 
						<span id="stock_allot_arr"> </span> 
					</tr>
				  </tbody>
                </table> 
              </div>
            </div>
          </div>
          <div class="modal-footer"></div> 
      </div>
    </div>
  </div>
  
  <div class="modal fade" id="PurchaseReqPop" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <form method="POST" action="{{ route('add-work-purchase-requisition') }}" class="form-horizontal" id="purchaseRequestForm" autocomplete="off">
          @csrf
          <div class="modal-header modal-header-primary">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
            <h3><i class="fa fa-plus m-r-5"></i> Purchase Request</h3>
          </div>
          <div class="modal-body">
            <div class="row">
              <div class="col-md-12">
                <fieldset>
                <table class="table table-bordered">
                  <tr>
                    <th>Work Item Name</th>
                    <th><span id="ItemNameReq">Loading...</span></th>
                  </tr>
                  <tr>
                    <td>Purchase Remark</td>
                    <td><input type="text" name="pur_remark" id="pur_remark" required class="form-control"></td>
                  </tr>
                </table>
                <span id="wprDetails"></span>
                </fieldset>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="submit" id="purchaseRequestSubmitBtn" class="btn btn-success pull-left">Send Purchase Request</button>
          </div>
        </form>
      </div>
    </div>
  </div>
  
  
  
  
 @include('frontend.common.footer') </div>
@include('frontend.common.footerscript')
<script type="text/javascript" src="{{ asset('js/jquery.validate.js') }}"></script>


<script type="text/javascript">  
var siteUrl = "{{url('/')}}";    
	$("#itemName").autocomplete({
		minLength: 0,
		source: siteUrl + '/' + "fabric_list_item",
		focus: function(event, ui) {
		  if (ui.item.part_number != '') {
			$("#itemName").val(ui.item.item_name);           
		  } else {
			$("#itemName").val(ui.item.item_name);
		  }
		  return false;
		},
		select: function(event, ui) {
		  if (ui.item.part_number != '') {
			$("#itemName").val(ui.item.item_name);            
		  } else {
			$("#itemName").val(ui.item.item_name);
		  }
		  return false;          
		}
	})
	.autocomplete("instance")._renderItem = function(ul, item) {
		return $("<li>") 
		  .append("<div>" + item.item_name + " </div>")
		  .appendTo(ul);
	};       
</script>

<script>   
  $("#dyeing_color").autocomplete({
	minLength: 1,
	source: siteUrl + '/' + "find_saleDyeingColor",
	focus: function(event, ui) { 
	  $( "#dyeing_color" ).val( ui.item.dyeing_color);
		  return false;
	},
	select: function(event, ui) {
	  $("#dyeing_color").val(ui.item.dyeing_color);          
	  return false;          
	}
  })
  .autocomplete("instance")._renderItem = function(ul, item) {
	return $("<li>")          
	  .append("<div>" + item.dyeing_color + " </div>")
	  .appendTo(ul);
  };
</script>

<script type="text/javascript">
var siteUrl = "{{url('/')}}"; 
function DenyWarehouseReq(id) 
{ 
	if(confirm("Are You Sure want to Deny this Requirement?"))
	{
		var $purchaseButton = $('#purchaseRequestSubmitBtn');
		$('#ItemNameReq').html('Loading...');
		$('#wprDetails').html('');
		$('#purchaseRequestForm').data('submitted', false);
		$purchaseButton.prop('disabled', true).addClass('disabled').text('Loading...');

		jQuery.ajax({
			type: "GET", 
			url: siteUrl + '/' +"ajax_script/getWorkProcessRequirement",
			data: {
				"_token": "{{ csrf_token() }}",
				"FId":id,	 			
			},			
			cache: false,				
			dataType: 'json',
			success: function(response)	
			{	 
				console.log(response);
				
				// alert(response.wprDetails);
				$("#ItemNameReq").html(response.WorkItemName); 
				$("#wprDetails").html(response.wprDetails); 
				$purchaseButton.prop('disabled', false).removeClass('disabled').text('Send Purchase Request');
				 
			},
			error: function(xhr)
			{
				var message = xhr.responseJSON && xhr.responseJSON.wprDetails ? xhr.responseJSON.wprDetails : '<div class="alert alert-danger">Unable to load purchase request details.</div>';
				$("#ItemNameReq").html('');
				$("#wprDetails").html(message);
				$purchaseButton.prop('disabled', true).addClass('disabled').text('Send Purchase Request');
			}
		});	
		
		$('#PurchaseReqPop').modal({backdrop: 'static', keyboard: false});	
		
	}	
		
}

function DenyWarehouseReq2(id) 
{ 
	if(confirm("Are You Sure want to Deny this Requirement?"))
	{
		jQuery.ajax({
			type: "GET", 
			url: siteUrl + '/' +"ajax_script/DenyWarehouseReq",
			data: {
				"_token": "{{ csrf_token() }}",
				"FId":id,	 			
			},			
			cache: false,				
			success: function(msg)	
			{	 
				// $("#Mid"+id).hide();	
				$("#Waccepted"+id).html('<a href="javascript:void(0);"  class="btn btn-success btn-xs">Denied</a>');				
			}
		});
		 
	}	
		
}
</script>

<script type="text/javascript">
var siteUrl = "{{url('/')}}";

function ViewWarehouseReq(id) {
    jQuery.ajax({
        type: "GET",
        url: siteUrl + '/' + "ajax_script/getWorkProcessAllotmentView",
        data: {
            "_token": "{{ csrf_token() }}",
            "FId": id,
        },
        cache: false,
        dataType: 'json', // Specify the expected data type
        success: function(response) {
            console.log(response);
            $("#stock_ItemName_arr").html(response.ItemName);
            $("#stock_allot_arr").html(response.stock_allot_arr);
        }
    });

    $('#StockAllotmentPop').modal({ backdrop: 'static', keyboard: false });
}
	

function getProcessRequirementItems(id) 
{    
    jQuery.ajax({
        type: "GET",
        url: siteUrl + '/ajax_script/getProcessRequirementItems',
        data: {
            "_token": "{{ csrf_token() }}",
            "FId": id,
        },
        cache: false,
        dataType: 'json',  
        success: function(response) {
            console.log(response);
            $("#req_work_item_name").html(response.WorkItemName);
            $("#req_stock_allot_arr").html(response.wprDetails);             
        },
        error: function(xhr, status, error) {
            // Handle errors here, e.g., console.log("Error:", error);
        }
    });
    $('#getProcessRequirementItems').modal({ backdrop: 'static', keyboard: false });
}




function AcceptWarehouseReq_Old(id) 
{ 
	if(confirm("Are You Sure want to Accept this Requirement?"))
	{
		jQuery.ajax({
			type: "GET", 
			url: siteUrl + '/' +"ajax_script/AcceptWarehouseReq",
			data: {
				"_token": "{{ csrf_token() }}",
				"FId":id,	 			
			},			
			cache: false,				
			success: function(msg)	
			{	 
				// $("#Mid"+id).hide();	
				  $("#Waccepted"+id).html('<a href="javascript:void(0);"  class="btn btn-success btn-xs">Accepted</a>');	
				
			}
		});
		 
	}	
		
}
</script>
<script>
$(function () {
    var $modal = $('#PurchaseReqPop');
    var $form = $('#purchaseRequestForm');
    var $button = $('#purchaseRequestSubmitBtn');

    $modal.on('hidden.bs.modal', function () {
        $form.data('submitted', false);
        $button.prop('disabled', false).removeClass('disabled').text('Send Purchase Request');
        $('#ItemNameReq').html('Loading...');
        $('#wprDetails').html('');
        $('#pur_remark').val('');
    });

    $form.on('submit', function (e) {
        if ($form.data('submitted') === true) {
            e.preventDefault();
            return false;
        }

        if (typeof $form.valid === 'function' && !$form.valid()) {
            return true;
        }

        $form.data('submitted', true);
        $button.prop('disabled', true).addClass('disabled').text('Processing...');
    });
});
</script>
</body>
</html>
