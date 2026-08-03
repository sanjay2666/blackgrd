<?php 
	use \App\Http\Controllers\CommonController;
?>
<!DOCTYPE html>
<html lang="en">
<meta name="csrf-token" content="{{ csrf_token() }}">
<head>@include('frontend.common.head')
</head>
<body class="hold-transition sidebar-mini jobmillwork-page jobmill-receive-page jobmill-weaving-page">

<div class="wrapper"> @include('frontend.common.header')
  <div class="content-wrapper">
    <section class="content">
      <div class="row">
        <div class="col-sm-12"> {!! display_message('message') !!}
          <div class="panel panel-bd lobidrag">
            <div class="panel-heading jobmill-page-heading">
              <div class="btn-group"><a class="btn btn-add" href="{{ route('show-purchaseorders')}}"><i class="fa fa-list"></i> Order Item Received In Stock</a></div>
            </div>
            <div class="panel-body">
              <form method="POST" action="{{ route('store_mill_dispatch_received_weaving_item_in_warehouse') }}" id="myForm" enctype="multipart/form-data">
                @csrf
                
                <fieldset>
                <div class="panel-heading btn-success clearfix jobmill-section-heading">
                  <h4 class="panel-title"> <i class="glyphicon glyphicon-info-sign"></i> General Information </h4>
                </div>
                <div class="row">
                  <div class="col-md-12">
                    <table class="table table-bordered table-striped">
                      <thead>
                        <tr class="active">
                          <th>Challan Number</th>
                          <th>Work Name</th>
                          <th>Vendor Name</th>
                          <th>Receiving Date</th>
                          <th>Bill Front Side</th>
                          <th>Bill Back Side</th>
                          <th>Warehouse</th>
                          <th>Compartment</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr>
                          <td><input type="hidden" id="chalan_no" readonly class="form-control" required name="chalan_no" value="<?= $dataP->chalan_no; ?>">
                            <input type="text" id="stock_mill_dispatch_id" name="stock_mill_dispatch_id" class="form-control" value="<?= $dataP->id; ?>">
                          </td>
                          <td><input type="text" id="work_name" class="form-control" required name="work_name" value="<?= $dataP->work_name; ?>"></td>
                          <td><input type="text" id="vendor_name" readonly name="vendor_name" class="form-control" value="<?= $dataP['Vendor']->name ?? $dataP->vendor_name; ?>">
                            <input type="hidden" id="vendor_ind_id" name="vendor_ind_id" required value="<?= $dataP->vendor_id; ?>">
                          </td>
                          <td><input type="text" id="receiving_date" readonly required name="receiving_date" class="form-control" value="<?= date('d-m-Y'); ?>"></td>
                          <td><input type="file" required name="bill_front_img" id="bill_front_img" class="form-control"></td>
                          <td><input type="file" name="bill_back_img" id="bill_back_img" class="form-control"></td>
                          <td><select class="form-control" name="warehouseId[]" id="warehouseId_1" onChange="selectCompartment(this.value, 1);">
                              <option value="">Please Select Warehouse</option>
                              <?php foreach($dataW as $val) { ?>
                              <option value="<?= $val->id; ?>">
                              <?= $val->warehouse_name; ?>
                              </option>
                              <?php } ?>
                            </select>
                          </td>
                          <td id="warehouseCompIdDiv_1"></td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>
                </fieldset>
                
				
				
                <fieldset class="jobmill-fieldset">
                <div class="panel-heading btn-success clearfix jobmill-section-heading">
                  <div class="row">
                    <div class="col-sm-6">
                      <h4 class="panel-title" style="margin: 0; color: #fff;"> <i class="glyphicon glyphicon-info-sign" style="margin-right: 8px;"></i>Dispatched Avaliable Item Details </h4>
                    </div>
					<div class="col-sm-6 text-right">
						  <div class="form-inline jobmill-total-control">
							<label for="sum_cur_meter_arr_value" class="text-white"> Total Sum Meter: </label>
							<input type="text" id="sum_cur_meter_arr_value" name="sum_cur_meter_arr_value" class="form-control input-sm" readonly>
						  </div>
						</div>
                  </div>
                </div>
                <div class="table-responsive">
                  <table class="table table-bordered table-striped table-condensed">
                    <thead>
                      <tr class="info">
                        <th style="width:1%;">Check</th>
                        <th>Item Name</th>
                        <th>Reqst.Meter</th>
                        <th>Rec'd. Meter</th>
                        <th>Balance Meter</th>
                        <th>Currently Rec'd. Meter</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php 
					 	 foreach($availableSources as $indexAs => $valArr) 
						 {  
							 
							$poId 		= $valArr['id']; 
							$itemName 	= $valArr['item']['item_name'] ?? ($valArr['item_name_label'] ?? 'N/A'); 
							$reqMeter 	= isset($valArr['insp_quan_size']) ? (float)$valArr['insp_quan_size'] : 0; 
							$recMeter 	= isset($valArr['received_quantity']) ? (float)$valArr['received_quantity'] : 0; 
							$balance 	=  max(0, $reqMeter - $recMeter); 
							$reqMeterF 	= number_format($reqMeter, 3, '.', '');
							$recMeterF 	= number_format($recMeter, 3, '.', '');
							$balanceF  	= number_format($balance, 3, '.', '');
						?>
                      <tr id="stock_mill_dispatch_item_id<?= $poId ?>">
                        <td><input type="checkbox" name="stock_mill_dispatch_item_id[]" value="<?= $poId ?>"> </td>
                        <td><input type="text" name="item_name_arr[]" readonly class="form-control" value="<?= htmlspecialchars($itemName, ENT_QUOTES) ?>">
                          <input type="hidden" name="item_id_arr[]" value="<?= $valArr['item_id'] ?>">
                          <input type="hidden" name="wis_id_arr[]" value="<?= $valArr['wis_id'] ?>">
                        </td>
                        <td><input type="text" name="requested_meter_arr[]" readonly class="form-control" value="<?= $reqMeterF ?>"></td>
                        <td><input type="text" name="meter_result_arr[]" class="form-control" readonly value="<?= $recMeterF ?>" placeholder="e.g. 54+65"> </td>
                        <td><input type="text" name="balance_meter_arr[]" readonly class="form-control" value="<?= $balanceF ?>"> </td>
                        <td><input type="text" name="current_rcvd_meter_arr[]" class="form-control"> </td>
                      </tr>
                      <?php }   ?>
                    </tbody>
                  </table>
                  <label id="purchase_order_item_id[]-error" style="display:none" class="error"> <span class="text-danger">Please select at least one item</span> </label>
                </div>
                </fieldset>
                 
				 
				
				 <fieldset class="jobmill-fieldset">
					
					<div class="panel-heading btn-success clearfix jobmill-section-heading">
					  <div class="row">
						<div class="col-sm-6">
						<h4 class="panel-title" style="margin: 0; color: #fff;"><i class="glyphicon glyphicon-info-sign" style="margin-right: 8px;"></i>Item Receiving Details</h4>
						</div>
						<div class="col-sm-6 text-right">
						  <div class="form-inline jobmill-total-control">
							<label for="sum_meter_arr_value" class="text-white"> Total Sum Meter: </label>
							<input type="text" id="sum_meter_arr_value" name="sum_meter_arr_value" class="form-control input-sm" readonly>
						  </div>
						</div>
					  </div>
					</div>

					<div class="panel-body">
					  <div class="row">
						<div class="col-xs-12">
						  
						  <div class="table-responsive table-responsive-custom">
							<table class="table table-bordered table-striped table-condensed">
							  <tbody>
								<tr>
								  <td style="width:12%;">
									<div class="input-group">
									  <label for="pur_type">Type</label>
									  <select class="form-control" required name="pur_type" id="pur_type" onChange="changePurType();">
										<option value="">Select Type</option>
										<?php foreach ($dataIT as $valIT) { ?>
										<option value="<?=$valIT->item_type_id;?>"> <?= $valIT->item_type_name;?> </option>
										<?php } ?>
									  </select>
									</div>
								  </td>

								  <td style="width:12%;">
									<div class="input-group">
									  <label for="product_name">Item Name</label>
									  <input type="text" id="product_name" name="product_name" class="form-control" placeholder="Product Name">
									  <input type="hidden" id="pro_id" name="pro_id">
									</div>
								  </td>

								 

								  <td style="width:10%;">
									<div class="input-group">
									  <label for="hsn">HSN/SAC</label>
									  <input type="text" id="hsn" name="hsn" class="form-control" placeholder="HSN/SCN" value="">
									</div>
								  </td>

								  <td style="width:10%;">
									<div class="input-group">
									  <label for="qty">Quantity</label>
									  <input type="text" id="qty" name="qty" class="form-control" placeholder="Quantity" value="">
									</div>
								  </td>

								  <td style="width:10%;">
									<div class="input-group">
									  <label for="unit">Unit </label>
									  <select id="unit" name="unit" class="form-control" onChange="changeUnit();">
										<option value="">Select Type</option>
										<?php foreach ($dataUT as $utVal) { ?>
										<option value="<?=$utVal->unit_type_id; ?>"> <?=$utVal->unit_type_id; ?> - <?=$utVal->unit_type_name;?> </option>
										<?php } ?>
									  </select>
									</div>
								  </td>

								  <td style="width:8%;">
									<div class="input-group">
									  <label id="meterId" for="meter">Meter</label>
									  <label id="meterkgId" for="meter">Kg</label>
									  <input type="text" id="meter" name="meter" class="form-control" value="0">
									</div>
								  </td>

								  <td style="width:7%; display:none;" id="beam_meterId">
									<div class="input-group">
									  <label for="meter">B.Meter</label>
									  <input type="text" id="beam_meter" name="beam_meter" class="form-control" value="0">
									</div>
								  </td>

								  <td style="width:12%;">
									<div class="input-group">
									  <label id="taka_numberLotId" for="taka_number">Lot Number</label>
									  <label id="taka_numberTakaId" for="taka_number">Taka Number</label>
									  <input type="text" id="taka_number" name="taka_number" class="form-control" value="0">
									</div>
								  </td>

								  <td style="width:8%;">
									<div class="input-group">
									  <label for="remarks">Remark</label>
									  <input type="text" id="remarks" class="form-control" name="remarks" value="">
									</div>
								  </td>

								  <td style="width:3%;">
									<div class="input-group">
									  <label for="add_button">&nbsp;</label>
									   <label for="add_button">  </label>	
									  <button type="button" id="Add_To_Purchase" class="btn btn-primary">+</button>
									</div>
								  </td>
								</tr>
							  </tbody>
							</table>
						  </div>
						  
						</div>
					  </div>

					  
					  <div class="form-group">
						<div class="box-body">
						   
						  <table id="example2" class="table table-bordered table-striped">
							<thead>
							  <tr>
								<th style="width:10px;">#</th>
								<th style="width:100px;">Type</th>
								<th style="width:100px;">Item Name</th> 
								<th style="width:100px;">HSN/SAC</th>
								<th style="width:100px;">Qty</th>
								<th style="width:100px;">Unit</th>
								<th style="width:100px;">Meter</th> 
								<th style="width:100px;">Taka Number</th>
								<th style="width:100px;">Remarks</th>
								<th style="width:30px;">Action</th>
							  </tr>
							  <input type="hidden" id="count_product" name="count_product" value="0">
							</thead>

							<tbody id="tbody" style="max-height: 200px;overflow-y: auto;overflow-x: hidden;">
							</tbody>

							<tfoot>
							  <tr>
								<th></th><th></th><th></th><th></th><th></th> 
								<th></th><th></th><th></th><th></th><th></th>
							  </tr>
							</tfoot>
						  </table>
						</div>
					  </div>

					</div>
				 </fieldset>
 
                
                <div class="row jobmill-action-row">
                  <div class="col-md-12 text-right">
                    <button type="button" class="btn btn-danger" id="confirmBtn" onClick="discardAction()"> <i class="fa fa-times"></i> Discard </button>
                    <button type="button" class="btn btn-primary" id="resetBtn" onClick="submitForm()"> <i class="fa fa-check"></i> Confirm </button>
                  </div>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </section>
    
  </div>
  
  @include('frontend.common.footer') </div>
