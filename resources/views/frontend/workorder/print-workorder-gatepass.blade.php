<?php
use \App\Http\Controllers\CommonController;

$unitTypeId        = $dataGp->unit_type_id;
$itemTypeId        = $dataGp->item_type_id;
$unitTypeName      = CommonController::getUnitTypeName($unitTypeId);
$itemTypeName      = CommonController::getItemTypeName($itemTypeId);
$generator         = new Picqer\Barcode\BarcodeGeneratorHTML();

$processType       = $data->process_type ?? '';
$processSlNo       = $data->process_sl_no ?? '';
$proTypeOrdNumber  = trim($processType . $processSlNo);
$gatepassNo        = !empty($dataGp->gatepass_number) ? $dataGp->gatepass_number : (1000 + (int) $GpId);
$barcodeValue      = $proTypeOrdNumber !== '' ? $proTypeOrdNumber : (string) $gatepassNo;

$dyeingColor       = $dataGp->dyeing_color ?? '';
$coatedPvc         = $dataGp->coated_pvc ?? $dataGp->coating_type ?? '';
$extraJob          = $dataGp->extra_job ?? '';
$printJob          = $dataGp->print_job ?? '';
$gatePassDate      = $dataGp->created ?? $dataGp->created_at ?? $dataGp->print_date ?? now();
$gpDate            = date('d-m-Y', strtotime($gatePassDate));
$saleOrderItemId   = $data->sale_order_item_id ?? data_get($data, 'WorkOrderItem.0.sale_order_item_id', '');
$companyName       = $compData->name ?? $compData->legal_name ?? $compData->trade_name ?? '';
$companyPhones     = array_values(array_unique(array_filter(array_map('trim', [
    (string) ($compData->phone ?? ''),
    (string) ($compData->alternate_phone ?? ''),
    (string) ($compData->mobile ?? ''),
    (string) ($compData->whatsapp_no ?? ''),
]))));
$companyPhoneText  = implode(' / ', $companyPhones);
$companyGstin      = trim((string) ($compData->gstin ?? ''));
$generatedBy       = !empty($dataGp->genrated_by) ? CommonController::getIndividualName($dataGp->genrated_by) : ($dataInd->name ?? '');

$readyItem = '';
if (!empty($dyeingColor)) {
    $readyItem = 'Dyeing Color : ' . $dyeingColor;
} elseif (!empty($coatedPvc)) {
    $readyItem = 'Coated : ' . $coatedPvc;
} elseif (!empty($extraJob)) {
    $readyItem = 'Extra Job : ' . $extraJob;
} elseif (!empty($printJob)) {
    $readyItem = 'Print Job : ' . $printJob;
}

$copies = [
    ['label' => 'Gatepass', 'copy' => 'Main Copy'],
    ['label' => 'P O D', 'copy' => 'Receiver Copy'],
    ['label' => 'P O D', 'copy' => 'Office Copy'],
];
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gatepass No:- {{ $gatepassNo }}</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 10px;
            color: #1f2933;
            background: #e9eef3;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            line-height: 1.22;
        }

        .print-actions {
            width: 100%;
            max-width: 980px;
            margin: 0 auto 12px;
            text-align: right;
        }

        .print-actions button {
            border: 0;
            border-radius: 4px;
            background: #198754;
            color: #fff;
            padding: 8px 15px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
        }

        .gatepass-sheet {
            width: 100%;
            max-width: 980px;
            margin: 0 auto;
            background: #fff;
            border: 1px solid #b8c2cc;
            box-shadow: 0 10px 28px rgba(31, 41, 51, 0.13);
        }

        .gatepass-slip {
            min-height: 92mm;
            padding: 7px 10px 6px;
            border-bottom: 1px dashed #8a98a8;
            page-break-inside: avoid;
        }

        .gatepass-slip:last-child {
            border-bottom: 0;
        }

        .gatepass-header {
            display: table;
            width: 100%;
            padding-bottom: 5px;
            border-bottom: 2px solid #2f4050;
        }

        .header-left,
        .header-center,
        .header-right {
            display: table-cell;
            vertical-align: top;
        }

        .header-left {
            width: 25%;
        }

        .header-center {
            width: 50%;
            text-align: center;
        }

        .header-right {
            width: 25%;
            text-align: right;
        }

        h1,
        h2,
        p {
            margin: 0;
        }

        h1 {
            color: #102a43;
            font-size: 18px;
            font-weight: 700;
            line-height: 1.15;
        }

        h2 {
            margin-top: 2px;
            color: #334e68;
            font-size: 13px;
            font-weight: 700;
        }

        .document-label {
            display: inline-block;
            margin-bottom: 4px;
            padding: 3px 7px;
            border: 1px solid #9fb3c8;
            border-radius: 3px;
            color: #243b53;
            background: #f5f8fb;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .gatepass-no {
            color: #102a43;
            font-size: 15px;
            font-weight: 700;
        }

        .muted {
            color: #66788a;
        }

        .company-meta {
            margin-top: 2px;
            color: #52616f;
            font-size: 10px;
            font-weight: 600;
        }

        .barcode {
            margin-top: 4px;
        }

        .barcode > div,
        .barcode svg {
            display: inline-block;
            max-width: 170px;
        }

        .detail-grid {
            width: 100%;
            margin-top: 6px;
            border-collapse: collapse;
        }

        .detail-grid th,
        .detail-grid td {
            border: 1px solid #d6dde5;
            padding: 4px 6px;
            vertical-align: top;
        }

        .detail-grid th {
            width: 16%;
            color: #334e68;
            background: #f3f6f9;
            font-weight: 700;
            text-align: left;
            white-space: nowrap;
        }

        .detail-grid td {
            color: #1f2933;
            font-weight: 600;
        }

        .quantity {
            font-size: 12px;
            font-weight: 700;
        }

        .route-box {
            display: table;
            width: 100%;
            margin-top: 6px;
            border: 1px solid #cbd5df;
            background: #f8fafc;
        }

        .route-item {
            display: table-cell;
            width: 50%;
            padding: 5px 8px;
        }

        .route-item + .route-item {
            border-left: 1px solid #cbd5df;
        }

        .route-label {
            display: block;
            color: #66788a;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .route-value {
            display: block;
            margin-top: 2px;
            color: #102a43;
            font-size: 12px;
            font-weight: 700;
        }

        .signature-row {
            display: table;
            width: 100%;
            margin-top: 14px;
        }

        .signature-box {
            display: table-cell;
            width: 50%;
            text-align: center;
        }

        .signature-line {
            display: inline-block;
            width: 78%;
            padding-top: 6px;
            border-top: 1px solid #25313d;
            color: #334e68;
            font-weight: 700;
        }

        @media print {
            @page {
                size: A4 portrait;
                margin: 6mm;
            }

            body {
                padding: 0;
                color: #000;
                background: #fff;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .print-actions {
                display: none;
            }

            .gatepass-sheet {
                max-width: none;
                border-color: #000;
                box-shadow: none;
            }

            .gatepass-slip {
                min-height: 91mm;
                padding: 6px 8px 5px;
                border-bottom-color: #555;
            }

            .gatepass-header {
                border-bottom-color: #000;
            }

            .detail-grid th,
            .detail-grid td,
            .route-box,
            .route-item + .route-item {
                border-color: #777;
            }
        }
    </style>
