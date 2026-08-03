<?php
use \App\Http\Controllers\CommonController;
$generator = new Picqer\Barcode\BarcodeGeneratorHTML(); 
 //  echo "<pre>"; print_r($data->toArray()); // exit;
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>BarCode</title>
<link href="{{ asset('css/gatepass.css') }}" rel="stylesheet" type="text/css"/>
<style>
    body {
        font-family: Arial, sans-serif;
        margin: 0;
        padding: 0;
        background-color: #f8f8f8;
    }

    .barcode-sheet {
        display: grid;
        grid-template-columns: repeat(3, 1fr); /* Three barcodes per row */
        gap: 10px;
        padding: 20px;
    }

    .barcode-label {
        background-color: #fff;
        border: 1px solid #ddd;
        border-radius: 5px;
        text-align: center;
        padding: 10px;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    }

    .barcode {
        margin-bottom: 5px;
    }

    .barcode-text {
        font-size: 12px;
        font-weight: bold;
        color: #333;
    }
</style>

</head>
<body>

    <!-- Loop through the data -->
   
	<div class="divLoop">
		<div class="barcode-sheet">
			@foreach ($data as $item)
				<div class="barcode-label">
					<!-- Generate Barcode -->
					<div class="barcode">
						{!! $generator->getBarcode($item->wis_id, $generator::TYPE_CODE_128) !!}
					</div>
					<!-- Display Text -->
					<div class="barcode-text">
						<small>
							{{ $item->item['item_name'] }} 
							{{ strtolower($item->dyeing_color ?? '') }} 
							{{ strtolower($item->coated_pvc ?? $item->coating_type ?? '') }} - 
							{{ number_format($item->insp_bal_quan_size,2) }} 
							{{ $item->quan_size_unit === 'Meter' ? 'M' : $item->quan_size_unit }}
						</small>
					</div>

				</div>
			@endforeach
		</div>

	</div>


    <span style="margin-left:50px;">
        <button class="hidden-print" onClick="window.print()">Print</button>
    </span>
    <div style="clear:both;"></div>
</body>
</html>

