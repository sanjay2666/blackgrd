<?php
	use \App\Http\Controllers\CommonController;
	
?>
<!DOCTYPE html>
<html lang="en">
<head>@include('frontend.common.head', ['pageTitle' => 'Warehouse Items List | Loomexa'])
</head>
<body class="hold-transition sidebar-mini warehouse-items-page">
 
<!-- Site wrapper -->
<div class="wrapper"> @include('frontend.common.header')
  <div class="content-wrapper"> 
    <!-- Content Header (Page header) -->
    <!-- Main content -->
    
    <section class="content">
      <div class="row">
        <div class="col-sm-12">
		  {!! display_message('message') !!}
          <div class="panel panel-bd lobidrag warehouse-list-panel">
            <div class="panel-heading warehouse-list-heading">
              <div>
                <h4><i class="fa fa-cubes"></i> Warehouse Items List</h4>
                <span>Click a row to expand related stock details. Use View for the full details page.</span>
              </div>
              <a href="{{ route('add-item-in-warehouse') }}" class="btn btn-add btn-sm"><i class="fa fa-plus"></i> Store Item</a>
            </div>
            <div class="panel-body">
              <div class="warehouse-filter-panel">
                <form action="" method="GET" role="search">
                  @csrf
				<div class="row">
					<div class="col-sm-2 col-xs-12">
						<div class="form-group">
							<input type="text" class="form-control input-sm" name="qsearch" id="qsearch" value="{{ $qsearch }}" placeholder="Search Item Name">
						</div>
					</div>				  
					<div class="col-sm-2 col-xs-12">
						<div class="form-group">
						<select class="form-control input-sm" name="item_type" id="item_type">
							<option value="">Select Item Type</option>
							<?php foreach($dataIT as $row) { ?>
								<option value="<?=$row->item_type_id;?>" <?php if($row->item_type_id == $item_type) { ?> selected <?php } ?>><?=$row->item_type_name;?></option>
							<?php } ?>
						</select> 
						</div>
					</div> 					
					
					<div class="col-sm-2 col-xs-12">
						<div class="form-group">
							<input type="text" class="form-control input-sm" name="colorSearch" id="colorSearch" value="{{ $colorSearch }}" placeholder="Color" autocomplete="off">
						</div>
					</div>
					<div class="col-sm-2 col-xs-12">
						<div class="form-group">
							<input type="text" class="form-control input-sm" name="coatingSearch" id="coatingSearch" value="{{ $coatingSearch }}" placeholder="Coating">
						</div>
					</div>
					<div class="col-sm-1 col-xs-12">
						<div class="form-group">
							<input type="text" class="form-control input-sm loomexa-datepicker" data-datepicker-max-date="0" name="from_date" id="from_date" placeholder="From Date" value="{{ $fromDate }}" >
						</div>
					</div> 
					<div class="col-sm-1 col-xs-12">
						<div class="form-group">
							<input type="text" class="form-control input-sm loomexa-datepicker" data-datepicker-max-date="0" name="to_date" id="to_date" placeholder="To Date" value="{{ $toDate }}" >
						</div>
					</div>   
			  
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
					<div class="col-sm-2 col-xs-12">
						<div class="form-group">
							<a href="{{ route('show') }}" class="btn btn-default btn-sm btn-block"><i class="fa fa-refresh"></i> Reset</a>
						</div>
					</div>
				</div>
                </form> 
              </div> 
              <div class="table-responsive warehouse-table-wrap">
                <table id="dataTableExample1" class="table table-bordered table-striped table-hover warehouse-list-table">
                  <thead>
                    <tr class="info"> 
						<th>Invoice No.</th>
						<th>Item Name</th>
						<th>Internal Name</th>
						<th>Warehouse</th>
						<th>Compartment</th>
						<th>R.Emp. Name</th>
						<th>Receiving Date</th>
						<th>Item Type</th>
						<th>Dyeing Color</th>
						<th>Coating</th>
						<th>Quantity</th>                       
						<th>Status</th>                       
                    </tr>
                  </thead>
                  <tbody>
                  
                  @foreach($dataWI as $data)
					<?php  
					
						// echo "<pre>"; print_r($data); exit; 
						$Id 					= enc($data->id);
						$unitTypeId 			= $data->unit_type_id;
						if($unitTypeId =='4') 	$unitType = 'Kg';
						else  					$unitType = 'Meter';
						$getItemName 			= $data['Item']->item_name ?? '';
						$getItemInternalName 	= $data['Item']->internal_item_name ?? '';
						$ReceiverName 			= $data['ReceiverIndividual']->name ?? ''; // CommonController::getIndividualName($data->receiver_id);
					?>
					
                  <tr id="Mid{{ $data->id }}" class="warehouse-main-row" data-row-id="{{ $data->id }}" data-detail-url="{{ route('show-stock-details-inline', ['id' => $Id]) }}"> 
					<td>{{ $data['WarehouseItem']->invoice_number ?? '' }} <span class="muted-id">#{{ $data->id }}</span></td>     
					<td> {{ $getItemName }} </td>
					<td> {{ $getItemInternalName }} </td>                    
                    <td> {{ $data['Warehouse']->warehouse_name ?? '' }} </td>
                    <td> {{ $data['WarehouseCompartment']->compartment_name ?? '' }} </td>
                    <td> {{ $ReceiverName }} </td>
                    <td> {{ !empty($data->receive_date) ? date('M jS, Y',strtotime($data->receive_date)) : '' }} </td>
                    <td> {{ $data['ItemType']->item_type_name ?? CommonController::getItemTypeName($data->item_type_id) }} </td>
                    <td> {{ $data->dyeing_color }} </td>
                    <td> {{ $data->coating_type }} </td>
                    <td> {{ $data->item_qty }} {{ $unitType }} </td>					
					<td> <a target="_blank" class="btn btn-info btn-xs stock-view-link" href="{{ route('show-stock-details-listing', ['id' => $Id]) }}"><i class="fa fa-external-link"></i> View</a></td> 
                  </tr>
				  
                  @endforeach
                  <tr class="center text-center">
                    <td class="center warehouse-pagination" colspan="12">{{ $dataWI->appends(request()->query())->links('vendor.pagination.bootstrap-4') }}</td>
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
  @include('frontend.common.footer') </div>
