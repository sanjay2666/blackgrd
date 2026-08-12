<?php
	use \App\Http\Controllers\CommonController;  	
	// echo "<pre>"; print_r($dataPR); exit;	
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
        <div class="col-sm-12">
		{!! CommonController::display_message('message') !!}
          <div class="panel panel-bd lobidrag">
            <div class="panel-heading">
              <div class="btn-group" id="buttonexport"><a href="javascript:void(0);"><h4>Packaging Item Requirement List</h4></a></div>
            </div>
            <div class="panel-body">
      
            <div class="row" style="margin-bottom:5px">
				<form action="{{ route('show-warehouse-packaging-item-requirement') }}" method="GET" role="search" autocomplete="off">
					@csrf
				 
					<div class="col-sm-2 col-xs-12">
					  <input type="text" class="form-control" name="item_search" id="item_search" value="{{ $itemSearch }}" placeholder="Search by Item Name.">
					</div>
					<div class="col-sm-2 col-xs-12">
					  <input type="text" class="form-control" name="ordNumSearch" id="ordNumSearch" value="{{ $ordNumSearch }}" placeholder="Search by Sale Order Number.">        
					</div> 
					<div class="col-sm-1 col-xs-12">
					  <input type="submit" name="sbtSearch" class="btn btn-success" value="Search">
					</div>
				</form>        
				<div class="col-sm-2 col-xs-12"> &nbsp;</div>
			</div>`

			<div class="table-responsive">
				<table id="dataTableExample1" class="table table-bordered table-striped table-hover">
					<thead>
						<tr class="info">
							<th>Request Id</th>
							<th>Sale Order Number</th>
							<th>Item</th>
							<th>Item Type</th>
							<th>Dyeing</th>
							<th>Coating</th>
							<th>Total Meter</th>
							<th>Date</th>
							<th>Status</th>
						</tr>
					</thead>

					<tbody>
						<?php
						foreach ($dataPR as $data)
						{
							
							  // echo "<pre>"; print_r($data->toArray()); exit;
							
							 
							$isJob = ($data->order_type == 'Job');

							$SaleOrdNumber = $isJob
								? ($data['JobWork']->job_work_number ?? '')
								: ($data['SaleOrder']->sale_order_number ?? '');

							$dyeing_color = $isJob
								? ($data['JobWorkItem']->dyeing_color ?? '')
								: ($data['SaleOrderItem']->dyeing_color ?? '');

							$coated_pvc = $isJob
								? ($data['JobWorkItem']->coated_pvc ?? '')
								: ($data['SaleOrderItem']->coated_pvc ?? '');

							$saleOrdItemId = $isJob
								? ($data->job_work_item_id ?? '')
								: ($data->sale_order_item_id ?? '');

							$SaleOrdId = $isJob
								? ($data->job_work_id ?? '')
								: ($data->sale_order_id ?? '');
  
							
							$ItemName            = isset($data['Item']->item_name) ? $data['Item']->item_name : '';
							$ItemType            = isset($data['ItemType']->item_type_name) ? $data['ItemType']->item_type_name : '';
							$totMtr              = isset($data->total_size_mtr) ? $data->total_size_mtr : '';
							$isProAccByWarehouse = isset($data->is_pro_acc_by_warehouse) ? $data->is_pro_acc_by_warehouse : '';
							$process_acc_deny_by = isset($data->acc_deny_by) ? $data->acc_deny_by : '';
							$workType     		 = isset($data->order_type) ? $data->order_type : '';
							$developmentType     = isset($data->development_type) ? $data->development_type : '';
							$createdDate         = isset($data->created) ? date('d-m-Y', strtotime($data->created)) : '';
							$processAccDenyBy    = CommonController::getEmpName($process_acc_deny_by);
						?>
							<tr id="<?php echo 'Mid' . htmlspecialchars($data->ppr_id ?? '', ENT_QUOTES, 'UTF-8'); ?>">
								<td>
									<?php echo htmlspecialchars($SaleOrdId, ENT_QUOTES, 'UTF-8'); ?>
									&nbsp;
									<?php echo htmlspecialchars($data->ppr_id ?? '', ENT_QUOTES, 'UTF-8'); ?>
								</td>

								<td><?php echo htmlspecialchars($SaleOrdNumber, ENT_QUOTES, 'UTF-8'); ?></td>
								<td><?php echo htmlspecialchars($ItemName, ENT_QUOTES, 'UTF-8'); ?></td>
								<td><?php echo htmlspecialchars($ItemType, ENT_QUOTES, 'UTF-8'); ?></td>
								<td><?php echo htmlspecialchars($dyeing_color, ENT_QUOTES, 'UTF-8'); ?></td>
								<td><?php echo htmlspecialchars($coated_pvc, ENT_QUOTES, 'UTF-8'); ?></td>
								<td><?php echo htmlspecialchars($totMtr, ENT_QUOTES, 'UTF-8'); ?></td>
								<td><?php echo htmlspecialchars($createdDate, ENT_QUOTES, 'UTF-8'); ?></td>
								<td>
									<?php if (empty($isProAccByWarehouse)) { ?> 

										<?php if ($workType == 'Job') { ?>
											<form method="post" action="<?php echo route('accept-packaging-job-item-requirement'); ?>" class="form-horizontal packaging-action-form pull-left" data-confirm-message="Are you sure you want to accept this item for packaging?" data-loading-text="Please wait...">
												<?php echo csrf_field(); ?>
												<input type="hidden" name="job_work_item_id" value="<?php echo htmlspecialchars($saleOrdItemId, ENT_QUOTES, 'UTF-8'); ?>">
												<button type="submit" class="btn btn-success btn-xs packaging-action-btn">Accept</button>
											</form>

											<form method="post" action="<?php echo route('deny-packaging-job-item-requirement'); ?>" class="form-horizontal packaging-action-form pull-left" data-confirm-message="Are you sure you want to deny this item for packaging?" data-loading-text="Please wait...">
												<?php echo csrf_field(); ?>
												<input type="hidden" name="job_work_item_id" value="<?php echo htmlspecialchars($saleOrdItemId, ENT_QUOTES, 'UTF-8'); ?>">
												<button type="submit" class="btn btn-danger btn-xs packaging-action-btn">Deny</button>
											</form>
										<?php } ?>

										<?php if ($workType == 'Home') { ?>

											<?php if ($developmentType == 'Bulk') { ?>
												<form method="post" action="<?php echo route('accept-packaging-item-requirement'); ?>" class="form-horizontal packaging-action-form pull-left" data-confirm-message="Are you sure you want to accept this item for packaging?" data-loading-text="Please wait...">
													<?php echo csrf_field(); ?>
													<input type="hidden" name="sale_order_item_id" value="<?php echo htmlspecialchars($saleOrdItemId, ENT_QUOTES, 'UTF-8'); ?>">
													<button type="submit" class="btn btn-success btn-xs packaging-action-btn">Accept</button>
												</form>

												<form method="post" action="<?php echo route('deny-packaging-item-requirement'); ?>" class="form-horizontal packaging-action-form pull-left" data-confirm-message="Are you sure you want to deny this item for packaging?" data-loading-text="Please wait...">
													<?php echo csrf_field(); ?>
													<input type="hidden" name="sale_order_item_id" value="<?php echo htmlspecialchars($saleOrdItemId, ENT_QUOTES, 'UTF-8'); ?>">
													<button type="submit" class="btn btn-danger btn-xs packaging-action-btn">Deny</button>
												</form>
											<?php } ?>

											<?php if ($developmentType == 'Sample') { ?>
												<form method="post" action="<?php echo route('accept-sample-packaging-requirement'); ?>" class="form-horizontal packaging-action-form pull-left" data-confirm-message="Are you sure you want to accept this sample item for packaging?" data-loading-text="Please wait...">
													<?php echo csrf_field(); ?>
													<input type="hidden" name="sale_order_item_id" value="<?php echo htmlspecialchars($saleOrdItemId, ENT_QUOTES, 'UTF-8'); ?>">
													<button type="submit" class="btn btn-success btn-xs packaging-action-btn">Sample Accept</button>
												</form>

												<form method="post" action="<?php echo route('deny-sample-packaging-requirement'); ?>" class="form-horizontal packaging-action-form pull-left" data-confirm-message="Are you sure you want to deny this sample item for packaging?" data-loading-text="Please wait...">
													<?php echo csrf_field(); ?>
													<input type="hidden" name="sale_order_item_id" value="<?php echo htmlspecialchars($saleOrdItemId, ENT_QUOTES, 'UTF-8'); ?>">
													<button type="submit" class="btn btn-danger btn-xs packaging-action-btn">Sample Deny</button>
												</form>
											<?php } ?>

										<?php } ?>

									<?php } else if ($isProAccByWarehouse == '1') { ?>

										<a href="javascript:void(0);" class="btn btn-success btn-xs">Accepted</a>
										<p>Accepted By <?php echo htmlspecialchars($processAccDenyBy, ENT_QUOTES, 'UTF-8'); ?></p>

									<?php } else if ($isProAccByWarehouse == '2') { ?>

										<a href="javascript:void(0);" class="btn btn-danger btn-xs">Denied</a>
										<p>Denied By <?php echo htmlspecialchars($processAccDenyBy, ENT_QUOTES, 'UTF-8'); ?></p>

									<?php } ?>
								</td>
							</tr>
						<?php } ?>

						<tr class="center text-center">
							<td class="center" colspan="9">
								<div class="pagination">
									<?php echo $dataPR->links('vendor.pagination.bootstrap-4'); ?>
								</div>
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
  <!-- /.content-wrapper -->
  
  
  
  
  
 @include('common.footer') </div>
@include('common.formfooterscript')
<script type="text/javascript" src="{{ asset('js/jquery.validate.js') }}"></script>

<script>
	$(document).on('submit', '.packaging-action-form', function(e) {
		var form = $(this);

		if (form.data('submitted') === true) {
			e.preventDefault();
			return false;
		}

		var confirmMessage = form.data('confirm-message') || 'Are you sure?';

		if (!confirm(confirmMessage)) {
			return false;
		}

		form.data('submitted', true);

		var currentButton = form.find('button[type="submit"]');
		var loadingText = form.data('loading-text') || 'Please wait...';

		form.closest('td').find('button[type="submit"]').prop('disabled', true);
		currentButton.text(loadingText);

		return true;
	});
</script>
 
</body>
</html>
