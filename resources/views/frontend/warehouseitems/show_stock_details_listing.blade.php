<?php
	use \App\Http\Controllers\CommonController;
	// echo "<pre>"; print_r(\Route::getRoutes()); 
	// echo "<pre>"; print_r($wbId); 
	// echo $wbId; // exit; 
?>
<!DOCTYPE html>
<html lang="en">
<head>@include('frontend.common.head', ['pageTitle' => 'Warehouse Stock Details | Loomexa'])
</head>
<body class="hold-transition sidebar-mini warehouse-stock-details-page">
<!--preloader-->
 
<!-- Site wrapper -->
<div class="wrapper"> @include('frontend.common.header')
  <div class="content-wrapper"> 
    <!-- Content Header (Page header) -->
    <!-- Main content -->
    
    <section class="content">
      <div class="row">
        <div class="col-sm-12">
		  {!! display_message('message') !!}
          <div class="panel panel-bd lobidrag stock-details-panel">
            <div class="panel-heading stock-details-heading">
              <div>
                <h4><i class="fa fa-list-alt"></i> Warehouse Stock Details</h4>
                <span>Review stock entries, documents, and compartment assignment.</span>
              </div>
              <a href="{{ route('show') }}" class="btn btn-default btn-sm"><i class="fa fa-arrow-left"></i> Warehouse Items</a>
            </div>
            <div class="panel-body">
              <div class="stock-details-filter-panel">
                <form action="" method="GET" role="search">
                  @csrf
				  
				  <?php /* ?>
					<div class="col-sm-2 col-xs-12">
						<input type="text" class="form-control" name="qsearch" id="qsearch" value="{{ $qsearch }}" placeholder="Search by Item Name">
					</div> 
					
					<div class="col-sm-2 col-xs-12">
						<input type="text" class="form-control" id="work_sale_ord" name="work_sale_ord" value="{{ $workSaleOrd }}" placeholder="Search by S.O or W.O. Number" aria-label="Search by Sale Order or Work Order Number" autocomplete="off">
					</div> 	
					
					<div class="col-sm-2 col-xs-12">
						<select class="form-control" name="item_type" id="item_type">	
							<option value="">Please Select Item</option>						
							<?php foreach($dataIT as $row) { ?>
								<option value="<?=$row->item_type_id;?>" <?php if($row->item_type_id == $item_type) { ?> selected <?php } ?>><?=$row->item_type_name;?></option>
							<?php } ?>
						</select>  
					</div> 						
					<?php */ ?>
					 
					<div class="col-sm-2 col-xs-12">
						<div class="form-group">
						<select class="form-control input-sm" name="stockType" id="stockType">						
							<option value="">Stock Type</option> 
							<option value="allstock" @if($stockType=="allstock") selected @endif> All</option>
							<option value="stockin" @if($stockType=="stockin") selected @endif>Stock In</option>
							<option value="stockout" @if($stockType=="stockout") selected @endif>Stock Out</option>							 
						</select>
						</div>
					</div> 
					 
					<?php /*  ?> 
					<div class="col-sm-2 col-xs-12">
						<input type="text" class="form-control" name="from_date" id="from_date" placeholder="From Date" value="{{ $fromDate }}">
					</div> 
					<div class="col-sm-2 col-xs-12">
						<input type="text" class="form-control" name="to_date" id="to_date" placeholder="To Date" value="{{ $toDate }}">                
					</div> 
					  <p>&nbsp;  </p>
					<div class="col-sm-6 col-xs-12"></div>  
					<div class="col-sm-2 col-xs-12">
						<select class="form-control" name="warehouseId" id="warehouseId" onChange="selectCompartment(this.value);">
							<option value="">Please Select Warehouse</option>
							<?php foreach($dataW as $val) { ?>
								<option value="<?=$val->id;?>" <?php if ($val->id == $warehouseId) { ?> selected <?php } ?>><?=$val->warehouse_name;?></option>
							<?php } ?>
						</select>
						<span id="warehouseCompIdDiv"></span>
					</div> 
					
					<?php */ ?>
					
					 <div class="col-sm-1 col-xs-6">
						<div class="form-group">
							<button type="submit" name="sbtSearch" class="btn btn-success btn-sm btn-block" value="Search"><i class="fa fa-search"></i> Search</button>
						</div>
					</div>
					<div class="col-sm-1 col-xs-6"> 
						<div class="form-group">
							<button type="submit" name="sbtSearch" class="btn btn-primary btn-sm btn-block" value="ExportToExcel"><i class="fa fa-download"></i> Export</button>
						</div>
                    </div>
					<div class="col-sm-1 col-xs-12">
						<div class="form-group">
							<a href="{{ route('show-stock-details-listing', ['id' => enc($wbId)]) }}" class="btn btn-default btn-sm btn-block"><i class="fa fa-refresh"></i> Reset</a>
						</div>
					</div>
					
                </form> 
				 
              </div>
			  
              <!-- Plugin content:powerpoint,txt,pdf,png,word,xl -->
              <div class="table-responsive stock-details-table-wrap">
                <table id="dataTableExample1" class="table table-bordered table-striped table-hover stock-details-table">
					<thead>
						<tr class="info">
							<th>Inv/JW</th>
							<th>Vendor</th>
							<th>Item</th>
							<th>Taka</th>
							<th>Lot</th>
							<th>Warehouse</th>
							<th>Compartment</th>
							<th>Receiver</th>
							<th>Rec. Date</th>
							<th>Type</th>
							<th>Dyeing</th>
							<th>Coating</th>
							<th>Qty</th>
							<th>Allot Qty</th>
							<th>Status</th>
						</tr>

					</thead>
					<tbody class="small">
						@foreach($dataWI as $data)
						<?php 							
							 //  echo "<pre>"; print_r($data['WarehouseItem']['Warehouse']->warehouse_name); exit;						
							   $wisId = $data->id;
							
							$unitTypeId 			= $data->unit_type_id;
							$unitType 				= ($unitTypeId == '2') ? 'Meter' : 'Kg';
							$itemTypeId 			= $data->item_type_id;
							$getReceiverName 		= $data['ReceiverIndividual']->name ?? 'N/A';
							$vendorName 			= $data['WarehouseItem']['Vendor']->name ?? 'N/A';
							$ItemTypeName           = $data['ItemType']->item_type_name ?? 'N/A';							
							$getItemName 			= $data['Item']->item_name ?? '';
							$getItemInternalName 	= $data['Item']->internal_item_name ?? '';    
							// $warehouse 				= $data['Warehouse']->warehouse_name ?? $data['WarehouseItem']['Warehouse']->warehouse_name ?? 'N/A';
							$warehouse 				= $data['WarehouseItem']['Warehouse']->warehouse_name ?? 'N/A';
							$warehouseComp 			= $data['WarehouseCompartment']->compartment_name ?? $data['WarehouseItem']['WarehouseCompartment']->compartment_name ?? 'N/A';
							$stockWarehouseId 		= $data->warehouse_id ?? $data['WarehouseItem']->warehouse_id ?? '';
							$totQuantity 			= $data->insp_quan_size;
							$AvaTotQuan 			= $totQuantity - $data->insp_allot_quan_size;
							$fileD 					= $data->StockFile; 
							$invoiceNumber 			= $data['WarehouseItem']->invoice_number ?? $data->invoice_number ?? '';
							$invoiceCopyUrl 		= !empty($fileD->invoice_copy_file) ? asset($fileD->invoice_copy_file) : '';
							$packingSlipUrl 		= !empty($fileD->packing_slip_file) ? asset($fileD->packing_slip_file) : '';
							$ewayBillUrl 			= !empty($fileD->eway_bill_file) ? asset($fileD->eway_bill_file) : '';
							$lrCopyUrl 				= !empty($fileD->lr_copy_file) ? asset($fileD->lr_copy_file) : '';
						?>
						<tr id="Mid{{ $data->id }}">                      
							<td> <a href="javascript:void(0);" class="stock-doc-trigger" onclick='showDocumentModal(@json($invoiceNumber), @json($invoiceCopyUrl), @json($packingSlipUrl), @json($ewayBillUrl), @json($lrCopyUrl))'>{{ $invoiceNumber }}</a>
							<span class="muted-id">{{ $data->job_work_number }} #{{ $data->id }}</span>
							</td>          
							<td> {{ $vendorName }} </td>                    
							<td> {{ $getItemName }} </td>           
							<td> {{ $data->insp_taka_number }} </td>
							<td> {{ $data->dyeing_lot_number }} </td>
							<td class="warehouse-cell">
							<span class="warehouseName" data-id="{{ $data->id }}" data-selected-value="{{ $stockWarehouseId }}">{{ $warehouse }}</span>
							<select class="warehouseSelect form-control input-sm is-hidden" data-id="{{ $data->id }}">
							@foreach($dataW as $warehouseOption)
							<option value="{{ $warehouseOption->id }}" @if((int) $warehouseOption->id === (int) $stockWarehouseId) selected @endif>{{ $warehouseOption->warehouse_name }}</option>
							@endforeach
							</select>
							</td>
							<td class="compartment-cell">
							<span class="warehouseComp" data-id="{{ $data->id }}" data-selected-value="{{ $data['WarehouseCompartment']->id ?? '' }}">
							{{ $warehouseComp }}
							</span>
							<select class="warehouseCompSelect form-control input-sm is-hidden" data-id="{{ $data->id }}">
							<option value="">Select...</option>
							</select>
							<button class="editWarehouseComp btn btn-primary btn-xs" data-id="{{ $data->id }}">
							<span class="glyphicon glyphicon-pencil"></span>  
							</button>

							<button class="saveWarehouseComp btn btn-success btn-xs is-hidden" data-id="{{ $data->id }}">
							<span class="glyphicon glyphicon-ok"></span>  
							</button>

							<button class="cancelWarehouseComp btn btn-default btn-xs is-hidden" data-id="{{ $data->id }}">
							<span class="glyphicon glyphicon-remove"></span>
							</button>

							</td>
							<td> {{ $getReceiverName }} </td>
							<td> {{ !empty($data->receive_date) ? \Carbon\Carbon::parse($data->receive_date)->format('d-m-Y') : '' }} </td>
							<td> {{ $ItemTypeName }} </td>
							<td> {{ $data->dyeing_color }} </td>
							<td> {{ $data->coating_type }} </td>
							 
							
							
							<td> {{ round($AvaTotQuan,2) }}  {{ $unitType }} </td>
							<td> {{ round($data->insp_allot_quan_size,2) }}  {{ $unitType }} </td>
							 
							<td class="center action-cell">
							<?php 
							$userId = Auth::id();
							if($userId =='1') { 
							?>
							<a href="javascript:void(0);" onClick="deleteWarehouseItem({{ $data->id }})" class="btn btn-danger btn-xs"><i class="fa fa-trash-o"></i></a>
							<?php } ?>
							
							
							</td> 
							
							 
						</tr>
						@endforeach
						<tr class="center text-center">
							<td class="center" colspan="15"><div class="pagination"> {{ $dataWI->links('vendor.pagination.bootstrap-4') }} </div></td>
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
  <!-- /.content-wrapper -->
  <div class="modal fade" id="documentModal" tabindex="-1" role="dialog" aria-labelledby="documentModalLabel">
	  <div class="modal-dialog modal-lg" role="document">
		<div class="modal-content">

		  <div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-label="Close">
			  <span aria-hidden="true">&times;</span>
			</button>
			<h4 class="modal-title" id="documentModalLabel">Documents</h4>
		  </div>

		  <div class="modal-body" id="documentModalBody">
			<!-- content inserted by JS -->
		  </div>

		</div>
	  </div>
	</div>
  
  @include('frontend.common.footer') </div>