@include('frontend.common.footerscript')
<script type="text/javascript" src="{{ asset('js/jquery.validate.js') }}"></script>

<script type="text/javascript">
var siteUrl = "{{url('/')}}";
</script>

<script type="text/javascript">
	$(document).on('click', '.stock-view-link, .stock-view-link *', function(event) {
		event.stopPropagation();
	});

	$(document).on('click', '.warehouse-main-row', function() {
		var $row = $(this);
		var rowId = $row.data('row-id');
		var detailUrl = $row.data('detail-url');
		var $nextRow = $row.next('.stock-detail-row');

		if ($nextRow.length) {
			$nextRow.remove();
			$row.removeClass('stock-row-open');
			return;
		}

		$('.stock-detail-row').remove();
		$('.warehouse-main-row').removeClass('stock-row-open');

		$row.addClass('stock-row-open');
		$row.after(
			'<tr class="stock-detail-row" id="StockDetailRow' + rowId + '">' +
				'<td colspan="12"><div class="text-center stock-loading"><i class="fa fa-spinner fa-spin"></i> Loading stock details...</div></td>' +
			'</tr>'
		);

		$.ajax({
			type: 'GET',
			url: detailUrl,
			cache: false,
			success: function(response) {
				$('#StockDetailRow' + rowId + ' td').html(response);
			},
			error: function(xhr) {
				var message = xhr.responseText || 'Unable to load stock details.';
				$('#StockDetailRow' + rowId + ' td').html('<div class="alert alert-danger stock-inline-alert">' + escapeHtml(message) + '</div>');
			}
		});
	});

	function escapeHtml(value) {
		return String(value || '')
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;')
			.replace(/'/g, '&#039;');
	}
</script>


 <script type="text/javascript">
     
    $("#qsearch").autocomplete({
        minLength: 0,
        source: siteUrl + '/' + "fabric_list_item",
        focus: function(event, ui) {
          if (ui.item.part_number != '') {
            $("#qsearch").val(ui.item.item_name); 
          } else {
            $("#qsearch").val(ui.item.item_name);
          }
          return false;
        },
        select: function(event, ui) {
          if (ui.item.part_number != '') {
            $("#qsearch").val(ui.item.item_name);
      
          } else {
            $("#qsearch").val(ui.item.item_name);
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


<script type="text/javascript">
	function deleteWarehouseItem(id)
	{
		if(confirm("Do you realy want to delete this record?"))
		{
			jQuery.ajax({
				type: "GET",
				url: siteUrl + '/' +"ajax_script/deleteWarehouseItem",
				data: {
					"_token": "{{ csrf_token() }}",
					"FId":id,
				},
				cache: false,
				success: function(msg)
				{
					$("#Mid"+id).hide();
				}
			});

		}

	}
</script>

<script type="text/javascript">
	$("#colorSearch").autocomplete({
		minLength: 0,
		source: function(request, response) {
			$.ajax({
				url: siteUrl + "/list_master_color",
				data: {
					term: request.term,
					id: ""
				},
				success: function(data) {
					response($.map(data, function(item) {
						return {
							label: item.name,
							value: item.name
						};
					}));
				}
			});
		},
		focus: function(event, ui) {
			$("#colorSearch").val(ui.item.label);
			return false;
		},
		select: function(event, ui) {
			$("#colorSearch").val(ui.item.label);
			return false;
		}
	}).autocomplete("instance")._renderItem = function(ul, item) {
		return $("<li>").append("<div>" + escapeHtml(item.label) + "</div>").appendTo(ul);
	};

	$("#coatingSearch").autocomplete({
		minLength: 0,
		source: siteUrl + "/list_coating",
		focus: function(event, ui) {
			$("#coatingSearch").val(ui.item.name);
			return false;
		},
		select: function(event, ui) {
			$("#coatingSearch").val(ui.item.name);
			return false;
		}
	}).autocomplete("instance")._renderItem = function(ul, item) {
		return $("<li>").append("<div>" + item.name + "</div>").appendTo(ul);
	};
</script>

</body>
</html>
