<?php
use App\Http\Controllers\CommonController;

$itemId             = $data->item_id;
$itemTypeId         = $data->item_type_id;
$itemTypeName       = CommonController::getItemTypeName($itemTypeId);
$externalItemTypeId = $dataWI->item_type_id ?? $itemTypeId;
?>

<!DOCTYPE html>
<html lang="en">
<head>
	@include('frontend.common.head', ['pageTitle' => 'Start Requisition | Loomexa'])
</head>

<body class="hold-transition sidebar-mini requisition-page">
<div class="wrapper">

	@include('frontend.common.header')

	<div class="content-wrapperd">
		<section class="content">
			<div class="row">
				<div class="col-sm-12">

					{!! display_message('message') !!}

					<div class="panel panel-bd lobidrag">
						<div class="panel-heading warehouse-page-heading">
							<div>
								<h4><i class="fa fa-list-alt"></i> Start Requisition For Weaving Process</h4>
								<span>Allot available beam stock for this weaving work order.</span>
							</div>
						</div>

						<div class="panel-body">
							<form method="post" action="{{ route('add_work_requisition_for_weaving') }}" onsubmit="disableSubmitButton(this)">
								@csrf

								<input type="hidden" name="itemIdReq" value="{{ $itemId }}">
								<input type="hidden" name="work_order_id_req" value="{{ $workOrderId }}">
								<input type="hidden" name="ext_item_type_id" value="{{ $externalItemTypeId }}">

								<div class="requisition-summary">
									<div class="row">
										<div class="col-sm-4">
											<span class="requisition-summary-label">Work Order</span>
											<span class="requisition-summary-value">{{ $workOrderNumber }}</span>
										</div>
										<div class="col-sm-5">
											<span class="requisition-summary-label">Item Name</span>
											<span class="requisition-summary-value">{{ $workOrderItemName }}</span>
										</div>
										<div class="col-sm-3">
											<span class="requisition-summary-label">Item Type</span>
											<span class="requisition-summary-value">{{ $itemTypeName }}</span>
										</div>
									</div>
								</div>

								<div class="wh-section-title">
									<span class="glyphicon glyphicon-th-large"></span> Available Beam Stock List
								</div>
								<div class="panel panel-info requisition-stock-panel">
									<div class="panel-body">
										<div class="table-responsive">
											<table class="table table-bordered table-striped table-condensed">
												<thead>
													<tr class="info">
														<th>ID</th>
														<th>Name</th>
														<th>Size</th>
														<th>Meter Size</th>
														<th>Beam No.</th>
														<th>Invoice</th>
														<th>Type</th>
														<th>Select</th>
													</tr>
												</thead>

												<tbody>
													@forelse($resultArray as $result)
													
													<?php 
													// echo "<pre>"; print_r($result);  
													$beamMeterSize = $result->beam_meter ?? $result->insp_bal_quan_size;
													?>
														<tr>
															<td class="vcenter">{{ $result->id }}</td>
															<td class="vcenter">{{ $workOrderItemName }}</td>
															<td class="vcenter">{{ $result->insp_bal_quan_size }} Kg</td>
															<td class="vcenter">
																<input type="number" name="work_size_meter[]" class="form-control row-quantity" step="0.01" min="0.01" max="{{ $beamMeterSize }}" disabled required>
															</td>
															<td class="vcenter">{{ $result->insp_taka_number }}</td>
															<td class="vcenter">{{ $result->invoice_number }}</td>
															<td class="vcenter">{{ $itemTypeName }}</td>
															<td class="text-center vcenter">
																<input type="checkbox" name="wis_id[]" class="stock-checkbox beam-stock-checkbox" value="{{ $result->id }}" data-auto-fill-value="{{ $beamMeterSize }}">
															</td>
														</tr>
													@empty
														<tr>
															<td colspan="8" class="text-center text-danger">No available beam stock found.</td>
														</tr>
													@endforelse
												</tbody>
											</table>
										</div>
									</div>
								</div>

								<div class="wh-section-title">
									<span class="glyphicon glyphicon-list-alt"></span> Available Yarn Stock List
								</div>
								<div class="panel panel-warning requisition-stock-panel">
									<div class="panel-body">
										<div class="table-responsive">
											<table id="stockTable" class="table table-bordered table-striped table-hover table-condensed">
												<thead>
													<tr class="info">
														<th>ID</th>
														<th>Item Name</th>
														<th>Invoice</th>
														<th>Taka Number</th>
														<th>Available</th>
														<th>Quantity</th>
														<th class="text-center">Select</th>
													</tr>
												</thead>

												<tbody>
													@forelse($dataWIS as $result)
													<?php 
													 // echo "<pre>"; print_r($result);   exit;
													?>
														<tr>
															<td class="vcenter">{{ $result->id }}</td>
															<td class="vcenter">{{ $result->Item->item_name ?? '' }}</td>
															<td class="vcenter">{{ $result->invoice_number }}</td>
															<td class="vcenter">{{ $result->insp_taka_number }}</td>
															<td class="vcenter">{{ $result->insp_bal_quan_size }} {{ $result->quan_size_unit }}</td>
															<td class="vcenter">
																<input type="number" name="quantity[]" class="form-control row-quantity" step="0.01" min="0.01" max="{{ $result->insp_bal_quan_size }}" disabled required>
															</td>
															<td class="text-center vcenter">
																<input type="checkbox" name="req_item_id[]" class="stock-checkbox" value="{{ $result->id }}" data-auto-fill-value="{{ $result->insp_bal_quan_size }}">
															</td>
														</tr>
													@empty
														<tr>
															<td colspan="7" class="text-center text-danger">No available yarn stock found.</td>
														</tr>
													@endforelse
												</tbody>
											</table>
										</div>
									</div>
								</div>

								<div class="requisition-actions">
									<div class="text-right">
										<button type="submit" id="sendRequisitionButton" class="btn btn-success" disabled><i class="glyphicon glyphicon-send"></i> Send Requisition</button>
									</div>
								</div>
							</form>
						</div>
					</div>

				</div>
			</div>
		</section>
	</div>

	@include('frontend.common.footer')

