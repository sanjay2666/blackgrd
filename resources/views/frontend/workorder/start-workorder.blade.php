<?php
	use \App\Http\Controllers\CommonController;  
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
<div class="wrapper">
@include('common.header')
<div class="content-wrapper">
  <section class="content">
    <div class="row">
      <!-- Form controls -->
      <div class="col-sm-12">
	  {!! CommonController::display_message('message') !!}
        <div class="panel panel-bd lobidrag">
          <div class="panel-heading">
            <div class="btn-group" id="buttonlist"> <a class="btn btn-add " href="{{ route('show-workorders') }}"> <i class="fa fa-list"></i> Work Order </a> </div>
          </div>
          <div class="panel-body">
            <form method="post" action="{{ route('store_workorder')}}" onSubmit="return check_form();" autocomplete="off">
              @csrf
              <div class="row">
                <div class="col-md-12"> </div>
                <div class="col-md-12">
                  <h2>Sale Order  : {{ $saleordId }}</h2>
                  <h3>Order Type  : {{ CommonController::getItemTypeName($dataSO->sale_order_type) }} </h3>
                  <h4>Priority    : {{ $dataSO->order_priority }}</h4>
                  <table class="table table-bordered table-striped table-hover">
                    <tr>
                      <th>Item Name</th>
                      <th>Unit</th>
                      <th>PCS</th>
                      <th>Cut</th>
                      <th>Meter</th>
                      <th>Priority</th>
                      <th>Remarks</th>
                      <th>Check Stock</th>
                    </tr>
                    <?php 
						foreach($dataSOI as $row) 
						{							 
							$WorkOrderId 		= $row['WorkOrder']->work_order_id;
							$procesNum   		= $row['WorkOrder']->process_type_id;
							$saleOrdItemId   	= $row->sale_order_item_id;
						?>
                    <tr>
                      <input type="hidden" id="itam_id" name="itam_id" value="<?=$row->item_id;?>" class="custom-control-input">
                      <td><?=$row->name;?><?=$row->sale_order_item_id;?></td>
                      <td><?php echo $row->unit; ?></td>
                      <td><?php echo $row->pcs; ?></td>
                      <td><?php echo $row->cut; ?></td>
                      <td><?php echo $row->meter;?></td>
                      <td><?php echo $row->order_item_priority; ?></td>
                      <td><?php echo $row->remarks; ?></td>
                      <td><?php if(empty($WorkOrderId)) { ?>
					  
                       <a id="chk{{ $row->sale_order_item_id }}" href="javascript:void(0);" onClick="checkWarehouseItemStock({{ $row->sale_order_item_id }})" class="btn btn-primary btn-xs">Check Stock</a> 
						 <?php /* ?>
                        <a id="chk{{ $saleOrdItemId }}" href="{{ route('check-warehouse-item-stock', enc($saleOrdItemId)) }}" class="btn btn-primary btn-xs">Check Stock</a>
						
						<?php */ ?>
						
                        <?php } else { ?>
                        <a href="javascript:void(0);" class="btn btn-success btn-xs">Item in {{ CommonController::getProcessName($procesNum) }} Process </a>
                        <?php } ?>
                        <span id="recordId{{ $row->sale_order_item_id }}"> </span> </td>
                    </tr>
                    <?php } ?>
                  </table>
                </div>
                <p>&nbsp; </p>
                <div class="col-md-12" id="main_div">
                  <div class="row">
                    <div class="col-xs-12">
                      <div class="table-responsive table-responsive-custom"> 
					  <span id="showPurchaseItems"> </span>
					  </div>
                    </div>
                  </div>
                  <?php /* ?><button type="submit" class="btn btn-primary pull-left">Confirm</button> <?php */ ?>
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
<!-------------------Model Start-------------------->
<div class="modal fade" id="CreateOrderModelId" tabindex="-1" role="dialog" aria-hidden="true">
<div class="modal-dialog modal-lg">
<div class="modal-content">
<div class="modal-header modal-header-primary">
  <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
  <h3><i class="fa fa-plus m-r-5"></i> Create Work Order</h3>
  <span id="WorkItemName"> </span> 
  <span id="purItemQtyTotal"> </span> 
  <p>Available Stock</p>
  </div>
	<div class="modal-body">
		<div class="row">
			<div class="col-md-12">
			 
			<fieldset>
			<table class="table table-bordered ">
			  <tr>
				<th>Item Name</th>
				<th>Stock</th>
				<th>Unit</th>
				<th>Pcs</th>
				<th>Cut</th>
				<th>Meter</th>
				<th>Warehouse</th>
				<th>Process</th>
				<th>Action</th>
			  </tr>
				<?php 
				foreach($dataIT as $row) 
				{ 	
				 
				  // echo "<pre>"; print_r($row['WarehouseItemStock']); // exit;
					$warehouseItemId = $row['WarehouseItem']->id;
					$warehouseId 	 = $row['WarehouseItem']->warehouse_id;	
					// $purItemQty 	 = $row['WarehouseItem']->pur_item_qty;	
			        $purItemQty 	 = $row['WarehouseItemStock']->count();			
					$procesNum 		 = $row['WarehouseItem']->process_type_id; // exit;
					$itemId 	     = $row['WarehouseItem']->item_id;	
					$itemTypeId 	 = $row->item_type_id;								
					$CrprocesNum 	 = $procesNum-1;
				?>
				  <tr>
					<td> {{ $row->item_type_name }} </td>
					<td> <a href="javascript:void(0);" onClick="showItemList({{ $itemTypeId }})" > {{ $purItemQty }} </a> </td>
					<td> {!! $row['WarehouseItem']->unit !!} </td>
					<td> {!! $row['WarehouseItem']->pcs !!} </td>
					<td> {!! $row['WarehouseItem']->cut !!} </td>
					<td> {!! $row['WarehouseItem']->cut !!} </td>
					<td> {{ CommonController::getWarehouseName($warehouseId) }} </td>
					<td> {{ CommonController::getProcessName($procesNum) }} </td>
					
					<td><input type="hidden" id="TypeId<?=$itemTypeId;?>" value="<?=$itemTypeId;?>">
					  <input type="hidden" id="ProTypeId<?=$itemTypeId;?>" value="<?=$itemTypeId+1;?>">
					  <?php if(!empty($procesNum) && !empty($purItemQty)) { ?>
					  <p><a href="javascript:void(0);" onClick="addProcessWorkOrder({{ $itemTypeId }},{{ $procesNum }})" class="btn btn-primary btn-xs">Create {{ CommonController::getProcessOutPutName($procesNum) }} Order</a></p>
					  <p><a target="_blank" href="{{ route('add-purchase') }}" class="btn btn-primary btn-xs">Create Purchase Order</a></p>
					  <?php //} else if(empty($procesNum) && $itemTypeId =='1') { ?>
					  <?php } else   { ?>
					  <p><a target="_blank" href="{{ route('add-purchase') }}" class="btn btn-primary btn-xs">Create Purchase Order</a></p>
					  <?php } ?>
					</td>  
					
				  </tr>
				<?php } ?>
			</table>
				<p>
				  <input type="hidden" id="item_id" name="item_id" value="">
				  <input type="hidden" id="saleordId" name="saleordId" value="<?=$saleordId;?>">
				  <input type="hidden" id="saleordItemId" name="saleordItemId" value="">
				</p>
			</fieldset>
			</div>
		</div>
	</div>
	<div class="modal-footer">
	  <button type="button" class="btn btn-danger pull-left" data-dismiss="modal" aria-hidden="true">Close</button>
	</div>
