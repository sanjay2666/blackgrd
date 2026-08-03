 <!DOCTYPE html>
<html lang="en">
<head>
@include('frontend.common.head', ['pageTitle' => 'Warehouse Stock Report | Loomexa'])
</head>
<body class="hold-transition sidebar-mini warehouse-stock-report-page">
<div id="preloader">
  <div id="status"></div>
</div>
<div class="wrapper">
@include('frontend.common.header')
  <div class="content-wrapper">
    {!! display_message('message') !!}
    <section class="content">
      <div class="row">
        <div class="col-sm-12">
          <div class="panel panel-bd lobidrag stock-report-panel">
            <div class="panel-heading stock-report-heading">
              <div>
                <h4><i class="fa fa-line-chart"></i> Warehouse Stock Report</h4>
                <span>Track available, alloted, and received stock across warehouse compartments.</span>
              </div>
              <a href="{{ route('add-item-in-warehouse') }}" class="btn btn-add btn-sm"><i class="fa fa-plus"></i> Store Item</a>
            </div>
            <div class="panel-body">
              <div class="stock-filter-panel">
                    <div class="panel-body">
                      <form action="{{ route('show-warehouse-stock-report') }}" method="GET" role="search" autocomplete="off">
                        <div class="row">
                          <div class="col-sm-2 col-xs-12">
                            <div class="form-group">
                              <input type="text" class="form-control input-sm" name="qsearch" id="qsearch" value="{{ $qsearch }}" placeholder="Item Name">
                              <input type="hidden" id="itemId" name="itemId" value="{{ $itemId }}">
                            </div>
                          </div>

                          <div class="col-sm-2 col-xs-12">
                            <div class="form-group">
                              <input type="text" class="form-control input-sm" id="vendor_name" name="vendor_name" value="{{ $vendorName }}" placeholder="Vendor Name">
                              <input type="hidden" id="vendor_id" name="vendor_id" value="{{ $vendorId }}">
                            </div>
                          </div>

                          <div class="col-sm-2 col-xs-12">
                            <div class="form-group">
                              <select class="form-control input-sm" name="item_type" id="item_type">
                                <option value="">Item Type</option>
                                @foreach ($dataIT as $row)
                                  <option value="{{ $row->item_type_id }}" @selected((string) $row->item_type_id === (string) $itemType)>{{ $row->item_type_name }}</option>
                                @endforeach
                              </select>
                            </div>
                          </div>

                          <div class="col-sm-2 col-xs-12">
                            <div class="form-group">
                              <select class="form-control input-sm" name="stockType" id="stockType">
                                <option value="">Stock Type</option>
								<option value="allstock" @selected($stockType === 'allstock')>All</option>
                                <option value="stockin" @selected($stockType === 'stockin')>Available</option>
                                <option value="stockout" @selected($stockType === 'stockout')>Dispatched</option>
                                <option value="rejected" @selected($stockType === 'rejected')>Rejected</option>
                                
                              </select>
                            </div>
                          </div>
						  
						  <div class="col-sm-2 col-xs-12">
                            <div class="form-group">
                              <input type="text" class="form-control input-sm" name="allot_from_date" id="allot_from_date" value="{{ $allotFromDate }}" placeholder="Allot From Date">
                            </div>
                          </div>

                          <div class="col-sm-2 col-xs-12">
                            <div class="form-group">
                              <input type="text" class="form-control input-sm" name="allot_to_date" id="allot_to_date" value="{{ $allotToDate }}" placeholder="Allot To Date">
                            </div>
                          </div>
						  

                          
                        </div>

                        <div class="row stock-filter-actions">
                          <div class="col-sm-2 col-xs-12">
                            <div class="form-group">
                              <input type="text" class="form-control input-sm" name="from_date" id="from_date" value="{{ $fromDate }}" placeholder="From Date">
                            </div>
                          </div>

                          <div class="col-sm-2 col-xs-12">
                            <div class="form-group">
                              <input type="text" class="form-control input-sm" name="to_date" id="to_date" value="{{ $toDate }}" placeholder="To Date">
                            </div>
                          </div>

                          
						  <!----
						  <div class="col-sm-1 col-xs-12">
                            <div class="form-group">
                              <select class="form-control input-sm" name="for_stock_type" id="for_stock_type">
                                <option value="">Job/Home</option>
                                <option value="0" @selected((string) $forStockType === '0')>Home</option>
                                <option value="1" @selected((string) $forStockType === '1')>Job</option>
                              </select>
                            </div>
                          </div> --->

                          <div class="col-sm-2 col-xs-12">
                            <div class="form-group">
                              <input type="text" class="form-control input-sm" name="LotNumSearch" id="LotNumSearch" value="{{ $lotNumSearch }}" placeholder="Lot No">
                            </div>
                          </div>

                          <div class="col-sm-2 col-xs-12">
                            <div class="form-group">
                              <input type="text" class="form-control input-sm" name="colorSearch" id="colorSearch" value="{{ $colorSearch }}" placeholder="Color" autocomplete="off">
                            </div>
                          </div>
						  

                          <div class="col-sm-1 col-xs-12">
                            <div class="form-group">
                              <button type="submit" name="sbtSearch" class="btn btn-success btn-sm btn-block" value="Search"><i class="fa fa-search"></i> Search</button>
                            </div>
                          </div>

                          <div class="col-sm-1 col-xs-12">
                            <div class="form-group">
                              <button type="submit" name="sbtSearch" class="btn btn-primary btn-sm btn-block" value="ExportToExcel"><i class="fa fa-download"></i> Export</button>
                            </div>
                          </div>

                          <div class="col-sm-1 col-xs-12">
                            <div class="form-group">
                              <button type="submit" name="sbtSearch" class="btn btn-info btn-sm btn-block" value="ExcelToBarcode" formtarget="_blank"><i class="fa fa-barcode"></i> Barcode</button>
                            </div>
                          </div>

                          <div class="col-sm-1 col-xs-12">
                            <div class="form-group">
                              <a href="{{ route('show-warehouse-stock-report') }}" class="btn btn-default btn-sm btn-block"><i class="fa fa-refresh"></i> Reset</a>
                            </div>
                          </div>
                        </div>
                      </form>
                    </div>
              </div>

              <div class="row stock-summary-row">
                <div class="col-sm-4 stock-summary-col">
                  <div class="stock-summary-tile">
                    <div class="stock-summary-label">Total Stock</div>
                    <div class="stock-summary-value">{{ number_format((float) $totalStock, 2) }}</div>
                  </div>
                </div>
                <div class="col-sm-4 stock-summary-col">
                  <div class="stock-summary-tile alloted">
                    <div class="stock-summary-label">Alloted Stock</div>
                    <div class="stock-summary-value">{{ number_format((float) $inspStockAllot, 2) }}</div>
                  </div>
                </div>
                <div class="col-sm-4 stock-summary-col">
                  <div class="stock-summary-tile available">
                    <div class="stock-summary-label">Available Stock</div>
                    <div class="stock-summary-value">{{ number_format((float) $totalBalStock, 2) }}</div>
                  </div>
                </div>
              </div>

              <div class="table-responsive stock-table-wrap">
                <table class="table table-bordered table-striped table-hover stock-report-table">
                  <thead>
                    <tr class="info">
                      <th>Id</th>
                      <th>Vendor</th>
                      <th>Warehouse</th>
                      <th>Compartment</th>
                      <th>Item</th>
                      <th>Type</th>
                      <th>Invoice No.</th>
                      <th>Taka No.</th>
                      <th>Lot No.</th>
                      <th>Dyeing</th>
                      <th>Quantity</th>
                      <th>Allot Qty</th>
                      <th>Bal Qty</th>
                      <th>Unit</th>
                      <th>Recv. Date</th>
                      <th>Alloted Date</th>
                    </tr>
                  </thead>
                  <tbody>
                    @forelse ($dataWI as $data)
                      @php
                        $unitType = $data->UnitType->unit_type_name ?? $data->quan_size_unit ?? '';
                        $allotedDate = !empty($data->WarehouseOutItem?->created_at) ? date('d-m-Y', strtotime($data->WarehouseOutItem->created_at)) : 'N/A';
                        $receiveDate = !empty($data->receive_date) ? date('d-m-Y', strtotime($data->receive_date)) : '';
                      @endphp
                      <tr id="Mid{{ $data->id }}">
                        <td>{{ $data->id }}</td>
                        <td>{{ $data->Vendor->name ?? '' }}</td>
                        <td>{{ $data->Warehouse->warehouse_name ?? '' }}</td>
                        <td>{{ $data->WarehouseCompartment->compartment_name ?? '' }}</td>
                        <td class="item-cell">{{ $data->Item->item_name ?? '' }}</td>
                        <td>{{ $data->ItemType->item_type_name ?? '' }}</td>
                        <td>
                          @if (!empty($data->invoice_number))
                            <a href="javascript:void(0);" class="show-stock-document stock-doc-link" data-wis-id="{{ enc($data->id) }}" data-invoice-number="{{ $data->invoice_number }}">
                              {{ $data->invoice_number }}
                            </a>
                          @else
                            <span class="muted-cell">N/A</span>
                          @endif
                        </td>
                        <td>{{ $data->insp_taka_number }}</td>
                        <td>{{ $data->dyeing_lot_number }}</td>
                        <td>{{ $data->dyeing_color }}</td>
                        <td>{{ number_format((float) $data->insp_quan_size, 2) }}</td>
                        <td>{{ number_format((float) $data->insp_allot_quan_size, 2) }}</td>
                        <td>
                          @php $balanceQty = (float) ($data->insp_bal_quan_size ?? 0); @endphp
                          <span class="stock-pill {{ $balanceQty > 0 ? 'available' : 'zero' }}">{{ number_format($balanceQty, 2) }}</span>
                        </td>
                        <td>{{ $unitType }}</td>
                        <td>{{ $receiveDate }}</td>
                        <td>
                          <span class="stock-pill {{ $allotedDate === 'N/A' ? 'zero' : 'alloted' }}">{{ $allotedDate }}</span>
                        </td>
                      </tr>
                    @empty
                      <tr>
                        <td colspan="16" class="text-center">No record found.</td>
                      </tr>
                    @endforelse

                    <tr>
                      <td colspan="16" class="stock-pagination">
                        {{ $dataWI->links('vendor.pagination.bootstrap-4') }}
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  </div>

  <div class="modal fade" id="documentModal" tabindex="-1" role="dialog" aria-labelledby="documentModalLabel">
    <div class="modal-dialog modal-lg" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
          <h4 class="modal-title" id="documentModalLabel">Documents</h4>
        </div>
        <div class="modal-body" id="documentModalBody">
          <div class="text-center">Loading...</div>
        </div>
      </div>
    </div>
  </div>

