<!DOCTYPE html>
<html lang="en">

<head>
@include('frontend.common.head', ['pageTitle' => 'Sale Orders | Loomexa'])
</head>

<body class="hold-transition sidebar-mini sale-order-page">
<div id="preloader"><div id="status"></div></div>
<div class="wrapper">
@include('frontend.common.header')
<div class="content-wrapper">
	
	<section class="content">
		{!! display_message('message') !!}
		<div class="row">
			<div class="col-sm-12">
				<div class="panel panel-bd lobidrag">
					<div class="panel-heading">
						<div class="btn-group">
							<h4>Sale Order List</h4>
						</div>
						<div class="pull-right">
							 <a href="{{ route('sale-orders.create') }}" class="btn btn-add"><i class="fa fa-plus"></i> Add Sale Order</a>
						</div>

					</div>
					<div class="panel-body">
						
						<div class="sale-order-filter-box">
							<form action="{{ route('sale-orders.index') }}" method="GET" role="search" autocomplete="off">
								<div class="row">
									<div class="col-sm-2 col-xs-12 form-group">
										<div class="input-group">
											<span class="input-group-addon"><i class="fa fa-user"></i></span>
											<input type="text" class="form-control" name="qsearch" id="cus_search" value="{{ $qsearch }}" placeholder="Customer Name">
										</div>
									</div>
									<div class="col-sm-2 col-xs-12 form-group">
										<div class="input-group">
											<span class="input-group-addon"><i class="fa fa-cube"></i></span>
											<input type="text" class="form-control" name="qnamesearch" id="item_search" value="{{ $qnamesearch }}" placeholder="Item Name">
										</div>
									</div>
									<div class="col-sm-2 col-xs-12 form-group">
										<div class="input-group">
											<span class="input-group-addon"><i class="fa fa-hashtag"></i></span>
											<input type="text" class="form-control" name="ordNumSearch" id="ordNumSearch" value="{{ $ordNumSearch }}" placeholder="S.O Number">
										</div>
									</div>
									<div class="col-sm-1 col-xs-12 form-group">
										<select class="form-control" name="priority">
											<option value="">Priority</option>
											@foreach ($priorityArr as $row)
											<option value="{{ $row }}" @selected($priority == $row)>{{ $row }}</option>
											@endforeach
										</select>
									</div>
									<div class="col-sm-1 col-xs-12 form-group">
										<input type="text" class="form-control loomexa-datepicker" name="from_date" id="from_date" value="{{ $fromDate }}" placeholder="From Date">
									</div>
									<div class="col-sm-1 col-xs-12 form-group">
										<input type="text" class="form-control loomexa-datepicker" name="to_date" id="to_date" value="{{ $toDate }}" placeholder="To Date">
									</div>
									<div class="col-sm-1 col-xs-12 form-group">
										<select class="form-control" name="sale_order_type">
											<option value="">All</option>
											<option value="1" @selected($sale_order_type == '1')>Customer</option>
											<option value="2" @selected($sale_order_type == '2')>Self</option>
										</select>
									</div>
									<div class="col-sm-1 col-xs-12 form-group">
										<input type="text" class="form-control loomexa-datepicker" name="create_date" id="create_date" value="{{ $createDate }}" placeholder="Created Date">
									</div>
									<div class="col-sm-1 col-xs-12 form-group">
										<button type="submit" class="btn btn-success btn-block"><i class="fa fa-search"></i></button>
									</div>
								</div>
							</form>
						</div>

					   
						<div class="table-responsive">
							<table class="table table-bordered table-striped table-hover sale-order-table small">
								<thead>
									<tr class="info">
										<th>#</th>
										<th><span class="glyphicon glyphicon-file"></span> S.O. Number</th>
										<th><span class="glyphicon glyphicon-user"></span> Customer</th>
										<th>Lifecycle</th>
										<th><span class="glyphicon glyphicon-info-sign"></span> Order Details</th>
										<th><span class="glyphicon glyphicon-list-alt"></span> Order Item Details</th>
										<th>Action</th>
									</tr>
								</thead>

								<tbody>
									@forelse ($saleOrders as $data)
										<tr id="Mid{{ $data->id }}">
										

											<td> {{ $data->id }} </td>
											<td class="order-number-cell text-center">
												<a href="javascript:void(0);" class="btn btn-info btn-sm openOrderModal order-number-link" data-id="{{ enc($data->id) }}" data-title="{{ $data->sale_order_number }}">
													{{ $data->sale_order_number }}
												</a>

												<div class="sale-order-cell-actions">
													@if (!empty($data->order_slip_file))
														 
														<a href="{{ asset($data->order_slip_file) }}" class="btn btn-primary btn-xs" title="Download Order Slip" download> <span class="glyphicon glyphicon-download-alt" aria-hidden="true"></span> </a>
														 
													@endif

													<a href="javascript:void(0);" class="btn btn-success btn-xs uploadBtn" data-id="{{ $data->id }}" title="Upload Order Slip">
														<span class="glyphicon glyphicon-upload" aria-hidden="true"></span> Upload
													</a>
												</div>

												<div class="sale-order-days-box">
													<strong class="order-days-label">No. of Days:</strong>
													{!! daysFromNow($data->sale_order_date) !!}
												</div>

												 
											</td>
											<td>
												<div class="sale-order-customer-name"> {{ $data->customer->name ?? '-' }} </div>
												@if (!empty($data->development_type))
													<span class="label label-info">{{ $data->development_type }}</span>
												@else
													<span class="text-muted">-</span>
												@endif
											</td>
											<td>
												<span class="label label-info">{{ $data->document_status?->label() ?? 'Unmapped' }}</span>
											</td>


											<td>
												<p class="sale-order-info-line"><span class="glyphicon glyphicon-flag text-muted"></span> <span class="text-muted">Priority:</span> <strong>{{ $data->order_priority ?: '-' }}</strong></p>

												 
												<p class="sale-order-info-line"><span class="glyphicon glyphicon-calendar text-muted"></span>  <strong>{{ !empty($data->sale_order_date) ? date('d-m-Y', strtotime($data->sale_order_date)) : '-' }}</strong></p>

												<p class="sale-order-info-line-last"><span class="glyphicon glyphicon-time text-muted"></span>  <strong>{{ !empty($data->created_at) ? date('d-m-Y', strtotime($data->created_at)) : '-' }}</strong></p>
											</td>

											<td class="sale-order-items-cell">
												<div class="table-responsive sale-order-inner-table-wrap">
													<table class="table table-bordered table-condensed table-hover sale-order-inner-table">
														<thead>
															<tr class="active">
																<th>Item</th> 
																<th>Priority</th>
																<th>Dyeing</th>
																<th>Coating</th>
																<th>Print / Extra</th>
																<th>Delivery Date</th>
																<th>Rate</th>
																<th>Meter</th>
																<th>Dlvrd</th>
																<th>Pending</th>
																 
																<th>Action</th>
															</tr>
														</thead>

														<tbody>
															@forelse ($data->saleOrderItems as $item)

																<tr>
																	<td> {{ $item->item_name ?: '-' }}  {{ $item->item_code ?: '-' }}   </td>

																	 
																	<td>
																		@if (!empty($item->order_item_priority))
																			<span class="label label-warning">{{ $item->order_item_priority }}</span>
																		@else
																			-
																		@endif
																	</td>

																	<td>{{ !empty($item->dyeing_color) ? $item->dyeing_color : '-' }}</td>

																	<td>{{ !empty($item->coating_type) ? $item->coating_type : '-' }}</td>
																	
																	<td> {{ $item->extra_job }} /  {{ $item->print_job }}</td>

																	<td>{{ !empty($item->expect_delivery_date) ? date('d-m-Y', strtotime($item->expect_delivery_date)) : '-' }}</td>

																	<td> {{ number_format((float) $item->rate, 2) }} </td>

																	
																	<td>{{ number_format((float) $item->meter, 2) }}</td>
																	<td>{{ number_format((float) $item->delivered_item_mtr, 2) }}</td>
																	<td>
																		@if ((float) $item->pending_item_mtr > 0)
																			<span class="text-danger"> {{ number_format((float) $item->pending_item_mtr, 2) }} </span>
																		@else
																			<span class="text-success"><strong>0.00</strong></span>
																		@endif
																	</td>
																	 
																	<td>
																		@if ($item->status == 'Active')
																			<script type="application/json" id="sale-order-item-json-{{ $item->id }}">@json($item->getAttributes())</script>
																			<button type="button" class="btn btn-primary btn-xs editSaleOrderItemBtn" data-toggle="modal" data-target="#editSaleOrderItemModal"
																				data-sale-order-id="{{ enc($data->id) }}"
																				data-sale-order-item-id="{{ enc($item->id) }}"
																				data-json-id="sale-order-item-json-{{ $item->id }}">
																				<i class="fa fa-pencil"></i>
																			</button>
																			<button type="button" class="btn btn-danger btn-xs cancelSaleOrderItemBtn" data-toggle="modal" data-target="#cancelSaleOrderItemModal" data-sale-order-id="{{ enc($data->id) }}" data-sale-order-item-id="{{ enc($item->id) }}">
																				<i class="fa fa-close"></i>
																			</button>
																		@else
																			<span class="label label-default">{{ $item->status }}</span>
																		@endif
																		
																		<p>{{ $item->id }}</p>
																	</td>

																</tr>
															@empty
																<tr>
																	<td colspan="12" class="text-center text-muted"><span class="glyphicon glyphicon-info-sign"></span> No order items found.</td>
																</tr>
															@endforelse
														</tbody>
													</table>
												</div>
											</td>
											 
											<td class="order-action-cell action-stack">
												<p class="sale-order-action-top"><a href="javascript:void(0);" class="btn btn-success btn-sm openOrderModal" data-id="{{ enc($data->id) }}" data-title="{{ $data->sale_order_number }}"><i class="fa fa-eye"></i> Details</a></p>

												<p class="sale-order-action-bottom">													
													<a href="{{ route('saleorders.print', ['id' => enc($data->id)]) }}" class="btn btn-default btn-xs" title="Print Sale Order" target="_blank"><i class="fa fa-print" aria-hidden="true"></i></a>
													@php
														$saleOrderJson = $data->getAttributes();
														$saleOrderJson['customer_name'] = '';
														$saleOrderJson['customer_phone'] = '';
														$saleOrderJson['customer_gstin'] = '';
														$saleOrderJson['employee_name'] = '';
														$saleOrderJson['agent_name'] = '';

														if (!empty($data->customer)) {
															$saleOrderJson['customer_name'] = $data->customer->name;

															if ($saleOrderJson['customer_name'] == '') {
																$saleOrderJson['customer_name'] = $data->customer->company_name;
															}

															$saleOrderJson['customer_phone'] = $data->customer->phone;
															$saleOrderJson['customer_gstin'] = $data->customer->gstin;
														}

														if (!empty($data->employee)) {
															$saleOrderJson['employee_name'] = $data->employee->name;
														}

														if (!empty($data->agent)) {
															$saleOrderJson['agent_name'] = $data->agent->name;
														}
													@endphp
													<script type="application/json" id="sale-order-json-{{ $data->id }}">@json($saleOrderJson)</script>
													<a href="javascript:void(0);" class="btn btn-primary btn-xs editSaleOrderBtn" title="Edit Sale Order" data-toggle="modal" data-target="#editSaleOrderModal"
														data-sale-order-id="{{ enc($data->id) }}"
														data-json-id="sale-order-json-{{ $data->id }}">
														<i class="fa fa-pencil"></i>
													</a>
													<a href="javascript:void(0);" onclick="deleteSaleOrder('{{ enc($data->id) }}', '{{ $data->id }}');" class="btn btn-danger btn-xs" title="Delete Sale Order"><i class="fa fa-trash-o"></i></a>
												</p>
											</td>


										</tr>
									@empty
										<tr>
											<td colspan="6" class="text-center text-muted sale-order-empty-cell"><span class="glyphicon glyphicon-info-sign"></span> No sale order records found.</td>
										</tr>
									@endforelse
								</tbody>
							</table>
						</div>



						<div class="pagination text-center sale-order-pagination">
							<span class="pagination-links">
								{{ $saleOrders->appends(['qsearch' => $qsearch, 'qnamesearch' => $qnamesearch, 'ordNumSearch' => $ordNumSearch, 'priority' => $priority, 'from_date' => $fromDate, 'to_date' => $toDate, 'create_date' => $createDate, 'sale_order_type' => $sale_order_type])->links('vendor.pagination.bootstrap-4') }}
							</span>
							@if ($saleOrders->lastPage() > 1)
							<span class="manual-page-input sale-order-manual-page">
								<label for="manualPageInput">Go to page:</label>
								<input type="number" id="manualPageInput" min="1" max="{{ $saleOrders->lastPage() }}" value="{{ $saleOrders->currentPage() }}" class="sale-order-page-input">
								<button type="button" class="btn btn-sm btn-success" id="goToPageButton">Go</button>
							</span>
							@endif
						</div>
					</div>
				</div>
			</div>
		</div>
	</section>
