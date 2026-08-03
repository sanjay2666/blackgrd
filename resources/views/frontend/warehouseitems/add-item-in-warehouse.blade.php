<!DOCTYPE html>
<html lang="en">
<head>
@include('frontend.common.head', ['pageTitle' => 'Store Warehouse Item | Loomexa'])
<meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body class="hold-transition sidebar-mini warehouse-item-page">
<div id="preloader">
  <div id="status"></div>
</div>

<div class="wrapper">
@include('frontend.common.header')

<div class="content-wrapper">
  <section class="content">
    <div class="row">
      <div class="col-sm-12">
        {!! display_message('message') !!}

        <div class="panel panel-bd lobidrag">
          <div class="panel-heading warehouse-page-heading">
            <div>
              <h4><i class="fa fa-archive"></i> Store Warehouse Item</h4>
              <span>Receive purchase stock into warehouse compartments with document tracking.</span>
            </div>
          </div>

          <div class="panel-body">
            <form method="post" action="{{ route('store_item_in_warehouse') }}" onsubmit="return warehouseSubmitAfterValid(this);" enctype="multipart/form-data" autocomplete="off">
              @csrf

              <div class="row">
                <div class="col-md-12">
                  <div class="wh-section-title">
                    <span class="glyphicon glyphicon-list-alt"></span> Purchase & Invoice Details
                  </div>
                  <div class="panel panel-info wh-detail-panel">
                    <div class="panel-body">
                      <div class="row">
                        <div class="form-group col-md-2">
                          <label for="purchase_ord_number">P.O Number <span class="text-danger">*</span></label>
                          <input type="text" id="purchase_ord_number" name="purchase_ord_number" class="form-control" required>
                        </div>
                        <div class="form-group col-md-2">
                          <label for="invoice_number">Invoice Number <span class="text-danger">*</span></label>
                          <input type="text" id="invoice_number" name="invoice_number" class="form-control" required>
                        </div>
                        <div class="form-group col-md-2">
                          <label for="for_stock_type">Stock Type <span class="text-danger">*</span></label>
                          <select class="form-control input-sm" id="for_stock_type" name="for_stock_type" required>
                            <option value="">Select Stock For</option>
                            <option value="0">Home</option>
                            <option value="1">Job</option>
                          </select>
                        </div>
                        <div class="form-group col-md-2">
                          <label for="receiving_date">Receiving Date <span class="text-danger">*</span></label>
                          <input type="text" id="receiving_date" name="receiving_date" class="form-control" required>
                        </div>
                        <div class="form-group col-md-2">
                          <label for="invoice_copy_file">Invoice PDF <span class="text-danger">*</span></label>
                          <label class="loomexa-file-upload" for="invoice_copy_file">
                            <input type="file" id="invoice_copy_file" name="invoice_copy_file" required>
                            <span class="loomexa-file-icon"><i class="fa fa-file-pdf-o"></i></span>
                            <span class="loomexa-file-main">
                              <span class="loomexa-file-action">Choose file</span>
                              <span class="loomexa-file-name" data-file-label-for="invoice_copy_file">No file selected</span>
                            </span>
                          </label>
                          <small class="text-muted"><span class="glyphicon glyphicon-info-sign"></span> Allowed: PDF, JPG, JPEG, PNG</small>
                        </div>
                        <div class="form-group col-md-2">
                          <label for="packing_slip_file">Packing PDF <span class="text-danger">*</span></label>
                          <label class="loomexa-file-upload" for="packing_slip_file">
                            <input type="file" id="packing_slip_file" name="packing_slip_file" required>
                            <span class="loomexa-file-icon"><i class="fa fa-file-text-o"></i></span>
                            <span class="loomexa-file-main">
                              <span class="loomexa-file-action">Choose file</span>
                              <span class="loomexa-file-name" data-file-label-for="packing_slip_file">No file selected</span>
                            </span>
                          </label>
                          <small class="text-muted"><span class="glyphicon glyphicon-info-sign"></span> Allowed: PDF, JPG, JPEG, PNG</small>
                        </div>
                      </div>
                    </div>
                  </div>

                  <div class="wh-section-title">
                    <span class="glyphicon glyphicon-home"></span> Warehouse & Employee Info
                  </div>
                  <div class="panel panel-info wh-detail-panel">
                    <div class="panel-body">
                      <div class="row">
                        <div class="form-group col-md-2">
                          <label for="warehouseId">Warehouse <span class="text-danger">*</span></label>
                          <select class="form-control" name="warehouseId" id="warehouseId" required onchange="selectCompartment(this.value);">
                            <option value="">Select Warehouse</option>
                            @foreach ($dataW as $val)
                              <option value="{{ $val->id }}">{{ $val->warehouse_name }}</option>
                            @endforeach
                          </select>
                        </div>
                        <div class="form-group col-md-2" id="warehouseCompIdDiv"></div>
                        
                        <div class="form-group col-md-2">
                          <label for="emp_name">Receiver Employee Name <span class="text-danger">*</span></label>
                          <input type="text" id="emp_name" name="emp_name" class="form-control" required>
                          <input type="hidden" id="ind_emp_id" name="ind_emp_id">
                        </div>
						
						<div class="form-group col-md-2">
                          <label for="vendor_name">Vendor Name <span class="text-danger">*</span></label>
                          <input type="text" id="vendor_name" name="vendor_name" class="form-control" placeholder="Vendor Name" required>
                          <input type="hidden" id="vendor_id" name="vendor_id">
                        </div>
						
                        <div class="form-group col-md-2">
                          <label for="eway_bill_file">Eway Bill <span class="text-danger">*</span></label>
                          <label class="loomexa-file-upload" for="eway_bill_file">
                            <input type="file" id="eway_bill_file" name="eway_bill_file" required>
                            <span class="loomexa-file-icon"><i class="fa fa-truck"></i></span>
                            <span class="loomexa-file-main">
                              <span class="loomexa-file-action">Choose file</span>
                              <span class="loomexa-file-name" data-file-label-for="eway_bill_file">No file selected</span>
                            </span>
                          </label>
                          <small class="text-muted"><span class="glyphicon glyphicon-info-sign"></span> Allowed: PDF, JPG, JPEG, PNG</small>
                        </div>
                        <div class="form-group col-md-2">
                          <label for="lr_copy_file">LR Copy <span class="text-danger">*</span></label>
                          <label class="loomexa-file-upload" for="lr_copy_file">
                            <input type="file" id="lr_copy_file" name="lr_copy_file" required>
                            <span class="loomexa-file-icon"><i class="fa fa-paperclip"></i></span>
                            <span class="loomexa-file-main">
                              <span class="loomexa-file-action">Choose file</span>
                              <span class="loomexa-file-name" data-file-label-for="lr_copy_file">No file selected</span>
                            </span>
                          </label>
                          <small class="text-muted"><span class="glyphicon glyphicon-info-sign"></span> Allowed: PDF, JPG, JPEG, PNG</small>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="col-md-12" id="main_div">
                  <div class="wh-section-title">
                    <span class="glyphicon glyphicon-plus-sign"></span> Add Warehouse Item
                  </div>
                  <div class="panel panel-info wh-item-panel">
                    <div class="panel-body">
                      <div class="row">
                        <div class="col-xs-12">
                          <div class="table-responsive table-responsive-custom">
                            <table class="table table-bordered wh-entry-table">
                              <tbody>
                                <tr>
                                  <td style="width:10%;">
                                    <div class="input-group">
                                      <label for="pur_type">Type</label>
                                      <select class="form-control" required name="pur_type" id="pur_type" onchange="changePurType();">
                                        <option value="">Select Type</option>
                                        @foreach ($dataIT as $valIT)
                                          <option value="{{ $valIT->item_type_id }}">{{ $valIT->item_type_name }}</option>
                                        @endforeach
                                      </select>
                                    </div>
                                  </td>
                                  <td style="width:15%;">
                                    <div class="input-group">
                                      <label for="product_name">Item Name</label>
                                      <input type="text" id="product_name" name="product_name" class="form-control" placeholder="Product Name">
                                      <input type="hidden" id="pro_id" name="pro_id">
                                    </div>
                                  </td>
                                  <td style="width:10%;" id="dyeingclr">
                                    <div class="input-group">
                                      <label for="item_dyeing_color">Dyeing Color</label>
                                      <input type="text" id="item_dyeing_color" name="item_dyeing_color" class="form-control" value="0">
                                    </div>
                                  </td>
                                  <td style="width:10%;">
                                    <div class="input-group">
                                      <label for="hsn">HSN/SAC</label>
                                      <input type="text" id="hsn" name="hsn" class="form-control" placeholder="HSN/SAC" value="">
                                    </div>
                                  </td>
                                  <td style="width:10%;">
                                    <div class="input-group">
                                      <label for="qty">Quantity</label>
                                      <input type="text" id="qty" name="qty" class="form-control" placeholder="Quantity" value="">
                                    </div>
                                  </td>
                                  <td style="width:10%;">
                                    <div class="input-group">
                                      <label for="unit">Unit</label>
                                      <select id="unit" name="unit" class="form-control" onchange="changeUnit();">
                                        <option value="">Select Type</option>
                                        @foreach ($dataUT as $utVal)
                                          <option value="{{ $utVal->unit_type_id }}">{{ $utVal->unit_type_name }}</option>
                                        @endforeach
                                      </select>
                                    </div>
                                  </td>
                                  <td style="width:10%;">
                                    <div class="input-group">
                                      <label id="meterId" for="meter">Meter</label>
                                      <label id="meterkgId" for="meter">Kg</label>
                                      <input type="text" id="meter" name="meter" class="form-control" value="0">
                                    </div>
                                  </td>
                                  <td style="width:10%;" id="beam_meterId">
                                    <div class="input-group">
                                      <label for="beam_meter">B.Meter</label>
                                      <input type="text" id="beam_meter" name="beam_meter" class="form-control" value="0">
                                    </div>
                                  </td>
                                  <td style="width:15%;">
                                    <div class="input-group">
                                      <label id="taka_numberLotId" for="taka_number">L.Number</label>
                                      <label id="taka_numberTakaId" for="taka_number">T.Number</label>
                                      <input type="text" id="taka_number" name="taka_number" class="form-control" value="0">
                                    </div>
                                  </td>
                                  <td style="width:10%;">
                                    <div class="input-group">
                                      <label for="remarks">Remark</label>
                                      <input type="text" id="remarks" class="form-control" name="remarks" value="">
                                    </div>
                                  </td>
                                  <td style="width:30px;">
                                    <button type="button" id="Add_To_Purchase" class="btn btn-primary" title="Add Item"><i class="fa fa-plus"></i></button>
                                  </td>
                                </tr>
                              </tbody>
                            </table>
                          </div>
                        </div>
                      </div>

                      <div class="wh-section-title wh-added-title">
                        <span class="glyphicon glyphicon-th-list"></span> Warehouse Item List
                        <div class="wh-total-meter">
                          <label for="sum_meter_arr_value">Total Meter/Kg</label>
                          <input readonly id="sum_meter_arr_value" name="sum_meter_arr_value" class="form-control">
                        </div>
                      </div>

                      <div class="form-group">
                        <div class="box-body">
                          <table id="example2" class="table table-bordered table-striped wh-added-table">
                            <thead>
                              <tr>
                                <th style="width:10px;">#</th>
                                <th style="width:100px;">Type</th>
                                <th style="width:100px;">Item</th>
                                <th style="width:100px;">Dyeing</th>
                                <th style="width:100px;">HSN/SAC</th>
                                <th style="width:100px;">Qty</th>
                                <th style="width:100px;">Unit</th>
                                <th style="width:100px;">Meter/Kg</th>
                                <th style="width:100px;">B.Meter</th>
                                <th style="width:100px;">T.Number</th>
                                <th style="width:100px;">Remarks</th>
                                <th style="width:30px;">Action</th>
                              </tr>
                            </thead>
                            <tbody id="tbody"></tbody>
                          </table>
                          <input type="hidden" id="count_product" name="count_product" value="0">
                        </div>
                      </div>

                      <div class="wh-actions">
                        <button type="reset" class="btn btn-danger" id="confirmBtn" style="display:none"><i class="fa fa-times"></i> Discard</button>
                        <button type="submit" class="btn btn-primary" id="resetBtn" style="display:none"><i class="fa fa-check"></i> Confirm</button>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>

