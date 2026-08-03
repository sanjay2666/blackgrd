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
    <section class="content">
      <div class="row">
        <div class="col-sm-12"> {!! CommonController::display_message('message') !!}
          <div class="panel panel-bd lobidrag">
            <div class="panel-heading">
              <div class="btn-group" id="buttonexport"> <a href="javascript:void(0);">
                <h4>Work Order Item List</h4>
                </a> </div>
            </div>
            <div class="panel-body"> 
              <div class="table-responsive">
                <table id="dataTableExample1" class="table table-bordered table-striped table-hover">
                  <thead>
                    <tr class="info">
                      <th>Order No.</th>
                      <th>Item</th>
                      <th>Item Type</th>
                      <th>Customer Name</th>
                      <th>Pcs</th>
                      <th>Cut</th>
                      <th>Meter</th>
                      <th>Priority</th> 
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach($dataWI as $data) 
					{ 				
						//  echo "<pre>"; print_r($data);
						$Id = $data->work_order_id;
					?>
						<tr id="Mid{{ $Id }}">
							<td> {{ $Id }} </td>
							<td> {{ CommonController::getItemName($data->item_id) }} </td>
							<td> {{ CommonController::getItemTypeName($data->item_type_id) }} </td>
							<td> {{ CommonController::getIndividualName($data->customer_id) }}  </td>
							<td> {{ $data->pcs }}  </td>
							<td> {{ $data->cut }}  </td>
							<td> {{ $data->meter }}  </td>
							<td> {{ $data->order_item_priority }} </td> 
						</tr>
                    <?php } ?>                   
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
	@include('common.footer') 
</div>
@include('common.formfooterscript')
<script type="text/javascript" src="{{ asset('js/jquery.validate.js') }}"></script>

 

</body>
</html>