</div>


<div id="confirmDeleteModal" class="modal fade modal-center loomexa-modal" tabindex="-1" role="dialog" aria-labelledby="confirmDeleteLabel">
	<div class="modal-dialog delete-modal-dialog" role="document">
		<div class="modal-content">

			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">&times;</button>
				<h4 class="modal-title" id="confirmDeleteLabel"><span class="glyphicon glyphicon-trash"></span> Confirm Deletion</h4>
			</div>

		   <div class="modal-body text-center delete-modal-body">
	<div class="loomexa-modal-icon-wrap">
		<span class="glyphicon glyphicon-warning-sign text-danger loomexa-modal-warning-icon"></span>
	</div>

	<h4 class="loomexa-modal-title-text"><strong>Are you sure you want to delete this sale order?</strong></h4>

	<p id="confirmDeleteMessage" class="text-muted loomexa-modal-message">
		This action will mark the sale order and all related items as deleted.
	</p>
	</div>
			<div class="modal-footer loomexa-modal-footer-center">
				<button id="confirmDeleteCancel" type="button" class="btn btn-default btn-sm" data-dismiss="modal"><i class="fa fa-times"></i> Cancel</button>
				<button id="confirmDeleteConfirm" type="button" class="btn btn-danger btn-sm"><span class="glyphicon glyphicon-trash"></span> Delete Now</button>
			</div>

		</div>
	</div>