@include('frontend.common.footer')
</div>
@include('frontend.common.footerscript')

<script type="text/javascript">
var siteUrl = "{{ url('/') }}";
var csrfToken = "{{ csrf_token() }}";
var warehouseFormSubmitting = false;
var selectedColor = null;
</script>

<script type="text/javascript">
function warehouseSubmitAfterValid(form) {
    if (check_form() === false) {
        return false;
    }

    if (form.checkValidity && !form.checkValidity()) {
        if (form.reportValidity) {
            form.reportValidity();
        }
        return false;
    }

    if (warehouseFormSubmitting) {
        return false;
    }

    warehouseFormSubmitting = true;

    $("#resetBtn").prop("disabled", true).html('<i class="fa fa-spinner fa-spin"></i> Processing...');
    $("#confirmBtn").prop("disabled", true);

    return true;
}
</script>

<script type="text/javascript">
function check_form() {
    if ($("#vendor_id").val() === "") {
        alert("Please select a vendor.");
        $("#vendor_name").focus();
        return false;
    }

    if (parseInt($("#count_product").val(), 10) === 0) {
        alert("Please add a product to the purchase list.");
        $("#product_name").focus();
        return false;
    }

    return true;
}
</script>

<script type="text/javascript">
function selectCompartment(Id) {
    $.ajax({
        type: "GET",
        url: siteUrl + "/ajax_script/search_warehouse_compartment",
        data: {
            _token: csrfToken,
            Id: Id
        },
        cache: false,
        success: function(res) {
            $("#warehouseCompIdDiv").html(res);
        }
    });
}
</script>

