<?php
use \App\Http\Controllers\CommonController;
$current_page = isset($_GET['page']) ? $_GET['page'] : 1;
$userId 		= Auth::id();
// echo "<pre>"; print_r($dataMas); exit;

?>
<!DOCTYPE html>
<html lang="en">
<head>@include('common.head')
 
</head><body class="hold-transition sidebar-mini">
<!-- Site wrapper -->
<div class="wrapper"> @include('common.header')
  <div class="content-wrapper">
    <section class="content">
      <div class="row">
        <div class="col-sm-12"> {!! CommonController::display_message('message') !!}
          <div class="panel panel-bd lobidrag">
            <div class="panel-heading">
              <div class="btn-group" id="buttonexport"><a href="javascript:void(0);">
                <h4>Work Order List</h4>
                </a></div>
            </div>
            <div class="panel-body">
			
			<div class="workorder-filter-wrap">
				<div class="workorder-filter-box">
					<form action="{{ route('show-workorders') }}" method="GET" role="search" autocomplete="off">

						<div class="workorder-filter-row">
							<div>
								<div class="input-group input-group-sm">
									<span class="input-group-addon"><i class="fa fa-user"></i></span>
									<input type="text" class="form-control" name="cus_search" id="cus_search" value="{{ $cusSearch }}" autofocus="autofocus" placeholder="Customer Name">
								</div>
								<input type="hidden" id="individual_id" name="individual_id" value="{{ $individualId }}">
							</div>

							<div>
								<div class="input-group input-group-sm">
									<span class="input-group-addon"><i class="fa fa-cube"></i></span>
									<input type="text" class="form-control" name="item_search" id="item_search" value="{{ $itemSearch }}" placeholder="Item Name">
								</div>
							</div>

							<div>
								<div class="input-group input-group-sm">
									<span class="input-group-addon"><i class="fa fa-file-text-o"></i></span>
									<input type="text" class="form-control" name="ordNumSearch" id="ordNumSearch" value="{{ $ordNumSearch }}" placeholder="S.O Number">
								</div>
							</div>

							<div>
								<div class="dropdown">
									<button class="btn btn-info btn-sm btn-block dropdown-toggle process-filter-btn" type="button" id="processDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
										<i class="fa fa-cogs"></i> Select Process
										<span class="caret pull-right" style="margin-top:8px;"></span>
									</button>

									<ul class="dropdown-menu process-filter-menu" aria-labelledby="processDropdown">
										<?php
											$search_process_id = is_array($search_process_id) ? array_map('intval', $search_process_id) : [];
											foreach ($processI as $process) {
										?>
											<li role="presentation">
												<label>
													<input type="checkbox" name="search_process_id[]" value="<?= $process->id; ?>" <?= (!empty($search_process_id) && in_array($process->id, $search_process_id)) ? 'checked' : ''; ?>>
													<span class="text-primary"><?= e($process->process_name); ?></span>
												</label>
											</li>
										<?php } ?>
									</ul>
								</div>
							</div>

							<div>
								<select class="form-control input-sm" name="work_status" id="search_work_status">
									<option value="1" {{ $workStatus == '1' ? 'selected' : '' }}>Pending</option>
									<option value="2" {{ $workStatus == '2' ? 'selected' : '' }}>Completed</option>
									<option value="0" {{ $workStatus == '0' ? 'selected' : '' }}>All</option>
								</select>
							</div>

							<div>
								<select class="form-control input-sm" name="process_status" id="process_status_main">
									<option value="0" {{ $proceStatus == '0' ? 'selected' : '' }}>All Process Status</option>
									<option value="1" {{ $proceStatus == '1' ? 'selected' : '' }}>Pending</option>
								</select>
							</div>

							<div>
								<select class="form-control input-sm" name="year_record">
									<?php for ($i = 0; $i < 5; $i++) {
										$year = date('Y') - $i;
									?>
										<option value="<?= $year ?>" <?= ($yearRecord ?? '') == $year ? 'selected' : '' ?>>
											<?= $year . '-' . ($year + 1) ?>
										</option>
									<?php } ?>
								</select>
							</div>
						</div>

						<div class="workorder-filter-row">
							<div>
								<div class="input-group input-group-sm">
									<span class="input-group-addon"><i class="fa fa-tag"></i></span>
									<input type="text" class="form-control" name="recLotNumSearch" id="recLotNumSearch" value="{{ $recLotNumSerch }}" placeholder="Rec. Coating Lot">
								</div>
							</div>

							<div>
								<div class="input-group input-group-sm">
									<span class="input-group-addon"><i class="fa fa-calendar"></i></span>
									<input type="text" class="form-control datepicker" name="from_date" id="from_date" placeholder="From Date" value="<?= $fromDate; ?>">
								</div>
							</div>

							<div>
								<div class="input-group input-group-sm">
									<span class="input-group-addon"><i class="fa fa-calendar"></i></span>
									<input type="text" class="form-control datepicker" name="to_date" id="to_date" placeholder="To Date" value="<?= $toDate; ?>">
								</div>
							</div>

							<div>
								<div class="input-group input-group-sm">
									<span class="input-group-addon"><i class="fa fa-barcode"></i></span>
									<input type="text" class="form-control" name="LotNumSearch" id="LotNumSearch" value="{{ $LotNumSearch }}" placeholder="Lot Number">
								</div>
							</div>

							<div>
								<div class="input-group input-group-sm">
									<span class="input-group-addon"><i class="fa fa-tint"></i></span>
									<input type="text" class="form-control" name="colorSearch" id="colorSearch" value="{{ $colorSearch }}" placeholder="Color">
								</div>
							</div>

							 

							<div class="filter-action-buttons">
								<div class="btn-group btn-group-sm" role="group">
									<button type="submit" name="sbtSearch" class="btn btn-success" value="Search">
										<i class="fa fa-search"></i> Search
									</button>
									<button type="submit" name="sbtSearch" class="btn btn-primary" value="ExportToExcel">
										<i class="fa fa-file-excel-o"></i> Excel
									</button>
									<button type="submit" name="sbtSearch" class="btn btn-danger" value="ExportToPdf">
										<i class="fa fa-file-pdf-o"></i> PDF
									</button>
								</div>
							</div>
						</div>

					</form>
				</div>
			</div>
		
			 <div class="table-responsive workorder-table-wrap">

                <div class="row workorder-action-bar">
					<div class="col-sm-4">
						<button type="button" class="btn btn-primary btn-sm" id="viewTotalsBtn" data-url="{{ url('/workorders/totals?' . http_build_query(request()->except('page'))) }}"><i class="fa fa-calculator"></i> View Total Meter</button>
						
						<div id="pageLoadTimeBox" style="
    display:inline-flex;
    align-items:center;
    gap:6px;
    margin-left:10px;
    padding:6px 12px;
    background:#eef7ff;
    border:1px solid #bcdffb;
    border-radius:20px;
    font-size:13px;
    font-weight:500;
    color:#2c3e50;
    box-shadow:0 2px 6px rgba(0,0,0,.08);
">
    <span style="font-size:14px;">⏱️</span>
    <span>Load Time:</span>
    <span id="pageLoadTimeText" style="font-weight:700; color:#007bff;">Calculating...</span>
