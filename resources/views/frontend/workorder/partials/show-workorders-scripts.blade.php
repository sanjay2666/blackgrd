<script type="text/javascript" src="{{ asset('js/jquery.validate.js') }}"></script>

<!-- ===== JS: set _print IDs and fetch function for Print modal ===== -->


<script type="text/javascript">
'use strict';

/* ==================== 01. animateCount ==================== */
function animateCount(selector, endValue) {
    $({ countNum: 0 }).animate({ countNum: endValue }, {
        duration: 900,
        easing: 'swing',
        step: function () {
            $(selector).text(Math.floor(this.countNum).toLocaleString());
        },
        complete: function () {
            $(selector).text(parseFloat(endValue).toLocaleString());
        }
    });
}

/* ==================== 02. events ==================== */
$(document).on('click', '#viewTotalsBtn', function () {
    var url = $(this).data('url');

    $('#totalDataWrap').hide();
    $('#totalLoading').show();
    $('#workOrderTotalModal').modal('show');

    $.ajax({
        url: url,
        type: 'GET',
        success: function (res) {
            $('#totalLoading').hide();
            $('#totalDataWrap').show();

            if (res.success) {
                animateCount('#showTotMtr', res.totMtr);
                animateCount('#showTotInspMtr', res.totInspMtr);
                animateCount('#showTotReqMtr', res.totReqMtr);
            } else {
                $('#showTotMtr').text('0');
                $('#showTotInspMtr').text('0');
                $('#showTotReqMtr').text('0');
            }
        },
        error: function () {
            $('#totalLoading').hide();
            $('#totalDataWrap').show();
            $('#showTotMtr').text('Error');
            $('#showTotInspMtr').text('Error');
            $('#showTotReqMtr').text('Error');
        }
    });
});

/* ==================== 03. events ==================== */
document.addEventListener('DOMContentLoaded', function () {

    var goBtn = document.getElementById('goToPageButton');
    if (goBtn) {
        goBtn.addEventListener('click', function () {

            var pageInput = document.getElementById('manualPageInput').value;
            var lastPage = {{ $dataWI->lastPage() }};

            if (pageInput > 0 && pageInput <= lastPage) {
                var params = new URLSearchParams(window.location.search);
                params.set('page', pageInput);
                window.location.href = window.location.pathname + '?' + params.toString();
            }
        });
    }
});

/* ==================== 04. openLabRequestModal, confirmLabRequest ==================== */
// Open Modal with Lot Info
function openLabRequestModal(button)
{
    let id  = $(button).data("id");
    let lot = $(button).data("lot");
    let wo  = $(button).data("wo");

    $("#modalLotId").val(id);
    $("#modalLotNo").text(lot);
    $("#modalWorkOrder").text(wo);

	$('#labRequestModal').modal({
		backdrop: 'static',
		keyboard: false,
		show: true
	});

}

// Confirm Request
function confirmLabRequest() {
    var id      = $("#modalLotId").val();
    var remarks = $("#labRemarks").val();
    var meter   = $("#labMeter").val();

    $.ajax({
        url: "{{ route('lab-request.send') }}",
        type: "GET",   // ✅ should be POST, not GET
        data: {
            _token: "{{ csrf_token() }}",
            id: id,
            remarks: remarks,
            meter: meter
        },
        success: function(res) {
            if (res.success) {
                $("#lotCell" + id).html('<span class="label label-warning">Request Sent</span>');
                $("#labRequestModal").modal("hide");
                alert(res.message);
            }
        },
        error: function(xhr) {
            var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : "Request failed.";
            alert("Error: " + msg);
        }
    });
}

/* ==================== 05. openReasonModal ==================== */
function openReasonModal(woId)
	{
		document.getElementById('modalFId').value = woId;

		const tableBody = document.querySelector('#reasonTable tbody');
		tableBody.innerHTML = '<tr><td colspan="3">Loading...</td></tr>';

		fetch("/get-work-reason-history/" + woId)
		.then(response => response.json())
		.then(data => {
		  tableBody.innerHTML = '';

		  if (data.length > 0) {
			data.forEach(function(item, index) {
			  const row = "<tr>" +
							"<td>" + (index + 1) + "</td>" +
							"<td>" + item.reason + "</td>" +
							"<td>" + item.created + "</td>" +
						  "</tr>";
			  tableBody.innerHTML += row;
			});
		  } else {
			tableBody.innerHTML = '<tr><td colspan="3">No reason history found.</td></tr>';
		  }

		  $('#reasonModal').modal('show');
		})
		.catch(function(error) {
		  console.error(error);
		  tableBody.innerHTML = '<tr><td colspan="3">Failed to load data.</td></tr>';
		});
	}

/* ==================== 06. ReActivateInspProcess ==================== */
let activateInspId = null;
	function ReActivateInspProcess(id) {
	  activateInspId = id; // Store the ID for use after confirmation
	  $('#activateInspModal').modal('show'); // Show the modal
	}

	$('#confirmActivateInspBtn').on('click', function() {
	  var siteUrl = "{{ url('/') }}";

	  jQuery.ajax({
		type: "GET",
		url: siteUrl + '/ajax_script/activateInspWorkOrder',
		data: {
		  "_token": "{{ csrf_token() }}",
		  "FId": activateInspId
		},
		cache: false,
		success: function(response) {
		  // $("#Mid" + activateInspId).hide();
		  alert("Work order Inspection button reactivated successfully.");
		  window.location.reload();
		  $('#activateInspModal').modal('hide'); // Hide the modal
		},
		error: function(xhr, status, error) {
		  alert("An error occurred: " + error);
		  $('#activateInspModal').modal('hide'); // Hide the modal
		}
	  });
	});

/* ==================== 07. DelGatePass ==================== */
let deleteGpId = null;
function DelGatePass(id) {
  deleteGpId = id;
  $('#deleteGpModal').modal('show');
}

$('#confirmDelGpBtn').on('click', function() {
  var siteUrl = "{{ url('/') }}";

  jQuery.ajax({
	type: "GET",
	url: siteUrl + '/ajax_script/deleteGpInspDetails',
	data: {
	  "_token": "{{ csrf_token() }}",
	  "FId": deleteGpId
	},
	cache: false,
	success: function(response) {
	  if (typeof response === 'string') {
		try {
		  response = JSON.parse(response);
		} catch (e) {
		  response = { success: false, message: 'Invalid server response.' };
		}
	  }

	  if (response.success) {
		$("#InsGpid" + deleteGpId).hide();
		alert(response.message || "Gatepass deleted successfully.");
		$('#deleteGpModal').modal('hide');
		deleteGpId = null;
	  } else {
		alert(response.message || "Gatepass could not be deleted.");
	  }
	},
	error: function(xhr, status, error) {
	  var message = "An error occurred: " + error;
	  if (xhr.responseJSON && xhr.responseJSON.message) {
		message = xhr.responseJSON.message;
	  }
	  alert(message);
	  $('#deleteGpModal').modal('hide');
	}
  });
});

/* ==================== 08. disableSubmitButton ==================== */
function disableSubmitButton(form) {
        const submitButton = form.querySelector('button[type="submit"]');
        submitButton.disabled = true;
        submitButton.innerHTML = 'Submitting...'; // Optional: Change button text while submitting
    }

/* ==================== 09. toggleSelectAll, validateReturnForm ==================== */
function toggleSelectAll(selectAllCheckbox)
	{
        // Get all checkboxes in the return items table
        const checkboxes = document.querySelectorAll('#returnItemsTable tbody input[type="checkbox"]');
        checkboxes.forEach(checkbox => {
            // Set the checked property of each checkbox based on the "Select All" checkbox
            if (!checkbox.disabled) { // Only check/uncheck enabled checkboxes
                checkbox.checked = selectAllCheckbox.checked;
            }
        });
    }

	function validateReturnForm(form)
	{
        const checkboxes = document.querySelectorAll('#returnItemsTable tbody input[type="checkbox"]');
        let isChecked = false;

        // Check if any checkbox is selected
        checkboxes.forEach(checkbox => {
            if (checkbox.checked && !checkbox.disabled) {
                isChecked = true;
            }
        });

        if (!isChecked) {
            alert("Please select at least one item to return.");
            return false;  // Prevent form submission
        }

		disableSubmitButton(form);

        return true;  // Allow form submission
    }