<script type="text/javascript">
function selectEmployee(Id) {
    $.ajax({
        type: "GET",
        url: siteUrl + "/ajax_script/getWarehouseCompEmployee",
        data: {
            _token: csrfToken,
            Id: Id
        },
        cache: false,
        success: function(msg) {
            var data = msg.split("||");
            $("#emp_name").val(data[1] || "");
            $("#ind_emp_id").val(data[0] || "");
        }
    });
}
</script>

<script type="text/javascript">
function changePurType() {
    var purType = $("#pur_type").val();

    $("#dyeingclr").toggle(purType === "4");
    $("#taka_numberLotId").toggle(purType === "1" || purType === "2");
    $("#taka_numberTakaId").toggle(!(purType === "1" || purType === "2"));
    $("#beam_meterId").toggle(purType === "2");
}
</script>

<script type="text/javascript">
function changeUnit() {
    var unit = $("#unit").val();

    $("#meterkgId").toggle(unit === "4");
    $("#meterId").toggle(unit !== "4");
}
</script>

<script type="text/javascript">
function calculateSum() {
    var sum = 0;

    $('input[name="meter_arr[]"]').each(function() {
        sum += Number($(this).val()) || 0;
    });

    $("#sum_meter_arr_value").val(sum.toFixed(2));
}
</script>