</div>
					</div>

					<div class="col-sm-8 text-right">
					<?php if (in_array($userId, [1, 7, 18])) { ?>
						<a href="{{ route('show-dyed-workorders') }}" target="_blank" rel="noopener noreferrer" class="btn btn-info btn-sm"><i class="fa fa-bell"></i> Dyeing Notifications</a>
					<?php } ?>
					<?php if (in_array($userId, [1, 10])) { ?>
						<a href="{{ route('genrate-dyed-plannings') }}" target="_blank" rel="noopener noreferrer" class="btn btn-primary btn-sm"><i class="fa fa-calendar"></i> Dyeing Plannings</a>
						<a href="{{ route('dyeing-planning.index') }}" target="_blank" rel="noopener noreferrer" class="btn btn-success btn-sm"><i class="fa fa-list"></i> Planning List</a>
					<?php } ?>
					</div>

                </div>

                <table id="dataTableExample1" class="table table-bordered table-striped table-hover table-condensed workorder-list-table">
                  <thead>
                    <tr class="info">
                      <th style="width:5%">W.O.No.</th>
                      <th style="width:6%">S.O No.</th>
                      <th style="width:10%">Item Name</th>
                      <th style="width:9%">Customer</th>
                      <th style="width:9%">Process</th>
                      <th style="width:4%">Priority</th>
                      <th style="width:3%">Cut</th>
                      <th style="width:3%">Pcs</th>
                      <th style="width:5%">Meter</th>
                      <th style="width:16%">Requirement</th>
                      <th style="width:10%">Status</th>
                      <th style="width:11%">Print</th>
                      <th style="width:9%">Action</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php

					foreach ($dataWI as $data)
					{
						// echo "<pre>"; print_r($data['	'][0]);  // exit;
						$printPosition 						= $data->print_position;
						$processType 						= $data->process_type;
						$proTypeId       					= $data->process_type_id;
						$WorkRequireReqAccepted   			= $data->is_work_require_request_accepted;
						$ChildId   							= $data->child_work_order_id;
						$getChildLot 						= CommonController::getChildLotNumber($ChildId);
						$totalReturnItemQty 				= array_sum(array_column($data['DepartmentReturnRequest']->toArray(), 'item_qty'));

						$IsReopend 							= $data->re_opend_by;
						$WOItem 							= $data['WorkOrderItem'];
						$printJob 							= collect($WOItem)->pluck('print_job')->filter()->first();
						$priority 							= collect($WOItem)->pluck('order_item_priority')->filter()->first();
						$unitTypeId 						= $data->unit_type_id;

						$Id   								= $data->work_order_id;
						$woId   							= $data->work_order_id;

						$firstWOI 							= $data['WorkOrderItem']->first();
						$SaleOrderDate 						= optional($firstWOI->SaleOrder)->sale_order_date ?? null;
						$JobWorkDate   						= optional($firstWOI->JobWork)->job_work_date ?? null;

						$allotedStock 						= CommonController::WorkProcessItemAllotedStock($Id);
						$totChildWork 						= CommonController::getTotalChildWork($Id);

						$quantity       					= $data->quantity;
						$masterIndId     					= $data->master_ind_id;
						$machineId       					= $data->machine_id;
						$currentMachineName 				= optional($data->WorkMachine)->name;
						$outputQuantity   					= $data->output_quantity;
						$outputProcess     					= $data->output_process;
						$endProcessEmpId   					= $data->machine_id;
						$inspWorkStatusProcess     			= $data->insp_status;
						$WorkStatusProcess       			= $data->work_status;
						$isWarehouseAccepted     			= $data->is_warehouse_accepted;
						$work_req_send_by       			= $data->work_req_send_by;
						$WorkRequireReqAccepted   			= $data->is_work_require_request_accepted;
						$IsGatePassGenrated         		= $data->is_gatepass_genrated_by_warehouse;
						$isItemReceivedFromWarehouse     	= $data->is_item_received_from_warehouse;
						$GatePassGenratedBy         		= $data['GatepassGenratedByWarehouseUser'] ? $data['GatepassGenratedByWarehouseUser']->name : 'N/A';
						$ReqSendBy               			= $data['WorkReqSend'] ? $data['WorkReqSend']->name : 'N/A';
						$internalName 						= $data['Item']->internal_item_name ?? null;
						$processName             			= $data['ProcessType']->process_name;

						$wprIdChk 	= $data['WorkProcessRequirement']->first()->id ?? null;
						$totDays  	= daysFromNowCount($data->created);
						$blinkMsg 	= '';
						$msgColor  	= '';
						$thresholds = [
							1 => ['blue' => 2, 'red' => 3, 'name' => 'Warping'],
							2 => ['blue' => 8, 'red' => 9, 'name' => 'Weaving'],
							3 => ['blue' => 2, 'red' => 3, 'name' => 'Dyeing'],
							4 => ['blue' => 4, 'red' => 5, 'name' => 'Coating'],
						];

						if (array_key_exists($proTypeId, $thresholds) && $WorkRequireReqAccepted == 'Null' && empty($wprIdChk))
						{
							$process = $thresholds[$proTypeId];
							if ($totDays >= $process['blue'])
							{
								if ($totDays == $process['blue']) {
									$msgColor = 'blue';
									$blinkMsg = "<div class='blink small' style='color:$msgColor; font-weight:bold;'>
										{$process['name']} work order pending for {$totDays} days. Please initiate the process to avoid escalation.
									</div>";
								} elseif ($totDays >= $process['red']) {
									$msgColor = 'red';
									$blinkMsg = "<div class='blink small' style='color:$msgColor; font-weight:bold;'>
										{$process['name']} work order pending for {$totDays} days. Please initiate the work at the earliest.
									</div>";
								}
							}
						}

						$formContent = '';
						$formContent .= '<form method="post" name="FrmReceivedStock" action="' . route('accept_item_for_work') . '" class="form-horizontal">';
						$formContent .= '<input type="hidden" name="_token" value="' . csrf_token() . '">';
						$formContent .= '<table class="table table-bordered">';
						$formContent .= '<tr><th>Taka Number</th><th>Alloted</th><th>Received</th></tr>';

						if (!empty($data['WarehouseOutItem']))
						{
							foreach ($data['WarehouseOutItem'] as $valRow) {
								$inspTaka = isset($valRow->insp_taka_number) ? htmlspecialchars($valRow->insp_taka_number, ENT_QUOTES, 'UTF-8') : '';
								$rowId    = isset($valRow->id) ? htmlspecialchars($valRow->id, ENT_QUOTES, 'UTF-8') : '';
								$itemQty  = isset($valRow->item_qty) ? htmlspecialchars($valRow->item_qty, ENT_QUOTES, 'UTF-8') : '';

								$formContent .= '<tr>';
								$formContent .= '<td><p class="form-control">' . $inspTaka . '</p>';
								$formContent .= '<input type="hidden" name="WarehouseOutItemId[]" id="WarehouseOutItemId' . $rowId . '" value="' . $rowId . '"></td>';

								$formContent .= '<td><input type="number" name="alloted_qty[]" readonly class="form-control" value="' . $itemQty . '"></td>';

								$formContent .= '<td><input type="number" name="received_qty[]" class="form-control" id="received_qty' . $rowId . '" value="' . $itemQty . '"></td>';

								$formContent .= '</tr>';
							}
						}

						$formContent .= '</table>';
						$formContent .= '<input type="hidden" name="work_order_Id" id="work_order_Id" value="' . htmlspecialchars($Id, ENT_QUOTES, 'UTF-8') . '">';
						$formContent .= '<div class="modal-footer"><button type="submit" class="btn btn-success pull-right">Accept Stock Items</button></div>';
						$formContent .= '</form>';
						// ---------- end build ----------

						$formContentEscaped = htmlspecialchars($formContent, ENT_QUOTES, 'UTF-8');

						$WOItems         	= collect($data['WorkOrderItem'] ?? []);
						$jobWorkNumbers  	= [];
						$saleOrderNumbers 	= [];
						$customerNames   	= [];
						$detailLines     	= [];
						$totalMeter      	= 0;
						$saleOrdIds      	= [];
						$poRemarksLines   	= [];

						foreach ($WOItems as $rowArr)
						{
							$totalMeter += (float) data_get($rowArr, 'meter', 0);

							$saleOrderId = data_get($rowArr, 'SaleOrder.sale_order_id');
							if (!empty($saleOrderId) && !in_array($saleOrderId, $saleOrdIds, true)) {
								$saleOrdIds[] = $saleOrderId;
							}

							$custName = CommonController::getIndividualName(data_get($rowArr, 'customer_id'));
							if (!empty($custName) && !in_array($custName, $customerNames, true)) {
								$customerNames[] = $custName;
							}

							if ($processType == 'JOB') {
								$jobWorkId = data_get($rowArr, 'job_work_id');
								$dataSO = CommonController::getJobWorkOrd($jobWorkId);
								$jobWorkNumber = $dataSO ? $dataSO->job_work_number : null;

								if (!empty($jobWorkNumber) && !in_array($jobWorkNumber, $jobWorkNumbers, true)) {
									$jobWorkNumbers[] = $jobWorkNumber;
								}
							} else {
								$saleOrderNumber = data_get($rowArr, 'SaleOrder.sale_order_number');

								if (!empty($saleOrderNumber) && !in_array($saleOrderNumber, $saleOrderNumbers, true)) {
									$saleOrderNumbers[] = $saleOrderNumber;
								}
							}

							$parts = [];

							if (!empty(data_get($rowArr, 'dyeing_color'))) {
								$parts[] = '<small class="text-success"><strong>' . htmlspecialchars(data_get($rowArr, 'dyeing_color')) . '</strong></small>';
							}

							if (!empty(data_get($rowArr, 'coated_pvc'))) {
								$parts[] = '<small class="text-info">' . htmlspecialchars(data_get($rowArr, 'coated_pvc')) . '</small>';
							}

							if (!empty(data_get($rowArr, 'extra_job'))) {
								$parts[] = '<small class="text-warning">' . htmlspecialchars(data_get($rowArr, 'extra_job')) . '</small>';
							}

							if (!empty(data_get($rowArr, 'print_job'))) {
								$parts[] = '<small class="text-danger">' . htmlspecialchars(data_get($rowArr, 'print_job')) . '</small>';
							}

							$remarksRow = data_get($rowArr, 'SaleOrderItem.remarks', '');
							if (!empty($remarksRow)) {
								$parts[] = '<small class="text-primary"><strong class="text-danger">Remarks - </strong>' . htmlspecialchars($remarksRow) . '</small>';
							}

							if (!empty($parts)) {
								$detailLines[] = implode('<br>', $parts);
							}

							$dlvrClearedReason = data_get($rowArr, 'SaleOrderItem.dlvr_cleared_reason', '');
							$saleOrderNumberForPo = data_get($rowArr, 'SaleOrder.sale_order_number', 'Sale Order Number Not Found');

							if (!empty($dlvrClearedReason))
							{
								$poRemarksLines[] = '<small class="text-danger" style="font-weight:bold;">PO - ' . htmlspecialchars($saleOrderNumberForPo, ENT_QUOTES, 'UTF-8') . ' is ' . htmlspecialchars($dlvrClearedReason, ENT_QUOTES, 'UTF-8') . '</small>';

								$poRemarksLines[] = '<small class="text-primary" style="font-weight:bold;">This PO has already been closed, so items cannot be dispatched against it. If you wish to manufacture the same material and dispatch it against another PO, you may proceed with this work order. Otherwise, please close this work order.</small>';

							}

						}

					?>
                    <tr id="Mid{{ $Id }}">

					<td>
						<?= htmlspecialchars($data->process_type); ?>
						<?= htmlspecialchars($data->process_sl_no); ?>
						<?= htmlspecialchars($Id); ?>
						<br>
						<?php $created = date("d-m-Y", strtotime($data->created)); ?>
						{!! daysFromNow($data->created) !!}
					</td>

					<td>
						<?php if ($processType == 'JOB') { ?>
							<span class="btn btn-info btn-xs">
								<?= date("d-m-Y", strtotime($JobWorkDate)); ?>
							</span>

							<?php foreach ($jobWorkNumbers as $jobWorkNumber) { ?>
								<p><?= htmlspecialchars($jobWorkNumber); ?></p>
							<?php } ?>

						<?php } else { ?>
							<span class="btn btn-info btn-xs">
								<?= date("d-m-Y", strtotime($SaleOrderDate)); ?>
							</span>

							<?php foreach ($saleOrderNumbers as $saleOrderNumber) { ?>
								<p><?= htmlspecialchars($saleOrderNumber); ?></p>
							<?php } ?>
						<?php } ?>
					</td>

					<td>
						<div style="font-weight: bold; font-size: 14px; color: #0d6efd;">
							{{ $data->item_name }}
						</div>
						<hr style="margin: 4px 0;" />
						<div style="color: gray;">
							<small>({{ $internalName }})</small>
						</div>
					</td>

					<td>
						<?php foreach ($customerNames as $custName) { ?>
							<p><?= mb_strimwidth($custName, 0, 10, ''); ?></p>
						<?php } ?>
					</td>

					<td>
						<?= htmlspecialchars($processName); ?>
						<p></p>

						<?php foreach ($detailLines as $detailLine) { ?>
							<?= $detailLine; ?>
							<br>
						<?php } ?>
					</td>

                      <td><?=htmlspecialchars($priority);?>
                        <?php if (isset($getChildLot[0]) && !empty($getChildLot[0])) { ?>
                          <hr style="border:1px solid #000; margin:6px 0;">
                          <strong style="font-size:12px; color:#444;">Lot</strong>
                          <?php foreach ($getChildLot as $childArr) { ?>
                          <p style="margin:2px 0; font-size:12px;"> <?php echo htmlspecialchars($childArr->dyeing_lot_number); ?> </p>
                          <?php } ?>
                        <?php } ?>
                      </td>
                      <td><?= $data->cut; ?></td>
                      <td><?= $data->pcs; ?></td>
                      <td><?=$totalMeter;?></td>

                      <td>

						<!-- 1) Request Denied -->
                        <?php if (!empty($work_req_send_by) && $WorkRequireReqAccepted == 'No') { ?>
                        <p>Request Denied By Warehouse</p>
                        <?php } ?>
                        <!-- 2) Requisition Sent -->
                        <?php if (!empty($work_req_send_by) && $WorkRequireReqAccepted == 'Null') { ?>
                        <p>Requisition Sent To Warehouse By <?php echo htmlspecialchars($ReqSendBy, ENT_QUOTES, 'UTF-8'); ?></p>
                        <?php } ?>
                        <!-- 3) Request Accepted -->
                        <?php if ($WorkRequireReqAccepted == 'Yes') { ?>
                        <p>Request Accepted By Warehouse</p>
                        <?php } ?>
                        <!-- 4) Gatepass info -->
                        <?php if ($WorkRequireReqAccepted == 'Yes' && $IsGatePassGenrated == 'Yes') { ?>
                        <p>Gatepass Generated By <?php echo htmlspecialchars($GatePassGenratedBy, ENT_QUOTES, 'UTF-8'); ?></p>
                        <br />
                        <?php } ?>
                        <!-- 5) Accept button (if not received) -->
                        <?php if ($WorkRequireReqAccepted == 'Yes' && $IsGatePassGenrated == 'Yes' && $isItemReceivedFromWarehouse == 'No') { ?>
                        <button type="button" class="btn btn-success open-modal" data-form-content="<?php echo htmlspecialchars($formContent, ENT_QUOTES, 'UTF-8'); ?>"> Accept </button>
                        <?php } ?>
                        <!-- 6) PO cleared reasons -->
                        <?php foreach ($poRemarksLines as $varRemrkline) { ?>
							<?= $varRemrkline; ?>
						<?php } ?>

                        <!-- 7) Requisition / Printing / Request flows -->
                        <?php if ($inspWorkStatusProcess == 'Pending') { ?>
                        <?php if (empty($printJob) || $proTypeId < 4) { ?>
                        <p> <a href="<?php echo route('start-requisition-process', base64_encode($Id)); ?>" class="btn btn-success btn-xs"> <i class="fa fa-paper-plane"></i> Request </a> </p>
                        <?php } else { ?>
                        <?php if ($printPosition == 'none') { ?>
                        <form method="POST" action="<?php echo route('decide-printing-position'); ?>">
						  <input type="hidden" name="_token" value="<?php echo csrf_token(); ?>">
						  <input type="hidden" name="work_order_id" value="<?php echo htmlspecialchars($Id, ENT_QUOTES, 'UTF-8'); ?>">

						  <div class="row">
							<div class="col-xs-12 col-sm-6 col-sm-offset-3 col-md-4 col-md-offset-4">
							  <button type="submit" name="print_position" value="before"
									  class="btn btn-primary btn-block btn-sm" >
								<i class="fa fa-print" aria-hidden="true"></i>&nbsp;<strong>Print Before Coating</strong>
							  </button>

							  <hr />

							  <button type="submit" name="print_position" value="after"
									  class="btn btn-warning btn-block btn-sm" >
								<i class="fa fa-print" aria-hidden="true"></i>&nbsp;<strong>Print After Coating</strong>
							  </button>
							</div>
						  </div>
						</form>

                        <?php } elseif ($printPosition == 'before') { ?>
                        <span class="btn btn-primary btn-xs">Printing <?php echo ucfirst($printPosition); ?> Coating.</span>
                        <hr style="margin: 4px 0;" />
                        <?php /* ?> <p><a href="<?php echo route('start-requisition-for-printing-process', [base64_encode($Id), 'dp']); ?>" class="btn btn-success btn-xs"> <i class="fa fa-paper-plane"></i> Send Request For Printing </a> </p><?php */ ?>
                        <p> <a href="<?php echo route('start-requisition-process', base64_encode($Id)); ?>" class="btn btn-success btn-xs"> <i class="fa fa-paper-plane"></i> Request Item for coating </a> </p>
                        <?php } elseif ($printPosition == 'after' && $proTypeId != '7') { ?>
                        <p><span class="label label-info"><i class="fa fa-forward"></i> Printing will happen After Coating</span></p>
                        <p> <a href="<?php echo route('start-requisition-process', base64_encode($Id)); ?>" class="btn btn-success btn-xs"> <i class="fa fa-paper-plane"></i> Request Item for coating </a> </p>
                        <?php } elseif ($printPosition == 'after' && $proTypeId == '7') { ?>
                        <a href="<?php echo route('start-requisition-for-printing-process', [base64_encode($Id), 'cp']); ?>" class="btn btn-success btn-xs"> <i class="fa fa-paper-plane"></i> Send Request For Printing </a>
                        <?php } ?>
                        <?php } // end else printJob condition ?>
                        <?php } // end inspWorkStatusProcess check ?>
                        <!-- 8) Alloted stock small table -->
                        <?php if (!empty($allotedStock[0])) { ?>
                        <small>
                        <table class="table table-bordered">
                          <tr>
                            <td>Requested</td>
                            <td>Received</td>
                          </tr>
                          <?php foreach ($allotedStock as $tableRow) { ?>
                          <tr>
                            <td><?php echo htmlspecialchars($tableRow['RequestQTY'], ENT_QUOTES, 'UTF-8'); ?> <?php echo htmlspecialchars($tableRow['unitTName'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars(($tableRow['AllotedQTY'] - $totalReturnItemQty), ENT_QUOTES, 'UTF-8'); ?> <?php echo htmlspecialchars($tableRow['unitTName'], ENT_QUOTES, 'UTF-8'); ?></td>
                          </tr>
                          <?php } ?>
                        </table>
                        </small>
                        <?php } ?>
                        <!-- 9) Blink message and Reason button -->
                        <?php if ($inspWorkStatusProcess === 'Pending' && !empty($blinkMsg)) { ?>
                        <?php echo $blinkMsg; ?>
                        <button type="button" class="btn btn-info btn-xs" onClick="openReasonModal(<?php echo (int)$Id; ?>)"> Reason </button>
                        <?php } ?>
                      </td>

                        <td>
							<strong><?= e($inspWorkStatusProcess) ?></strong>

							<p>
								<small>
									<?php if ($proTypeId > 2 && !empty($currentMachineName)) { ?>
										<small><?= e($currentMachineName); ?></small>
									<?php } ?>

									<?php if ($proTypeId < 3 && !empty($currentMachineName)) { ?>
										<span class="machine-name" id="beam-machine-name-<?= e($woId) ?>">
											<small><?= e($currentMachineName); ?></small>
										</span>

										<button type="button" class="btn btn-xs btn-primary btn-edit-machine" data-woid="<?= e($woId) ?>" style="margin-left:8px;">
											<span class="glyphicon glyphicon-edit"></span>
										</button>

										<div class="machine-edit" id="beam-machine-edit-<?= e($woId) ?>" style="display:none; margin-top:8px;">
											<select class="form-control machine-select" id="beam-machine-select-<?= e($woId) ?>">
												<option value="">-- Select Machine --</option>
												<?php foreach ($machine as $m) { ?>
													<option value="<?= e($m->id) ?>" <?= ($machineId == $m->id) ? 'selected' : '' ?>>
														<?= e($m->name) ?>
													</option>
												<?php } ?>
											</select>

											<div style="margin-top:5px;">
												<button type="button" class="btn btn-xs btn-success btn-save-machine" data-woid="<?= e($woId) ?>">
													<span class="glyphicon glyphicon-floppy-disk"></span> Save
												</button>

												<button type="button" class="btn btn-xs btn-default btn-cancel-machine" data-woid="<?= e($woId) ?>">
													Cancel
												</button>
											</div>

											<div class="machine-edit-error text-danger" id="beam-machine-edit-error-<?= e($woId) ?>" style="display:none; margin-top:6px;"></div>
										</div>
									<?php } ?>
								</small>
							</p>

							<small>
								<table class="table table-bordered">
									<tr>
										<?php if ($proTypeId > 2) { ?>
											<td>Dyed Lot</td>
										<?php } ?>
									</tr>

									<?php
									$warehouseOutItemsByReq = collect($data['WarehouseOutItem'] ?? [])->groupBy('work_pro_req_id');
									foreach ($data['WorkProcessRequirement'] as $dataRow) {
										$lotNum = $dataRow->req_lot_no;
										$lab_req_status = $dataRow->lab_req_status;
										$workOrderId = $dataRow->work_order_id;
										$workProReqId = $dataRow->id;

										if ($proTypeId == 4) {
											$machineIDd = $dataRow->dyeing_machine_id;
										} else {
											$machineIDd = $dataRow->dyeing_machine_id;
										}

										// find saved machine name (if any)
										$savedMachineName = '';
										if (!empty($machineIDd)) {
											foreach ($machine as $m) {
												if ($m->id == $machineIDd) {
													$savedMachineName = $m->name;
													break;
												}
											}
										}

										$returnItemsForReq = $warehouseOutItemsByReq->get($workProReqId, collect());
										$isReturnStock = ($returnItemsForReq->isEmpty() || $returnItemsForReq->contains('is_item_return_whouse', '0')) ? 1 : 0;
									?>

										<!-- Warping and Weaving condition -->
										<?php if (!empty($isReturnStock) && $proTypeId < 3) { ?>
											<tr>
												<td id="beamWprCell<?= e($dataRow->id) ?>">
													<?= e($dataRow->id) ?>

													<?php if ($dataRow->process_type_id == '2' && empty($dataRow->WorkProcessRequirementChangeHistory)) { ?>
														<button type="button"
																class="btn btn-warning btn-xs beam-return-btn"
																data-wpr-id="<?= e($dataRow->id) ?>"
																data-work-order-id="<?= e($Id) ?>"
																title="Beam/Yarn Return"
																data-toggle="tooltip">
															<i class="fa fa-undo"></i>
														</button>
													<?php } ?>
												</td>
											</tr>
										<?php } ?>

										<!-- Dyeing and Coating condition -->
										<?php if (!empty($isReturnStock) && $proTypeId > 2) { ?>
											<tr>
												<td id="beam-lotCell<?= e($dataRow->id) ?>">
													<span class="lot-no">
														<strong><?= e($dataRow->req_lot_no) ?></strong>&nbsp;
													</span>

													<?php // if ($dataRow->process_type_id == '3' && empty($dataRow['WorkProcessRequirementChangeHistory'])) { ?>
													<?php if (in_array($dataRow->process_type_id, [3, 4]) && is_null($dataRow->WorkProcessRequirementChangeHistory)) { ?>
														<button type="button" class="btn btn-warning mini-btn btn-xs open-lot-return-modal"
																data-form-content='{"id":"<?= e($dataRow->id) ?>","req_lot_no":"<?= e($dataRow->req_lot_no) ?>","work_order_id":"<?= e($Id) ?>"}'
																title="Item Return" data-toggle="tooltip"
																onclick="GetLotReturnItems('<?= e($dataRow->id) ?>', '<?= e($dataRow->req_lot_no) ?>', '<?= e($Id) ?>', 'returnItemsTable')">
															<i class="fa fa-undo"></i>
														</button>
													<?php } ?>

													&nbsp;

													<?php if ($dataRow->lab_req_status == 'Pending' || $dataRow->lab_req_status == 'Rejected') { ?>
														<button type="button" class="btn btn-info mini-btn btn-xs" data-id="<?= e($dataRow->id) ?>" data-lot="<?= e($dataRow->req_lot_no) ?>" data-wo="<?= e($Id) ?>" onclick="openLabRequestModal(this)">
															<i class="fa fa-flask"></i>
														</button>
													<?php } elseif ($dataRow->lab_req_status == 'Requested') { ?>
														<span class="label label-warning">Request Sent</span>
													<?php } elseif ($dataRow->lab_req_status == 'Approved') { ?>
														<span class="label label-success">Approved</span>
													<?php } ?>

													&nbsp;

													<!-- Edit button -->
													<button type="button" class="btn btn-xs mini-btn btn-primary edit-machine-btn" data-id="<?= e($dataRow->id) ?>" data-woid="<?= e($woId) ?>">
														<i class="fa fa-pencil"></i>
													</button>

													<!-- Machine name -->
													<small class="text-muted">
														<div style="margin-top:4px;">
															<?php
																$hasMachine = !empty($savedMachineName);
																$labelClass = $hasMachine ? 'label-success' : 'label-danger';
																$labelText  = $hasMachine ? $savedMachineName : 'Machine Not Set';
															?>
															<span class="label <?= e($labelClass) ?> machine-display-<?= e($dataRow->id) ?>" title="<?= e($labelText) ?>">
																<i class="fa fa-cog"></i> <?= e($labelText) ?>
															</span>
														</div>
													</small>

													<!-- Inline edit area (hidden by default) -->
													<div class="machine-edit-area" id="beam-machine-edit-<?= e($dataRow->id) ?>" style="display:none; margin-top:6px;">
														<select class="form-control machine-select" id="beam-machine-select-<?= e($dataRow->id) ?>">
															<option value="">Select Machine</option>
															<?php foreach ($machine as $m) {
																$selected = ($dataRow->dyeing_machine_id == $m->id) ? 'selected' : '';
															?>
																<option value="<?= e($m->id) ?>" <?= $selected ?>><?= e($m->name) ?></option>
															<?php } ?>
														</select>

														<div style="margin-top:6px; margin-bottom:10px;">
															<button type="button" class="btn btn-success btn-xs save-machine-btn" data-id="<?= e($dataRow->id) ?>">Save</button>
															<button type="button" class="btn btn-default btn-xs cancel-machine-btn" data-id="<?= e($dataRow->id) ?>">Cancel</button>
															<span class="machine-edit-status text-muted" id="beam-machine-status-<?= e($dataRow->id) ?>" style="margin-left:8px;"></span>
														</div>
													</div>
												</td>
											</tr>
										<?php } ?>

									<?php } // end foreach ?>
								</table>
							</small>
						</td>

                      <td>
						<?php if(!empty($totChildWork)) { ?>
							<div class="label label-success" style="display:inline-block; margin-bottom:5px;">
								<small>Created <?= $totChildWork; ?> WO for next process
							</small></div><br>
						<?php } ?>

                        <?php if($data->process_type == 'V') { ?>
                        <?php  if(!empty($data['WorkInspectionOne']->insp_taka_number)) { ?>
                        <p style="margin-top: 10px;">
                          <?= ($data->process_type != 'W') ? (($data->process_type == 'V') ? 'Beam Num.' : 'Taka Number') : ''; ?>
                          <br/>
                          <?=$data['WorkInspectionOne']->insp_taka_number;?>
                        </p>
                        <?php }   ?>
                       <hr style="margin: 2px 0;" />
						<?php
						if (!empty($data['WarehouseOutItem'])) {
						foreach ($data['WarehouseOutItem'] as $item)
						{
							// echo "<pre>"; print_r($item); exit;
						$itemTypeID = $item->item_type_id;
						if (!empty($item->insp_taka_number) && $itemTypeID > 1) { ?>
                        <p style="margin-top: 10px;">
                          <?= $item->item_type_id == '1' ? 'Yarn Number' : ($data->item_type_id == '2' ? 'Beam Number' : 'Taka Number'); ?>
                          <hr style="margin: 2px 0;" />
                          <?=$item->insp_taka_number; ?>
                        </p>
                        <p>Beam Meter
						<hr style="margin: 2px 0;" />
                          <?=$item['WarehouseItem']->beam_meter; ?>
                        </p>
                        <?php }
								}
							}
						}
						?>
                        <?php
							$itemTypeNum 	= $data->item_type_id;
							$i 			 	= 1;
							$qtySize 		= 0;
							$inspBeamMeter 	= 0;
							foreach ($data['GatePass'] as $gateVal)
							{
								$GPId 			= $gateVal->id;
								$InspId 		= $gateVal->inspection_id;
								$InspComment 	= $gateVal['WorkInspection']->insp_comment;
								$totAvlTaka 	= $availableTakaCounts[$InspId] ?? 0;
								if ($itemTypeNum < '3') {
									$GPTakaNumb = $gateVal->insp_taka_number;
								} else {
									$GPTakaNumb = $gateVal->dyeing_lot_number;
								}
								$GPitemRcv 		= $gateVal->is_item_received_in_warehouse;
								$qtySize 		+= $gateVal->qty_size;
								$inspBeamMeter 	+= $gateVal->insp_beam_meter;
								$butclr = ($GPitemRcv == 'Yes') ? 'info' : 'success';
								echo '<p id="InsGpid' . $GPId . '">';

								echo '<a target="_blank"
										href="' . route('print-workorder-gatepass', base64_encode($GPId)) . '"
										class="btn btn-' . $butclr . ' btn-xs"
										data-toggle="tooltip"
										title="' . $InspComment . '">
										' . $GPTakaNumb . '-GP
									  </a>';

								if ($GPitemRcv == 'No') {
									echo ' <a href="javascript:void(0);" onClick="DelGatePass(' . $GPId . ')" class="btn btn-danger btn-xs"><i class="fa fa-trash-o"></i></a>';
								}
								 
								echo '</p>';
								if ($i % 2 == 0) {
									// echo '<br />';
								}
								$i++;
							}
						?>
                        <?php
							$totalValue = null;
							$unitFeb 	= '';
							if (!empty($qtySize)) {
								$totalValue = $qtySize;
								$unitFeb = ($proTypeId == '1') ? 'Kg Beam' : 'Meter';
							} elseif (!empty($inspBeamMeter)) {
								$totalValue = $inspBeamMeter;
								$unitFeb = 'Meter';
							}
						?>
                        <?php if (!empty($totalValue)) { ?>
                        <div class="mt-2"> Total : <?=$totalValue.' '.$unitFeb; ?>  </div>
                        <?php } ?>
                      </td>

					  <td class="center machine-cell" data-woid="<?= $woId ?>">
                      <?php
						if($proTypeId == 3 && $isItemReceivedFromWarehouse === 'Yes' && $WorkRequireReqAccepted !== 'Yes' && $inspWorkStatusProcess === 'Pending')
						{
						?>
                      <p style="margin-top: 10px;"><a href="javascript:void(0);" onClick="ReActivateInspProcess(<?=$Id;?>)" class="btn btn-success btn-xs">Reactivate Inspection</a></p>
                      <?php
						}
						?>
                      <?php if ($WorkRequireReqAccepted == 'Yes') {  ?>
                      <?php if (empty($masterIndId)) { ?>
                      <?php if ($IsGatePassGenrated == 'Yes' && $isItemReceivedFromWarehouse == 'Yes') {  ?>

						<?php if ($proTypeId > 2) { ?>
						<a href="javascript:void(0);" onClick="StartProcess({{ $Id }})" class="btn btn-success btn-xs">Start Process </a>
						<?php } else { ?>
						<a href="javascript:void(0);" onClick="StartProcessWev({{ $Id }})" class="btn btn-success btn-xs">Start Process</a>
						<?php } ?>

                      <?php } ?>
                      <?php } elseif ($inspWorkStatusProcess == 'Complete') {  ?>
                      <div class="label-custom label label-default"><small>Work Order Closed</small></div>
					  <?php if ($inspWorkStatusProcess === 'Complete' && empty($IsReopend)) { ?>
						<p style="margin-top:10px;">
							<button type="button" class="btn btn-success btn-xs" onclick="ReActivateProcess(<?=$Id;?>);" title="This work order is allowed to be reopened only once.">↺ Re Open</button>
						</p>
					  <?php } ?>

                      <?php } elseif ($inspWorkStatusProcess == 'Pending') { ?>
                      <?php if ($proTypeId == 7) { ?>
                      <a href="javascript:void(0);" onClick="CoatingPrintInspProcess({{ $Id }})" class="btn btn-success btn-xs">Start Inspection</a>
                      <?php } elseif ($proTypeId == 4) { ?>
                      <a href="javascript:void(0);" onClick="CoatingInspProcess({{ $Id }})" class="btn btn-success btn-xs">Start Inspection</a>
                      <?php } elseif ($proTypeId == 3) { ?>
                      <a href="javascript:void(0);" onClick="DyeingInspProcess({{ $Id }})" class="btn btn-success btn-xs">Start Inspection</a>
                      <?php } elseif ($proTypeId == 2) { ?>
                      <a href="javascript:void(0);" onClick="WeavingInspProcess({{ $Id }})" class="btn btn-success btn-xs">Start Inspection</a>
                      <?php } elseif ($proTypeId == 1) { ?>
                      <a href="javascript:void(0);" onClick="InspectionProcess({{ $Id }})" class="btn btn-success btn-xs">Start Inspection</a>
                      <?php } ?>
                      <?php } ?>
                      <?php } ?>

					 <!----
                      <p style="margin-top: 10px;">
						<a target="_blank" href="workorder-details/{{ base64_encode($Id) }}" title="View Work Order Details" class="tooltip-info"><i class="fa fa-eye"></i></a>
					  </p> ---->
						<?php foreach ($saleOrdIds as $saleOrdId) { ?>
							<p style="margin-top: 10px;">
								<a target="_blank"
								   href="<?php echo route('show-saleorder-workorder-details', ['id' => base64_encode($saleOrdId)]); ?>"
								   title="View Work Details"
								   class="label bg-green tooltip-info"
								   style="display:inline-block; margin-right:5px; margin-bottom:5px;">
									<i class="fa fa-eye"></i>
								</a>
							</p>
						<?php } ?>

                      <?php if($proTypeId =='2' && $WorkRequireReqAccepted != 'Yes' && empty($work_req_send_by)) { ?>
                      <a href="javascript:void(0);" onClick="ShiftWorkOrderToWarping({{ $Id }})" class="btn btn-success btn-xs">Shift In Warping</a>
                      <?php } ?>

                      <?php if (empty($data['WorkProcessRequirement'][0])) {  ?>

					    <p style="margin-top: 50px;">
							<a href="javascript:void(0);"
							   onClick="DelWoProcess({{ $Id }})"
							   class="btn btn-success btn btn-xs"
							   title="Delete this record permanently">
							   <i class="fa fa-trash-o"></i>
							</a>
						</p>

                      <?php } ?>

					<?php if ($proTypeId == '3' && $inspWorkStatusProcess == 'Pending') { ?>
						<p style="margin-top: 10px;">
							<a href="javascript:void(0);"
							   onclick="CloseWorkProcess(<?php echo $Id; ?>)"
							   class="btn btn-danger btn-xs"
							   title="Using this button will close the work order. You will not be able to activate this work order again.">
							   Close WO
							</a>
						</p>
					<?php } ?>

                      </td>
                    </tr>
                    <?php } ?>

                    <tr class="center text-center">
                      <td colspan="13"><div class="pagination text-center">
					  <span class="mr-3"> {{ $dataWI->links('vendor.pagination.bootstrap-4') }} </span>
					  <span class="manual-page-input d-flex align-items-center">
                          <label for="manualPageInput" class=" mr-1">Go to page:</label>
                          <input type="number" id="manualPageInput" min="1" max="{{ $dataWI->lastPage() }}" value="{{ $dataWI->currentPage() }}" style="width:70px" class="mr-1">
                          <button class="btn btn-sm btn-success" id="goToPageButton">Go</button>
                          </span> </div></td>
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
  <!-------------------Modal Start-------------------->

  <!-- Reason Modal -->
  <div class="modal fade" id="reasonModal" tabindex="-1" role="dialog" aria-labelledby="reasonModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
      <div class="modal-content">
        <form method="POST" action="{{ route('SetReasonForWorkOrderItem') }}">
          @csrf
          <input type="hidden" name="FId" id="modalFId">
          <div class="modal-header">
            <h3 class="modal-title">Are you ready to provide the reason for not creating the work order yet?</h3>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
          </div>
          <div class="modal-body">
            <h5 align="center">You will not be able to undo this action, and a detailed report will be sent to the <strong>director</strong> for review.</h5>
            <div class="panel panel-primary" style="border-radius: 6px; box-shadow: 0 2px 6px rgba(0,0,0,0.1);">
              <div class="panel-heading" style="font-size: 18px; font-weight: bold; text-align: center;"> Reason History </div>
              <div class="panel-body" style="padding: 20px; background-color: #fafafa;">
                <div class="table-responsive">
                  <table class="table table-bordered table-hover" id="reasonTable" style="background-color: #fff; border-radius: 4px;">
                    <thead style="background-color: #f0f8ff;">
                      <tr style="text-align: center; font-size: 15px;">
                        <th style="width: 60px;">SrNo.</th>
                        <th>Reason</th>
                        <th style="width: 180px;">Date</th>
                      </tr>
                    </thead>
                    <tbody style="font-size: 14px;">
                      <!-- JavaScript will fill this -->
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
            <div class="form-group ">
              <label>Comment</label>
              <input type="text" class="form-control" name="pending_reason" required placeholder="Enter comment">
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-danger" data-dismiss="modal">Close</button>
            <button type="submit" class="btn btn-success">Save</button>
          </div>
        </form>
      </div>
    </div>
  </div>
  <!------------------------------------------------------------->

  <div class="modal fade" id="CoatingInspProcessPop" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <form method="post" action="{{ route('update_coating_inspec_process')}}" class="form-horizontal" onSubmit="disableSubmitButton(this)">
          @csrf
          <div class="modal-header modal-header-primary">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
            <h3 class="modal-title"> <i class="fa fa-plus m-r-5"></i> Coating Inspection Process </h3>
          </div>
          <input type="hidden" name="page" value="<?=htmlspecialchars($current_page); ?>">
          <div class="modal-body">
            <div class="row">
              <div class="col-md-12">
                <fieldset>
                <table class="table table-bordered table-striped table-hover table-condensed">
                  <tr class="warning">
                    <th>Item Name</th>
                    <td><span id="coating_ItemName"></span></td>
                  </tr>
                </table>
                <table class="table table-bordered">
                  <tr> <span id="coating_workRequirement1"></span> </tr>
                </table>
                <table class="table table-bordered table-striped">
                  <thead>
                    <tr class="info">
                      <th colspan="6" class="text-center"><strong>Lot Info & Destination</strong></th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr class="active">
                      <!-- Lot Number -->
                      <th style="width: 15%;">Lot Number <span style="color:red;">*</span></th>
                      <td style="width: 18%;"><input type="number" id="coating_req_lot_no" name="req_lot_no"
								   oninput="fetchWarehouseItemStockCoating(this.value, document.getElementById('coating_ins_work_order_id').value, 'myTableCoated')" class="form-control">
                      </td>
                      <!-- Width -->
                      <th style="width: 15%;">Width <span style="color:red;">*</span></th>
                      <td style="width: 18%;"><input type="text" class="form-control" id="coating_insp_width" value="0" name="insp_width">
                      </td>
                      <!-- GSM -->
                      <th style="width: 15%;">GSM <span style="color:red;">*</span></th>
                      <td style="width: 18%;"><input type="text" class="form-control" id="coating_insp_gsm" value="0" name="insp_gsm">
                      </td>
                    </tr>
					 <tr>
                      <td colspan="10"><span id="coating_workRequirement"></span> </td>
                    </tr>
                  </tbody>
                </table>
                <table class="table table-bordered" id="myTableCoated">
                  <input type="hidden" id="coating_ins_item_id" name="ins_item_id">
                  <input type="hidden" id="coating_ins_work_order_id" name="ins_work_order_id">
				  <input type="hidden" id="machineIdC" name="insp_work_machine_id">
				  <input type="hidden" id="reqProIdsC" name="insp_work_process_req_id">
                  <thead>
                    <tr class="success">
                      <th>Sr.No.</th>
                      <th>G.T.Number</th>
                      <th>Greige Meter</th>
                      <th>Break Meter</th>
                      <th>Output</th>
                      <th>Shrinkage</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr class="table-row"> </tr>
                  </tbody>
                  <tfoot>
                    <tr>
                      <td colspan="2"><strong>T.Input:</strong></td>
                      <td id="toGreigeItemQty">0</td>
                      <td colspan="1"><strong>T.Output:</strong></td>
                      <td id="totalOutput">0</td>
                    </tr>
                  </tfoot>
                </table>
                <table class="table table-bordered">
                  <tr>
                    <td><strong>Comment</strong>
                      <p>Machine 	 : <span id="MachineNameC"></span> </p>
                      <p>Taka Number : <span id="inspTakaNumberC"></span> </p></td>
                    <td><textarea class="form-control" id="coating_inspec_comment" required name="inspec_comment"></textarea></td>
                  </tr>
                  <tr>
                    <td><strong> <span id="coating_processtext"> </span></strong> </td>
                    <td><select name="insp_work_status_process" required id="coating_insp_work_status_process" class="form-control">
                        <option value=""> Select Inspection Process Status</option>
                        <option value="No">Not Complete</option>
                        <option value="Yes">Completed</option>
                      </select>
                    </td>
                  </tr>
                  <tr>
                    <td><strong>Work Status</strong> </td>
                    <td><select name="work_status" required id="work_status_1" onChange='selectWorkStatus(this)' class="form-control">
                        <option value=""> Select Work Status</option>
                        <option value="Completed"> Completed</option>
                        <option value="Defective"> Defective</option>
                      </select>
                    </td>
                  </tr>
                  <tr class="js-work-status-reason" style="display:none;">
                    <td><strong>Defect Type Reason</strong></td>
                    <td><select name="fabric_fault_id" id="coating_fabric_fault_id" class="form-control">
                        <option value=""> Select Reason</option>
                        <?php foreach ($dataF as $rowF) { ?>
                        <option value="<?= $rowF->id; ?>">
                        <?= $rowF->reason; ?>
                        </option>
                        <?php } ?>
                      </select>
                    </td>
                  </tr>
                  <tr>
                    <td><strong>Warehouse</strong></td>
                    <td><select name="insp_work_warehouseId" id="coating_insp_work_warehouseId" required class="form-control">
                        <option> Select Warehouse</option>
                        <?php foreach ($dataW as $row) { ?>
                        <option value="<?= $row->id; ?>">
                        <?= $row->warehouse_name; ?>
                        </option>
                        <?php } ?>
                      </select>
                    </td>
                  </tr>
                </table>
                </fieldset>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="submit" class="btn btn-success pull-left">Update Inspection Process</button>
          </div>
        </form>
      </div>
    </div>
  </div>
  <!-- ===== Modal 1 (Print) - IDs suffixed with _print ===== -->
  <div class="modal fade" id="CoatingPrintInspProcessPop" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <form method="post" action="{{ route('update_coating_print_inspec_process')}}" class="form-horizontal" onSubmit="disableSubmitButton(this)">
          @csrf
          <div class="modal-header modal-header-primary">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
            <h3><i class="fa fa-plus m-r-5"></i>Inspection Process</h3>
          </div>
          <input type="hidden" name="page" value="<?=htmlspecialchars($current_page); ?>">
          <div class="modal-body">
            <div class="row">
              <div class="col-md-12">
                <fieldset>
                <table class="table table-bordered">
                  <tr>
                    <th>Item Name</th>
                    <td><span id="coating_ItemName_print"></span></td>
                  </tr>
                </table>
                <span id="coating_workRequirement_print1"></span>
                <table class="table table-bordered" id="myTableCoatedPrint">
                  <input type="hidden" id="coating_ins_item_id_print" name="ins_item_id">
                  <input type="hidden" id="coating_ins_work_order_id_print" name="ins_work_order_id">
				  <input type="hidden"   id="machineIdC_print" name="insp_work_machine_id">

                  <thead>
                    <tr>
                      <p><strong>Lot Number : </strong>
                        <input type="number" id="req_lot_no_print" name="req_lot_no"
                          oninput="fetchWarehouseItemStockCoatingPrint(this.value, document.getElementById('coating_ins_work_order_id_print').value, 'myTableCoatedPrint')">
                      </p>
                    </tr>
					 <tr>
                      <td colspan="10"><span id="coating_workRequirement_print"></span> </td>
                    </tr>
                    <tr>
                      <th>Sr.No.</th>
                      <th>G.T.Number</th>
                      <th>Greige Meter</th>
                      <th>Break Meter</th>
                      <th>Output</th>
                      <th>Shrinkage</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr class="table-row2"> </tr>
                  </tbody>
                  <tfoot>
                    <tr>
                      <td colspan="2"><strong>T.Input:</strong></td>
                      <td id="toGreigeItemQty_print">0</td>
                      <td colspan="1"><strong>T.Output:</strong></td>
                      <td id="totalOutput_print">0</td>
                    </tr>
                  </tfoot>
                </table>
                <table class="table table-bordered">
                  <tr>
                    <td><strong>Comment</strong>
                      <p>Machine : <span id="MachineNameC_print"></span> </p>
                      <p>Taka Number : <span id="inspTakaNumberC_print"></span> </p></td>
                    <td><textarea class="form-control" id="inspec_comment_print" required name="inspec_comment"></textarea></td>
                  </tr>
                  <tr>
                    <td><strong> <span id="coating_processtext_print"> </span></strong> </td>
                    <td><select name="insp_work_status_process" required id="coating_print_insp_work_status_process" class="form-control">
                        <option value=""> Select Inspection Process Status</option>
                        <option value="No">Not Complete</option>
                        <option value="Yes">Completed</option>
                      </select>
                    </td>
                  </tr>
                  <tr>
                    <td><strong>Work Status</strong> </td>
                    <td><select name="work_status" required id="work_status_2" onChange='selectWorkStatus(this)' class="form-control">
                        <option value=""> Select Work Status</option>
                        <option value="Completed"> Completed</option>
                        <option value="Defective"> Defective</option>
                      </select>
                    </td>
                  </tr>
                  <tr>
                    <td><strong>Warehouse</strong></td>
                    <td><select name="insp_work_warehouseId" id="coating_insp_work_warehouseId_print" required class="form-control">
                        <option> Select Warehouse</option>
                        <?php foreach ($dataW as $row) { ?>
                        <option value="<?= $row->id; ?>"><?=$row->warehouse_name;?></option>
                        <?php } ?>
                      </select>
                    </td>
                  </tr>
                </table>
                </fieldset>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="submit" class="btn btn-success pull-left">Update Inspection Process</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="modal fade" id="DyeingInspProcessPop" aria-hidden="true">
    <div class="modal-dialog modal-lg" style="max-width: 95%;">
      <div class="modal-content">
        <form method="post" action="{{ route('update_dyeing_inspec_process') }}" onSubmit="disableSubmitButton(this)">
          @csrf
          <input type="hidden" name="submission_token" value="{{ Str::uuid() }}">
          <div class="modal-header modal-header-primary">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
            <h3><i class="fa fa-plus m-r-5"></i>Inspection Process</h3>
          </div>
          <input type="hidden" name="page" value="{{ htmlspecialchars($current_page) }}">
          <div class="modal-body">
            <div class="row">
              <div class="col-md-12">
                <fieldset>
                <table class="table table-bordered table-striped table-hover table-condensed">
                  <tr class="warning">
                    <th>Item Name</th>
                    <td><span id="dyeing_ItemName"></span></td>
                  </tr>
                </table>
                <table class="table table-bordered">
                  <tr>
                    <td><span id="dyeing_workRequirement1"></span> </td>
                  </tr>
                </table>
                <table class="table table-bordered table-striped">
                  <thead>
                    <tr class="info">
                      <th colspan="6" class="text-center"><strong>Lot Info & Destination</strong></th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr>
                      <td colspan="10"> Destination:
                        <label style="margin-left:8px;">
                        <input type="radio" id="to_warehouse" name="destination" value="Warehouse" checked>
                        <span>Warehouse</span> </label>
                        <label style="margin-left:12px;">
                        <input type="radio" id="to_coating" name="destination" value="Department">
                        <span>Department</span> </label>
                      </td>
                    </tr>
                    <!-- Lot Number / Width / GSM row -->
                    <tr class="active">
                      <th style="width: 15%;">Lot Number</th>
                      <td style="width: 18%;"><input type="number" class="form-control" id="dyeing_req_lot_no" name="req_lot_no"
                               oninput="fetchWarehouseItemStock(this.value, document.getElementById('dyeing_ins_work_order_id').value, 'myTableDyed')">
                      </td>
                      <th style="width: 15%;">Width <span style="color:red;">*</span></th>
                      <td style="width: 18%;"><input type="text" class="form-control" id="dyeing_insp_width" name="insp_width" value="0">
                      </td>
                      <th style="width: 15%;">GSM <span style="color:red;">*</span></th>
                      <td style="width: 18%;"><input type="text" class="form-control" id="dyeing_insp_gsm" name="insp_gsm" value="0">
                      </td>
                    </tr>
                    <tr>
                      <td colspan="10"><span id="dyeing_workRequirement"></span> </td>
                    </tr>
                  </tbody>
                </table>
                <table class="table table-bordered" id="myTableDyed">
                  <!-- hidden ids used by your JS / form -->
                  <input type="hidden" id="dyeing_ins_item_id" name="ins_item_id">
                  <input type="hidden" id="dyeing_ins_work_order_id" name="ins_work_order_id">
                  <input type="hidden" id="machineIdD" name="insp_work_machine_id">
				  <input type="hidden" id="reqProIdsDieing" name="insp_work_process_req_id">
                  <thead>
                    <tr class="warning">
                      <th>Sr.No.</th>
                      <th>G.T.Number</th>
                      <th>G.Meter</th>
                      <th>BRK Meter</th>
                      <th>Output</th>
                      <th>Rej.Mtr</th>
                      <th>Rej.Reason</th>
                      <th>Shrinkage</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr class="table-row"></tr>
                  </tbody>
                  <tfoot>
                    <tr>
                      <td colspan="2"><strong>T.Input:</strong></td>
                      <td id="toGreigeItemQtyy">0</td>
                      <td colspan="1"><strong>T.Output:</strong></td>
                      <td id="totalOutputt">0</td>
					  <td id="totalRejectOutputt">0</td>
                    </tr>
                  </tfoot>
                </table>
                <table class="table table-bordered" style="background:#fff; border:1px solid #337ab7;">
                  <tr>
                    <td style="width:30%; vertical-align:middle;" class="warning"><strong>Comment</strong>
                      <p style="display:none; margin-top:5px; color:#777;"> Taka Number : <span id="inspTakaNumberD"></span> </p></td>
                    <td><textarea class="form-control" id="dyeing_inspec_comment" required name="inspec_comment" rows="3" placeholder="Enter inspection comment"></textarea> </td>
                  </tr>
                  <tr>
                    <td style="vertical-align:middle;" class="warning"><strong><span id="dyeing_processtext"></span></strong> </td>
                    <td><select name="insp_work_status_process" required id="dyeing_insp_work_status_process"  class="form-control" onChange="updateCoatingProcess()">
                        <option value=""> Select Inspection Process Status</option>
                        <option value="No">Not Complete</option>
                        <option value="Yes">Completed</option>
                      </select>
                    </td>
                  </tr>
                  <tr>
                    <td style="vertical-align:middle;" class="warning"><strong>Proceed with coating process?</strong> </td>
                    <td><select name="insp_coating_process" id="dyeing_insp_coating_process" required class="form-control">
                        <option value="No">No</option>
                        <option value="Yes">Yes</option>
                      </select>
                    </td>
                  </tr>
                  <tr>
                    <td style="vertical-align:middle;" class="warning"><strong>Work Status</strong> </td>
                    <td><select name="work_status" required id="work_status_3"
                              onChange="selectWorkStatus(this)"
                              class="form-control">
                        <option value=""> Select Work Status</option>
                        <option value="Completed">Ok</option>
                        <option value="Defective">Defective</option>
                      </select>
                    </td>
                  </tr>
                  <tr>
                    <td style="vertical-align:middle;" class="warning"><strong>Machine</strong> </td>
                    <td><span id="MachineNameD" style="font-weight:bold;"></span> </td>
                  </tr>
                  <tr>
                    <td style="vertical-align:middle;" class="warning"><strong>Warehouse</strong> </td>
                    <td ><select name="insp_work_warehouseId" id="dyeing_insp_work_warehouseId" required class="form-control">
                        <option> Select Warehouse</option>
                        <?php foreach ($dataW as $row) { ?>
                        <option value="<?= $row->id; ?>">
                        <?= $row->warehouse_name; ?>
                        </option>
                        <?php } ?>
                      </select>
                    </td>
                  </tr>
                </table>
                <!-- end comment/controls table -->
                </fieldset>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="submit" class="btn btn-success pull-left">Update Inspection Process</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="modal fade" id="WeavingInspProcessPop" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <form method="post" action="{{ route('update_weaving_inspec_process')}}" onSubmit="disableSubmitButton(this)">
          @csrf
          <!-- Header -->
          <div class="modal-header panel-heading">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
            <h4 class="modal-title"> <i class="fa fa-check-circle"></i> Inspection Process </h4>
          </div>
          <input type="hidden" name="page" value="<?=htmlspecialchars($current_page); ?>">
          <!-- Body -->
          <div class="modal-body">
            <fieldset>
            <!-- Work Requirement -->
            <div id="weav_workRequirement" class="mb-3"></div>
            <!-- Main Table -->
            <table class="table table-bordered table-striped text-center" id="myTable">
              <input type="hidden" id="weav_ins_item_id" name="ins_item_id">
              <input type="hidden" id="weav_ins_work_order_id" name="ins_work_order_id">
              <thead class="bg-success">
                <tr>
                  <th class="col-xs-2">Sr.</th>
                  <th class="col-xs-3">Item Name</th>
                  <th class="col-xs-3">Taka Number</th>
                  <th class="col-xs-2">Output</th>
                </tr>
                <tr>
                  <td>1</td>
                  <td><span id="weav_ItemName" class="text-primary font-bold"></span></td>
                  <td><input type="text" min="1" id="weaving_insp_taka_number" name="insp_taka_number" required class="form-control">
                  </td>
                  <td><input type="number" min="1" step="any" id="weaving_output_quan_size" name="output_quan_size[]" required placeholder="Output Size (Meter)" class="form-control">
                  </td>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <th>EPI</th>
                  <th>PPI</th>
                  <th>Width</th>
                  <th>GSM</th>
                </tr>
                <tr>
                  <td><input type="number" min="1" step="any" id="insp_epi" name="insp_epi[]" required class="form-control"></td>
                  <td><input type="number" min="1" step="any" id="insp_ppi" name="insp_ppi[]" required class="form-control"></td>
                  <td><input type="number" min="1" step="any" id="insp_width_weav" name="insp_width[]" required class="form-control">
                  </td>
                  <td><input type="number" min="1" step="any" id="insp_gsm_weav" name="insp_gsm[]" required class="form-control"></td>
                </tr>
              </tbody>
            </table>
            <!-- Extra Details -->
            <table class="table table-bordered table-striped">
              <tr>
                <td class="col-xs-4"><strong>Comment</strong>
                  <p>Machine: <span id="MachineName" class="font-bold"></span></p>
                  <p>Beam Number: <span id="inspTakaNumber" class="font-bold"></span></p>
				</td>
                <td><textarea class="form-control" id="weaving_inspec_comment" required name="inspec_comment"></textarea></td>
              </tr>
              <tr>
                <td><strong><span id="weav_processtext"></span></strong></td>
                <td><select name="insp_work_status_process" required id="weaving_insp_work_status_process" class="form-control" onChange="updateDyeingProcess()">
                    <option value="">Select Inspection Process Status</option>
                    <option value="No">Not Complete</option>
                    <option value="Yes">Completed</option>
                  </select>
                </td>
              </tr>
              <tr>
                <td><strong>Do you want to start the dyeing process?</strong></td>
                <td><select name="insp_dyeing_process" id="weaving_insp_dyeing_process" required class="form-control">
                    <option value="No">No</option>
                    <option value="Yes">Yes</option>
                  </select>
                </td>
              </tr>
              <tr>
                <td><strong>Work Status</strong></td>
                <td><select name="work_status" required id="work_status_4" onChange="selectWorkStatus(this)" class="form-control">
                    <option value="">Select Work Status</option>
                    <option value="Completed">Ok</option>
                    <option value="Defective">Defective</option>
                  </select>
                </td>
              </tr>
              <tr class="js-work-status-reason" style="display:none;">
                <td><strong>Defect Type Reason</strong></td>
                <td><select name="fabric_fault_id" id="weaving_fabric_fault_id" class="form-control">
                    <option value="">Select Reason</option>
                    <?php foreach ($dataF as $rowF) { ?>
                    <option value="<?= $rowF->id; ?>"> <?= $rowF->reason; ?> </option>
                    <?php } ?>
                  </select>
                </td>
              </tr>
              <tr>
                <td><strong>Warehouse</strong></td>
                <td><select name="insp_work_warehouseId" id="insp_work_warehouseId" required class="form-control">
                    <option>Select Warehouse</option>
                    <?php foreach ($dataW as $row) { ?>
                    <option value="<?= $row->id; ?>"> <?= $row->warehouse_name; ?> </option>
                    <?php } ?>
                  </select>
                </td>
              </tr>
            </table>
            </fieldset>
          </div>
          <!-- Footer -->
          <div class="modal-footer bg-light">
            <button type="submit" class="btn btn-success"> <i class="fa fa-save"></i> Update Inspection Process </button>
            <button type="button" class="btn btn-default" data-dismiss="modal"> <i class="fa fa-times"></i> Close </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="modal fade" id="InspectionProcessPop" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <form method="post" action="{{ route('update_inspec_process')}}" class="form-horizontal" autocomplete="off" onSubmit="disableSubmitButton(this)">
          @csrf
          <div class="modal-header modal-header-primary">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
            <h3><i class="fa fa-plus m-r-5"></i>Inspection Process</h3>
          </div>
          <input type="hidden" name="page" value="<?php echo htmlspecialchars($current_page); ?>">
          <div class="modal-body">
            <div class="row">
              <div class="col-md-12">
                <fieldset>
                <table class="table table-bordered">
                  <tr>
                    <th>Item Name</th>
                    <td><span id="ItemName"></span></td>
                  </tr>
                </table>
                <span id="workRequirement"></span>
                <table class="table table-bordered table-striped shadow-sm rounded" id="myTableInsp">
                  <thead>
                    <tr>
                      <input type="hidden" id="ins_item_id" name="ins_item_id">
                      <input type="hidden" id="ins_work_order_id" name="ins_work_order_id">
                      <th>Completed <span id="InsoutputNext"></span> (Quantity)</th>
                      <th>Output <span id="outputNext"></span> Size (<span id="outputUnitType"></span>)</th>
                      <th>Beam Number</th>
                      <th>Weaving Work Meter</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr>
                      <td>1</td>
                      <td><input type="number" min="1" id="inspection_output_quan_size" class="form-control" name="output_quan_size[]" required>
                      </td>
                      <td><input type="text" id="inspection_insp_taka_number" class="form-control" name="insp_taka_number" required>
                      </td>
                      <td><input type="text" id="weaving_mtr" class="form-control" name="weaving_mtr" required>
                      </td>
                    </tr>
                  </tbody>
                </table>
                <table class="table table-bordered">
                  <tr>
                    <td><strong>Comment</strong> </td>
                    <td><textarea class="form-control" id="inspection_inspec_comment" required name="inspec_comment"></textarea></td>
                  </tr>
                  <tr>
                    <td><strong> <span id="processtext"> </span></strong> </td>
                    <td><select name="insp_work_status_process" required id="inspection_insp_work_status_process" class="form-control">
                        <option value=""> Select Inspection Process Status</option>
                        <option value="No">Not Complete</option>
                        <option value="Yes">Completed</option>
                      </select>
                    </td>
                  </tr>
                  <tr>
                    <td><strong>Do you want to start the Weaving process ?</strong></td>
                    <td><select name="insp_weaving_process" id="insp_weaving_process" required class="form-control">
                        <option value="No">No</option>
                        <option value="Yes">Yes</option>
                      </select>
                    </td>
                  </tr>
                  <tr>
                    <td><strong>Work Status</strong> </td>
                    <td><select name="work_status" required id="work_status_5" class="form-control">
                        <option value="Completed"> Completed</option>
                      </select>
                    </td>
                  </tr>
                  <tr class="js-work-status-process" style="display:none;">
                    <td><strong>Process</strong></td>
                    <td><div class="i-check">
                        <input tabindex="7" type="radio" id="minimal-radio-1" value="reprocess" onClick="gatePass(this.value)" name="work_status_process">
                        <label for="minimal-radio-1">Re-Processing</label>
                      </div>
                      <div class="i-check">
                        <input tabindex="8" type="radio" id="minimal-radio-2" value="stock" onClick="gatePass(this.value)" name="work_status_process">
                        <label for="minimal-radio-2">Send To Warehouse</label>
                      </div></td>
                  </tr>
                  <tr class="js-work-status-reason" style="display:none;">
                    <td><strong>Defect Type Reason</strong></td>
                    <td><select name="fabric_fault_id" id="inspection_fabric_fault_id" class="form-control">
                        <option value=""> Select Reason</option>
                        <?php foreach ($dataF as $rowF) { ?>
                        <option value="<?= $rowF->id; ?>">
                        <?= $rowF->reason; ?>
                        </option>
                        <?php } ?>
                      </select>
                    </td>
                  </tr>
                  <tr>
                    <td><strong>Warehouse</strong></td>
                    <td><select name="insp_work_warehouse_id" id="insp_work_warehouse_id" required class="form-control">
                      </select>
                    </td>
                  </tr>
                </table>
                </fieldset>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="submit" class="btn btn-success pull-left">Update</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="modal fade" id="StartProcessPop" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <form method="post" action="{{ route('update_startprocess')}}" class="form-horizontal" autocomplete="off" onSubmit="disableSubmitButton(this)">
          @csrf
          <div class="modal-header modal-header-primary">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
            <h3><i class="fa fa-plus m-r-5"></i> Start <span id="processNameId"></span> Process </h3>
          </div>
          <input type="hidden" name="page" value="<?php echo htmlspecialchars($current_page); ?>">
          <div class="modal-body">
            <div class="row">
              <div class="col-md-12">
                <fieldset>
                <span id="RequestedItems"></span>
                <table class="table table-bordered ">
                  <tr>
                    <th>Item Name</th>
                    <td><span id="ItemNameS"></span> </td>
                  </tr>
                  <tr>
                    <input type="hidden" id="itemId" name="itemId">
                    <input type="hidden" id="work_order_id" name="work_order_id">
                  </tr>
                  <tr>
                    <td><strong>Master</strong> </td>
                    <td><select id="masterId" class="form-control" name="masterId">
                        <?php foreach ($dataMas as $row) { ?>
							<option value="<?=$row->id;?>"><?=$row->name;?></option>
                        <?php } ?>
                      </select>
                    </td>
                  </tr>

                </table>
                <tr>
                  <td><label>Process Remarks <span class="required">*</span></label>
                    <input type="text" name="process_started_remarks" id="process_started_remarks" required class="form-control">
                  </td>
                </tr>
                </fieldset>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="submit" class="btn btn-success pull-left">Start Process</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="modal fade" id="StartProcessPopWev" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <form method="post" action="{{ route('update_startprocess')}}" class="form-horizontal" autocomplete="off" onSubmit="disableSubmitButton(this)">
          @csrf
          <div class="modal-header modal-header-primary">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
            <h3><i class="fa fa-plus m-r-5"></i> Start <span id="processNameIdWev"></span> Process </h3>
          </div>
          <input type="hidden" name="page" value="<?php echo htmlspecialchars($current_page); ?>">
          <div class="modal-body">
            <div class="row">
              <div class="col-md-12">
                <fieldset>
                <span id="RequestedItemsWev"></span>
                <table class="table table-bordered ">
                  <tr>
                    <th>Item Name</th>
                    <td><span id="ItemNameWev"></span> </td>
                  </tr>
                  <tr>
                    <input type="hidden" id="itemIdWev" name="itemId">
                    <input type="hidden" id="work_order_idWev" name="work_order_id">
                  </tr>
                  <tr>
                    <td><strong>Master</strong> </td>
                    <td><select id="masterIdWev" class="form-control" name="masterId">
                        <?php foreach ($dataMas as $row) { ?>
							<option value="<?=$row->id;?>"><?=$row->name;?></option>
                        <?php } ?>
                      </select>
                    </td>
                  </tr>

                  <tr>
                    <td><strong>Machine </strong></td>
                    <td><select id="machineIdWev" class="form-control" name="machineId">
                      </select>
                    </td>
                  </tr>
                </table>
                <tr>
                  <td><label>Process Remarks <span class="required">*</span></label>
                    <input type="text" name="process_started_remarks" id="process_started_remarksWev" required class="form-control">
                  </td>
                </tr>
                </fieldset>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="submit" class="btn btn-success pull-left">Start Process</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Modal HTML -->
  <div id="activateModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="activateModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="activateModalLabel">Confirm Activation</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"> <span aria-hidden="true">&times;</span> </button>
        </div>
        <div class="modal-body"> You can activate this work order only once. A detailed report of this change will be sent to the director for review. After this modification, the button will be disabled. </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-primary" id="confirmActivateBtn">OK</button>
        </div>
      </div>
    </div>
  </div>
  <!-- Modal HTML -->
  <div id="receiveStockModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="receiveStockModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="receiveStockModalLabel">Receive Stock</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"> <span aria-hidden="true">&times;</span> </button>
        </div>
        <div class="modal-body">
          <!-- Modal content will be loaded here -->
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>
  <!-- Modal for Updating Lot Number -->
  <div class="modal fade" id="updateLotModal" tabindex="-1" role="dialog" aria-labelledby="updateLotModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="updateLotModalLabel">Update Lot Number</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"> <span aria-hidden="true">&times;</span> </button>
        </div>
        <div class="modal-body">
          <input type="hidden" id="workOrderId" class="form-control" placeholder="Enter new lot number">
          <input type="hidden" id="workProId" class="form-control" placeholder="Enter new lot number">
          <div class="form-group">
            <label class="control-label" for="newLotNo"> Current Lot Number: <span id="currentLotNo"></span><br>
            Please enter a new lot number below to update: </label>
            <input type="text" id="newLotNo" class="form-control" placeholder="Enter new lot number">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
          <button type="button" class="btn btn-success" id="saveLotBtn">Save</button>
        </div>
      </div>
    </div>
  </div>
  <!-- Modal HTML -->
  <div id="shiftWoModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="shiftModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="shiftModalLabel">Are you sure you want to confirm the shift of the Work Order to the Warping Department?</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"> <span aria-hidden="true">&times;</span> </button>
        </div>
        <div class="modal-body"> You can shift this work order only once. A detailed report of this change will be sent to the director for review. After this modification, the button will be disabled. </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-primary" id="confirmShiftBtn">OK</button>
        </div>
      </div>
    </div>
  </div>

