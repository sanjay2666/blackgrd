<?php
	use \App\Http\Controllers\CommonController;
	// exit;
?>
<!DOCTYPE html>
<html lang="en">
<head>@include('common.head', ['pageTitle' => 'Warehouse Balance Stock | Loomexa'])
</head>
<body class="hold-transition sidebar-mini warehouse-balance-page">
 
<!-- Site wrapper -->
<div class="wrapper"> @include('common.header')
  <div class="content-wrapper"> 
    <!-- Content Header (Page header) -->
    <!-- Main content -->
    
    <section class="content">
      <div class="row">
        <div class="col-sm-12">
		  {!! CommonController::display_message('message') !!}
          <div class="panel panel-bd lobidrag warehouse-balance-panel">
            <div class="panel-heading warehouse-balance-heading">
              <div>
                <h4><i class="fa fa-balance-scale"></i> Warehouse Balance Stock</h4>
                <span>Review current item balance and refresh stock quantity from warehouse stock records.</span>
              </div>
            </div>
            <div class="panel-body">
               <div class="warehouse-balance-filter">
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
								<select class="form-control input-sm" name="warehouseId" id="warehouseId" onChange="selectCompartment(this.value);">
									<option value="">Select Warehouse</option>
									<?php foreach($dataW as $val) { ?>
										<option value="<?=$val->id;?>" <?php if ($val->id == $warehouseId) { ?> selected <?php } ?>><?=$val->warehouse_name;?></option>
									<?php } ?>
								</select>
								<span id="warehouseCompIdDiv"></span>
							</div>
						</div>  
						<div class="col-sm-1 col-xs-6">
							<div class="form-group">
								<input type="text" class="form-control input-sm" name="from_date" id="from_date" placeholder="From Date" value="{{ $fromDate }}" >
							</div>
						</div> 
						<div class="col-sm-1 col-xs-6">
							<div class="form-group">
								<input type="text" class="form-control input-sm" name="to_date" id="to_date" placeholder="To Date" value="{{ $toDate }}" >
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
								<a href="{{ route('show-balance-table-stock') }}" class="btn btn-default btn-sm btn-block"><i class="fa fa-refresh"></i> Reset</a>
							</div>
						</div>
					</div>
                </form> 
              </div> 
              <div class="table-responsive warehouse-balance-table-wrap">
                <table id="dataTableExample1" class="table table-bordered table-striped table-hover warehouse-balance-table">
                  <thead>
                    <tr class="info"> 
						<th>ID</th>
						<th>Item Name</th>
						<th>Internal Name</th>
						<th>Warehouse</th> 
						<th>Receiver</th>
						<th>Receiving Date</th>
						<th>Item Type</th>
						<th>Color</th>
						<th>Coating</th>
						<th>Quantity</th>                       
						<th>Details</th>                      
						<th>Refresh</th>                       
                    </tr>
                  </thead>
                  <tbody>
                  
                  @foreach($dataWB as $data)
					<?php   
					
						// echo "<pre>"; print_r($data); exit;
						$Id 					= enc($data->id);
						$unitTypeId 			= $data->unit_type_id;
						if($unitTypeId =='4') 	$unitType = 'Kg';
						else  					$unitType = 'Meter';
						$getItemName 			= $data['Item']->item_name ?? '';
						$getItemInternalName 	= $data['Item']->internal_item_name ?? '';
						$ReceiverName 			= @$data['ReceiverIndividual']->name;  
					?>
					
                  <tr id="Mid{{ $data->id }}" class="warehouse-balance-row"> 
					<td><span class="muted-id">#{{ $data->id }}</span></td>     
					<td class="warehouse-balance-item-name">{{ $getItemName }}</td>
					<td>{{ $getItemInternalName }}</td>                    
                    <td> {{ $data['Warehouse']->warehouse_name ?? '' }} </td> 
                    <td> {{ $ReceiverName }} </td>
                    <td> {{ !empty($data->receive_date) ? date('M jS, Y',strtotime($data->receive_date)) : (!empty($data->created) ? date('M jS, Y',strtotime($data->created)) : '') }} </td>
                    <td> {{ CommonController::getItemTypeName($data->item_type_id) }} </td>
                    <td> {{ $data->dyeing_color }} </td> 
                    <td> {{ $data->coated_pvc }} </td> 
					<td><span class="warehouse-balance-qty" id="item_qty_{{ $data->id }}" data-unit="{{ $unitType }}">{{ $data->item_qty }} {{ $unitType }}</span></td>  
 					
					<td><a target="_blank" class="btn btn-info btn-xs" href="{{ route('show-stock-details-listing', ['id' => $Id]) }}"><i class="fa fa-external-link"></i> View</a></td> 
					<td><a class="btn btn-default btn-xs warehouse-refresh-btn" onclick="RefreshWarehouseItem({{ $data->id }})" href="javascript:void(0);"><i class="fa fa-refresh"></i></a>
					 
					</td> 
                  </tr>
				  
                  @endforeach
                  <tr class="center text-center">
                    <td class="center warehouse-balance-pagination" colspan="12">{{ $dataWB->appends(request()->query())->links('vendor.pagination.bootstrap-4') }}</td>
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
  @include('common.footer') </div>