/* ==================== 10. GetLotReturnItems ==================== */
function GetLotReturnItems(id, reqLotNo, workOrderId, tableId)
{
    const siteUrl = "{{ url('/') }}";
    const modalLotNumber = document.getElementById('modalLotNumber');
    modalLotNumber.textContent = reqLotNo;
    const ReqLotNumber = document.getElementById('ReqLotNumber');
    ReqLotNumber.value = reqLotNo;
    const modalwprId = document.getElementById('wprId');
    modalwprId.value = id;
    const modalworkOrderId = document.getElementById('chkworkOrderId');
    modalworkOrderId.value = workOrderId;

    jQuery.ajax({
        type: "GET",
        url: "/ajax_script/getLotReturnItems",
        data: {
            "_token": "{{ csrf_token() }}",
            "id": id,
            "req_lot_no": reqLotNo,
            "work_order_id": workOrderId,
        },
        cache: false,
        success: function(response) {
            let returnItems;
            try {
                returnItems = typeof response === 'string' ? JSON.parse(response) : response;
            } catch (e) {
                console.error("Error parsing JSON response:", e);
                return;
            }

            const tableBody = document.querySelector(`#returnItemsTable tbody`);
            tableBody.innerHTML = ''; // Clear previous content

            returnItems.forEach((item, index) => {
				const newRow = document.createElement('tr');

				const isCheckboxDisabled = item.department_return_request && item.department_return_request.id ? 'disabled' : '';

				// Dynamically build the table row with the disabled checkbox condition
				newRow.innerHTML = '<td><input type="hidden" class="form-control" name="ware_out_item_id[]" value="' + item.id + '">' +
					'<input type="text" class="form-control" name="return_wis_id[]" readonly value="' + item.wis_id + '"></td>' +
					'<td><input type="text" class="form-control" name="return_insp_taka_number[]" readonly value="' + item.insp_taka_number + '"></td>' +
					'<td><input type="text" class="form-control" name="return_dyeing_lot_number[]" readonly value="' + item.dyeing_lot_number + '"></td>' +
					'<td><input type="text" class="form-control" name="return_dyeing_taka_number[]" readonly value="' + item.dyeing_taka_number + '"></td>' +
					'<td><input type="text" class="form-control" name="return_item_qty[]" readonly value="' + (item.item_qty || '') + '"></td>' +
					'<td><input type="checkbox" name="is_return[' + index + ']" value="1" ' + isCheckboxDisabled + '>' +
					(isCheckboxDisabled ? '<input type="hidden" name="is_return[' + index + ']" value="0">' : '') + '</td>';

				tableBody.appendChild(newRow);
			});

            $('#returnModal').modal({
                backdrop: 'static',
                keyboard: false
            });
        },
        error: function(xhr, status, error) {
            console.error("AJAX error:", status, error);
        }
    });
}

/* ==================== 11. events ==================== */
var siteUrl = "{{ url('/') }}";
$(document).ready(function() {
    // Open the lot update modal and populate it with data
    $('.open-lot-modal').click(function() {
        var formContent = $(this).data('form-content');

        // Populate the modal fields with data
        $('#newLotNo').val(formContent.req_lot_no);
		 $('#currentLotNo').text(formContent.req_lot_no);
        $('#workOrderId').val(formContent.work_order_id);
        $('#workProId').val(formContent.id);
        $('#saveLotBtn').data('id', formContent.id);
        $('#saveLotBtn').data('work-order-id', formContent.work_order_id);

        // Show the modal
        $('#updateLotModal').modal('show');
    });

    // Save button click event
    $('#saveLotBtn').click(function() {
        var id = $(this).data('id');
        var newLotNo = $('#newLotNo').val();
        var workOrderId = $(this).data('work-order-id');

        // Ajax request to update the req_lot_no
        $.ajax({
            url: siteUrl + '/ajax_script/updateLotNumber',
            type: 'GET',
            data: {
                id: id,
                req_lot_no: newLotNo,
                work_order_id: workOrderId,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                // Update the lot number in the table
                $('#lotCell' + id).text(newLotNo);

                // Close the modal
                $('#updateLotModal').modal('hide');
            },
            error: function(xhr) {
                console.log(xhr.responseText); // Handle errors
            }
        });
    });
});

/* ==================== 12. ShiftWorkOrderToWarping ==================== */
let shiftWoId = null;

	function ShiftWorkOrderToWarping(id) {
	  shiftWoId = id; // Store the ID for use after confirmation
	  $('#shiftWoModal').modal('show'); // Show the modal
	}
	$('#confirmShiftBtn').on('click', function() {
	  if (!shiftWoId) {
		alert("Please select a Work Order first.");
		return;
	  }

	  var $button = $(this);
	  $button.prop('disabled', true);
	  var siteUrl = "{{ url('/') }}";

	  jQuery.ajax({
		type: "GET",
		url: siteUrl + '/ajax_script/shiftWorkOrderToWarping',
		data: {
		  "_token": "{{ csrf_token() }}",
		  "FId": shiftWoId
		},
		cache: false,
		success: function(response) {
		  if (response && response.success) {
			$("#Mid" + shiftWoId).hide();
			alert(response.message || "Work order shifted successfully.");
		  } else {
			alert((response && response.message) || "Failed to shift Work Order.");
		  }
		  $('#shiftWoModal').modal('hide'); // Hide the modal
		},
		error: function(xhr, status, error) {
		  var message = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : error;
		  alert("An error occurred: " + message);
		  $('#shiftWoModal').modal('hide'); // Hide the modal
		},
		complete: function() {
		  $button.prop('disabled', false);
		  shiftWoId = null;
		}
	  });
	});

/* ==================== 13. events ==================== */
$(document).ready(function() {
    $('.btn-success.open-modal').on('click', function() {
      var $button = $(this);
      var formContent = $button.data('form-content'); // Get the form HTML content from data attribute

      $('#receiveStockModal .modal-body').html(formContent); // Update modal content
      $('#receiveStockModal').modal('show'); // Show the modal
    });

    $('#receiveStockModal').on('submit', '.receive-stock-form', function(e) {
      var $form = $(this);
      var $submitBtn = $form.find('button[type="submit"]');

      if ($form.data('submitted') === true) {
        e.preventDefault();
        return false;
      }

      $form.data('submitted', true);
      $submitBtn.prop('disabled', true).addClass('disabled').text('Processing...');
    });
  });

/* ==================== 14. events ==================== */
let fabricFaultOptions = '<option value="">Select Reason</option>';
<?php foreach ($dataF->where('process_id', 3) as $rowF) { ?>
fabricFaultOptions += '<option value="<?= e($rowF->id) ?>"><?= e(addslashes($rowF->reason)) ?></option>';
<?php } ?>

/* ==================== 15. fetchWarehouseItemStock ==================== */
function fetchWarehouseItemStock(lotNumber, workOrderId, tableId)
{
    if (!lotNumber || !workOrderId) {
        $("#dyeing_workRequirement").html('');
        return;
    }

    var siteUrl = "{{ url('/') }}";

    $("#dyeing_workRequirement").html('');

    jQuery.ajax({
        type: "GET",
        url: siteUrl + "/ajax_script/getWarehouseItemStock",
        data: {
            "_token": "{{ csrf_token() }}",
            "lot_number": lotNumber,
            "dyeing_ins_work_order_id": workOrderId
        },
        cache: false,

        success: function(response)
        {
            console.log("Raw server response (success):", response);

            var payload = null;

            try {
                payload = (typeof response === 'string') ? JSON.parse(response) : response;
            } catch (e) {
                console.error("Failed to parse response JSON:", e);
                payload = response;
            }

            $("#dyeing_workRequirement").html('');

            var $tableBody = $('#' + tableId + ' tbody');
            $tableBody.html('');

            if (payload && payload.show_planning_popup)
            {
                $("#planningWarningMessage").html(payload.planning_warning_message);

				$("#planningLotNumber").text(payload.lot_number);
                $("#planningWarningModal").modal({
                    backdrop: 'static',
                    keyboard: false,
                    show: true
                });

                setTimeout(function() {
                    $("#planningWarningModal").css("z-index", 1060);
                    $(".modal-backdrop").last().css("z-index", 1055);
                }, 300);
            }

			$('#planningWarningModal').on('hidden.bs.modal', function () {
				if ($('.modal.in').length > 0) {
					$('body').addClass('modal-open');
				}
			});

            if (payload && (payload.message || payload.message_hi) && !payload.show_planning_popup)
            {
                $("#dyeing_workRequirement").html('<div class="alert alert-info small">' + (payload.message_hi || payload.message) + '</div>');

                var nameFallback = payload.machineName || payload.MachineName || null;
                var idFallback   = payload.machineId || payload.MachineId || payload.machine_id || null;

                $("#MachineNameD").text(nameFallback ? nameFallback : 'Not allocated');
                $("#machineIdD").val(idFallback ? idFallback : '');

                return;
            }

            var stockItems = [];

            if (Array.isArray(payload))
            {
                stockItems = payload;
            }
            else if (payload && Array.isArray(payload.stockItems))
            {
                stockItems = payload.stockItems;
            }
            else if (payload && payload.data && Array.isArray(payload.data))
            {
                stockItems = payload.data;
            }

            var rowNumber = 1;

            stockItems.forEach(function(stockItem)
            {
                var qty = Number(stockItem.item_qty) || 0;
                var maxVal = Math.ceil(qty * 2.10);

                var newRow = document.createElement('tr');
                newRow.classList.add('table-row');

                newRow.innerHTML =
                    '<td><input type="text" class="form-control" name="dyeing_taka_number[]" readonly value="' + rowNumber + '"></td>' +
                    '<td><input type="text" class="form-control" name="insp_taka_number[]" readonly value="' + (stockItem.insp_taka_number || '') + '"></td>' +
                    '<td><input type="text" class="form-control greige_item_qty" name="greige_item_qty[]" readonly value="' + qty + '"></td>' +
                    '<td><input type="text" class="form-control output_quan_break_size" name="output_quan_break_size[]" value="" oninput="calculateOutputSize(this)"></td>' +
                    '<td><input type="number" min="0" step="0.01" max="' + maxVal + '" class="form-control output_quan_size" name="output_quan_size[]" value="0"></td>' +
                    '<td><input type="number" min="0" step="0.01" class="form-control reject_quan_size" name="reject_quan_size[]" value="0"></td>' +
                    '<td><select name="fabric_fault_id[]" class="form-control">' + (typeof fabricFaultOptions !== 'undefined' ? fabricFaultOptions : '') + '</select></td>' +
                    '<td><input type="number" step="0.01" min="0" class="form-control shrinkage_quan_size" name="shrinkage_quan_size[]" value="0"></td>';

                $tableBody.append(newRow);
                rowNumber++;
            });

            if (typeof updateTotalOutput === 'function') {
                updateTotalOutput();
            }

            if (typeof updateTotalGreigeItemQty === 'function') {
                updateTotalGreigeItemQty();
            }

            if (typeof updateTotalReject === 'function') {
                updateTotalReject();
            }

            var machineName = payload && (payload.machineName || payload.MachineName) ? (payload.machineName || payload.MachineName) : null;
            var machineId   = payload && (payload.machineId || payload.MachineId || payload.machine_id) ? (payload.machineId || payload.MachineId || payload.machine_id) : '';

            $("#MachineNameD").text(machineName ? machineName : 'Not allocated');
            $("#machineIdD").val(machineId);

            var reqProIds = payload && payload.reqProIds ? payload.reqProIds : '';
            $("#reqProIdsDieing").val(reqProIds);
        },

        error: function(xhr, status, error)
        {
            console.error("AJAX error:", status, error);
            console.error("Server response:", xhr.responseText);

            var res = null;

            try {
                res = xhr.responseJSON || JSON.parse(xhr.responseText);
            } catch (e) {
                res = null;
            }

            var errorMessage = "Unable to fetch warehouse stock. Try again.";

            if (res && (res.message || res.message_hi)) {
                errorMessage = res.message_hi || res.message;
            }

            $("#dyeing_workRequirement").html('<div class="alert alert-danger small">' + errorMessage + '</div>');

            $('#' + tableId + ' tbody').html('');

            $("#MachineNameD").text('Not allocated');
            $("#machineIdD").val('');
            $("#reqProIdsDieing").val('');

            if (typeof updateTotalOutput === 'function') {
                updateTotalOutput();
            }

            if (typeof updateTotalGreigeItemQty === 'function') {
                updateTotalGreigeItemQty();
            }

            if (typeof updateTotalReject === 'function') {
                updateTotalReject();
            }
        }
    });
}