<div class="modal fade" id="returnModal" tabindex="-1" role="dialog" aria-labelledby="returnModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">

            <form method="post" action="{{ route('sendItemReturnRequest') }}" class="form-horizontal" autocomplete="off" onsubmit="return validateReturnForm(this)">
                @csrf
				<div class="modal-header text-center">
					<button type="button" class="close" data-dismiss="modal" aria-label="Close">
						<span aria-hidden="true">&times;</span>
					</button>

					<h4 class="modal-title" id="returnModalLabel"> <span class="glyphicon glyphicon-retweet text-primary"></span> &nbsp; LOT ITEM RETURN </h4>
					<hr>
					<p class="text-muted">
						<span class="label label-info">Lot Number: <span id="modalLotNumber"></span></span>
						<span class="label label-danger">Return Process</span>
					</p>
				</div>

                <div class="modal-body">
                    <input type="hidden" id="ReqLotNumber" name="ReqLotNumber" value="">
                    <input type="hidden" id="wprId" name="wprId" value="">
                    <input type="hidden" id="chkworkOrderId" name="workOrderId" value="">

                    <div class="table-responsive">
                        <table class="table table-striped table-bordered table-hover table-condensed" id="returnItemsTable">
                           <thead>
								<tr class="info">
									<th># StockId</th>
									<th>Taka Number</th>
									<th>Lot Number</th>
									<th>Dyeing Sr.</th>
									<th>Meter</th>
									<th>All <input type="checkbox" id="selectAll" onclick="toggleSelectAll(this)"> </th>
								</tr>
							</thead>
                            <tbody>
                                <!-- Dynamic rows will be appended here -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">
                        Close
                    </button>
                    <button type="submit" class="btn btn-primary">
                        Return
                    </button>
                </div>
            </form>

        </div>
    </div>
