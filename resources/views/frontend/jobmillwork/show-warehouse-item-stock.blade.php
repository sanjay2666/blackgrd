<?php
	use \App\Http\Controllers\CommonController;
?>
<!DOCTYPE html>
<html lang="en">
<head>
@include('frontend.common.head') 
</head>
<body class="hold-transition sidebar-mini jobmillwork-page jobmill-dispatch-page">
  
  <div id="preloader">
    <div id="status"></div>
  </div>
  
  <div class="wrapper"> @include('frontend.common.header')
    <div class="content-wrapper">
      <section class="content">
        <div class="row">
          <div class="col-sm-12">
            {!! display_message('message') !!}
            <div class="panel panel-bd lobidrag">
              <div class="panel-heading jobmill-page-heading">
                <div class="btn-group" id="buttonexport"> <a href="javascript:void(0);">
                    <h4>Available Item List</h4>
                  </a> </div>
              </div>
              <div class="panel-body">
			  
                <div class="jobmill-filter-box">
                  <form action="{{ route('show-warehouse-item-stock') }}" method="GET" role="search" class="jobmill-filter-form">
                    @csrf
                    
                    <div class="col-sm-2 col-xs-12">
                      <input type="text" class="form-control" name="itemName" id="itemName" value="<?=$itemName;?>" placeholder="Search by Item Name">
					  <input type="hidden" id="itemId" name="itemId" value="{{ $itemId }}">
                    </div>
                    
                    <div class="col-sm-2 col-xs-12">
						<select class="form-control" name="item_type" id="item_type">
							<option value="">Select Item Type</option>
							<?php foreach($dataIT as $row) { ?>
								<option value="<?=$row->item_type_id;?>" <?php if($row->item_type_id == $item_type) { ?> selected <?php } ?>><?=$row->item_type_name;?></option>
							<?php } ?>
						</select> 					
					</div> 					
					<div class="col-sm-2 col-xs-12">
                      <input type="text" class="form-control" name="colorSearch" id="colorSearch" value="{{ $colorSearch }}" placeholder="Color">
					</div>
					
					<div class="col-sm-2 col-xs-12">
						<input type="text" class="form-control" name="LotNumSearch" id="LotNumSearch" value="{{ $LotNumSearch }}" placeholder="Lot Number.">       
					</div>
					
                    <div class="col-sm-1 col-xs-12">
                      <input type="submit" name="sbtSearch" class="btn btn-success" value="Search">
                    </div>
					<div class="col-sm-1 col-xs-12">
					<button type="button" class="btn btn-default" onclick="window.location='{{ route('show-warehouse-item-stock') }}'">Reset</button>
					</div>
                  </form>
				  
				  
               
                  <div class="col-sm-2 col-xs-12">  </div>
                </div>
				
				<form action="{{ route('storeStockForMillDispatch') }}" method="post" name="creat_chalan_for_mill_dispatch" id="creat_chalan_for_mill_dispatch">
				@csrf
				<?php if(!empty($itemId)) { ?>
				<div class="table-responsive jobmill-card-table">
						<input type="hidden" class="form-control" name="itemName" id="itemName" value="<?=$itemName;?>" placeholder="Search by Item Name. ">
						<input type="hidden" id="itemId" name="itemId" value="{{ $itemId }}">				
					 
				<table class="table table-bordered custom-table jobmill-form-table">
					<tbody>
						
						<tr class="jobmill-section-row jobmill-section-row-warning">
							<td colspan="5" class="text-center">
								<i class="fa fa-file-text-o"></i> VOUCHER & CHALAN DETAILS
							</td>
						</tr>

						
						<tr>
							<td>
								<label for="voucher_number"><strong>Voucher Number</strong></label>
								<input type="text" id="voucher_number" required class="form-control" name="voucher_number" value="<?=$totChDispach;?>">
							</td>
							<td>
								<label for="chalan_number"><strong>Chalan Number</strong></label>
								<input type="text" id="chalan_number" required class="form-control" name="chalan_number" value="<?=$totChDispach;?>">
							</td>
							<td>
								<label for="chalan_date"><strong>Chalan Date</strong></label>
								<input type="text" id="chalan_date" required class="form-control" name="chalan_date" value="{{old('chalan_date')}}">
							</td>
							<td>
								<label for="process_type"><strong>Current Item Type</strong></label>
								<select id="process_type" required class="form-control" name="process_type">
									<?php foreach($processI as $prow) { ?>
									<option value="<?=$prow->id;?>"><?=$prow->process_name;?></option>
									<?php } ?>
								</select>
							 
								<label for="work_type_id"><strong>For Work Type</strong></label>
								<select id="work_type_id" required class="form-control" name="work_type_id"> 
									 
									<option value="">Select Work Type</option>
									<option value="weaving">Weaving</option>
									<option value="dyeing">Dyeing</option>
									<option value="coating">Coating</option>
									<option value="printing">Printing</option>
									<option value="extra">Extra</option> 
								</select>
							</td>
							
							<td>
								<label for="work_name"><strong>Work Name</strong></label>
								<input type="text" id="work_name" name="work_name" class="form-control" placeholder="Work Name" required value="{{old('work_name')}}"> 
							</td>
							
						</tr>

						
						<tr class="jobmill-section-row jobmill-section-row-info">
							<td colspan="5" class="text-center">
								<i class="fa fa-user"></i> VENDOR DETAILS
							</td>
						</tr>

						
						<tr>
							<td>
								<label for="vendor_name"><strong>Vendor Name *</strong></label>
								<input type="text" id="vendor_name" name="vendor_name" class="form-control" placeholder="Vendor Name" required value="{{old('vendor_name')}}"> 
								<input type="hidden" id="individual_id" name="individual_id" required>

								<label style="margin-top: 5px;"><i class="fa fa-phone"></i> Phone: <span id="phone" class="text-primary"></span></label>
								<input type="hidden" name="mobile" id="mobile">
								<input type="hidden" name="email" id="email">

								<label>GSTIN: <span id="gst_label" class="text-primary"></span></label>
							</td>

							<td>
								<label><strong>Billing Address</strong></label> 
								<p><span id="address" class="text-muted"></span></p>
							</td>

							<td>
								<label><strong>Shipping Address</strong></label>
								<p><span id="Shipaddress" class="text-muted"></span></p>
							</td>

							<td>
								<label for="allotment_remark"><strong>Alloted Remark</strong></label>
								<input type="text" id="allotment_remark" name="allotment_remark" class="form-control" placeholder="Alloted Remark" required value="{{old('allotment_remark')}}"> 
							</td>

							<td>
								<a class="btn btn-primary btn-sm" id="add_billing_shipping_address" target="_blank" href="{{ \Illuminate\Support\Facades\Route::has('add-individualaddress') ? route('add-individualaddress') : 'javascript:void(0);' }}">
									<i class="fa fa-plus"></i> Add Address
								</a>
							</td>
						</tr>
					</tbody>
				</table>

				</div>
				<?php } ?>
                <div class="table-responsive jobmill-card-table"> 
				<div class="jobmill-total-line">
						<strong>Total Bal Qty: <span id="totalBalQty2">0</span></strong>
					</div>	
				 <table id="dataTableExample1" class="table table-bordered table-striped table-hover jobmill-data-table">
                  <thead>
                    <tr class="info">                        
					  <th>Select</th>                    
					  <th>Item Name</th>
                      <th>Item Type</th> 					  
                      <th>Taka No.</th> 					  
                      <th>Lot No.</th>					  
                      <th>Dyeing</th>  				  
                      <th>Coating</th>  
                      <th>Bal Qty</th>                  
                      <th>Unit</th>              
                      <th>Reason</th> 
                      <th>Date</th>
                    </tr>
                  </thead>
                  <tbody> 
				 
				 
				<?php 
				$totalStock = 0;
				foreach($dataWI as $data) 
				{
					$id =  enc($data->id);
					$unitTypeId = $data->unit_type_id;
					$wisId 		= $data->id;
					if($unitTypeId =='4') $unitType = 'Kg';
					else  $unitType = 'Meter';
				?>
                  <tr id="Mid{{ $wisId }}">                     
                     
					<td><input type="checkbox" name="wisId[]" onClick="updateTotal(this, {{ $data->insp_bal_quan_size }})" value="<?=$wisId;?>" id="wisId">
					 <button type="button"
							  class="btn btn-xs btn-warning break-meter-btn"
							  data-wisid="{{ $wisId }}"
							  data-bal="{{ $data->insp_bal_quan_size }}">
						Break
					  </button>
					<?=$wisId;?>
					
					</td>
					<td> {{ $data['Item']->item_name ?? '' }} </td>
					<td> {{ $data['ItemType']->item_type_name ?? '' }} </td>
					<td> {{ $data->insp_taka_number }}  </td>
					<td> {{ $data->dyeing_lot_number }}  </td> 
					<td> {{ $data->dyeing_color }}  </td> 
					<td> {{ $data->coated_pvc }}  </td>  
					<td> {{ $data->insp_bal_quan_size }}  </td>					
					<td> {{ $unitType }}</td>				
					<td> {{ @$data['FabricFaultReason']->reason }}</td>
					<td> {{ date('d-m-Y',strtotime($data->created)) }} </td>					                 
                  </tr>
                <?php } ?>
				  <tr class="center text-left"><td class="center" colspan="20"> &nbsp; </td></tr>	
				  <tr class="center text-left">
                    <td class="center" colspan="20"> 
						<button type="submit" name="Submit" value="StockForMillDispatch" class="btn btn-success">Save  </button>  						
					</td>
                  </tr>		
                  <tr class="center text-center">
                    <td class="center" colspan="16"><div class="pagination"> {{ $dataWI->links('vendor.pagination.bootstrap-4')}} </div></td>
                  </tr> 
                  </tbody>                  
                </table>				
                <div class="jobmill-total-line">
						<strong>Total Bal Qty: <span id="totalBalQty">0</span></strong>
					</div>	
					</div>
				</form>  
              </div>
            </div>
          </div>
        </div>
      </section>
      
    </div>
    
	