@include('frontend.common.footer')
</div>
@include('frontend.common.footerscript')
<script type="text/javascript" src="{{ asset('js/jquery.validate.js') }}"></script>

<script>
var siteUrl = "{{ url('/') }}";

$("#vendor_name").autocomplete({
  minLength: 0,
  source: siteUrl + "/list_customerandvendor",
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
    .append("<div style=\"padding:5px;\">" + escapeHtml(item.name) + "<br>GSTIN : " + escapeHtml(item.gstin || "N/A") + "<br>TYPE : <strong>" + escapeHtml((item.type || "N/A").toUpperCase()) + "</strong></div>")
    .appendTo(ul);
};

$("#vendor_name").on("input", function() {
  $("#vendor_id").val("");
});

$("#qsearch").autocomplete({
  minLength: 0,
  source: siteUrl + "/fabric_list_item",
  focus: function(event, ui) {
    $("#qsearch").val(ui.item.item_name);
    return false;
  },
  select: function(event, ui) {
    $("#qsearch").val(ui.item.item_name);
    $("#itemId").val(ui.item.item_id);
    return false;
  }
}).autocomplete("instance")._renderItem = function(ul, item) {
  return $("<li>")
    .append("<div>" + escapeHtml(item.item_name) + "</div>")
    .appendTo(ul);
};

$("#qsearch").on("input", function() {
  $("#itemId").val("");
});

$("#colorSearch").autocomplete({
  minLength: 0,
  source: function(request, response) {
    $.ajax({
      url: siteUrl + "/list_master_color",
      data: {
        term: request.term,
        id: ""
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
    $("#colorSearch").val(ui.item.label);
    return false;
  },
  select: function(event, ui) {
    $("#colorSearch").val(ui.item.label);
    return false;
  }
}).autocomplete("instance")._renderItem = function(ul, item) {
  return $("<li>")
    .append("<div>" + escapeHtml(item.label) + "</div>")
    .appendTo(ul);
};

$(function() {
  $("#from_date, #to_date, #allot_from_date, #allot_to_date").datepicker({
    dateFormat: "dd-mm-yy",
    changeMonth: true,
    changeYear: true,
    autoclose: true,
    maxDate: 0
  });
});

$(document).on("click", ".show-stock-document", function() {
  var wisId = $(this).data("wis-id");
  var invoiceNumber = $(this).data("invoice-number");

  $("#documentModalLabel").text("Documents for Invoice No: " + invoiceNumber);
  $("#documentModalBody").html("<div class=\"text-center\"><i class=\"fa fa-spinner fa-spin\"></i> Loading...</div>");
  $("#documentModal").modal("show");

  $.get(siteUrl + "/warehouse-stock-document/" + wisId, function(response) {
    if (response.status === "success") {
      showDocumentFiles(response.data);
    } else {
      $("#documentModalBody").html("<div class=\"alert alert-warning\">" + escapeHtml(response.message) + "</div>");
    }
  }).fail(function() {
    $("#documentModalBody").html("<div class=\"alert alert-danger\">Unable to load documents. Please try again.</div>");
  });
});

function showDocumentFiles(data) {
  var files = [
    { label: "Invoice Copy", url: data.invoice_copy_file },
    { label: "Packing Slip", url: data.packing_slip_file },
    { label: "E-Way Bill", url: data.eway_bill_file },
    { label: "LR Copy", url: data.lr_copy_file }
  ];

  showDocumentFileList(files, "No document uploaded for this stock.");
}

function showDocumentFileList(files, emptyMessage) {
  var html = "<div class=\"row\">";
  var found = false;
  var boxCount = 0;

  $.each(files, function(index, file) {
    if (file.url && file.url !== "") {
      found = true;
      boxCount++;

      var fileExt = file.url.split(".").pop().toLowerCase();
      html += "<div class=\"col-sm-6 col-xs-12\"><div class=\"panel panel-default\"><div class=\"panel-heading text-center\"><strong>" + escapeHtml(file.label) + "</strong></div><div class=\"panel-body text-center\">";

      if ($.inArray(fileExt, ["jpg", "jpeg", "png", "gif", "webp"]) !== -1) {
        html += "<a href=\"" + file.url + "\" target=\"_blank\" class=\"thumbnail\"><img src=\"" + file.url + "\" class=\"img-responsive center-block\" alt=\"" + escapeHtml(file.label) + "\"></a>";
      } else if (fileExt === "pdf") {
        html += "<div class=\"embed-responsive embed-responsive-4by3\"><iframe class=\"embed-responsive-item\" src=\"" + file.url + "\"></iframe></div>";
      } else {
        html += "<div class=\"well well-sm\"><p class=\"text-muted\">Preview not available.</p></div>";
      }

      html += "<a href=\"" + file.url + "\" class=\"btn btn-primary btn-sm\" target=\"_blank\"><i class=\"fa fa-eye\"></i> View / Download</a>";
      html += "</div></div></div>";

      if (boxCount % 2 === 0) {
        html += "<div class=\"clearfix visible-sm-block visible-md-block visible-lg-block\"></div>";
      }
    }
  });

  html += "</div>";

  if (found === false) {
    html = "<div class=\"alert alert-warning\">" + emptyMessage + "</div>";
  }

  $("#documentModalBody").html(html);
}

function escapeHtml(value) {
  return String(value || "")
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#039;");
}
</script>
</body>
</html>
