<!DOCTYPE html>
<html lang="en">
<head>
@include('frontend.common.head', ['pageTitle' => 'Warehouse Balance Report | Loomexa'])
</head>
<body class="hold-transition sidebar-mini warehouse-stock-report-page warehouse-balance-report-page">
<div id="preloader"><div id="status"></div></div>
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
                <h4><i class="fa fa-balance-scale"></i> Warehouse Balance Report</h4>
                <span>Review opening, inward, outward, and current balance from warehouse balance items.</span>
              </div>
              <a href="{{ route('show-warehouse-stock-report') }}" class="btn btn-add btn-sm"><i class="fa fa-line-chart"></i> Stock Report</a>
            </div>

            <div class="panel-body">
              <div class="stock-filter-panel">
                <div class="panel-body">
                  <form action="{{ route('show-warehouse-balance-report') }}" method="GET" role="search" autocomplete="off">
                    <div class="row balance-filter-grid">
                      <div class="col-sm-2 col-xs-12 balance-filter-field">
                        <div class="form-group">
                          <label for="qsearch">Item</label>
                          <input type="text" class="form-control input-sm" name="qsearch" id="qsearch" value="{{ $qsearch }}" placeholder="Item Name">
                          <input type="hidden" id="itemId" name="itemId" value="{{ $itemId }}">
                        </div>
                      </div>

                      <div class="col-sm-2 col-xs-12 balance-filter-field">
                        <div class="form-group">
                          <label for="item_type">Item Type</label>
                          <select class="form-control input-sm" name="item_type" id="item_type">
                            <option value="">Item Type</option>
                            @foreach ($dataIT as $row)
                              <option value="{{ $row->item_type_id }}" @selected((string) $row->item_type_id === (string) $itemType)>{{ $row->item_type_name }}</option>
                            @endforeach
                          </select>
                        </div>
                      </div>

                      <div class="col-sm-2 col-xs-12 balance-filter-field">
                        <div class="form-group">
                          <label for="balance_status">Balance Mode</label>
                          <select class="form-control input-sm" name="balance_status" id="balance_status">
                            <option value="">All Transactions</option>
                            <option value="current" @selected($balanceStatus === 'current')>Current Balance</option>
                            <option value="history" @selected($balanceStatus === 'history')>Item History</option>
                          </select>
                        </div>
                      </div>

                      <div class="col-sm-2 col-xs-12 balance-filter-field">
                        <div class="form-group">
                          <label for="colorSearch">Color</label>
                          <input type="text" class="form-control input-sm" name="colorSearch" id="colorSearch" value="{{ $colorSearch }}" placeholder="Dyeing Color" autocomplete="off">
                        </div>
                      </div>

                      <div class="col-sm-2 col-xs-12 balance-filter-field">
                        <div class="form-group">
                          <label for="from_date">From Date</label>
                          <input type="text" class="form-control input-sm loomexa-datepicker" data-datepicker-max-date="0" name="from_date" id="from_date" value="{{ $fromDate }}" placeholder="From Date">
                        </div>
                      </div>

                      <div class="col-sm-2 col-xs-12 balance-filter-field">
                        <div class="form-group">
                          <label for="to_date">To Date</label>
                          <input type="text" class="form-control input-sm loomexa-datepicker" data-datepicker-max-date="0" name="to_date" id="to_date" value="{{ $toDate }}" placeholder="To Date">
                        </div>
                      </div>
                    </div>

                    <div class="row stock-filter-actions balance-filter-actions">
                      <div class="col-sm-2 col-xs-6">
                        <div class="form-group">
                          <button type="submit" name="sbtSearch" class="btn btn-success btn-sm btn-block" value="Search"><i class="fa fa-search"></i> Search</button>
                        </div>
                      </div>

                      <div class="col-sm-2 col-xs-6">
                        <div class="form-group">
                          <a href="{{ route('show-warehouse-balance-report') }}" class="btn btn-default btn-sm btn-block"><i class="fa fa-refresh"></i> Reset</a>
                        </div>
                      </div>
                    </div>
                  </form>
                </div>
              </div>

              <div class="row stock-summary-row">
                <div class="col-sm-3 stock-summary-col">
                  <div class="stock-summary-tile">
                    <div class="stock-summary-label">Opening Qty</div>
                    <div class="stock-summary-value">{{ number_format((float) $totalOpeningQty, 2) }}</div>
                  </div>
                </div>
                <div class="col-sm-3 stock-summary-col">
                  <div class="stock-summary-tile available">
                    <div class="stock-summary-label">In Qty</div>
                    <div class="stock-summary-value">{{ number_format((float) $totalInQty, 2) }}</div>
                  </div>
                </div>
                <div class="col-sm-3 stock-summary-col">
                  <div class="stock-summary-tile alloted">
                    <div class="stock-summary-label">Out Qty</div>
                    <div class="stock-summary-value">{{ number_format((float) $totalOutQty, 2) }}</div>
                  </div>
                </div>
                <div class="col-sm-3 stock-summary-col">
                  <div class="stock-summary-tile available">
                    <div class="stock-summary-label">Balance Qty</div>
                    <div class="stock-summary-value">{{ number_format((float) $totalBalanceQty, 2) }}</div>
                  </div>
                </div>
              </div>

              <div class="table-responsive stock-table-wrap">
                <table class="table table-bordered table-striped table-hover stock-report-table">
                  <thead>
                    <tr class="info">
                      <th>Id</th>
                      <th>Date</th>
                      <th>Warehouse</th>
                      <th>Compartment</th>
                      <th>Item</th>
                      <th>Type</th>
                      <th>Particulars</th>
                      <th>Invoice No.</th>
                      <th>Taka No.</th>
                      <th>Lot No.</th>
                      <th>Dyeing</th>
                      <th>Opening</th>
                      <th>In Qty</th>
                      <th>Out Qty</th>
                      <th>Balance</th>
                      <th>Unit</th>
                      <th>Status</th>
                    </tr>
                  </thead>
                  <tbody>
                    @forelse ($dataWBI as $data)
                      @php
                        $balanceQty = (float) ($data->item_qty ?? 0);
                        $receiveDate = !empty($data->receive_date) ? date('d-m-Y', strtotime($data->receive_date)) : '';
                        $statusText = (string) $data->balance_status === '1' ? 'Current' : 'History';
                        $particulars = [];
                        if ((float) $data->in_item_qty > 0) {
                          $particulars[] = 'Stock In';
                        }
                        if ((float) $data->out_item_qty > 0) {
                          $particulars[] = 'Stock Out';
                        }
                        if (empty($particulars)) {
                          $particulars[] = (string) $data->balance_status === '1' ? 'Current Balance' : 'Balance Update';
                        }
                      @endphp
                      <tr>
                        <td>{{ $data->id }}</td>
                        <td>{{ $receiveDate }}</td>
                        <td>{{ $data->Warehouse->warehouse_name ?? '' }}</td>
                        <td>{{ $data->WarehouseCompartment->compartment_name ?? '' }}</td>
                        <td class="item-cell">{{ $data->Item->item_name ?? '' }}</td>
                        <td>{{ $data->ItemType->item_type_name ?? '' }}</td>
                        <td>{{ implode(' / ', $particulars) }}</td>
                        <td>{{ $data->WarehouseItem->invoice_number ?? '' }}</td>
                        <td>{{ $data->WarehouseItem->insp_taka_number ?? '' }}</td>
                        <td>{{ $data->WarehouseItem->dyeing_lot_number ?? '' }}</td>
                        <td>{{ $data->dyeing_color }}</td>
                        <td>{{ number_format((float) $data->op_item_qty, 2) }}</td>
                        <td>{{ number_format((float) $data->in_item_qty, 2) }}</td>
                        <td>{{ number_format((float) $data->out_item_qty, 2) }}</td>
                        <td><span class="stock-pill {{ $balanceQty > 0 ? 'available' : 'zero' }}">{{ number_format($balanceQty, 2) }}</span></td>
                        <td>{{ $data->UnitType->unit_type_name ?? '' }}</td>
                        <td><span class="stock-pill {{ (string) $data->balance_status === '1' ? 'available' : 'zero' }}">{{ $statusText }}</span></td>
                      </tr>
                    @empty
                      <tr>
                        <td colspan="17" class="text-center">No record found.</td>
                      </tr>
                    @endforelse

                    <tr>
                      <td colspan="17" class="stock-pagination">
                        {{ $dataWBI->links('vendor.pagination.bootstrap-4') }}
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

@include('frontend.common.footer')
</div>
@include('frontend.common.footerscript')

<script type="text/javascript">
var siteUrl = "{{ url('/') }}";
</script>

<script type="text/javascript">
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
  return $("<li>").append("<div>" + escapeHtml(item.item_name) + "</div>").appendTo(ul);
};
</script>

<script type="text/javascript">
$("#qsearch").on("input", function() {
  $("#itemId").val("");
});
</script>

<script type="text/javascript">
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
  return $("<li>").append("<div>" + escapeHtml(item.label) + "</div>").appendTo(ul);
};
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
</body>
</html>
