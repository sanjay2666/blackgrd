<?php
	use Illuminate\Support\Facades\Route;
?>
<!DOCTYPE html>
<html lang="en">
<head>@include('frontend.common.head', ['pageTitle' => 'Department Return Requests | Loomexa'])
</head>
<body class="hold-transition sidebar-mini department-return-page">
<div class="wrapper"> @include('frontend.common.header')
	<div class="content-wrapperd">
		<section class="content">
			<div class="row">
				<div class="col-sm-12">
					{!! display_message('message') !!}
					<div class="panel panel-bd lobidrag department-return-panel">
						<div class="panel-heading department-return-heading">
							<div>
								<h4>Department Return Requests</h4>
								<span>Review pending and accepted return requests from departments.</span>
							</div>
							<a href="{{ route('show-department-return-requests') }}" class="btn btn-default btn-sm">
								<i class="glyphicon glyphicon-refresh"></i> Refresh
							</a>
						</div>
						<div class="panel-body">
							<div class="department-return-table-wrap table-responsive">
								<table id="dataTableExample1" class="table table-bordered table-striped table-hover department-return-table">
									<thead>
										<tr class="info text-center">
											<th class="dr-col-id">Request</th>
											<th class="dr-col-lot">Lot</th>
											<th class="dr-col-wo">Work Order</th>
											<th class="dr-col-employee">Employee</th>
											<th class="dr-col-wpr">WPR</th>
											<th class="dr-col-date">Return Date</th>
											<th class="dr-col-reason">Reason</th>
											<th class="dr-col-items">Items</th>
											<th class="dr-col-status">Status</th>
										</tr>
									</thead>
									<tbody>
										@forelse($dataDR as $data)
											@php
												$drrId = $data->id;
												$reqStatus = strtolower((string) $data->status);
												$itemsCount = $data->DepartmentReturnRequest->count();
												$acceptRouteExists = Route::has('accept-department-return-request');
												$acceptedViewRouteExists = Route::has('show-accepted-department-return-request');
												$denyRouteExists = Route::has('warehouse.denyDepartmentRequest');
											@endphp
											<tr id="Mid{{ $drrId }}">
												<td>
													<strong>#{{ $data->id }}</strong>
													<div class="muted-id">DR</div>
												</td>
												<td>{{ $data->req_lot_number ?: '-' }}</td>
												<td>{{ $data->work_order_id ?: '-' }}</td>
												<td>
													<i class="glyphicon glyphicon-user"></i>
													{{ $data->Individual->name ?? '-' }}
												</td>
												<td>{{ $data->work_pro_req_id ?: '-' }}</td>
												<td>
													<i class="glyphicon glyphicon-calendar"></i>
													{{ $data->return_date ? date('d-m-Y', strtotime($data->return_date)) : '-' }}
												</td>
												<td class="department-return-reason">{{ $data->reason ?: '-' }}</td>
												<td>
													<span class="department-return-count">{{ $itemsCount }}</span>
												</td>
												<td class="department-return-actions">
													@if($reqStatus === 'pending')
														<span class="department-return-status pending"><i class="glyphicon glyphicon-time"></i> Pending</span>
														<div>
															@if($acceptRouteExists)
																<a href="{{ route('accept-department-return-request', enc($drrId)) }}" target="_blank" class="btn btn-success btn-xs">
																	<i class="glyphicon glyphicon-ok"></i> Accept
																</a>
															@else
																<span class="btn btn-default btn-xs disabled">Accept</span>
															@endif

															@if($denyRouteExists)
																<a href="javascript:void(0);" onClick="openDenyModal({{ $drrId }})" class="btn btn-danger btn-xs">
																	<i class="glyphicon glyphicon-remove"></i> Deny
																</a>
															@endif
														</div>
													@elseif($reqStatus === 'accepted')
														<span class="department-return-status accepted"><i class="glyphicon glyphicon-ok"></i> Accepted</span>
														<div>
															@if($acceptedViewRouteExists)
																<a href="{{ route('show-accepted-department-return-request', enc($drrId)) }}" target="_blank" class="btn btn-primary btn-xs">
																	<i class="glyphicon glyphicon-eye-open"></i> View
																</a>
															@endif
														</div>
													@elseif($reqStatus === 'rejected' || $reqStatus === 'denied')
														<span class="department-return-status rejected"><i class="glyphicon glyphicon-remove"></i> Rejected</span>
													@else
														<span class="department-return-status default">{{ ucfirst($data->status) }}</span>
													@endif
												</td>
											</tr>
										@empty
											<tr>
												<td colspan="9" class="department-return-empty">
													<i class="glyphicon glyphicon-inbox"></i>
													No department return requests found.
												</td>
											</tr>
										@endforelse

										@if($dataDR->hasPages())
											<tr class="text-center">
												<td colspan="9">
													<div class="department-return-pagination">
														{{ $dataDR->links('vendor.pagination.bootstrap-4') }}
													</div>
												</td>
											</tr>
										@endif
									</tbody>
								</table>
							</div>
						</div>
					</div>
				</div>
			</div>
		</section>
	</div>

	<div id="denyModal" class="modal fade department-return-deny-modal" tabindex="-1" role="dialog" aria-labelledby="denyModalLabel">
		<div class="modal-dialog" role="document">
			<div class="modal-content">
				<form id="denyForm">
					<div class="modal-header">
						<button type="button" class="close" data-dismiss="modal" aria-label="Close">
							<span aria-hidden="true">&times;</span>
						</button>
						<h4 class="modal-title" id="denyModalLabel">
							<i class="glyphicon glyphicon-remove-circle"></i> Deny Department Return
						</h4>
					</div>
					<div class="modal-body">
						<input type="hidden" id="deny_department_return_id" name="department_return_id" value="">
						<div class="form-group">
							<label for="deny_reason">Reason</label>
							<textarea id="deny_reason" name="reason" class="form-control" rows="4" placeholder="Type reason"></textarea>
						</div>
						<div id="deny_error" class="alert alert-danger department-return-deny-error"></div>
					</div>
					<div class="modal-footer">
						<button type="button" id="denyCancelBtn" class="btn btn-default" data-dismiss="modal">Cancel</button>
						<button type="submit" id="denySubmitBtn" class="btn btn-danger">Deny Request</button>
					</div>
				</form>
			</div>
		</div>
	</div>

	@include('frontend.common.footer')