</div>
</div>
</div>

<div class="modal fade" id="ShowItemModelId" tabindex="-1" role="dialog" aria-hidden="true">
<div class="modal-dialog modal-lg">
<div class="modal-content">
<div class="modal-header modal-header-primary">
	<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
	<h3><i class="fa fa-plus m-r-5"></i>Available Stock</h3> 
</div>

	<div class="modal-body">
		<div class="row">
			<div class="col-md-12">
			 <span id="ItemTyleNameD"></span>
			 <span id="StockAvalableD"></span>
			 
			</div>
		</div>
	</div>
	<div class="modal-footer">
	  <button type="button" class="btn btn-danger pull-left" data-dismiss="modal" aria-hidden="true">Close</button>
	</div>
	
</div>
</div>
</div>


<!-------------------Model End-------------------->


@include('common.footer')
</div>
@include('common.formfooterscript')
<link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
<script type="text/javascript">
	var siteUrl = "{{url('/')}}";
	 
	function showItemList(itemTypeId) 
	{		
		var siteUrl = "{{url('/')}}";	 
		jQuery.ajax({
			type: "GET",  
			url: siteUrl + '/' +"ajax_script/showItemList", 
			data: 
			{
				"_token": "{{ csrf_token() }}",
				"ItemTypeId":itemTypeId,	 			
			},	 
			cache: false,				
			success: function(response)	
			{  
				response = JSON.parse(response);
				console.log(response);	
				
				 $("#ItemTyleNameD").html(response.ItemTypeName); 
				 $("#StockAvalableD").html(response.RequestedItemList);  

				$('#ShowItemModelId').modal({backdrop: 'static', keyboard: false});								
				 
			}
		});		
	}
</script>

<script type="text/javascript">
	var siteUrl = "{{url('/')}}";
	 
	function addProcessWorkOrder(itemTypeId,procesNum) 
	{		
		var siteUrl = "{{url('/')}}";	
		var saleordItemId 	= $("#saleordItemId").val();  
		jQuery.ajax({
			type: "GET",  
			url: siteUrl + '/' +"ajax_script/createWorkOrder", 
			data: {
				"_token": "{{ csrf_token() }}",
				"SaleOrdItemId":saleordItemId,
				"ItemTypeId":itemTypeId,
				"procesNum":procesNum,	 			
			},	 
			cache: false,				
			success: function(msg)	
			{ 
				 
				// var data = msg.split("||");  
				if(msg ==='1')
				{
					window.location.href = siteUrl + '/' +"show-workorders"; 	
				}
				 

				
			}
		});		
	}
</script>

<script type="text/javascript">
	var siteUrl = "{{url('/')}}";
	function checkWarehouseItemStock(id) 
	{		
		// alert(id);
		jQuery.ajax({
			type: "GET",  
			url: siteUrl + '/' +"ajax_script/checkWarehouseItemStock", 
			data: {
				"_token": "{{ csrf_token() }}",
				"FId":id,	 			
			}, 
			cache: false,				
			success: function(response)	
			{  
				response = JSON.parse(response);
				console.log(response);	 
				if(response.itemId === '0')
				{
					$("#recordId"+id).html( 'Not Available' );	 
				}  
				$("#item_id").val(response.itemId); 
				$("#WorkItemName").html(response.ItemName); 
				$("#saleordItemId").val(response.saleordItemId); 
				$("#purItemQtyTotal").html(response.purItemQtyTotal);  
					
				$('#CreateOrderModelId').modal({backdrop: 'static', keyboard: false})				
			}
		}); 
	}
</script>
</body>
</html>