</div>

@include('frontend.common.footerscript')

<script type="text/javascript">
function disableSubmitButton(form) {
	var submitButton = form.querySelector('button[type="submit"]');

	if (submitButton) {
		submitButton.disabled = true;
		submitButton.innerHTML = '<i class="glyphicon glyphicon-refresh"></i> Submitting...';
	}
}

document.addEventListener('DOMContentLoaded', function () {
	document.querySelectorAll('.stock-checkbox').forEach(function (checkbox) {
		toggleRowQuantity(checkbox);

		checkbox.addEventListener('change', function () {
			if (this.checked && this.classList.contains('beam-stock-checkbox')) {
				document.querySelectorAll('.beam-stock-checkbox').forEach(function (beamCheckbox) {
					if (beamCheckbox !== checkbox) {
						beamCheckbox.checked = false;
						toggleRowQuantity(beamCheckbox);
					}
				});
			}

			toggleRowQuantity(this);
			updateSubmitButtonState();
		});
	});

	document.querySelectorAll('.row-quantity').forEach(function (input) {
		input.addEventListener('input', updateSubmitButtonState);
	});

	function toggleRowQuantity(checkbox) {
		var row = checkbox.closest('tr');
		var quantityInput = row ? row.querySelector('.row-quantity') : null;

		if (!quantityInput) {
			return;
		}

		quantityInput.disabled = !checkbox.checked;

		if (checkbox.checked) {
			var autoFillValue = checkbox.getAttribute('data-auto-fill-value');

			if (autoFillValue !== null && autoFillValue !== '') {
				quantityInput.max = autoFillValue;

				if (quantityInput.value === '') {
					quantityInput.value = autoFillValue;
				}
			}
		}

		if (!checkbox.checked) {
			quantityInput.value = '';
		}
	}

	function updateSubmitButtonState() {
		var submitButton = document.getElementById('sendRequisitionButton');
		var hasValidStock = false;

		document.querySelectorAll('.stock-checkbox:checked').forEach(function (checkbox) {
			var row = checkbox.closest('tr');
			var quantityInput = row ? row.querySelector('.row-quantity') : null;
			var quantity = quantityInput ? parseFloat(quantityInput.value) : 0;

			if (quantity > 0) {
				hasValidStock = true;
			}
		});

		if (submitButton) {
			submitButton.disabled = !hasValidStock;
		}
	}

	updateSubmitButtonState();
});
</script>

</body>
</html>
