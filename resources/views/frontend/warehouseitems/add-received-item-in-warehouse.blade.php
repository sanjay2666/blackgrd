<!DOCTYPE html>
<html lang="en">
<head>
@include('frontend.common.head', ['pageTitle' => 'Add Received Item In Warehouse | Loomexa'])
<meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body class="hold-transition sidebar-mini warehouse-item-page warehouse-received-item-page">
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
              <h4><i class="fa fa-download"></i> Add Received Item In Warehouse</h4>
              <span>Receive pending purchase order items into warehouse compartments.</span>
            </div>
            <a href="{{ route('add-item-in-warehouse') }}" class="btn btn-add btn-sm"><i class="fa fa-archive"></i> Store Direct Item</a>
          </div>

          <div class="panel-body">
            <form method="get" action="{{ route('add-received-item-in-warehouse') }}" autocomplete="off">
              <div class="panel panel-info wh-detail-panel received-filter-panel">
                <div class="panel-body">
                  <div class="row">
                    <div class="form-group col-md-3">
                      <label for="receiving_date">Receiving Date <span class="text-danger">*</span></label>
                      <input type="text" id="receiving_date" required name="receiving_date" value="{{ $receivingDate }}" class="form-control">
                    </div>
                    <div class="form-group col-md-3">
                      <label for="cus_name">Vendor Name <span class="text-danger">*</span></label>
                      <input type="text" id="cus_name" name="cus_name" class="form-control" value="{{ $vendorName }}" placeholder="Vendor Name" required>
                      <input type="hidden" id="individual_id" name="individual_id" value="{{ $vendorId }}" required>
                    </div>
                    <div class="form-group col-md-2">
                      <label for="invoice_number">Invoice Number <span class="text-danger">*</span></label>
                      <input type="text" id="invoice_number" class="form-control" required name="invoice_number" value="{{ $invoiceNumber }}" oninput="updateChallanNumber(this.value)">
                    </div>
                    <div class="form-group col-md-2">
                      <label for="challan_number">Challan Number</label>
                      <input type="text" id="challan_number" class="form-control" name="challan_number" value="{{ $challanNumber }}">
                    </div>
                    <div class="form-group col-md-2">
                      <label>&nbsp;</label>
                      <button type="submit" class="btn btn-add btn-block"><i class="fa fa-search"></i> Search</button>
                    </div>
                  </div>
                </div>
              </div>
            </form>

            <form id="receivedWarehouseForm" method="post" action="{{ route('storeReceivedItemsFromInvoice') }}" enctype="multipart/form-data" autocomplete="off">
              @csrf
              <input type="hidden" name="receiving_date" value="{{ $receivingDate }}">
              <input type="hidden" name="cus_name" value="{{ $vendorName }}">
              <input type="hidden" name="individual_id" value="{{ $vendorId }}">
              <input type="hidden" name="invoice_number" value="{{ $invoiceNumber }}">
              <input type="hidden" name="challan_number" value="{{ $challanNumber }}">

              <div class="panel panel-info wh-detail-panel">
                <div class="panel-heading"><strong><i class="fa fa-file-text-o"></i> Document Info</strong></div>
                <div class="panel-body">
                  <div class="row">
                    <div class="form-group col-md-3">
                      <label for="invoice_copy_file">Invoice Copy <span class="text-danger">*</span></label>
                      <label class="loomexa-file-upload" for="invoice_copy_file">
                        <input type="file" name="invoice_copy_file" required id="invoice_copy_file">
                        <span class="loomexa-file-icon"><i class="fa fa-file-pdf-o"></i></span>
                        <span class="loomexa-file-main">
                          <span class="loomexa-file-action">Choose file</span>
                          <span class="loomexa-file-name" data-file-label-for="invoice_copy_file">No file selected</span>
                        </span>
                      </label>
                      <small class="text-muted"><span class="glyphicon glyphicon-info-sign"></span> Allowed: PDF, JPG, JPEG, PNG</small>
                    </div>
                    <div class="form-group col-md-3">
                      <label for="packing_slip_file">Packing Copy</label>
                      <label class="loomexa-file-upload" for="packing_slip_file">
                        <input type="file" name="packing_slip_file" id="packing_slip_file">
                        <span class="loomexa-file-icon"><i class="fa fa-file-text-o"></i></span>
                        <span class="loomexa-file-main">
                          <span class="loomexa-file-action">Choose file</span>
                          <span class="loomexa-file-name" data-file-label-for="packing_slip_file">No file selected</span>
                        </span>
                      </label>
                      <small class="text-muted"><span class="glyphicon glyphicon-info-sign"></span> Allowed: PDF, JPG, JPEG, PNG</small>
                    </div>
                    <div class="form-group col-md-3">
                      <label for="eway_bill_file">E-Way Bill</label>
                      <label class="loomexa-file-upload" for="eway_bill_file">
                        <input type="file" name="eway_bill_file" id="eway_bill_file">
                        <span class="loomexa-file-icon"><i class="fa fa-truck"></i></span>
                        <span class="loomexa-file-main">
                          <span class="loomexa-file-action">Choose file</span>
                          <span class="loomexa-file-name" data-file-label-for="eway_bill_file">No file selected</span>
                        </span>
                      </label>
                      <small class="text-muted"><span class="glyphicon glyphicon-info-sign"></span> Allowed: PDF, JPG, JPEG, PNG</small>
                    </div>
                    <div class="form-group col-md-3">
                      <label for="lr_copy_file">LR Copy</label>
                      <label class="loomexa-file-upload" for="lr_copy_file">
                        <input type="file" name="lr_copy_file" id="lr_copy_file">
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

              <div class="table-responsive">
                <table id="receivedItemsTable" class="table table-bordered table-striped table-hover">
                  <thead>
                    <tr class="info">
                      <th class="col-select">#</th>
                      <th class="col-type">Type</th>
                      <th class="col-quality">Quality</th>
                      <th class="col-color">Color</th>
                      <th class="col-po">P.O. No.</th>
                      <th class="col-mrp">MRP</th>
                      <th class="col-ord-mtr">Ord. Mtrs</th>
                      <th class="col-rec-mtr">Rec. Mtrs</th>
                      <th class="col-bal-mtr">Bal. Mtrs</th>
                      <th class="col-pcs">PCS</th>
                      <th class="col-total-rec">Total Rec. Mtrs</th>
                      <th class="col-warehouse">Warehouse</th>
                      <th class="col-compartment">Compartment</th>
                      <th class="col-action">Action</th>
                    </tr>
                  </thead>
                  <tbody>
                    @forelse($dataPO as $index => $rowArr)
                      @php
                        $quantity = (float) ($rowArr->quantity ?? 0);
                        $receivedMtr = (float) ($rowArr->received_quantity ?? 0);
                        $remainingQuantity = max(0, $quantity - $receivedMtr);
                        $defaultPcs = old("pcs.$index", 1);
                        $defaultMeter = old("invoice_mtr.$index", $remainingQuantity > 0 ? $remainingQuantity : '');
                      @endphp
                      <tr>
                        <td>
                          <input type="checkbox" class="purchaseOrderItemCheckbox" name="purchase_order_item_id[]" value="{{ $rowArr->id }}">
                          <input type="hidden" name="purchase_order_item_id_arr[]" value="{{ $rowArr->id }}">
                        </td>
                        <td>{{ optional($rowArr->ItemType)->item_type_name }}</td>
                        <td>{{ optional($rowArr->Item)->item_name ?: $rowArr->name }}</td>
                        <td>{{ $rowArr->colour_name }}</td>
                        <td>{{ optional($rowArr->PurchaseOrder)->purchase_number ?: '#'.$rowArr->purchase_id }}</td>
                        <td>{{ $rowArr->mrp }}</td>
                        <td>{{ number_format($quantity, 2, '.', '') }}</td>
                        <td>{{ number_format($receivedMtr, 2, '.', '') }}</td>
                        <td>{{ number_format($remainingQuantity, 2, '.', '') }}</td>
                        <td><input type="text" min="1" step="1" name="pcs[]" value="{{ $defaultPcs }}" class="form-control input-sm"></td>
                        <td><input type="text" min="0.01" step="0.01" max="{{ $remainingQuantity }}" name="invoice_mtr[]" value="{{ $defaultMeter }}" class="form-control input-sm invoice-mtr"></td>
                        <td>
                          <select class="form-control input-sm warehouse-select" name="warehouseId[]" data-index="{{ $index }}">
                            <option value="">Select Warehouse</option>
                            @foreach($dataW as $val)
                              <option value="{{ $val->id }}">{{ $val->warehouse_name }}</option>
                            @endforeach
                          </select>
                        </td>
                        <td id="warehouseCompIdDiv_{{ $index }}">
                          <select class="form-control input-sm" name="warehouseCompId[]">
                            <option value="">Select Compartment</option>
                          </select>
                        </td>
                        <td>
                          <input type="hidden" name="taka_details[]" class="taka-details-json" value="{{ old("taka_details.$index") }}">
                          <button type="button"
                            class="btn btn-add btn-xs set-taka-btn"
                            data-index="{{ $index }}"
                            data-type="{{ e(optional($rowArr->ItemType)->item_type_name) }}"
                            data-name="{{ e(optional($rowArr->Item)->item_name ?: $rowArr->name) }}"
                            data-color="{{ e($rowArr->colour_name) }}"
                            data-po="{{ e(optional($rowArr->PurchaseOrder)->purchase_number ?: '#'.$rowArr->purchase_id) }}"
                            data-quantity="{{ number_format($remainingQuantity, 2, '.', '') }}"
                            disabled>
                            Set Taka
                          </button>
                        </td>
                      </tr>
                    @empty
                      <tr>
                        <td colspan="14" class="text-center text-muted">Search vendor to load pending purchase order items.</td>
                      </tr>
                    @endforelse
                  </tbody>
                </table>
              </div>

              @if($dataPO->count())
                <button type="submit" class="btn btn-primary" id="saveChanges"><i class="fa fa-save"></i> Store Item In Warehouse</button>
              @endif
            </form>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>