/* ==================== 16. fetchWarehouseItemStockCoating ==================== */
function fetchWarehouseItemStockCoating(lotNumber, workOrderId, tableId)
{
    if (!lotNumber || !workOrderId) {
        $("#coating_workRequirement").html('');
        return;
    }

    var siteUrl = "{{ url('/') }}";

    // New request start hote hi old message clear kar do
    $("#coating_workRequirement").html('');

    jQuery.ajax({
        type: "GET",
        url: siteUrl + "/ajax_script/getWarehouseItemStock",
        data: {
            "_token": "{{ csrf_token() }}",
            "lot_number": lotNumber,
            "dyeing_ins_work_order_id": workOrderId
        },
        cache: false,

        success: function(response)
        {
            console.log("Raw server response (success):", response);

            var payload = null;

            try {
                payload = (typeof response === 'string') ? JSON.parse(response) : response;
            } catch (e) {
                console.error("Failed to parse response JSON:", e);
                payload = response;
            }

            // Success aaya matlab purana error/message clear
            $("#coating_workRequirement").html('');

            var $tableBody = $('#' + tableId + ' tbody');
            $tableBody.html('');

            // Agar backend success response mein message bhej raha hai
            if (payload && (payload.message || payload.message_hi))
            {
                $("#coating_workRequirement").html('<div class="alert alert-info small">' + (payload.message_hi || payload.message) + '</div>');

                var nameFallback = payload.machineName || payload.MachineName || null;
                var idFallback   = payload.machineId || payload.MachineId || payload.machine_id || '';

                $("#MachineNameC").text(nameFallback ? nameFallback : 'Not allocated');
                $("#machineIdC").val(idFallback);
                $("#reqProIdsC").val('');

                return;
            }

            var stockItems = [];

            if (Array.isArray(payload))
            {
                stockItems = payload;
            }
            else if (payload && Array.isArray(payload.stockItems))
            {
                stockItems = payload.stockItems;
            }
            else if (payload && payload.data && Array.isArray(payload.data))
            {
                stockItems = payload.data;
            }

            var rowNumber = 1;

            stockItems.forEach(function(stockItem)
            {
                var qty = Number(stockItem.item_qty) || 0;
                var maxVal = Math.ceil(qty * 3.30);

                var newRow = document.createElement('tr');
                newRow.classList.add('table-row');

                newRow.innerHTML =
                    '<td><input type="text" class="form-control" name="dyeing_taka_number[]" readonly value="' + rowNumber + '"></td>' +
                    '<td><input type="text" class="form-control" name="insp_taka_number[]" readonly value="' + (stockItem.insp_taka_number || '') + '"></td>' +
                    '<td><input type="text" class="form-control greige_item_qty" name="greige_item_qty[]" readonly value="' + qty + '"></td>' +
                    '<td><input type="text" class="form-control output_quan_break_size" name="output_quan_break_size[]" value="" oninput="calculateOutputSize(this)"></td>' +
                    '<td><input type="number" min="0" step="0.01" max="' + maxVal + '" class="form-control output_quan_size" name="output_quan_size[]" value="0"></td>' +
                    '<td><input type="number" min="0" step="0.01" class="form-control shrinkage_quan_size" name="shrinkage_quan_size[]" value="0"></td>';

                $tableBody.append(newRow);
                rowNumber++;
            });

            if (typeof updateTotalOutput === 'function') {
                updateTotalOutput();
            }

            if (typeof updateTotalGreigeItemQty === 'function') {
                updateTotalGreigeItemQty();
            }

            var machineName = payload && (payload.machineName || payload.MachineName) ? (payload.machineName || payload.MachineName) : null;
            var machineId   = payload && (payload.machineId || payload.MachineId || payload.machine_id) ? (payload.machineId || payload.MachineId || payload.machine_id) : '';

            $("#MachineNameC").text(machineName ? machineName : 'Not allocated');
            $("#machineIdC").val(machineId);

            var reqProIds = payload && payload.reqProIds ? payload.reqProIds : '';
            $("#reqProIdsC").val(reqProIds);
        },

        error: function(xhr, status, error)
        {
            console.error("AJAX error:", status, error);
            console.error("Server response:", xhr.responseText);

            var res = null;

            try {
                res = xhr.responseJSON || JSON.parse(xhr.responseText);
            } catch (e) {
                res = null;
            }

            var errorMessage = "Unable to fetch warehouse stock. Try again.";

            if (res && (res.message || res.message_hi)) {
                errorMessage = res.message_hi || res.message;
            }

            $("#coating_workRequirement").html('<div class="alert alert-danger small">' + errorMessage + '</div>');

            $('#' + tableId + ' tbody').html('');

            $("#MachineNameC").text('Not allocated');
            $("#machineIdC").val('');
            $("#reqProIdsC").val('');

            if (typeof updateTotalOutput === 'function') {
                updateTotalOutput();
            }

            if (typeof updateTotalGreigeItemQty === 'function') {
                updateTotalGreigeItemQty();
            }
        }
    });
}

/* ==================== 17. calculateOutputSize ==================== */
function calculateOutputSize(element) {
    const breakSizeInput = element.value.trim();
    const sum = breakSizeInput.split('+').reduce((acc, val) => acc + (parseFloat(val.trim()) || 0), 0);
    const outputSizeInput = element.parentElement.nextElementSibling.querySelector('.output_quan_size');
    if (outputSizeInput) {
        outputSizeInput.value = sum.toFixed(2);
    }
    updateTotalOutput();
}

/* ==================== 18. updateTotalOutput ==================== */
function updateTotalOutput() {
    const outputFields = document.querySelectorAll('.output_quan_size');
    let total = 0;
    outputFields.forEach(field => {
        const value = parseFloat(field.value) || 0;
        total += value;
    });
    document.getElementById('totalOutput').textContent = total.toFixed(2);
    document.getElementById('totalOutputt').textContent = total.toFixed(2);
}

/* ==================== 19. updateTotalReject ==================== */
function updateTotalReject()
{
    const rejectFields = document.querySelectorAll('.reject_quan_size');
    let total = 0;
    rejectFields.forEach(field => {
        const value = parseFloat(field.value) || 0;
        total += value;
    });
	console.log("Total Reject:", total); // अभी test के लिए
	document.getElementById('totalRejectOutputt').textContent = total.toFixed(2);
    // Update any matching total elements if they exist (mirrors pattern used for outputs/greige)
    const el1 = document.getElementById('totalReject');
    const el2 = document.getElementById('totalRejectt');
    if (el1) el1.textContent = total.toFixed(2);
    if (el2) el2.textContent = total.toFixed(2);
}

