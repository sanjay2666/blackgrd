<?php 
	use \App\Http\Controllers\CommonController;
?>
<!DOCTYPE html>
<html lang="en">
<meta name="csrf-token" content="{{ csrf_token() }}">
<head>@include('frontend.common.head')
</head>
<body class="hold-transition sidebar-mini jobmillwork-page jobmill-receive-page">

<div class="wrapper"> 
@include('frontend.common.header')
  <div class="content-wrapper">
    <section class="content">
      <div class="row">
        <div class="col-sm-12"> {!! display_message('message') !!}
          <div class="panel panel-bd lobidrag">
            <div class="panel-heading jobmill-page-heading">
              <div class="btn-group"><a class="btn btn-add" href="{{ route('show-purchaseorders')}}"><i class="fa fa-list"></i> Order Item Received In Stock</a></div>
            </div>
            <div class="panel-body">
               
			  <form method="POST" action="{{ route('store_mill_dispatch_received_item_in_warehouse') }}" id="myForm" enctype="multipart/form-data">    
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
                            <option value="<?= $val->id; ?>"> <?= $val->warehouse_name; ?> </option>
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
                      <h4 class="panel-title" style="margin: 0; color: #fff;"> <i class="glyphicon glyphicon-info-sign" style="margin-right: 8px;"></i> Item Receiving Details </h4>
                    </div>
                    <div class="col-sm-6 text-right">
                      <div class="form-inline jobmill-total-control">
                        <label for="grand-total-meter" class="text-white"> Total Sum Meter: </label>
                        <input type="text" id="grand-total-meter" class="form-control input-sm" readonly>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="table-responsive">
                  <table class="table table-bordered table-striped table-condensed">
                    <thead>
                      <tr class="info">
                        <th style="width:1%;">Check</th>
                        <th style="width:6.5%;">Type</th>
                        <th>Item Name</th>
                        <th>D.Color</th>
                        <th>Coating</th>
                        <th>Printing</th>
                        <th>ExtraJob</th>
                        <th>Reqst.Meter</th>
                        <th>Rec'd. Qty</th>
                        <th>Unit</th>
                        <th>Rec'd. Meter</th>
                        <th>Taka Num.</th>                      
                        <th>Remark</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php  
							foreach($dataP['StockMillDispatchItem'] as $index => $rowArr) 
							{
								$poId = $rowArr->id;
								$itemTypeId = $rowArr->item_type_id; 
								$unitTypeId = $rowArr['Item']->unit_type_id ?? null;
								$unitTypeName = $rowArr['Item']['UnitType']->unit_type_name ?? '';
								$balanceQty = $rowArr->insp_quan_size - $rowArr->received_quantity;

								if(!empty($balanceQty)) {
						?>
                      <tr id="stock_mill_dispatch_item_id<?= $poId ?>">
                        <td><input type="checkbox" name="stock_mill_dispatch_item_id[]" value="<?= $poId ?>"></td>
                        <td><select class="form-control" name="item_type_id[]" readonly>
                            <?php foreach ($dataIT as $valIT) { ?>
                            <option value="<?= $valIT->item_type_id; ?>" <?= $valIT->item_type_id == $itemTypeId ? 'selected' : ''; ?>>
								<?= $valIT->item_type_name; ?>
                            </option>
                            <?php } ?>
                          </select>
                        </td>
                        <td><input type="text" name="item_name_arr[]" readonly class="form-control" value="<?= $rowArr['Item']->item_name ?? ''; ?>">
                          <input type="hidden" name="item_id_arr[]" value="<?= $rowArr->item_id; ?>">
                        </td>
                        <td><input type="text" name="item_dyeing_color_arr[]" class="form-control" value="<?= $rowArr->dyeing_color; ?>"></td>
                        <td><input type="text" name="item_coating_arr[]" class="form-control" value="<?= $rowArr->coated_pvc; ?>"></td>
                        <td><input type="text" name="item_print_arr[]" class="form-control" value="<?= $rowArr->print_job; ?>"></td>
                        <td><input type="text" name="item_extra_job_arr[]" class="form-control" value="<?= $rowArr->extra_job; ?>"></td>
                        <td><input type="text" name="qty_arr[]" readonly class="form-control" value="<?= $balanceQty; ?>"></td>
                        <td><input type="text" name="rec_qty_arr[]" class="form-control" value="1"></td>
                        <td><select name="unit_arr[]" class="form-control" readonly>
                            <option value="">Select Unit</option>
                            <?php foreach ($dataUT as $utVal) { ?>
                            <option value="<?= $utVal->unit_type_id; ?>" <?= $utVal->unit_type_id == $unitTypeId ? 'selected' : ''; ?>>
                            <?= $utVal->unit_type_name; ?>
                            </option>
                            <?php } ?>
                          </select>
                        </td>
                        <td><input type="text" name="meter_arr[]" class="form-control meter-expression" value="0" placeholder="e.g. 54+65">
                          <input type="hidden" class="form-control meter-result">
                        </td>
                        <td><input type="text" name="taka_number_arr[]" class="form-control" value="<?= $rowArr->insp_taka_number; ?>">
                          <small><strong>D.Lot.Num.</strong></small>
                          <input type="text" name="dyeing_lot_number_arr[]" class="form-control" value="<?= $rowArr->dyeing_lot_number; ?>">
                          <small><strong>D.T.Num.</strong></small>
                          <input type="text" name="dyeing_taka_number_arr[]" class="form-control" value="<?= $rowArr->dyeing_taka_number; ?>">
                        </td>
                        
                        <td><input type="text" name="remarks[]" class="form-control"></td>
                      </tr>
                      <?php } } ?>
                    </tbody>
                  </table>
                  <label id="purchase_order_item_id[]-error" style="display:none" class="error"> <span class="text-danger">Please select at least one item</span> </label>
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



