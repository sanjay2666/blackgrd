<?php

use \App\Http\Controllers\CommonController;
$toDepart        = CommonController::getProcessName($data->process_type_id);
$warehouseId     = $data['WarehouseItem']->warehouse_id;
$warehouseName   = CommonController::getWarehouseName($warehouseId);
$accDenDate      = date('d-m-Y', strtotime($dataWPR['0']->acc_deny_date));
$processTypeId   = $dataWPR['0']->process_type_id;
$lotno = !empty($dataWPR['0']->req_lot_no) ? $dataWPR['0']->req_lot_no : $dataWPR['0']->id;
$dyingColor = $data['WorkOrderItem']['0']->dyeing_color;
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Production Sheet - Print</title>

<!-- Bootstrap 3.3.7 -->
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">

<style>
  /* ===== base styles (kept from original) ===== */
  html, body { height: 100%; }
  body {
    font-family: Arial, Helvetica, sans-serif;
    color: #222;
    background: #fff;
    -webkit-print-color-adjust: exact;
    margin: 0;
    padding: 0;
  }

  .print-wrapper {
    max-width: 1040px;
    margin: 18px auto;
    padding: 18px;
    background: transparent; /* allow watermark to show through */
    position: relative;
    z-index: 1;
  }

  .company-name { font-size: 18px; font-weight: 700; letter-spacing: 0.6px; }
  .subtle { color: #666; font-size: 12px; }
  .meta-table th, .meta-table td { vertical-align: middle; padding: 8px 10px; font-size: 13px; }

  table { width: 100%; border-collapse: collapse; }
  .table { border: 1px solid #ddd; margin-bottom: 0; background: transparent; }
  .table th, .table td { border: 1px solid #e7e7e7; padding: 8px 10px; font-size: 13px; background: transparent; }
  .table thead th { background: rgba(247,247,247,0.9); font-weight: 600; } /* header slightly opaque */
  .table.table-striped>tbody>tr:nth-child(odd)>td { background-color: rgba(0,0,0,0.01); }

  .info-box { background: #f2f9ff; border: 1px solid #e1f0ff; padding: 10px; margin-bottom: 12px; border-radius: 3px; }
  .text-muted-small { color: #777; font-size: 12px; }

  .text-center { text-align: center; }
  .text-right { text-align: right; }
  .text-left { text-align: left; }

  thead { display: table-header-group; }
  tfoot { display: table-footer-group; }
  tr { page-break-inside: avoid; break-inside: avoid; }

  @media (max-width: 768px) {
    .print-wrapper { padding: 10px; }
    .company-name { font-size: 16px; }
    .meta-table th, .meta-table td { padding: 6px 8px; font-size: 12px; }
  }

  /* ===== SVG watermark overlay (single fixed element, repeated tiles via <pattern>) =====
     This overlay sits above all content so watermark will appear on top of tables.
     Opacity is low for readability; change --wm-opacity to adjust strength.
  */
  :root {
    --wm-opacity: 0.06;       /* change 0.03 - 0.12 to adjust darkness */
    --wm-font-size: 20;       /* text size inside each tile */
    --wm-rotate: -30;         /* rotation inside each tile */
    --wm-tile-w: 420px;       /* horizontal spacing of tiles */
    --wm-tile-h: 260px;       /* vertical spacing */
  }

  /* The fixed SVG container that'll hold a repeating pattern */
  .watermark-svg-overlay {
    position: fixed;
    inset: 0;                 /* top:0; right:0; bottom:0; left:0 */
    width: 100%;
    height: 100%;
    pointer-events: none;
    z-index: 99999;           /* ABOVE everything so watermark overlays tables */
    opacity: 1;               /* final tile opacity set inside SVG via fill-opacity */
    display: none;            /* hide on screen if desired; shown in print below */
  }

  /* Show overlay on screen too (optional) — comment out if you don't want screen overlay */
  .show-watermark-on-screen .watermark-svg-overlay { display: block; }

  @media print {
    /* Force-overlay visible in print */
    .watermark-svg-overlay { display: block !important; }

    /* make sure wrapper and tables allow overlay to be visible above them */
    .print-wrapper { background: transparent !important; box-shadow: none !important; }
    table, tr, td, th { background: transparent !important; -webkit-print-color-adjust: exact; }
    .no-print { display: none !important; }
  }

  /* small helper to ensure header row stays readable if needed */
  .table thead th { background: rgba(255,255,255,0.85) !important; -webkit-print-color-adjust: exact; }

  /* Print button safe style */
  .btn { cursor: pointer; user-select: none; }
  
  @media print {
    /* Prevent fixed watermark from contributing to page flow */
    .watermark-svg-overlay {
        position: fixed !important;
        top: 0 !important;
        left: 0 !important;
        right: 0 !important;
        bottom: 0 !important;
        height: 100vh !important;
        width: 100vw !important;
        overflow: hidden !important;
        pointer-events: none !important;
    }

    /* Most important: prevent overlay from expanding printable area */
    body, html {
        height: auto !important;
        overflow: visible !important;
    }

    /* Prevent forced extra blank page */
    @page {
        margin: 10mm;
        size: auto;
    }
}


</style>

<script>
  function printPage() { window.print(); }
</script>
</head>
<body class="show-watermark-on-screen">

<!-- ======= WATERMARK SVG OVERLAY (REPEATING PATTERN) =======
     This SVG uses a <pattern> to tile the watermark text across the entire viewport.
     It's fixed and has very high z-index so it sits ABOVE tables.
     You can edit the text inside <text> to change watermark text.
-->
<svg class="watermark-svg-overlay" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false" preserveAspectRatio="none">
  <defs>
    <!-- pattern tile -->
    <pattern id="wmPattern" width="420" height="260" patternUnits="userSpaceOnUse">
      <g>
        <text x="210" y="130" text-anchor="middle"
              transform="rotate(-30 210 130)"
              font-family="Arial, Helvetica, sans-serif"
              font-size="20"
              fill="#000"
              fill-opacity="0.38">
          AJY tech india pvt ltd
        </text>
      </g>
    </pattern>
    <!-- If you want an SVG text with different rotation/size per tile, adjust above -->
  </defs>

  <!-- large rect covering viewport filled with repeating pattern -->
  <rect x="0" y="0" width="100%" height="100%" fill="url(#wmPattern)" />
</svg>

<div class="print-wrapper">

  <!-- Header -->
  <div class="row" style="margin-bottom:12px;">
    <div class="col-xs-6">
      <strong class="company-name">AJY TECH INDIA PVT. LTD.</strong><br>
      <span class="subtle">Traceability / Production Sheet</span>
    </div>
    <div class="col-xs-6 text-right no-print">
      <button class="btn btn-primary" onclick="printPage()">Print</button>
      <button class="btn btn-default" onclick="window.close()">Close</button>
    </div>
  </div>

  <?php
  // Build vendor list
  $tt = 1;
  $vendorNames = [];
  foreach ($dataWPR2 as $rowWOI) {
      foreach ($rowWOI->WarehouseOutItem as $warehouseOutItem) {
          $tt++;
          $vendorId       = $warehouseOutItem['WarehouseItemStock']->vendor_id ?? null;
          $invoiceNumber  = $warehouseOutItem['WarehouseItemStock']->invoice_number ?? null;
          if (!empty($vendorId)) {
              $VendorName = CommonController::getIndividualName($vendorId);
          } elseif (empty($vendorId) && empty($invoiceNumber)) {
              $VendorName = "AJY";
          } else {
              $VendorName = $invoiceNumber;
          }
          if (!in_array($VendorName, $vendorNames)) {
              $vendorNames[] = $VendorName;
          }
      }
  }
  $totalTaka      = $tt - 1;
  $GetAllVendor   = implode(', ', $vendorNames);
  ?>

  <!-- SUMMARY PANEL -->
  <div class="panel panel-primary" style="margin-bottom:12px;">
    <div class="panel-heading">
      <h3 class="panel-title">Summary</h3>
    </div>
    <div class="panel-body" style="padding:10px;">
      <div class="row">
        <div class="col-xs-8">
      <table class="table table-bordered">
        <tbody>

          <tr>
            <th style="width:18%;">Date</th>
            <td style="width:28%;"><?php echo date('d-m-Y'); ?></td>
            <th>Quality</th>
            <td colspan="3">
              <?php echo htmlspecialchars($dataWPR2['0']['Item']->item_name ?? ''); ?>
            </td>
          </tr>

          <tr>
            <th>Color</th>
            <td><?php echo htmlspecialchars($dyingColor); ?></td>
            <th>Greige Mtr</th>
            <td colspan="3">
              <?php 
                echo htmlspecialchars($totalAllotedQuantity ?? '') . ' ' .
                     htmlspecialchars($dataWPR2['0']['UnitType']->unit_type_name ?? '') . ' ' .
                     htmlspecialchars($dataWPR2['0']['ItemType']->item_type_name ?? '');
              ?>
            </td>
          </tr>
          
          
          <?php 
            $weavingGsm = '';
            $dyeingGsm   = '';
            $coatingGsm = '';          
          ?>
          <tr>
            <th>Weaving GSM</th>
            <td><?php echo $weavingGsm !== null ? htmlspecialchars($weavingGsm) : str_repeat('&nbsp;', 5); ?></td>

            <th>Dyeing GSM</th>
            <td><?php echo $dyeingGsm !== null ? htmlspecialchars($dyeingGsm) : str_repeat('&nbsp;', 5); ?>  0.00</td>

            <th>Coating GSM</th>
            <td><?php echo $coatingGsm !== null ? htmlspecialchars($coatingGsm) : str_repeat('&nbsp;', 5); ?> 0.00</td>
          </tr>

        </tbody>
      </table>

        </div>

        <div class="col-xs-4">
          <div class="info-box text-center">
            <div style="font-size:22px; font-weight:700;"><?php echo htmlspecialchars($dataWPR2['0']['Item']->item_name ?? ''); ?></div>
            <div class="text-muted-small">Traceability / Production Sheet</div>
            <div style="margin-top:8px; font-size:14px;"><strong>Lot:</strong> <?php echo htmlspecialchars($lotno); ?></div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- INSPECTION LIST PANEL -->
  <?php
  $i = 1;
  $totalRows = 0;
  $totSum = 0;
  foreach($dataWPR2 as $rowWOI) {
      foreach($rowWOI->WarehouseOutItem as $warehouseOutItem) {
          $totSum += $warehouseOutItem->item_qty;
          $totalRows++;
      }
  }
  ?>
  <div class="panel panel-primary" style="margin-bottom:12px;">
    <div class="panel-heading">
      <h3 class="panel-title">Alloted Item Roll List</h3>
    </div>
    <div class="panel-body" style="padding:10px;">
      
    <?php
      // Flatten items
      $allItems = [];
      foreach ($dataWPR2 as $rowWOI) {
        foreach ($rowWOI->WarehouseOutItem as $warehouseOutItem) {
          $allItems[] = $warehouseOutItem;
        }
      }

      $totalData = count($allItems);
      $cols = 3; // number of columns
      $rows = (int) ceil($totalData / $cols);

      // grand total variable
      $grandTotal = 0;
    ?>
    <table class="table custom-table table-striped" style="margin-top:10px;">
      <tbody>
      <tr>
        <?php for ($c = 0; $c < $cols; $c++) { ?>
          <th>Sr.No.</th>
          <th>Taka</th>
          <th>Meters</th>
        <?php } ?>
      </tr>

      <?php
      for ($r = 0; $r < $rows; $r++) {
        echo "<tr>";
        for ($c = 0; $c < $cols; $c++) {
          $index = $r + ($c * $rows); // distribute vertically
          if ($index < $totalData) {
            $item = $allItems[$index];
            $srNo = $index + 1;
            $taka = htmlspecialchars($item->insp_taka_number ?? '');
            $metersVal = isset($item->item_qty) ? (float) $item->item_qty : 0;
            $grandTotal += $metersVal;
            $meters = number_format($metersVal, 2);

            echo "<td>{$srNo}</td>";
            echo "<td>{$taka}</td>";
            echo "<td>{$meters}</td>";
          } else {
            echo "<td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td>";
          }
        }
        echo "</tr>";
      }
      ?>

      <!-- Single Grand Total -->
      <tr class="total-row">
        <td colspan="<?php echo $cols * 3 - 1; ?>" class="text-right"><strong>Total Meters:</strong></td>
        <td><strong><?php echo number_format($grandTotal, 2); ?> MTR</strong></td>
      </tr>
      </tbody>
    </table>

  
  </div>
  </div>

  <!-- DETAILED BLOCK PANEL -->
  <div class="panel panel-primary" style="margin-bottom:12px;">
    <div class="panel-heading">
      <h3 class="panel-title">Detailed Inspection / Work Orders</h3>
    </div>
    <div class="panel-body" style="padding:10px;">
      <table class="table" style="margin-bottom:0;">
        <thead>
          <tr>
        <th>Department</th>
            <th>Lot No.</th>
            <th>Customer</th>
            <th>Gen. Date</th>
            <th>Item Name</th>
            <th>Meter</th>
            <th>Pcs</th> 
            <th>Insp. Meter</th>
            <th>Insp. Date</th>
          </tr>
        </thead>
        <tbody>
        <?php
        foreach ($dataPur as $rowArr) {
            $reqlotNum = $rowArr->req_lot_no;
            $workOrdId = $rowArr->work_order_id;
            $TotalInspSize = CommonController::calculateTotalInspectionSize($workOrdId,$reqlotNum);
            $InspDate = CommonController::getInspectionSummary($workOrdId,$reqlotNum);
            $warehouseOutItemsArray = $rowArr['WarehouseOutItem']->toArray();
            $itemQtys = array_column($warehouseOutItemsArray, 'item_qty');
            $totalQty = array_sum($itemQtys);
        ?>
          <tr>
			<td>
              <?php
              if ($rowArr['WorkOrder']->process_type == 'D') echo "Dyeing";
              else if ($rowArr['WorkOrder']->process_type == 'C') echo "Coating";
              else if ($rowArr['WorkOrder']->process_type == 'V') echo "Weaving";
              else if ($rowArr['WorkOrder']->process_type == 'W') echo "Warping";
              ?>
            </td>
            <td><?php echo htmlspecialchars($rowArr->req_lot_no); ?></td>
            <td>
				<?php
				$prevCustomerId = null;
				foreach ($rowArr['WorkOrder']['WorkOrderItem'] as $siArr) {
					if ($prevCustomerId !== $siArr->customer_id) {
						$name = CommonController::getIndividualName($siArr->customer_id);
						// Limit to 6 characters
						$shortName = mb_substr($name, 0, 6, "UTF-8");
						
						echo "<p>" . htmlspecialchars($shortName) . "</p>";
						$prevCustomerId = $siArr->customer_id;
					}
				}
				?>
			</td>
            <td><?php echo htmlspecialchars($rowArr->created); ?></td>
            <td><?php echo htmlspecialchars($rowArr['Item']->item_name); ?></td>
            <td><?php echo htmlspecialchars($rowArr->alloted_quantity); ?></td>
            <td><?php echo count($rowArr['WarehouseOutItem']); ?></td>
           
            <td><?php echo htmlspecialchars($TotalInspSize); ?></td>
            <td><?php echo htmlspecialchars(date("d-m-Y", strtotime($InspDate))); ?></td>
          </tr>
        <?php } ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- DISPATCH REPORT PANEL -->
  <div class="panel panel-primary" style="margin-bottom:12px;">
    <div class="panel-heading">
      <h3 class="panel-title">Dispatch report</h3>
    </div>
    <div class="panel-body" style="padding:10px;">
      <div class="table-responsive">
        <?php
        if (!isset($packOrd)) {
            $packOrd = [];
        }
        $rows = is_array($packOrd) ? $packOrd : (method_exists($packOrd, 'toArray') ? $packOrd->toArray() : (array) $packOrd);
        ?>
        <table class="table table-striped table-bordered table-hover" style="margin-bottom:0;">
          <thead>
            <tr>
			  <th style="width:4%;">#</th>
			  <th style="width:8%;">PackId</th>
			  <th style="width:10%;">Challan</th>
			  <th style="width:8%;">Lot</th>
			  <th style="width:18%;">Item Name</th>
			  <th style="width:12%;">Sale Order</th>
			  <th style="width:12%;">Customer</th>
			  <th style="width:10%;">Meter</th>
			  <th style="width:12%;">Date</th>
			  <th style="width:6%;">Status</th>
			</tr>
          </thead>
          <tbody>
          <?php $i = 1; ?>
          <?php if (empty($rows)) { ?>
            <tr>
              <td colspan="10" class="text-center">No records found</td>
            </tr>
          <?php } else { ?>
            <?php 
				foreach ($rows as $row) 
				{          
					$packOrder   = isset($row['packaging_order']) ? $row['packaging_order'] : []; 
					$item = isset($row['item']) ? $row['item'] : [];
					$sale = isset($row['sale_order']) ? $row['sale_order'] : [];
					$individual = isset($packOrder['individual']) ? $packOrder['individual'] : [];
					$packId 	= $packOrder['id'] ?? ($row['packaging_ord_id'] ?? '');
					$totmtr   	= isset($totals[$packId]) ? (float)$totals[$packId] : 0.0;
					$chalan 	= $packOrder['pack_chalan_number'] ?? '';
					$shipAddr 	= $packOrder['shiping_address'] ?? $packOrder['shipping_address'] ?? '';
            ?>
            <tr>
              <td><?php echo $i++; ?></td>
              <td><?php echo $packId; ?></td>
              <td><?php echo $chalan; ?></td>
              <td><?php echo htmlspecialchars($row['dyeing_lot_number'] ?? ''); ?></td>
              <td><?php echo htmlentities($item['item_name'] ?? ''); ?></td>
              <td><?php echo ($sale['sale_order_number'] ?? '') ? htmlentities($sale['sale_order_number']) : ''; ?></td>
              <td><?php echo htmlentities(mb_substr($individual['name'], 0, 6, "UTF-8") ?? ''); ?>     </td>
              <td><?php echo number_format($totmtr, 2);?></td>
              <td><?php echo date("d-m-Y", strtotime($packOrder['stock_alloted_date'])) ?? ''; ?></td>
              <td><span class="label label-success">Active</span></td>
            </tr>
            <?php } ?>
          <?php } ?>
          </tbody>
        </table>
		
      </div>
    </div>
  </div>
<p>This is a computer-generated production sheet; no signature is required.</p>
 

</div> <!-- /.print-wrapper -->

</body>
</html>
