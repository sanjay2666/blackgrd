<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width">
<title>Daily Sales Order Report</title>
<style>
    @page {
        margin: 120px 30px 60px 30px;
    }

    body {
        font-family: DejaVu Sans, sans-serif;
        font-size: 11px;
        color: #222;
    }

    .report-header {
        position: fixed;
        top: -100px;
        left: 0;
        right: 0;
        height: 90px;
        padding: 8px 0;
    }

    .company-title {
        font-size: 20px;
        font-weight: 700;
        margin-bottom: 2px;
    }

    .company-sub {
        font-size: 11px;
        color: #333;
        line-height: 1.1;
        margin-bottom: 6px;
    }

    .report-meta {
        margin-top: 6px;
        font-size: 12px;
    }

    table.main-table {
        width: 100%;
        border-collapse: collapse;
    }

    table.main-table thead th {
        border-bottom: 2px solid #000;
        padding: 6px 8px;
        font-weight: 700;
        background: #f0f4f8;
        text-align: left;
        font-size: 11px;
    }

    table.main-table tbody td {
        border-bottom: 1px solid #e6e6e6;
        padding: 6px 8px;
        vertical-align: middle;
        font-size: 11px;
    }

    .customer-heading {
        background: #eaf7ea;
        color: #1f7a1f;
        font-weight: 700;
        font-size: 13px;
        border-top: 3px solid #2d8f2d;
    }

    .customer-heading td {
        border-bottom: none;
    }

    .sub-total {
        font-weight: 700;
        background: #e7f0ff;
        color: #093b88;
        border-top: 3px solid #0b56b7;
    }

    .right {
        text-align: right;
    }

    .center {
        text-align: center;
    }

    .grand-total {
        font-weight: 900;
        font-size: 13px;
        background: #fff;
        border-top: 4px solid #000;
    }
</style>
</head>
<body>
    <div class="report-header">
        <div class="company-title">Ajy Tech India Private Limited</div>
        <div class="company-sub">
            Factory-Plot No. 6A 6B-7A 7B &nbsp; Block No. 167 Village - Jolva-394305<br>
            Office - 323-Shree Krishna Market-Nr Kinnari Cinema-Ring SURAT
        </div>
        <div class="report-meta">
            <strong>Report :</strong> Daily Sales Order &nbsp;&nbsp;
            <strong>Period :</strong> From Date : {{ $fromDate ?: '-' }} &nbsp; Upto : {{ $toDate ?: '-' }}
        </div>
    </div>

    <table class="main-table">
        <thead>
            <tr>
                <th style="width:5%; text-align:center;">ORDNO</th>
                <th style="width:8%; text-align:center;">S.O.</th>
                <th style="width:8%; text-align:center;">Date</th>
                <th style="width:8%; text-align:center;">Due Days</th>
                <th style="width:20%;">Quality</th>
                <th style="width:14%;">Color</th>
                <th style="width:18%;">Process</th>
                <th style="width:7%;" class="right">O. Mtr</th>
                <th style="width:7%;" class="right">D. Mtr</th>
                <th style="width:7%;" class="right">B. Mtr</th>
            </tr>
        </thead>
        <tbody>
            @php
                $groups = collect($dataSOI)->groupBy(function ($row) {
                    $customer = $row->saleOrder->customer ?? null;
                    if (!empty($customer)) {
                        return !empty($customer->id) ? 'id_'.$customer->id : 'name_'.trim(strtolower($customer->name ?? $customer->company_name));
                    }
                    return 'name_ajy(self)';
                });

                $grandMtr = 0;
                $grandDelivered = 0;
                $grandPending = 0;
                $custIndex = 0;
            @endphp

            @foreach ($groups as $items)
                @php
                    $first = $items->first();
                    $customer = $first->saleOrder->customer ?? null;
                    $custName = $customer->name ?? $customer->company_name ?? 'AJY(Self)';
                    $custIndex++;
                    $subMtr = $items->sum('meter');
                    $subDel = $items->sum('delivered_item_mtr');
                    $subPend = $items->sum(function ($row) {
                        $pending = (float) ($row->pending_item_mtr ?? 0);
                        if ($pending == 0 && (float) ($row->meter ?? 0) > 0) {
                            $pending = (float) $row->meter - (float) ($row->delivered_item_mtr ?? 0);
                        }
                        return max(0, $pending);
                    });
                    $grandMtr += $subMtr;
                    $grandDelivered += $subDel;
                    $grandPending += $subPend;
                @endphp

                <tr class="customer-heading">
                    <td colspan="10">{{ $custIndex }}. {{ strtoupper($custName) }}</td>
                </tr>

                @foreach ($items as $data)
                    @php
                        $saleOrder = $data->saleOrder;
                        $saleOrderDate = $saleOrder->sale_order_date ?? null;
                        $orderMeter = (float) ($data->meter ?? 0);
                        $deliveredMeter = (float) ($data->delivered_item_mtr ?? 0);
                        $balanceMeter = (float) ($data->pending_item_mtr ?? 0);
                        if ($balanceMeter == 0 && $orderMeter > 0) {
                            $balanceMeter = $orderMeter - $deliveredMeter;
                        }
                        $process = trim(($data->coating_type ?? '').' '.($data->extra_job ?? '').' '.($data->print_job ?? ''));
                    @endphp
                    <tr>
                        <td class="center">{{ $data->id }}</td>
                        <td class="center">{{ $saleOrder->sale_order_number ?? '' }}</td>
                        <td>{{ $saleOrderDate ? \Carbon\Carbon::parse($saleOrderDate)->format('d/m/Y') : '' }}</td>
                        <td>{!! $saleOrderDate ? daysFromNow($saleOrderDate) : '' !!}</td>
                        <td>{{ $data->item_name }}</td>
                        <td>{{ $data->dyeing_color }}</td>
                        <td>{{ $process }}</td>
                        <td class="right">{{ number_format($orderMeter, 2) }}</td>
                        <td class="right">{{ number_format($deliveredMeter, 2) }}</td>
                        <td class="right">{{ number_format(max(0, $balanceMeter), 2) }}</td>
                    </tr>
                @endforeach

                <tr class="sub-total">
                    <td colspan="7" class="right">Sub Total :</td>
                    <td class="right">{{ number_format($subMtr, 2) }}</td>
                    <td class="right">{{ number_format($subDel, 2) }}</td>
                    <td class="right">{{ number_format($subPend, 2) }}</td>
                </tr>
            @endforeach

            <tr style="height:12px;"><td colspan="10">&nbsp;</td></tr>
            <tr class="grand-total">
                <td colspan="7" class="right">Grand Total :</td>
                <td class="right">{{ number_format($grandMtr, 2) }}</td>
                <td class="right">{{ number_format($grandDelivered, 2) }}</td>
                <td class="right">{{ number_format($grandPending, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <script type="text/php">
        if (isset($pdf)) {
            $font = $fontMetrics->getFont("DejaVu Sans", "normal");
            $pdf->page_text($pdf->get_width() - 40, 20, "{PAGE_NUM}", $font, 8, array(0,0,0));
        }
    </script>
</body>
</html>