/* ==================== 20. updateTotalGreigeItemQty ==================== */
function updateTotalGreigeItemQty() {
    const greigeItemQtyFields = document.querySelectorAll('.greige_item_qty');
    let total = 0;
    greigeItemQtyFields.forEach(field => {
        const value = parseFloat(field.value) || 0;
        total += value;
    });
    document.getElementById('toGreigeItemQty').textContent = total.toFixed(2);
    document.getElementById('toGreigeItemQtyy').textContent = total.toFixed(2);
}

/* ==================== 21. events ==================== */
document.addEventListener('input', function(event) {
    if (event.target.classList.contains('output_quan_break_size')) {
        calculateOutputSize(event.target);
    } else if (event.target.classList.contains('output_quan_size')) {
        updateTotalOutput();
    } else if (event.target.classList.contains('greige_item_qty')) {
        updateTotalGreigeItemQty();
    }
    else if (event.target.classList.contains('reject_quan_size')) { // ADDED: new line
        updateTotalReject(); // ADDED: call reject total function
    }
});

/* ==================== 22. DelWoProcess ==================== */
let deleteId = null;
function DelWoProcess(id) {
  deleteId = id; // Store the ID for use after confirmation
  $('#deleteModal').modal('show'); // Show the modal
}

$('#confirmDelBtn').on('click', function() {
  var siteUrl = "{{ url('/') }}";

  jQuery.ajax({
	type: "GET",
	url: siteUrl + '/ajax_script/deleteWorkOrder',
	data: {
	  "_token": "{{ csrf_token() }}",
	  "FId": deleteId
	},
	cache: false,
	success: function(response) {
	  $("#Mid" + deleteId).hide();
	  alert("Work order deleted successfully.");
	  $('#deleteModal').modal('hide'); // Hide the modal
	},
	error: function(xhr, status, error) {
	  alert("An error occurred: " + error);
	  $('#deleteModal').modal('hide'); // Hide the modal
	}
  });
});

/* ==================== 23. ReActivateProcess ==================== */
let activateId = null;

	function ReActivateProcess(id) {
	  activateId = id; // Store the ID for use after confirmation
	  $('#activateModal').modal('show'); // Show the modal
	}

	$('#confirmActivateBtn').on('click', function() {
	  var siteUrl = "{{ url('/') }}";

	  jQuery.ajax({
		type: "GET",
		url: siteUrl + '/ajax_script/activateWorkOrder',
		data: {
		  "_token": "{{ csrf_token() }}",
		  "FId": activateId
		},
		cache: false,
		success: function(response) {
		  $("#Mid" + activateId).hide();
		  alert("Work order reactivated successfully.");
		  $('#activateModal').modal('hide'); // Hide the modal
		},
		error: function(xhr, status, error) {
		  alert("An error occurred: " + error);
		  $('#activateModal').modal('hide'); // Hide the modal
		}
	  });
	});

/* ==================== 24. updateDyeingProcess ==================== */
function updateDyeingProcess()
{
	var workStatusSelect = document.getElementById("weaving_insp_work_status_process");
	var dyeingProcessSelect = document.getElementById("weaving_insp_dyeing_process");

	// If insp_work_status_process is selected as "Yes"
	if (workStatusSelect.value === "Yes") {
		dyeingProcessSelect.value = "Yes";
	} else {
		dyeingProcessSelect.value = "No"; // Set a default value if not "Yes"
	}
}

/* ==================== 25. updateCoatingProcess ==================== */
function updateCoatingProcess() {
        var workStatusSelect = document.getElementById("dyeing_insp_work_status_process");
        var dyeingProcessSelect = document.getElementById("dyeing_insp_coating_process");

        // If insp_work_status_process is selected as "Yes"
        if (workStatusSelect.value === "Yes") {
            dyeingProcessSelect.value = "Yes";
        } else {
            dyeingProcessSelect.value = "No"; // Set a default value if not "Yes"
        }
    }

/* ==================== 26. selectWorkStatus, gatePass ==================== */
function selectWorkStatus(element) {
    var value = (typeof element === 'string') ? element : $(element).val();
    var $scope = (typeof element === 'string') ? $(document) : $(element).closest('form');
    var $rows = $scope.find('.js-work-status-process, .js-work-status-reason, #WorkStatusProcess, #WorkStatusProcessReason');

    if (value === 'Defective') {
        $rows.show();
    } else {
        $rows.hide();
    }
}

function gatePass(value) {
        if (value === 'stock') {
            var siteUrl = "{{url('/')}}";
            var Id = Base64.encode($("#ins_work_order_id").val());
            var pageUrl = siteUrl + '/' + "print-workorder-gatepass" + '/' + Id;
            $("#ItemGatePass").html('<div class="i-check"> <a target="_blank" href=' + pageUrl + ' class="btn btn-success btn-xs">Gatepass</a></div>').show();
        } else if (value === 'reprocess') {
            $("#ItemGatePass").hide();
        }
    }

/* ==================== 27. events ==================== */
$(function() {
  $("#from_date, #to_date").datepicker({
	dateFormat: "dd-mm-yy",
	changeMonth: true,
	changeYear: true,
	autoclose: true,
  });
});

/* ==================== 28. split, extractLast ==================== */
var siteUrl = "{{url('/')}}";

  function split(val) {
    return val.split(/,\s*/);
  }

  function extractLast(term) {
    return split(term).pop();
  }

  $("#cus_search")
    .on("keydown", function(event) {
      if (event.keyCode === $.ui.keyCode.TAB && $(this).autocomplete("instance").menu.active) {
        event.preventDefault();
      }
    })
    .autocomplete({
      minLength: 3,
      source: function(request, response) {
        $.getJSON(siteUrl + "/list_customer", {
          term: extractLast(request.term)
        }, response);
      },
      focus: function() {
        return false;
      },
      select: function(event, ui) {
        var terms = split(this.value);
        var ids = split($("#individual_id").val());

        // remove current input
        terms.pop();
        ids.pop();

        // add the selected item
        terms.push(ui.item.name);
        ids.push(ui.item.id);

        // add placeholder to get the comma-and-space at the end
        terms.push("");
        ids.push("");

        this.value = terms.join(", ");
        $("#individual_id").val(ids.join(","));
        return false;
      }
    }).autocomplete("instance")._renderItem = function(ul, item) {
      return $("<li>")
        .append("<div>" + item.name + "</div>")
        .appendTo(ul);
    };

/* ==================== 29. events ==================== */
$("#item_search").autocomplete({
        minLength: 0,
        source: siteUrl + '/' + "fabric_list_item",
        focus: function(event, ui) {
          if (ui.item.part_number != '') {
            $("#item_search").val(ui.item.item_name);
            //$( "#product_name" ).val( ui.item.item_name + ' ' + ui.item.item_code );
          } else {
            $("#product_name").val(ui.item.item_name);
          }
          return false;
        },
        select: function(event, ui) {
          if (ui.item.part_number != '') {
            $("#product_name").val(ui.item.item_name);
            //$( "#product_name" ).val( ui.item.item_name + ' ' + ui.item.item_code);
          } else {
            $("#product_name").val(ui.item.item_name);
          }
          return false;
        }
      })
      .autocomplete("instance")._renderItem = function(ul, item) {
        return $("<li>")
          //.append( "<div>" + item.item_name + " </div>" )
          .append("<div>" + item.item_name + " </div>")
          .appendTo(ul);
      };
      //console.log($("#ordNumSearch").val());
      $("#ordNumSearch").autocomplete({
        minLength: 0,
        source: siteUrl + '/' + "find_saleOrderNumer",
        focus: function(event, ui) {
          //var ordNumSearch=$("#ordNumSearch").val();
          $( "#ordNumSearch" ).val( ui.item.sale_order_number);
		      return false;
        },
        select: function(event, ui) {
          $("#ordNumSearch").val(ui.item.sale_order_number);
          //$("#qsaleOrderId").val(ui.item.sale_order_id);
          return false;
        }
      })
      .autocomplete("instance")._renderItem = function(ul, item) {
        return $("<li>")
          //.append( "<div>" + item.item_name + " </div>" )
          .append("<div>" + item.sale_order_number + " </div>")
          .appendTo(ul);
      };

/* ==================== 30. CoatingInspProcess ==================== */
var siteUrl = "{{url('/')}}";

function CoatingInspProcess(Id) {
  jQuery.ajax({
	type: "GET",
	url: siteUrl + '/' + "ajax_script/getWorkOrderDetails",
	data: {
	  "_token": "{{ csrf_token() }}",
	  "FId": Id,
	},
	cache: false,
	success: function(response) {
	  response = JSON.parse(response);
	  console.log(response);

	  //alert(response.workRequirement);
	  $("#coating_ins_item_id").val(response.itemId);
	  $("#coating_ins_work_order_id").val(response.workOrdId);
	  $("#coating_ItemName").html(response.ItemName);
	  $("#coating_InsoutputNext").html(response.outputNextPro);
	  $("#coating_InsoutputUnit").html(response.outputUnit);
	  $("#coating_processtext").html(response.processtext);
	  $("#coating_outputUnitType").html(response.outputUnitType);
	  $("#coating_workRequirement1").html(response.workRequirement);
	  $("#coating_insp_work_warehouseId").html(response.warehouses);
	  // $("#MachineNameC").html(response.MachineName);
	  $("#inspTakaNumberC").html(response.inspTakaNumber);

	}
  });

  $('#CoatingInspProcessPop').modal({
	backdrop: 'static',
	keyboard: false
  });
}