</div>
@include('frontend.common.footerscript')
<script type="text/javascript" src="{{ asset('js/jquery.validate.js') }}"></script>
<script>
var denyDepartmentRequestUrl = @json(Route::has('warehouse.denyDepartmentRequest') ? route('warehouse.denyDepartmentRequest') : '');

function openDenyModal(dprId) {
	if (!dprId || !denyDepartmentRequestUrl) {
		return;
	}

	$('#deny_department_return_id').val(dprId);
	$('#deny_reason').val('');
	$('#deny_error').hide().text('');
	$('#denySubmitBtn').prop('disabled', false).text('Deny Request');
	$('#denyModal').modal('show');
}

$(function() {
	$('#denyForm').on('submit', function(e) {
		e.preventDefault();

		var dprId = $('#deny_department_return_id').val();
		var reason = $('#deny_reason').val().trim();

		if (!dprId) {
			$('#deny_error').show().text('Invalid request.');
			return;
		}

		if (!confirm('Are you sure you want to deny this department return request?')) {
			return;
		}

		$('#denySubmitBtn').prop('disabled', true).text('Processing...');

		$.ajax({
			url: denyDepartmentRequestUrl,
			method: 'POST',
			dataType: 'json',
			data: {
				department_return_id: dprId,
				reason: reason
			},
			headers: {
				'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
			},
			success: function(res) {
				if (res.success) {
					$('#denyModal').modal('hide');
					location.reload();
					return;
				}

				$('#deny_error').show().text(res.message || 'Failed to deny request.');
				$('#denySubmitBtn').prop('disabled', false).text('Deny Request');
			},
			error: function(xhr) {
				var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Server error.';
				$('#deny_error').show().text(msg);
				$('#denySubmitBtn').prop('disabled', false).text('Deny Request');
			}
		});
	});
});
</script>
</body>
</html>