<div class="modal fade" id="setTakaModal" tabindex="-1" role="dialog" aria-labelledby="setTakaModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="setTakaModalLabel">Set Taka</h4>
      </div>
      <div class="modal-body">
        <table class="table table-bordered table-striped table-hover">
          <tbody>
            <tr>
              <th>Type</th>
              <th>Item Name</th>
              <th>Color</th>
              <th>P.O Number</th>
              <th>Bal. Mtrs</th>
              <th>Invoice Mtrs</th>
            </tr>
            <tr>
              <td id="modalType"></td>
              <td id="modalName"></td>
              <td id="modalColor"></td>
              <td id="modalPo"></td>
              <td id="modalQuantity"></td>
              <td><input type="number" step="0.01" min="0.01" id="modalInvoiceMtr" class="form-control input-sm"></td>
            </tr>
          </tbody>
        </table>

        <input type="hidden" id="modalRowIndex" value="">
        <table id="takaRowsTable" class="table table-bordered table-striped table-hover">
          <thead>
            <tr>
              <th>Sr No.</th>
              <th>Meter</th>
              <th>Taka Number</th>
              <th>Remark</th>
              <th></th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
        <button type="button" class="btn btn-primary btn-sm" id="addTakaRowBtn">Add Row</button>
        <span id="takaTotalText" class="text-muted"></span>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-add" id="saveTakaDetailsBtn">Save</button>
      </div>
    </div>
  </div>
