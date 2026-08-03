<div class="stock-inline-box">
	<div class="stock-inline-head">
		<div>
			<strong>Related Stock Details</strong>
			<span class="stock-inline-count">{{ $dataWI->count() }} rows</span>
		</div>
		<a href="{{ $fullDetailsUrl }}" target="_blank" class="btn btn-info btn-xs stock-inline-action"><i class="fa fa-external-link"></i> View Full Page</a>
	</div>

	@if($dataWI->count() > 0)
		<div class="table-responsive">
			<table class="table table-bordered table-condensed stock-inline-table">
				<thead>
					<tr class="active">
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
					</tr>
				</thead>
				<tbody>
					@foreach($dataWI as $data)
						@php
							$unitType = ($data->unit_type_id == '2') ? 'Meter' : 'Kg';
							$totalQuantity = (float) ($data->insp_quan_size ?? 0);
							$allottedQuantity = (float) ($data->insp_allot_quan_size ?? 0);
							$availableQuantity = $totalQuantity - $allottedQuantity;
							$invoiceNumber = $data->WarehouseItem->invoice_number ?? $data->invoice_number ?? '';
							$vendorName = $data->WarehouseItem->Vendor->name ?? 'N/A';
							$itemName = $data->Item->item_name ?? '';
							$warehouseName = $data->WarehouseItem->Warehouse->warehouse_name ?? $data->Warehouse->warehouse_name ?? 'N/A';
							$compartmentName = $data->WarehouseCompartment->compartment_name ?? $data->WarehouseItem->WarehouseCompartment->compartment_name ?? 'N/A';
							$receiverName = $data->ReceiverIndividual->name ?? 'N/A';
							$receiveDate = !empty($data->receive_date) ? \Carbon\Carbon::parse($data->receive_date)->format('d-m-Y') : '';
							$itemTypeName = $data->ItemType->item_type_name ?? 'N/A';
						@endphp
						<tr>
							<td><strong>{{ $invoiceNumber }}</strong> @if(!empty($data->job_work_number)) <span class="muted-id">{{ $data->job_work_number }}</span> @endif</td>
							<td>{{ $vendorName }}</td>
							<td>{{ $itemName }}</td>
							<td>{{ $data->insp_taka_number }}</td>
							<td>{{ $data->dyeing_lot_number }}</td>
							<td>{{ $warehouseName }}</td>
							<td>{{ $compartmentName }}</td>
							<td>{{ $receiverName }}</td>
							<td>{{ $receiveDate }}</td>
							<td>{{ $itemTypeName }}</td>
							<td>{{ $data->dyeing_color }}</td>
							<td>{{ $data->coating_type }}</td>
							<td><span class="stock-inline-pill available">{{ round($availableQuantity, 2) }}</span> {{ $unitType }}</td>
							<td><span class="stock-inline-pill alloted">{{ round($allottedQuantity, 2) }}</span> {{ $unitType }}</td>
						</tr>
					@endforeach
				</tbody>
			</table>
		</div>
	@else
		<div class="alert alert-info stock-inline-empty"><i class="fa fa-info-circle"></i> No related stock details found.</div>
	@endif
</div>