/* ==================== 31. DyeingInspProcess ==================== */
var siteUrl = "{{url('/')}}";

    function DyeingInspProcess(Id) {
      var $dyeingForm = $("#dyeingInspectionForm");
      if ($dyeingForm.length) {
        $dyeingForm[0].reset();
        $dyeingForm.data('submitted', false);
        $dyeingForm.find('button[type="submit"]').prop('disabled', false).removeClass('disabled').html('Update Inspection Process');
      }
      $("#dyeing_workRequirement").html('');
      $("#myTableDyed tbody").html('<tr class="table-row"></tr>');
      $("#MachineNameD").text('Not allocated');
      $("#machineIdD").val('');
      $("#reqProIdsDieing").val('');
      $("#toGreigeItemQtyy").text('0');
      $("#totalOutputt").text('0');
      $("#totalRejectOutputt").text('0');

      jQuery.ajax({
        type: "GET",
        url: siteUrl + '/' + "ajax_script/getWorkOrderDetails",
        data: {
          "_token": "{{ csrf_token() }}",
          "FId": Id,
        },
        cache: false,
        success: function(response) {
          response = JSON.parse(response);
          console.log(response);

          //alert(response.workRequirement);
          $("#dyeing_ins_item_id").val(response.itemId);
          $("#dyeing_ins_work_order_id").val(response.workOrdId);
          $("#dyeing_ItemName").html(response.ItemName);
          $("#dyeing_InsoutputNext").html(response.outputNextPro);
          $("#dyeing_InsoutputUnit").html(response.outputUnit);
          $("#dyeing_processtext").html(response.processtext);
          $("#dyeing_outputUnitType").html(response.outputUnitType);
          $("#dyeing_workRequirement1").html(response.workRequirement);
          $("#dyeing_insp_work_warehouseId").html(response.warehouses);
          $("#dyeing_insp_work_warehouseId option").filter(function () {
            return $.trim($(this).text()).toLowerCase() == 'dyed warehouse';
          }).prop('selected', true);
		  // $("#MachineNameD").html(response.MachineName);
		  // $("#machineIdD").val(response.machineId);
          $("#inspTakaNumberD").html(response.inspTakaNumber);

        }
      });

      $('#DyeingInspProcessPop').modal({
        backdrop: 'static',
        keyboard: false
      });
    }

/* ==================== 32. WeavingInspProcess ==================== */
var siteUrl = "{{url('/')}}";

    function WeavingInspProcess(Id)
	{
      $("#weavingInspectionForm").attr('action', "{{ url('/update_weaving_inspec_process') }}");

      jQuery.ajax({
        type: "GET",
        url: siteUrl + '/' + "ajax_script/getWorkOrderDetails",
        data: {
          "_token": "{{ csrf_token() }}",
          "FId": Id,
        },
        cache: false,
        success: function(response)
		{
			response = JSON.parse(response);
			console.log(response);

			//alert(response.workRequirement);
			$("#weav_ins_item_id").val(response.itemId);
			$("#weav_ins_work_order_id").val(response.workOrdId);
			$("#weav_ItemName").html(response.ItemName);
			$("#weav_InsoutputNext").html(response.outputNextPro);
			$("#weav_InsoutputUnit").html(response.outputUnit);
			$("#weav_processtext").html(response.processtext);
			$("#weav_outputUnitType").html(response.outputUnitType);
			$("#weav_workRequirement").html(response.workRequirement);
			$("#insp_work_warehouseId").html(response.warehouses);
			$("#insp_work_warehouseId option").filter(function () {
				return $.trim($(this).text()).toLowerCase() == 'greige warehouse';
			}).prop('selected', true);
			$("#MachineName").html(response.MachineName || 'Not allocated');
			$("#MasterName").html(response.MasterName || response.masterName || 'Not allocated');
			$("#weav_machineId").val(response.machineId || response.MachineId || response.machine_id || '');
			$("#inspTakaNumber").html(response.inspTakaNumber || '');
			$("#insp_epi").val(response.inspEpi);
			$("#insp_ppi").val(response.inspPpi);
			$("#insp_width_weav").val(response.inspWidth);
			$("#insp_gsm_weav").val(response.inspGsm);

        }
      });

      $('#WeavingInspProcessPop').modal({
        backdrop: 'static',
        keyboard: false
      });
    }

/* ==================== 33. InspectionProcess ==================== */
var siteUrl = "{{url('/')}}";

    function InspectionProcess(Id) {
      jQuery.ajax({
        type: "GET",
        url: siteUrl + '/' + "ajax_script/getWorkOrderDetails",
        data: {
          "_token": "{{ csrf_token() }}",
          "FId": Id,
        },
        cache: false,
        success: function(response) {
          response = JSON.parse(response);
          console.log(response);
          // alert(response.warehouses);
          //alert(response.workRequirement);
          $("#ins_item_id").val(response.itemId);
          $("#ins_work_order_id").val(response.workOrdId);
          $("#ItemName").html(response.ItemName);
          $("#InspectionMachineName").html(response.MachineName || response.machineName || 'Not allocated');
          $("#InspectionMasterName").html(response.MasterName || response.masterName || 'Not allocated');
          $("#InsoutputNext").html(response.outputNextPro);
          $("#InsoutputUnit").html(response.outputUnit);
          $("#processtext").html(response.processtext);
          $("#outputUnitType").html(response.outputUnitType);
          $("#insp_work_warehouse_id").html(response.warehouses);
          $("#insp_work_warehouse_id option").filter(function () {
            return $.trim($(this).text()).toLowerCase() == 'beam warehouse';
          }).prop('selected', true);
        }
      });
      $('#InspectionProcessPop').modal({
        backdrop: 'static',
        keyboard: false
      });
    }

/* ==================== 34. StartProcess, StartProcessWev ==================== */
var siteUrl = "{{url('/')}}";
function StartProcess(Id)
	{
      var masterFallbackOptions = $('#masterId').html() || '<option value="">Select Master</option>';
      var machineFallbackOptions = $('#machineId').html() || '<option value="">Select Machine</option>';
      $("#itemId").val('');
      $("#work_order_id").val('');
      $("#ItemNameS").html('Loading...');
      $("#processNameId").html('');
      $("#RequestedItems").html('');
      $("#masterId").html('<option value="">Loading Masters...</option>');
      $("#machineId").html('<option value="">Loading Machines...</option>');
      jQuery.ajax({
        type: "GET",
        url: siteUrl + '/' + "ajax_script/getWorkOrderDetails",
        data: {
          "_token": "{{ csrf_token() }}",
          "FId": Id,
        },
        cache: false,
        success: function(response)
		{
			response = typeof response === 'string' ? JSON.parse(response) : response;
			console.log(response);
			$("#itemId").val(response.itemId);
			$("#work_order_id").val(response.workOrdId);
			$("#ItemNameS").html(response.ItemName || 'N/A');
			$("#processNameId").html(response.processName || '');
			$("#RequestedItems").html(response.RequestedItems || '');
			$("#masterId").html(response.masterOptions || masterFallbackOptions);
			$("#machineId").html(response.options || machineFallbackOptions);
        },
        error: function() {
			$("#ItemNameS").html('Unable to load item details.');
			$("#RequestedItems").html('<div class="alert alert-danger small">Unable to fetch work order details. Try again.</div>');
			$("#masterId").html(masterFallbackOptions);
			$("#machineId").html(machineFallbackOptions);
        }
      });
      $('#StartProcessPop').modal({
        backdrop: 'static',
        keyboard: false
      });
    }

	function StartProcessWev(Id)
	{
      var masterFallbackOptions = $('#masterIdWev').html() || '<option value="">Select Master</option>';
      var machineFallbackOptions = $('#machineIdWev').html() || '<option value="">Select Machine</option>';
      $("#itemIdWev").val('');
      $("#work_order_idWev").val('');
      $("#ItemNameWev").html('Loading...');
      $("#processNameIdWev").html('');
      $("#RequestedItemsWev").html('');
      $("#masterIdWev").html('<option value="">Loading Masters...</option>');
      $("#machineIdWev").html('<option value="">Loading Machines...</option>');
      jQuery.ajax({
        type: "GET",
        url: siteUrl + '/' + "ajax_script/getWorkOrderDetails",
        data: {
          "_token": "{{ csrf_token() }}",
          "FId": Id,
        },
        cache: false,
        success: function(response)
		{
			response = typeof response === 'string' ? JSON.parse(response) : response;
			console.log(response);
			$("#itemIdWev").val(response.itemId);
			$("#work_order_idWev").val(response.workOrdId);
			$("#ItemNameWev").html(response.ItemName || 'N/A');
			$("#processNameIdWev").html(response.processName || '');
			$("#RequestedItemsWev").html(response.RequestedItems || '');
			$("#masterIdWev").html(response.masterOptions || masterFallbackOptions);
			$("#machineIdWev").html(response.options || machineFallbackOptions);
        },
        error: function() {
			$("#ItemNameWev").html('Unable to load item details.');
			$("#RequestedItemsWev").html('<div class="alert alert-danger small">Unable to fetch work order details. Try again.</div>');
			$("#masterIdWev").html(masterFallbackOptions);
			$("#machineIdWev").html(machineFallbackOptions);
        }
      });
      $('#StartProcessPopWev').modal({  backdrop: 'static',  keyboard: false });
    }