<script type="text/javascript">
function updateProductCount() {
    $("#tbody tr").each(function(index) {
        $(this).find("td:first").text(index + 1);
    });

    $("#count_product").val($("#tbody tr").length);
}
</script>

<script type="text/javascript">
function toggleActionButtons() {
    var hasRows = $("#tbody tr").length > 0;
    $("#resetBtn, #confirmBtn").toggle(hasRows);
}
</script>

<script type="text/javascript">
function escapeHtml(value) {
    return String(value || "")
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}
</script>

<script type="text/javascript">
function removeRows(rowId) {
    $("#" + rowId).remove();
    updateProductCount();
    calculateSum();
    toggleActionButtons();
}
</script>

<script type="text/javascript">
function addWarehouseItemRow() {
    if ($("#product_name").val() === "") {
        $("#product_name").focus();
        return;
    }

    var rowNumber = $("#tbody tr").length + 1;
    var rowId = "tr_" + Date.now();
    var rowHtml = "<tr id='" + rowId + "'>" +
        "<td>" + rowNumber + "</td>" +
        "<td><input readonly value='" + escapeHtml($("#pur_type").val()) + "' type='text' class='form-control' name='pur_type_arr[]'></td>" +
        "<td><input value='" + escapeHtml($("#pro_id").val()) + "' type='hidden' name='pro_id_arr[]' readonly><input readonly value='" + escapeHtml($("#product_name").val()) + "' type='text' class='form-control' name='product_name_arr[]'></td>" +
        "<td><input readonly value='" + escapeHtml($("#item_dyeing_color").val()) + "' type='text' class='form-control' name='item_dyeing_color_arr[]'></td>" +
        "<td><input readonly value='" + escapeHtml($("#hsn").val()) + "' type='text' class='form-control' name='hsn_arr[]'></td>" +
        "<td><input readonly value='" + escapeHtml($("#qty").val()) + "' type='text' class='form-control' name='qty_arr[]'></td>" +
        "<td><input readonly value='" + escapeHtml($("#unit").val()) + "' type='text' class='form-control' name='unit_arr[]'></td>" +
        "<td><input readonly value='" + escapeHtml($("#meter").val()) + "' type='text' class='form-control' name='meter_arr[]'></td>" +
        "<td><input readonly value='" + escapeHtml($("#beam_meter").val()) + "' type='text' class='form-control' name='beam_meter_arr[]'></td>" +
        "<td><input readonly value='" + escapeHtml($("#taka_number").val()) + "' type='text' class='form-control' name='taka_number_arr[]'></td>" +
        "<td><input readonly value='" + escapeHtml($("#remarks").val()) + "' type='text' class='form-control' name='remarks_arr[]'></td>" +
        "<td><a data-toggle='tooltip' href='javascript:void(0);' onclick='removeRows(\"" + rowId + "\");' title='Remove'><span class='glyphicon glyphicon-remove-circle remove'></span></a></td>" +
        "</tr>";

    $("#tbody").append(rowHtml);
    updateProductCount();
    calculateSum();
    toggleActionButtons();
    $("#product_name").focus();
}
</script>

