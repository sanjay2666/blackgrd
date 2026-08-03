<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Sale Order Print - {{ $saleOrder->sale_order_number ?? '' }}</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}?v=50">
    <link href="{{ asset('frontend-assets/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet" type="text/css">
    <link href="{{ asset('frontend-assets/font-awesome/css/font-awesome.min.css') }}" rel="stylesheet" type="text/css">

    <style>
        body { background:#f4f6f9; font-family:Arial, Helvetica, sans-serif; font-size:13px; color:#222; }
        .print-wrap { width:1000px; max-width:100%; margin:20px auto; background:#fff; border:1px solid #ddd; padding:20px; }
        .print-header { border-bottom:2px solid #2f4050; padding-bottom:12px; margin-bottom:15px; }
        .company-title { font-size:26px; font-weight:bold; color:#2f4050; margin:0; }
        .print-title { font-size:20px; font-weight:bold; color:#d9534f; margin:5px 0 0; }
        .info-box { border:1px solid #ddd; padding:10px; min-height:105px; margin-bottom:12px; }
        .info-box h4 { margin:0 0 8px; font-size:15px; font-weight:bold; color:#2f4050; border-bottom:1px solid #eee; padding-bottom:6px; }
        .info-line { margin:0 0 5px; }
        .table-print th { background:#f5f5f5 !important; font-weight:bold; }
        .total-box { margin-top:10px; width:320px; float:right; }
        .print-footer { clear:both; margin-top:45px; padding-top:20px; }
        .signature-box { height:70px; border-top:1px solid #999; padding-top:8px; text-align:center; margin-top:45px; }
        .no-print { margin-bottom:15px; text-align:right; }
        @media print {
            body { background:#fff; }
            .print-wrap { width:100%; margin:0; border:0; padding:0; }
            .no-print { display:none; }
            a[href]:after { content:""; }
        }
    </style>
</head>

<body>

<div class="print-wrap">

    <div class="no-print">
        <button type="button" class="btn btn-primary btn-sm" onclick="window.print();"><i class="fa fa-print"></i> Print</button>
        <a href="{{ route('sale-orders.index') }}" class="btn btn-default btn-sm"><i class="fa fa-arrow-left"></i> Back</a>
    </div>

    <div class="print-header">
        <div class="row">
            <div class="col-xs-7">
                <h1 class="company-title">Loomexa</h1>
                <p style="margin:4px 0 0;">Production ERP Management</p>
            </div>
            <div class="col-xs-5 text-right">
                <h2 class="print-title">SALE ORDER</h2>
                <p style="margin:4px 0 0;"><strong>S.O. No:</strong> {{ $saleOrder->sale_order_number ?? '-' }}</p>
            </div>
        </div>
    </div>

    <div class="row">

        <div class="col-xs-4">
            <div class="info-box">
                <h4><i class="fa fa-user"></i> Customer Details</h4>
                <p class="info-line"><strong>Name:</strong> {{ $saleOrder->customer->name ?? '-' }}</p>
                <p class="info-line"><strong>Development Type:</strong> {{ $saleOrder->development_type ?: '-' }}</p>
                <p class="info-line"><strong>Order Type:</strong> {{ $saleOrder->sale_order_type == 1 ? 'Customer' : ($saleOrder->sale_order_type == 2 ? 'Self' : '-') }}</p>
            </div>
        </div>

        <div class="col-xs-4">
            <div class="info-box">
                <h4><i class="fa fa-info-circle"></i> Order Details</h4>
                <p class="info-line"><strong>Priority:</strong> {{ $saleOrder->order_priority ?: '-' }}</p>
                <p class="info-line"><strong>Lot No:</strong> {{ $saleOrder->lot_number ?: '-' }}</p>
                <p class="info-line"><strong>Sales Order:</strong> {{ $saleOrder->sales_order ?: '-' }}</p>
            </div>
        </div>

        <div class="col-xs-4">
            <div class="info-box">
                <h4><i class="fa fa-calendar"></i> Date Details</h4>
                <p class="info-line"><strong>Order Date:</strong> {{ !empty($saleOrder->sale_order_date) ? date('d-m-Y', strtotime($saleOrder->sale_order_date)) : '-' }}</p>
                <p class="info-line"><strong>Added Date:</strong> {{ !empty($saleOrder->created_at) ? date('d-m-Y', strtotime($saleOrder->created_at)) : '-' }}</p>
                <p class="info-line"><strong>Print Date:</strong> {{ date('d-m-Y h:i A') }}</p>
            </div>
        </div>

    </div>

    <table class="table table-bordered table-condensed table-print">
        <thead>
            <tr>
                <th style="width:40px;">#</th>
                <th>Item Details</th>
                <th style="width:90px;">Unit</th>
                <th style="width:90px;">Priority</th>
                <th style="width:110px;">Dyeing</th>
                <th style="width:110px;">Coating</th>
                <th style="width:105px;">Delivery</th>
                <th class="text-right" style="width:90px;">Rate</th>
                <th class="text-right" style="width:100px;">Meter</th>
                <th class="text-right" style="width:100px;">Pending</th>
            </tr>
        </thead>

        <tbody>
            @php
                $totalMeter = 0;
                $totalPending = 0;
                $totalAmount = 0;
            @endphp

            @forelse ($saleOrder->saleOrderItems as $item)
                @php
                    $meter = (float) $item->meter;
                    $pending = (float) $item->pending_item_mtr;
                    $rate = (float) $item->rate;
                    $amount = $meter * $rate;
                    $totalMeter += $meter;
                    $totalPending += $pending;
                    $totalAmount += $amount;
                @endphp

                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>
                        <strong>{{ $item->item_name ?: '-' }}</strong>
                        @if (!empty($item->grey_quality))
                            <br><small class="text-muted">{{ $item->grey_quality }}</small>
                        @endif
                    </td>
                    <td>{{ $item->unitType->unit_type_name ?? ($item->unit ?: '-') }}</td>
                    <td>{{ $item->order_item_priority ?: '-' }}</td>
                    <td>{{ $item->dyeing_color ?: '-' }}</td>
                    <td>{{ $item->coating_type ?: '-' }}</td>
                    <td>{{ !empty($item->expect_delivery_date) ? date('d-m-Y', strtotime($item->expect_delivery_date)) : '-' }}</td>
                    <td class="text-right">{{ number_format($rate, 2) }}</td>
                    <td class="text-right">{{ number_format($meter, 2) }}</td>
                    <td class="text-right">{{ number_format($pending, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="text-center text-muted">No order items found.</td>
                </tr>
            @endforelse
        </tbody>

        <tfoot>
            <tr>
                <th colspan="8" class="text-right">Total</th>
                <th class="text-right">{{ number_format($totalMeter, 2) }}</th>
                <th class="text-right">{{ number_format($totalPending, 2) }}</th>
            </tr>
        </tfoot>
    </table>

    <div class="total-box">
        <table class="table table-bordered table-condensed">
            <tbody>
                <tr>
                    <th>Total Meter</th>
                    <td class="text-right">{{ number_format($totalMeter, 2) }}</td>
                </tr>
                <tr>
                    <th>Total Pending</th>
                    <td class="text-right">{{ number_format($totalPending, 2) }}</td>
                </tr>
                <tr>
                    <th>Total Amount</th>
                    <td class="text-right"><strong>{{ number_format($totalAmount, 2) }}</strong></td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="print-footer">
        <div class="row">
            <div class="col-xs-4">
                <div class="signature-box">Prepared By</div>
            </div>
            <div class="col-xs-4">
                <div class="signature-box">Checked By</div>
            </div>
            <div class="col-xs-4">
                <div class="signature-box">Authorized Signatory</div>
            </div>
        </div>
    </div>

</div>

</body>
</html>