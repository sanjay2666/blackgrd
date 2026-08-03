<?php
use \App\Http\Controllers\CommonController;

$toDepart = CommonController::getProcessName($data->process_type_id);
$warehouseId = $data->WarehouseItem->warehouse_id ?? null;
$warehouseName = $warehouseId ? CommonController::getWarehouseName($warehouseId) : '';
$generator = new Picqer\Barcode\BarcodeGeneratorHTML();
$generatedDateValue = $dataWPR->acc_deny_date ?? $dataWPR->updated_at ?? $dataWPR->created_at ?? now();
$accDenDate = date('d-m-Y', strtotime($generatedDateValue));
$processTypeId = $dataWPR->process_type_id;
$lotno = !empty($dataWPR->req_lot_no) ? $dataWPR->req_lot_no : $dataWPR->id;
$gatepassNo = 10000 + (int) $data->work_order_id;
$firstRequirement = $dataWPR2->first();
$itemName = $firstRequirement->Item->item_name ?? '';
$itemCode = $firstRequirement->Item->item_code ?? '';
$quantityText = $dataWPR2->map(function ($row) {
    $qty = $row->alloted_quantity ?? $row->issued_quantity ?? 0;
    $unit = $row->UnitType->unit_type_name ?? '';
    $type = $row->ItemType->item_type_name ?? '';
    return trim($qty . ' ' . $unit . ' ' . $type);
})->filter()->implode(', ');
$totalMeter = collect($dataWOI)->sum('item_qty');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gatepass No:-{{ $gatepassNo }}</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 24px;
            color: #1f2933;
            background: #e9eef3;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 13px;
            line-height: 1.35;
        }

        .print-actions {
            max-width: 880px;
            margin: 0 auto 12px;
            text-align: right;
        }

        .print-actions button {
            border: 0;
            border-radius: 4px;
            background: #1f6f8b;
            color: #fff;
            padding: 8px 14px;
            font-size: 13px;
            cursor: pointer;
        }

        .gatepass {
            width: 100%;
            max-width: 880px;
            margin: 0 auto;
            background: #fff;
            border: 1px solid #bac7d5;
            box-shadow: 0 10px 30px rgba(31, 41, 51, 0.12);
        }

        .gatepass-header {
            display: table;
            width: 100%;
            padding: 18px 22px 14px;
            border-bottom: 2px solid #243b53;
        }

        .header-left,
        .header-right {
            display: table-cell;
            vertical-align: top;
            width: 30%;
        }

        .header-center {
            display: table-cell;
            width: 40%;
            text-align: center;
            vertical-align: top;
        }

        .document-label {
            display: inline-block;
            margin-bottom: 8px;
            padding: 4px 10px;
            border: 1px solid #9fb3c8;
            border-radius: 999px;
            color: #243b53;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .04em;
        }

        h1,
        h2,
        p {
            margin: 0;
        }

        h1 {
            color: #102a43;
            font-size: 23px;
            font-weight: 700;
        }

        h2 {
            margin-top: 4px;
            color: #334e68;
            font-size: 15px;
            font-weight: 600;
        }

        .muted {
            color: #627d98;
        }

        .gatepass-no {
            color: #102a43;
            font-size: 18px;
            font-weight: 700;
        }

        .barcode {
            margin-top: 8px;
            text-align: right;
        }

        .barcode > div,
        .barcode svg {
            display: inline-block;
            max-width: 220px;
        }

        .section {
            padding: 16px 22px;
            border-bottom: 1px solid #d9e2ec;
        }

        .section-title {
            margin-bottom: 8px;
            color: #102a43;
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .info-table,
        .items-table,
        .route-table {
            width: 100%;
            border-collapse: collapse;
        }

        .info-table th,
        .route-table th {
            width: 18%;
            background: #f0f4f8;
            color: #334e68;
            font-weight: 700;
            text-align: left;
        }

        .info-table td,
        .info-table th,
        .route-table td,
        .route-table th {
            border: 1px solid #d9e2ec;
            padding: 8px 10px;
            vertical-align: top;
        }

        .items-table th {
            background: #243b53;
            color: #fff;
            border: 1px solid #243b53;
            padding: 8px;
            text-align: center;
            font-weight: 700;
        }

        .items-table td {
            border: 1px solid #d9e2ec;
            padding: 7px 8px;
            text-align: center;
            vertical-align: middle;
        }

        .items-table tbody tr:nth-child(even) {
            background: #f8fafc;
        }

        .text-left {
            text-align: left !important;
        }

        .text-right {
            text-align: right !important;
        }

        .summary-row td {
            background: #f0f4f8;
            font-weight: 700;
        }

        .signature {
            display: table;
            width: 100%;
            padding: 34px 22px 20px;
        }

        .signature-box {
            display: table-cell;
            width: 50%;
            text-align: center;
        }

        .signature-line {
            display: inline-block;
            width: 82%;
            padding-top: 10px;
            border-top: 1px solid #102a43;
            color: #334e68;
            font-weight: 700;
        }

        @media print {
            @page {
                size: A4;
                margin: 12mm;
            }

            body {
                padding: 0;
                background: #fff;
                color: #000;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .print-actions {
                display: none;
            }

            .gatepass {
                max-width: none;
                border-color: #000;
                box-shadow: none;
            }

            .gatepass-header,
            .section,
            .signature {
                padding-left: 12px;
                padding-right: 12px;
            }

            .items-table th {
                background: #e5e7eb !important;
                color: #000 !important;
                border-color: #999;
            }

            .info-table td,
            .info-table th,
            .route-table td,
            .route-table th,
            .items-table td {
                border-color: #999;
            }
        }
    </style>
</head>

<body>
    <div class="print-actions">
        <button type="button" onclick="window.print()">Print Gatepass</button>
    </div>

    <main class="gatepass">
        <header class="gatepass-header">
            <div class="header-left">
                <span class="document-label">Warehouse Gatepass</span>
                <p class="gatepass-no">No. {{ $gatepassNo }}</p>
                <p class="muted">Lot: {{ $lotno }}</p>
            </div>

            <div class="header-center">
                <h1>{{ $compData->name }}</h1>
                <h2>Item Issue Gatepass</h2>
                <p class="muted">{{ $compData->phone }}/{{ $compData->another_phone }}</p>
            </div>

            <div class="header-right">
                <div class="barcode">{!! $generator->getBarcode($data->work_order_id, $generator::TYPE_CODE_128) !!}</div>
            </div>
        </header>

        <section class="section">
            <div class="section-title">Requirement Details</div>
            <table class="info-table">
                <tbody>
                    <tr>
                        <th>Work Order</th>
                        <td>{{ $data->work_order_id }}</td>
                        <th>Lot Number</th>
                        <td>{{ $lotno }}</td>
                    </tr>
                    <tr>
                        <th>Item</th>
                        <td>{{ $itemName }}</td>
                        <th>Design</th>
                        <td>{{ $itemCode }}</td>
                    </tr>
                    <tr>
                        <th>Quantity</th>
                        <td>{{ $quantityText }}</td>
                        <th>Generated Date</th>
                        <td>{{ $accDenDate }}</td>
                    </tr>
                </tbody>
            </table>
        </section>

        <section class="section">
            <div class="section-title">Issued Stock Details</div>
            <table class="items-table">
                <thead>
                    <tr>
                        <th>Sr. No.</th>
                        <th>G.T. Number</th>
                        <th>Meter</th>
                        <?php if($processTypeId == '4') { ?>
                            <th>Lot Number</th>
                            <th>D.T. Number</th>
                            <th>Color</th>
                        <?php } ?>
                        <th class="text-left">Supplier</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $i = 1; ?>
                    @foreach($dataWOI as $rowWOI)
                        <?php
                            $vendorId = $rowWOI->WarehouseItem->vendor_id ?? $rowWOI->WarehouseItem->individual_id ?? null;
                            $vendorName = $vendorId ? CommonController::getVendorName($vendorId) : '';
                        ?>
                        <tr>
                            <td>{{ $i++ }}</td>
                            <td>{{ $rowWOI->insp_taka_number }}</td>
                            <td class="text-right">{{ $rowWOI->item_qty }}</td>
                            <?php if($processTypeId == '4') { ?>
                                <td>{{ $rowWOI->dyeing_lot_number }}</td>
                                <td>{{ $rowWOI->dyeing_taka_number }}</td>
                                <td>{{ $rowWOI->dyeing_color }}</td>
                            <?php } ?>
                            <td class="text-left">{{ $vendorName }}</td>
                        </tr>
                    @endforeach
                    <tr class="summary-row">
                        <td colspan="{{ $processTypeId == '4' ? 6 : 3 }}" class="text-right">Total Meter</td>
                        <td class="text-right">{{ $totalMeter }}</td>
                    </tr>
                </tbody>
            </table>
        </section>

        <section class="section">
            <div class="section-title">Movement</div>
            <table class="route-table">
                <tbody>
                    <tr>
                        <th>From Department</th>
                        <td>{{ $warehouseName }}</td>
                        <th>To Department</th>
                        <td>{{ $toDepart }}</td>
                    </tr>
                </tbody>
            </table>
        </section>

        <section class="signature">
            <div class="signature-box">
                <span class="signature-line">Sender Signature</span>
            </div>
            <div class="signature-box">
                <span class="signature-line">Receiver Signature</span>
            </div>
        </section>
    </main>
</body>
</html>
