<?php   

use \App\Http\Controllers\CommonController;

$firstWPR = $dataWPR->first();
$firstPrintableWPR = $dataWPR2->first();

$toDepart = !empty($data->process_type_id) ? CommonController::getProcessName($data->process_type_id) : '';
$warehouseId = !empty($data->WarehouseItem) ? $data->WarehouseItem->warehouse_id : '';
$warehouseName = !empty($warehouseId) ? CommonController::getWarehouseName($warehouseId) : '';
$accDenDate = (!empty($firstWPR) && !empty($firstWPR->acc_deny_date)) ? date('d-m-Y', strtotime($firstWPR->acc_deny_date)) : '';
$processTypeId = !empty($firstWPR) ? $firstWPR->process_type_id : '';
$lotno = (!empty($firstWPR) && !empty($firstWPR->req_lot_no)) ? $firstWPR->req_lot_no : (!empty($firstWPR) ? $firstWPR->id : '');

$workOrderFirstItem = (!empty($data->WorkOrderItem) && count($data->WorkOrderItem) > 0) ? $data->WorkOrderItem[0] : null;
$dyingColor = !empty($workOrderFirstItem->dyeing_color) ? $workOrderFirstItem->dyeing_color : (!empty($dataRow->dyeing_color) ? $dataRow->dyeing_color : '');

$totGenrateJW = (int) ($dataRow->tot_genrate_jw ?? 0);
$doctext = ($totGenrateJW > 0) ? 'DUPLICATE' : 'ORIGINAL';

$totalTaka = 0;
$totalRows = 0;
$totSum = 0;
$vendorNames = [];
$vendorNameCache = [];
$designNames = [];

foreach ($dataWPR2 as $rowWOI) {
	if (!empty($rowWOI->Item) && !empty($rowWOI->Item->item_code)) {
		if (!in_array($rowWOI->Item->item_code, $designNames, true)) {
			$designNames[] = $rowWOI->Item->item_code;
		}
	}

	foreach ($rowWOI->WarehouseOutItem as $warehouseOutItem) {
		$totalTaka++;
		$totalRows++;
		$totSum += (float) $warehouseOutItem->item_qty;

		$vendorId = !empty($warehouseOutItem->WarehouseItemStock) ? $warehouseOutItem->WarehouseItemStock->vendor_id : null;
		$invoiceNumber = !empty($warehouseOutItem->WarehouseItemStock) ? $warehouseOutItem->WarehouseItemStock->invoice_number : null;

		if (!empty($vendorId)) {
			if (!isset($vendorNameCache[$vendorId])) {
				$vendorNameCache[$vendorId] = CommonController::getIndividualName($vendorId);
			}

			$VendorName = $vendorNameCache[$vendorId];
		} elseif (empty($vendorId) && empty($invoiceNumber)) {
			$VendorName = 'AJY';
		} else {
			$VendorName = $invoiceNumber;
		}

		if (!empty($VendorName) && !in_array($VendorName, $vendorNames, true)) {
			$vendorNames[] = $VendorName;
		}
	}
}

$GetAllVendor = implode(', ', $vendorNames);
$shortVendorNames = [];
foreach ($vendorNames as $vendorName) {
	$words = preg_split('/\s+/', trim($vendorName));
	$shortName = '';

	foreach ($words as $word) {
		if ($word !== '') {
			$shortName .= strtoupper(substr($word, 0, 1));
		}
	}

	$shortVendorNames[] = !empty($shortName) ? $shortName : $vendorName;
}
$GetAllVendorShort = implode(', ', $shortVendorNames);
$designList = implode(', ', $designNames);

$itemName = !empty($firstPrintableWPR->Item) ? $firstPrintableWPR->Item->item_name : '';
$unitTypeName = !empty($firstPrintableWPR->UnitType) ? $firstPrintableWPR->UnitType->unit_type_name : '';
$itemTypeName = !empty($firstPrintableWPR->ItemType) ? $firstPrintableWPR->ItemType->item_type_name : '';

?>