<script>
$(document).ready(function(){

  function parseMeterExpression(str) {
    if(!str) return 0;
    var parts = str.split('+');
    var sum = 0;
    for(var i=0;i<parts.length;i++){
      var v = parseFloat(parts[i].toString().trim()) || 0;
      sum += v;
    }
    return +sum.toFixed(3);
  }

  function updateGrandTotal(){
    var total = 0;
    $('.meter-result').each(function(){
      total += parseFloat($(this).val() || 0);
    });
    $('#grand-total-meter').val(total.toFixed(3));
  }

  $(document).on('change keyup', '.meter-expression', function(){
    var $this = $(this);
    var idx = $this.data('index');
    var val = $this.val();
    var res = parseMeterExpression(val);
    $this.closest('tr').find('.meter-result').val(res);
    updateGrandTotal();
    var totalUsed = $('#usage-table-'+idx).find('.total-used-mtr').val() || 0;
    if( $('#usage-table-'+idx+' tbody tr').length == 0 ){
      $('#usage-table-'+idx).closest('tr').find('.total-used-mtr').val(res.toFixed(3));
    }
  });

  $('input[name="stock_mill_dispatch_item_id[]"]').on('change', function(){
    var $tr = $(this).closest('tr');
    var checked = $(this).is(':checked');
    var indexInDom = $tr.index();
    var idx = $tr.find('.meter-expression').data('index');
    if(checked){
      $('#usage-row-'+idx).show();
    } else {
      $('#usage-row-'+idx).hide();
    }
  });

  $(document).on('click', '.add-input-btn', function(){
    var idx = $(this).data('index');
    var $tbody = $('#usage-table-'+idx+' tbody');

    var options = window.availableSourcesHtml || '<option value="">Select</option>';

    var rowHtml = '<tr>' +
      '<td><select class="form-control input-source" name="used_inputs['+idx+'][][input_stock_mill_dispatch_item_id]">' + options + '</select></td>' +
      '<td><input type="text" readonly class="form-control source-available" value=""></td>' +
      '<td><input type="number" step="0.001" class="form-control used-qty" name="used_inputs['+idx+'][][used_qty]" value="0"></td>' +
      '<td><input type="number" step="0.000001" class="form-control conv-factor" name="used_inputs['+idx+'][][conversion_factor]" value="1"></td>' +
      '<td><input type="text" readonly class="form-control used-mtr" name="used_inputs['+idx+'][][used_mtr]" value="0"></td>' +
      '<td><button type="button" class="btn btn-danger btn-xs remove-input">X</button></td>' +
      '</tr>';
    $tbody.append(rowHtml);
  });

  $(document).on('change', '.input-source', function(){
    var $sel = $(this);
    var available = $sel.find('option:selected').data('available') || 0;
    $sel.closest('tr').find('.source-available').val(parseFloat(available).toFixed(3));
    var conv = $sel.find('option:selected').data('conv') || 1;
    $sel.closest('tr').find('.conv-factor').val(conv);
  });

  $(document).on('input change', '.used-qty, .conv-factor', function(){
    var $tr = $(this).closest('tr');
    var usedQty = parseFloat($tr.find('.used-qty').val() || 0);
    var conv = parseFloat($tr.find('.conv-factor').val() || 1);
    var usedMtr = usedQty * conv;
    $tr.find('.used-mtr').val(usedMtr.toFixed(3));

    var $usageTable = $tr.closest('table');
    var sum = 0;
    $usageTable.find('.used-mtr').each(function(){ sum += parseFloat($(this).val() || 0); });
    $usageTable.find('.total-used-mtr').val(sum.toFixed(3));

    var idx = $usageTable.attr('id').split('-').pop();
    if(sum > 0){
      $('input.meter-expression[data-index="'+idx+'"]').closest('tr').find('.meter-result').val(sum.toFixed(3));
    }
    updateGrandTotal();
  });

  $(document).on('click', '.remove-input', function(){
    var $tr = $(this).closest('tr');
    var $table = $tr.closest('table');
    $tr.remove();
    var sum = 0; $table.find('.used-mtr').each(function(){ sum += parseFloat($(this).val() || 0); });
    $table.find('.total-used-mtr').val(sum.toFixed(3));
    updateGrandTotal();
  });

  window.submitForm = function(){
    if($('input[name="stock_mill_dispatch_item_id[]"]:checked').length == 0){
      alert('Please select at least one item to receive.');
      return false;
    }
    $('#myForm').submit();
  };

});
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
<script language="javascript" type="text/javascript">
 