</div>

  <!-- Modal HTML -->
  <div id="deleteModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog " role="document">
      <div class="modal-content">
        <!-- Modal Header -->
        <div class="modal-header bg-danger">
          <h5 class="modal-title" id="receiveStockModalLabel2">⚠️ Confirm Deletion</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"> <span aria-hidden="true">&times;</span> </button>
        </div>
        <!-- Modal Body -->
        <div class="modal-body text-center">
          <p class=" text-dark"> Are you sure you want to <strong>delete</strong> this work order? This action cannot be undone. </p>
          <p class="text-muted"> <i>A detailed report of this change will be sent to the director for review.</i> </p>
        </div>
        <!-- Modal Footer -->
        <div class="modal-footer justify-content-center">
          <button type="button" class="btn btn-default btn-sm" data-dismiss="modal"> ❌ Cancel </button>
          <button type="button" class="btn btn-danger btn-sm" id="confirmDelBtn"> ✅ Confirm Delete </button>
        </div>
      </div>
    </div>
  </div>
  <!-- Modal HTML -->
  <div id="deleteGpModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="deleteGpModalLabel" aria-hidden="true">
    <div class="modal-dialog " role="document">
      <div class="modal-content">
        <!-- Modal Header -->
        <div class="modal-header bg-danger">
          <h5 class="modal-title" id="receiveStockModalLabel3">⚠️ Confirm Deletion</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"> <span aria-hidden="true">&times;</span> </button>
        </div>
        <!-- Modal Body -->
        <div class="modal-body text-center">
          <p class=" text-dark"> Are you sure you want to <strong>delete</strong> this work order? This action cannot be undone. </p>
          <p class="text-muted"> <i>A detailed report of this change will be sent to the director for review.</i> </p>
        </div>
        <!-- Modal Footer -->
        <div class="modal-footer justify-content-center">
          <button type="button" class="btn btn-default btn-sm" data-dismiss="modal"> ❌ Cancel </button>
          <button type="button" class="btn btn-danger btn-sm" id="confirmDelGpBtn"> ✅ Confirm Delete </button>
        </div>
      </div>
    </div>
  </div>
  <div id="activateInspModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="activateModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="activateModalLabel2">Confirm Activation</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"> <span aria-hidden="true">&times;</span> </button>
        </div>
        <div class="modal-body"> You can activate this inspection button. A detailed report of this change will be sent to the director for review. After this modification, the button will be disabled. </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-primary" id="confirmActivateInspBtn">OK</button>
        </div>
      </div>
    </div>
  </div>
  <!-- Lab Request Modal -->
  <div class="modal fade" id="labRequestModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-md">
      <div class="modal-content ">
        <!-- Header -->
        <div class="modal-header" style="background: linear-gradient(135deg, #17a2b8, #138496); color: #fff;">
          <h4 class="modal-title"> <i class="fa fa-flask"></i> Lab Test Request </h4>
          <button type="button" class="close" data-dismiss="modal" style="color: #fff; opacity: 1;">×</button>
        </div>
        <!-- Body -->
        <div class="modal-body" style="background: #f9f9f9;">
          <table class="table table-bordered table-condensed ">
            <tbody>
              <tr>
                <th style="width:40%; background:#f8f9fa; color:#555;">Lot Number</th>
                <td><span id="modalLotNo" class="badge badge-info  "></span> </td>
              </tr>
              <tr>
                <th style="background:#f8f9fa; color:#555;">Work Order ID</th>
                <td><span id="modalWorkOrder" class="badge badge-primary  "></span> </td>
              </tr>
            </tbody>
          </table>
          <input type="hidden" id="modalLotId">
          <div class="form-group">
            <label for="labRemarks" class="control-label"> <i class="fa fa-commenting"></i> Remarks / Comments </label>
            <textarea id="labRemarks" class="form-control" rows="3" placeholder="Enter remarks"></textarea>
          </div>
          <div class="form-group">
            <label for="labMeter" class="control-label"> <i class="fa fa-ruler"></i> Total Meter </label>
            <input type="number" id="labMeter" class="form-control" placeholder="Enter total meter">
          </div>
        </div>
        <!-- Footer -->
        <div class="modal-footer" style="background: #f1f1f1;">
          <button type="button" class="btn btn-default btn-sm" data-dismiss="modal"> <i class="fa fa-times"></i> Cancel </button>
          <button type="button" class="btn btn-success btn-sm" onClick="confirmLabRequest()"> <i class="fa fa-check"></i> Confirm Request </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal -->
	<div class="modal fade" id="beamReturnBeamModal" tabindex="-1" role="dialog" aria-labelledby="beamReturnModalLabel" aria-hidden="true">
	  <div class="modal-dialog modal-lg" role="document">
		<div class="modal-content">
		  <form method="post" action="<?php echo route('sendItemReturnRequest'); ?>" class="form-horizontal" autocomplete="off" onsubmit="return validateBeamReturnForm(this)">
			<?php echo csrf_field(); ?>
			<div class="modal-header">
			  <h5 class="modal-title" id="beamReturnModalLabel">Beam Item Return: <span id="modalBeamLotNumber"></span></h5>
			  <button type="button" class="close" data-dismiss="modal" aria-label="Close"> <span aria-hidden="true">&times;</span> </button>
			</div>

			<div class="modal-body">
			  <input type="hidden" id="beamWprId" name="wprId" value="">
			  <input type="hidden" id="beamChkworkOrderId" name="workOrderId" value="">

			  <table class="table" id="beamReturnItemsTable">
				<thead>
				  <tr>
					<th># StockId</th>
					<th>Taka Number</th>
					<th>Received Meter</th>
					<th>Used Meter</th>
					<th>Return Meter</th>
					<th><input type="checkbox" id="beamSelectAll" onclick="toggleSelectAllBeam(this)">All</th>
				  </tr>
				</thead>
				<tbody>
				  <!-- Dynamic rows appended here -->
				</tbody>
			  </table>
			</div>

			<div class="modal-footer">
			  <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
			  <button type="submit" class="btn btn-primary">Return</button>
			</div>
		  </form>
		</div>
	  </div>
	</div>

  <!-- Modal HTML -->
	<div id="closeActivateModal" class="modal fade">
	  <div class="modal-dialog" role="document">
		<div class="modal-content">
		  <div class="modal-header">
			<h5 class="modal-title" id="activateModalLabel3">Confirm to Close this Work Order</h5>
			<button type="button" class="close" data-dismiss="modal" aria-label="Close"> <span aria-hidden="true">&times;</span> </button>
		  </div>
		  <div class="modal-body">
			Are you sure you want to close this work order? After confirming by clicking the OK button, you will not be able to work on this work order.
		  </div>
		  <div class="modal-footer">
			<button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
			<button type="button" class="btn btn-primary" id="confirmCloseWOBtn">OK</button>
		  </div>
		</div>
	  </div>
	</div>