</head>
<body>
    <div class="print-actions">
        <button type="button" onclick="window.print()">Print Gatepass</button>
    </div>

    <main class="gatepass-sheet">
        @foreach ($copies as $copy)
            <section class="gatepass-slip">
                <header class="gatepass-header">
                    <div class="header-left">
                        <span class="document-label">{{ $copy['copy'] }}</span>
                        <p class="gatepass-no">No. {{ $gatepassNo }}</p>
                        <p class="muted">Date: {{ $gpDate }}</p>
                    </div>

                    <div class="header-center">
                        <h1>{{ $companyName }}</h1>
                        <h2>{{ $copy['label'] }}</h2>
                        @if ($companyPhoneText !== '')
                            <p class="company-meta">Phone: {{ $companyPhoneText }}</p>
                        @endif
                        @if ($companyGstin !== '')
                            <p class="company-meta">GSTIN: {{ $companyGstin }}</p>
                        @endif
                    </div>

                    <div class="header-right">
                        <p class="muted">Work Order</p>
                        <p class="gatepass-no">#{{ $proTypeOrdNumber }}</p>
                        <div class="barcode">{!! $generator->getBarcode($barcodeValue, $generator::TYPE_CODE_128) !!}</div>
                    </div>
                </header>

                <table class="detail-grid">
                    <tbody>
                        <tr>
                            <th>Work Order</th>
                            <td>#{{ $proTypeOrdNumber }}</td>
                            <th>Sale Order Item</th>
                            <td>#{{ $saleOrderItemId }}</td>
                        </tr>
                        <tr>
                            <th>Item</th>
                            <td colspan="3">{{ $data->item_name ?? '' }}</td>
                        </tr>
                        <tr>
                            <th>Quantity</th>
                            <td class="quantity">{{ $dataGp->qty_size }} {{ $unitTypeName }} {{ $itemTypeName }} ({{ $dataGp->qty }} Pcs)</td>
                            <th>Taka Number</th>
                            <td>{{ $dataGp->insp_taka_number }}</td>
                        </tr>
                        @if (!empty($readyItem))
                            <tr>
                                <th>Ready Item</th>
                                <td colspan="3">{{ $readyItem }}</td>
                            </tr>
                        @endif
                        <tr>
                            <th>Generated By</th>
                            <td>{{ $generatedBy }}</td>
                            <th>Generated Date</th>
                            <td>{{ $gpDate }}</td>
                        </tr>
                    </tbody>
                </table>

                <div class="route-box">
                    <div class="route-item">
                        <span class="route-label">From Department</span>
                        <span class="route-value">{{ $toDepart }}</span>
                    </div>
                    <div class="route-item">
                        <span class="route-label">To Department / Warehouse</span>
                        <span class="route-value">{{ $warehouseName }}</span>
                    </div>
                </div>

                <div class="signature-row">
                    <div class="signature-box">
                        <span class="signature-line">Sender Signature</span>
                    </div>
                    <div class="signature-box">
                        <span class="signature-line">Receiver Signature</span>
                    </div>
                </div>
            </section>
        @endforeach
    </main>
</body>
</html>
