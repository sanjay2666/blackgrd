<?php
	use \App\Http\Controllers\CommonController; 	
	// echo "<pre>"; print_r($dataWPR); exit;	
?>
<!DOCTYPE html>
<html lang="en">
<head>@include('common.head')
</head>
<body class="hold-transition sidebar-mini">
<!--preloader-->
 
<!-- Site wrapper -->
<div class="wrapper"> @include('common.header')
  <div class="content-wrapper">
    <section class="content">
      <div class="row">
	  {!! CommonController::display_message('message') !!}
        <div class="col-sm-12">
          <div class="panel panel-bd lobidrag">
            <div class="panel-heading">
              <div class="btn-group" id="buttonexport"><h4><i class="fa fa-plus m-r-5"></i> Stock Allotment</h4></div>
            </div>
            <div class="panel-body"> 
              <div class="table-responsive">
			  
			   <form method="post" action="{{ route('StoreWarehouseYarnBeamStockAllotment') }}" class="form-horizontal" id="stockAllotFormBeam" autocomplete="off">
				<div class="col-sm-12">
				<table class="table table-bordered table-striped table-hover">
				<thead>
					<tr class="info">
						<th>Item Name</th>
						<th>Internal Name</th>		 
						<th>Available Qty</th>	 
						<th>Needed Qty</th>
						<th>Warehouse</th>
						<th>W.Compartment</th>
						 
					</tr>
				</thead>
				 <tbody>					
				@csrf 
				 
				<?php
				$flag 	= 0;
				$i 		= 0;
				$anyZeroStock = false; 
				foreach ($dataWPR as $wprArr) 
				{	
					// echo "<pre>"; print_r($wprArr); 
					$wbitemId 			= $wprArr->warehouse_balance_item_id;
				 	$wisId 				= $wprArr->wis_id;
				    $itemTypeId 		= $wprArr->item_type_id;
					$wprId 				= $wprArr->id;
					$itemId 			= $wprArr->item_id;					 
					$processId 			= $wprArr->process_type_id;					 
					$Quantity 			= $wprArr->quantity;			 
					
					//  echo $wprArr['Item']->unit_type_id;
					if(!empty($wisId))
					{
						$warehouseName 	= CommonController::getWareHouseNameByItemStockId($wisId);
					} else 
					{
						$warehouseName 	= CommonController::getWareHouseNameByItemStock($itemId, $processId);
					}					
					$unitType 			= CommonController::getUnitTypeName($wprArr->unit_type_id);
					
					$ItemTypeName 		= CommonController::getItemTypeName($itemTypeId);
					$totAvlStock 		= null;
					if (!empty($wbitemId)) {
						$totAvlStock 	= CommonController::getBalanceStockById($wbitemId);
					
					} 
						
					// $totAvlStock 	= CommonController::getWarehouseItemStockById($wisId);
					$totAvlStock 	= CommonController::getTotalAvailableItemStock($itemId, $itemTypeId);
					 
					
					// $flag 			= !empty($totAvlStock) ? 1 : 0;	
					if (empty($totAvlStock)) {
						$anyZeroStock = true;
					}
					
					$itemName 		= $wprArr['Item']->item_name;
					$itemCode 		= $wprArr['Item']->item_code;
					$itemInterName 	= $wprArr['Item']->internal_item_name;
				?>
					 
					<tr>	
						<td class="text-left"><?=$itemName; ?></td> 
						<td class="text-left"><?=$itemInterName; ?></td>   
						<td class="text-left"><?=$totAvlStock;?> <?=$unitType;?> <?=$ItemTypeName;?></td> 
						<td class="text-left"><?=$Quantity;?> <?=$unitType;?> <?=$ItemTypeName;?></td> 
						<td class="text-left"><?=$warehouseName['Warehouse']; ?></td> 
						<td class="text-left"><?=$warehouseName['WarehouseCompartment']; ?></td>
						 <input type="hidden" name="received_quantities[]" value="<?=$Quantity; ?>" class="form-control">   
					 <input type="hidden" name="work_process_req_ids[]" value="<?=$wprId;?>" class="form-control">
					</tr>					
							  
						
				<?php $i++;
				} ?>
				</tbody>			
							 
						</table>
					</div>
				<table class="table table-bordered">
					<input type="hidden" name="work_order_id" id="work_order_id" value="<?= $result['workOrdId']; ?>" class="form-control">
					<tr>
						<th>Remark Comment <span class="required" aria-required="true">*</span></th>
						<td><input type="text" name="allotment_remark" id="allotment_remark" required class="form-control"></td>
					</tr>
				</table>
				<?php
					$flag = $anyZeroStock ? 0 : 1;
				?>
				<?php if (empty($flag)) { ?>
					<p> Note: <b style="color: red;">Some Item Not Available in Warehouse.</b></p>
				<?php } ?>
				<?php if (!empty($flag)) { ?>
					<button type="submit" id="submitBtnBeam" class="btn btn-success pull-left">Update Allotment</button>
				<?php } ?>
			</form>

			  </div>
            </div>
          </div>
        </div>
      </div>
    </section>
    <!-- /.content -->
  </div>
  <!-- /.content-wrapper --> 
@include('common.footer') 
</div>
@include('common.formfooterscript')
<script type="text/javascript" src="{{ asset('js/jquery.validate.js') }}"></script>
 <script>
$(document).ready(function() {
    var $form = $('#stockAllotFormBeam');
    var $btn  = $('#submitBtnBeam');

    // अगर आपने पहले से $form.validate({...}) कहीं लागू किया हुआ है तो यह लाइन हटाना,
    // वरना यह ensure करेगा .valid() उपलब्ध हो।
    if (typeof $form.validate === 'function') {
        $form.validate(); 
    }

    $form.on('submit', function(e) {
        // अगर validate() मौजूद है तो पहले वैलिडेट करें
        if (typeof $form.valid === 'function') {
            if (!$form.valid()) {
                // validation failed — don't disable button
                return;
            }
        }

        // अगर पहले से सबमिट हो चुका है, तो रोक दो
        if ($btn.data('submitted') === true) {
            e.preventDefault();
            return;
        }

        // mark as submitted and disable button + change text for UX
        $btn.data('submitted', true);
        $btn.prop('disabled', true).addClass('disabled').text('Please wait...');
        // form will continue to submit
    });

    // safety: अगर कोई दूसरा तरीका से बटन दबाया जाए (double click), click handler भी रखें
    $btn.on('click', function(e){
        if ($(this).data('submitted') === true) {
            e.preventDefault();
            return false;
        }
        // allow click to submit — actual disabling handled in submit handler
    });
});
</script>
<script>
$(function () {
    $('form').on('submit', function (e) {
        var $form = $(this);
        var $button = $form.find('button[type="submit"], input[type="submit"]').filter(':visible').first();

        if ($form.data('submitted') === true) {
            e.preventDefault();
            return false;
        }

        if (typeof $form.valid === 'function' && !$form.valid()) {
            return true;
        }

        $form.data('submitted', true);
        $button.prop('disabled', true).addClass('disabled');

        if ($button.is('input')) {
            $button.val('Please wait...');
        } else {
            $button.text('Please wait...');
        }
    });
});
</script>

</body>
</html>
