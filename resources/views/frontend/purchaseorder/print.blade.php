<!doctype html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>Purchase Order #{{ $dataPur->id }}</title>
	<style>
		* { box-sizing: border-box; }
		body {
			font-family: Arial, Helvetica, sans-serif;
			font-size: 12px;
			line-height: 1.35;
			margin: 0;
			padding: 18px;
			color: #202124;
			background: #f4f6f8;
		}
		.sheet {
			max-width: 1120px;
			margin: 0 auto;
			padding: 18px;
			background: #fff;
			border: 1px solid #d7dde5;
		}
		.topbar {
			display: flex;
			align-items: center;
			gap: 18px;
			padding-bottom: 14px;
			border-bottom: 3px solid #1f6fb2;
		}
		.logo-box {
			width: 22%;
			min-height: 72px;
			display: flex;
			align-items: center;
		}
		.logo-box img {
			max-width: 160px;
			max-height: 70px;
			object-fit: contain;
		}
		.company-block {
			width: 50%;
			text-align: center;
		}
		.company-block h1 {
			margin: 0 0 4px;
			font-size: 22px;
			letter-spacing: 0;
			color: #145b92;
			text-transform: uppercase;
		}
		.company-line { color: #4b5563; margin: 2px 0; }
		.po-block {
			width: 28%;
			text-align: right;
		}
		.po-block h2 {
			margin: 0 0 8px;
			font-size: 19px;
			color: #c0392b;
			text-transform: uppercase;
		}
		.meta-line { margin: 3px 0; }
		.meta-line strong { color: #111827; }
		.section-grid {
			display: grid;
			grid-template-columns: 1fr 1fr;
			gap: 14px;
			margin-top: 16px;
		}
		.panel {
			border: 1px solid #d7dde5;
			background: #fbfcfe;
		}
		.panel-title {
			padding: 7px 9px;
			font-weight: 700;
			color: #fff;
			background: #1f6fb2;
		}
		.panel-title.seller { background: #2f855a; }
		.panel-body { padding: 9px; min-height: 34px; }
		.panel-body p { margin: 0 0 5px; }
		.name { font-weight: 700; color: #111827; }
		table {
			width: 100%;
			border-collapse: collapse;
			margin-top: 16px;
		}
		th, td {
			border: 1px solid #d7dde5;
			padding: 6px;
			vertical-align: top;
		}
		th {
			background: #eef4fb;
			color: #1f2937;
			font-weight: 700;
			text-align: left;
		}
		.items-table th,
		.items-table td { font-size: 11px; }
		.text-right { text-align: right; }
		.text-center { text-align: center; }
		.nowrap { white-space: nowrap; }
		.summary-wrap {
			display: flex;
			justify-content: space-between;
			gap: 18px;
			margin-top: 14px;
			align-items: flex-start;
		}
		.remark-box {
			flex: 1;
			border: 1px solid #d7dde5;
			padding: 9px;
			min-height: 80px;
			background: #fbfcfe;
		}
		.summary {
			width: 340px;
			margin-top: 0;
		}
		.summary th { width: 55%; }
		.summary .grand th,
		.summary .grand td {
			background: #145b92;
			color: #fff;
			font-size: 13px;
			font-weight: 700;
		}
		.footer-row {
			display: flex;
			justify-content: space-between;
			gap: 18px;
			margin-top: 34px;
		}
		.signature {
			width: 260px;
			text-align: center;
			padding-top: 38px;
			border-top: 1px solid #9aa4b2;
			font-weight: 700;
		}
		.no-print {
			margin: 14px auto 0;
			max-width: 1120px;
			text-align: right;
		}
		.btn {
			padding: 7px 14px;
			border: 0;
			background: #1f6fb2;
			color: #fff;
			cursor: pointer;
			border-radius: 3px;
		}
		@page { margin: 10mm; }
		@media print {
			body { padding: 0; background: #fff; }
			.sheet { max-width: none; border: 0; padding: 0; }
			.no-print { display: none; }
			.topbar { break-inside: avoid; }
			.panel, tr, .summary-wrap { break-inside: avoid; }
		}
	</style>
</head>
<body>
	@php
		$companyName = $dataCom->legal_name ?: ($dataCom->name ?? 'Company');
		$companyAddress = collect([
			$dataCom->address_1 ?? null,
			$dataCom->address_2 ?? null,
			$dataCom->city_name ?? null,
			$dataCom->state_name ?? null,
			$dataCom->pincode ?? null,
		])->filter()->implode(', ');
		$companyLogo = !empty($dataCom->logo) ? asset($dataCom->logo) : asset('assets/brand/loomexa-logo.png');
		$poDate = !empty($dataPur->purchased_on) ? date('d-m-Y', strtotime($dataPur->purchased_on)) : '-';
		$vendor = $dataPur->vendor;
		$vendorName = $vendor->company_name ?: ($vendor->name ?? '-');
	@endphp

	<div class="sheet">
		<div class="topbar">
			<div class="logo-box">
				<img src="{{ $companyLogo }}" alt="Company Logo">
			</div>
			<div class="company-block">
				<h1>{{ $companyName }}</h1>
				 
				@if ($companyAddress != '')
					<div class="company-line">{{ $companyAddress }}</div>
				@endif
				<div class="company-line">
					@if (!empty($dataCom->email)) Email: {{ $dataCom->email }} @endif
					@if (!empty($dataCom->phone)) | Phone: {{ $dataCom->phone }} @endif
					@if (!empty($dataCom->website)) | {{ $dataCom->website }} @endif
				</div>
				@if (!empty($dataCom->gstin))
					<div class="company-line"><strong>GSTIN:</strong> {{ $dataCom->gstin }}</div>
				@endif
			</div>
			<div class="po-block">
				<h2>{{ $documentSettings['document_title'] ?: 'Purchase Order' }}</h2>
				<div class="meta-line"><strong>PO No:</strong> {{ $dataPur->id }}</div>
				<div class="meta-line"><strong>Date:</strong> {{ $poDate }}</div>
				<div class="meta-line"><strong>Status:</strong> {{ $dataPur->status }}</div>
			</div>
		</div>

		<div class="section-grid">
			<div class="panel">
				<div class="panel-title seller">Seller / Vendor Details</div>
				<div class="panel-body">
					<p class="name">{{ $vendorName }}</p>
					@if (!empty($vendor->phone)) <p><strong>Phone:</strong> {{ $vendor->phone }}</p> @endif
					@if (!empty($vendor->email)) <p><strong>Email:</strong> {{ $vendor->email }}</p> @endif
					@if (!empty($vendor->gstin)) <p><strong>GSTIN:</strong> {{ $vendor->gstin }}</p> @endif
				</div>
			</div>
			<div class="panel">
				<div class="panel-title">Buyer Details</div>
				<div class="panel-body">
					<p class="name">{{ $companyName }}</p>
					@if ($companyAddress != '') <p>{{ $companyAddress }}</p> @endif
					@if (!empty($dataCom->email)) <p><strong>Email:</strong> {{ $dataCom->email }}</p> @endif
					@if (!empty($dataCom->phone)) <p><strong>Phone:</strong> {{ $dataCom->phone }}</p> @endif
				</div>
			</div>
		</div>

		<div class="section-grid">
			<div class="panel">
				<div class="panel-title">Billing Address</div>
				<div class="panel-body">{!! nl2br(e($dataPur->billing_address ?: '-')) !!}</div>
			</div>
			<div class="panel">
				<div class="panel-title">Shipping Address</div>
				<div class="panel-body">{!! nl2br(e($dataPur->shiping_address ?: '-')) !!}</div>
			</div>
		</div>

		<table class="items-table">
			<thead>
				<tr>
					<th class="text-center nowrap">S. No.</th>
					<th>Type</th>
					<th>Product</th>
					<th>Colour</th>
					<th class="nowrap">HSN/SAC</th>
					<th class="text-right nowrap">Qty</th>
					<th class="nowrap">UOM</th>
					<th class="text-right nowrap">Meter</th>
					<th class="text-right nowrap">Rate</th>
					<th class="text-right nowrap">Taxable</th>
					<th class="text-right nowrap">CGST</th>
					<th class="text-right nowrap">SGST</th>
					<th class="text-right nowrap">IGST</th>
					<th class="text-right nowrap">Net Total</th>
				</tr>
			</thead>
			<tbody>
				@forelse ($dataPI as $item)
					<tr>
						<td class="text-center">{{ $loop->iteration }}</td>
						<td>{{ $item->ItemType->item_type_name ?? '-' }}</td>
						<td>{{ $item->name ?: ($item->Item->item_name ?? '-') }}</td>
						<td>{{ $item->colour_name ?: '-' }}</td>
						<td>{{ $item->hsn ?: '-' }}</td>
						<td class="text-right">{{ number_format((float) $item->quantity, 2) }}</td>
						<td>{{ $item->unit ?: '-' }}</td>
						<td class="text-right">{{ number_format((float) $item->meter, 2) }}</td>
						<td class="text-right">{{ number_format((float) $item->mrp, 2) }}</td>
						<td class="text-right">{{ number_format((float) $item->saleprice_wot, 2) }}</td>
						<td class="text-right">{{ number_format((float) $item->cgstrs, 2) }}</td>
						<td class="text-right">{{ number_format((float) $item->sgstrs, 2) }}</td>
						<td class="text-right">{{ number_format((float) $item->igstrs, 2) }}</td>
						<td class="text-right">{{ number_format((float) $item->total_price, 2) }}</td>
					</tr>
				@empty
					<tr>
						<td colspan="14" class="text-center">No items found.</td>
					</tr>
				@endforelse
			</tbody>
		</table>

		<div class="summary-wrap">
			<div class="remark-box">
				<strong>Remark</strong><br>
				{!! nl2br(e($dataPur->order_remark ?: '-')) !!}
			</div>
			<table class="summary">
				<tr><th>Total</th><td class="text-right">{{ number_format((float) $dataPur->total, 2) }}</td></tr>
				<tr><th>CGST</th><td class="text-right">{{ number_format((float) $dataPur->cgstrs, 2) }}</td></tr>
				<tr><th>SGST</th><td class="text-right">{{ number_format((float) $dataPur->sgstrs, 2) }}</td></tr>
				<tr><th>IGST</th><td class="text-right">{{ number_format((float) $dataPur->igstrs, 2) }}</td></tr>
				<tr><th>Tax</th><td class="text-right">{{ number_format((float) $dataPur->taxrs, 2) }}</td></tr>
				<tr><th>Freight</th><td class="text-right">{{ number_format((float) $dataPur->frieght, 2) }}</td></tr>
				<tr class="grand"><th>Grand Total</th><td class="text-right">{{ number_format((float) $dataPur->subtotal, 2) }}</td></tr>
			</table>
		</div>

		<div class="footer-row">
			<div>
				<strong>Terms & Conditions</strong><br>
				{!! nl2br(e($documentSettings['terms_text'] ?: ($dataCom->terms_and_conditions ?: ''))) !!}
			</div>
			<div class="signature">{{ $documentSettings['signatory_label'] ?: 'Authorized Signatory' }}</div>
		</div>
	</div>

	<div class="no-print">
		<button onclick="window.print()" class="btn">Print</button>
	</div>
</body>
</html>