</div>

@include('frontend.common.footer')
</div>
@include('frontend.common.footerscript')

<script type="text/javascript">
function updateChallanNumber(value) {
  $("#challan_number").val(value);
}

function validateReceivedWarehouseForm() {
  var checkedRows = $(".purchaseOrderItemCheckbox:checked");
  if (!checkedRows.length) {
    alert("Please select at least one item to store in the warehouse.");
    return false;
  }

  var valid = true;
  checkedRows.each(function() {
    var row = $(this).closest("tr");
    var pcs = row.find('input[name="pcs[]"]');
    var meter = row.find('input[name="invoice_mtr[]"]');
    var warehouse = row.find('select[name="warehouseId[]"]');
    var compartment = row.find('select[name="warehouseCompId[]"]');
    var takaDetails = row.find('input[name="taka_details[]"]').val();

    if (!pcs.val() || parseFloat(pcs.val()) <= 0) {
      alert("PCS value must be a positive number for selected items.");
      pcs.focus();
      valid = false;
      return false;
    }
    if (!meter.val() || parseFloat(meter.val()) <= 0) {
      alert("Received meter must be a positive number for selected items.");
      meter.focus();
      valid = false;
      return false;
    }
    if (!warehouse.val()) {
      alert("Please select warehouse for selected items.");
      warehouse.focus();
      valid = false;
      return false;
    }
    if (!compartment.val()) {
      alert("Please select warehouse compartment for selected items.");
      compartment.focus();
      valid = false;
      return false;
    }
    if (takaDetails) {
      var details = [];
      try {
        details = JSON.parse(takaDetails);
      } catch (error) {
        alert("Invalid Set Taka details found. Please open Set Taka and save again.");
        valid = false;
        return false;
      }
      var total = details.reduce(function(sum, item) {
        return sum + (parseFloat(item.meter) || 0);
      }, 0);
      if (Math.abs(total - parseFloat(meter.val())) > 0.01) {
        alert("Set Taka total meter must match received meter.");
        valid = false;
        return false;
      }
    }
  });

  return valid && confirm("Are you sure you want to submit the form?");
}