@include('frontend.common.footerscript')
<script type="text/javascript" src="{{ asset('js/jquery.validate.js') }}"></script>

<script type="text/javascript">
$(document).ready(function() {
    function parseNum(v) {
        if (v === undefined || v === null) return 0;
        v = String(v).replace(/,/g,'').trim();
        var n = parseFloat(v);
        return isNaN(n) ? 0 : n;
    }
    function isValidNumStr(v) {
        v = String(v||'').replace(/,/g,'').trim();
        return v !== '' && !isNaN(parseFloat(v));
    }

    $('input[name="current_rcvd_meter_arr[]"]').prop('readonly', true);

    if ($.validator) {
        $.validator.addMethod("atLeastOne", function(value, element) {
            return $('input[name="stock_mill_dispatch_item_id[]"]:checked').length > 0;
        }, "Please select at least one item.");
    }

    var validator = $("#myForm").validate({
        ignore: [],
        rules: {
            'stock_mill_dispatch_item_id[]': { atLeastOne: true },
            'warehouseId[]': { required: true },
            'work_name': { required: true },
            'vendor_ind_id': { required: true },
            'receiving_date': { required: true }
        },
        messages: {
            'stock_mill_dispatch_item_id[]': { atLeastOne: "<span style='color: red;'>Please select at least one taka</span>" },
            'warehouseId[]': { required: "Please select warehouse" },
            'work_name': { required: "Please enter Work Name" },
            'vendor_ind_id': { required: "Vendor missing" },
            'receiving_date': { required: "Please select receiving date" }
        },
        errorPlacement: function(error, element) {
            if (element.attr("name") === "stock_mill_dispatch_item_id[]") {
                var $lbl = $('label[id="purchase_order_item_id[]-error"]');
                $lbl.html(error);
                $lbl.show();
            } else {
                error.insertAfter(element);
            }
        },
        success: function(label, element) {
            if ($(element).attr("name") === "stock_mill_dispatch_item_id[]") {
                $('label[id="purchase_order_item_id[]-error"]').hide();
            }
        }
    });

    function updateSumCurrentReceived() {
        var sum = 0;
        $('input[name="stock_mill_dispatch_item_id[]"]').each(function() {
            var $chk = $(this);
            if ($chk.is(':checked')) {
                var $tr = $chk.closest('tr');
                var $curr = $tr.find('input[name="current_rcvd_meter_arr[]"]');
                var v = $curr.val();
                if (isValidNumStr(v)) sum += parseNum(v);
            }
        });
        $('#sum_cur_meter_arr_value').val(sum.toFixed(3));
    }

    $(document).on('change', 'input[name="stock_mill_dispatch_item_id[]"]', function() {
        var $tr = $(this).closest('tr');
        var $curr = $tr.find('input[name="current_rcvd_meter_arr[]"]');
        var balance = parseNum($tr.find('input[name="balance_meter_arr[]"]').val());

        if ($(this).is(':checked')) {
            $curr.prop('readonly', false);
            try {
                $curr.rules('add', {
                    required: true,
                    number: true,
                    min: 0.0001,
                    max: balance,
                    messages: {
                        required: "Please enter currently received meter",
                        number: "Enter a valid number (e.g. 12.345)",
                        min: "Value must be greater than 0",
                        max: "Can't exceed available balance (" + balance + ")"
                    }
                });
            } catch(e){}
        } else {
            try { $curr.rules('remove'); } catch(e){}
            $curr.val('');
            $curr.prop('readonly', true);
        }

        if (validator) validator.element('input[name="stock_mill_dispatch_item_id[]"]');
        updateSumCurrentReceived();
    });

    $(document).on('input change blur', 'input[name="current_rcvd_meter_arr[]"]', function() {
        updateSumCurrentReceived();
    });

    $('input[name="stock_mill_dispatch_item_id[]"]:checked').each(function() {
        $(this).trigger('change');
    });

    window.submitForm = function() {
        if (validator) validator.element('input[name="stock_mill_dispatch_item_id[]"]');
        if (!validator.form()) return false;

        var ok = true;
        $('input[name="stock_mill_dispatch_item_id[]"]:checked').each(function() {
            var $tr = $(this).closest('tr');
            var balance = parseNum($tr.find('input[name="balance_meter_arr[]"]').val());
            var v = $tr.find('input[name="current_rcvd_meter_arr[]"]').val();
            var num = parseFloat(String(v || '').replace(/,/g,''));
            if (isNaN(num) || num <= 0 || num > balance) {
                ok = false;
                $tr.find('input[name="current_rcvd_meter_arr[]"]').focus();
                return false;
            }
        });

        if (!ok) {
            alert("Please check Currently Rec'd. Meter values (must be >0 and ≤ Balance).");
            return false;
        }

        $('#myForm')[0].submit();
    };
});
</script>
 