<div class="modal fade" id="workOrderTotalModal" tabindex="-1" role="dialog" aria-labelledby="workOrderTotalModalLabel">
    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content">

            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="workOrderTotalModalLabel">
                    <i class="fa fa-bar-chart"></i> Work Order Totals
                </h4>
            </div>

            <div class="modal-body">
                <div id="totalLoading" class="loading-wrap" style="display:none;">
                    <div class="spinner"></div>
                    <div class="loading-text">Loading totals...</div>
                </div>

                <div id="totalDataWrap">
                    <div class="total-box mtr">
                        <div class="total-icon"><i class="fa fa-arrows-h"></i></div>
                        <div class="total-label">Total Meter</div>
                        <div class="total-value mtr" id="showTotMtr">0</div>
                    </div>

                    <div class="total-box insp">
                        <div class="total-icon"><i class="fa fa-search"></i></div>
                        <div class="total-label">Total Inspected Meter</div>
                        <div class="total-value insp" id="showTotInspMtr">0</div>
                    </div>

                    <div class="total-box req">
                        <div class="total-icon"><i class="fa fa-check-circle"></i></div>
                        <div class="total-label">Total Required</div>
                        <div class="total-value req" id="showTotReqMtr">0</div>
                    </div>

                    <div class="summary-note">
                        <i class="fa fa-info-circle"></i>
                        These totals are calculated from the currently applied filters.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="planningWarningModal" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">

            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
                <h4 class="modal-title text-primary"><i class="fa fa-info-circle"></i> Planning Notice</h4>
            </div>

            <div class="modal-body">
                <div class="panel panel-primary">
                    <div class="panel-heading">
                        <h4 class="panel-title">
                            <i class="fa fa-clipboard"></i> Planning Required Before Work
                        </h4>
                    </div>

                    <div class="panel-body text-center">
                        <h3 class="text-primary">
                            Planning has not been created
                        </h3>

                        <p class="lead">
                            Lot Number <span class="label label-primary" id="planningLotNumber"></span>
                        </p>

                        <div class="well well-sm text-left">
                            <h4 class="text-primary">
                                <i class="fa fa-check-circle"></i> Current Permission
                            </h4>
                            <p>
                                You can continue for now by clicking the <strong>OK</strong> button.
                            </p>
                        </div>

                        <div class="list-group text-left">
                            <div class="list-group-item list-group-item-info">
                                <h4 class="list-group-item-heading">
                                    <i class="fa fa-info-circle"></i> Future Rule
                                </h4>
                                <p class="list-group-item-text">
                                    This temporary facility will be disabled in the future. Please create the planning first before starting work or inspection for any lot.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-dismiss="modal"><i class="fa fa-check"></i> OK, Continue for Now</button>
            </div>

        </div>
    </div>
</div>

  <!-------------------Modal End-------------------->
  @include('common.footer') </div>
@include('common.formfooterscript')
<script type="text/javascript" src="{{ asset('js/jquery.validate.js') }}"></script>

<!-- ===== JS: set _print IDs and fetch function for Print modal ===== -->


<script type="text/javascript">
'use strict';

/* ==================== 01. animateCount ==================== */
function animateCount(selector, endValue) {
    $({ countNum: 0 }).animate({ countNum: endValue }, {
        duration: 900,
        easing: 'swing',
        step: function () {
            $(selector).text(Math.floor(this.countNum).toLocaleString());
        },
        complete: function () {
            $(selector).text(parseFloat(endValue).toLocaleString());
        }
    });
}

/* ==================== 02. events ==================== */
$(document).on('click', '#viewTotalsBtn', function () {
    var url = $(this).data('url');

    $('#totalDataWrap').hide();
    $('#totalLoading').show();
    $('#workOrderTotalModal').modal('show');

    $.ajax({
        url: url,
        type: 'GET',
        success: function (res) {
            $('#totalLoading').hide();
            $('#totalDataWrap').show();

            if (res.success) {
                animateCount('#showTotMtr', res.totMtr);
                animateCount('#showTotInspMtr', res.totInspMtr);
                animateCount('#showTotReqMtr', res.totReqMtr);
            } else {
                $('#showTotMtr').text('0');
                $('#showTotInspMtr').text('0');
                $('#showTotReqMtr').text('0');
            }
        },
        error: function () {
            $('#totalLoading').hide();
            $('#totalDataWrap').show();
            $('#showTotMtr').text('Error');
            $('#showTotInspMtr').text('Error');
            $('#showTotReqMtr').text('Error');
        }
    });
});

/* ==================== 03. events ==================== */
document.addEventListener('DOMContentLoaded', function () {

    var goBtn = document.getElementById('goToPageButton');
    if (goBtn) {
        goBtn.addEventListener('click', function () {

            var pageInput = document.getElementById('manualPageInput').value;
            var lastPage = {{ $dataWI->lastPage() }};

            if (pageInput > 0 && pageInput <= lastPage) {
                var params = new URLSearchParams(window.location.search);
                params.set('page', pageInput);
                window.location.href = window.location.pathname + '?' + params.toString();
            }
        });
    }
});

/* ==================== 04. openLabRequestModal, confirmLabRequest ==================== */
// Open Modal with Lot Info
function openLabRequestModal(button)
{
    let id  = $(button).data("id");
    let lot = $(button).data("lot");
    let wo  = $(button).data("wo");

    $("#modalLotId").val(id);
    $("#modalLotNo").text(lot);
    $("#modalWorkOrder").text(wo);

	$('#labRequestModal').modal({
		backdrop: 'static',
		keyboard: false,
		show: true
	});

}

// Confirm Request
function confirmLabRequest() {
    var id      = $("#modalLotId").val();
    var remarks = $("#labRemarks").val();
    var meter   = $("#labMeter").val();

    $.ajax({
        url: "{{ route('lab-request.send') }}",
        type: "GET",   // ✅ should be POST, not GET
        data: {
            _token: "{{ csrf_token() }}",
            id: id,
            remarks: remarks,
            meter: meter
        },
        success: function(res) {
            if (res.success) {
                $("#lotCell" + id).html('<span class="label label-warning">Request Sent</span>');
                $("#labRequestModal").modal("hide");
                alert(res.message);
            }
        },
        error: function(xhr) {
            var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : "Request failed.";
            alert("Error: " + msg);
        }
    });
}

/* ==================== 05. openReasonModal ==================== */
function openReasonModal(woId)
	{
		document.getElementById('modalFId').value = woId;

		const tableBody = document.querySelector('#reasonTable tbody');
		tableBody.innerHTML = '<tr><td colspan="3">Loading...</td></tr>';

		fetch("/get-work-reason-history/" + woId)
		.then(response => response.json())
		.then(data => {
		  tableBody.innerHTML = '';

		  if (data.length > 0) {
			data.forEach(function(item, index) {
			  const row = "<tr>" +
							"<td>" + (index + 1) + "</td>" +
							"<td>" + item.reason + "</td>" +
							"<td>" + item.created + "</td>" +
						  "</tr>";
			  tableBody.innerHTML += row;
			});
		  } else {
			tableBody.innerHTML = '<tr><td colspan="3">No reason history found.</td></tr>';
		  }

		  $('#reasonModal').modal('show');
		})
		.catch(function(error) {
		  console.error(error);
		  tableBody.innerHTML = '<tr><td colspan="3">Failed to load data.</td></tr>';
		});
	}

/* ==================== 06. ReActivateInspProcess ==================== */
let activateInspId = null;
	function ReActivateInspProcess(id) {
	  activateInspId = id; // Store the ID for use after confirmation
	  $('#activateInspModal').modal('show'); // Show the modal
	}

	$('#confirmActivateInspBtn').on('click', function() {
	  var siteUrl = "{{ url('/') }}";

	  jQuery.ajax({
		type: "GET",
		url: siteUrl + '/ajax_script/activateInspWorkOrder',
		data: {
		  "_token": "{{ csrf_token() }}",
		  "FId": activateInspId
		},
		cache: false,
		success: function(response) {
		  // $("#Mid" + activateInspId).hide();
		  alert("Work order Inspection button reactivated successfully.");
		  window.location.reload();
		  $('#activateInspModal').modal('hide'); // Hide the modal
		},
		error: function(xhr, status, error) {
		  alert("An error occurred: " + error);
		  $('#activateInspModal').modal('hide'); // Hide the modal
		}
	  });
	});

/* ==================== 07. DelGatePass ==================== */
let deleteGpId = null;
function DelGatePass(id) {
  deleteGpId = id;
  $('#deleteGpModal').modal('show');
}

$('#confirmDelGpBtn').on('click', function() {
  var siteUrl = "{{ url('/') }}";

  jQuery.ajax({
	type: "GET",
	url: siteUrl + '/ajax_script/deleteGpInspDetails',
	data: {
	  "_token": "{{ csrf_token() }}",
	  "FId": deleteGpId
	},
	cache: false,
	success: function(response) {  //alert(response);
	  $("#InsGpid" + deleteGpId).hide();
	  alert("Work Inspection Record deleted successfully.");
	  $('#deleteGpModal').modal('hide');
	},
	error: function(xhr, status, error) {
	  alert("An error occurred: " + error);
	  $('#deleteGpModal').modal('hide');
	}
  });
});

/* ==================== 08. disableSubmitButton ==================== */
function disableSubmitButton(form) {
        const submitButton = form.querySelector('button[type="submit"]');
        submitButton.disabled = true;
        submitButton.innerHTML = 'Submitting...'; // Optional: Change button text while submitting
    }

/* ==================== 09. toggleSelectAll, validateReturnForm ==================== */
function toggleSelectAll(selectAllCheckbox)
	{
        // Get all checkboxes in the return items table
        const checkboxes = document.querySelectorAll('#returnItemsTable tbody input[type="checkbox"]');
        checkboxes.forEach(checkbox => {
            // Set the checked property of each checkbox based on the "Select All" checkbox
            if (!checkbox.disabled) { // Only check/uncheck enabled checkboxes
                checkbox.checked = selectAllCheckbox.checked;
            }
        });
    }

	function validateReturnForm(form)
	{
        const checkboxes = document.querySelectorAll('#returnItemsTable tbody input[type="checkbox"]');
        let isChecked = false;

        // Check if any checkbox is selected
        checkboxes.forEach(checkbox => {
            if (checkbox.checked && !checkbox.disabled) {
                isChecked = true;
            }
        });

        if (!isChecked) {
            alert("Please select at least one item to return.");
            return false;  // Prevent form submission
        }

		disableSubmitButton(form);

        return true;  // Allow form submission
    }