/* ==================== 35. events ==================== */
$("#colorSearch").autocomplete({
        minLength: 0,
        source: siteUrl + '/' + "find_saleDyeingColor",
        focus: function(event, ui) {
          $( "#colorSearch" ).val( ui.item.dyeing_color);
		      return false;
        },
        select: function(event, ui) {
          $("#colorSearch").val(ui.item.dyeing_color);
          return false;
        }
      })
      .autocomplete("instance")._renderItem = function(ul, item) {
        return $("<li>")
          .append("<div>" + item.dyeing_color + " </div>")
          .appendTo(ul);
      };

/* ==================== 36. CoatingPrintInspProcess ==================== */
var siteUrl = "{{url('/')}}";

  function CoatingPrintInspProcess(Id)
  {
    jQuery.ajax({
      type: "GET",
      url: siteUrl + '/' + "ajax_script/getWorkOrderDetails",
      data: {
        "_token": "{{ csrf_token() }}",
        "FId": Id,
      },
      cache: false,
      success: function(response) {
        response = JSON.parse(response);
        console.log(response);

        // set values into *_print IDs (names untouched)
        $("#coating_ins_item_id_print").val(response.itemId);
        $("#coating_ins_work_order_id_print").val(response.workOrdId);
        $("#coating_ItemName_print").html(response.ItemName);
        $("#coating_InsoutputNext_print")?.html ? $("#coating_InsoutputNext_print").html(response.outputNextPro) : null;
        $("#coating_InsoutputUnit_print")?.html ? $("#coating_InsoutputUnit_print").html(response.outputUnit) : null;
        $("#coating_processtext_print").html(response.processtext);
        $("#coating_outputUnitType_print")?.html ? $("#coating_outputUnitType_print").html(response.outputUnitType) : null;
        $("#coating_workRequirement_print1").html(response.workRequirement);
        // warehouses HTML (keeps <option> list) - ensure you pass proper html from backend
        $("#coating_insp_work_warehouseId_print").html(response.warehouses || '');
        // $("#MachineNameC_print").html(response.MachineName);
        $("#inspTakaNumberC_print").html(response.inspTakaNumber);

        // clear any existing rows/totals in this print table
        $('#myTableCoatedPrint tbody').html('<tr class="table-row2"> </tr>');
        $('#toGreigeItemQty_print').text('0');
        $('#totalOutput_print').text('0');
      }
    });

    $('#CoatingPrintInspProcessPop').modal({
      backdrop: 'static',
      keyboard: false
    });
  }

/* ==================== 37. fetchWarehouseItemStockCoatingPrint, updateTotalOutputForTable, updateTotalGreigeForTable, calculateOutputSizePrint ==================== */
function fetchWarehouseItemStockCoatingPrint(lotNumber, workOrderId, tableId) 
{
    if (!lotNumber || !workOrderId) return;

    var siteUrl = "{{ url('/') }}";
    jQuery.ajax({
        type: "GET",
        url: siteUrl + '/' + "ajax_script/getWarehouseItemStockPrint",
        data: {
            "_token": "{{ csrf_token() }}",
            "lot_number": lotNumber,
            "dyeing_ins_work_order_id": workOrderId,
        },
        cache: false,
        success: function(response) {
            console.log("Raw server response (success):", response);

            // normalize/parse payload
            let payload;
            try {
                payload = (typeof response === 'string') ? JSON.parse(response) : response;
            } catch (e) {
                console.error("Error parsing JSON response:", e);
                payload = response;
            }

            // show message (Hindi preferred) and clear table if server returned message
            if (payload && (payload.message || payload.message_hi)) {
                document.getElementById('coating_workRequirement_print').innerHTML =
                    '<button type="button" class="btn btn-info" disabled>' + (payload.message_hi || payload.message) + '</button>';

                const tbl = document.getElementById(tableId);
                if (tbl) {
                    const existingTbody = tbl.querySelector('tbody');
                    if (existingTbody) existingTbody.innerHTML = '';
                }

                // still set machine info if present
                const nameMsg = payload.machineName || payload.MachineName || null;
                const idMsg   = payload.machineId   || payload.MachineId   || payload.machine_id || '';
                $("#MachineNameC_print").text(nameMsg ? nameMsg : 'Not allocated');
                $("#machineIdC_print").val(idMsg);
                return;
            }

            // Resolve stockItems from various possible payload shapes
            let stockItems = [];
            if (Array.isArray(payload)) {
                stockItems = payload;
            } else if (payload && Array.isArray(payload.stockItems)) {
                stockItems = payload.stockItems;
            } else if (payload && Array.isArray(payload.data)) {
                stockItems = payload.data;
            }

            // find/create table tbody
            const table = document.getElementById(tableId);
            if (!table) {
                console.error("Table not found:", tableId);
                return;
            }
            let tableBody = table.querySelector('tbody');
            if (!tableBody) {
                tableBody = document.createElement('tbody');
                table.appendChild(tableBody);
            }
            tableBody.innerHTML = '';

            // populate rows
            let rowNumber = 1;
            (stockItems || []).forEach(stockItem => {
                const qty = Number(stockItem.item_qty) || 0;
                const maxVal = Math.ceil(qty * 1.30);

                const newRow = document.createElement('tr');
                newRow.classList.add('table-row2');
                newRow.innerHTML =
                    '<td><input type="text" class="form-control" name="dyeing_taka_number[]" readonly value="' + rowNumber + '"></td>' +
                    '<td><input type="text" class="form-control" name="insp_taka_number[]" readonly value="' + (stockItem.insp_taka_number || '') + '"></td>' +
                    '<td><input type="text" class="form-control greige_item_qty" name="greige_item_qty[]" readonly value="' + (qty || '') + '"></td>' +
                    '<td><input type="text" class="form-control output_quan_break_size" name="output_quan_break_size[]" value="" oninput="calculateOutputSize(this)"></td>' +
                    '<td><input type="number" min="0" step="0.01" max="' + maxVal + '" class="form-control output_quan_size" name="output_quan_size[]" value="0"></td>' +
                    '<td><input type="number" min="0" step="0.01" class="form-control shrinkage_quan_size" name="shrinkage_quan_size[]" value="0"></td>';
                tableBody.appendChild(newRow);
                rowNumber++;
            });

            // update totals scoped to this table (print-specific functions)
            if (typeof updateTotalOutputForTable === 'function') updateTotalOutputForTable(tableId, '_print');
            if (typeof updateTotalGreigeForTable === 'function') updateTotalGreigeForTable(tableId, '_print');

            // set machine info (safe fallbacks, multiple key variants)
            const machineName = (payload && (payload.machineName || payload.MachineName)) || null;
            const machineId   = (payload && (payload.machineId || payload.MachineId || payload.machine_id)) || '';

            $("#MachineNameC_print").text(machineName ? machineName : 'Not allocated');
            $("#machineIdC_print").val(machineId);
        },
        error: function(xhr, status, error) {
            console.error("AJAX error:", status, error);
            console.error("Server response:", xhr.responseText);

            // try parse server error JSON
            var res = null;
            try {
                res = xhr.responseJSON || JSON.parse(xhr.responseText);
            } catch (e) {
                res = null;
            }

            // clear/notify
            const table = document.getElementById(tableId);
            if (table) {
                const existingTbody = table.querySelector('tbody');
                if (existingTbody) existingTbody.innerHTML = '';
            }

            if (res && (res.message || res.message_hi)) {
                document.getElementById('coating_workRequirement_print').innerHTML =
                    '<button type="button" class="btn btn-danger" disabled>' + (res.message_hi || res.message) + '</button>';
            } else {
                document.getElementById('coating_workRequirement_print').innerHTML =
                    '<div class="alert alert-danger">Unable to fetch warehouse stock. Try again.</div>';
            }

            // clear machine info on error
            $("#MachineNameC_print").text('Not allocated');
            $("#machineIdC_print").val('');
        }
    });
}

// helper to update total output for a given table and suffix
function updateTotalOutputForTable(tableId, printSuffix) {
    var table = document.getElementById(tableId);
    if (!table) return;
    const outputs = table.querySelectorAll('.output_quan_size');
    let total = 0;
    outputs.forEach(f => { total += parseFloat(f.value) || 0; });

    // set print-specific total if exists
    var printId = tableId + (printSuffix || '') + '_totalOutput'; // e.g., myTableCoatedPrint_totalOutput (not used) - we used myTableCoatedPrint_totalOutput earlier in earlier suggestions
    // simpler: our tfoot ids are '<tableId>_totalOutput' OR we set fixed id 'totalOutput_print' in markup
    var totalElemPrint = document.getElementById('totalOutput_print');
    if (totalElemPrint) totalElemPrint.textContent = total.toFixed(2);

    // fallback: generic id (if present elsewhere)
    var totalElemGeneric = document.getElementById('totalOutput');
    if (totalElemGeneric) totalElemGeneric.textContent = total.toFixed(2);
}