<div id="breakMeterModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="breakMeterModalLabel">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <form id="breakMeterForm">
        <div class="modal-header">
          <h5 class="modal-title" id="breakMeterModalLabel">Break Meter (e.g. 65+35)</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">&times;</button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="wis_id" id="bm_wis_id" value="">
          <div class="form-group">
            <label>Parts (format: 65+35)</label>
            <input type="text" name="parts" id="bm_parts" class="form-control" placeholder="e.g. 65+35" required>
            <small class="form-text text-muted">Enter exactly two positive decimal numbers separated by +. Sum must equal current balance.</small>
          </div>
          <div id="bm_error" class="alert alert-danger" style="display:none"></div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
          <button type="submit" id="saveSplitBtn" class="btn btn-primary">Save Split</button>
        </div>
      </form>
    </div>
  </div>
</div>

    
    @include('frontend.common.footer')
  </div>
  @include('frontend.common.footerscript')
  <script type="text/javascript" src="{{ asset('js/jquery.validate.js') }}"></script>
<script type="text/javascript">
$(document).ready(function(){

  $(document).on('click', '.break-meter-btn', function(){
    var wisId = $(this).data('wisid');
    var bal = $(this).data('bal');
    $('#bm_wis_id').val(wisId);
    $('#bm_parts').val('');
    $('#bm_parts').attr('placeholder', 'Sum must equal ' + bal);
    $('#bm_error').hide().text('');
    $('#breakMeterModal').modal('show');
  });

  
  $('#breakMeterForm').on('submit', function(e){
    e.preventDefault();
    $('#bm_error').hide().text('');

    var $form = $(this);
    var $btn = $('#saveSplitBtn');

    if ($btn.data('submitted')) return;

    var wis_id = $('#bm_wis_id').val();
    var parts = $('#bm_parts').val().trim();

    if(!wis_id || !parts){
      $('#bm_error').show().text('Please provide parts like 65+35.');
      return;
    }

    $btn.data('submitted', true);
    $btn.prop('disabled', true).hide();

    $.ajax({
      url: '{{ route("warehouse.breakMeter") }}',
      type: 'POST',
      data: {
        _token: '{{ csrf_token() }}',
        wis_id: wis_id,
        parts: parts
      },
      dataType: 'json',
      success: function(res){
        if(res.success){
          location.reload();
        } else {
          $('#bm_error').show().text(res.message || 'Something went wrong.');
          $btn.data('submitted', false);
          $btn.prop('disabled', false).show();
        }
      },
      error: function(xhr){
        var msg = 'Server error';
        if(xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
        $('#bm_error').show().text(msg);

        $btn.data('submitted', false);
        $btn.prop('disabled', false).show();
      }
    });

  });
 
   
   
});
</script>

<script>
let totalBalQty = 0;

function updateTotal(checkbox, qty) {
	if (checkbox.checked) {
		totalBalQty += parseFloat(qty);
	} else {
		totalBalQty -= parseFloat(qty);
	}
	document.getElementById('totalBalQty').innerText = totalBalQty;
	document.getElementById('totalBalQty2').innerText = totalBalQty;
}


</script>
<script>
var siteUrl = "{{url('/')}}";
  $(function() {
    $("#vendor_name").autocomplete({
      minLength: 0,
      source: siteUrl + '/' + "list_vendor",
      focus: function(event, ui) {
        $("#vendor_name").val(ui.item.name);
        return false;
      },
      select: function(event, ui) {
        var individualId = ui.item.id;
        getCustomerShipAddress(individualId);
        getCustomerBillAddress(individualId);
        $("#individual_id").val(ui.item.id);
        $("#vendor_name").val(ui.item.name);
        $("#mobile").val(ui.item.phone);
        $("#phone").val(ui.item.phone);
        $("#email").val(ui.item.email);
        $("#gst_label").html(ui.item.gstin);
        $("#phone").html(ui.item.phone);
        return false;
      }
    })
    .autocomplete("instance")._renderItem = function(ul, item) {
      return $("<li>")
        .append("<div>" + item.name + "<br> GSTIN - " + item.gstin + "</div>")
        .appendTo(ul);
    };
  });
</script>
<script>
function getCustomerShipAddress(individualId) 
{
	$.ajax({
	  type: "GET",
	  url: siteUrl + '/' + "ajax_script/search_customer_ship_address",
	  data: {
		"_token": "{{ csrf_token() }}",
		"individualId": individualId,
	  },
	  cache: false,
	  success: function(res) {

		$("#Shipaddress").html(res);

	  }
	})
}
</script>

<script>
function getCustomerBillAddress(individualId) 
{
	$.ajax({
	  type: "GET",
	  url: siteUrl + '/' + "ajax_script/search_customer_bill_address",
	  data: {
		"_token": "{{ csrf_token() }}",
		"individualId": individualId,
	  },
	  cache: false,
	  success: function(res) {

		$("#address").html(res);

	  }
	})
}
</script>

<script type="text/javascript">
var siteUrl = "{{url('/')}}";
$(document).ready(function() {
	$("#colorSearch").autocomplete({
		minLength: 0,
		source: function(request, response) {
			var item_search = $("#itemName").val();  
			$.ajax({
				url: siteUrl + '/' + "find_saleDyeingColor",
				dataType: "json",
				data: {
					term: request.term,
					item_search: item_search  
				},
				success: function(data) {
					response(data);
				}
			});
		},
		focus: function(event, ui) {
			$("#colorSearch").val(ui.item.dyeing_color);
			return false;
		},
		select: function(event, ui) {
			$("#colorSearch").val(ui.item.dyeing_color);
			return false;
		}
	}).autocomplete("instance")._renderItem = function(ul, item) {
		return $("<li>")
			.append("<div>" + item.dyeing_color + " </div>")
			.appendTo(ul);
	};
});
</script>
  
  
<script type="text/javascript">
	var siteUrl = "{{url('/')}}";
	$("#itemName").autocomplete({
		minLength: 0,
		source: siteUrl + '/' + "list_item",
		focus: function(event, ui) {
		  if (ui.item.part_number != '') {
			$("#itemName").val(ui.item.item_name); 
		  } else {
			$("#itemName").val(ui.item.item_name);
		  }
		  return false;
		},
		select: function(event, ui) {
		  if (ui.item.part_number != '') {
			$("#itemName").val(ui.item.item_name);
			$("#itemId").val(ui.item.item_id);			
		  } else {
			$("#itemName").val(ui.item.item_name);
			$("#itemId").val(ui.item.item_id);
		  }
		  return false;          
		}
	})
	.autocomplete("instance")._renderItem = function(ul, item) {
		return $("<li>") 
		  .append("<div>" + item.item_name + " </div>")
		  .appendTo(ul);
	}; 
</script>
  
  
<script>
$(function() {
  $("#chalan_date, #to_date").datepicker({
	dateFormat: "dd-mm-yy",
	changeMonth: true,
	changeYear: true,
	autoclose: true,
  });
});
</script> 

  
</body>

</html>
