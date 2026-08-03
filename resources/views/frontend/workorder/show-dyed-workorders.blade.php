<?php 
use \App\Http\Controllers\CommonController;
$current_page = isset($_GET['page']) ? $_GET['page'] : 1;

?>
<!DOCTYPE html>
<html lang="en">
<head>@include('common.head')
</head>
<body class="hold-transition sidebar-mini">
<!-- Site wrapper -->
<div class="wrapper"> @include('common.header')
  <div class="content-wrapperd">
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
              <div class="table-responsive" style="margin-bottom:5px">
                <form action="{{ route('show-dyed-workorders') }}" method="GET" role="search" autocomplete="off">
                  @csrf
                  <table id="dataTableExample4" class="table" style="border-collapse: collapse;">
                    <tr style="border: none;">
                      <td><input type="text" class="form-control" name="cus_search" id="cus_search" value="{{ $cusSearch }}" autofocus="autofocus" placeholder="Search by Customer Name. ">
                        <input type="hidden" id="individual_id" name="individual_id" value="{{ $individualId }}">
                      </td>
                      <td><input type="text" class="form-control" name="item_search" id="item_search" value="{{ $itemSearch }}" placeholder="Search by Item Name.">
                      </td>
                      <td><input type="text" class="form-control" name="ordNumSearch" id="ordNumSearch" value="{{ $ordNumSearch }}" placeholder="S O Number.">
                      </td>
                      <td><select class="form-control" name="work_status" id="work_status">
                          <option value="1" {{ $workStatus == '1' ? 'selected' : '' }}> Pending </option>
                          <option value="2" {{ $workStatus == '2' ? 'selected' : '' }}> Completed </option>
                          <option value="0" {{ $workStatus == '0' ? 'selected' : '' }}> All </option>
                        </select>
                      </td>
                      <td><select class="form-control" name="year_record" id="year_record">
                          <option value="2025" {{ $yearRecord == '2025' ? 'selected' : '' }}> 2025-2026 </option>
                          <option value="2024" {{ $yearRecord == '2024' ? 'selected' : '' }}> 2024-2025 </option>
                        </select>
                      </td>
                    </tr>
                    <tr style="border: none;">
                      <td><input type="text" class="form-control" name="recLotNumSearch" id="recLotNumSearch" value="{{ $recLotNumSerch }}" placeholder="Rec.for Coating Lot Number.">
                      </td>
                      <td><input type="text" class="form-control" name="from_date" id="from_date" placeholder="From Date" value="<?=$fromDateInput;?>"></td>
                      <td><input type="text" class="form-control" name="to_date" id="to_date" placeholder="To Date" value="<?=$toDateInput;?>"></td>
                      <td><input type="text" class="form-control" name="LotNumSearch" id="LotNumSearch" value="{{ $LotNumSearch }}" placeholder="Lot Number."></td>
                      <td><input type="text" class="form-control" name="colorSearch" id="colorSearch" value="{{ $colorSearch }}" placeholder="Color"></td>
                      <td><input type="submit" name="sbtSearch" class="btn btn-success" value="Search"></td>
                    </tr>
                  </table>
                </form>
              </div>
              <div class="table-responsive"> 
                <table id="dataTableExample1" class="table table-bordered table-striped table-hover">
                  <thead>
                     
					<tr class="info">
					  <th style="width:6%">W.O.No.</th>
                      <th style="width:6%">S.O No.</th>
                      <th style="width:10%">Item Name</th>
                      <th style="width:10%">Customer</th>
                      <th style="width:8%">Process</th>
                      <th style="width:4%">Priority</th>
                      <th style="width:3%">Cut</th>
                      <th style="width:3%">Pcs</th>
                      <th style="width:5%">Meter</th>
                      <th style="width:14%">Requirement</th>
                      <th style="width:15%">Status</th>
                      <th style="width:10%">Print</th> 
                      <th style="width:10%">Action</th> 
					</tr>

                  </thead>
                 <tbody>
					<?php
					foreach ($dataWI as $data) {
						// Basic props
						$Id                          = $data->work_order_id;
						$woId   					 = $data->work_order_id;
						$processType                 = $data->process_type;
						$proTypeId                   = $data->process_type_id;
						$WorkRequireReqAccepted      = $data->is_work_require_request_accepted;
						$ChildId                     = $data->child_work_order_id;
						$getChildLot                 = CommonController::getChildLotNumber($ChildId);
						$totalReturnItemQty          = array_sum(array_column($data['DepartmentReturnRequest']->toArray() ?? [], 'item_qty'));
						$WOItem                      = $data['WorkOrderItem'] ?? collect();
						$printJob                    = collect($WOItem)->pluck('print_job')->filter()->first();
						$priority                    = collect($WOItem)->pluck('order_item_priority')->filter()->first();
						$unitTypeId                  = $data->unit_type_id;
						$SaleOrderDate               = optional($WOItem->first()->SaleOrder)->sale_order_date ?? null;
						$allotedStock                = CommonController::WorkProcessItemAllotedStock($Id);
						$totChildWork                = CommonController::getTotalChildWork($Id);
						$internalName                = $data['Item']->internal_item_name ?? null;
						$processName                 = $data['ProcessType']->process_name ?? '';
						$wprIdChk                    = optional($data['WorkProcessRequirement']->first())->id;
						$totDays                     = daysFromNowCount($data->created);
						$work_req_send_by            = $data->work_req_send_by;
						$IsGatePassGenrated          = $data->is_gatepass_genrated_by_warehouse;
						$GatePassGenratedBy          = $data['GatepassGenratedByWarehouseUser']->name ?? 'N/A';
						$ReqSendBy                   = $data['WorkReqSend']->name ?? 'N/A';
						$inspWorkStatusProcess       = $data->insp_status;
						$WorkStatusProcess           = $data->work_status;
						$IsReopend                   = $data->re_opend_by;
						$itemTypeNum                 = $data->item_type_id;
						$totalMeter                  = 0;
						$shownCustomers              = [];

						// compute totalMeter and shown customers
						foreach ($WOItem as $siArr) {
							$totalMeter += $siArr->meter ?? 0;
							$custName = CommonController::getIndividualName($siArr->customer_id);
							if (!in_array($custName, $shownCustomers)) {
								$shownCustomers[] = $custName;
							}
						}
						?>
						
						 <tr id="Mid{{ $Id }}">
                      <td><?=$data->process_type; ?>
                        <?=$data->process_sl_no;?>
                        <?=$Id;?>
                        <br>
                        <?php $created = date("d-m-Y", strtotime($data->created));  ?>
                        {!! daysFromNow($data->created) !!} </td>
                      <td>
                        <span class="btn btn-info btn-xs">
                        <?=date("d-m-Y", strtotime($SaleOrderDate));?>
                        </span>
                        <?php 
							$shownSaleOrders = [];
							foreach ($WOItem as $rowArr) 
							{									 
								$saleOrderNumber = $rowArr['SaleOrder']->sale_order_number ?? 'Sale Order Number Not Found';
								if (!in_array($saleOrderNumber, $shownSaleOrders)) 
								{
									echo "<p>" . htmlspecialchars($saleOrderNumber) . "</p>";
									$shownSaleOrders[] = $saleOrderNumber;
								} 
							}
						?>
                      </td>
                      <td><div style="font-weight: bold; font-size: 14px; color: #0d6efd;"> {{ $data->item_name }} </div>
                        <hr style="margin: 4px 0;" />
                        <div style="color: gray;"> <small>({{ $internalName }})</small> </div></td>
                      <td><?php 
							$shownCustomers = [];
							$totalMeter 	= 0;
							foreach ($WOItem as $siArr) 
							{
								$totalMeter += $siArr->meter;
								$custName 	= CommonController::getIndividualName($siArr->customer_id);										
								if (!in_array($custName, $shownCustomers)) {
									echo "<p>" . mb_strimwidth($custName, 0, 10, '') . "</p>";
									$shownCustomers[] = $custName; // Mark as shown
								}  
							}
						?>
                      </td>
                      <td><?=htmlspecialchars($processName); ?>
                        <p></p>
                        <?php foreach ($WOItem as $dieArr) { ?>
                        <?php if (!empty($dieArr->dyeing_color)) { ?>
                        <small class="text-success"> <strong><?php echo htmlspecialchars($dieArr->dyeing_color); ?></strong> </small>
                        <?php $dieCoatingType = $dieArr->coated_pvc ?? $dieArr->coating_type ?? ''; ?>
                        <?php if (!empty($dieCoatingType) || !empty($dieArr->extra_job) || !empty($dieArr->print_job)) { ?>
                        <br>
                        <?php } ?>
                        <?php } ?>
                        <?php if (!empty($dieCoatingType)) { ?>
                        <small class="text-info"> <?php echo htmlspecialchars($dieCoatingType); ?> </small>
                        <?php if (!empty($dieArr->extra_job) || !empty($dieArr->print_job)) { ?>
                        <br>
                        <?php } ?>
                        <?php } ?>
                        <?php if (!empty($dieArr->extra_job)) { ?>
                        <small class="text-warning"> <?php echo htmlspecialchars($dieArr->extra_job); ?> </small>
                        <?php if (!empty($dieArr->print_job)) { ?>
                        <br>
                        <?php } ?>
                        <?php } ?>
                        <?php if (!empty($dieArr->print_job)) { ?>
                        <small class="text-danger"> <?php echo htmlspecialchars($dieArr->print_job); ?> </small>
                        <?php } ?>
                        <?php 
							$remarksRow = $dieArr['SaleOrderItem']->remarks ?? '';
							if (!empty($remarksRow)) {
								?>
                        <strong class="text-danger">Remarks - </strong> <span class="text-primary"><?php echo htmlspecialchars($remarksRow); ?></span>
                        <?php
							}
							?>
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
					  
                      <td><!-- 1) Request Denied -->
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
                        <p>Gatepass generated In Warehouse By <?php echo htmlspecialchars($GatePassGenratedBy, ENT_QUOTES, 'UTF-8'); ?></p>
                        <br />
                        <?php } ?>
                        <!-- 5) Accept button (if not received) -->
                        <?php if ($WorkRequireReqAccepted == 'Yes' && $IsGatePassGenrated == 'Yes' && $isItemReceivedFromWarehouse == 'No') { ?>
                        <button type="button"
								class="btn btn-success open-modal"
								data-form-content="<?php echo htmlspecialchars($formContent, ENT_QUOTES, 'UTF-8'); ?>"> Accept </button>
                        <?php } ?>
                        <!-- 6) PO cleared reasons -->
                        <?php
						if (!empty($WOItem) && is_array($WOItem)) {
							foreach ($WOItem as $rowVal) {
								$saleOrderNumber   = isset($rowVal['SaleOrder']->sale_order_number) ? $rowVal['SaleOrder']->sale_order_number : 'Sale Order Number Not Found';
								$dlvrClearedReason = isset($rowVal['SaleOrderItem']->dlvr_cleared_reason) ? $rowVal['SaleOrderItem']->dlvr_cleared_reason : '';
								if (!empty($dlvrClearedReason)) {
									echo '<p><strong> PO - ' . htmlspecialchars($saleOrderNumber, ENT_QUOTES, 'UTF-8') . ' is ' . htmlspecialchars($dlvrClearedReason, ENT_QUOTES, 'UTF-8') . '</strong></p>';
								}
							}
						}
						?>
                         
                        
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
							  <strong>{{ $inspWorkStatusProcess }}</strong>
							  <br>

							  <table class="table table-bordered table-condensed">
								<thead>
								  <tr class="active">
									<th>Dyeing Lot</th>
								  </tr>
								</thead>
								<tbody>
								  <?php
									$hasRow = false;
									foreach($data['WorkProcessRequirement'] as $dataRow)
									{
									  $lotNum        = $dataRow->req_lot_no;
									  $workOrderId   = $dataRow->work_order_id;
									  $workProReqId  = $dataRow->id;
									  $machineIDd    = $dataRow->dyeing_machine_id;
									  $isReturnStock = CommonController::getDepartmentReturnLot($lotNum, $workOrderId, $workProReqId);
									  $machineName   = CommonController::machineName($machineIDd);

									  if (!empty($lotNum) && !empty($isReturnStock))
									  {
										$hasRow = true;
								  ?>
									<tr id="lotCell<?= $dataRow->id ?>">
									  <td class="text-nowrap">
							  <strong><?= e($lotNum) ?></strong>&nbsp;
							  <span class="label label-success"><?= e($machineName) ?></span>
							</td>

									</tr>
								  <?php
									  }
									}

									if (!$hasRow) {
								  ?>
									<tr>
									  <td class="text-muted"><em>No dyed lot found</em></td>
									</tr>
								  <?php } ?>
								</tbody>
							  </table>
							</td>



						
						
						
						
						
						
						
						
						
                      <td><?php if(!empty($totChildWork)) { ?>
                        <span style="margin-bottom: 10px;" class="btn btn-success btn-xs"> 
                        <?=$totChildWork;?> Work order created </span> <br />
                        <?php } ?>
                        <?php if($data->process_type == 'V') { ?>
                        <?php  if(!empty($data['WorkInspectionOne']->insp_taka_number)) { ?>
                        <p style="margin-top: 10px;">
                          <?= ($data->process_type != 'W') ? (($data->process_type == 'V') ? 'Beam Num.' : 'Taka Number') : ''; ?>
                          <br/>
                          <?=$data['WorkInspectionOne']->insp_taka_number;?>
                        </p>
                        <?php }   ?>
                        <hr style="border: 1px solid black;">
						<?php  			 
						if (!empty($data['WarehouseOutItem'])) {
						foreach ($data['WarehouseOutItem'] as $item) 
						{

						$itemTypeID = $item->item_type_id;									
						if (!empty($item->insp_taka_number) && $itemTypeID > 1) { ?>
                        <p style="margin-top: 10px;">
                          <?= $item->item_type_id == '1' ? 'Yarn Number' : ($data->item_type_id == '2' ? 'Beam Number' : 'Taka Number'); ?>
                          <br/>
                          <?=$item->insp_taka_number; ?>
                        </p>
                        <p>Beam Meter
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
								$GPId 		= $gateVal->id; 
								$InspId 	= $gateVal->inspection_id; 
								$totAvlTaka = CommonController::getAnyTakaAvailableInWarehouseItemStock($InspId);
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
								echo '<a target="_blank" href="' . route('print-workorder-gatepass', enc($GPId)) . '" class="btn btn-' . $butclr . ' btn-xs">' . $GPTakaNumb . '-GP</a>';
								if ($GPitemRcv == 'No') {
									echo ' <a href="javascript:void(0);"  class="btn btn-danger btn-xs"><i class="fa fa-trash-o"></i></a>';
								}
								if(!empty($totAvlTaka))
								{
									echo ' <a target="_blank" href="#" class="btn btn-' . $butclr . ' btn-xs">' . $GPTakaNumb . '-PBC</a>';
								}
								echo '</p>';
								if ($i % 2 == 0) {
									echo '<br />';
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
					  
                       
					  <td>
							 
							<?php if ($WorkRequireReqAccepted == 'Yes') { ?>

								<?php if (
									(empty($masterIndId) || empty($machineId))
									&& $IsGatePassGenrated == 'Yes'
									&& $isItemReceivedFromWarehouse == 'Yes'
								) { ?>
									<a href="javascript:void(0);" class="btn btn-success btn-xs">Start Process</a>

								<?php } elseif ($inspWorkStatusProcess == 'Complete') { ?>
									<span class="label label-default label-custom">Item Send To Warehouse</span>

								<?php } elseif ($inspWorkStatusProcess == 'Pending') { ?>
									<a href="javascript:void(0);" class="btn btn-success btn-xs">Inspect</a>
								<?php } ?>

							<?php } ?>
						</td>

                    </tr>

						 
					<?php
					}  
					?>

					 
					<tr class="center text-center">
						<td class="center" colspan="15">
							<div class="pagination"> <?= $dataWI->links('vendor.pagination.bootstrap-4') ?> </div>
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
  @include('common.footer') </div>
@include('common.formfooterscript')
<script type="text/javascript" src="{{ asset('js/jquery.validate.js') }}"></script>

<script type="text/javascript">
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
</script>
<script type="text/javascript">
     
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
  </script>
<script type="text/javascript">
$(function() {
  $("#from_date, #to_date").datepicker({
	dateFormat: "dd-mm-yy",
	changeMonth: true,
	changeYear: true,
	autoclose: true,
  });
});
</script>

  
</body>
</html>