// helper to update greige total for a given table and suffix
function updateTotalGreigeForTable(tableId, printSuffix) {
    var table = document.getElementById(tableId);
    if (!table) return;
    const greigeFields = table.querySelectorAll('.greige_item_qty');
    let total = 0;
    greigeFields.forEach(f => { total += parseFloat(f.value) || 0; });

    var greigeElemPrint = document.getElementById('toGreigeItemQty_print');
    if (greigeElemPrint) greigeElemPrint.textContent = total.toFixed(2);

    var greigeElemGeneric = document.getElementById('toGreigeItemQty');
    if (greigeElemGeneric) greigeElemGeneric.textContent = total.toFixed(2);
}

// keep existing calculate/handler behaviour but scoped where possible
function calculateOutputSizePrint(element) {
    const breakSizeInput = element.value.trim();
    const sum = breakSizeInput.split('+').reduce((acc, val) => acc + (parseFloat(val.trim()) || 0), 0);

    // try find sibling output_quan_size
    var td = element.closest('td');
    var nextTd = td ? td.nextElementSibling : null;
    var outputSizeInput = nextTd ? nextTd.querySelector('.output_quan_size') : null;
    if (!outputSizeInput) {
        var row = element.closest('tr');
        outputSizeInput = row ? row.querySelector('.output_quan_size') : null;
    }
    if (outputSizeInput) outputSizeInput.value = sum.toFixed(2);

    // update totals for the table containing this input (print)
    var table = element.closest('table');
    if (table) {
        updateTotalOutputForTable(table.id, '_print');
    }
}

document.addEventListener('input', function(event) {
    if (event.target.classList.contains('output_quan_break_size')) {
        calculateOutputSizePrint(event.target);
    } else if (event.target.classList.contains('output_quan_size')) {
        var table = event.target.closest('table');
        if (table) updateTotalOutputForTable(table.id, '_print');
    } else if (event.target.classList.contains('greige_item_qty')) {
        var table = event.target.closest('table');
        if (table) updateTotalGreigeForTable(table.id, '_print');
    }
});

