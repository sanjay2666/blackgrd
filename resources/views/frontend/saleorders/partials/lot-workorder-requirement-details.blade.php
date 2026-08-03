<?php
	if (!function_exists('lotFirstName')) {
		function lotFirstName($name) {
			$name = trim((string) $name);
			if ($name === '') return '';
			$parts = preg_split('/\s+/', $name);
			return ucfirst(strtolower($parts[0]));
		}
	}
?>
<table class="table table-bordered table-striped table-condensed">
	<tr>
		<th class="alert-success">Id</th>
		<th class="alert-success">Item Name</th>
		<th class="alert-success">QTY</th>
		<th class="alert-success">Unit</th>
		<th class="alert-success">Type</th>
		<th class="alert-success">Taka Number</th>
		<th class="alert-success">Vendor</th>
		<th class="alert-success">Invoice No.</th>
		<th class="alert-success">Receive Date</th>
		<th class="alert-success">Received By</th>
		<th class="alert-success">Remarks</th>
	</tr>
	<?php
	foreach($rowArr['WarehouseOutItem'] as $outItemRow)
	{
		// echo "<pre>"; print_r($outItemRow); exit;
		$WarehouseItem 		= $outItemRow->WarehouseItem;
		$RowitemName 		= isset($outItemRow->Item->item_name) ? $outItemRow->Item->item_name : '';
		$RowitemTypeName 	= isset($outItemRow->ItemType->item_type_name) ? $outItemRow->ItemType->item_type_name : '';
		$RowitemUnitName 	= isset($outItemRow->UnitType->unit_type_name) ? $outItemRow->UnitType->unit_type_name : '';
		$invoiceNumber 		= isset($WarehouseItem->invoice_number) ? $WarehouseItem->invoice_number : '';
		$documentWisId 		= !empty($outItemRow->wis_id) ? enc($outItemRow->wis_id) : '';
		$documentUrl 		= !empty($documentWisId) ? route('warehouse-stock-document', $documentWisId) : '';
		$receivedByName 	= '';
		if ($WarehouseItem && $WarehouseItem->Individual) {
			$receivedByName = !empty($WarehouseItem->Individual->nick_name) ? $WarehouseItem->Individual->nick_name : $WarehouseItem->Individual->name;
		} elseif ($WarehouseItem && !empty($WarehouseItem->emp_name)) {
			$receivedByName = $WarehouseItem->emp_name;
		}
		$vendorName 	= '';
		if ($WarehouseItem && $WarehouseItem->Vendor) {
			$vendorName = !empty($WarehouseItem->Vendor->nick_name) ? $WarehouseItem->Vendor->nick_name : $WarehouseItem->Vendor->name;
		}
	?>
		<tr>
			<td class="alert-link"> <?= $outItemRow->id; ?> </td>
			<td class="alert-link"> <?= $RowitemName; ?> </td>
			<td class="alert-link"> <?= $outItemRow->item_qty; ?> </td>
			<td class="alert-link"> <?= $RowitemUnitName; ?> </td>
			<td class="alert-link"> <?= $RowitemTypeName; ?> </td>
			<td class="alert-link">
				<?= (empty($WarehouseItem->invoice_number))
					? '<a href="javascript:void(0);" class="toggle-sub-details">' . $outItemRow->insp_taka_number . '</a>'
					: $outItemRow->insp_taka_number; ?>
			</td>
			<td class="alert-link"> <?= htmlspecialchars($vendorName); ?> </td>
			<td class="alert-link">
				<a href="javascript:void(0);" class="show-stock-document" data-wis-id="<?= htmlspecialchars($documentWisId); ?>" data-document-url="<?= htmlspecialchars($documentUrl); ?>" data-invoice-number="<?= htmlspecialchars($invoiceNumber); ?>"> <?= $invoiceNumber; ?></a>
			</td>
			<td class="alert-link"> <?= isset($WarehouseItem->receive_date) ? $WarehouseItem->receive_date : ''; ?> </td>
			<td class="alert-link"> <?= htmlspecialchars(lotFirstName($receivedByName)); ?> </td>
			<td class="alert-link"> <?= $outItemRow->item_remark; ?> </td>
		</tr>

		<?php if($rowArr['WorkOrder']->process_type =='D') { ?>
		<tr class="sub-details-row hidden">
			<td colspan="13">
				<table class="table table-bordered table-striped table-condensed">
					<tr>
						<th class="alert-warning">WO No.</th>
						<th class="alert-warning">Gen. Date</th>
						<th class="alert-warning">Item</th>
						<th class="alert-warning">Insp. No.</th>
						<th class="alert-warning">Size</th>
						<th class="alert-warning">Machine</th>
						<th class="alert-warning">Master</th>
						<th class="alert-warning">Inspection</th>
						<th class="alert-warning">Vendor Name</th>
						<th class="alert-warning">Inv. No.</th>
						<th class="alert-warning">Inv. Date</th>
					</tr>
					<?php
					$getSubWork = isset($subWorkDetailsByOutItem) ? $subWorkDetailsByOutItem->get($outItemRow->id, collect()) : collect();
					foreach ($getSubWork as $valArr)
					{
						
						//  echo "<pre>"; print_r($valArr); exit;
						$invoiceNumbers = [];
						$inspTakaNumber = [];
						$vendorNames = [];
						$invoiceWisId = null;
						foreach ($valArr['WarehouseOutItem'] as $item) 
						{
							$itemWarehouseItem = $item->WarehouseItem;
							if (isset($item['WarehouseItem']['invoice_number'])) {
								$invoiceNumbers[] = $item['WarehouseItem']['invoice_number'];
							}
							if (isset($item['insp_taka_number'])) {
								$inspTakaNumber[] = $item['insp_taka_number'];
							}
							if ($itemWarehouseItem && $itemWarehouseItem->Vendor) {
								$itemVendorName = !empty($itemWarehouseItem->Vendor->nick_name) ? $itemWarehouseItem->Vendor->nick_name : $itemWarehouseItem->Vendor->name;
								if (!empty($itemVendorName)) {
									$vendorNames[] = $itemVendorName;
								}
							}
							if (!$invoiceWisId && !empty($item->wis_id)) {
								$invoiceWisId = $item->wis_id;
							}
						}
						$invoiceNumbersStr = implode(', ', $invoiceNumbers);
						$inspTakaNumberStr = implode(', ', $inspTakaNumber);
						$vendorNamesStr = implode(', ', array_unique($vendorNames));
						$documentWisId = !empty($invoiceWisId) ? enc($invoiceWisId) : '';
						$documentUrl = !empty($documentWisId) ? route('warehouse-stock-document', $documentWisId) : '';

						$reqlotNum = $valArr->req_lot_no;
						$workOrdId = $valArr->work_order_id;
						$itemTypeId = $valArr->item_type_id;
						$TotalInspSize = "";
						if($itemTypeId !='1') 
						{
							$inspectionKey = $workOrdId . '|' . $reqlotNum;
							$inspectionRow = isset($inspectionSummary) ? $inspectionSummary->get($inspectionKey) : null;
							$TotalInspSize = ($inspectionRow && $inspectionRow->total_size > 0) ? $inspectionRow->total_size : 'Not Available.';
							if ($TotalInspSize == 'Not Available.') {
								$TotalInspSize = \App\Http\Controllers\CommonController::calculateTotalInspectionSize($workOrdId, $reqlotNum);
							}
						}
						$inspectionKey = $workOrdId . '|' . $reqlotNum;
						$inspectionRow = isset($inspectionSummary) ? $inspectionSummary->get($inspectionKey) : null;
						$InspDate = ($inspectionRow && $inspectionRow->first_created) ? date('Y-m-d', strtotime($inspectionRow->first_created)) : 'Not Available';
						if ($InspDate == 'Not Available') {
							$InspDate = \App\Http\Controllers\CommonController::getInspectionSummary($workOrdId, $reqlotNum);
						}
						$ItemTypeName = isset($valArr->ItemType->item_type_name) ? $valArr->ItemType->item_type_name : '';
					?>
						<tr>
							<td class="success"><?=$valArr['WorkOrder']->process_type;?><?=$valArr['WorkOrder']->process_sl_no;?>-{{ $valArr->id }}</td>
							<td class="success"><?=$valArr->created;?></td>
							<td class="success"><?=$valArr['Item']->item_name;?></td>
							<td class="success"><?=$inspTakaNumberStr;?> </td>
						
							
							<td class="success">
								<?= ($invoiceNumbersStr == '') ? '<a href="javascript:void(0);" class="toggle-nextsub-details">' . $valArr->quantity . ' Kg ' . $ItemTypeName . '</a>' : $valArr->quantity . ' Kg ' . $ItemTypeName; ?>
							</td>
							
							<td class="success"><?=isset($valArr['WorkOrder']['WorkMachine']->name) ? $valArr['WorkOrder']['WorkMachine']->name : '';?></td>
							<td class="success"><?=isset($valArr['WorkOrder']['WorkMaster']->name) ? htmlspecialchars(lotFirstName(!empty($valArr['WorkOrder']['WorkMaster']->nick_name) ? $valArr['WorkOrder']['WorkMaster']->nick_name : $valArr['WorkOrder']['WorkMaster']->name)) : '';?></td>
							<td class="success"><?=$TotalInspSize;?> <?php if(!empty($itemTypeId !='1')) { ?>Meter <?php } ?> </td>
							<td class="success"><?= htmlspecialchars($vendorNamesStr); ?></td>
							<td class="success">
								<a href="javascript:void(0);" class="show-stock-document" data-wis-id="<?= htmlspecialchars($documentWisId); ?>" data-document-url="<?= htmlspecialchars($documentUrl); ?>" data-invoice-number="<?= htmlspecialchars($invoiceNumbersStr); ?>"><?=$invoiceNumbersStr;?></a>
							</td>
							<td class="success"><?=$InspDate;?></td>
						</tr>

						<tr class="sub-nextdetails-row hidden">
							<td colspan="13">
								<table class="table table-bordered table-striped table-condensed">
									<tr>
										<th class="alert-success">Work Order No.</th>
										<th class="alert-success">Genrate Date</th>
										<th class="alert-success">Item Name</th>
										<th class="alert-success">Size</th>
										<th class="alert-success">Machine</th>
										<th class="alert-success">Master</th>
										<th class="alert-success">Inspection</th>
										<th class="alert-success">Invoice</th>
										<th class="alert-success">Date</th>
									</tr>
									<?php
									foreach ($valArr['WarehouseOutItem'] as $Outitem)
									{
										$getNextSubWork = isset($nextSubWorkDetailsByOutItem) ? $nextSubWorkDetailsByOutItem->get($Outitem->id, collect()) : collect();
										foreach ($getNextSubWork as $varArr)
										{
											$invoiceNumbers = [];
											$invoiceWisId = null;
											foreach ($varArr['WarehouseOutItem'] as $item) {
												if (isset($item['WarehouseItem']['invoice_number'])) {
													$invoiceNumbers[] = $item['WarehouseItem']['invoice_number'];
												}
												if (!$invoiceWisId && !empty($item->wis_id)) {
													$invoiceWisId = $item->wis_id;
												}
											}
											$invoiceNumbersStr = implode(', ', $invoiceNumbers);
											$documentWisId = !empty($invoiceWisId) ? enc($invoiceWisId) : '';
											$documentUrl = !empty($documentWisId) ? route('warehouse-stock-document', $documentWisId) : '';
											$reqlotNum = $varArr->req_lot_no;
											$workOrdId = $varArr->work_order_id;
											$itemTypeId = $varArr->item_type_id;
											$TotalInspSize = "";
											if($itemTypeId =='1') {
												$inspectionKey = $workOrdId . '|' . $reqlotNum;
												$inspectionRow = isset($inspectionSummary) ? $inspectionSummary->get($inspectionKey) : null;
												$TotalInspSize = ($inspectionRow && $inspectionRow->total_size > 0) ? $inspectionRow->total_size : 'Not Available.';
												if ($TotalInspSize == 'Not Available.') {
													$TotalInspSize = \App\Http\Controllers\CommonController::calculateTotalInspectionSize($workOrdId, $reqlotNum);
												}
											}
											$inspectionKey = $workOrdId . '|' . $reqlotNum;
											$inspectionRow = isset($inspectionSummary) ? $inspectionSummary->get($inspectionKey) : null;
											$InspDate = ($inspectionRow && $inspectionRow->first_created) ? date('Y-m-d', strtotime($inspectionRow->first_created)) : 'Not Available';
											if ($InspDate == 'Not Available') {
												$InspDate = \App\Http\Controllers\CommonController::getInspectionSummary($workOrdId, $reqlotNum);
											}
											$ItemTypeName = isset($varArr->ItemType->item_type_name) ? $varArr->ItemType->item_type_name : '';
									?>
										<tr>
											<td class="success"><?=$varArr['WorkOrder']->process_type;?><?=$varArr['WorkOrder']->process_sl_no;?>-{{ $varArr->id }}</td>
											<td class="success"><?=$varArr->created;?></td>
											<td class="success"><?=$varArr['Item']->item_name;?></td>
											<td class="success"><?=$varArr->quantity;?> Kg<?=$ItemTypeName;?></td>
											<td class="success"><?=isset($varArr['WorkOrder']['WorkMachine']->name) ? $varArr['WorkOrder']['WorkMachine']->name : '';?></td>
											<td class="success"><?=isset($varArr['WorkOrder']['WorkMaster']->name) ? htmlspecialchars(lotFirstName(!empty($varArr['WorkOrder']['WorkMaster']->nick_name) ? $varArr['WorkOrder']['WorkMaster']->nick_name : $varArr['WorkOrder']['WorkMaster']->name)) : '';?></td>
											<td class="success"><?=$TotalInspSize;?> <?php if(!empty($itemTypeId =='1')) { ?>Meter <?php } ?> </td>
											<td class="success">
												<a href="javascript:void(0);" class="show-stock-document" data-wis-id="<?= htmlspecialchars($documentWisId); ?>" data-document-url="<?= htmlspecialchars($documentUrl); ?>" data-invoice-number="<?= htmlspecialchars($invoiceNumbersStr); ?>"><?=$invoiceNumbersStr;?></a>
											</td>
											<td class="success"><?=$InspDate;?></td>
										</tr>
									<?php } ?>
									<?php } ?>
								</table>
							</td>
						</tr>
					<?php } ?>
				</table>
			</td>
		</tr>
		<?php } ?>
	<?php } ?>
</table>