$(function() {
  var siteUrl = "{{ url('/') }}";

  function addTakaRow(meter, takaNumber, remarks) {
    var rowNo = $("#takaRowsTable tbody tr").length + 1;
    $("#takaRowsTable tbody").append(
      '<tr>' +
        '<td class="taka-row-no">' + rowNo + '</td>' +
        '<td><input type="number" step="0.01" min="0.01" class="form-control input-sm taka-meter" value="' + (meter || "") + '"></td>' +
        '<td><input type="text" class="form-control input-sm taka-number" value="' + escapeAttr(takaNumber || "") + '"></td>' +
        '<td><input type="text" class="form-control input-sm taka-remark" value="' + escapeAttr(remarks || "") + '"></td>' +
        '<td><button type="button" class="btn btn-danger btn-xs remove-taka-row">&times;</button></td>' +
      '</tr>'
    );
    updateTakaTotal();
  }

  function escapeAttr(value) {
    return String(value)
      .replace(/&/g, "&amp;")
      .replace(/"/g, "&quot;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;");
  }

  function updateTakaRowNumbers() {
    $("#takaRowsTable tbody tr").each(function(index) {
      $(this).find(".taka-row-no").text(index + 1);
    });
  }

  function updateTakaTotal() {
    var total = 0;
    $("#takaRowsTable .taka-meter").each(function() {
      total += parseFloat($(this).val()) || 0;
    });
    $("#takaTotalText").text(" Total Meters: " + total.toFixed(2));
  }

  $("#receiving_date").datepicker({
    dateFormat: "dd-mm-yy",
    changeMonth: true,
    changeYear: true,
    autoclose: true
  });

  $("#cus_name").autocomplete({
    minLength: 1,
    source: siteUrl + "/list_vendor",
    focus: function(event, ui) {
      $("#cus_name").val(ui.item.name);
      event.preventDefault();
    },
    select: function(event, ui) {
      $("#individual_id").val(ui.item.id);
      $("#cus_name").val(ui.item.name);
      return false;
    }
  }).autocomplete("instance")._renderItem = function(ul, item) {
    return $("<li>").append("<div>" + item.name + "<br> GSTIN - " + (item.gstin || "-") + "</div>").appendTo(ul);
  };

  $(document).on("change", ".warehouse-select", function() {
    var warehouseId = $(this).val();
    var index = $(this).data("index");

    $.ajax({
      type: "GET",
      url: siteUrl + "/ajax_script/search_warehouse_compartment_arr",
      data: {
        "_token": "{{ csrf_token() }}",
        "Id": warehouseId,
        "index": index
      },
      cache: false,
      success: function(res) {
        $("#warehouseCompIdDiv_" + index).html(res);
      }
    });
  });

  $(document).on("change", ".purchaseOrderItemCheckbox", function() {
    var row = $(this).closest("tr");
    row.find(".set-taka-btn").prop("disabled", !this.checked);
  });

  $(document).on("click", ".set-taka-btn", function() {
    var button = $(this);
    var row = button.closest("tr");
    var meterInput = row.find('input[name="invoice_mtr[]"]');
    var pcsInput = row.find('input[name="pcs[]"]');
    var invoiceMtr = parseFloat(meterInput.val()) || 0;
    var pcs = parseInt(pcsInput.val(), 10) || 1;

    if (invoiceMtr <= 0) {
      alert("Please enter received meter before Set Taka.");
      meterInput.focus();
      return;
    }

    $("#modalRowIndex").val(button.data("index"));
    $("#modalType").text(button.data("type") || "");
    $("#modalName").text(button.data("name") || "");
    $("#modalColor").text(button.data("color") || "");
    $("#modalPo").text(button.data("po") || "");
    $("#modalQuantity").text(button.data("quantity") || "");
    $("#modalInvoiceMtr").val(invoiceMtr);
    $("#takaRowsTable tbody").empty();

    var existingDetails = row.find('input[name="taka_details[]"]').val();
    if (existingDetails) {
      try {
        JSON.parse(existingDetails).forEach(function(item) {
          addTakaRow(item.meter, item.taka_number, item.remarks);
        });
      } catch (error) {
        addTakaRow(invoiceMtr, "", "");
      }
    } else {
      for (var i = 0; i < pcs; i++) {
        addTakaRow("", "", "");
      }
    }

    $("#setTakaModal").modal("show");
  });

  $("#addTakaRowBtn").on("click", function() {
    addTakaRow("", "", "");
  });

  $(document).on("input", ".taka-meter", updateTakaTotal);

  $(document).on("click", ".remove-taka-row", function() {
    $(this).closest("tr").remove();
    updateTakaRowNumbers();
    updateTakaTotal();
  });

  $("#saveTakaDetailsBtn").on("click", function() {
    var rowIndex = $("#modalRowIndex").val();
    var invoiceMtr = parseFloat($("#modalInvoiceMtr").val()) || 0;
    var details = [];
    var total = 0;

    $("#takaRowsTable tbody tr").each(function() {
      var meter = parseFloat($(this).find(".taka-meter").val()) || 0;
      var takaNumber = $(this).find(".taka-number").val();
      var remarks = $(this).find(".taka-remark").val();
      if (meter > 0) {
        total += meter;
        details.push({
          meter: meter,
          taka_number: takaNumber,
          remarks: remarks
        });
      }
    });

    if (!details.length) {
      alert("Please enter at least one taka meter row.");
      return;
    }
    if (Math.abs(total - invoiceMtr) > 0.01) {
      alert("Total taka meter must match invoice meter.");
      return;
    }

    var targetRow = $('.set-taka-btn[data-index="' + rowIndex + '"]').closest("tr");
    targetRow.find('input[name="invoice_mtr[]"]').val(invoiceMtr);
    targetRow.find('input[name="pcs[]"]').val(details.length);
    targetRow.find('input[name="taka_details[]"]').val(JSON.stringify(details));
    targetRow.find(".set-taka-btn").text("Selected (" + details.length + ")");
    $("#setTakaModal").modal("hide");
  });

  $("#receivedWarehouseForm").on("submit", function(event) {
    if (!validateReceivedWarehouseForm()) {
      event.preventDefault();
    }
  });
});
</script>
</body>
</html>