<!DOCTYPE html>
<html>
<head>
	<title>Job Card</title>
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">

	<style>
		@media print {
			.row {
				display: flex;
				flex-wrap: nowrap;
			}
			.col-sm-7, .col-sm-5 {
				float: none !important;
				display: inline-block;
				width: 50%;
			}
			table {
				page-break-inside: avoid;
				border-collapse: collapse;
			}
			body {
				position: relative;
			}
			.watermark-layer {
				position: fixed;
				top: 0;
				left: 0;
				right: 0;
				bottom: 0;
				z-index: 0;
				pointer-events: none;
			}
			.watermark-logo {
				position: absolute;
				width: 210px;
				opacity: 0.07;
			}
			.container, .table-bordered {
				position: relative;
				z-index: 1;
			}
			.table-bordered td, .table-bordered th {
				background-color: transparent !important;
				border: 4px solid #150101 !important;
			}
			th, td {
				border: 4px solid #150101 !important;
				padding: 2px;
			}
		}

		.watermark-layer {
			position: fixed;
			top: 0;
			left: 0;
			right: 0;
			bottom: 0;
			z-index: 0;
			pointer-events: none;
		}

		.watermark-logo {
			position: absolute;
			width: 210px;
			opacity: 0.07;
		}

		.watermark-logo:nth-child(1) { top: 8%; left: 8%; }
		.watermark-logo:nth-child(2) { top: 8%; right: 8%; }
		.watermark-logo:nth-child(3) { top: 38%; left: 36%; }
		.watermark-logo:nth-child(4) { top: 62%; left: 8%; }
		.watermark-logo:nth-child(5) { top: 62%; right: 8%; }
		.watermark-logo:nth-child(6) { top: 84%; left: 36%; }

		.container {
			position: relative;
			z-index: 1;
		}
	</style>
</head>

<body>

<div class="watermark-layer">
	<img src="https://ajytech.in/assets/dist/img/ajylogo.png" class="watermark-logo" alt="AJY Tech Watermark">
	<img src="https://ajytech.in/assets/dist/img/ajylogo.png" class="watermark-logo" alt="AJY Tech Watermark">
	<img src="https://ajytech.in/assets/dist/img/ajylogo.png" class="watermark-logo" alt="AJY Tech Watermark">
	<img src="https://ajytech.in/assets/dist/img/ajylogo.png" class="watermark-logo" alt="AJY Tech Watermark">
	<img src="https://ajytech.in/assets/dist/img/ajylogo.png" class="watermark-logo" alt="AJY Tech Watermark">
	<img src="https://ajytech.in/assets/dist/img/ajylogo.png" class="watermark-logo" alt="AJY Tech Watermark">
</div>

