@php
    use App\Http\Controllers\CommonController;
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
    @include('frontend.common.head', ['pageTitle' => 'Create Work Order from Sale Order | Loomexa'])
</head>
<body class="hold-transition sidebar-mini sale-orderitems-page">
<div class="wrapper">
    @include('frontend.common.header')

    <div class="content-wrapper">
        <section class="content">
            <div class="row">
                <div class="col-sm-12">
                    {!! display_message('message') !!}

                    <div class="panel panel-bd lobidrag">
                        <div class="panel-heading">
                            <div class="btn-group" id="buttonexport">
                                <a href="javascript:void(0);">
                                    <h4>Sale Order Work Order Planning</h4>
                                </a>
                            </div>
                        </div>

                        <div class="panel-body">
                            <div class="row sale-order-filter-row">
                                <div class="panel panel-default">
                                    <div class="panel-body">
                                        <form action="{{ route('show-saleorderitems') }}" method="GET" role="search">
                                            <div class="row">
                                                <div class="col-sm-3">
                                                    <input type="text" class="form-control" name="qsearch" id="cus_search" value="{{ $qsearch }}" placeholder="Customer Name">
                                                </div>
                                                <div class="col-sm-3">
                                                    <input type="text" class="form-control" name="qnamesearch" id="item_search" value="{{ $qnamesearch }}" placeholder="Item Name">
                                                </div>
                                                <div class="col-sm-2">
                                                    <input type="text" class="form-control" name="ordNumSearch" id="ordNumSearch" value="{{ $ordNumSearch }}" placeholder="Order Number">
                                                </div>
                                                <div class="col-sm-2">
                                                    <input type="text" class="form-control" name="from_date" id="from_date" placeholder="From Date" value="{{ $fromDate }}">
                                                </div>
                                                <div class="col-sm-2">
                                                    <input type="text" class="form-control" name="to_date" id="to_date" placeholder="To Date" value="{{ $toDate }}">
                                                </div>
                                            </div>

                                            <br>

                                            <div class="row">
                                                <div class="col-sm-3">
                                                    <select class="form-control" name="priority" id="priority">
                                                        <option value="">Priority</option>
                                                        @foreach($priorityArr as $pArr)
                                                            <option value="{{ $pArr }}" @selected($pArr == $priority)>{{ $pArr }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="col-sm-3">
                                                    <input type="text" class="form-control" name="colorSearch" id="colorSearch" value="{{ $colorSearch }}" placeholder="Color">
                                                </div>
                                                <div class="col-sm-2">
                                                    <input type="text" class="form-control" name="create_date" id="create_date" placeholder="Create Date" value="{{ $createDate }}">
                                                </div>
                                                <div class="col-sm-2">
                                                    <button type="submit" name="sbtSearch" value="Search" class="btn btn-primary btn-block">
                                                        <i class="glyphicon glyphicon-search"></i> Search
                                                    </button>
                                                </div>
                                                <div class="col-sm-2">
                                                    <button type="submit" name="sbtSearch" value="ExportToExcel" class="btn btn-success btn-block">
                                                        <i class="glyphicon glyphicon-download-alt"></i> Export to Excel
                                                    </button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <form action="{{ route('store_workorder') }}" method="POST" name="creatworkord" id="creatworkord">
                                    @csrf

                                    <table id="dataTableExample1" class="table table-bordered table-striped table-hover">
                                        <thead>
                                            <tr class="info">
                                                <th>#</th>
                                                <th>Lmxa No.</th>
                                                <th>S.O. Number</th>
                                                <th>Customer</th>
                                                <th>Item Name</th>
                                                <th>Dyeing</th>
                                                <th>Coating</th>
                                                <th>Print & Extra</th>
                                                <th>Req.Mtr</th>
                                                <th>Priority</th>
                                                <th>S.O. Date</th>
                                                <th>Delivery</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($dataSOI as $data)
                                                <?php
                                                    $itemId = $data->item_id;
                                                $saleOrder = $data->saleOrder;
                                                $saleOrderDate = optional($saleOrder)->sale_order_date;
                                                $saleOrderId = $data->sale_order_id;
                                                $saleOrderItemId = $data->id;
                                                $customer = $saleOrder ? $saleOrder->customer : null;
                                                $cusName = $customer->name ?? $customer->company_name ?? '-';
                                                $itemtypeId = $data->item_type_id;
                                                $dyeingColor = $data->dyeing_color;
                                                $coatingPvc = $data->coating_type;
                                                $priority = $data->order_item_priority;

                                                $totGreige = CommonController::checkWarehouseBalanceItemStock($itemId, $itemtypeId);
                                                $totWOGreige = CommonController::getWorkOrderGreigeTypeBalance($itemId, $itemtypeId);
                                                $totDying = CommonController::checkWarehouseBalanceItemStock($itemId, $itemtypeId, $dyeingColor);
                                                $totCoated = CommonController::checkWarehouseBalanceItemStock($itemId, $itemtypeId, $dyeingColor, $coatingPvc);

                                                $getItemInternalName = CommonController::getItemInternalName($data->item_id);
                                                $getTotaCreatedMtr = CommonController::getTotaCreatedMtr($saleOrderItemId);

                                                $totDays = ! empty($data->created_at) ? daysFromNowCount($data->created_at) : 0;
                                                $coatedPvc = strtolower(trim((string) $data->coating_type));
                                                $showCoatingEdit = empty($coatedPvc) || in_array($coatedPvc, ['not', 'no']);
                                                $remainingMeter = ($data->meter - $data->delivered_item_mtr) - ($getTotaCreatedMtr ?? 0);
                                                ?>

                                                <tr id="Mid{{ $data->id }}" @class(['danger' => $priority == 'Extreme'])>
                                                    <td>
                                                        <input
                                                            type="checkbox"
                                                            name="chk_sale_order_item_id[]"
                                                            data-item-name="{{ $data->item_name }}"
                                                            data-sale_order_id="{{ $data->sale_order_id }}"
                                                            data-item-id="{{ $itemId }}"
                                                            data-sale-order-item-id="{{ $saleOrderItemId }}"
                                                            value="{{ $saleOrderItemId }}"
                                                            id="sale_order_item_id{{ $saleOrderItemId }}"
                                                            class="sale-order-item-checkbox"
                                                        >
                                                    </td>
                                                    <td>{{ $saleOrderItemId }}</td>
                                                    <td>{{ optional($saleOrder)->sale_order_number ?? '-' }}</td>
                                                    <td>{{ mb_strimwidth($cusName, 0, 16, '') }}</td>
                                                    <td class="small">
                                                        <strong class="text-primary">{{ $data->item_name }}</strong>
                                                        <hr class="sale-order-item-separator">
                                                        <div class="text-muted">({{ $getItemInternalName }})</div>
                                                        <hr class="sale-order-item-separator">
                                                        <div class="text-success">AVL: <strong>{{ round($totGreige - $totWOGreige) }} Meter</strong></div>
                                                    </td>
                                                    <td>
                                                        <small class="text-primary"><strong>{{ $data->dyeing_color }}</strong></small>
                                                        <hr class="sale-order-item-separator">
                                                        <div class="text-success"><small>AVL: <strong>{{ $totDying }} Meter</strong></small></div>
                                                    </td>
                                                    <td>
                                                        <small class="text-muted coatingComp" data-id="{{ $data->id }}"><strong>{{ $data->coating_type }}</strong></small>
                                                        <input type="text" class="coatingInput form-control input-sm coating-input is-hidden" data-id="{{ $data->id }}" value="{{ $data->coating_type }}">

                                                        @if($showCoatingEdit)
                                                            <button type="button" class="editCoating btn btn-primary btn-xs small-btn" data-id="{{ $data->id }}">
                                                                <i class="fa fa-pencil coating-icon"></i>
                                                            </button>
                                                        @endif

                                                        <button type="button" class="saveCoating btn btn-success btn-xs small-btn is-hidden" data-id="{{ $data->id }}">
                                                            <i class="fa fa-save coating-icon"></i>
                                                        </button>
                                                        <button type="button" class="cancelCoating btn btn-danger btn-xs small-btn is-hidden" data-id="{{ $data->id }}">
                                                            <i class="fa fa-times coating-icon"></i>
                                                        </button>

                                                        <hr class="sale-order-item-separator">
                                                        <div class="text-success"><small>AVL: <strong>{{ $totCoated }} Meter</strong></small></div>
                                                    </td>
                                                    <td>
                                                        @if(!empty($data->print_job))
                                                            <small class="text-muted"><strong>Print:</strong> {{ $data->print_job }}</small>
                                                            <hr class="sale-order-item-separator">
                                                        @endif

                                                        @if(!empty($data->extra_job))
                                                            <small class="text-info"><strong>Extra:</strong> {{ $data->extra_job }}</small>
                                                            <hr class="sale-order-item-separator">
                                                        @endif

                                                        @if($totDays == 2)
                                                            <div>
                                                                <div class="blink small text-primary">
                                                                    Order pending for {{ $totDays }} days. Please initiate the process to avoid escalation.
                                                                </div>
                                                                <button type="button" class="btn btn-info btn-xs reason-modal-btn" data-so-item-id="{{ $saleOrderItemId }}" data-sale-order-id="{{ $saleOrderId }}">Reason</button>
                                                            </div>
                                                        @elseif($totDays > 2)
                                                            <div>
                                                                <div class="blink small text-danger">
                                                                    Order pending for {{ $totDays }} days. Please initiate the work at the earliest.
                                                                </div>
                                                                <button type="button" class="btn btn-info btn-xs reason-modal-btn" data-so-item-id="{{ $saleOrderItemId }}" data-sale-order-id="{{ $saleOrderId }}">Reason</button>
                                                            </div>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        {{ number_format($remainingMeter, 2) }}
                                                        <p id="sale_order_production_value{{ $saleOrderItemId }}" hidden>
                                                            <input type="number" class="form-control" name="sale_order_production_value[]" value="" id="sale_order_production_id{{ $saleOrderItemId }}">
                                                        </p>
                                                        <p id="sale_order_item_rework{{ $saleOrderItemId }}" hidden>
                                                            <label>
                                                                <input type="checkbox" name="sale_order_item_rework[]" value="Yes" id="sale_order_item_id_rework{{ $saleOrderItemId }}"> ReGen. W. O. ?
                                                            </label>
                                                        </p>
                                                    </td>
                                                    <td>{{ $data->order_item_priority }}</td>
                                                    <td>
                                                        <div>{{ !empty($saleOrderDate) ? date('d-m-Y', strtotime($saleOrderDate)) : '-' }}</div>
                                                        <small class="text-info">Added: {{ !empty($data->created_at) ? date('d-m-Y', strtotime($data->created_at)) : '-' }}</small>
                                                    </td>
                                                    <td>{{ !empty($data->expect_delivery_date) ? date('d-m-Y', strtotime($data->expect_delivery_date)) : '-' }}</td>
                                                </tr>
                                            @endforeach

                                            <tr class="center text-center">
                                                <td class="center" colspan="20">
                                                    <input type="hidden" id="WorkSubmitHidden" name="WorkSubmit" value="">
                                                    <button type="button" name="WorkSubmit" value="Weaving" class="btn btn-success work-submit-btn">Create Weaving Work</button>
                                                    <button type="button" name="WorkSubmit" value="Dyeing" class="btn btn-success work-submit-btn">Create Dyeing Work</button>
                                                    <button type="button" name="WorkSubmit" value="Coating" class="btn btn-success work-submit-btn">Create Coating Work</button>
                                                    <button type="button" name="WorkSubmit" value="Packaging" class="btn btn-success work-submit-btn">Send In Packaging</button>
                                                </td>
                                            </tr>
                                            <tr class="center text-center">
                                                <td class="center" colspan="20">
                                                    <div class="pagination">{{ $dataSOI->links('vendor.pagination.bootstrap-4') }}</div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </form>

                                <ul class="list-group">
                                    <li class="list-group-item"><strong>Note:</strong> If greige material is <span class="label label-danger">out of stock</span>, a <strong>Warping Work Order</strong> will be generated.</li>
                                    <li class="list-group-item"><strong>Note:</strong> If greige material is <span class="label label-success">in stock</span>, a <strong>Dyeing Work Order</strong> will be generated.</li>
                                    <li class="list-group-item"><strong>Reminder:</strong> If a work order is not being generated, verify whether the sale order includes any <strong>Dyeing Work</strong>.</li>
                                    <li class="list-group-item"><strong>Note:</strong> If dyed material is <span class="label label-success">in stock</span>, a <strong>Coating Work Order</strong> will be generated.</li>
                                    <li class="list-group-item"><strong>Reminder:</strong> If a work order is not being generated, verify whether the sale order includes any <strong>Coating Work</strong>.</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <div class="modal fade reason-modal" id="reasonModal" tabindex="-1" role="dialog" aria-labelledby="reasonModalLabel" aria-hidden="true">
        <div class="modal-dialog reason-modal-dialog" role="document">
            <div class="modal-content">
                <form method="POST" action="{{ route('SetReasonForSaleOrderItem') }}">
                    @csrf
                    <input type="hidden" name="FId" id="modalFId">
                    <input type="hidden" name="soItemId" id="modalSoItemId">

                    <div class="modal-header reason-modal-header">
                        <button type="button" class="close reason-modal-close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <div class="reason-modal-title-row">
                            <span class="reason-modal-icon"><i class="fa fa-clipboard"></i></span>
                            <div>
                                <h3 class="modal-title" id="reasonModalLabel">Work Order Delay Reason</h3>
                                <p class="reason-modal-subtitle">Add a clear reason for the pending sale order item before proceeding.</p>
                            </div>
                        </div>
                    </div>

                    <div class="modal-body reason-modal-body">
                        <div class="reason-modal-notice">
                            <i class="fa fa-info-circle"></i>
                            <span>This note will be saved in the reason history and included in the director review report.</span>
                        </div>

                        <div class="reason-history-panel">
                            <div class="reason-history-heading">
                                <span>Previous Reasons</span>
                                <small>Latest first</small>
                            </div>
                            <div class="reason-history-body">
                                <div class="table-responsive">
                                    <table class="table table-hover reason-history-table" id="reasonTable">
                                        <thead>
                                            <tr>
                                                <th class="reason-history-sr">#</th>
                                                <th>Reason</th>
                                                <th class="reason-history-date">Date</th>
                                            </tr>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="reason-comment-box">
                            <label for="pending_reason">New Reason</label>
                            <textarea class="form-control" name="pending_reason" id="pending_reason" rows="3" required placeholder="Write a short, specific reason for the delay"></textarea>
                        </div>
                    </div>

                    <div class="modal-footer reason-modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success"><i class="fa fa-save"></i> Save Reason</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @include('frontend.common.footer')
</div>

@include('frontend.common.footerscript')
<script type="text/javascript" src="{{ asset('js/jquery.validate.js') }}"></script>

<script type="text/javascript">
    var siteUrl = "{{ url('/') }}";
    var csrfToken = "{{ csrf_token() }}";

    document.addEventListener('DOMContentLoaded', function () {
        var form = document.getElementById('creatworkord');

        document.querySelectorAll('.sale-order-item-checkbox').forEach(function (checkbox) {
            checkbox.addEventListener('click', function () {
                checkItemStock(this.getAttribute('data-item-id'), this.getAttribute('data-sale-order-item-id'));
            });
        });

        document.querySelectorAll('.work-submit-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var checkedItems = document.querySelectorAll('input[name="chk_sale_order_item_id[]"]:checked');

                if (checkedItems.length === 0) {
                    alert('Please select at least one item.');
                    return;
                }

                document.querySelectorAll('input[name="sale_order_production_value[]"]').forEach(function (input) {
                    if (input.value.trim() === '') {
                        input.remove();
                    }
                });

                var hiddenBtn = document.getElementById('WorkSubmitHidden');
                if (hiddenBtn) {
                    hiddenBtn.value = this.value;
                }

                if (form) {
                    if (typeof form.requestSubmit === 'function') {
                        form.requestSubmit();
                    } else {
                        $(form).trigger('submit');
                    }
                }
            });
        });

        document.querySelectorAll('.reason-modal-btn').forEach(function (button) {
            button.addEventListener('click', function () {
                openReasonModal(this.getAttribute('data-so-item-id'), this.getAttribute('data-sale-order-id'));
            });
        });

        bindCoatingActions();
    });

    function checkItemStock(itemId, saleOrderItemId) {
        var checkbox = document.getElementById('sale_order_item_id' + saleOrderItemId);
        var saleOrderProductionInput = document.getElementById('sale_order_production_value' + saleOrderItemId);
        var saleOrderItemRework = document.getElementById('sale_order_item_rework' + saleOrderItemId);

        if (!checkbox) {
            return false;
        }

        if (checkbox.checked) {
            if (saleOrderProductionInput) {
                saleOrderProductionInput.removeAttribute('hidden');
                saleOrderProductionInput.style.display = 'block';
            }

            if (saleOrderItemRework) {
                saleOrderItemRework.removeAttribute('hidden');
                saleOrderItemRework.style.display = 'block';
            }
        } else {
            if (saleOrderProductionInput) {
                saleOrderProductionInput.setAttribute('hidden', 'hidden');
                saleOrderProductionInput.style.display = 'none';
            }

            if (saleOrderItemRework) {
                saleOrderItemRework.setAttribute('hidden', 'hidden');
                saleOrderItemRework.style.display = 'none';
            }
        }

        let selectedItems = document.querySelectorAll('input[name="chk_sale_order_item_id[]"]:checked');
        var firstItemName = '';
        var isDifferentItems = false;

        selectedItems.forEach(function (item) {
            var currentItemName = (item.getAttribute('data-item-name') || '').trim();

            if (firstItemName === '') {
                firstItemName = currentItemName;
            } else if (firstItemName !== currentItemName) {
                isDifferentItems = true;
            }
        });

        if (isDifferentItems) {
            alert('You are unable to choose different items to initiate any process.');
            checkbox.checked = false;

            if (saleOrderProductionInput) {
                saleOrderProductionInput.setAttribute('hidden', 'hidden');
                saleOrderProductionInput.style.display = 'none';
            }

            if (saleOrderItemRework) {
                saleOrderItemRework.setAttribute('hidden', 'hidden');
                saleOrderItemRework.style.display = 'none';
            }

            selectedItems = document.querySelectorAll('input[name="chk_sale_order_item_id[]"]:checked');
        }

        var selectedNames = new Set();
        selectedItems.forEach(function (item) {
            selectedNames.add(item.getAttribute('data-item-name'));
        });

        var uncheckedAlertShown = false;
        document.querySelectorAll('input[name="chk_sale_order_item_id[]"]:not(:checked)').forEach(function (item) {
            var uncheckedItemName = item.getAttribute('data-item-name');

            if (selectedNames.has(uncheckedItemName) && !uncheckedAlertShown) {
                alert(uncheckedItemName + ' similar item is in the list.');
                uncheckedAlertShown = true;
            }
        });

        var selectedValues = [];
        selectedItems.forEach(function (item) {
            selectedValues.push(item.value);
        });

        if (selectedValues.length === 0) {
            $('button[value="Dyeing"], button[value="Coating"], button[value="Packaging"]').prop('disabled', true);
            return false;
        }

        $.ajax({
            type: 'GET',
            url: siteUrl + '/ajax_script/checkIteminWarehouse',
            dataType: 'json',
            data: {
                _token: csrfToken,
                FId: selectedValues.join(',')
            },
            cache: false,
            success: function (response) {
                $('button[value="Dyeing"]').prop('disabled', String(response.dyeingwrk) === '0');
                $('button[value="Coating"]').prop('disabled', String(response.coatingwrk) === '0');
                $('button[value="Packaging"]').prop('disabled', String(response.packagingwrk) === '0');
            },
            error: function (xhr) {
                console.error('Unable to check item stock:', xhr.responseText || xhr.statusText);
                $('button[value="Dyeing"], button[value="Coating"], button[value="Packaging"]').prop('disabled', true);
            }
        });

        return true;
    }

    function openReasonModal(soItemId, saleOrderId) {
        document.getElementById('modalSoItemId').value = soItemId;
        document.getElementById('modalFId').value = saleOrderId;

        var tableBody = document.querySelector('#reasonTable tbody');
        tableBody.innerHTML = '<tr class="reason-history-loading"><td colspan="3">Loading reason history...</td></tr>';

        fetch(siteUrl + '/get-reason-history/' + soItemId)
            .then(function (response) {
                return response.json();
            })
            .then(function (data) {
                tableBody.innerHTML = '';

                if (data.length > 0) {
                    data.forEach(function (item, index) {
                        var row = document.createElement('tr');
                        var srCell = document.createElement('td');
                        var reasonCell = document.createElement('td');
                        var createdCell = document.createElement('td');

                        srCell.textContent = index + 1;
                        reasonCell.textContent = item.reason || '';
                        createdCell.textContent = item.created || '';

                        row.appendChild(srCell);
                        row.appendChild(reasonCell);
                        row.appendChild(createdCell);
                        tableBody.appendChild(row);
                    });
                } else {
                    tableBody.innerHTML = '<tr class="reason-history-empty"><td colspan="3">No previous reason has been added for this item.</td></tr>';
                }

                $('#reasonModal').modal('show');
            })
            .catch(function (error) {
                console.error(error);
                tableBody.innerHTML = '<tr class="reason-history-error"><td colspan="3">Unable to load reason history. Please try again.</td></tr>';
            });
    }

    function bindCoatingActions() {
        document.querySelectorAll('.editCoating').forEach(function (button) {
            button.addEventListener('click', function (event) {
                event.preventDefault();

                var id = this.getAttribute('data-id');
                var span = document.querySelector(".coatingComp[data-id='" + id + "']");
                var input = document.querySelector(".coatingInput[data-id='" + id + "']");
                var saveButton = document.querySelector(".saveCoating[data-id='" + id + "']");
                var cancelButton = document.querySelector(".cancelCoating[data-id='" + id + "']");

                input.value = span.textContent.trim();
                span.style.display = 'none';
                input.style.display = 'inline-block';
                saveButton.style.display = 'inline-block';
                cancelButton.style.display = 'inline-block';
                this.style.display = 'none';
            });
        });

        document.querySelectorAll('.cancelCoating').forEach(function (button) {
            button.addEventListener('click', function (event) {
                event.preventDefault();

                var id = this.getAttribute('data-id');
                var span = document.querySelector(".coatingComp[data-id='" + id + "']");
                var input = document.querySelector(".coatingInput[data-id='" + id + "']");
                var saveButton = document.querySelector(".saveCoating[data-id='" + id + "']");
                var editButton = document.querySelector(".editCoating[data-id='" + id + "']");

                input.value = span.textContent.trim();
                input.style.display = 'none';
                saveButton.style.display = 'none';
                this.style.display = 'none';
                span.style.display = 'inline-block';

                if (editButton) {
                    editButton.style.display = 'inline-block';
                }
            });
        });

        document.querySelectorAll('.saveCoating').forEach(function (button) {
            button.addEventListener('click', function (event) {
                event.preventDefault();

                var id = this.getAttribute('data-id');
                var input = document.querySelector(".coatingInput[data-id='" + id + "']");
                var span = document.querySelector(".coatingComp[data-id='" + id + "']");
                var editButton = document.querySelector(".editCoating[data-id='" + id + "']");
                var cancelButton = document.querySelector(".cancelCoating[data-id='" + id + "']");
                var selectedValue = input.value.trim();

                updateCoatingRequirement(id, selectedValue, function (success) {
                    if (!success) {
                        return;
                    }

                    span.innerHTML = '<strong>' + selectedValue + '</strong>';
                    span.style.display = 'inline-block';
                    input.style.display = 'none';
                    button.style.display = 'none';
                    cancelButton.style.display = 'none';

                    if (editButton) {
                        editButton.style.display = 'inline-block';
                    }
                });
            });
        });
    }

    function updateCoatingRequirement(id, selectedValue, callback) {
        $.ajax({
            type: 'GET',
            url: siteUrl + '/ajax_script/updateCoatingRequirement',
            data: {
                _token: csrfToken,
                id: id,
                selectedValue: selectedValue
            },
            cache: false,
            success: function (res) {
                if (typeof callback === 'function') {
                    callback(!!res.success);
                }
            },
            error: function (xhr) {
                console.error('Error: ' + xhr.status + ' ' + xhr.statusText);

                if (typeof callback === 'function') {
                    callback(false);
                }
            }
        });
    }

    $(function () {
        $('#from_date, #to_date, #create_date').datepicker({
            dateFormat: 'dd-mm-yy',
            changeMonth: true,
            changeYear: true,
            autoclose: true,
            maxDate: 0
        });

        $('#item_search').autocomplete({
            minLength: 0,
            source: siteUrl + '/fabric_list_item',
            focus: function (event, ui) {
                $('#item_search').val(ui.item.item_name);
                return false;
            },
            select: function (event, ui) {
                $('#item_search').val(ui.item.item_name);
                return false;
            }
        }).autocomplete('instance')._renderItem = function (ul, item) {
            return $('<li>').append('<div>' + item.item_name + ' </div>').appendTo(ul);
        };

        $('#ordNumSearch').autocomplete({
            minLength: 0,
            source: siteUrl + '/find_saleOrderNumer',
            focus: function (event, ui) {
                $('#ordNumSearch').val(ui.item.sale_order_number);
                return false;
            },
            select: function (event, ui) {
                $('#ordNumSearch').val(ui.item.sale_order_number);
                return false;
            }
        }).autocomplete('instance')._renderItem = function (ul, item) {
            return $('<li>').append('<div>' + item.sale_order_number + ' </div>').appendTo(ul);
        };

        $('#cus_search').autocomplete({
            minLength: 0,
            source: siteUrl + '/list_customer',
            focus: function (event, ui) {
                $('#cus_search').val(ui.item.name);
                return false;
            },
            select: function (event, ui) {
                $('#cus_search').val(ui.item.name);
                $('#individual_id').val(ui.item.id);
                return false;
            }
        }).autocomplete('instance')._renderItem = function (ul, item) {
            return $('<li>').append('<div>' + item.name + '</div>').appendTo(ul);
        };

        $('#colorSearch').autocomplete({
            minLength: 0,
            source: function (request, response) {
                $.ajax({
                    url: siteUrl + '/find_saleDyeingColor',
                    dataType: 'json',
                    data: {
                        term: request.term,
                        item_search: $('#item_search').val()
                    },
                    success: function (data) {
                        response(data);
                    }
                });
            },
            focus: function (event, ui) {
                $('#colorSearch').val(ui.item.dyeing_color);
                return false;
            },
            select: function (event, ui) {
                $('#colorSearch').val(ui.item.dyeing_color);
                return false;
            }
        }).autocomplete('instance')._renderItem = function (ul, item) {
            return $('<li>').append('<div>' + item.dyeing_color + ' </div>').appendTo(ul);
        };
    });
</script>
</body>
</html>
