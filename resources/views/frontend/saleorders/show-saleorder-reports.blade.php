<!DOCTYPE html>
<html lang="en">

<head>
@include('frontend.common.head', ['pageTitle' => 'Sale Order Reports | Loomexa'])
</head>

<body class="hold-transition sidebar-mini sale-order-page">
<div id="preloader"><div id="status"></div></div>
<div class="wrapper">
@includeWhen(empty($isPrintReport), 'frontend.common.header')

<div class="content-wrapper">
    <section class="content">
        @if (empty($isPrintReport))
            {!! display_message('message') !!}
        @endif

        <div class="row">
            <div class="col-sm-12">
                <div class="panel panel-bd lobidrag">
                    <div class="panel-heading">
                        <div class="btn-group">
                            <h4>Sale Order Item Report</h4>
                        </div>
                    </div>

                    <div class="panel-body">
                        @if (empty($isPrintReport))
                            <div class="sale-order-filter-box">
                                <form action="{{ route('show-saleorder-reports') }}" method="GET" role="search" autocomplete="off" class="sale-order-report-filter-form">
                                    <div class="sale-order-report-filter-grid">
                                        <div class="report-filter-field report-filter-field-wide">
                                            <label for="cus_search">Customer</label>
                                            <input type="text" class="form-control" name="qsearch" id="cus_search" value="{{ $qsearch }}" placeholder="Customer name">
                                        </div>
                                        <div class="report-filter-field report-filter-field-wide">
                                            <label for="item_search">Item</label>
                                            <input type="text" class="form-control" name="qnamesearch" id="item_search" value="{{ $qnamesearch }}" placeholder="Item name">
                                            <input type="hidden" name="itemId" id="itemId" value="{{ $itemId }}">
                                        </div>
                                        <div class="report-filter-field">
                                            <label for="ordNumSearch">S.O. #</label>
                                            <input type="text" class="form-control" name="ordNumSearch" id="ordNumSearch" value="{{ $ordNumSearch }}" placeholder="S.O. #">
                                        </div>
                                        <div class="report-filter-field">
                                            <label for="priority">Status</label>
                                            <select class="form-control" name="priority" id="priority">
                                                <option value="">All</option>
                                                <option value="1" @selected($priority == '1')>Clear</option>
                                                <option value="2" @selected($priority == '2')>Pending</option>
                                            </select>
                                        </div>
                                        <div class="report-filter-field">
                                            <label for="from_date">From</label>
                                            <input type="text" class="form-control loomexa-datepicker" data-datepicker-max-date="0" name="from_date" id="from_date" value="{{ $fromDate }}" placeholder="From">
                                        </div>
                                        <div class="report-filter-field">
                                            <label for="to_date">To</label>
                                            <input type="text" class="form-control loomexa-datepicker" data-datepicker-max-date="0" name="to_date" id="to_date" value="{{ $toDate }}" placeholder="To">
                                        </div>
                                        <div class="report-filter-field">
                                            <label for="colorSearch">Color</label>
                                            <input type="text" class="form-control" name="colorSearch" id="colorSearch" value="{{ $colorSearch }}" placeholder="Color">
                                        </div>
                                        <div class="report-filter-field">
                                            <label for="sortingType">Sort</label>
                                            <select class="form-control" name="sortingType" id="sortingType">
                                                <option value="">Sort By</option>
                                                <option value="AZ" @selected($sortingType == 'AZ')>AZ</option>
                                                <option value="ZA" @selected($sortingType == 'ZA')>ZA</option>
                                            </select>
                                        </div>
                                        <div class="report-filter-actions">
                                            <label>&nbsp;</label>
                                            <button type="submit" name="sbtSearch" value="Search" class="btn btn-success" title="Search"><i class="fa fa-search"></i></button>
                                            <button type="submit" name="sbtSearch" value="ExportToExcel" class="btn btn-primary" title="Export Excel" formtarget="_blank"><i class="fa fa-download"></i></button>
                                            <button type="submit" name="sbtSearch" value="ExportToPdf" class="btn btn-danger" title="Export PDF" formtarget="_blank"><i class="fa fa-file-pdf-o"></i></button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        @endif

                        @php
                            $totMtr = 0;
                            $totDelvrMtr = 0;
                            $totPendMtr = 0;
                        @endphp

                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-hover small sale-order-report-table">
                                <thead>
                                    <tr class="info">
                                        <th>Rec.No.</th>
                                        <th>Duration</th>
                                        <th>S.O Date</th>
                                        <th>S.O.Number</th>
                                        <th>Customer</th>
                                        <th>Item</th>
                                        <th>Dyeing</th>
                                        <th>Coating</th>
                                        <th>Extra</th>
                                        <th>Print</th>
                                        <th>Rate</th>
                                        <th>Meter</th>
                                        <th>Delivered</th>
                                        <th>Pending</th>
                                        @if (empty($isPrintReport))
                                            <th>Action</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($dataSOI as $data)
                                        @php
                                            $saleOrder = $data->saleOrder;
                                            $customer = $saleOrder ? $saleOrder->customer : null;
                                            $customerName = $customer->name ?? $customer->company_name ?? '-';
                                            $meter = (float) $data->meter;
                                            $deliveredMeter = (float) $data->delivered_item_mtr;
                                            $pendingMeter = (float) $data->pending_item_mtr;

                                            if ($pendingMeter == 0 && $meter > 0) {
                                                $pendingMeter = $meter - $deliveredMeter;
                                            }

                                            $totMtr += $meter;
                                            $totDelvrMtr += $deliveredMeter;
                                            $totPendMtr += $pendingMeter;

                                            $saleOrderItemJson = $data->getAttributes();
                                            $saleOrderItemJson['sale_order_number'] = $saleOrder->sale_order_number ?? '';
                                            $saleOrderItemJson['customer_name'] = $customerName;
                                        @endphp
                                        <tr>
                                            <td class="text-center">{{ $data->id }}</td>
                                            <td>{!! $saleOrder && $saleOrder->sale_order_date ? daysFromNow($saleOrder->sale_order_date) : '-' !!}</td>
                                            <td>{{ $saleOrder && $saleOrder->sale_order_date ? date('d-m-Y', strtotime($saleOrder->sale_order_date)) : '-' }}</td>
                                            <td class="text-center">{{ $saleOrder->sale_order_number ?? '-' }}</td>
                                            <td title="{{ $customerName }}">{{ \Illuminate\Support\Str::limit($customerName, 28) }}</td>
                                            <td>{{ $data->item_name ?: '-' }}</td>
                                            <td>{{ $data->dyeing_color ?: '-' }}</td>
                                            <td>{{ $data->coating_type ?: '-' }}</td>
                                            <td>{{ $data->extra_job ?: '-' }}</td>
                                            <td>{{ $data->print_job ?: '-' }}</td>
                                            <td>{{ number_format((float) $data->rate, 2) }}</td>
                                            <td>{{ number_format($meter, 2) }}</td>
                                            <td>
                                                {{ number_format($deliveredMeter, 2) }}
                                                @if (!empty($data->dlvr_cleared_by) && !empty($data->dlvr_cleared_reason))
                                                    <button type="button" class="btn btn-add btn-xs reportReasonBtn" data-toggle="modal" data-target="#reportReasonModal" data-reason="{{ $data->dlvr_cleared_reason }}">Reason</button>
                                                @endif
                                            </td>
                                            <td>{{ number_format($pendingMeter, 2) }}</td>
                                            @if (empty($isPrintReport))
                                                <td>
                                                    @if ($data->is_work_final_dlvr_completed == '1')
                                                        <span class="label label-success sale-order-report-status-icon" title="Clear"><i class="fa fa-check"></i></span>
                                                    @else
                                                        <button type="button" class="btn btn-add btn-xs reportClearItemBtn" data-toggle="modal" data-target="#reportClearItemModal" title="Clear"
                                                            data-sale-order-id="{{ enc($data->sale_order_id) }}"
                                                            data-sale-order-item-id="{{ enc($data->id) }}">
                                                            <i class="fa fa-check"></i>
                                                        </button>

                                                        @if (!empty($data->edited_by))
                                                            <span class="label label-default sale-order-report-status-icon" title="Edited"><i class="fa fa-pencil"></i></span>
                                                        @else
                                                        <script type="application/json" id="report-sale-order-item-json-{{ $data->id }}">@json($saleOrderItemJson)</script>
                                                        <button type="button" class="btn btn-primary btn-xs reportEditItemBtn" data-toggle="modal" data-target="#reportEditItemModal" title="Edit"
                                                            data-sale-order-id="{{ enc($data->sale_order_id) }}"
                                                            data-sale-order-item-id="{{ enc($data->id) }}"
                                                            data-json-id="report-sale-order-item-json-{{ $data->id }}">
                                                            <i class="fa fa-pencil"></i>
                                                        </button>
                                                        @endif
                                                    @endif
                                                </td>
                                            @endif
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="{{ empty($isPrintReport) ? 15 : 14 }}" class="text-center text-muted sale-order-empty-cell">No sale order item report found.</td>
                                        </tr>
                                    @endforelse

                                    <tr class="info">
                                        <td colspan="10" class="text-right"><strong>Total</strong></td>
                                        <td>&nbsp;</td>
                                        <td><strong>{{ number_format($totMtr, 2) }} MTR</strong></td>
                                        <td><strong>{{ number_format($totDelvrMtr, 2) }} MTR</strong></td>
                                        <td><strong>{{ number_format($totPendMtr, 2) }} MTR</strong></td>
                                        @if (empty($isPrintReport))
                                            <td>&nbsp;</td>
                                        @endif
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        @if (empty($isPrintReport) && method_exists($dataSOI, 'links'))
                            <div class="pagination text-center sale-order-pagination">
                                {{ $dataSOI->links('vendor.pagination.bootstrap-4') }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

@if (empty($isPrintReport))
<div class="modal fade loomexa-modal" id="reportReasonModal" tabindex="-1" role="dialog" aria-labelledby="reportReasonLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">&times;</button>
                <h4 class="modal-title" id="reportReasonLabel">Reason</h4>
            </div>
            <div class="modal-body">
                <p id="reportReasonText" class="text-center"></p>
            </div>
        </div>
    </div>
</div>

<div class="modal fade loomexa-modal" id="reportClearItemModal" tabindex="-1" role="dialog" aria-labelledby="reportClearItemLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg report-clear-item-dialog" role="document">
        <div class="modal-content">
            <form name="report_clear_sale_order_item_form" method="post" action="{{ route('clearSaleOrderItem') }}">
                @csrf
                <div class="modal-header report-clear-item-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">&times;</button>
                    <h3 class="modal-title" id="reportClearItemLabel"><i class="fa fa-check-circle"></i> Complete Order Item</h3>
                </div>
                <div class="modal-body report-clear-item-body">
                    <div class="report-clear-confirm-box">
                        <div class="report-clear-warning-icon">
                            <i class="fa fa-check"></i>
                        </div>
                        <div class="report-clear-confirm-text">
                            <h3>Are you sure you want to complete this order item?</h3>
                            <p>You will not be able to undo this action, and a detailed report will be sent to the <strong>director</strong> for review.</p>
                        </div>
                    </div>

                    <input name="FId" id="report_clear_sale_order_id" value="" type="hidden">
                    <input name="soItemId" id="report_clear_sale_order_item_id" value="" type="hidden">

                    <fieldset>
                        <div class="report-clear-comment-box form-group">
                            <label class="control-label">Completion Comment</label>
                            <input type="text" placeholder="Write a short completion comment" required class="form-control" name="dlvr_cleared_reason" id="report_clear_dlvr_cleared_reason">
                            <p class="help-block">Your comment will be saved with this clear action.</p>
                        </div>
                    </fieldset>
                </div>
                <div class="modal-footer report-clear-item-footer">
                    <button type="button" class="btn btn-danger" data-dismiss="modal"><i class="fa fa-times"></i> Close</button>
                    <button type="submit" class="btn btn-success"><i class="fa fa-save"></i> Save changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade loomexa-modal" id="reportEditItemModal" tabindex="-1" role="dialog" aria-labelledby="reportEditItemLabel" aria-hidden="true">
    <div class="modal-dialog modal-md report-update-item-dialog" role="document">
        <div class="modal-content">
            <form id="reportEditForm" name="report_edit_sale_order_item_form" method="post" action="{{ route('sale-order.update-item') }}" onsubmit="return confirmReportItemSubmission();">
                @csrf
                <div class="modal-header report-update-item-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">&times;</button>
                    <h4 class="modal-title" id="reportEditItemLabel"><i class="glyphicon glyphicon-edit"></i> Update Sale Order Item</h4>
                </div>
                <div class="modal-body report-update-item-body">
                    <div class="alert alert-warning report-update-item-notice">
                        <strong><i class="glyphicon glyphicon-info-sign"></i> Notice:</strong>
                        You can modify this sales order item only once. A detailed report of this change will be sent to the director for review.
                        After this modification, the button will be disabled, so please ensure all changes are made carefully.
                    </div>

                    <input name="FId" id="report_edit_sale_order_id" value="" type="hidden">
                    <input name="soItemId" id="report_edit_sale_order_item_id" value="" type="hidden">
                    <input name="item_id" id="report_edit_item_id" value="" type="hidden">
                    <input name="unit_type_id" id="report_edit_unit_type_id" value="" type="hidden">
                    <input name="order_item_priority" id="report_edit_order_item_priority" value="" type="hidden">
                    <input name="pcs" id="report_edit_pcs" value="" type="hidden">
                    <input name="cut" id="report_edit_cut" value="" type="hidden">
                    <input name="amount" id="report_edit_amount" value="" type="hidden">
                    <input name="grey_quality" id="report_edit_grey_quality" value="" type="hidden">
                    <input name="packing_roll_length" id="report_edit_packing_roll_length" value="" type="hidden">
                    <input name="final_dispatch_width" id="report_edit_final_dispatch_width" value="" type="hidden">
                    <input name="tube_width" id="report_edit_tube_width" value="" type="hidden">
                    <input name="development_type" id="report_edit_development_type" value="" type="hidden">
                    <input name="expect_delivery_date" id="report_edit_expect_delivery_date" value="" type="hidden">

                    <div class="report-update-item-section">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                <label>Item Name</label>
                                    <input type="text" name="item_name" id="report_edit_item_name" class="form-control input-lg" autocomplete="off" placeholder="Search item name">
                                </div>
                            </div>

                            <div class="col-md-12">
                                <div class="form-group">
                                    <label>Sale Order Number</label>
                                    <input type="text" id="report_edit_sale_order_number" class="form-control" placeholder="Sale order number" readonly>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Dyeing Color</label>
                                    <input type="text" class="form-control" id="report_edit_dyeing_color" name="dyeing_color" autocomplete="off" placeholder="Enter color">
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Coated PVC</label>
                                    <select id="report_edit_coating_type" name="coating_type" class="form-control">
                                        <option value="">Select Coating</option>
                                        @foreach (($coatings ?? collect()) as $coating)
                                            <option value="{{ $coating->code }}">{{ $coating->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Extra Job</label>
                                    <input type="text" class="form-control" id="report_edit_extra_job" name="extra_job" placeholder="Extra job">
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Print Job</label>
                                    <input type="text" class="form-control" id="report_edit_print_job" name="print_job" placeholder="Print job">
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Meter</label>
                                    <input type="text" class="form-control" id="report_edit_meter" name="meter" placeholder="Enter meter">
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Rate</label>
                                    <input type="text" class="form-control" id="report_edit_rate" name="rate" placeholder="Enter rate">
                                </div>
                            </div>

                            <div class="col-md-12">
                                <div class="form-group">
                                    <label>Remark</label>
                                    <input type="text" class="form-control" id="report_edit_remarks" name="remarks" placeholder="Enter Remark">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer report-update-item-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal"><i class="fa fa-times"></i> Close</button>
                    <button type="submit" class="btn btn-primary"><i class="glyphicon glyphicon-floppy-disk"></i> Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

@include('frontend.common.footer')
@endif
</div>

@if (empty($isPrintReport))
@include('frontend.common.footerscript')

<script type="text/javascript">
var siteUrl = "{{ url('/') }}";

function confirmReportItemSubmission()
{
    return confirm("Are you sure you want to submit the report?");
}

$(document).on('click', '.reportReasonBtn', function () {
    $('#reportReasonText').text($(this).data('reason') || '-');
});

$(document).on('click', '.reportClearItemBtn', function () {
    $('#report_clear_sale_order_id').val($(this).data('sale-order-id'));
    $('#report_clear_sale_order_item_id').val($(this).data('sale-order-item-id'));
    $('#report_clear_dlvr_cleared_reason').val('');
});

$(document).on('click', '.reportEditItemBtn', function () {
    $('#report_edit_sale_order_id').val($(this).data('sale-order-id'));
    $('#report_edit_sale_order_item_id').val($(this).data('sale-order-item-id'));

    var jsonId = $(this).data('json-id');
    var itemData = JSON.parse($('#' + jsonId).html());

    $.each(itemData, function (fieldName, fieldValue) {
        $('#report_edit_' + fieldName).val(formatReportEditDateValue(fieldValue));
    });

    $('#report_edit_item_id').val(itemData.item_id);
});

$(document).on('keyup change', '#report_edit_meter, #report_edit_rate', function () {
    var meter = parseFloat($('#report_edit_meter').val()) || 0;
    var rate = parseFloat($('#report_edit_rate').val()) || 0;
    $('#report_edit_amount').val((meter * rate).toFixed(2));
});

function formatReportEditDateValue(fieldValue)
{
    if (fieldValue == null) {
        return '';
    }

    if (typeof fieldValue === 'string' && fieldValue.length >= 10 && fieldValue.substring(4, 5) === '-' && fieldValue.substring(7, 8) === '-') {
        return fieldValue.substring(8, 10) + '-' + fieldValue.substring(5, 7) + '-' + fieldValue.substring(0, 4) + fieldValue.substring(10);
    }

    return fieldValue;
}

$("#cus_search").autocomplete({
    minLength: 0,
    source: siteUrl + "/list_customer",
    focus: function(event, ui) {
        $("#cus_search").val(ui.item.name);
        return false;
    },
    select: function(event, ui) {
        $("#cus_search").val(ui.item.name);
        return false;
    }
}).autocomplete("instance")._renderItem = function(ul, item) {
    return $("<li>").append($("<div>").text(item.name)).appendTo(ul);
};

$("#item_search").autocomplete({
    minLength: 0,
    source: siteUrl + "/fabric_list_item",
    focus: function(event, ui) {
        $("#item_search").val(ui.item.item_name);
        return false;
    },
    select: function(event, ui) {
        $("#item_search").val(ui.item.item_name);
        $("#itemId").val(ui.item.item_id);
        return false;
    }
}).autocomplete("instance")._renderItem = function(ul, item) {
    return $("<li>").append("<div>" + item.item_name + "<br> Item Code: " + (item.item_code || "") + "</div>").appendTo(ul);
};

$("#ordNumSearch").autocomplete({
    minLength: 0,
    source: siteUrl + "/find_saleOrderNumer",
    focus: function(event, ui) {
        $("#ordNumSearch").val(ui.item.sale_order_number);
        return false;
    },
    select: function(event, ui) {
        $("#ordNumSearch").val(ui.item.sale_order_number);
        return false;
    }
}).autocomplete("instance")._renderItem = function(ul, item) {
    return $("<li>").append($("<div>").text(item.sale_order_number)).appendTo(ul);
};

$("#report_edit_item_name").autocomplete({
    minLength: 0,
    appendTo: "#reportEditItemModal",
    source: function(request, response) {
        $.ajax({
            url: siteUrl + "/fabric_list_item",
            dataType: "json",
            data: { term: request.term },
            success: function(data) {
                response(data);
            }
        });
    },
    focus: function(event, ui) {
        $("#report_edit_item_name").val(ui.item.item_name);
        return false;
    },
    select: function(event, ui) {
        $("#report_edit_item_name").val(ui.item.item_name);
        $("#report_edit_item_id").val(ui.item.item_id);
        $("#report_edit_grey_quality").val(ui.item.internal_item_name || "");
        $("#report_edit_unit_type_id").val(ui.item.unit_type_id || "");
        $("#report_edit_rate").val(ui.item.sale_rate || ui.item.unit_price || "");

        var meter = parseFloat($("#report_edit_meter").val()) || 0;
        var rate = parseFloat($("#report_edit_rate").val()) || 0;
        $("#report_edit_amount").val((meter * rate).toFixed(2));
        return false;
    }
}).on("focus", function() {
    $(this).autocomplete("search", $(this).val());
}).autocomplete("instance")._renderItem = function(ul, item) {
    return $("<li>").append("<div>" + item.item_name + "<br> Item Code: " + (item.item_code || "") + "<br> Internal Name: " + (item.internal_item_name || "") + "</div>").appendTo(ul);
};
</script>
@else
<script>
window.print();
</script>
@endif
</body>
</html>
