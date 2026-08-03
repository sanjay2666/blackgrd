<?php
	$acceptedItems = $dataDpr->AcceptedDepartmentReturnRequest ?? collect();
	$totalMeters = 0;
	foreach ($acceptedItems as $acceptedItem) {
		$totalMeters += (float) $acceptedItem->item_qty;
	}

	$firstWarehouseItem = !empty($acceptedWarehouseItems) ? $acceptedWarehouseItems->first() : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>@include('frontend.common.head', ['pageTitle' => 'Accepted Department Return | Loomexa'])
</head>
<body class="hold-transition sidebar-mini accepted-department-return-page">
<div class="wrapper"> @include('frontend.common.header')
	<div class="content-wrapperd">
		<section class="content">
			<div class="row">
				<div class="col-sm-12">
					{!! display_message('message') !!}
					<div class="panel panel-bd lobidrag accepted-return-panel">
						<div class="panel-heading accepted-return-heading">
							<div>
								<h4>Accepted Department Return</h4>
								<span>Accepted return items received back into warehouse stock.</span>
							</div>
							<a class="btn btn-default btn-sm" href="{{ route('show-department-return-requests') }}">
								<i class="fa fa-arrow-left"></i> Back
							</a>
						</div>
						<div class="panel-body">
							<div class="accepted-return-hero">
								<div class="accepted-return-title-wrap">
									<div class="accepted-return-icon">
										<i class="fa fa-check"></i>
									</div>
									<div>
										<h3>Department Return Accepted</h3>
										<p>Request #{{ $dataDpr->id }} has been accepted and stock entries are available for review.</p>
									</div>
								</div>
								<span class="accepted-return-status">
									<i class="fa fa-check-circle"></i> Accepted
								</span>
							</div>

							<div class="accepted-return-summary">
								<div>
									<span>Request ID</span>
									<strong>#{{ $dataDpr->id }}</strong>
								</div>
								<div>
									<span>Work Order ID</span>
									<strong>{{ $dataDpr->work_order_id ?: '-' }}</strong>
								</div>
								<div>
									<span>Total Items</span>
									<strong>{{ count($acceptedItems) }}</strong>
								</div>
								<div>
									<span>Total Meter</span>
									<strong>{{ number_format($totalMeters, 2) }}</strong>
								</div>
								<div>
									<span>Received By</span>
									<strong>{{ $firstWarehouseItem->emp_name ?? '-' }}</strong>
								</div>
								<div>
									<span>Receive Date</span>
									<strong>{{ !empty($firstWarehouseItem->receive_date) ? date('d-m-Y', strtotime($firstWarehouseItem->receive_date)) : '-' }}</strong>
								</div>
							</div>

							<div class="accepted-return-table-wrap table-responsive">
								<table class="table table-bordered table-hover accepted-return-table">
									<thead>
										<tr>
											<th>Item Type</th>
											<th>Item Name</th>
											<th>Stock ID</th>
											<th>WPR ID</th>
											<th>Lot Number</th>
											<th>Return Date</th>
											<th>Taka Number</th>
											<th>Total Meter</th>
											<th>Warehouse</th>
											<th>Compartment</th>
										</tr>
									</thead>
									<tbody>
										@forelse($acceptedItems as $tblRow)
											@php
												$warehouseItem = !empty($acceptedWarehouseItems) ? $acceptedWarehouseItems->get($tblRow->id) : null;
												$itemTypeId = $warehouseItem->item_type_id ?? $tblRow->item_type_id ?? $dataDpr->item_type_id;
												$itemTypeName = $warehouseItem->ItemType->item_type_name ?? $dataIT->firstWhere('item_type_id', $itemTypeId)->item_type_name ?? '-';
											@endphp
											<tr>
												<td>{{ $itemTypeName }}</td>
												<td class="accepted-return-item">{{ $tblRow->Item->item_name ?? '-' }}</td>
												<td>{{ $tblRow->wis_id ?: '-' }}</td>
												<td>{{ $tblRow->work_pro_req_id ?: '-' }}</td>
												<td>{{ $tblRow->req_lot_number ?: '-' }}</td>
												<td>{{ !empty($tblRow->return_date) ? date('d-m-Y', strtotime($tblRow->return_date)) : '-' }}</td>
												<td>{{ $tblRow->insp_taka_number ?: '-' }}</td>
												<td>{{ number_format((float) $tblRow->item_qty, 2) }}</td>
												<td>{{ $warehouseItem->Warehouse->warehouse_name ?? '-' }}</td>
												<td>{{ $warehouseItem->WarehouseCompartment->compartment_name ?? '-' }}</td>
											</tr>
										@empty
											<tr>
												<td colspan="10" class="accepted-return-empty">
													<i class="fa fa-info-circle"></i>
													No accepted return items found.
												</td>
											</tr>
										@endforelse
									</tbody>
								</table>
							</div>
						</div>
					</div>
				</div>
			</div>
		</section>
	</div>
	@include('frontend.common.footer')
</div>
@include('frontend.common.footerscript')
</body>
</html>