@include('frontend.common.footerscript')
<script type="text/javascript" src="{{ asset('js/jquery.validate.js') }}"></script>

<script>
 document.addEventListener('DOMContentLoaded', function () {
    const editButtons = document.querySelectorAll('.editWarehouseComp');
    editButtons.forEach(button => {
        button.addEventListener('click', function (event) {
            event.preventDefault();
            const id = this.getAttribute('data-id');
            const row = this.closest('tr');
            const cell = this.closest('.compartment-cell');
            const selectBox = row.querySelector(`.warehouseCompSelect[data-id='${id}']`);
            const span = row.querySelector(`.warehouseComp[data-id='${id}']`);
            const warehouseSelect = row.querySelector(`.warehouseSelect[data-id='${id}']`);
            const warehouseSpan = row.querySelector(`.warehouseName[data-id='${id}']`);
            const selectedValue = span.getAttribute('data-selected-value'); // Retrieve the currently saved value
            const warehouseId = warehouseSpan.getAttribute('data-selected-value');

            // Fetch options for the select box and set the saved value as selected
            warehouseSelect.value = warehouseId;
            fetchWarehouseCompOptions(id, warehouseId, selectBox, selectedValue);

            // Show select box and save/cancel buttons, hide display text and edit button.
            cell.classList.add('is-editing');
            this.classList.add('is-hidden');
            selectBox.classList.remove('is-hidden');
            warehouseSpan.classList.add('is-hidden');
            warehouseSelect.classList.remove('is-hidden');
            const saveButton = document.querySelector(`.saveWarehouseComp[data-id='${id}']`);
            if (saveButton) {
                saveButton.classList.remove('is-hidden');
            }
            const cancelButton = document.querySelector(`.cancelWarehouseComp[data-id='${id}']`);
            if (cancelButton) {
                cancelButton.classList.remove('is-hidden');
            }
        });
    });

    // Handle save button clicks
    const saveButtons = document.querySelectorAll('.saveWarehouseComp');
    saveButtons.forEach(button => {
        button.addEventListener('click', function (event) {
            event.preventDefault();
            const id = this.getAttribute('data-id');
            const row = this.closest('tr');
            const cell = this.closest('.compartment-cell');
            const selectBox = row.querySelector(`.warehouseCompSelect[data-id='${id}']`);
            const selectedValue = selectBox.value;
            const span = row.querySelector(`.warehouseComp[data-id='${id}']`);
            const warehouseSelect = row.querySelector(`.warehouseSelect[data-id='${id}']`);
            const warehouseSpan = row.querySelector(`.warehouseName[data-id='${id}']`);
            const warehouseId = warehouseSelect.value;

            this.disabled = true;
            updateWarehouseComp(id, warehouseId, selectedValue).done(function(res) {
                if (!res.success) {
                    alert(res.message || 'Warehouse location update failed.');
                    return;
                }

                const selectedText = selectBox.options[selectBox.selectedIndex] ? selectBox.options[selectBox.selectedIndex].text : span.textContent;
                span.textContent = selectedText;
                span.setAttribute('data-selected-value', selectedValue);
                warehouseSpan.textContent = warehouseSelect.options[warehouseSelect.selectedIndex].text;
                warehouseSpan.setAttribute('data-selected-value', warehouseId);
                cell.classList.remove('is-editing');
                button.classList.add('is-hidden');
                selectBox.classList.add('is-hidden');
                warehouseSelect.classList.add('is-hidden');
                warehouseSpan.classList.remove('is-hidden');
                const editButton = row.querySelector(`.editWarehouseComp[data-id='${id}']`);
                if (editButton) {
                    editButton.classList.remove('is-hidden');
                }
                const cancelButton = row.querySelector(`.cancelWarehouseComp[data-id='${id}']`);
                if (cancelButton) {
                    cancelButton.classList.add('is-hidden');
                }
            }).fail(function(xhr) {
                alert(xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Warehouse location update failed.');
            }).always(function() {
                button.disabled = false;
            });
        });
    });

    const cancelButtons = document.querySelectorAll('.cancelWarehouseComp');
    cancelButtons.forEach(button => {
        button.addEventListener('click', function (event) {
            event.preventDefault();
            const id = this.getAttribute('data-id');
            const row = this.closest('tr');
            const cell = this.closest('.compartment-cell');
            const selectBox = row.querySelector(`.warehouseCompSelect[data-id='${id}']`);
            const span = row.querySelector(`.warehouseComp[data-id='${id}']`);
            const warehouseSelect = row.querySelector(`.warehouseSelect[data-id='${id}']`);
            const warehouseSpan = row.querySelector(`.warehouseName[data-id='${id}']`);
            const editButton = row.querySelector(`.editWarehouseComp[data-id='${id}']`);
            const saveButton = row.querySelector(`.saveWarehouseComp[data-id='${id}']`);

            selectBox.value = span.getAttribute('data-selected-value') || '';
            warehouseSelect.value = warehouseSpan.getAttribute('data-selected-value') || '';
            cell.classList.remove('is-editing');
            selectBox.classList.add('is-hidden');
            warehouseSelect.classList.add('is-hidden');
            warehouseSpan.classList.remove('is-hidden');
            this.classList.add('is-hidden');

            if (saveButton) {
                saveButton.classList.add('is-hidden');
            }
            if (editButton) {
                editButton.classList.remove('is-hidden');
            }
        });
    });

    document.querySelectorAll('.warehouseSelect').forEach(selectBox => {
        selectBox.addEventListener('change', function() {
            const row = this.closest('tr');
            const stockId = this.getAttribute('data-id');
            fetchWarehouseCompOptions(stockId, this.value, row.querySelector(`.warehouseCompSelect[data-id='${stockId}']`), '');
        });
    });
});

 

</script>

<script>
function fetchWarehouseCompOptions(id, warehouseId, selectBox, selectedValue) {
    var siteUrl = "{{ url('/') }}";
    selectBox.dataset.warehouseId = warehouseId;
    selectBox.innerHTML = '<option value="">Select...</option>';

    $.ajax({
        type: "GET",
        url: siteUrl + "/ajax_script/get_warehouse_compartment_options",
        data: {
            "_token": "{{ csrf_token() }}",
            "Id": id,
            "warehouseId": warehouseId
        },
        cache: false,
        success: function(res) {
            if (selectBox.dataset.warehouseId !== String(warehouseId)) {
                return;
            }

            selectBox.innerHTML = '';
            res.forEach(option => {
                const isSelected = option.id == selectedValue ? 'selected' : '';
                selectBox.innerHTML += '<option value="' + option.id + '" ' + isSelected + '>' + option.compartment_name + '</option>';
            });
        }
    });
}


 

function updateWarehouseComp(id, warehouseId, selectedValue) {
    var siteUrl = "{{ url('/') }}";

    return $.ajax({
        type: "GET",
        url: siteUrl + "/ajax_script/updateWarehouseComp",
        data: {
            "_token": "{{ csrf_token() }}",
            "id": id,
            "warehouseId": warehouseId,
            "selectedValue": selectedValue
        },
        cache: false
    });
}



 
</script>
  
<script type="text/javascript">
var siteUrl = "{{url('/')}}";
    function deleteWarehouseItem(id)
    {
        if(confirm("Do you really want to delete this record?"))
        {
            jQuery.ajax({
                type: "GET",
                url: siteUrl + '/' +"ajax_script/deleteWarehouseItemStock",
                data: {
                    "_token": "{{ csrf_token() }}",
                    "FId":id,
                },
                cache: false,
                success: function(response)
                {
                    if(response.error) {
                       // alert(response.error);
                    } else if(response.success) {
                      //  alert(response.success);
                        $("#Mid"+id).hide();
                    } else {
                        alert("An error occurred while deleting the record.");
                    }
                },
                error: function(xhr, status, error) {
                    alert("An error occurred while processing your request. Please try again later.");
                }
            });

        }

    }
</script>
 
<script type="text/javascript">
function selectCompartment(Id) {
    var siteUrl = "{{ url('/') }}";

    $.ajax({
        type: "GET",
        url: siteUrl + "/ajax_script/search_warehouse_compartment",
        data: {
            "_token": "{{ csrf_token() }}",
            "Id": Id,
        },
        cache: false,
        success: function(res) {
            $("#warehouseCompIdDiv").html(res);
        }
    });
}
</script>

<script>
  $(function() {
    $("#from_date, #to_date").datepicker({
      dateFormat: "dd-mm-yy",
      changeMonth: true,
      changeYear: true,
      autoclose: true,
	  maxDate: 0,
    });
  });
</script>



<script>
function showDocumentModal(takaNumber, invoiceUrl, packingUrl, ewayUrl, lrUrl) {
    const files = [
        { label: "Invoice Copy", url: invoiceUrl },
        { label: "Packing Slip", url: packingUrl },
        { label: "E-Way Bill", url: ewayUrl },
        { label: "LR Copy", url: lrUrl }
    ];

    let html = '<div class="row">';

    files.forEach(function(file) {
        if (file.url && file.url.trim() !== '') {
            const fileExt = file.url.split('.').pop().toLowerCase();

            html += '<div class="col-sm-6">';
            html += '<div class="panel panel-default">';
            html += '<div class="panel-heading"><strong>' + file.label + '</strong></div>';
            html += '<div class="panel-body stock-document-panel-body">';

            if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(fileExt)) {
                html += '<img src="' + file.url + '" alt="' + file.label + '" class="stock-document-preview-img">';
            } else if (fileExt === 'pdf') {
                html += '<iframe src="' + file.url + '" class="stock-document-preview-frame"></iframe>';
            } else {
                html += '<p>[Preview not available]</p>';
            }

            html += '<p class="stock-document-download"><a href="' + file.url + '" class="btn btn-primary btn-sm" download>Download</a></p>';

            html += '</div></div></div>';
        }
    });

    html += '</div>';

    if (html === '<div class="row"></div>') {
        html = '<div class="alert alert-warning stock-inline-alert">No document uploaded for this stock.</div>';
    }

    $('#documentModalLabel').text("Documents for Invoice No: " + takaNumber);
    $('#documentModalBody').html(html);
    $('#documentModal').modal('show');
}
</script>

</body>
</html>
