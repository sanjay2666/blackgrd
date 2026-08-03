<?php
use \App\Http\Controllers\CommonController;
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Work Order Items List</title>

<style>
@page {
    size: A4 portrait;
    margin: 5mm;
}
body{
    font-family: DejaVu Sans, sans-serif;
    font-size: 9px;
    line-height: 1.2;
    color: #000;
    margin: 0;
}

h2{
    text-align: center;
    margin: 0 0 6px 0;
    font-size: 13px;
    font-weight: bold;
}

table{
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
    font-size: 8px;
    page-break-inside: auto;
}

th, td{
    border: 1px solid #000;
    padding: 2px 4px;
    vertical-align: top;
    line-height: 1.15;
    overflow-wrap: break-word;
    word-break: break-word;
    white-space: normal;
    font-size: 8px;
}

th{
    background: #eeeeee;
    font-weight: bold;
    text-align: center;
}

.small{
    font-size: 8px;
    color: #000;
}

.total-row td{
    font-weight: bold;
    background: #f2f2f2;
}

tr{
    page-break-inside: avoid;
}

col.c1  { width: 9%; }
col.c2  { width: 11%; }
col.c3  { width: 10%; }
col.c4  { width: 10%; }
col.c5  { width: 12%; }
col.c6  { width: 18%; }
col.c7  { width: 8%; }
col.c8  { width: 7%; }
col.c9  { width: 7%; }
col.c10 { width: 8%; }
</style>


</head>

<body>

<h2>Work Order Items List</h2>

<?php
$totMtr 	= 0;
$totInspMtr = 0;
$totReqMtr 	= 0;
foreach ($dataWI as $d) 
{ 
	$meter_local 			= collect($d->WorkOrderItem)->sum('meter');
	$processTypeId 			= $d->process_type_id;
	$inspBeamMeter_local 	= 0;
	if (!empty($d->WorkInspection)) 
	{
		foreach ($d->WorkInspection as $insp) 
		{
			if($processTypeId < 3 )
			{
				$inspBeamMeter_local += (float)($insp->insp_beam_meter ?? 0);
			} else {
				$inspBeamMeter_local += (float)($insp->insp_quan_size ?? 0);
			} 
		}
	}

	$totMtr 	+= $meter_local;
	$totInspMtr += $inspBeamMeter_local;
	$totReqMtr 	+= ($meter_local - $inspBeamMeter_local);
}
?>

<table>
	<colgroup>
		<col class="c1">
		<col class="c2">
		<col class="c3">
		<col class="c4">
		<col class="c5">
		<col class="c6">
		<col class="c7">
		<col class="c8">
		<col class="c9"> 
		<col class="c10"> 
	</colgroup>

<thead>
<tr>
	<th>W.O No</th>
	<th>S.O / Job</th>
	<th>Item</th>
	<th>Internal</th> 
	<th>Customer</th>
	<th>Process</th>
	<th>Meter</th>
	
	<th>Insp Mtr</th>
	<th>Insp Kg</th>
	<th>Required</th>
	 
</tr>
</thead>

<tbody>
<?php foreach ($dataWI as $data) 
{ 
	$WOItem = $data->WorkOrderItem ?? []; 
	$meter = collect($data->WorkOrderItem)->sum('meter'); 

	$inspQuan = 0;
	$inspBeamMeter = 0;
	if (!empty($data->WorkInspection)) 
	{
		foreach ($data->WorkInspection as $insp) {
			$inspQuan += (float)($insp->insp_quan_size ?? 0);
			$inspBeamMeter += (float)($insp->insp_beam_meter ?? 0);
		}
	}
	
	 
	
	if($processTypeId > 2 )
	{
		$required = $meter - $inspQuan;
	} else {
		$required = $meter - $inspBeamMeter;
	}
?>

<tr>
	<td>
		<?=$data->process_type.$data->process_sl_no.' '.$data->work_order_id; ?><br>
		<span class="small"><?=daysFromNow($data->created); ?></span>
	</td>

	<td>
		<?=(!empty($WOItem[0]->SaleOrder->sale_order_date) ? \Carbon\Carbon::parse($WOItem[0]->SaleOrder->sale_order_date)->format('d-m-Y') : ''); ?><br>
		<?php foreach ($WOItem as $r) {
			$so = CommonController::getSaleOrd($r->sale_order_id);
			echo '<div class="small">'.($so->sale_order_number ?? '').'</div>';
		} ?>
	</td>

	<td><?=$data->item_name; ?> </td>
	<td><?=$data->Item->internal_item_name ?? ''; ?></td>
	 

	<td> 
		<?php foreach ($WOItem as $v) {
			echo '<div class="small">'.substr(CommonController::getEmpName($v->customer_id), 0, 10).'</div>';
		} ?>
	</td>

	<td>
		<strong><?=$data->ProcessType->process_name ?? ''; ?></strong>
		<?php
			// show dyeing/print/extras stacked, each on new line
			foreach ($WOItem as $ii) {
				$parts = [];
				if (!empty($ii->dyeing_color)) { $parts[] = $ii->dyeing_color; }
				if (!empty($ii->coated_pvc ?? $ii->coating_type ?? ''))   { $parts[] = $ii->coated_pvc ?? $ii->coating_type; }
				if (!empty($ii->extra_job))    { $parts[] = 'Extra - '.$ii->extra_job; }
				if (!empty($ii->print_job))    { $parts[] = 'Print - '.$ii->print_job; } 
				$remarksRow = data_get($ii, 'SaleOrderItem.remarks', '');
				if (!empty($remarksRow)) {
					$parts[] = '<small class="text-primary"><strong class="text-danger">Remarks - </strong>' . htmlspecialchars($remarksRow) . '</small>';
				}

				if (!empty($parts)) {
					foreach ($parts as $p) {
						echo '<div class="small">'.$p.'</div>';
					}
				}
			}
		?>
	</td>

	<td class="text-right"><?=number_format($meter); ?></td>
	<td class="text-right"><?=number_format(($inspQuan < 0) ? 0 : $inspQuan); ?></td>
	<td class="text-right"><?=number_format(($inspBeamMeter < 0) ? 0 : $inspBeamMeter); ?></td>
	<td class="text-right"><?=number_format(($required < 0) ? 0 : $required); ?></td>
	
</tr>

<?php } ?>

<tr class="total-row">
	<td colspan="6" class="text-right">TOTAL</td>
	<td class="text-right"><?=number_format($totMtr); ?></td>
	 
	<td class="text-right"><?=number_format($totInspMtr); ?></td>
	<td class="text-right"><?=number_format($totInspMtr); ?></td>
	<td class="text-right"><?=number_format($totReqMtr); ?></td>
</tr>

</tbody>
</table>

</body>
</html>