</div>
<div class="modal fade loomexa-modal" id="orderModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">

            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="orderModalTitle">Sale Order Details</h4>
            </div>

            <div class="modal-body" id="orderModalBody">
                <div class="text-center loomexa-modal-loading">
                    Loading...
                </div>
            </div>
             
        </div>
    </div>
</div> 

<div class="modal fade loomexa-modal" id="editSaleOrderModal" tabindex="-1" role="dialog" aria-labelledby="editSaleOrderLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg edit-sale-order-dialog" role="document">
        <div class="modal-content">
            <form name="edit_sale_order_form" method="post" action="{{ route('sale-order.update') }}">
                @csrf
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h3 class="modal-title" id="editSaleOrderLabel"><i class="fa fa-pencil-square-o"></i> Edit Sale Order</h3>
                    <p class="edit-sale-order-subtitle">Update order, customer, and delivery information from one place.</p>
                </div>
                <div class="modal-body edit-sale-order-body">
                    <input name="FId" id="edit_order_id" value="" type="hidden">

                    @php
                        $saleOrderSelectFields = [
                            'sale_order_type' => ['1' => 'Customer', '2' => 'Self'],
                            'sales_order' => ['direct' => 'Direct', 'agent' => 'Agent', 'email' => 'Email', 'phone' => 'Phone', 'whatsapp' => 'Whatsapp'],
                            'order_priority' => array_combine($priorityArr, $priorityArr),
                            'development_type' => ['Bulk' => 'Bulk', 'Sample' => 'Sample', 'JobWork' => 'JobWork'],
                        ];
                    @endphp

                    <div class="sale-order-edit-section">
                        <h4>Basic Sale Order Details</h4>
                        <div class="row">
                            <div class="col-sm-2 form-group">
                                <label>Order Type</label>
                                <select name="sale_order_type" id="edit_order_sale_order_type" class="form-control">
                                    <option value="">Select Order Type</option>
                                    @foreach ($saleOrderSelectFields['sale_order_type'] as $optionValue => $optionText)
                                        <option value="{{ $optionValue }}">{{ $optionText }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-sm-2 form-group">
                                <label>Loomexa Number</label>
                                <input type="text" name="lot_number" id="edit_order_lot_number" class="form-control" placeholder="Loomexa Number">
                            </div>

                            <div class="col-sm-2 form-group">
                                <label>Sale Order Number <span class="text-danger">*</span></label>
                                <input type="text" name="sale_order_number" id="edit_order_sale_order_number" class="form-control" placeholder="Sale Order Number">
                            </div>

                            <div class="col-sm-2 form-group">
                                <label>Sale Order Date <span class="text-danger">*</span></label>
                                <input type="text" name="sale_order_date" id="edit_order_sale_order_date" class="form-control loomexa-datepicker" placeholder="Sale Order Date">
                            </div>

                            <div class="col-sm-2 form-group">
                                <label>Sales Order <span class="text-danger">*</span></label>
                                <select name="sales_order" id="edit_order_sales_order" class="form-control" onchange="changeEditSaleOrder();">
                                    <option value="">Select Sales Order</option>
                                    @foreach ($saleOrderSelectFields['sales_order'] as $optionValue => $optionText)
                                        <option value="{{ $optionValue }}">{{ $optionText }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-sm-2 form-group" id="edit_sale_order_fromId">
                                <label id="edit_sale_order_from_label">Sale Order From</label>
                                <input type="text" name="sale_order_from" id="edit_order_sale_order_from" class="form-control" placeholder="Direct Order From">
                            </div>
							 <div class="col-sm-2 form-group" id="edit_agentId">
                                <label>Agent Name</label>
                                <input type="text" name="agent_name" id="edit_order_agent_name" class="form-control" placeholder="Agent Name">
                                <input type="hidden" name="ind_agent_id" id="edit_order_ind_agent_id">
                            </div>
                        </div>
                    </div>

                    <div class="sale-order-edit-section">
                        <h4>Employee, Customer & Delivery Details</h4>
                        <div class="row">
                            <div class="col-sm-3 form-group">
                                <label>Order By Employee <span class="text-danger">*</span></label>
                                <input type="text" name="employee_name" id="edit_order_employee_name" class="form-control" placeholder="Order By Employee">
                                <input type="hidden" name="order_by_employee" id="edit_order_order_by_employee">
                            </div>

                            <div class="col-sm-3 form-group">
                                <label>Development Type <span class="text-danger">*</span></label>
                                <select name="development_type" id="edit_order_development_type" class="form-control">
                                    <option value="">Select Development Type</option>
                                    @foreach ($saleOrderSelectFields['development_type'] as $optionValue => $optionText)
                                        <option value="{{ $optionValue }}">{{ $optionText }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-sm-3 form-group">
                                <label>Sale Order Priority <span class="text-danger">*</span></label>
                                <select name="order_priority" id="edit_order_order_priority" class="form-control">
                                    <option value="">Select Priority</option>
                                    @foreach ($saleOrderSelectFields['order_priority'] as $optionValue => $optionText)
                                        <option value="{{ $optionValue }}">{{ $optionText }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-sm-3 form-group">
                                <label>Customer Name <span class="text-danger">*</span></label>
                                <input type="text" name="customer_name" id="edit_order_customer_name" class="form-control" placeholder="Customer Name">
                                <input type="hidden" name="customer_id" id="edit_order_customer_id">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-sm-3 form-group">
                                <label>Phone</label>
                                <div id="edit_order_customer_phone" class="sale-order-edit-info-text">-</div>
                            </div>

                            <div class="col-sm-3 form-group">
                                <label>GSTIN</label>
                                <div id="edit_order_customer_gstin" class="sale-order-edit-info-text">-</div>
                            </div>
 
                        </div>

                        <div class="row">
                            <div class="col-sm-6 form-group">
                                <label>Billing Address</label>
                                <input type="hidden" name="billing_id" id="edit_order_billing_id">
                                <input type="hidden" name="billing_address" id="edit_order_billing_address">
                                <div id="editBillingAddressList" class="sale-order-address-list form-control sale-order-edit-address-box">
                                    <span class="text-muted">Billing address not selected.</span>
                                </div>
                            </div>

                            <div class="col-sm-6 form-group">
                                <label>Shipping Address</label>
                                <input type="hidden" name="shipping_id" id="edit_order_shipping_id">
                                <input type="hidden" name="shipping_address" id="edit_order_shipping_address">
                                <div id="editShippingAddressList" class="sale-order-address-list form-control sale-order-edit-address-box">
                                    <span class="text-muted">Shipping address not selected.</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer edit-sale-order-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal"><i class="fa fa-times"></i> Close</button>
                    <button type="submit" class="btn btn-success"><i class="fa fa-save"></i> Update Sale Order</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade loomexa-modal" id="editSaleOrderItemModal" tabindex="-1" role="dialog" aria-labelledby="editSaleOrderItemLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg edit-sale-order-item-dialog" role="document">
        <div class="modal-content">
            <form name="edit_sale_order_item_form" method="post" action="{{ route('sale-order.update-item') }}">
                @csrf
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h3 class="modal-title" id="editSaleOrderItemLabel"><i class="fa fa-cube"></i> Edit Sale Order Item</h3>
                    <p class="edit-sale-order-item-subtitle">Update fabric, quantity, rate, job, and delivery details.</p>
                </div>
                <div class="modal-body edit-sale-order-item-body">
                    <input name="FId" id="edit_sale_order_id" value="" type="hidden">
                    <input name="soItemId" id="edit_sale_order_item_id" value="" type="hidden">

                    @php
                        $saleOrderItemTextareas = ['remarks', 'cancel_reason', 'dlvr_cleared_reason'];
                        $saleOrderItemDateFields = ['expect_delivery_date', 'dlvr_clear_date', 'in_packaging_send_date', 'created_at', 'modified_at'];
                        $saleOrderItemSelectFields = [
                            'unit_type_id' => $unitTypes->pluck('unit_type_name', 'unit_type_id')->toArray(),
                            'order_item_priority' => array_combine($priorityArr, $priorityArr),
                            'coating_type' => $coatings->pluck('name', 'code')->toArray(),
                            'development_type' => ['Bulk' => 'Bulk', 'Sample' => 'Sample', 'JobWork' => 'JobWork'],
                            'is_deleted' => ['0' => 'No', '1' => 'Yes'],
                            'is_return' => ['No' => 'No', 'Yes' => 'Yes'],
                        ];
                        $saleOrderItemFields = [
                            'unit_type_id', 'order_item_priority', 'pcs', 'cut', 'meter',
                            'rate', 'amount',
                            'grey_quality', 'dyeing_color', 'coating_type', 'extra_job', 'print_job',
                            'packing_roll_length', 'final_dispatch_width', 'tube_width', 'development_type',
                            'expect_delivery_date', 'remarks',
                        ];
                    @endphp

                    <div class="sale-order-item-edit-section">
                        <h4>Item Information</h4>
                        <div class="row">
                            <div class="col-sm-3 form-group">
                                <label>Item Name</label>
                                <input type="text" name="item_name" id="edit_item_item_name" class="form-control" placeholder="Item Name">
                                <input type="hidden" name="item_id" id="edit_item_item_id">
                            </div>
                         
                            @foreach ($saleOrderItemFields as $fieldName)
                                <div class="{{ in_array($fieldName, $saleOrderItemTextareas) ? 'col-sm-6' : 'col-sm-3' }} form-group">
                                    <label>{{ ucwords(str_replace('_', ' ', $fieldName)) }}</label>
                                    @if (isset($saleOrderItemSelectFields[$fieldName]))
                                        <select name="{{ $fieldName }}" id="edit_item_{{ $fieldName }}" class="form-control">
                                            <option value="">Select {{ ucwords(str_replace('_', ' ', $fieldName)) }}</option>
                                            @foreach ($saleOrderItemSelectFields[$fieldName] as $optionValue => $optionText)
                                                <option value="{{ $optionValue }}">{{ $optionText }}</option>
                                            @endforeach
                                        </select>
                                    @elseif (in_array($fieldName, $saleOrderItemTextareas))
                                        <textarea name="{{ $fieldName }}" id="edit_item_{{ $fieldName }}" class="form-control" rows="3"></textarea>
                                    @else
                                        <input type="text" name="{{ $fieldName }}" id="edit_item_{{ $fieldName }}" class="form-control {{ in_array($fieldName, $saleOrderItemDateFields) ? 'loomexa-datepicker' : '' }}">
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="modal-footer edit-sale-order-item-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal"><i class="fa fa-times"></i> Close</button>
                    <button type="submit" class="btn btn-success"><i class="fa fa-save"></i> Update Item</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade loomexa-modal" id="cancelSaleOrderItemModal" tabindex="-1" role="dialog" aria-labelledby="cancelSaleOrderItemLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form name="cancel_sale_order_item_form" method="post" action="{{ route('cancelSaleOrderItem') }}">
                @csrf
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h3 class="modal-title" id="cancelSaleOrderItemLabel">Are you sure you want to remove this item from your order list?</h3>
                </div>
                <div class="modal-body">
                    <h3 align="center">You won't be able to revert this!</h3>
                    <input name="FId" id="cancel_sale_order_id" value="" type="hidden">
                    <input name="soItemId" id="cancel_sale_order_item_id" value="" type="hidden">
                    <fieldset>
                        <div class="col-md-12 form-group">
                            <label class="control-label">Your Comment for Cancellation</label>
                            <input type="text" placeholder="Comment" required class="form-control" name="cancel_reason" id="cancel_reason" value="">
                        </div>
                    </fieldset>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-success">Save changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

@include('frontend.common.footer')
</div>
@include('frontend.common.footerscript')

<script type="text/javascript">
	var siteUrl = "{{ url('/') }}";

    $(document).on('click', '.openOrderModal', function () {
        var saleOrderId = $(this).data('id');
        var title = $(this).data('title');

        $('#orderModalTitle').text('Sale Order No: ' + title);
        $('#orderModalBody').html('<div class="text-center loomexa-modal-loading">Loading...</div>');
        $('#orderModal').modal('show');

        $.ajax({
            url: "<?php echo url('/sale-order/ajax-details'); ?>/" + saleOrderId,
            type: "GET",
            success: function (response) {
                $('#orderModalBody').html(response);
            },
            error: function () {
                $('#orderModalBody').html('<div class="alert alert-danger">Data load nahi ho paya.</div>');
            }
        });
    });

    $(document).on('change', '.item-check', function () {
        var target = $(this).data('target');

        if ($(this).is(':checked')) {
            $(target).show();
        } else {
            $(target).hide();
            $(target).find('input[type="number"]').val('');
        }
    });

	$(document).on('click', '.cancelSaleOrderItemBtn', function () {
		$('#cancel_sale_order_id').val($(this).data('sale-order-id'));
		$('#cancel_sale_order_item_id').val($(this).data('sale-order-item-id'));
		$('#cancel_reason').val('');
	});

	$(document).on('click', '.editSaleOrderBtn', function () {
		$('#edit_order_id').val($(this).data('sale-order-id'));
		var jsonId = $(this).data('json-id');
		var saleOrderData = JSON.parse($('#' + jsonId).html());

		$.each(saleOrderData, function (fieldName, fieldValue) {
			$('#edit_order_' + fieldName).val(formatEditDateValue(fieldValue));
		});

		$('#edit_order_customer_name').val(saleOrderData.customer_name);
		$('#edit_order_customer_id').val(saleOrderData.customer_id);
		$('#edit_order_customer_phone').text(saleOrderData.customer_phone || '-');
		$('#edit_order_customer_gstin').text(saleOrderData.customer_gstin || '-');
		$('#edit_order_employee_name').val(saleOrderData.employee_name);
		$('#edit_order_order_by_employee').val(saleOrderData.order_by_employee);
		$('#edit_order_agent_name').val(saleOrderData.agent_name);
		$('#edit_order_ind_agent_id').val(saleOrderData.ind_agent_id);

		getEditCustomerAddress(saleOrderData.customer_id, saleOrderData.billing_id, saleOrderData.shipping_id, saleOrderData.billing_address, saleOrderData.shipping_address);
		changeEditSaleOrder();
	});

	$(document).on('click', '.editSaleOrderItemBtn', function () {
		$('#edit_sale_order_id').val($(this).data('sale-order-id'));
		$('#edit_sale_order_item_id').val($(this).data('sale-order-item-id'));
		var jsonId = $(this).data('json-id');
		var saleOrderItemData = JSON.parse($('#' + jsonId).html());

		$.each(saleOrderItemData, function (fieldName, fieldValue) {
			$('#edit_item_' + fieldName).val(formatEditDateValue(fieldValue));
		});

		calculateEditItemAmount();
	});

	$(document).on('keyup change', '#edit_item_meter, #edit_item_rate', function () {
		calculateEditItemAmount();
	});

	function calculateEditItemAmount()
	{
		var meter = parseFloat($('#edit_item_meter').val()) || 0;
		var rate = parseFloat($('#edit_item_rate').val()) || 0;
		var amount = meter * rate;
		$('#edit_item_amount').val(amount.toFixed(2));
	}

	function changeEditSaleOrder()
	{
		var salesOrder = $('#edit_order_sales_order').val();
		var placeholder = 'Enter Sale Order From';
		var labelText = 'Sale Order From';

		if (salesOrder === 'agent') {
			$('#edit_agentId').show();
			$('#edit_sale_order_fromId').hide();
			$('#edit_order_sale_order_from').val('');
			return;
		}

		$('#edit_agentId').hide();
		$('#edit_sale_order_fromId').show();
		$('#edit_order_ind_agent_id').val('');
		$('#edit_order_agent_name').val('');

		if (salesOrder === 'direct') {
			placeholder = 'Enter Customer or Contact Person Name';
			labelText = 'Direct Order From';
		} else if (salesOrder === 'email') {
			placeholder = 'Enter Email Address';
			labelText = 'Email Address';
		} else if (salesOrder === 'phone') {
			placeholder = 'Enter Phone Number';
			labelText = 'Phone Number';
		} else if (salesOrder === 'whatsapp') {
			placeholder = 'Enter WhatsApp Number';
			labelText = 'WhatsApp Number';
		}

		$('#edit_order_sale_order_from').attr('placeholder', placeholder);
		$('#edit_sale_order_from_label').text(labelText);
	}

	function formatEditDateValue(fieldValue)
	{
		if (fieldValue == null) {
			return '';
		}

		if (typeof fieldValue === 'string' && fieldValue.length >= 10 && fieldValue.substring(4, 5) === '-' && fieldValue.substring(7, 8) === '-') {
			return fieldValue.substring(8, 10) + '-' + fieldValue.substring(5, 7) + '-' + fieldValue.substring(0, 4) + fieldValue.substring(10);
		}

		return fieldValue;
	}

	function selectEditBillingAddress(addressId, addressText) {
		$("#edit_order_billing_id").val(addressId);
		$("#edit_order_billing_address").val(addressText);
	}

	function selectEditShippingAddress(addressId, addressText) {
		$("#edit_order_shipping_id").val(addressId);
		$("#edit_order_shipping_address").val(addressText);
	}

	function getEditCustomerAddress(individualId, billingAddressId, shippingAddressId, oldBillingAddress, oldShippingAddress) {
		if (!individualId) {
			$("#editBillingAddressList").html('<span class="text-muted">Billing address not selected.</span>');
			$("#editShippingAddressList").html('<span class="text-muted">Shipping address not selected.</span>');
			return;
		}

		$.ajax({
			type: "GET",
			url: siteUrl + "/customer-addresses",
			data: { individual_id: individualId },
			dataType: "json",
			success: function(data) {
				var billingHtml = "";
				var shippingHtml = "";

				if (data.billing_addresses.length == 0) {
					billingHtml = '<p class="text-muted">No billing address found.</p>';
					selectEditBillingAddress(billingAddressId || '', oldBillingAddress || '');
				}

				$.each(data.billing_addresses, function(index, row) {
					var checked = "";
					if (row.id == billingAddressId || (billingAddressId == "" && index == 0)) {
						checked = "checked";
						selectEditBillingAddress(row.id, row.address);
					}

					billingHtml += '<label class="sale-order-address-option">';
					billingHtml += '<input type="radio" name="edit_billing_address_radio" value="' + row.id + '" data-address="' + row.address.replace(/"/g, '&quot;') + '" ' + checked + '> ';
					billingHtml += '<span>' + row.address + '</span>';
					billingHtml += '</label>';
				});

				if (data.shipping_addresses.length == 0) {
					shippingHtml = '<p class="text-muted">No shipping address found.</p>';
					selectEditShippingAddress(shippingAddressId || '', oldShippingAddress || '');
				}

				$.each(data.shipping_addresses, function(index, row) {
					var checked = "";
					if (row.id == shippingAddressId || (shippingAddressId == "" && index == 0)) {
						checked = "checked";
						selectEditShippingAddress(row.id, row.address);
					}

					shippingHtml += '<label class="sale-order-address-option">';
					shippingHtml += '<input type="radio" name="edit_shipping_address_radio" value="' + row.id + '" data-address="' + row.address.replace(/"/g, '&quot;') + '" ' + checked + '> ';
					shippingHtml += '<span>' + row.address + '</span>';
					shippingHtml += '</label>';
				});

				$("#editBillingAddressList").html(billingHtml);
				$("#editShippingAddressList").html(shippingHtml);
			}
		});
	}

	$(document).on("change", "input[name='edit_billing_address_radio']", function() {
		selectEditBillingAddress($(this).val(), $(this).data("address"));
	});

	$(document).on("change", "input[name='edit_shipping_address_radio']", function() {
		selectEditShippingAddress($(this).val(), $(this).data("address"));
	});

	$("#edit_order_customer_name").autocomplete({
		minLength: 0,
		source: siteUrl + "/list_customer",
		focus: function(event, ui) {
			$("#edit_order_customer_name").val(ui.item.name);
			return false;
		},
		select: function(event, ui) {
			$("#edit_order_customer_id").val(ui.item.id);
			$("#edit_order_customer_name").val(ui.item.name);
			$("#edit_order_customer_phone").text(ui.item.phone || "-");
			$("#edit_order_customer_gstin").text(ui.item.gstin || "-");
			getEditCustomerAddress(ui.item.id, "", "", "", "");
			return false;
		}
	}).autocomplete("instance")._renderItem = function(ul, item) {
		return $("<li>").append("<div>" + item.name + "<br> GSTIN - " + (item.gstin || "") + "</div>").appendTo(ul);
	};

	$("#edit_order_employee_name").autocomplete({
		minLength: 0,
		source: function(request, response) {
			$.ajax({
				url: siteUrl + "/list_individual",
				dataType: "json",
				data: { term: request.term, type: "employee" },
				success: function(data) { response(data); }
			});
		},
		focus: function(event, ui) {
			$("#edit_order_employee_name").val(ui.item.name);
			return false;
		},
		select: function(event, ui) {
			$("#edit_order_order_by_employee").val(ui.item.id);
			$("#edit_order_employee_name").val(ui.item.name);
			return false;
		}
	}).autocomplete("instance")._renderItem = function(ul, item) {
		return $("<li>").append("<div>" + item.name + "</div>").appendTo(ul);
	};

	$("#edit_order_agent_name").autocomplete({
		minLength: 0,
		source: function(request, response) {
			$.ajax({
				url: siteUrl + "/list_individual",
				dataType: "json",
				data: { term: request.term, type: "agents" },
				success: function(data) { response(data); }
			});
		},
		focus: function(event, ui) {
			$("#edit_order_agent_name").val(ui.item.name);
			return false;
		},
		select: function(event, ui) {
			$("#edit_order_ind_agent_id").val(ui.item.id);
			$("#edit_order_agent_name").val(ui.item.name);
			return false;
		}
	}).autocomplete("instance")._renderItem = function(ul, item) {
		return $("<li>").append("<div>" + item.name + "</div>").appendTo(ul);
	};

	$("#edit_item_item_name").autocomplete({
		minLength: 0,
		source: siteUrl + "/fabric_list_item",
		focus: function(event, ui) {
			$("#edit_item_item_name").val(ui.item.item_name);
			return false;
		},
		select: function(event, ui) {
			$("#edit_item_item_id").val(ui.item.item_id);
			$("#edit_item_item_name").val(ui.item.item_name);
			$("#edit_item_grey_quality").val(ui.item.internal_item_name);
			$("#edit_item_unit_type_id").val(ui.item.unit_type_id);
			if (ui.item.sale_rate) {
				$("#edit_item_rate").val(ui.item.sale_rate);
			} else {
				$("#edit_item_rate").val(ui.item.unit_price);
			}
			calculateEditItemAmount();
			return false;
		}
	}).autocomplete("instance")._renderItem = function(ul, item) {
		return $("<li>").append("<div>" + item.item_name + "<br> Item Code: " + (item.item_code || "") + "<br> Internal Name: " + (item.internal_item_name || "") + "</div>").appendTo(ul);
	};
</script> 

<script type="text/javascript">
var siteUrl 			= "<?php echo url('/'); ?>";
var deleteCandidateId 	= null;
var deleteCandidateRowId = null;

// Open the confirmation modal for the selected sale order id
function deleteSaleOrder(id, rowId)
{
	deleteCandidateId = id;
	deleteCandidateRowId = rowId;

	$('#confirmDeleteMessage').text('This sale order and its related items will be marked as deleted.');
	$('#confirmDeleteConfirm').prop('disabled', false).html('<span class="glyphicon glyphicon-trash"></span> Delete Now');
	$('#confirmDeleteModal').modal('show');
}

// When user confirms deletion
jQuery(document).on('click', '#confirmDeleteConfirm', function() {
	if (!deleteCandidateId) {
		$('#confirmDeleteModal').modal('hide');
		alert('Invalid sale order identifier.');
		return;
	}

	jQuery.ajax({
		type: "POST",
		url: siteUrl + '/ajax_script/deleteSaleOrder',
		data: {
			_token: "<?php echo csrf_token(); ?>",
			FId: deleteCandidateId
		},
		headers: {
			'X-CSRF-TOKEN': '<?php echo csrf_token(); ?>'
		},
		cache: false,
		dataType: 'json',
		success: function(res) {
			$('#confirmDeleteModal').modal('hide');
			if (res.status === 1) {
				// hide the row visually (adjust selector if different)
				$("#Mid" + deleteCandidateRowId).hide();
				alert(res.message || 'Deleted successfully.');
			} else {
				alert(res.message || 'Unable to delete this sale order.');
			}
		},
		error: function(xhr) {
			$('#confirmDeleteModal').modal('hide');
			var message = 'An unexpected error occurred. Please try again or contact support.';
			if (xhr.responseJSON && xhr.responseJSON.message) {
				message = xhr.responseJSON.message;
			}
			alert(message);
		}
	});
});

</script>



<script>
var siteUrl = "{{ url('/') }}";

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
	source: function(request, response) {
		$.ajax({
			url: siteUrl + "/fabric_list_item",
			dataType: "json",
			data: { term: request.term },
			success: function(data) { response(data); }
		});
	},
	focus: function(event, ui) {
		$("#item_search").val(ui.item.item_name);
		return false;
	},
	select: function(event, ui) {
		$("#item_search").val(ui.item.item_name);
		return false;
	}
}).autocomplete("instance")._renderItem = function(ul, item) {
	return $("<li>").append($("<div>").text(item.item_name)).appendTo(ul);
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

$("#goToPageButton").on("click", function() {
	var pageInput = $("#manualPageInput").val();
	var lastPage = {{ $saleOrders->lastPage() }};

	if (pageInput > 0 && pageInput <= lastPage) {
		var baseUrl = window.location.href.split("?")[0];
		var params = new URLSearchParams(window.location.search);
		params.set("page", pageInput);
		window.location.href = baseUrl + "?" + params.toString();
	}
});
</script>
</body>

</html>