<script type="text/javascript">
function initVendorAutocomplete() {
    $("#vendor_name").autocomplete({
        minLength: 3,
        source: function(request, response) {
            var stockType = $("#for_stock_type").val();
            var url = "";

            if (stockType === "0") {
                url = siteUrl + "/list_vendor";
            } else if (stockType === "1") {
                url = siteUrl + "/list_customer";
            } else {
                response([]);
                return;
            }

            $.ajax({
                url: url,
                dataType: "json",
                data: {
                    term: request.term
                },
                success: function(data) {
                    response(data);
                }
            });
        },
        focus: function(event, ui) {
            $("#vendor_name").val(ui.item.name);
            event.preventDefault();
        },
        select: function(event, ui) {
            $("#vendor_id").val(ui.item.id);
            $("#vendor_name").val(ui.item.name);
            return false;
        }
    }).autocomplete("instance")._renderItem = function(ul, item) {
        return $("<li>")
            .append("<div>" + escapeHtml(item.name) + "<br> GSTIN - " + escapeHtml(item.gstin) + "</div>")
            .appendTo(ul);
    };
}
</script>

<script type="text/javascript">
function initEmployeeAutocomplete() {
    $("#emp_name").autocomplete({
        minLength: 0,
        source: siteUrl + "/list_employee",
        focus: function(event, ui) {
            $("#emp_name").val(ui.item.name);
            $("#ind_emp_id").val(ui.item.id);
            return false;
        },
        select: function(event, ui) {
            $("#emp_name").val(ui.item.name);
            $("#ind_emp_id").val(ui.item.id);
            return false;
        }
    }).autocomplete("instance")._renderItem = function(ul, item) {
        return $("<li>")
            .append("<div>" + escapeHtml(item.name) + "</div>")
            .appendTo(ul);
    };
}
</script>

<script type="text/javascript">
function initProductAutocomplete() {
    $("#product_name").autocomplete({
        source: function(request, response) {
            $.ajax({
                url: siteUrl + "/list_warehouse_item_type",
                dataType: "json",
                data: {
                    term: request.term,
                    type: $("#pur_type").val()
                },
                success: function(data) {
                    response(data);
                }
            });
        },
        minLength: 0,
        focus: function(event, ui) {
            $("#product_name").val(ui.item.item_name);
            return false;
        },
        select: function(event, ui) {
            $("#pro_id").val(ui.item.item_id);
            $("#product_name").val(ui.item.item_name);
            $("#hsn").val(ui.item.hsncode);
            return false;
        }
    }).autocomplete("instance")._renderItem = function(ul, item) {
        return $("<li>")
            .append("<div>" + escapeHtml(item.item_name) + "<br> Item Code: " + escapeHtml(item.item_code) + "<br> Internal Name: " + escapeHtml(item.internal_item_name) + "</div>")
            .appendTo(ul);
    };
}
</script>

<script type="text/javascript">
function initReceivingDatePicker() {
    $("#receiving_date").datepicker({
        dateFormat: "dd-mm-yy",
        autoclose: true
    });
}
</script>

<script type="text/javascript">
function initColorAutocomplete() {
    $("#item_dyeing_color").autocomplete({
        minLength: 0,
        source: function(request, response) {
            $.ajax({
                url: siteUrl + "/list_master_color",
                data: {
                    term: request.term,
                    id: $("#vendor_id").val()
                },
                success: function(data) {
                    response($.map(data, function(item) {
                        return {
                            label: item.name,
                            value: item.name
                        };
                    }));
                }
            });
        },
        focus: function(event, ui) {
            $("#item_dyeing_color").val(ui.item.label);
            return false;
        },
        select: function(event, ui) {
            $("#item_dyeing_color").val(ui.item.label);
            selectedColor = ui.item.label;
            return false;
        }
    });

    $("#item_dyeing_color").on("blur change", function() {
        var currentVal = $(this).val();
        if (currentVal !== selectedColor) {
            $(this).val("");
            selectedColor = null;
        }
    });
}
</script>

<script type="text/javascript">
$(document).ready(function() {
    changePurType();
    changeUnit();
    calculateSum();
    toggleActionButtons();
    initVendorAutocomplete();
    initEmployeeAutocomplete();
    initProductAutocomplete();
    initReceivingDatePicker();
    initColorAutocomplete();

    $("#Add_To_Purchase").on("click", addWarehouseItemRow);
});
</script>
</body>
</html>
