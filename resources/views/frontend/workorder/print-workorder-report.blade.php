<?php  
	use \App\Http\Controllers\CommonController; 
	$fromDepart   = CommonController::getProcessName($data->process_type_id);
	$toDepart     = CommonController::getProcessName($toDepartment); 
	$warehouseId  = $data->insp_work_warehouse_id; 
	$fabricFaultReasonId  = $data->fabric_fault_reason_id; 
	$warehouseName  = CommonController::getWarehouseName($warehouseId); 
	$fabricFaultReason  = CommonController::fabricFaultReason($fabricFaultReasonId);
	$workOrderNo = $data->work_order_id ?? $data->id ?? '';
	$saleOrderItemNo = $data->sale_order_item_id ?? data_get($data, 'WorkOrderItem.0.sale_order_item_id', '');
	// exit;
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>
<?php if(trim($workOrderNo)!='') { ?>
Report No:-<?php echo (10000+$workOrderNo);?>
<?php  } ?>
</title>
<link href="{{ asset('assets/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet" type="text/css"/>
<style>
	@import url(http://fonts.googleapis.com/css?family=Bree+Serif);
	body, h1, h2, h3, h4, h5, h6{
	font-family: 'Bree Serif', serif;
	}
	body{
		font-size: 16px;
	}
/*	.table > tbody > tr > td, .table > tbody > tr > th, .table > tfoot > tr > td, .table > tfoot > tr > th, .table > thead > tr > td, .table > thead > tr > th {
	padding: 8px;
	line-height: 1.42857143;
	vertical-align: top;
	border-top: 0px !important;
    }
	*/
</style>
</head>
<body>
<div class="container">
  <div class="row">
    <div class="col-xs-6">
      <h1>&nbsp;</h1>
    </div>
    <div class="col-xs-6 text-right">
      <h1>&nbsp;</h1>
    </div>
  </div>
  <div class="row">
    <div class="col-xs-6">
      <div class="panel panel-default">
        <div class="panel-heading">
          <h4 style="text-align: center;"><?php echo $compData->name;?></h4>
        </div>
        <div class="panel-body">
          <div class="table-responsive">
            <table class="table">
              <tbody>
                <tr>
                  <td>Work Order : #<?php echo (10000+$workOrderNo);?> </td>
                  <td>Sale Order Item : #<?php echo $saleOrderItemNo;?></td>
                </tr>
                <tr>
                  <td>Item : <?php echo $data->item_name;?></td>
                  <td>Warehouse: &nbsp; {{ $warehouseName }}</td>
                </tr>
                <tr>
                  <td>Fault Reason : &nbsp; {{ $fabricFaultReason }}</td>
                  <td>Work Status  : &nbsp;  {{ $data->insp_work_status }} </td>
                </tr>
                <tr>
                  <td>Completed QTY : &nbsp; {{ $data->insp_quantity }}</td>
                  <td>Defective QTY : &nbsp;</td>
                </tr>
                <tr>
                  <td>Comment : &nbsp; {{ $data->insp_comment }}</td>
                  <td>Process Status : &nbsp;
                    <?php if(!empty($data->insp_work_status_process)) { ?>
                    Item Send To Warehouse
                    <?php } ?></td>
                </tr>
                <tr>
                  <td>Receiver Name and Signature </td>
                  <td>Warehouse Staff Name & Signature</td>
                </tr>
                <tr>
                  <td>&nbsp;</td>
                  <td>&nbsp;</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
    <div class="col-xs-6">
      <div class="panel panel-default">
        <div class="panel-heading">
          <h4 style="text-align: center;"><?php echo $compData->name;?></h4>
        </div>
        <div class="panel-body">
          <div class="table-responsive">
            <table class="table">
              <tbody>
                <tr>
                  <td>Work Order : #<?php echo (10000+$workOrderNo);?> </td>
                  <td>Sale Order Item : #<?php echo $saleOrderItemNo;?></td>
                </tr>
                <tr>
                  <td>Item : <?php echo $data->item_name;?></td>
                  <td>Warehouse: &nbsp; {{ $warehouseName }}</td>
                </tr>
                <tr>
                  <td>Fault Reason : &nbsp; {{ $fabricFaultReason }}</td>
                  <td>Work Status  : &nbsp;  {{ $data->insp_work_status }} </td>
                </tr>
                <tr>
                  <td>Completed QTY : &nbsp; {{ $data->insp_quantity }}</td>
                  <td>Defective QTY : &nbsp;</td>
                </tr>
                <tr>
                  <td>Comment : &nbsp; {{ $data->insp_comment }}</td>
                  <td>Process Status : &nbsp;
                    <?php if(!empty($data->insp_work_status_process)) { ?>
                    Item Send To Warehouse
                    <?php } ?></td>
                </tr>
                <tr>
                  <td>Receiver Name and Signature </td>
                  <td>Warehouse Staff Name & Signature</td>
                </tr>
                <tr>
                  <td>&nbsp;</td>
                  <td>&nbsp;</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
    <button onClick="window.print()"> Print this page</button>
  </div>
</div>
</body>
</html>