/* ==================== 10. GetLotReturnItems ==================== */
function GetLotReturnItems(id, reqLotNo, workOrderId, tableId)
{
    const siteUrl = "{{ url('/') }}";
    const modalLotNumber = document.getElementById('modalLotNumber');
    modalLotNumber.textContent = reqLotNo;
    const ReqLotNumber = document.getElementById('ReqLotNumber');
    ReqLotNumber.value = reqLotNo;
    const modalwprId = document.getElementById('wprId');
    modalwprId.value = id;
    const modalworkOrderId = document.getElementById('chkworkOrderId');
    modalworkOrderId.value = workOrderId;

    jQuery.ajax({
        type: "GET",
        url: siteUrl + '/' + "ajax_script/getLotReturnItems",
        data: {
            "_token": "{{ csrf_token() }}",
            "id": id,
            "req_lot_no": reqLotNo,
            "work_order_id": workOrderId,
        },
        cache: false,
        success: function(response) {
            let returnItems;
            try {
                returnItems = typeof response === 'string' ? JSON.parse(response) : response;
            } catch (e) {
                console.error("Error parsing JSON response:", e);
                return;
            }

            const tableBody = document.querySelector(`#returnItemsTable tbody`);
            tableBody.innerHTML = ''; // Clear previous content

            returnItems.forEach((item, index) => {
				const newRow = document.createElement('tr');

				const isCheckboxDisabled = item.department_return_request && item.department_return_request.id ? 'disabled' : '';

				// Dynamically build the table row with the disabled checkbox condition
				newRow.innerHTML = '<td><input type="hidden" class="form-control" name="ware_out_item_id[]" value="' + item.id + '">' +
					'<input type="text" class="form-control" name="return_wis_id[]" readonly value="' + item.wis_id + '"></td>' +
					'<td><input type="text" class="form-control" name="return_insp_taka_number[]" readonly value="' + item.insp_taka_number + '"></td>' +
					'<td><input type="text" class="form-control" name="return_dyeing_lot_number[]" readonly value="' + item.dyeing_lot_number + '"></td>' +
					'<td><input type="text" class="form-control" name="return_dyeing_taka_number[]" readonly value="' + item.dyeing_taka_number + '"></td>' +
					'<td><input type="text" class="form-control" name="return_item_qty[]" readonly value="' + (item.item_qty || '') + '"></td>' +
					'<td><input type="checkbox" name="is_return[' + index + ']" value="1" ' + isCheckboxDisabled + '>' +
					(isCheckboxDisabled ? '<input type="hidden" name="is_return[' + index + ']" value="0">' : '') + '</td>';

				tableBody.appendChild(newRow);
			});

            $('#returnModal').modal({
                backdrop: 'static',
                keyboard: false
            });
        },
        error: function(xhr, status, error) {
            console.error("AJAX error:", status, error);
        }
    });
}

/* ==================== 11. events ==================== */
var siteUrl = "{{ url('/') }}";
$(document).ready(function() {
    // Open the lot update modal and populate it with data
    $('.open-lot-modal').click(function() {
        var formContent = $(this).data('form-content');

        // Populate the modal fields with data
        $('#newLotNo').val(formContent.req_lot_no);
		 $('#currentLotNo').text(formContent.req_lot_no);
        $('#workOrderId').val(formContent.work_order_id);
        $('#workProId').val(formContent.id);
        $('#saveLotBtn').data('id', formContent.id);
        $('#saveLotBtn').data('work-order-id', formContent.work_order_id);

        // Show the modal
        $('#updateLotModal').modal('show');
    });

    // Save button click event
    $('#saveLotBtn').click(function() {
        var id = $(this).data('id');
        var newLotNo = $('#newLotNo').val();
        var workOrderId = $(this).data('work-order-id');

        // Ajax request to update the req_lot_no
        $.ajax({
            url: siteUrl + '/ajax_script/updateLotNumber',
            type: 'GET',
            data: {
                id: id,
                req_lot_no: newLotNo,
                work_order_id: workOrderId,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                // Update the lot number in the table
                $('#lotCell' + id).text(newLotNo);

                // Close the modal
                $('#updateLotModal').modal('hide');
            },
            error: function(xhr) {
                console.log(xhr.responseText); // Handle errors
            }
        });
    });
});

/* ==================== 12. ShiftWorkOrderToWarping ==================== */
let shiftWoId = null;

	function ShiftWorkOrderToWarping(id) {
	  shiftWoId = id; // Store the ID for use after confirmation
	  $('#shiftWoModal').modal('show'); // Show the modal
	}
	$('#confirmShiftBtn').on('click', function() {
	  var siteUrl = "{{ url('/') }}";

	  jQuery.ajax({
		type: "GET",
		url: siteUrl + '/ajax_script/shiftWorkOrderToWarping',
		data: {
		  "_token": "{{ csrf_token() }}",
		  "FId": shiftWoId
		},
		cache: false,
		success: function(response) {
		  $("#Mid" + shiftWoId).hide();
		  alert("Work order shifted successfully.");
		  $('#shiftWoModal').modal('hide'); // Hide the modal
		},
		error: function(xhr, status, error) {
		  alert("An error occurred: " + error);
		  $('#shiftWoModal').modal('hide'); // Hide the modal
		}
	  });
	});

/* ==================== 13. events ==================== */
$(document).ready(function() {
    $('.btn-success.open-modal').on('click', function() {
      var $button = $(this);
      var formContent = $button.data('form-content'); // Get the form HTML content from data attribute

      $('#receiveStockModal .modal-body').html(formContent); // Update modal content
      $('#receiveStockModal').modal('show'); // Show the modal
    });
  });

/* ==================== 14. events ==================== */
let fabricFaultOptions = '<option value="">Select Reason</option>';
<?php foreach ($dataF as $rowF) { ?>
fabricFaultOptions += '<option value="<?= e($rowF->id) ?>"><?= e(addslashes($rowF->reason)) ?></option>';
<?php } ?>

/* ==================== 15. fetchWarehouseItemStock ==================== */
function fetchWarehouseItemStock(lotNumber, workOrderId, tableId)
{
    if (!lotNumber || !workOrderId) {
        $("#dyeing_workRequirement").html('');
        return;
    }

    var siteUrl = "{{ url('/') }}";

    $("#dyeing_workRequirement").html('');

    jQuery.ajax({
        type: "GET",
        url: siteUrl + "/ajax_script/getWarehouseItemStock",
        data: {
            "_token": "{{ csrf_token() }}",
            "lot_number": lotNumber,
            "dyeing_ins_work_order_id": workOrderId
        },
        cache: false,

        success: function(response)
        {
            console.log("Raw server response (success):", response);

            var payload = null;

            try {
                payload = (typeof response === 'string') ? JSON.parse(response) : response;
            } catch (e) {
                console.error("Failed to parse response JSON:", e);
                payload = response;
            }

            $("#dyeing_workRequirement").html('');

            var $tableBody = $('#' + tableId + ' tbody');
            $tableBody.html('');

            if (payload && payload.show_planning_popup)
            {
                $("#planningWarningMessage").html(payload.planning_warning_message);

				$("#planningLotNumber").text(payload.lot_number);
                $("#planningWarningModal").modal({
                    backdrop: 'static',
                    keyboard: false,
                    show: true
                });

                setTimeout(function() {
                    $("#planningWarningModal").css("z-index", 1060);
                    $(".modal-backdrop").last().css("z-index", 1055);
                }, 300);
            }

			$('#planningWarningModal').on('hidden.bs.modal', function () {
				if ($('.modal.in').length > 0) {
					$('body').addClass('modal-open');
				}
			});

            if (payload && (payload.message || payload.message_hi) && !payload.show_planning_popup)
            {
                $("#dyeing_workRequirement").html('<div class="alert alert-info small">' + (payload.message_hi || payload.message) + '</div>');

                var nameFallback = payload.machineName || payload.MachineName || null;
                var idFallback   = payload.machineId || payload.MachineId || payload.machine_id || null;

                $("#MachineNameD").text(nameFallback ? nameFallback : 'Not allocated');
                $("#machineIdD").val(idFallback ? idFallback : '');

                return;
            }

            var stockItems = [];

            if (Array.isArray(payload))
            {
                stockItems = payload;
            }
            else if (payload && Array.isArray(payload.stockItems))
            {
                stockItems = payload.stockItems;
            }
            else if (payload && payload.data && Array.isArray(payload.data))
            {
                stockItems = payload.data;
            }

            var rowNumber = 1;

            stockItems.forEach(function(stockItem)
            {
                var qty = Number(stockItem.item_qty) || 0;
                var maxVal = Math.ceil(qty * 2.10);

                var newRow = document.createElement('tr');
                newRow.classList.add('table-row');

                newRow.innerHTML =
                    '<td><input type="text" class="form-control" name="dyeing_taka_number[]" readonly value="' + rowNumber + '"></td>' +
                    '<td><input type="text" class="form-control" name="insp_taka_number[]" readonly value="' + (stockItem.insp_taka_number || '') + '"></td>' +
                    '<td><input type="text" class="form-control greige_item_qty" name="greige_item_qty[]" readonly value="' + qty + '"></td>' +
                    '<td><input type="text" class="form-control output_quan_break_size" name="output_quan_break_size[]" value="" oninput="calculateOutputSize(this)"></td>' +
                    '<td><input type="number" min="0" step="0.01" max="' + maxVal + '" class="form-control output_quan_size" name="output_quan_size[]" value="0"></td>' +
                    '<td><input type="number" min="0" step="0.01" class="form-control reject_quan_size" name="reject_quan_size[]" value="0"></td>' +
                    '<td><select name="fabric_fault_id[]" class="form-control">' + (typeof fabricFaultOptions !== 'undefined' ? fabricFaultOptions : '') + '</select></td>' +
                    '<td><input type="number" step="0.01" min="0" class="form-control shrinkage_quan_size" name="shrinkage_quan_size[]" value="0"></td>';

                $tableBody.append(newRow);
                rowNumber++;
            });

            if (typeof updateTotalOutput === 'function') {
                updateTotalOutput();
            }

            if (typeof updateTotalGreigeItemQty === 'function') {
                updateTotalGreigeItemQty();
            }

            if (typeof updateTotalReject === 'function') {
                updateTotalReject();
            }

            var machineName = payload && (payload.machineName || payload.MachineName) ? (payload.machineName || payload.MachineName) : null;
            var machineId   = payload && (payload.machineId || payload.MachineId || payload.machine_id) ? (payload.machineId || payload.MachineId || payload.machine_id) : '';

            $("#MachineNameD").text(machineName ? machineName : 'Not allocated');
            $("#machineIdD").val(machineId);

            var reqProIds = payload && payload.reqProIds ? payload.reqProIds : '';
            $("#reqProIdsDieing").val(reqProIds);
        },

        error: function(xhr, status, error)
        {
            console.error("AJAX error:", status, error);
            console.error("Server response:", xhr.responseText);

            var res = null;

            try {
                res = xhr.responseJSON || JSON.parse(xhr.responseText);
            } catch (e) {
                res = null;
            }

            var errorMessage = "Unable to fetch warehouse stock. Try again.";

            if (res && (res.message || res.message_hi)) {
                errorMessage = res.message_hi || res.message;
            }

            $("#dyeing_workRequirement").html('<div class="alert alert-danger small">' + errorMessage + '</div>');

            $('#' + tableId + ' tbody').html('');

            $("#MachineNameD").text('Not allocated');
            $("#machineIdD").val('');
            $("#reqProIdsDieing").val('');

            if (typeof updateTotalOutput === 'function') {
                updateTotalOutput();
            }

            if (typeof updateTotalGreigeItemQty === 'function') {
                updateTotalGreigeItemQty();
            }

            if (typeof updateTotalReject === 'function') {
                updateTotalReject();
            }
        }
    });
}

/* ==================== 16. fetchWarehouseItemStockCoating ==================== */
function fetchWarehouseItemStockCoating(lotNumber, workOrderId, tableId)
{
    if (!lotNumber || !workOrderId) {
        $("#coating_workRequirement").html('');
        return;
    }

    var siteUrl = "{{ url('/') }}";

    // New request start hote hi old message clear kar do
    $("#coating_workRequirement").html('');

    jQuery.ajax({
        type: "GET",
        url: siteUrl + "/ajax_script/getWarehouseItemStock",
        data: {
            "_token": "{{ csrf_token() }}",
            "lot_number": lotNumber,
            "dyeing_ins_work_order_id": workOrderId
        },
        cache: false,

        success: function(response)
        {
            console.log("Raw server response (success):", response);

            var payload = null;

            try {
                payload = (typeof response === 'string') ? JSON.parse(response) : response;
            } catch (e) {
                console.error("Failed to parse response JSON:", e);
                payload = response;
            }

            // Success aaya matlab purana error/message clear
            $("#coating_workRequirement").html('');

            var $tableBody = $('#' + tableId + ' tbody');
            $tableBody.html('');

            // Agar backend success response mein message bhej raha hai
            if (payload && (payload.message || payload.message_hi))
            {
                $("#coating_workRequirement").html('<div class="alert alert-info small">' + (payload.message_hi || payload.message) + '</div>');

                var nameFallback = payload.machineName || payload.MachineName || null;
                var idFallback   = payload.machineId || payload.MachineId || payload.machine_id || '';

                $("#MachineNameC").text(nameFallback ? nameFallback : 'Not allocated');
                $("#machineIdC").val(idFallback);
                $("#reqProIdsC").val('');

                return;
            }

            var stockItems = [];

            if (Array.isArray(payload))
            {
                stockItems = payload;
            }
            else if (payload && Array.isArray(payload.stockItems))
            {
                stockItems = payload.stockItems;
            }
            else if (payload && payload.data && Array.isArray(payload.data))
            {
                stockItems = payload.data;
            }

            var rowNumber = 1;

            stockItems.forEach(function(stockItem)
            {
                var qty = Number(stockItem.item_qty) || 0;
                var maxVal = Math.ceil(qty * 3.30);

                var newRow = document.createElement('tr');
                newRow.classList.add('table-row');

                newRow.innerHTML =
                    '<td><input type="text" class="form-control" name="dyeing_taka_number[]" readonly value="' + rowNumber + '"></td>' +
                    '<td><input type="text" class="form-control" name="insp_taka_number[]" readonly value="' + (stockItem.insp_taka_number || '') + '"></td>' +
                    '<td><input type="text" class="form-control greige_item_qty" name="greige_item_qty[]" readonly value="' + qty + '"></td>' +
                    '<td><input type="text" class="form-control output_quan_break_size" name="output_quan_break_size[]" value="" oninput="calculateOutputSize(this)"></td>' +
                    '<td><input type="number" min="0" step="0.01" max="' + maxVal + '" class="form-control output_quan_size" name="output_quan_size[]" value="0"></td>' +
                    '<td><input type="number" min="0" step="0.01" class="form-control shrinkage_quan_size" name="shrinkage_quan_size[]" value="0"></td>';

                $tableBody.append(newRow);
                rowNumber++;
            });

            if (typeof updateTotalOutput === 'function') {
                updateTotalOutput();
            }

            if (typeof updateTotalGreigeItemQty === 'function') {
                updateTotalGreigeItemQty();
            }

            var machineName = payload && (payload.machineName || payload.MachineName) ? (payload.machineName || payload.MachineName) : null;
            var machineId   = payload && (payload.machineId || payload.MachineId || payload.machine_id) ? (payload.machineId || payload.MachineId || payload.machine_id) : '';

            $("#MachineNameC").text(machineName ? machineName : 'Not allocated');
            $("#machineIdC").val(machineId);

            var reqProIds = payload && payload.reqProIds ? payload.reqProIds : '';
            $("#reqProIdsC").val(reqProIds);
        },

        error: function(xhr, status, error)
        {
            console.error("AJAX error:", status, error);
            console.error("Server response:", xhr.responseText);

            var res = null;

            try {
                res = xhr.responseJSON || JSON.parse(xhr.responseText);
            } catch (e) {
                res = null;
            }

            var errorMessage = "Unable to fetch warehouse stock. Try again.";

            if (res && (res.message || res.message_hi)) {
                errorMessage = res.message_hi || res.message;
            }

            $("#coating_workRequirement").html('<div class="alert alert-danger small">' + errorMessage + '</div>');

            $('#' + tableId + ' tbody').html('');

            $("#MachineNameC").text('Not allocated');
            $("#machineIdC").val('');
            $("#reqProIdsC").val('');

            if (typeof updateTotalOutput === 'function') {
                updateTotalOutput();
            }

            if (typeof updateTotalGreigeItemQty === 'function') {
                updateTotalGreigeItemQty();
            }
        }
    });
}

/* ==================== 17. calculateOutputSize ==================== */
function calculateOutputSize(element) {
    const breakSizeInput = element.value.trim();
    const sum = breakSizeInput.split('+').reduce((acc, val) => acc + (parseFloat(val.trim()) || 0), 0);
    const outputSizeInput = element.parentElement.nextElementSibling.querySelector('.output_quan_size');
    if (outputSizeInput) {
        outputSizeInput.value = sum.toFixed(2);
    }
    updateTotalOutput();
}

/* ==================== 18. updateTotalOutput ==================== */
function updateTotalOutput() {
    const outputFields = document.querySelectorAll('.output_quan_size');
    let total = 0;
    outputFields.forEach(field => {
        const value = parseFloat(field.value) || 0;
        total += value;
    });
    document.getElementById('totalOutput').textContent = total.toFixed(2);
    document.getElementById('totalOutputt').textContent = total.toFixed(2);
}

/* ==================== 19. updateTotalReject ==================== */
function updateTotalReject()
{
    const rejectFields = document.querySelectorAll('.reject_quan_size');
    let total = 0;
    rejectFields.forEach(field => {
        const value = parseFloat(field.value) || 0;
        total += value;
    });
	console.log("Total Reject:", total); // अभी test के लिए
	document.getElementById('totalRejectOutputt').textContent = total.toFixed(2);
    // Update any matching total elements if they exist (mirrors pattern used for outputs/greige)
    const el1 = document.getElementById('totalReject');
    const el2 = document.getElementById('totalRejectt');
    if (el1) el1.textContent = total.toFixed(2);
    if (el2) el2.textContent = total.toFixed(2);
}

/* ==================== 20. updateTotalGreigeItemQty ==================== */
function updateTotalGreigeItemQty() {
    const greigeItemQtyFields = document.querySelectorAll('.greige_item_qty');
    let total = 0;
    greigeItemQtyFields.forEach(field => {
        const value = parseFloat(field.value) || 0;
        total += value;
    });
    document.getElementById('toGreigeItemQty').textContent = total.toFixed(2);
    document.getElementById('toGreigeItemQtyy').textContent = total.toFixed(2);
}

/* ==================== 21. events ==================== */
document.addEventListener('input', function(event) {
    if (event.target.classList.contains('output_quan_break_size')) {
        calculateOutputSize(event.target);
    } else if (event.target.classList.contains('output_quan_size')) {
        updateTotalOutput();
    } else if (event.target.classList.contains('greige_item_qty')) {
        updateTotalGreigeItemQty();
    }
    else if (event.target.classList.contains('reject_quan_size')) { // ADDED: new line
        updateTotalReject(); // ADDED: call reject total function
    }
});

/* ==================== 22. DelWoProcess ==================== */
let deleteId = null;
function DelWoProcess(id) {
  deleteId = id; // Store the ID for use after confirmation
  $('#deleteModal').modal('show'); // Show the modal
}

$('#confirmDelBtn').on('click', function() {
  var siteUrl = "{{ url('/') }}";

  jQuery.ajax({
	type: "GET",
	url: siteUrl + '/ajax_script/deleteWorkOrder',
	data: {
	  "_token": "{{ csrf_token() }}",
	  "FId": deleteId
	},
	cache: false,
	success: function(response) {
	  $("#Mid" + deleteId).hide();
	  alert("Work order deleted successfully.");
	  $('#deleteModal').modal('hide'); // Hide the modal
	},
	error: function(xhr, status, error) {
	  alert("An error occurred: " + error);
	  $('#deleteModal').modal('hide'); // Hide the modal
	}
  });
});

