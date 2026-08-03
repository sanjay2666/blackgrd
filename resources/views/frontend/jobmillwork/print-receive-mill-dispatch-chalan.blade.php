<?php
	use \App\Http\Controllers\CommonController;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Mill Dispatch Challan</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 12mm;
        }

        body {
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            font-size: 14px;
            background: #eef3f7;
            color: #333;
            margin: 0;
        }

        .print-sheet {
            max-width: 1120px;
            margin: 24px auto;
            background: #fff;
            padding: 24px;
            border: 1px solid #dfe8ef;
            border-radius: 8px;
            box-shadow: 0 10px 30px rgba(20, 45, 65, 0.10);
        }

        .header, .footer {
            text-align: center;
            font-weight: bold;
            margin-bottom: 20px;
        }

        .title {
            font-size: 20px;
            font-weight: bold;
            text-transform: uppercase;
            color: #173247;
            margin: 5px 0;
        }

        .section-title {
            font-size: 16px;
            font-weight: bold;
            color: #383d41;
            margin-top: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            margin-bottom: 20px;
            border-radius: 6px;
            overflow: hidden;
            box-shadow: none;
        }

        th, td {
            padding: 8px 12px;
            border: 1px solid #dee2e6;
        }

        .no-border td {
            border: none;
        }

        .bold {
            font-weight: bold;
        }

        .right {
            text-align: right;
        }

        .left {
            text-align: left;
        }

        .remark-table td {
            border: none;
            padding: 6px 10px;
        }

        .meter-table2 th {
            background: #007bff;
            color: #fff;
            text-align: center;
        }

        .meter-table2 td {
            text-align: center;
        }

        .meter-table2 tr:nth-child(even) {
            background: #f1f1f1;
        }

        .footer {
			text-align: center;
			font-size: 13px;
			margin-top: 40px;
			color: #555;
			line-height: 1.8;
		}


        .footer span {
            display: inline-block;
            margin: 0 20px;
        }
    </style>
@media print {
    body {
        background: #fff !important;
        -webkit-print-color-adjust: exact !important; 
        print-color-adjust: exact !important;         
    }

    .print-sheet {
        max-width: none;
        margin: 0;
        padding: 0;
        border: 0;
        border-radius: 0;
        box-shadow: none;
    }

    table, th, td {
        border-color: #dee2e6 !important;
    }

    .meter-table2 th {
        background-color: #007bff !important;
        color: #fff !important;
        -webkit-print-color-adjust: exact !important;
    }

    .meter-table2 tr:nth-child(even) {
        background-color: #f1f1f1 !important;
    }

    .title {
        color: #004085 !important;
    }

    .section-title {
        color: #383d41 !important;
    }

    .footer {
        color: #555 !important;
    }
}
</head>
<body>

<div class="print-sheet">
<div class="header">
    GST No: 24AAJCA5287D1ZQ<br>
    <div class="title">AJY TECH INDIA PRIVATE LIMITED</div>
    Factory - Plot No. 6A 6B-7A 7B Block No. 167, Village - Jolva-394305, Taluka - Palsana, Dist. - Surat, Office - 32<br>
    <div class="section-title">Mill Dispatch Item Received Details</div>
</div>

<table class="no-border">
    <tr>
        <td colspan="2" class="bold"><?=$smData->vendor_name;?></td>
        <td class="right bold">CH.NO</td>
        <td class="bold"><?=$smData->chalan_no;?></td>
    </tr>
    <tr>
        <td colspan="2"><?=$smData->billing_address;?></td>
        <td colspan="1" class="right bold">Date</td>
        <td colspan="1" class="bold"><?=date('d-m-Y',strtotime($smData->chalan_date));?></td>
    </tr>
    <tr>
        <td class="bold">GST No</td>
        <td><?=$smData['Vendor']->gstin ?? '';?></td>
        <td class="right bold">Fabric Type</td>
        <td><?=$smData['ProcessType']->process_name ?? '';?></td>
    </tr>
    <tr>
        <td class="bold">Quality</td>
        <td><?=$smData['Item']->item_name ?? $smData->dispatch_item_name;?></td>
        <td class="right bold">Work Name</td>
        <td><?=$smData->work_name;?></td>
    </tr>
</table>

<table class="meter-table2">
    <tr class="info"> 
        <th>Sr No</th>
        <th>Item Name</th>
        <th>Dyeing Color</th>
        <th>Dispatch Meter</th>
        <th>Short %</th>
        <th>Received Meter</th>
        <th>Taka Number</th>
        <th>Remarks</th>
        <th>Date</th>
    </tr>

    <?php
        $totalMeter = 0;
		$totalRecMeter = 0;
        $srno = 0;

        foreach ($smData['StockMillDispatchItem'] as $index => $data) 
		{
            $srno = $index + 1;
            $dispatchMeter = $data['insp_quan_size'] ?? 0;
            $receivedTotal = $data['ReceiveStockMillDispatchItem']->sum('received_mtr');
            $shortPercent  = $dispatchMeter > 0 ? round((($dispatchMeter - $receivedTotal) / $dispatchMeter) * 100, 2) : 0;

            $totalMeter += $dispatchMeter;
			$totalRecMeter += $receivedTotal;
			
			
			
			$dyeingColors 	= $data->ReceiveStockMillDispatchItem
								->pluck('dyeing_color')
								->filter()
								->unique()
								->implode(', ');
			$remarks 		= $data->ReceiveStockMillDispatchItem
								->pluck('remarks')
								->filter()
								->unique()
								->implode(', ');

			$createdDates = $data->ReceiveStockMillDispatchItem
								->pluck('created_at')
								->filter()
								->map(fn($date) => date('d-m-Y', strtotime($date)))
								->unique()
								->implode(', ');


    ?>
	
    <tr>
        <td><?= $srno ?></td>
        <td><?=$smData['Item']->item_name ?? $smData->dispatch_item_name;?></td>
		
        <td><?= $dyeingColors;?></td>
        <td><?= number_format($dispatchMeter, 2) ?></td>
        <td><?= $shortPercent ?>%</td>
        <td><?= number_format($receivedTotal, 2) ?></td>
        <td><?= $data['insp_taka_number'] ?></td>
        <td><?= $remarks; ?></td>
        <td><?= $createdDates; ?></td>
    </tr>
    <?php } ?>
</table>


<table class="no-border">
    <tr>
        <td class="bold">Total Pcs</td>
        <td><?= $srno ?></td>
        <td class="bold">Total Meter</td>
        <td><?= number_format($totalMeter, 2) ?></td>
		<td class="bold">Total Received Meter</td>
        <td><?= number_format($totalRecMeter, 2) ?></td>
    </tr>
</table>

<table class="remark-table">
    <tr>
        <td class="bold left">Remark</td>
        <td class="right"> </td>
    </tr>
    <tr>
        <td><?= $smData->remark; ?></td>
        <td class="right"> </td>
    </tr>
     
</table>

 

</div>

</body>
</html>