document.addEventListener('DOMContentLoaded', function () {
    const inputs = document.querySelectorAll('.meter-expression');
    const totalBox = document.getElementById('grand-total-meter');

    function calculateRow(input, output) {
        const value = input.value.replace(/\s/g, '');
        const parts = value.split('+');
        let sum = 0;

        parts.forEach(function (num) {
            const n = parseFloat(num);
            if (!isNaN(n)) {
                sum += n;
            }
        });

        output.value = sum;
        return sum;
    }

    function updateAll() {
        let grandTotal = 0;

        inputs.forEach(function (input) {
            const output = input.closest('tr').querySelector('.meter-result');
            const rowTotal = calculateRow(input, output);
            grandTotal += rowTotal;
        });

        totalBox.value = grandTotal;
    }

    inputs.forEach(function (input) {
        input.addEventListener('input', updateAll);
    });

    updateAll();
});
</script>
<script language="javascript" type="text/javascript">
     
	function submitForm() { 
		if ($("#myForm").valid()) {
			$("input[name='stock_mill_dispatch_item_id[]']").each(function() {
				if (!$(this).prop('checked')) {
					$(this).closest('tr').remove();
				}
			});

			$("#myForm").submit();
		}
	}

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
<script>
    function toggleDyeingColor(selectElement) 
	{
        var dyeingColorTd = $(selectElement).closest('tr').find('#dyeingclr');
        if (selectElement.value == 4) 
		{
             dyeingColorTd.show();
        } else {
             dyeingColorTd.hide();
        }
    }
</script>
</body>
</html>