<script type="text/javascript">
$(document).ready(function() {
    function parseNum(v) {
        if (v === undefined || v === null) return 0;
        v = String(v).replace(/,/g,'').trim();
        var n = parseFloat(v);
        return isNaN(n) ? 0 : n;
    }
    function isValidNumStr(v) {
        v = String(v||'').replace(/,/g,'').trim();
        return v !== '' && !isNaN(parseFloat(v));
    }

    function recalcProductSum() {
        var sum = 0;
        $('#tbody').find('input[name="meter_arr[]"]').each(function() {
            var v = $(this).val();
            if (isValidNumStr(v)) sum += parseNum(v);
        });
        $('#sum_meter_arr_value').val(sum.toFixed(2));
    }

    recalcProductSum();

    $('#Add_To_Purchase').off('click').on('click', function() {
        if ($('#product_name').val().trim() === '') { $('#product_name').focus(); return; }

        $("#resetBtn, #confirmBtn").show();
        $('#example2').show();

        var count_product = parseInt($('#count_product').val() || 0) + 1;
        var meterValRaw = $('#meter').val();
        var meterVal = isValidNumStr(meterValRaw) ? parseNum(meterValRaw).toString() : '0';

        function e(s){ return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;'); }

        var new_pro = "<tr id='tr_" + count_product + "'>" +
             "<td> " + count_product + "</td>" +
             "<td><input readonly value='" + e($('#pur_type').val()) + "' type='text' class='form-control' name='pur_type_arr[]'></td>" +
             "<td><input value='" + e($('#pro_id').val()) + "' type='hidden' name='pro_id_arr[]' readonly><input readonly value='" + e($('#product_name').val()) + "' type='text' class='form-control' name='product_name_arr[]'></td>" +
             "<td><input readonly value='" + e($('#hsn').val())+ "' type='text' class='form-control' name='hsn_arr[]'></td>" +
             "<td><input readonly value='" + e($('#qty').val())+ "' type='text' class='form-control' name='qty_arr[]'></td>" +
             "<td><input readonly value='" + e($('#unit').val()) + "' type='text' class='form-control' name='unit_arr[]'></td>" +
             "<td><input readonly value='" + e(meterVal) + "' type='text' class='form-control' name='meter_arr[]'></td>" + 
             "<td><input readonly value='" + e($('#taka_number').val()) + "' type='text' class='form-control' name='taka_number_arr[]'></td>" +
             "<td><input readonly value='" + e($('#remarks').val()) + "' type='text' class='form-control' name='remarks_arr[]'></td>" +
             "<td><a data-toggle='tooltip' href='javascript:void(0);' onclick='removeRows(\"tr_" + count_product + "\");' title='Remove'><span class='glyphicon glyphicon-remove-circle remove' data-trid='tr_" + count_product + "' ></span></a></td>" +
             "</tr>";

        $('#tbody').append(new_pro);
        $('#count_product').val(count_product);

        recalcProductSum();
    });

    window.removeRows = function(rowId) {
        var $r = $('#' + rowId);
        if ($r.length) {
            $r.remove();
            recalcProductSum();
        }
    };

    $(document).on('input change blur', '#tbody input[name="meter_arr[]"]', function() { recalcProductSum(); });

    var tbody = document.getElementById('tbody');
    if (tbody) {
        var observer = new MutationObserver(function() {
            clearTimeout(window._productSumTimer);
            window._productSumTimer = setTimeout(recalcProductSum, 50);
        });
        observer.observe(tbody, { childList: true, subtree: true });
    }
});
</script>
 
<script>
function removeRows(rowId) {
    $("#" + rowId).remove();
}
</script>

<script type="text/javascript">
$(function() {
    var siteUrl = "{{ url('/') }}";

    $("#product_name").autocomplete({
        source: function(request, response) {
            $.ajax({
                url: siteUrl + '/list_warehouse_item_type',
                dataType: "json",
                data: {
                    term: request.term,
                    type: $('#pur_type').val()
                },
                success: function(data) {
                    response(data);
                }
            });
        },
        minLength: 0,
        focus: function(event, ui) {
            $("#product_name").val(ui.item.item_name);
            return false;
        },
        select: function(event, ui) {
            $("#pro_id").val(ui.item.item_id);
            $("#product_name").val(ui.item.item_name);
            $("#hsn").val(ui.item.hsncode);
            return false;
        }
    }).autocomplete("instance")._renderItem = function(ul, item) {
        var purType = $('#pur_type').val();
        console.log(purType);
        return $("<li>")
            .append("<div>" + item.item_name + "<br> Item Code: " + item.item_code + "<br> Internal Name:" + item.internal_item_name + " </div>")
            .appendTo(ul);
    };
});
</script>

<script type="text/javascript">
 
$("#taka_numberLotId").hide();		
	function changePurType()
	{  
		var pur_type  = $('#pur_type').val(); 
		 
		if(pur_type =='1' || pur_type =='2')
		{
			$("#taka_numberLotId").show();	
			$("#beam_meterId").show();	
			$("#taka_numberTakaId").hide();	
			 
		} 
		else 
		{
			$("#taka_numberLotId").hide();	
			$("#beam_meterId").hide();	
			$("#taka_numberTakaId").show();	 
		}
		
		if(pur_type =='2')
		{ 
			$("#beam_meterId").show();	 
		} 
		else 
		{ 
			$("#beam_meterId").hide(); 
		}	 
	}
</script>
 
<script type="text/javascript">
$("#meterId").hide();			
	function changeUnit()
	{  
		var unit  = $('#unit').val(); 
		if(unit =='4')
		{
			$("#meterkgId").show();	
			$("#meterId").hide();
		} 
		else 
		{
			$("#meterId").show();
			$("#meterkgId").hide();	
		}	 
	}
</script>

<script language="javascript" type="text/javascript">
    $(document).ready(function() {
        $("#myForm").validate({
            ignore: [],  
            rules: {
                'stock_mill_dispatch_item_id[]': {
                    required: true
                }
            },
            messages: {
                'stock_mill_dispatch_item_id[]': {
                    required: "<span style='color: red;'>Please select at least one taka</span>"
                }
                
            }
        });
    });
</script>
<script type="text/javascript">
$( "#receiving_date" ).datepicker({
    dateFormat: "dd-mm-yy",
    autoclose: true,
});
</script>
<script>
    function discardAction() {
        
        location.reload();
    }
</script>
<script type="text/javascript">
	function selectCompartment(warehouseId, index) 
	{
		var siteUrl = "{{ url('/') }}";

		$.ajax({
			type: "GET",
			url: siteUrl + "/ajax_script/search_warehouse_compartment_arr",
			data: {
				"_token": "{{ csrf_token() }}",
				"Id": warehouseId,
			},
			cache: false,
			success: function(res) {
				$("#warehouseCompIdDiv_" + index).html(res);
			}
		});
	}
</script>
 
</body>
</html>