<div class="container">

	<h3 class="text-center"> 
		<span style="float:left;"><b>{{ $doctext }}</b></span>
		<span style="float:right;"><b>JOB CARD</b></span> 
		<span style="font-size:24px; font-weight:bold;">AJY TECH INDIA PVT. LTD.</span>
	</h3>

	<table class="table table-bordered">
		<tr>
			<th>DATE</th> 
			<td><?php echo empty($dataRow->acc_deny_date) ? date('d-m-Y') : date('d-m-Y', strtotime($dataRow->acc_deny_date)); ?></td>
			<th>LOT NO</th>
			<td><span style="font-size:24px; font-weight:bold;">{{ $lotno }}</span></td>
		</tr>
		<tr>
			<th>QUALITY</th>
			<td>{{ $itemName }}</td>
			<th>TAKA</th>
			<td>{{ $totalTaka }}</td>  
		</tr>
		<tr>
			<th>COLOR</th>
			<td>{{ $dyingColor }}</td>
			<th>GREIGE MTR</th>
			<td>{{ number_format((float) $totalAllotedQuantity, 2, '.', '') }} {{ $unitTypeName }} {{ $itemTypeName }}</td>
		</tr>
		<tr>
			<th>SUPPLIER</th>
			<td title="{{ $GetAllVendor }}">{{ $GetAllVendorShort }}</td> 
			<th>DESIGN</th>
			<td>{{ $designList }}</td>		
		</tr>
	</table>

	<table class="table table-bordered">
		<thead>
			<tr>
				<th>Sr.No.</th>
				<th>Taka</th>
				<th>G.MTR</th>
				<th>F.MTR</th>
				<th>Color</th>
				<th width="50%">Remark</th>
			</tr>
		</thead>
		<tbody>
			<?php 
				$j = 1;
				$middleRow = ($totalRows > 0) ? (ceil($totalRows / 2) + 1) : 0;
			?>

			<?php foreach($dataWPR2 as $rowWOI) { ?>
				<?php foreach($rowWOI->WarehouseOutItem as $warehouseOutItem) { ?>
					<tr>
						<td>{{ $j }}</td>
						<td>{{ !empty($warehouseOutItem->insp_taka_number) ? $warehouseOutItem->insp_taka_number : $warehouseOutItem->dyeing_taka_number }}</td>
						<td>{{ number_format((float) $warehouseOutItem->item_qty, 2, '.', '') }}</td>
						<td>&nbsp;</td>
						<td>{{ !empty($warehouseOutItem->dyeing_color) ? $warehouseOutItem->dyeing_color : '' }}</td>
						<td class="text-center">
							<?php if ($j == $middleRow) { ?>
								<b>FOR FABRIC CUTTING</b>
							<?php } ?>
						</td>
					</tr>
					<?php $j++; ?>
				<?php } ?>
			<?php } ?>

			<?php if ($totalRows == 0) { ?>
				<tr>
					<td colspan="6" class="text-center">No warehouse item found.</td>
				</tr>
			<?php } ?>

			<tr>                                 
				<td><b>Total</b></td>
				<td>&nbsp;</td>
				<td><b>{{ number_format((float) $totSum, 2, '.', '') }} MTR</b></td>
				<td>&nbsp;</td>
				<td>&nbsp;</td>
				<td>&nbsp;</td>
			</tr>
		</tbody>
	</table>

	<table class="table table-bordered">
		<tr>
			<th width="15%">FINISH GSM</th>
			<td width="35%">&nbsp;</td>
			<th width="15%">COATED GSM</th>
			<td width="35%">&nbsp;</td>
		</tr>
		<tr>
			<th width="15%">DYEING DATE</th>
			<td width="35%">&nbsp;</td>
			<th width="15%">FINAL SHORTAGE %</th>
			<td width="35%">&nbsp;</td>
		</tr>
		<tr>
			<th width="15%">FINAL INS DATE</th>
			<td width="35%">&nbsp;</td>
			<th width="15%">FINAL INS MTR</th>
			<td width="35%">&nbsp;</td>
		</tr>
		<tr>
			<th width="15%">MTR IF BAL</th>
			<td width="35%">&nbsp;</td>
			<th width="15%">DISPATCH MTR</th>
			<td width="35%">&nbsp;</td>
		</tr>
		<tr>
			<th width="15%">CUSTOMER</th>
			<td width="35%">&nbsp;</td>
			<th width="15%">INV & DATE</th>
			<td width="35%">&nbsp;</td>
		</tr>
		<tr>
			<th width="15%">DYEING QC NAME & DATE</th>
			<td width="35%">&nbsp;</td>
			<th width="15%">FINAL INSPECTION QC NAME</th>
			<td width="35%">&nbsp;</td>
		</tr>
		<tr>
			<th width="15%">APPROVED BY</th>
			<td colspan="3">&nbsp;</td> 
		</tr>
	</table>

</div>

<script>
	var wprId = "{{ $wprId }}";
	var siteUrl = "{{ url('/') }}";
	var printCountUpdated = false;

	function updateTotGenrateJw() {
		if (printCountUpdated) {
			return false;
		}

		printCountUpdated = true;

		fetch(siteUrl + "/update-tot-genrate-jw/" + wprId, {
			method: "POST",
			headers: {
				"X-CSRF-TOKEN": "{{ csrf_token() }}",
				"Content-Type": "application/json",
				"Accept": "application/json"
			},
			body: JSON.stringify({})
		}).then(function(response) {
			return response.json();
		}).then(function(data) {
			console.log("tot_genrate_jw updated:", data);
		}).catch(function(error) {
			printCountUpdated = false;
			console.error("Error updating tot_genrate_jw:", error);
		});
	}

	window.addEventListener("afterprint", function() {
		updateTotGenrateJw();
	});

	// Automatically open print dialog on page load
	// window.onload = function() {
	// 	window.print();
	// };
</script>

</body>
</html>