@include('common.formfooterscript')
<script type="text/javascript" src="{{ asset('js/jquery.validate.js') }}"></script>

<script type="text/javascript">
var siteUrl = "{{ url('/') }}";
var fabricListItemUrl = "{{ route('fabric_list_item') }}";
</script>


 <script type="text/javascript">
    $("#qsearch").autocomplete({
        minLength: 0,
        source: function(request, response) {
          $.ajax({
            url: fabricListItemUrl,
            dataType: "json",
            data: {
              term: request.term,
              item_type_id: $("#item_type").val() || "all"
            },
            success: response
          });
        },
        focus: function(event, ui) {
          if ((ui.item.part_number || '') != '') {
            $("#qsearch").val(ui.item.item_name); 
          } else {
            $("#qsearch").val(ui.item.item_name);
          }
          return false;
        },
        select: function(event, ui) {
          if ((ui.item.part_number || '') != '') {
            $("#qsearch").val(ui.item.item_name);
      
          } else {
            $("#qsearch").val(ui.item.item_name);
          }
          return false;          
        }
      })
      .autocomplete("instance")._renderItem = function(ul, item) {
        return $("<li>") 
          .append("<div>" + item.item_name + (item.internal_item_name ? " <small>(" + item.internal_item_name + ")</small>" : "") + "</div>")
          .appendTo(ul);
      };
      
       
  </script>


<script type="text/javascript">
function RefreshWarehouseItem(id) {
    var refreshIcon = $(".warehouse-refresh-btn[onclick='RefreshWarehouseItem(" + id + ")'] i");
    refreshIcon.addClass("fa-spin");

    // if(confirm("Do you really want to refresh this record?")) {
        jQuery.ajax({
            type: "GET",
            url: siteUrl + '/ajax_script/RefreshWarehouseItem',
            data: {
                "_token": "{{ csrf_token() }}",
                "FId": id
            },
            cache: false,
            success: function(response) {
                if (response.success) {
                    var qtyEl = $("#item_qty_" + id);
                    var unit = qtyEl.data("unit") || "";
                    qtyEl.text(response.new_qty + (unit ? " " + unit : ""));
                } else {
                    alert("Failed to refresh data");
                }
            },
            error: function() {
                alert("Something went wrong!");
            },
            complete: function() {
                refreshIcon.removeClass("fa-spin");
            }
        });
    // }
}
</script>

<script type="text/javascript">
function selectCompartment(Id) {
    var siteUrl = "{{ url('/') }}";

    $.ajax({
        type: "GET",
        url: "{{ route('search_warehouse_compartment') }}",
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

</body>
</html>
