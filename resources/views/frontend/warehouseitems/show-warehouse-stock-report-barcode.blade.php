<?php
use \App\Http\Controllers\CommonController;

$generator = new Picqer\Barcode\BarcodeGeneratorHTML();
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>BarCode</title>
<link href="{{ asset('frontend-assets/dist/css/loomexa_style.css') }}" rel="stylesheet" type="text/css"/>
</head>
<body class="barcode-print-page">
    <div class="hidden-print">
        <button onClick="window.print()">Print</button>
    </div>

    <div class="divLoop">
        <div class="barcode-sheet">
            @forelse ($dataWI as $item)
                @php
                    $barcodeValue = (string) ($item->id ?? '');
                    $itemName = $item->Item->item_name ?? '';
                    $dyeingColor = strtolower($item->dyeing_color ?? '');
                    $coatingType = strtolower($item->coating_type ?? '');
                    $balanceQty = $item->insp_bal_quan_size ?? $item->insp_quan_size ?? 0;
                    $unitName = $item->UnitType->unit_type_name ?? $item->quan_size_unit ?? '';
                    $unitLabel = $unitName === 'Meter' ? 'M' : $unitName;
                    $labelText = trim($itemName.' '.$dyeingColor.' '.$coatingType);
                @endphp

                <div class="barcode-label">
                    <div class="barcode">
                        {!! $generator->getBarcode($barcodeValue, $generator::TYPE_CODE_128, 2.4, 18) !!}
                    </div>
                    <div class="barcode-text">
                        {{ $labelText }} - {{ number_format((float) $balanceQty, 2) }} {{ $unitLabel }}
                    </div>
                </div>
            @empty
                <div class="barcode-label">
                    <div class="barcode-text">No record found.</div>
                </div>
            @endforelse
        </div>
    </div>

    <div style="clear:both;"></div>
</body>
</html>