/* ==================== 23. ReActivateProcess ==================== */
let activateId = null;

	function ReActivateProcess(id) {
	  activateId = id; // Store the ID for use after confirmation
	  $('#activateModal').modal('show'); // Show the modal
	}

	$('#confirmActivateBtn').on('click', function() {
	  var siteUrl = "{{ url('/') }}";

	  jQuery.ajax({
		type: "GET",
		url: siteUrl + '/ajax_script/activateWorkOrder',
		data: {
		  "_token": "{{ csrf_token() }}",
		  "FId": activateId
		},
		cache: false,
		success: function(response) {
		  $("#Mid" + activateId).hide();
		  alert("Work order reactivated successfully.");
		  $('#activateModal').modal('hide'); // Hide the modal
		},
		error: function(xhr, status, error) {
		  alert("An error occurred: " + error);
		  $('#activateModal').modal('hide'); // Hide the modal
		}
	  });
	});

/* ==================== 24. updateDyeingProcess ==================== */
function updateDyeingProcess()
{
	var workStatusSelect = document.getElementById("weaving_insp_work_status_process");
	var dyeingProcessSelect = document.getElementById("weaving_insp_dyeing_process");

	// If insp_work_status_process is selected as "Yes"
	if (workStatusSelect.value === "Yes") {
		dyeingProcessSelect.value = "Yes";
	} else {
		dyeingProcessSelect.value = "No"; // Set a default value if not "Yes"
	}
}

/* ==================== 25. updateCoatingProcess ==================== */
function updateCoatingProcess() {
        var workStatusSelect = document.getElementById("dyeing_insp_work_status_process");
        var dyeingProcessSelect = document.getElementById("dyeing_insp_coating_process");

        // If insp_work_status_process is selected as "Yes"
        if (workStatusSelect.value === "Yes") {
            dyeingProcessSelect.value = "Yes";
        } else {
            dyeingProcessSelect.value = "No"; // Set a default value if not "Yes"
        }
    }

/* ==================== 26. selectWorkStatus, gatePass ==================== */
function selectWorkStatus(element) {
    var value = (typeof element === 'string') ? element : $(element).val();
    var $scope = (typeof element === 'string') ? $(document) : $(element).closest('form');
    var $rows = $scope.find('.js-work-status-process, .js-work-status-reason, #WorkStatusProcess, #WorkStatusProcessReason');

    if (value === 'Defective') {
        $rows.show();
    } else {
        $rows.hide();
    }
}

function gatePass(value) {
        if (value === 'stock') {
            var siteUrl = "{{url('/')}}";
            var Id = Base64.encode($("#ins_work_order_id").val());
            var pageUrl = siteUrl + '/' + "print-workorder-gatepass" + '/' + Id;
            $("#ItemGatePass").html('<div class="i-check"> <a target="_blank" href=' + pageUrl + ' class="btn btn-success btn-xs">Gatepass</a></div>').show();
        } else if (value === 'reprocess') {
            $("#ItemGatePass").hide();
        }
    }

/* ==================== 27. events ==================== */
$(function() {
  $("#from_date, #to_date").datepicker({
	dateFormat: "dd-mm-yy",
	changeMonth: true,
	changeYear: true,
	autoclose: true,
  });
});

/* ==================== 28. split, extractLast ==================== */
var siteUrl = "{{url('/')}}";

  function split(val) {
    return val.split(/,\s*/);
  }

  function extractLast(term) {
    return split(term).pop();
  }

  $("#cus_search")
    .on("keydown", function(event) {
      if (event.keyCode === $.ui.keyCode.TAB && $(this).autocomplete("instance").menu.active) {
        event.preventDefault();
      }
    })
    .autocomplete({
      minLength: 3,
      source: function(request, response) {
        $.getJSON(siteUrl + "/list_customer", {
          term: extractLast(request.term)
        }, response);
      },
      focus: function() {
        return false;
      },
      select: function(event, ui) {
        var terms = split(this.value);
        var ids = split($("#individual_id").val());

        // remove current input
        terms.pop();
        ids.pop();

        // add the selected item
        terms.push(ui.item.name);
        ids.push(ui.item.id);

        // add placeholder to get the comma-and-space at the end
        terms.push("");
        ids.push("");

        this.value = terms.join(", ");
        $("#individual_id").val(ids.join(","));
        return false;
      }
    }).autocomplete("instance")._renderItem = function(ul, item) {
      return $("<li>")
        .append("<div>" + item.name + "</div>")
        .appendTo(ul);
    };

/* ==================== 29. events ==================== */
$("#item_search").autocomplete({
        minLength: 0,
        source: siteUrl + '/' + "fabric_list_item",
        focus: function(event, ui) {
          if (ui.item.part_number != '') {
            $("#item_search").val(ui.item.item_name);
            //$( "#product_name" ).val( ui.item.item_name + ' ' + ui.item.item_code );
          } else {
            $("#product_name").val(ui.item.item_name);
          }
          return false;
        },
        select: function(event, ui) {
          if (ui.item.part_number != '') {
            $("#product_name").val(ui.item.item_name);
            //$( "#product_name" ).val( ui.item.item_name + ' ' + ui.item.item_code);
          } else {
            $("#product_name").val(ui.item.item_name);
          }
          return false;
        }
      })
      .autocomplete("instance")._renderItem = function(ul, item) {
        return $("<li>")
          //.append( "<div>" + item.item_name + " </div>" )
          .append("<div>" + item.item_name + " </div>")
          .appendTo(ul);
      };
      //console.log($("#ordNumSearch").val());
      $("#ordNumSearch").autocomplete({
        minLength: 0,
        source: siteUrl + '/' + "find_saleOrderNumer",
        focus: function(event, ui) {
          //var ordNumSearch=$("#ordNumSearch").val();
          $( "#ordNumSearch" ).val( ui.item.sale_order_number);
		      return false;
        },
        select: function(event, ui) {
          $("#ordNumSearch").val(ui.item.sale_order_number);
          //$("#qsaleOrderId").val(ui.item.sale_order_id);
          return false;
        }
      })
      .autocomplete("instance")._renderItem = function(ul, item) {
        return $("<li>")
          //.append( "<div>" + item.item_name + " </div>" )
          .append("<div>" + item.sale_order_number + " </div>")
          .appendTo(ul);
      };

/* ==================== 30. CoatingInspProcess ==================== */
var siteUrl = "{{url('/')}}";

function CoatingInspProcess(Id) {
  jQuery.ajax({
	type: "GET",
	url: siteUrl + '/' + "ajax_script/getWorkOrderDetails",
	data: {
	  "_token": "{{ csrf_token() }}",
	  "FId": Id,
	},
	cache: false,
	success: function(response) {
	  response = JSON.parse(response);
	  console.log(response);

	  //alert(response.workRequirement);
	  $("#coating_ins_item_id").val(response.itemId);
	  $("#coating_ins_work_order_id").val(response.workOrdId);
	  $("#coating_ItemName").html(response.ItemName);
	  $("#coating_InsoutputNext").html(response.outputNextPro);
	  $("#coating_InsoutputUnit").html(response.outputUnit);
	  $("#coating_processtext").html(response.processtext);
	  $("#coating_outputUnitType").html(response.outputUnitType);
	  $("#coating_workRequirement1").html(response.workRequirement);
	  $("#coating_insp_work_warehouseId").html(response.warehouses);
	  // $("#MachineNameC").html(response.MachineName);
	  $("#inspTakaNumberC").html(response.inspTakaNumber);

	}
  });

  $('#CoatingInspProcessPop').modal({
	backdrop: 'static',
	keyboard: false
  });
}

/* ==================== 31. DyeingInspProcess ==================== */
var siteUrl = "{{url('/')}}";

    function DyeingInspProcess(Id) {
      jQuery.ajax({
        type: "GET",
        url: siteUrl + '/' + "ajax_script/getWorkOrderDetails",
        data: {
          "_token": "{{ csrf_token() }}",
          "FId": Id,
        },
        cache: false,
        success: function(response) {
          response = JSON.parse(response);
          console.log(response);

          //alert(response.workRequirement);
          $("#dyeing_ins_item_id").val(response.itemId);
          $("#dyeing_ins_work_order_id").val(response.workOrdId);
          $("#dyeing_ItemName").html(response.ItemName);
          $("#dyeing_InsoutputNext").html(response.outputNextPro);
          $("#dyeing_InsoutputUnit").html(response.outputUnit);
          $("#dyeing_processtext").html(response.processtext);
          $("#dyeing_outputUnitType").html(response.outputUnitType);
          $("#dyeing_workRequirement1").html(response.workRequirement);
          $("#dyeing_insp_work_warehouseId").html(response.warehouses);
		  // $("#MachineNameD").html(response.MachineName);
		  // $("#machineIdD").val(response.machineId);
          $("#inspTakaNumberD").html(response.inspTakaNumber);

        }
      });

      $('#DyeingInspProcessPop').modal({
        backdrop: 'static',
        keyboard: false
      });
    }

/* ==================== 32. WeavingInspProcess ==================== */
var siteUrl = "{{url('/')}}";

    function WeavingInspProcess(Id)
	{
      jQuery.ajax({
        type: "GET",
        url: siteUrl + '/' + "ajax_script/getWorkOrderDetails",
        data: {
          "_token": "{{ csrf_token() }}",
          "FId": Id,
        },
        cache: false,
        success: function(response)
		{
			response = JSON.parse(response);
			console.log(response);

			//alert(response.workRequirement);
			$("#weav_ins_item_id").val(response.itemId);
			$("#weav_ins_work_order_id").val(response.workOrdId);
			$("#weav_ItemName").html(response.ItemName);
			$("#weav_InsoutputNext").html(response.outputNextPro);
			$("#weav_InsoutputUnit").html(response.outputUnit);
			$("#weav_processtext").html(response.processtext);
			$("#weav_outputUnitType").html(response.outputUnitType);
			$("#weav_workRequirement").html(response.workRequirement);
			$("#insp_work_warehouseId").html(response.warehouses);
			$("#MachineName").html(response.MachineName);
			$("#inspTakaNumber").html(response.inspTakaNumber);
			$("#insp_epi").val(response.inspEpi);
			$("#insp_ppi").val(response.inspPpi);
			$("#insp_width_weav").val(response.inspWidth);
			$("#insp_gsm_weav").val(response.inspGsm);

        }
      });

      $('#WeavingInspProcessPop').modal({
        backdrop: 'static',
        keyboard: false
      });
    }

/* ==================== 33. InspectionProcess ==================== */
var siteUrl = "{{url('/')}}";

    function InspectionProcess(Id) {
      jQuery.ajax({
        type: "GET",
        url: siteUrl + '/' + "ajax_script/getWorkOrderDetails",
        data: {
          "_token": "{{ csrf_token() }}",
          "FId": Id,
        },
        cache: false,
        success: function(response) {
          response = JSON.parse(response);
          console.log(response);
          // alert(response.warehouses);
          //alert(response.workRequirement);
          $("#ins_item_id").val(response.itemId);
          $("#ins_work_order_id").val(response.workOrdId);
          $("#ItemName").html(response.ItemName);
          $("#InsoutputNext").html(response.outputNextPro);
          $("#InsoutputUnit").html(response.outputUnit);
          $("#processtext").html(response.processtext);
          $("#outputUnitType").html(response.outputUnitType);
          $("#insp_work_warehouse_id").html(response.warehouses);
        }
      });
      $('#InspectionProcessPop').modal({
        backdrop: 'static',
        keyboard: false
      });
    }

/* ==================== 34. StartProcess, StartProcessWev ==================== */
var siteUrl = "{{url('/')}}";
function StartProcess(Id)
	{
      jQuery.ajax({
        type: "GET",
        url: siteUrl + '/' + "ajax_script/getWorkOrderDetails",
        data: {
          "_token": "{{ csrf_token() }}",
          "FId": Id,
        },
        cache: false,
        success: function(response)
		{
			response = JSON.parse(response);
			console.log(response);
			$("#itemId").val(response.itemId);
			$("#work_order_id").val(response.workOrdId);
			$("#ItemNameS").html(response.ItemName);
			$("#processNameId").html(response.processName);
			$("#RequestedItems").html(response.RequestedItems);
			// $("#machineId").html(response.options);
        }
      });
      $('#StartProcessPop').modal({
        backdrop: 'static',
        keyboard: false
      });
    }

	function StartProcessWev(Id)
	{
      jQuery.ajax({
        type: "GET",
        url: siteUrl + '/' + "ajax_script/getWorkOrderDetails",
        data: {
          "_token": "{{ csrf_token() }}",
          "FId": Id,
        },
        cache: false,
        success: function(response)
		{
			response = JSON.parse(response);
			console.log(response);
			$("#itemIdWev").val(response.itemId);
			$("#work_order_idWev").val(response.workOrdId);
			$("#ItemNameWev").html(response.ItemName);
			$("#processNameIdWev").html(response.processName);
			$("#RequestedItemsWev").html(response.RequestedItems);
			$("#machineIdWev").html(response.options);
        }
      });
      $('#StartProcessPopWev').modal({  backdrop: 'static',  keyboard: false });
    }

/* ==================== 35. events ==================== */
$("#colorSearch").autocomplete({
        minLength: 0,
        source: siteUrl + '/' + "find_saleDyeingColor",
        focus: function(event, ui) {
          $( "#colorSearch" ).val( ui.item.dyeing_color);
		      return false;
        },
        select: function(event, ui) {
          $("#colorSearch").val(ui.item.dyeing_color);
          return false;
        }
      })
      .autocomplete("instance")._renderItem = function(ul, item) {
        return $("<li>")
          .append("<div>" + item.dyeing_color + " </div>")
          .appendTo(ul);
      };

/* ==================== 36. CoatingPrintInspProcess ==================== */
var siteUrl = "{{url('/')}}";

  function CoatingPrintInspProcess(Id)
  {
    jQuery.ajax({
      type: "GET",
      url: siteUrl + '/' + "ajax_script/getWorkOrderDetails",
      data: {
        "_token": "{{ csrf_token() }}",
        "FId": Id,
      },
      cache: false,
      success: function(response) {
        response = JSON.parse(response);
        console.log(response);

        // set values into *_print IDs (names untouched)
        $("#coating_ins_item_id_print").val(response.itemId);
        $("#coating_ins_work_order_id_print").val(response.workOrdId);
        $("#coating_ItemName_print").html(response.ItemName);
        $("#coating_InsoutputNext_print")?.html ? $("#coating_InsoutputNext_print").html(response.outputNextPro) : null;
        $("#coating_InsoutputUnit_print")?.html ? $("#coating_InsoutputUnit_print").html(response.outputUnit) : null;
        $("#coating_processtext_print").html(response.processtext);
        $("#coating_outputUnitType_print")?.html ? $("#coating_outputUnitType_print").html(response.outputUnitType) : null;
        $("#coating_workRequirement_print1").html(response.workRequirement);
        // warehouses HTML (keeps <option> list) - ensure you pass proper html from backend
        $("#coating_insp_work_warehouseId_print").html(response.warehouses || '');
        // $("#MachineNameC_print").html(response.MachineName);
        $("#inspTakaNumberC_print").html(response.inspTakaNumber);

        // clear any existing rows/totals in this print table
        $('#myTableCoatedPrint tbody').html('<tr class="table-row2"> </tr>');
        $('#toGreigeItemQty_print').text('0');
        $('#totalOutput_print').text('0');
      }
    });

    $('#CoatingPrintInspProcessPop').modal({
      backdrop: 'static',
      keyboard: false
    });
  }

/* ==================== 37. fetchWarehouseItemStockCoatingPrint, updateTotalOutputForTable, updateTotalGreigeForTable, calculateOutputSizePrint ==================== */
function fetchWarehouseItemStockCoatingPrint(lotNumber, workOrderId, tableId) {
    if (!lotNumber || !workOrderId) return;

    var siteUrl = "{{ url('/') }}";
    jQuery.ajax({
        type: "GET",
        url: siteUrl + '/' + "ajax_script/getWarehouseItemStockPrint",
        data: {
            "_token": "{{ csrf_token() }}",
            "lot_number": lotNumber,
            "dyeing_ins_work_order_id": workOrderId,
        },
        cache: false,
        success: function(response) {
            console.log("Raw server response (success):", response);

            // normalize/parse payload
            let payload;
            try {
                payload = (typeof response === 'string') ? JSON.parse(response) : response;
            } catch (e) {
                console.error("Error parsing JSON response:", e);
                payload = response;
            }

            // show message (Hindi preferred) and clear table if server returned message
            if (payload && (payload.message || payload.message_hi)) {
                document.getElementById('coating_workRequirement_print').innerHTML =
                    '<button type="button" class="btn btn-info" disabled>' + (payload.message_hi || payload.message) + '</button>';

                const tbl = document.getElementById(tableId);
                if (tbl) {
                    const existingTbody = tbl.querySelector('tbody');
                    if (existingTbody) existingTbody.innerHTML = '';
                }

                // still set machine info if present
                const nameMsg = payload.machineName || payload.MachineName || null;
                const idMsg   = payload.machineId   || payload.MachineId   || payload.machine_id || '';
                $("#MachineNameC_print").text(nameMsg ? nameMsg : 'Not allocated');
                $("#machineIdC_print").val(idMsg);
                return;
            }

            // Resolve stockItems from various possible payload shapes
            let stockItems = [];
            if (Array.isArray(payload)) {
                stockItems = payload;
            } else if (payload && Array.isArray(payload.stockItems)) {
                stockItems = payload.stockItems;
            } else if (payload && Array.isArray(payload.data)) {
                stockItems = payload.data;
            }

            // find/create table tbody
            const table = document.getElementById(tableId);
            if (!table) {
                console.error("Table not found:", tableId);
                return;
            }
            let tableBody = table.querySelector('tbody');
            if (!tableBody) {
                tableBody = document.createElement('tbody');
                table.appendChild(tableBody);
            }
            tableBody.innerHTML = '';

            // populate rows
            let rowNumber = 1;
            (stockItems || []).forEach(stockItem => {
                const qty = Number(stockItem.item_qty) || 0;
                const maxVal = Math.ceil(qty * 1.30);

                const newRow = document.createElement('tr');
                newRow.classList.add('table-row2');
                newRow.innerHTML =
                    '<td><input type="text" class="form-control" name="dyeing_taka_number[]" readonly value="' + rowNumber + '"></td>' +
                    '<td><input type="text" class="form-control" name="insp_taka_number[]" readonly value="' + (stockItem.insp_taka_number || '') + '"></td>' +
                    '<td><input type="text" class="form-control greige_item_qty" name="greige_item_qty[]" readonly value="' + (qty || '') + '"></td>' +
                    '<td><input type="text" class="form-control output_quan_break_size" name="output_quan_break_size[]" value="" oninput="calculateOutputSize(this)"></td>' +
                    '<td><input type="number" min="0" step="0.01" max="' + maxVal + '" class="form-control output_quan_size" name="output_quan_size[]" value="0"></td>' +
                    '<td><input type="number" min="0" step="0.01" class="form-control shrinkage_quan_size" name="shrinkage_quan_size[]" value="0"></td>';
                tableBody.appendChild(newRow);
                rowNumber++;
            });

            // update totals scoped to this table (print-specific functions)
            if (typeof updateTotalOutputForTable === 'function') updateTotalOutputForTable(tableId, '_print');
            if (typeof updateTotalGreigeForTable === 'function') updateTotalGreigeForTable(tableId, '_print');

            // set machine info (safe fallbacks, multiple key variants)
            const machineName = (payload && (payload.machineName || payload.MachineName)) || null;
            const machineId   = (payload && (payload.machineId || payload.MachineId || payload.machine_id)) || '';

            $("#MachineNameC_print").text(machineName ? machineName : 'Not allocated');
            $("#machineIdC_print").val(machineId);
        },
        error: function(xhr, status, error) {
            console.error("AJAX error:", status, error);
            console.error("Server response:", xhr.responseText);

            // try parse server error JSON
            var res = null;
            try {
                res = xhr.responseJSON || JSON.parse(xhr.responseText);
            } catch (e) {
                res = null;
            }

            // clear/notify
            const table = document.getElementById(tableId);
            if (table) {
                const existingTbody = table.querySelector('tbody');
                if (existingTbody) existingTbody.innerHTML = '';
            }

            if (res && (res.message || res.message_hi)) {
                document.getElementById('coating_workRequirement_print').innerHTML =
                    '<button type="button" class="btn btn-danger" disabled>' + (res.message_hi || res.message) + '</button>';
            } else {
                document.getElementById('coating_workRequirement_print').innerHTML =
                    '<div class="alert alert-danger">Unable to fetch warehouse stock. Try again.</div>';
            }

            // clear machine info on error
            $("#MachineNameC_print").text('Not allocated');
            $("#machineIdC_print").val('');
        }
    });
}

