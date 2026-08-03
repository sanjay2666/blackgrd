<?php
   
  // echo "<pre>"; print_r($dataWPR); exit;
use \App\Http\Controllers\CommonController;
$toDepart   	= CommonController::getProcessName($data->process_type_id);
// $toDepart    = CommonController::getProcessName($toDepartment);
$warehouseId 	= $data['WarehouseItem']->warehouse_id;
$warehouseName  = CommonController::getWarehouseName($warehouseId); 
$generator 		= new Picqer\Barcode\BarcodeGeneratorHTML();
$accDenDate 	= date('d-m-Y', strtotime($dataWPR['0']->acc_deny_date)); 
$processTypeId 	= $dataWPR['0']->process_type_id; 
 
if(!empty($dataWPR['0']->req_lot_no)) $lotno  = 	$dataWPR['0']->req_lot_no;
else $lotno  = 	$dataWPR['0']->id; 


	// echo "<pre>"; print_r($dataWPR2); exit;
				 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?php if(trim($data->work_order_id)!='') { ?>
		Gatepass No:-<?php echo (10000+$data->work_order_id);?>
	<?php  } ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            background-color: #f5f5f5;
        }
        .gatepass {
            background-color: #fff;
            padding: 20px;
            border: 1px solid #ddd;
            width: 100%;
            max-width: 800px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .gatepass-header {
            text-align: center;
            margin-bottom: 20px;
        }
        .gatepass-header h1 {
            margin: 0;
            font-size: 24px;
            color: #333;
        }
        .gatepass-header h2 {
            margin: 5px 0;
            font-size: 20px;
            color: #555;
        }
        .gatepass-header .barcode {
            margin: 10px 0;
        }
        .gatepass-header .barcode img {
            width: 200px;
            height: 50px;
        }
        .details, .from-to {
            margin: 15px 0;
        }
        .details ul, .from-to ul {
            list-style: none;
            padding: 0;
        }
        .details ul li, .from-to ul li {
            margin: 5px 0;
            font-size: 16px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 8px;
            text-align: center;
        }
        th {
            background-color: #f2f2f2;
        }
        .signature {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }
        .signature div {
            width: 48%;
            text-align: center;
            padding: 10px;
            border-top: 1px solid #000;
        }

        @media print {
            body, .gatepass {
                width: 100%;
                max-width: none;
                box-shadow: none;
                margin: 0;
                padding: 0;
            }
            .gatepass {
                border: none;
                padding: 0;
            }
            .gatepass-header h1 {
                font-size: 22px;
            }
            .gatepass-header h2 {
                font-size: 18px;
            }
            .details ul li, .from-to ul li, th, td {
                font-size: 12px;
            }
            .signature {
                margin-top: 30px;
            }
            .signature div {
                border-top: 1px solid #000;
                padding-top: 15px;
            }
        }
    </style>
</head>

<body>
    <div class="gatepass">
        <div class="gatepass-header">
            <h1><?php echo $lotno;?></h1>
            <h2>Gatepass</h2>
            <h2>{{ $compData->name }}</h2>  
 <div class="barcode">
                <center>{!! $generator->getBarcode($data->work_order_id, $generator::TYPE_CODE_128) !!}</center>
            </div>			
            <p>{{ $compData->phone }}/{{ $compData->another_phone }}</p>
        </div>

        <div class="details"> 
            <table border="1"> 
                <tbody>
				<?php 
				if(!empty($dataWPR['0']->req_lot_no)) $lotno  = $dataWPR['0']->req_lot_no;
				else $lotno  = 	$dataWPR['0']->id; 
				?> 
                    <tr>
						<th>Work Order</th>
                        <td>  
						<?php
							$workOrderIdsArray = $workOrderIds->toArray();  						
							$string = implode(',', $workOrderIdsArray);							 
							echo $string;						
						?> 
						</td>
                        <th>Lot Number</th>
                        <td> {{ $lotno }}</td> 
                    </tr> 
                    <tr>                        
						<th>Item</th>
                        <td>
						{{ $dataWPR2['0']['Item']->item_name }}   
                        </td>
						<th>Designe</th>
                        <td>
						{{ $dataWPR2['0']['Item']->item_code }}                            
                        </td>
                    </tr>
                    <tr>
                        <th>QTY</th>
                        <td>{{ $totalAllotedQuantity }} {{ $dataWPR2['0']['UnitType']->unit_type_name }} {{ $dataWPR2['0']['ItemType']->item_type_name }} 
                            
                        </td>
                       	<th>Supplier</th>
                        <td> &nbsp; </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Sr.No.</th>                   
                    <th>G.T.Number</th>	
					<th>Meter</th> 			 
					<?php if($processTypeId =='4') {  ?>
					<th>Lot Number</th>
					<th>D.T.Number</th> 					
                    <th>Color</th>
					<?php } ?>
					<th>Supplier</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $i = 1;
                foreach($dataWPR2 as $rowWOI)
                {    // echo "<pre>"; print_r($rowWOI['WarehouseOutItem']); exit;
                ?>  
				@foreach($rowWOI->WarehouseOutItem as $warehouseOutItem)	
				<?php 
					$vendorId 	= $rowWOI['WarehouseItem']->individual_id;
					$vendorName = CommonController::getVendorName($vendorId);
				?>
                <tr>
                    <td>{{ $i++ }}   </td>
					<td>{{ $warehouseOutItem->insp_taka_number }}</td> 
                    <td>{{ $warehouseOutItem->item_qty }}</td> 					
					<?php if($processTypeId =='4') {  ?>
					<td>{{ $warehouseOutItem->dyeing_lot_number }}</td> 
					<td>{{ $warehouseOutItem->dyeing_taka_number }}</td> 
					<td>{{ $warehouseOutItem->dyeing_color }}</td> 
					<?php } ?> 
					<td>{{ $vendorName }}</td> 	
                </tr>
				 @endforeach
                <?php } ?> 
            </tbody>
        </table>

        <div class="from-to"> 
            <table>     
                <tr>
                    <th><strong>From Department:</strong></th>
                    <td>{{ $warehouseName }}</td>
                </tr>
                <tr>
                    <th><strong>To Department:</strong></th>
                    <td>{{ $toDepart }}</td>
                </tr>
                <tr>
                    <th><strong>Generated Date:</strong></th>
                    <td>{{ $accDenDate }}</td>
                </tr>
            </table> 
        </div>

        <div class="signature">
            <div>Sender Signature</div>
            <div>Receiver Signature</div>
        </div>
    </div>
</body>
</html>
 
 
 
