<?php

$current_page = isset($_GET['page']) ? $_GET['page'] : 1;
$userId = Auth::id();
// echo "<pre>"; print_r($dataMas); exit;

?>
<!DOCTYPE html>
<html lang="en">
<head>@include('frontend.common.head', ['pageTitle' => 'Work Orders | Loomexa'])
<meta name="csrf-token" content="{{ csrf_token() }}">


</head><body class="hold-transition sidebar-mini workorder-page">
<!-- Site wrapper -->
<div class="wrapper"> @include('frontend.common.header')
  <div class="content-wrapper">
    <section class="content">
      <div class="row">
        <div class="col-sm-12"> {!! display_message('message') !!}
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
										<span class="caret pull-right wo-filter-caret"></span>
									</button>

									<ul class="dropdown-menu process-filter-menu" aria-labelledby="processDropdown">
										<?php
                                            $search_process_id = is_array($search_process_id) ? array_map('intval', $search_process_id) : [];
foreach ($processI as $process) {
    ?>
											<li role="presentation">
												<label>
													<input type="checkbox" name="search_process_id[]" value="<?= $process->id; ?>" <?= (! empty($search_process_id) && in_array($process->id, $search_process_id)) ? 'checked' : ''; ?>>
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
											<?= $year.'-'.($year + 1) ?>
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
						
						<div id="pageLoadTimeBox" class="wo-page-load-time">
						<span class="wo-page-load-icon">⏱️</span>
						<span>Load Time:</span>
						<span id="pageLoadTimeText" class="wo-page-load-text">Calculating...</span>
						</div>
					</div> 

                </div>

                <table id="dataTableExample1" class="table table-bordered table-striped table-hover table-condensed workorder-list-table">
                  <thead>
                    <tr class="info">
                      <th class="wo-col-5">W.O.No.</th>
                      <th class="wo-col-6">S.O No.</th>
                      <th class="wo-col-10">Item</th>
                      <th class="wo-col-9">Customer</th>
                      <th class="wo-col-10">Process</th>
                      <th class="wo-col-4">Priority</th>
                      <th class="wo-col-3">Cut</th>
                      <th class="wo-col-3">Pcs</th>
                      <th class="wo-col-5">Meter</th>
                      <th class="wo-col-14">Requirement</th>
                      <th class="wo-col-10">Status</th>
                      <th class="wo-col-11">Print</th>
                      <th class="wo-col-10">Action</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php

                    foreach ($dataWI as $data) {
                        // echo "<pre>"; print_r($data);    exit;
                        $parentWorkOrderId = $data->parent_work_order_id;
                        $printPosition = $data->print_position;
                        $processType = $data->process_type;
                        $proTypeId = $data->process_type_id;
                        $WorkRequireReqAccepted = $data->is_work_require_request_accepted;
                        $ChildId = $data->parent_work_order_id;
                        $getChildLot = $childLotNumbersByWorkOrder[$ChildId] ?? collect();
                        $totalReturnItemQty = array_sum(array_column($data['DepartmentReturnRequest']->toArray(), 'item_qty'));

                        $IsReopend = $data->re_opend_by;
                        $WOItem = $data['WorkOrderItem'];
                        $printJob = collect($WOItem)->pluck('print_job')->filter()->first();
                        $priority = collect($WOItem)->pluck('order_item_priority')->filter()->first();
                        $unitTypeId = $data->unit_type_id;

                        $Id = $data->work_order_id;
                        $woId = $data->work_order_id;

                        $firstWOI = $data['WorkOrderItem']->first();
                        $SaleOrderDate = optional($firstWOI->SaleOrder)->sale_order_date ?? null;

                        $allotedStock = $allotedStocksByWorkOrder[$Id] ?? [];
                        $totChildWork = $totalChildWorkByWorkOrder[$Id] ?? 0;

                        $quantity = $data->quantity;
                        $masterIndId = $data->master_ind_id;
                        $machineId = $data->machine_id;
                        $currentMachineName = optional($data->WorkMachine)->name;
                        $outputQuantity = $data->output_quantity;
                        $outputProcess = $data->output_process;
                        $endProcessEmpId = $data->machine_id;
                        $inspWorkStatusProcess = $data->insp_status;
                        $WorkStatusProcess = $data->work_status;
                        $executionStatusLabel = $data->execution_status?->label() ?? 'Unmapped';
                        $isWarehouseAccepted = $data->is_warehouse_accepted;
                        $work_req_send_by = $data->work_req_send_by;
                        $WorkRequireReqAccepted = $data->is_work_require_request_accepted;
                        $IsGatePassGenrated = $data->is_gatepass_genrated_by_warehouse;
                        $isItemReceivedFromWarehouse = $data->is_item_received_from_warehouse;
                        $isDirectPrintingRoute = ! empty($parentWorkOrderId)
                            && in_array($printPosition, ['before', 'after'], true)
                            && (in_array((int) $proTypeId, $printingProcessIds, true) || in_array((int) $proTypeId, $coatingProcessIds, true));
                        $GatePassGenratedBy = $data['GatepassGenratedByWarehouseUser'] ? $data['GatepassGenratedByWarehouseUser']->name : 'N/A';
                        $ReqSendBy = $data['WorkReqSend'] ? $data['WorkReqSend']->name : 'N/A';
                        $internalName = $data['Item']->internal_item_name ?? null;
                        $processName = $data['ProcessType']->process_name;

                        $wprIdChk = $data['WorkProcessRequirement']->first()->id ?? null;
                        $totDays = daysFromNowCount($data->created);
                        $blinkMsg = '';
                        $msgColor = '';
                        $thresholds = [
                            1 => ['blue' => 2, 'red' => 3, 'name' => 'Warping'],
                            2 => ['blue' => 8, 'red' => 9, 'name' => 'Weaving'],
                            3 => ['blue' => 2, 'red' => 3, 'name' => 'Dyeing'],
                            4 => ['blue' => 4, 'red' => 5, 'name' => 'Coating'],
                        ];

                        if (array_key_exists($proTypeId, $thresholds) && empty($WorkRequireReqAccepted) && empty($wprIdChk)) {
                            $process = $thresholds[$proTypeId];
                            if ($totDays >= $process['blue']) {
                                if ($totDays == $process['blue']) {
                                    $msgColor = 'blue';
                                    $msgClass = 'wo-alert-blue';
                                    $blinkMsg = "<div class='blink small {$msgClass}'>
										{$process['name']} work order pending for {$totDays} days. Please initiate the process to avoid escalation.
									</div>";
                                } elseif ($totDays >= $process['red']) {
                                    $msgColor = 'red';
                                    $msgClass = 'wo-alert-red';
                                    $blinkMsg = "<div class='blink small {$msgClass}'>
										{$process['name']} work order pending for {$totDays} days. Please initiate the work at the earliest.
									</div>";
                                }
                            }
                        }

                        $formContent = '';
                        $formContent .= '<form method="post" name="FrmReceivedStock" action="'.route('accept_item_for_work').'" class="form-horizontal receive-stock-form">';
                        $formContent .= '<input type="hidden" name="_token" value="'.csrf_token().'">';
                        $formContent .= '<div class="receive-stock-table-wrap">';
                        $formContent .= '<table class="table table-bordered receive-stock-table">';
                        $formContent .= '<thead><tr><th>Taka Number</th><th>Alloted</th><th>Received</th></tr></thead><tbody>';

                        if (! empty($data['WarehouseOutItem'])) {
                            foreach ($data['WarehouseOutItem'] as $valRow) {
                                $inspTaka = isset($valRow->insp_taka_number) ? htmlspecialchars($valRow->insp_taka_number, ENT_QUOTES, 'UTF-8') : '';
                                $rowId = isset($valRow->id) ? htmlspecialchars($valRow->id, ENT_QUOTES, 'UTF-8') : '';
                                $itemQty = isset($valRow->item_qty) ? htmlspecialchars($valRow->item_qty, ENT_QUOTES, 'UTF-8') : '';

                                $formContent .= '<tr>';
                                $formContent .= '<td><span class="receive-stock-taka">'.$inspTaka.'</span>';
                                $formContent .= '<input type="hidden" name="WarehouseOutItemId[]" id="WarehouseOutItemId'.$rowId.'" value="'.$rowId.'"></td>';

                                $formContent .= '<td><input type="number" name="alloted_qty[]" readonly class="form-control" value="'.$itemQty.'"></td>';

                                $formContent .= '<td><input type="number" name="received_qty[]" class="form-control" id="received_qty'.$rowId.'" value="'.$itemQty.'"></td>';

                                $formContent .= '</tr>';
                            }
                        }

                        $formContent .= '</tbody></table>';
                        $formContent .= '</div>';
                        $formContent .= '<input type="hidden" name="work_order_Id" id="work_order_Id" value="'.htmlspecialchars($Id, ENT_QUOTES, 'UTF-8').'">';
                        $formContent .= '<div class="receive-stock-actions"><button type="button" class="btn btn-default" data-dismiss="modal">Close</button><button type="submit" class="btn btn-success">Accept Stock Items</button></div>';
                        $formContent .= '</form>';
                        // ---------- end build ----------

                        $formContentEscaped = htmlspecialchars($formContent, ENT_QUOTES, 'UTF-8');

                        $WOItems = collect($data['WorkOrderItem'] ?? []);
                        $saleOrderNumbers = [];
                        $customerNames = [];
                        $detailLines = [];
                        $totalMeter = 0;
                        $saleOrdIds = [];
                        $poRemarksLines = [];

                        foreach ($WOItems as $rowArr) {
                            $totalMeter += (float) data_get($rowArr, 'meter', 0);

                            $saleOrderId = data_get($rowArr, 'SaleOrder.sale_order_id');
                            if (! empty($saleOrderId) && ! in_array($saleOrderId, $saleOrdIds, true)) {
                                $saleOrdIds[] = $saleOrderId;
                            }

                            $custName = $customerNamesById[data_get($rowArr, 'customer_id')] ?? '';
                            if (! empty($custName) && ! in_array($custName, $customerNames, true)) {
                                $customerNames[] = $custName;
                            }

                            $saleOrderNumber = data_get($rowArr, 'SaleOrder.sale_order_number');

                            if (! empty($saleOrderNumber) && ! in_array($saleOrderNumber, $saleOrderNumbers, true)) {
                                $saleOrderNumbers[] = $saleOrderNumber;
                            }

                            $parts = [];

                            if (! empty(data_get($rowArr, 'dyeing_color'))) {
                                $parts[] = '<small class="text-success"><strong>'.htmlspecialchars(data_get($rowArr, 'dyeing_color')).'</strong></small>';
                            }

                            $rowCoatingType = data_get($rowArr, 'coated_pvc') ?: data_get($rowArr, 'coating_type');
                            if (! empty($rowCoatingType)) {
                                $parts[] = '<small class="text-info">'.htmlspecialchars($rowCoatingType).'</small>';
                            }

                            if (! empty(data_get($rowArr, 'extra_job'))) {
                                $parts[] = '<small class="text-warning">'.htmlspecialchars(data_get($rowArr, 'extra_job')).'</small>';
                            }

                            if (! empty(data_get($rowArr, 'print_job'))) {
                                $parts[] = '<small class="text-danger">'.htmlspecialchars(data_get($rowArr, 'print_job')).'</small>';
                            }

                            $remarksRow = data_get($rowArr, 'SaleOrderItem.remarks', '');
                            if (! empty($remarksRow)) {
                                $parts[] = '<small class="text-primary"><strong class="text-danger">Remarks - </strong>'.htmlspecialchars($remarksRow).'</small>';
                            }

                            if (! empty($parts)) {
                                $detailLines[] = implode('<br>', $parts);
                            }

                            $dlvrClearedReason = data_get($rowArr, 'SaleOrderItem.dlvr_cleared_reason', '');
                            $saleOrderNumberForPo = data_get($rowArr, 'SaleOrder.sale_order_number', 'Sale Order Number Not Found');

                            if (! empty($dlvrClearedReason)) {
                                $poRemarksLines[] = '<small class="text-danger wo-font-bold">PO - '.htmlspecialchars($saleOrderNumberForPo, ENT_QUOTES, 'UTF-8').' is '.htmlspecialchars($dlvrClearedReason, ENT_QUOTES, 'UTF-8').'</small>';

                                $poRemarksLines[] = '<small class="text-primary wo-font-bold">This PO has already been closed, so items cannot be dispatched against it. If you wish to manufacture the same material and dispatch it against another PO, you may proceed with this work order. Otherwise, please close this work order.</small>';

                            }

                        }

                        ?>
                    <tr id="Mid{{ $Id }}">

					<td>
						<?= htmlspecialchars($data->process_type); ?>
						<?= htmlspecialchars($data->process_sl_no); ?>
						<?= htmlspecialchars($Id); ?>
						<br>
						<?php $created = date('d-m-Y', strtotime($data->created)); ?>
						{!! daysFromNow($data->created) !!}
					</td>

					<td>
						<span class="btn btn-info btn-xs">
							<?= date('d-m-Y', strtotime($SaleOrderDate)); ?>
						</span>

						<?php foreach ($saleOrderNumbers as $saleOrderNumber) { ?>
							<p><?= htmlspecialchars($saleOrderNumber); ?></p>
						<?php } ?>
					</td>

					<td>
						<div class="wo-item-name">
							{{ $data->item_name }}
						</div>
						<hr class="wo-hr-sm" />
						<div class="wo-text-muted">
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

                      <td><?= htmlspecialchars($priority); ?>
                        <?php if (isset($getChildLot[0]) && ! empty($getChildLot[0])) { ?>
                          <hr class="wo-lot-divider">
                          <strong class="wo-lot-label">Lot</strong>
                          <?php foreach ($getChildLot as $childArr) { ?>
                          <p class="wo-lot-line"> <?php echo htmlspecialchars($childArr->dyeing_lot_number); ?> </p>
                          <?php } ?>
                        <?php } ?>
                      </td>
                      <td><?= $data->cut; ?></td>
                      <td><?= $data->pcs; ?></td>
                      <td><?= $totalMeter; ?></td>
						 <td>
							<?php
							$isPending = ($inspWorkStatusProcess === 'Pending');
							$isCoating = in_array((int) $proTypeId, $coatingProcessIds, true);

							$isRequestSent = !empty($work_req_send_by);
							$isRequestAccepted = ($WorkRequireReqAccepted === 'Yes');
							$isRequestDenied = ($WorkRequireReqAccepted === 'No');
							$isGatePassGenerated = ($IsGatePassGenrated === 'Yes');
							$isItemReceived = ($isItemReceivedFromWarehouse === 'Yes');

							// Current Department / Process Name
							$requestForName = !empty($departmentName) ? $departmentName : (!empty($processName) ? $processName : 'Process');

							// Request button should not show when Printing must happen before Coating
							$showRequestButton = $isPending && (!$isCoating || $printPosition !== 'before');
							?>

							<!-- Warehouse Request Status -->
							<?php if ($isRequestSent && $isRequestDenied) { ?>

								<p>
									<span class="label label-danger">
										<i class="fa fa-times"></i> Request Denied By Warehouse
									</span>
								</p>

							<?php } elseif ($isRequestSent && empty($WorkRequireReqAccepted)) { ?>

								<p>
									<span class="label label-warning">
										<i class="fa fa-clock-o"></i> Requisition Sent To Warehouse
									</span>
								</p>

								<p>
									<small class="text-muted">
										By <?= htmlspecialchars($ReqSendBy, ENT_QUOTES, 'UTF-8'); ?>
									</small>
								</p>

							<?php } elseif ($isRequestAccepted) { ?>

								<p>
									<span class="label label-success">
										<i class="fa fa-check"></i> Request Accepted By Warehouse
									</span>
								</p>

							<?php } ?>


							<!-- Gate Pass Status -->
							<?php if ($isRequestAccepted && $isGatePassGenerated) { ?>

								<p>
									<span class="label label-info">
										<i class="fa fa-file-text-o"></i> Gatepass Generated
									</span>
								</p>

								<p>
									<small class="text-muted">
										By <?= htmlspecialchars($GatePassGenratedBy, ENT_QUOTES, 'UTF-8'); ?>
									</small>
								</p>

							<?php } ?>


							<!-- Receive Material -->
							<?php if ($isRequestAccepted && $isGatePassGenerated && !$isItemReceived) { ?>

								<p>
									<button type="button" class="btn btn-success btn-xs open-modal" data-form-content="<?= htmlspecialchars($formContent, ENT_QUOTES, 'UTF-8'); ?>">
										<i class="fa fa-check-circle"></i> Accept Material
									</button>
								</p>

							<?php } ?>


							<!-- PO Cleared Remarks -->
							<?php foreach ($poRemarksLines as $varRemrkline) { ?>
								<?= $varRemrkline; ?>
							<?php } ?>


							<!-- Coating Printing Decision -->
							<?php if ($isPending && $isCoating) { ?>

								<?php if (in_array($printPosition, ['', 'none'], true)) { ?>

									<p>
										<span class="label label-warning">
											<i class="fa fa-exclamation-circle"></i> Printing Decision
										</span>
									</p>

									<form method="POST" action="<?= route('decide-printing-position'); ?>">
										<input type="hidden" name="_token" value="<?= csrf_token(); ?>">
										<input type="hidden" name="work_order_id" value="<?= htmlspecialchars($Id, ENT_QUOTES, 'UTF-8'); ?>">

										<button type="submit" name="print_position" value="before" class="btn btn-primary btn-block btn-sm">
											<i class="fa fa-print"></i> Print Before Coating
										</button>

									<div style="height:4px;"></div>

									<button type="submit" name="print_position" value="after" class="btn btn-warning btn-block btn-sm">
										<i class="fa fa-print"></i> Coating Before Printing
									</button>

									<div style="height:4px;"></div>

									<button type="submit" name="print_position" value="none" class="btn btn-default btn-block btn-sm">
										<i class="fa fa-ban"></i> No Printing Required
									</button>
								</form>

								<?php } elseif ($printPosition === 'before') { ?>

									<p>
										<span class="label label-primary">
											<i class="fa fa-print"></i> Printing Before Coating
										</span>
									</p>

									<p>
										<small class="text-muted">
											Complete Printing before starting Coating.
										</small>
									</p>

								<?php } elseif ($printPosition === 'after') { ?>

									<p>
										<span class="label label-info">
											<i class="fa fa-forward"></i> Coating Before Printing
										</span>
									</p>

								<?php } ?>

							<?php } ?>


							<!-- Common Request Button For All Departments -->
							<?php if ($showRequestButton) { ?>

								<p>
									<a href="<?= route('start-requisition-process', enc($Id)); ?>" class="btn btn-success btn-xs">
										<i class="fa fa-paper-plane"></i> Request
									</a>
								</p>

							<?php } ?>


							<!-- Allotted Stock -->
							<?php if (!empty($allotedStock)) { ?>

								<table class="table table-bordered table-condensed" style="margin-top:6px; margin-bottom:6px;">
									<thead>
										<tr>
											<th>Requested</th>
											<th>Received</th>
										</tr>
									</thead>

									<tbody>
										<?php foreach ($allotedStock as $tableRow) { ?>
											<tr>
												<td>
													<?= htmlspecialchars($tableRow['RequestQTY'], ENT_QUOTES, 'UTF-8'); ?>
													<?= htmlspecialchars($tableRow['unitTName'], ENT_QUOTES, 'UTF-8'); ?>
												</td>

												<td>
													<?= htmlspecialchars(($tableRow['AllotedQTY'] - $totalReturnItemQty), ENT_QUOTES, 'UTF-8'); ?>
													<?= htmlspecialchars($tableRow['unitTName'], ENT_QUOTES, 'UTF-8'); ?>
												</td>
											</tr>
										<?php } ?>
									</tbody>
								</table>

							<?php } ?>


							<!-- Pending Reason -->
							<?php if ($isPending && !empty($blinkMsg)) { ?>

								<div style="margin-top:5px;">
									<?= $blinkMsg; ?>

									<button type="button" class="btn btn-info btn-xs" onclick="openReasonModal(<?= (int) $Id; ?>)">
										<i class="fa fa-info-circle"></i> Reason
									</button>
								</div>

							<?php } ?>
						</td>

						<td>
							<strong><?= e($executionStatusLabel) ?></strong>
							<p><small>Inspection: <?= e($data->inspection_status?->label() ?? 'Unmapped') ?></small></p>

							<p>
								<small>
									<?php if ($proTypeId > 2 && ! empty($currentMachineName)) { ?>
										<small><?= e($currentMachineName); ?></small>
									<?php } ?>

									<?php if ($proTypeId < 3 && ! empty($currentMachineName)) { ?>
										<span class="machine-name" id="beam-machine-name-<?= e($woId) ?>">
											<small><?= e($currentMachineName); ?></small>
										</span>

										<button type="button" class="btn btn-xs btn-primary btn-edit-machine wo-ml-8" data-woid="<?= e($woId) ?>">
											<span class="glyphicon glyphicon-edit"></span>
										</button>

										<div class="machine-edit wo-hidden wo-mt-8" id="beam-machine-edit-<?= e($woId) ?>">
											<select class="form-control machine-select" id="beam-machine-select-<?= e($woId) ?>">
												<option value="">-- Select Machine --</option>
												<?php foreach ($machine as $m) { ?>
													<option value="<?= e($m->id) ?>" <?= ($machineId == $m->id) ? 'selected' : '' ?>>
														<small><?= e($m->name) ?></small>
													</option>
												<?php } ?>
											</select>

											<div class="wo-mt-5">
												<button type="button" class="btn btn-xs btn-success btn-save-machine" data-woid="<?= e($woId) ?>" title="Save Machine" data-toggle="tooltip">
													<span class="glyphicon glyphicon-floppy-disk"></span>
												</button>

												<button type="button" class="btn btn-xs btn-default btn-cancel-machine" data-woid="<?= e($woId) ?>" title="Cancel" data-toggle="tooltip">
													<span class="glyphicon glyphicon-remove"></span>
												</button>
											</div>

											<div class="machine-edit-error text-danger wo-hidden wo-mt-6" id="beam-machine-edit-error-<?= e($woId) ?>"></div>
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
                            if (! empty($machineIDd)) {
                                foreach ($machine as $m) {
                                    if ($m->id == $machineIDd) {
                                        $savedMachineName = $m->name;
                                        break;
                                    }
                                }
                            }

                            $returnItemsForReq = $warehouseOutItemsByReq->get($workProReqId, collect());
                            $hasReturnableStock = $returnItemsForReq->contains(function ($returnItem) {
                                return (string) ($returnItem->is_item_return_whouse ?? '0') === '0';
                            });
                            $isReturnStock = $returnItemsForReq->isEmpty() || $hasReturnableStock;
                            ?>

										<!-- Warping and Weaving condition -->
										<?php if (! empty($isReturnStock) && $proTypeId < 3) { ?>
											<tr>
												<td id="beamWprCell<?= e($dataRow->id) ?>">
													<?= e($dataRow->id) ?>

											<?php if ($dataRow->process_type_id == '2') { ?>
														<button type="button" class="btn btn-warning btn-xs mini-btn beam-return-btn" data-wpr-id="<?= e($dataRow->id) ?>" data-work-order-id="<?= e($Id) ?>" title="Beam/Yarn Return" data-toggle="tooltip">
															<i class="fa fa-undo"></i>
														</button>
													<?php } ?>
												</td>
											</tr>
										<?php } ?>

										<!-- Dyeing and Coating condition -->
										<?php if (! empty($isReturnStock) && $proTypeId > 2) { ?>
											<tr>
												<td id="beam-lotCell<?= e($dataRow->id) ?>">
													<span class="lot-no">
														<strong><?= e($dataRow->req_lot_no) ?></strong>&nbsp;
													</span>

													 
											<?php if (in_array($dataRow->process_type_id, [3, 4])) { ?>
														<button type="button" class="btn btn-warning mini-btn btn-xs open-lot-return-modal"
																data-form-content='{"id":"<?= e($dataRow->id) ?>","req_lot_no":"<?= e($dataRow->req_lot_no) ?>","work_order_id":"<?= e($Id) ?>"}'
																title="Item Return" data-toggle="tooltip"
																onclick="GetLotReturnItems('<?= e($dataRow->id) ?>', '<?= e($dataRow->req_lot_no) ?>', '<?= e($Id) ?>', 'returnItemsTable')">
															<i class="fa fa-undo"></i>
														</button>
													<?php } ?>

													&nbsp;

											<?php if ($dataRow->lab_req_status == 'Pending' || $dataRow->lab_req_status == 'Rejected') { ?>
												<span class="label label-default" title="Lab Test module is not available">
												<i class="fa fa-flask"></i> Lab unavailable
												</span>
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
														<div class="wo-mt-4">
															<?php
                                                        $hasMachine = ! empty($savedMachineName);
										    $labelClass = $hasMachine ? 'label-success' : 'label-danger';
										    $labelText = $hasMachine ? $savedMachineName : 'Machine Not Set';
										    ?>
															<span class="label <?= e($labelClass) ?> machine-display-<?= e($dataRow->id) ?>" title="<?= e($labelText) ?>">
																<i class="fa fa-cog"></i> <?= e($labelText) ?>
															</span>
														</div>
													</small>

													<!-- Inline edit area (hidden by default) -->
													<div class="machine-edit-area wo-hidden wo-mt-6" id="beam-machine-edit-<?= e($dataRow->id) ?>">
														<select class="form-control machine-select small" id="beam-machine-select-<?= e($dataRow->id) ?>">
															<option class="small" value="">Select Machine</option>
															<?php foreach ($machine as $m) {
															    $selected = ($dataRow->dyeing_machine_id == $m->id) ? 'selected' : '';
															    ?>
																<option value="<?= e($m->id) ?>" <?= $selected ?>><?= e($m->name) ?></option>
															<?php } ?>
														</select>

														<div class="wo-machine-actions">
															<button type="button" class="btn btn-success btn-xs save-machine-btn" data-id="<?= e($dataRow->id) ?>" data-woid="<?= e($woId) ?>">Save</button>
															<button type="button" class="btn btn-default btn-xs cancel-machine-btn" data-id="<?= e($dataRow->id) ?>" data-woid="<?= e($woId) ?>">Cancel</button>
															<span class="machine-edit-status text-muted wo-ml-8" id="beam-machine-status-<?= e($dataRow->id) ?>"></span>
														</div>
													</div>
												</td>
											</tr>
										<?php } ?>

									<?php } // end foreach?>
								</table>
							</small>
						</td>

                      <td>
						<?php if (! empty($totChildWork)) { ?>
							<div class="label label-success wo-inline-label"><small>Created <?= $totChildWork; ?> WO for next process</small></div><br>
						<?php } ?>
						
						<?php
                            if ($data->process_type == 'V') {
                                $shownBeamNumbers = [];

                                $inspectionBeamNumber = preg_replace('/\s+/', ' ', trim((string) ($data->WorkInspectionOne->insp_taka_number ?? '')));
                                if ($inspectionBeamNumber !== '') {
                                    $shownBeamNumbers[] = $inspectionBeamNumber;
                                    ?>
									<p style="margin-top: 10px;">
										<strong>Beam Number</strong>
										<hr style="margin: 2px 0;">
										<?php echo htmlspecialchars($inspectionBeamNumber, ENT_QUOTES, 'UTF-8'); ?>
									</p>
									<?php
                                }

                                if (! empty($data->WarehouseOutItem)) {

                                    foreach ($data->WarehouseOutItem as $item) {

                                        $itemTypeId = $item->item_type_id;
                                        $itemBeamNumber = preg_replace('/\s+/', ' ', trim((string) ($item->insp_taka_number ?? '')));

                                        if ($itemBeamNumber !== '' && $itemTypeId > 1) {
                                            $isDuplicateBeamNumber = in_array($itemBeamNumber, $shownBeamNumbers, true);
                                            if (! $isDuplicateBeamNumber) {
                                                $shownBeamNumbers[] = $itemBeamNumber;
                                            }

                                            $itemTypeLabel = $itemTypeId == 1 ? 'Yarn Number' : ($itemTypeId == 2 ? 'Beam Number' : 'Taka Number');
                                            if (! $isDuplicateBeamNumber) {
                                                ?>
											<p style="margin-top: 10px;">
												<strong><?php echo $itemTypeLabel; ?></strong>
												<hr style="margin: 2px 0;">
												<?php echo htmlspecialchars($itemBeamNumber, ENT_QUOTES, 'UTF-8'); ?>
											</p>
											<?php } ?>

											<p>
												<strong>Beam Meter</strong>
												<hr style="margin: 2px 0;">
												<?php echo htmlspecialchars($item->WarehouseItem->beam_meter ?? '', ENT_QUOTES, 'UTF-8'); ?>
											</p>
											<?php
                                        }
                                    }
                                }
                            }
                        ?>
                         
                        <?php
                        $itemTypeNum = $data->item_type_id;
                        $i = 1;
                        $qtySize = 0;
                        $inspBeamMeter = 0;
                        foreach ($data['GatePass'] as $gateVal) {
                            $GPId = $gateVal->id;
                            $InspId = $gateVal->inspection_id;
                            $InspComment = $gateVal['WorkInspection']->insp_comment ?? '';
                            $totAvlTaka = $availableTakaCounts[$InspId] ?? 0;
                            if ($itemTypeNum < '3') {
                                $GPTakaNumb = $gateVal->insp_taka_number;
                            } else {
                                $GPTakaNumb = $gateVal->dyeing_lot_number;
                            }
                            $GPitemRcv = $gateVal->is_item_received_in_warehouse;
                            $qtySize += $gateVal->qty_size;
                            $inspBeamMeter += $gateVal->insp_beam_meter;
                            $butclr = ($GPitemRcv == 'Yes') ? 'info' : 'success';
                            echo '<p id="InsGpid'.$GPId.'">';

                            echo '<a target="_blank" href="'.route('print-workorder-gatepass', enc($GPId)).'" class="btn btn-'.$butclr.' btn-xs" data-toggle="tooltip"
										title="'.e($InspComment).'"> '.e($GPTakaNumb).'-GP  </a>';

                            if ($GPitemRcv == 'No') {
                                echo ' <a href="javascript:void(0);" onClick="DelGatePass('.$GPId.')" class="btn btn-danger btn-xs"><i class="fa fa-trash-o"></i></a>';
                            }

                            echo '</p>';

                            $i++;
                        }
                        ?>
                        <?php
                            $totalValue = null;
                        $unitFeb = '';
                        if (! empty($qtySize)) {
                            $totalValue = $qtySize;
                            $unitFeb = ($proTypeId == '1') ? 'Kg Beam' : 'Meter';
                        } elseif (! empty($inspBeamMeter)) {
                            $totalValue = $inspBeamMeter;
                            $unitFeb = 'Meter';
                        }
                        ?>
                        <?php if (! empty($totalValue)) { ?>
                        <div class="mt-2"> Total : <?= $totalValue.' '.$unitFeb; ?>  </div>
                        <?php } ?>
                      </td>

					  <td class="center machine-cell" data-woid="<?= $woId ?>">
                      <?php
                        if ($proTypeId == 3 && $isItemReceivedFromWarehouse === 'Yes' && $WorkRequireReqAccepted !== 'Yes' && $inspWorkStatusProcess === 'Pending') {
                            ?>
                      <p class="wo-mt-10"><a href="javascript:void(0);" onClick="ReActivateInspProcess(<?= $Id; ?>)" class="btn btn-success btn-xs">Reactivate Inspection</a></p>
                      <?php
                        }
                        ?>
                      <?php if ($WorkRequireReqAccepted == 'Yes') {  ?>
                      <?php if (empty($masterIndId)) { ?>
                      <?php if (($IsGatePassGenrated == 'Yes' || $isDirectPrintingRoute) && $isItemReceivedFromWarehouse == 'Yes') {  ?>

						<?php if ($proTypeId > 2) { ?>
						<a href="javascript:void(0);" onClick="StartProcess({{ $Id }})" class="btn btn-success btn-xs">Start Process </a>
						<?php } else { ?>
						<a href="javascript:void(0);" onClick="StartProcessWev({{ $Id }})" class="btn btn-success btn-xs">Start Process</a>
						<?php } ?>

                      <?php } ?>
                      <?php } elseif ($inspWorkStatusProcess == 'Complete') {  ?>
                      <div class="label-custom label label-default"><small>Work Order Closed</small></div>
					  <?php if ($inspWorkStatusProcess === 'Complete' && empty($IsReopend)) { ?>
						<p class="wo-mt-10">
							<button type="button" class="btn btn-success btn-xs" onclick="ReActivateProcess(<?= $Id; ?>);" title="This work order is allowed to be reopened only once.">↺ Re Open</button>
						</p>
					  <?php } ?>

                      <?php } elseif ($inspWorkStatusProcess == 'Pending') { ?>
                      <?php if (in_array((int) $proTypeId, $printingProcessIds, true)) { ?>
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

                      <?php if ($proTypeId == '2' && $WorkRequireReqAccepted != 'Yes' && empty($work_req_send_by) && empty($parentWorkOrderId)) { ?>
                      <a href="javascript:void(0);" onClick="ShiftWorkOrderToWarping({{ $Id }})" class="btn btn-success btn-xs">Shift In Warping</a>
                      <?php } ?>

                       
					<?php if ($inspWorkStatusProcess == 'Pending') { ?>
						<p class="wo-mt-10">
							<a href="javascript:void(0);" onclick="CloseWorkProcess(<?= $Id; ?>)" class="btn btn-danger btn-xs" title="Using this button will close the work order. You will not be able to activate this work order again.">
								<i class="fa fa-times-circle"></i>
							</a>
						</p>
					<?php } ?>
					
					<?php if (empty($data['WorkProcessRequirement'][0])) {  ?>

					    <p style="margin-top: 50px;">
							<a href="javascript:void(0);" onClick="DelWoProcess({{ $Id }})" class="btn btn-success btn-xs" title="Delete this record permanently"> <i class="fa fa-trash-o"></i> </a>
						</p>

                      <?php } ?>

					 
					
					
					

                      </td>
                    </tr>
                    <?php } ?>

                    <tr class="center text-center">
                      <td colspan="13"><div class="pagination text-center">
					  <span class="mr-3"> {{ $dataWI->links('vendor.pagination.bootstrap-4') }} </span>
					    </div></td>
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
  @include('frontend.workorder.partials.show-workorders-modals')

  @include('frontend.common.footer') </div>
@include('frontend.common.footerscript')
@include('frontend.workorder.partials.show-workorders-scripts')

</body>
</html>