// helper to update total output for a given table and suffix
function updateTotalOutputForTable(tableId, printSuffix) {
    var table = document.getElementById(tableId);
    if (!table) return;
    const outputs = table.querySelectorAll('.output_quan_size');
    let total = 0;
    outputs.forEach(f => { total += parseFloat(f.value) || 0; });

    // set print-specific total if exists
    var printId = tableId + (printSuffix || '') + '_totalOutput'; // e.g., myTableCoatedPrint_totalOutput (not used) - we used myTableCoatedPrint_totalOutput earlier in earlier suggestions
    // simpler: our tfoot ids are '<tableId>_totalOutput' OR we set fixed id 'totalOutput_print' in markup
    var totalElemPrint = document.getElementById('totalOutput_print');
    if (totalElemPrint) totalElemPrint.textContent = total.toFixed(2);

    // fallback: generic id (if present elsewhere)
    var totalElemGeneric = document.getElementById('totalOutput');
    if (totalElemGeneric) totalElemGeneric.textContent = total.toFixed(2);
}

// helper to update greige total for a given table and suffix
function updateTotalGreigeForTable(tableId, printSuffix) {
    var table = document.getElementById(tableId);
    if (!table) return;
    const greigeFields = table.querySelectorAll('.greige_item_qty');
    let total = 0;
    greigeFields.forEach(f => { total += parseFloat(f.value) || 0; });

    var greigeElemPrint = document.getElementById('toGreigeItemQty_print');
    if (greigeElemPrint) greigeElemPrint.textContent = total.toFixed(2);

    var greigeElemGeneric = document.getElementById('toGreigeItemQty');
    if (greigeElemGeneric) greigeElemGeneric.textContent = total.toFixed(2);
}

// keep existing calculate/handler behaviour but scoped where possible
function calculateOutputSizePrint(element) {
    const breakSizeInput = element.value.trim();
    const sum = breakSizeInput.split('+').reduce((acc, val) => acc + (parseFloat(val.trim()) || 0), 0);

    // try find sibling output_quan_size
    var td = element.closest('td');
    var nextTd = td ? td.nextElementSibling : null;
    var outputSizeInput = nextTd ? nextTd.querySelector('.output_quan_size') : null;
    if (!outputSizeInput) {
        var row = element.closest('tr');
        outputSizeInput = row ? row.querySelector('.output_quan_size') : null;
    }
    if (outputSizeInput) outputSizeInput.value = sum.toFixed(2);

    // update totals for the table containing this input (print)
    var table = element.closest('table');
    if (table) {
        updateTotalOutputForTable(table.id, '_print');
    }
}

document.addEventListener('input', function(event) {
    if (event.target.classList.contains('output_quan_break_size')) {
        calculateOutputSizePrint(event.target);
    } else if (event.target.classList.contains('output_quan_size')) {
        var table = event.target.closest('table');
        if (table) updateTotalOutputForTable(table.id, '_print');
    } else if (event.target.classList.contains('greige_item_qty')) {
        var table = event.target.closest('table');
        if (table) updateTotalGreigeForTable(table.id, '_print');
    }
});

/* ==================== 38. escapeHtml ==================== */
(function(){
  function escapeHtml(str) {
    if (str === null || str === undefined) return '';
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  // Event delegation for button (works for dynamically rendered rows)
  document.addEventListener('click', function(e) {
    const btn = e.target.closest('.beam-return-btn');
    if (!btn) return;
    const wprId = btn.getAttribute('data-wpr-id');
    const workOrderId = btn.getAttribute('data-work-order-id');
    // pass the updated table id (with 'beam')
    GetBeamReturnItems(wprId, workOrderId, 'beamReturnItemsTable');
  });

  // Toggle 'select all' checkbox
  window.toggleSelectAllBeam = function(masterCheckbox) {
    const checkboxes = document.querySelectorAll('#beamReturnItemsTable tbody input[type="checkbox"]');
    checkboxes.forEach(function(cb) {
      if (!cb.disabled) cb.checked = masterCheckbox.checked;
    });
  };

  // Validate at least one is selected (excluding disabled ones)
  window.validateBeamReturnForm = function(form) {
    const checked = Array.from(document.querySelectorAll('#beamReturnItemsTable tbody input[type="checkbox"]'))
      .some(function(cb) { return cb.checked && !cb.disabled; });
    if (!checked) {
      alert('Please select at least one item to return.');
      return false;
    }
    return true;
  };

  // Main AJAX loader
  window.GetBeamReturnItems = function(id, workOrderId, tableId) {
    if (!id) return;
    var modalwprId = document.getElementById('beamWprId');
    var modalworkOrderId = document.getElementById('beamChkworkOrderId');
    modalwprId.value = id;
    modalworkOrderId.value = workOrderId;

    var siteUrl = "<?php echo url('/'); ?>";
    var ajaxUrl = siteUrl + '/ajax_script/getBeamReturnItems';

    jQuery.ajax({
      type: "GET",
      url: ajaxUrl,
      data: {
        id: id,
        work_order_id: workOrderId,
        _token: "<?php echo csrf_token(); ?>"
      },
      dataType: 'json',
      cache: false,
      success: function(returnItems) {
        var tableBody = document.querySelector('#' + tableId + ' tbody');
        tableBody.innerHTML = '';

        if (!Array.isArray(returnItems)) {
          console.error('Expected array, got:', returnItems);
          return;
        }

        returnItems.forEach(function(item, index) {
          var tr = document.createElement('tr');

          var isDisabled = item.department_return_request && item.department_return_request.id ? true : false;

          var wareOutHidden = '<input type="hidden" class="form-control" name="ware_out_item_id[]" value="' + escapeHtml(item.id) + '">';
          var stockIdInput = '<input type="text" class="form-control" name="return_wis_id[]" readonly value="' + escapeHtml(item.wis_id) + '">';
          var takaInput = '<input type="text" class="form-control" name="return_insp_taka_number[]" readonly value="' + escapeHtml(item.insp_taka_number) + '">';
          var qtyInput = '<input type="text" class="form-control" name="received_item_qty[]" readonly value="' + escapeHtml(item.item_qty || '') + '">';
          var usedqtyInput = '<input type="text" class="form-control" Required name="used_item_qty[]" value="">';
          var returnqtyInput = '<input type="text" class="form-control" Required name="return_item_qty[]" value="">';

          var checkboxHtml = '<input type="checkbox" name="is_return[' + index + ']" value="1"' + (isDisabled ? ' disabled' : '') + '>';
          if (isDisabled) {
            checkboxHtml += '<input type="hidden" name="is_return[' + index + ']" value="0">';
          }

          tr.innerHTML = '<td>' + wareOutHidden + stockIdInput + '</td>' +
                         '<td>' + takaInput + '</td>' +
                         '<td>' + qtyInput + '</td>' +
                         '<td>' + usedqtyInput + '</td>' +
                         '<td>' + returnqtyInput + '</td>' +
                         '<td>' + checkboxHtml + '</td>';

          tableBody.appendChild(tr);
        });

        var selectAll = document.getElementById('beamSelectAll');
        if (selectAll) selectAll.checked = false;

        // show modal (use the updated modal id)
        jQuery('#beamReturnBeamModal').modal({ backdrop: 'static', keyboard: false });
        jQuery('#beamReturnBeamModal').modal('show');
      },
      error: function(xhr, status, error) {
        console.error("AJAX error:", status, error, xhr.responseText);
        alert('Failed to load return items. Check console for details.');
      }
    });
  };

})();

/* ==================== 39. findEditArea, findSelect, findStatus, findNameElement ==================== */
/*
  Unified machine edit/save JS
  - Handles both per-work-order editors (data-woid / beam-machine-*)
  - And per-work-process-row editors (data-id / beam-machine-* where id is the row id)
  - Tries 'beam-' prefixed IDs first (as in your HTML), falls back to non-prefixed IDs if needed.
*/
$(function(){
    // Ensure CSRF header
    $.ajaxSetup({
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
    });

    // Helper: find element by trying beam- prefix then fallback to no prefix
    function findEditArea(id){
        var sel = $('#beam-machine-edit-' + id);
        if(sel.length) return sel;
        sel = $('#machine-edit-' + id);
        return sel;
    }
    function findSelect(id){
        var sel = $('#beam-machine-select-' + id);
        if(sel.length) return sel;
        sel = $('#machine-select-' + id);
        return sel;
    }
    function findStatus(id){
        var sel = $('#beam-machine-status-' + id);
        if(sel.length) return sel;
        sel = $('#machine-status-' + id);
        return sel;
    }
    function findNameElement(woid){
        var sel = $('#beam-machine-name-' + woid);
        if(sel.length) return sel;
        sel = $('#machine-name-' + woid);
        return sel;
    }

    // OPEN editor (handles both classes used in markup)
    $(document).on('click', '.edit-machine-btn, .btn-edit-machine', function(e){
        e.preventDefault();
        var $btn = $(this);
        var rowId = $btn.data('id');     // per-row id (WorkProcessRequirement id)
        var woid  = $btn.data('woid');   // work-order id (for top-level editor)
        var targetId = rowId ? rowId : woid;

        // show the corresponding edit area (beam-machine-edit-<id> or fallback)
        var $editArea = findEditArea(targetId);
        if($editArea.length){
            $editArea.slideDown();
        }

        // hide the edit button that was clicked (cleaner UX)
        $btn.hide();

        // hide machine display label if it exists (per-row labels have class .machine-display-<id>)
        if(rowId){
            $('.machine-display-' + rowId).hide();
        } else if(woid){
            var $nameEl = findNameElement(woid);
            if($nameEl.length) $nameEl.hide();
        }

        // Copy woid onto save button for per-row editor if needed (some flows expect woid)
        if(woid){
            // find a save btn inside edit area and set data-woid
            $editArea.find('.save-machine-btn, .btn-save-machine').attr('data-woid', woid);
        }
    });

    // CANCEL editor
    $(document).on('click', '.cancel-machine-btn, .btn-cancel-machine', function(e){
        e.preventDefault();
        var $btn = $(this);
        var rowId = $btn.data('id');
        var woid  = $btn.data('woid');
        var targetId = rowId ? rowId : woid;

        // hide edit area
        var $editArea = findEditArea(targetId);
        if($editArea.length) $editArea.slideUp();

        // clear status text
        var $status = findStatus(targetId);
        if($status.length) $status.text('');

        // show appropriate edit button
        if(rowId){
            $('.edit-machine-btn[data-id="' + rowId + '"]').show();
            $('.machine-display-' + rowId).show();
        } else if(woid){
            $('.btn-edit-machine[data-woid="' + woid + '"]').show();
            var $nameEl = findNameElement(woid);
            if($nameEl.length) $nameEl.show();
            $('#beam-machine-edit-error-' + woid).hide().text('');
        }
    });

    // SAVE handler (single handler for both save button variants)
    $(document).on('click', '.save-machine-btn, .btn-save-machine', function(e){
        e && e.preventDefault();
        var $btn = $(this);

        // detect whether this is per-row (has data-id) or per-wo (only data-woid)
        var rowId = $btn.data('id');    // WorkProcessRequirement id
        var woid  = $btn.data('woid');  // work order id

        // select element depends on id
        var $select;
        if(rowId){
            $select = findSelect(rowId);
        } else if(woid){
            $select = findSelect(woid);
        } else {
            // nothing to do
            console.warn('Save clicked but no id/woid present on button');
            return;
        }

        var machineId = $select.length ? $select.val() : '';

        // UX
        var $status = findStatus(rowId ? rowId : woid);
        if($status.length){
            $status.css('color','').text('Saving...');
        }
        $btn.prop('disabled', true);

        // Decide which endpoint to call:
        // - per-row update: route('workorder.updateMachine') expects { id, dyeing_machine_id, woid? }
        // - per-workorder update: route('workorder.updateMachineWo') expects { work_order_id, machine_id }
        if(rowId){
            // per-row update
            $.ajax({
                url: '{{ route("workorder.updateMachine") }}',
                method: 'POST',
                dataType: 'json',
                data: {
                    id: rowId,
                    dyeing_machine_id: machineId,
                    woid: woid || ''  // optional, pass if available
                }
            })
            .done(function(res){
                if(res && res.status === 'success'){
                    // update per-row label element (.machine-display-<rowId>)
                    var labelEl = $('.machine-display-' + rowId);
                    var display = res.machine_name ? res.machine_name : (res.machine_id ? res.machine_id : 'Machine Not Set');
                    var escaped = $('<div/>').text(display).html();

                    // update classes
                    labelEl.removeClass('label-danger label-success label-primary label-info label-warning label-default');
                    if(res.machine_id){
                        labelEl.addClass('label-success');
                    } else {
                        labelEl.addClass('label-danger');
                    }
                    labelEl.attr('title', display);
                    labelEl.html('<i class="fa fa-cog"></i> ' + escaped);

                    // close editor + show label
                    if($status.length){
                        $status.text('Saved').fadeOut(1200, function(){ $(this).text('').show(); });
                    }
                    var $editArea = findEditArea(rowId);
                    if($editArea.length){
                        $editArea.slideUp(function(){ $('.edit-machine-btn[data-id="' + rowId + '"]').show(); labelEl.show(); });
                    }
                } else {
                    var msg = (res && res.message) ? res.message : 'Update failed';
                    if($status.length) $status.css('color','red').text(msg);
                }
            })
            .fail(function(xhr){
                var msg = 'Error saving.';
                if(xhr && xhr.responseJSON){
                    if(xhr.responseJSON.message) msg = xhr.responseJSON.message;
                    else if(xhr.responseJSON.errors){
                        var key = Object.keys(xhr.responseJSON.errors)[0];
                        msg = xhr.responseJSON.errors[key][0];
                    }
                }
                if($status.length) $status.css('color','red').text(msg);
            })
            .always(function(){
                $btn.prop('disabled', false);
            });

        } else {
            // per-workorder update
            $.post('{{ route("workorder.updateMachineWo") }}', {
                work_order_id: woid,
                machine_id: machineId
            })
            .done(function(res){
                if(res && res.status === 'success'){
                    // update name element if exists
                    var $nameEl = findNameElement(woid);
                    if($nameEl.length){
                        var txt = res.machine_name || '';
                        $nameEl.text(txt);
                        $nameEl.show();
                    }
                    // update edit button data if any
                    $('.btn-edit-machine[data-woid="' + woid + '"]').data('machine-id', res.machine_id);

                    // hide editor and show edit button
                    var $editArea = findEditArea(woid);
                    if($editArea.length){
                        $editArea.hide();
                    }
                    $('.btn-edit-machine[data-woid="' + woid + '"]').show();
                } else {
                    var msg = (res && res.message) ? res.message : 'Update failed';
                    $('#beam-machine-edit-error-' + woid).show().text(msg);
                }
            })
            .fail(function(xhr){
                var msg = 'Error saving.';
                if(xhr && xhr.responseJSON){
                    if(xhr.responseJSON.message) msg = xhr.responseJSON.message;
                    else if(xhr.responseJSON.errors){
                        var key = Object.keys(xhr.responseJSON.errors)[0];
                        msg = xhr.responseJSON.errors[key][0];
                    }
                }
                $('#beam-machine-edit-error-' + woid).show().text(msg);
            })
            .always(function(){
                $btn.prop('disabled', false);
            });
        }
    });

}); // end ready

/* ==================== 40. CloseWorkProcess ==================== */
let closewoId = null;

function CloseWorkProcess(id) {
  closewoId = id;
  $('#closeActivateModal').modal('show');
}

// Confirm button handler
$('#confirmCloseWOBtn').on('click', function() {
  var $btn = $(this);

  if (!closewoId) {
    alert('Invalid work order id.');
    return;
  }

  // disable to prevent double-clicks
  if ($btn.data('processing')) return;
  $btn.data('processing', true);
  $btn.prop('disabled', true).text('Processing...');

  jQuery.ajax({
    type: "POST",
    url: "{{ route('ajax.closeWorkOrder') }}",
    data: {
      "_token": "{{ csrf_token() }}",
      "FId": closewoId
    },
    cache: false,
    success: function(response) {
      if (response && response.success) {
        // hide the UI element for this work order if present
        var $row = $("#Mid" + closewoId);
        if ($row.length) {
          $row.hide();
        }
        alert("Work order closed successfully.");
      } else {
        // server responded but with success=false
        alert(response.message || "Failed to close work order.");
      }
      $('#closeActivateModal').modal('hide');
    },
    error: function(xhr, status, error) {
      var msg = "An error occurred.";
      try {
        if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
        else if (xhr.responseText) msg = xhr.responseText;
      } catch (e) {}
      alert("Error: " + msg);
      $('#closeActivateModal').modal('hide');
    },
    complete: function() {
      // re-enable button (or you may keep it disabled to prevent retry)
      $btn.data('processing', false);
      $btn.prop('disabled', false).text('OK');
    }
  });
});

window.addEventListener('load', function() {
  var timeText = document.getElementById('pageLoadTimeText');
  if (!timeText || !window.performance) return;

  setTimeout(function() {
    var navEntry = performance.getEntriesByType && performance.getEntriesByType('navigation')[0];
    var loadMs = navEntry ? navEntry.loadEventEnd : (performance.timing.loadEventEnd - performance.timing.navigationStart);

    if (loadMs && loadMs > 0) {
      timeText.textContent = (loadMs / 1000).toFixed(2) + ' sec';
    } else {
      timeText.textContent = 'N/A';
    }
  }, 0);
});
</script>

</body>
</html>
