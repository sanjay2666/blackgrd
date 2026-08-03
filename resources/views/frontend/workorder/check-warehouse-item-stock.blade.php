<?php
	use \App\Http\Controllers\CommonController;
?>
<!DOCTYPE html>
<html lang="en">
<head>@include('common.head')
</head>
<body class="hold-transition sidebar-mini">
<!--preloader-->
<div id="preloader">
  <div id="status"></div>
</div>
<!-- Site wrapper -->
<div class="wrapper"> @include('common.header')
  <div class="content-wrapperd"> 
    <!-- Content Header (Page header) -->
    <!-- Main content -->
    {!! CommonController::display_message('message') !!}
    <section class="content">
      <div class="row">
        <div class="col-sm-12">
          <div class="panel panel-bd lobidrag">
            <div class="panel-heading">
              <div class="btn-group" id="buttonexport"> <a href="javascript:void(0);">
                <h4>Warehouse Items Out List</h4>
                </a> </div>
            </div>
            <div class="panel-body">
              <div class="row" style="margin-bottom:5px">
                <form action="" method="GET" role="search">
                  @csrf
                  <div class="col-sm-4 col-xs-12">
                    <input type="text" class="form-control" name="qsearch" id="qsearch" value="{{ $qsearch }}" placeholder="Search by Purchase Number, Employee Name, Invoice number, Item Name">
                  </div>
                  <!------
						   <div class="col-sm-2 col-xs-12">
                             <input type="date" data-date-format="DD MMMM YYYY" class="form-control" name="from_date" placeholder="From Date" value="">                       
                          </div>
						  
                          <div class="col-sm-2 col-xs-12">
                            <input type="date" data-date-format="DD MMMM YYYY" class="form-control" name="to_date" placeholder="To Date" value="">                
                          </div> 
						   --->
                  <div class="col-sm-2 col-xs-12">
                    <input type="submit" name="sbtSearch" class="btn btn-success" value="Search">
                  </div>
                </form>
                <div class="col-sm-2 col-xs-12"> <a class="btn btn-add" href="add-warehouse-item-out"> <i class="fa fa-plus"></i> Add Warehouse Item Out</a> </div>
              </div>
              <!-- Plugin content:powerpoint,txt,pdf,png,word,xl -->
              <div class="table-responsive">
                <table id="dataTableExample1" class="table table-bordered table-striped table-hover">
                  <thead>
                    <tr class="info">
                      <th>Purchase Number</th>
                      <th>Warehouse Name</th>
                      <th>Warehouse Compartment</th>
                      
                      <th>Item Type</th>
                      <th>Alloted Quntity</th>
                      <th>Status</th>
                    </tr>
                  </thead>
                  <tbody>
                  
                  @foreach($dataWI as $data)
                  <?php 
								//	   echo "<pre>"; print_r($data); 
								   $id =  enc($data->id);
								  ?>
                  <tr id="Mid{{ $data->id }}">
                    <td> {{ $data['Purchase']->purchase_number }} </td>
                    <td> {{ $data['Warehouse']->warehouse_name }} </td>
                    <td> {{ $data['WarehouseCompartment']->compartment_name }} </td>
					
                    
                    <td> {!! CommonController::getItemType($data->item_type_id) !!}  </td>
                    <td> {{ $data->allotted_qty }} </td>
                    <td class="center"><a href="javascript:void(0);" onClick="deleteWarehouseItem({{ $data->id }})" class="btn btn-danger btn-xs"><i class="fa fa-trash-o"></i></a> </td>
                  </tr>
                  @endforeach
                  <tr class="center text-center">
                    <td class="center" colspan="8"><div class="pagination"> {{ $dataWI->links('vendor.pagination.bootstrap-4')}} </div></td>
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
var siteUrl = "{{url('/')}}";
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
</body>
</html>