/* ==================== 38. escapeHtml ==================== */
(function(){
  function escapeHtml(str) {
    if (str === null || str === undefined) return '';
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  // Event delegation for button (works for dynamically rendered rows)
  document.addEventListener('click', function(e) {
    const btn = e.target.closest('.beam-return-btn');
    if (!btn) return;
    const wprId = btn.getAttribute('data-wpr-id');
    const workOrderId = btn.getAttribute('data-work-order-id');
    // pass the updated table id (with 'beam')
    GetBeamReturnItems(wprId, workOrderId, 'beamReturnItemsTable');
  });

  // Toggle 'select all' checkbox
  window.toggleSelectAllBeam = function(masterCheckbox) {
    const checkboxes = document.querySelectorAll('#beamReturnItemsTable tbody input[type="checkbox"]');
    checkboxes.forEach(function(cb) {
      if (!cb.disabled) cb.checked = masterCheckbox.checked;
    });
  };

  // Validate at least one is selected (excluding disabled ones)
  window.validateBeamReturnForm = function(form) {
    const checked = Array.from(document.querySelectorAll('#beamReturnItemsTable tbody input[type="checkbox"]'))
      .some(function(cb) { return cb.checked && !cb.disabled; });
    if (!checked) {
      alert('Please select at least one item to return.');
      return false;
    }
    disableSubmitButton(form);
    return true;
  };

  // Main AJAX loader
  window.GetBeamReturnItems = function(id, workOrderId, tableId) {
    if (!id) return;
    var modalwprId = document.getElementById('beamWprId');
    var modalworkOrderId = document.getElementById('beamChkworkOrderId');
    modalwprId.value = id;
    modalworkOrderId.value = workOrderId;
    jQuery('#modalBeamLotNumber').text('WO ' + escapeHtml(workOrderId) + ' / Req ' + escapeHtml(id));

    var tableBody = document.querySelector('#' + tableId + ' tbody');
    if (!tableBody) {
      console.error('Beam return table body not found:', tableId);
      return;
    }

    tableBody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">Loading return items...</td></tr>';
    var selectAllLoading = document.getElementById('beamSelectAll');
    if (selectAllLoading) selectAllLoading.checked = false;

    jQuery('#beamReturnBeamModal').modal({ backdrop: 'static', keyboard: false });
    jQuery('#beamReturnBeamModal').modal('show');

    var siteUrl = "<?php echo url('/'); ?>";
    var ajaxUrl = '/ajax_script/getBeamReturnItems';

    jQuery.ajax({
      type: "GET",
      url: ajaxUrl,
      data: {
        id: id,
        work_order_id: workOrderId,
        _token: "<?php echo csrf_token(); ?>"
      },
      dataType: 'json',
      cache: false,
      success: function(returnItems) {
        tableBody.innerHTML = '';

        if (!Array.isArray(returnItems)) {
          console.error('Expected array, got:', returnItems);
          tableBody.innerHTML = '<tr><td colspan="6" class="text-center text-danger">Invalid return item response.</td></tr>';
          return;
        }

        if (returnItems.length === 0) {
          tableBody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No return items found for this requirement.</td></tr>';
          var selectAllEmpty = document.getElementById('beamSelectAll');
          if (selectAllEmpty) selectAllEmpty.checked = false;
          return;
        }

        returnItems.forEach(function(item, index) {
          var tr = document.createElement('tr');

          var isDisabled = item.department_return_request && item.department_return_request.id ? true : false;

          var wareOutHidden = '<input type="hidden" class="form-control" name="ware_out_item_id[]" value="' + escapeHtml(item.id) + '">';
          var stockIdInput = '<input type="text" class="form-control" name="return_wis_id[]" readonly value="' + escapeHtml(item.wis_id) + '">';
          var takaInput = '<input type="text" class="form-control" name="return_insp_taka_number[]" readonly value="' + escapeHtml(item.insp_taka_number) + '">';
          var qtyInput = '<input type="text" class="form-control" name="received_item_qty[]" readonly value="' + escapeHtml(item.item_qty || '') + '">';
          var usedqtyInput = '<input type="text" class="form-control" Required name="used_item_qty[]" value="">';
          var returnqtyInput = '<input type="text" class="form-control" Required name="return_item_qty[]" value="">';

          var checkboxHtml = '<input type="checkbox" name="is_return[' + index + ']" value="1"' + (isDisabled ? ' disabled' : '') + '>';
          if (isDisabled) {
            checkboxHtml += '<input type="hidden" name="is_return[' + index + ']" value="0">';
          }

          tr.innerHTML = '<td>' + wareOutHidden + stockIdInput + '</td>' +
                         '<td>' + takaInput + '</td>' +
                         '<td>' + qtyInput + '</td>' +
                         '<td>' + usedqtyInput + '</td>' +
                         '<td>' + returnqtyInput + '</td>' +
                         '<td>' + checkboxHtml + '</td>';

          tableBody.appendChild(tr);
        });

        var selectAll = document.getElementById('beamSelectAll');
        if (selectAll) selectAll.checked = false;
      },
      error: function(xhr, status, error) {
        console.error("AJAX error:", status, error, xhr.responseText);
        var message = 'Unable to load return items.';
        if (xhr && xhr.responseJSON && xhr.responseJSON.message) {
          message = xhr.responseJSON.message;
        }
        tableBody.innerHTML = '<tr><td colspan="6" class="text-center text-danger">' + escapeHtml(message) + '</td></tr>';
      }
    });
  };

})();

/* ==================== 39. findEditArea, findSelect, findStatus, findNameElement ==================== */
/*
  Unified machine edit/save JS
  - Handles both per-work-order editors (data-woid / beam-machine-*)
  - And per-work-process-row editors (data-id / beam-machine-* where id is the row id)
  - Tries 'beam-' prefixed IDs first (as in your HTML), falls back to non-prefixed IDs if needed.
*/
$(function(){
    // Ensure CSRF header
    $.ajaxSetup({
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
    });

    // Helper: find element by trying beam- prefix then fallback to no prefix
    function findEditArea(id){
        var sel = $('#beam-machine-edit-' + id);
        if(sel.length) return sel;
        sel = $('#machine-edit-' + id);
        return sel;
    }
    function findSelect(id){
        var sel = $('#beam-machine-select-' + id);
        if(sel.length) return sel;
        sel = $('#machine-select-' + id);
        return sel;
    }
    function findStatus(id){
        var sel = $('#beam-machine-status-' + id);
        if(sel.length) return sel;
        sel = $('#machine-status-' + id);
        return sel;
    }
    function findNameElement(woid){
        var sel = $('#beam-machine-name-' + woid);
        if(sel.length) return sel;
        sel = $('#machine-name-' + woid);
        return sel;
    }

    // OPEN editor (handles both classes used in markup)
    $(document).on('click', '.edit-machine-btn, .btn-edit-machine', function(e){
        e.preventDefault();
        var $btn = $(this);
        var rowId = $btn.data('id');     // per-row id (WorkProcessRequirement id)
        var woid  = $btn.data('woid');   // work-order id (for top-level editor)
        var targetId = rowId ? rowId : woid;

        // show the corresponding edit area (beam-machine-edit-<id> or fallback)
        var $editArea = findEditArea(targetId);
        if($editArea.length){
            $editArea.slideDown();
        }

        // hide the edit button that was clicked (cleaner UX)
        $btn.hide();

        // hide machine display label if it exists (per-row labels have class .machine-display-<id>)
        if(rowId){
            $('.machine-display-' + rowId).hide();
        } else if(woid){
            var $nameEl = findNameElement(woid);
            if($nameEl.length) $nameEl.hide();
        }

        // Copy woid onto save button for per-row editor if needed (some flows expect woid)
        if(woid){
            // find a save btn inside edit area and set data-woid
            $editArea.find('.save-machine-btn, .btn-save-machine').attr('data-woid', woid);
        }
    });

    // CANCEL editor
    $(document).on('click', '.cancel-machine-btn, .btn-cancel-machine', function(e){
        e.preventDefault();
        var $btn = $(this);
        var rowId = $btn.data('id') || $btn.attr('data-id');
        var woid  = $btn.data('woid') || $btn.attr('data-woid');
        var targetId = rowId ? rowId : woid;

        // hide edit area
        var $editArea = findEditArea(targetId);
        if($editArea.length) $editArea.slideUp();

        // clear status text
        var $status = findStatus(targetId);
        if($status.length) $status.text('');

        // show appropriate edit button
        if(rowId){
            $('.edit-machine-btn[data-id="' + rowId + '"]').show();
            $('.machine-display-' + rowId).show();
        } else if(woid){
            $('.btn-edit-machine[data-woid="' + woid + '"]').show();
            var $nameEl = findNameElement(woid);
            if($nameEl.length) $nameEl.show();
            $('#beam-machine-edit-error-' + woid).hide().text('');
        }
    });

    // SAVE handler (single handler for both save button variants)
    $(document).on('click', '.save-machine-btn, .btn-save-machine', function(e){
        e && e.preventDefault();
        var $btn = $(this);

        // detect whether this is per-row (has data-id) or per-wo (only data-woid)
        var rowId = $btn.data('id') || $btn.attr('data-id');       // WorkProcessRequirement id
        var woid  = $btn.data('woid') || $btn.attr('data-woid');   // work order id

        // select element depends on id
        var $select;
        if(rowId){
            $select = findSelect(rowId);
        } else if(woid){
            $select = findSelect(woid);
        } else {
            // nothing to do
            console.warn('Save clicked but no id/woid present on button');
            return;
        }

        var machineId = $select.length ? $select.val() : '';

        // UX
        var $status = findStatus(rowId ? rowId : woid);
        if($status.length){
            $status.css('color','').text('Saving...');
        }
        $btn.prop('disabled', true);

        // Decide which endpoint to call:
        // - per-row update: route('workorder.updateMachine') expects { id, dyeing_machine_id, woid? }
        // - per-workorder update: route('workorder.updateMachineWo') expects { work_order_id, machine_id }
        if(rowId){
            // per-row update
            $.ajax({
                url: '{{ route("workorder.updateMachine") }}',
                method: 'POST',
                dataType: 'json',
                data: {
                    _token: '{{ csrf_token() }}',
                    id: rowId,
                    dyeing_machine_id: machineId,
                    woid: woid || ''  // optional, pass if available
                }
            })
            .done(function(res){
                if(res && res.status === 'success'){
                    // update per-row label element (.machine-display-<rowId>)
                    var labelEl = $('.machine-display-' + rowId);
                    var display = res.machine_name ? res.machine_name : (res.machine_id ? res.machine_id : 'Machine Not Set');
                    var escaped = $('<div/>').text(display).html();

                    // update classes
                    labelEl.removeClass('label-danger label-success label-primary label-info label-warning label-default');
                    if(res.machine_id){
                        labelEl.addClass('label-success');
                    } else {
                        labelEl.addClass('label-danger');
                    }
                    labelEl.attr('title', display);
                    labelEl.html('<i class="fa fa-cog"></i> ' + escaped);

                    // close editor + show label
                    if($status.length){
                        $status.text('Saved').fadeOut(1200, function(){ $(this).text('').show(); });
                    }
                    var $editArea = findEditArea(rowId);
                    if($editArea.length){
                        $editArea.slideUp(function(){ $('.edit-machine-btn[data-id="' + rowId + '"]').show(); labelEl.show(); });
                    }
                } else {
                    var msg = (res && res.message) ? res.message : 'Update failed';
                    if($status.length) $status.css('color','red').text(msg);
                }
            })
            .fail(function(xhr){
                var msg = 'Error saving.';
                if(xhr && xhr.responseJSON){
                    if(xhr.responseJSON.message) msg = xhr.responseJSON.message;
                    else if(xhr.responseJSON.errors){
                        var key = Object.keys(xhr.responseJSON.errors)[0];
                        msg = xhr.responseJSON.errors[key][0];
                    }
                }
                if($status.length) $status.css('color','red').text(msg);
            })
            .always(function(){
                $btn.prop('disabled', false);
            });

        } else {
            // per-workorder update
            $.post('{{ route("workorder.updateMachineWo") }}', {
                _token: '{{ csrf_token() }}',
                work_order_id: woid,
                machine_id: machineId
            })
            .done(function(res){
                if(res && res.status === 'success'){
                    // update name element if exists
                    var $nameEl = findNameElement(woid);
                    if($nameEl.length){
                        var txt = res.machine_name || '';
                        $nameEl.text(txt);
                        $nameEl.show();
                    }
                    // update edit button data if any
                    $('.btn-edit-machine[data-woid="' + woid + '"]').data('machine-id', res.machine_id);

                    // hide editor and show edit button
                    var $editArea = findEditArea(woid);
                    if($editArea.length){
                        $editArea.hide();
                    }
                    $('.btn-edit-machine[data-woid="' + woid + '"]').show();
                } else {
                    var msg = (res && res.message) ? res.message : 'Update failed';
                    $('#beam-machine-edit-error-' + woid).show().text(msg);
                }
            })
            .fail(function(xhr){
                var msg = 'Error saving.';
                if(xhr && xhr.responseJSON){
                    if(xhr.responseJSON.message) msg = xhr.responseJSON.message;
                    else if(xhr.responseJSON.errors){
                        var key = Object.keys(xhr.responseJSON.errors)[0];
                        msg = xhr.responseJSON.errors[key][0];
                    }
                }
                $('#beam-machine-edit-error-' + woid).show().text(msg);
            })
            .always(function(){
                $btn.prop('disabled', false);
            });
        }
    });

}); // end ready

/* ==================== 40. CloseWorkProcess ==================== */
let closewoId = null;

function CloseWorkProcess(id) {
  closewoId = id;
  $('#closeActivateModal').modal('show');
}

// Confirm button handler
$('#confirmCloseWOBtn').on('click', function() {
  var $btn = $(this);

  if (!closewoId) {
    alert('Invalid work order id.');
    return;
  }

  // disable to prevent double-clicks
  if ($btn.data('processing')) return;
  $btn.data('processing', true);
  $btn.prop('disabled', true).text('Processing...');

  jQuery.ajax({
    type: "POST",
    url: "{{ route('ajax.closeWorkOrder') }}",
    data: {
      "_token": "{{ csrf_token() }}",
      "FId": closewoId
    },
    cache: false,
    success: function(response) {
      if (response && response.success) {
        // hide the UI element for this work order if present
        var $row = $("#Mid" + closewoId);
        if ($row.length) {
          $row.hide();
        }
        alert("Work order closed successfully.");
      } else {
        // server responded but with success=false
        alert(response.message || "Failed to close work order.");
      }
      $('#closeActivateModal').modal('hide');
    },
    error: function(xhr, status, error) {
      var msg = "An error occurred.";
      try {
        if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
        else if (xhr.responseText) msg = xhr.responseText;
      } catch (e) {}
      alert("Error: " + msg);
      $('#closeActivateModal').modal('hide');
    },
    complete: function() {
      // re-enable button (or you may keep it disabled to prevent retry)
      $btn.data('processing', false);
      $btn.prop('disabled', false).text('OK');
    }
  });
});

window.addEventListener('load', function() {
  var timeText = document.getElementById('pageLoadTimeText');
  if (!timeText || !window.performance) return;

  setTimeout(function() {
    var navEntry = performance.getEntriesByType && performance.getEntriesByType('navigation')[0];
    var loadMs = navEntry ? navEntry.loadEventEnd : (performance.timing.loadEventEnd - performance.timing.navigationStart);

    if (loadMs && loadMs > 0) {
      timeText.textContent = (loadMs / 1000).toFixed(2) + ' sec';
    } else {
      timeText.textContent = 'N/A';
    }
  }, 0);
});
</script>

