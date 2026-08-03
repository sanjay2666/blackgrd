<?php
	use \App\Http\Controllers\CommonController;  
?>
<!DOCTYPE html>
<html lang="en">
<head>@include('common.head')
</head>
<body class="hold-transition sidebar-mini">
<!--preloader-->
 
<!-- Site wrapper -->
<div class="wrapper"> @include('common.header')
    <div class="content-wrapperd">
    <section class="content">
      <div class="row">
        <div class="col-sm-12">
		{!! CommonController::display_message('message') !!}
          <div class="panel panel-bd lobidrag">
            <div class="panel-heading">
              <div class="btn-group" id="buttonexport"><a href="add-warehouse"><h4>Required Warehouse Items for Scheduled Printing</h4></a></div>
            </div>
            <div class="panel-body">
      
                
              
              <div class="table-responsive">
                 <table id="dataTableExample1" class="table table-bordered table-striped table-hover">
					<thead>
						<tr class="info">
							<th style="width:4%;">ID</th>
							<th style="width:4%;">WO No.</th>
							<th style="width:4%;">Lot No.</th>
							<th style="width:4%;">Process</th>
							<th style="width:5%;">Requested By</th>
							<th style="width:8%;">Item</th> 
							<th style="width:6%;">Type</th>
							<th style="width:8%;">Req. Qty</th>
							<th style="width:8%;">Allot. Qty</th>
							<th style="width:12%;">Stock</th>
							<th style="width:6%;">Created</th>
							<th style="width:5%;">Status</th>
							<th style="width:5%;">Allotment</th>
						</tr>
					</thead>

					<tbody>
						<?php foreach($dataWPR as $data) { ?>
							<?php
								//   echo "<pre>"; print_r($data);  
								$wprId              	= $data->id;
								$reqLotNo               = $data->req_lot_no;
							    $woId               	= $data->work_order_id;
								$isAccept 				= $data->is_accept;
							    $itemId               	= $data->item_id;
								$process_accepted_by   	= $data->process_accepted_by;
								$process_deny_by       	= $data->process_deny_by;
								$isProAccByWarehouse   	= $data->is_pro_acc_by_warehouse;
								$processAcceptedBy     	= CommonController::getEmpName($process_accepted_by);
								$processDenyBy         	= CommonController::getEmpName($process_deny_by); 
								$Itembalance            = CommonController::WorkProcessItemBalanceById($wprId,$isAccept); 	
								$processName 			= CommonController::getProcessName($data->process_type_id);
								$workreqSendBy 			= CommonController::getEmpName($data->work_req_send_by);
							?>
							
							<tr id="Mid{{ $data->id }}">
								<td>{{ $wprId }} </td>
								<td>{{ $data['WorkOrder']->process_type }}{{ $data['WorkOrder']->process_sl_no }}</td>
								<td><?=$data->req_lot_no;?>  </td>	
								<td><?=$processName;?></td>
								<td><?=$workreqSendBy;?></td>	
								
								
								<td>
									<div style="font-size:16px; font-weight:bold; color:#31708f; margin-bottom:8px;">
										<?=$data['Item']->item_name;?>
									</div>
									<div style="background:#fff; border:1px solid #ddd; border-radius:4px; padding:8px; margin-bottom:8px;"> 
										<?php if (!empty($data->dyeing_color)) { ?>
											<small style="display:block; font-weight:bold; color:#5cb85c;">
												🎨 <?= htmlspecialchars($data->dyeing_color); ?>    
											</small>
										<?php } ?> 
										<?php if (!empty($data->coated_pvc)) { ?>
											<hr style="margin:4px 0;" />
											<small style="display:block; color:#5bc0de;">
												🧪 <?= htmlspecialchars($data->coated_pvc); ?>   
											</small>
										<?php } ?> 
										<?php if (!empty($data->extra_job)) { ?>
											<hr style="margin:4px 0;" />
											<small style="display:block; color:#f0ad4e;">
												➕ <?= htmlspecialchars($data->extra_job); ?>  
											</small>
										<?php } ?> 
										<?php if (!empty($data->print_job)) { ?>
											<hr style="margin:4px 0;" />
											<small style="display:block; color:#d9534f;">
											<i class="fa fa-print"></i>  <?= htmlspecialchars($data->print_job); ?>    
											</small>
										<?php } ?> 
									</div>
								
								    </td>	
								
								
								
								<td><?=$data['ItemType']->item_type_name;?>  </td>	
								<td><?=$data->quantity;?>  <?=$data['UnitType']->unit_type_name;?> </td>
								<td><?=$data->alloted_quantity;?>  <?=$data['UnitType']->unit_type_name;?> </td>	
								<td><?=$Itembalance;?>  </td>
								<td><?= date('d-m-Y', strtotime($data->created_at)); ?>  </td>
								<td class="center" id="Waccepted<?=$data->id;?>">
									<?php if(empty($isProAccByWarehouse)) { ?>
										<p><a href="<?php echo route('accept-warehouse-item-requirement-for-printing', base64_encode($wprId)); ?>" target="_blank" class="btn btn-success btn-xs">Accept</a>
										<a href="javascript:void(0);" onclick="DenyWarehouseReq(<?php echo $wprId; ?>)"  class="btn btn-danger btn-xs"> Deny </a>
									</p>
									<?php } elseif($isProAccByWarehouse == 'Yes') { ?>
										<span class="badge badge-success">Accepted</span>
										<small class="text-muted d-block"> Accepted By: <?=$processAcceptedBy; ?> </small>
									<?php } elseif($isProAccByWarehouse == 'No') { ?>
										<span class="badge badge-danger">Denied</span>
										<small class="text-muted d-block">Denied By: <?=$processDenyBy; ?></small>
									<?php } ?>
								</td>								
								<td> Working  </td>
								
							</tr>
						<?php } ?>
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
        <form method="post" action="{{ route('add_remark_for_deny_requisition')}}" class="form-horizontal" autocomplete="off">
          @csrf
          <div class="modal-header modal-header-primary">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
            <h3><i class="fa fa-plus m-r-5"></i> Deny Request Reason</h3>
          </div>
          <div class="modal-body">
            <div class="row">
              <div class="col-md-12">
                <fieldset>
                <table class="table table-bordered">
                  <tr>
                    <th>Work Item Name</th>
                    <th><span id="ItemNameReq"></span></th>
                  </tr>                   
                  <tr>
				  <td>Your Remark </td>
				  <td> <input type="text" name="deny_remark" id="deny_remark" required class="form-control"> </td>
				  </tr>
                </table>
				<span id="wprDetails"></span>
				
				
                </fieldset>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="submit" class="btn btn-success pull-left">Save</button>
          </div>
        </form>
      </div>
    </div>
  </div>
  
  
  
  
 @include('common.footer') </div>
@include('common.formfooterscript')
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
		jQuery.ajax({
			type: "GET", 
			url: siteUrl + '/' +"ajax_script/getWorkProcessPrintingRequirement",
			data: {
				"_token": "{{ csrf_token() }}",
				"FId":id,	 			
			},			
			cache: false,				
			success: function(response)	
			{	 
				response = JSON.parse(response);
				console.log(response);
				
				// alert(response.wprDetails);
				$("#ItemNameReq").html(response.WorkItemName); 
				$("#wprDetails").html(response.wprDetails); 
				 
			}
		});	
		
		$('#PurchaseReqPop').modal({backdrop: 'static', keyboard: false});	
		
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
</body>
</html>
