<!DOCTYPE html>
<html lang="en">

<head>
	@include('admin.common.head')
</head>

<body class="hold-transition sidebar-mini">
	<div id="preloader">
		<div id="status"></div>
	</div>
	<div class="wrapper">
		@include('admin.common.header')
		@include('admin.common.sidebar')
		<div class="content-wrapper">
			<section class="content-header">
				<div class="header-icon"><i class="fa fa-building"></i></div>
				<div class="header-title">
					<h1>Edit Company</h1><small>Company details</small>
				</div>
			</section>
			<section class="content">
				<div class="row">
					<div class="col-sm-12">
						<div class="panel panel-bd lobidrag">
							<div class="panel-heading"><a href="{{ route('admin.companies.index') }}" class="btn btn-add"><i class="fa fa-list"></i> Company Details</a></div>
							<div class="panel-body">
								{!! display_message('message') !!}
								<form method="POST" action="{{ route('admin.companies.update', enc($company->id)) }}">
									@csrf
									@method('PUT')
									<div class="border-bottom">
										<h4>Basic Company Details</h4>
									</div>
									<div class="row">
										<div class="col-sm-3">
											<div class="form-group"><label>Company Code</label><input type="text" name="company_code" value="{{ old('company_code', $company->company_code) }}" class="form-control"></div>
										</div>
										<div class="col-sm-3">
											<div class="form-group"><label>Name <span class="required">*</span></label><input type="text" name="name" value="{{ old('name', $company->name) }}" class="form-control" required></div>
										</div>
										<div class="col-sm-3">
											<div class="form-group"><label>Legal Name</label><input type="text" name="legal_name" value="{{ old('legal_name', $company->legal_name) }}" class="form-control"></div>
										</div>
										<div class="col-sm-3">
											<div class="form-group"><label>Trade Name</label><input type="text" name="trade_name" value="{{ old('trade_name', $company->trade_name) }}" class="form-control"></div>
										</div>
										<div class="col-sm-12">
											<div class="form-group"><label>Company Description</label><textarea name="company_description" class="form-control" rows="3">{{ old('company_description', $company->company_description) }}</textarea></div>
										</div>
									</div>

									<div class="border-bottom">
										<h4>Registration Details</h4>
									</div>
									<div class="row">
										<div class="col-sm-3">
											<div class="form-group"><label>Registration No</label><input type="text" name="registration_no" value="{{ old('registration_no', $company->registration_no) }}" class="form-control"></div>
										</div>
										<div class="col-sm-3">
											<div class="form-group"><label>PAN No</label><input type="text" name="pan_no" value="{{ old('pan_no', $company->pan_no) }}" class="form-control"></div>
										</div>
										<div class="col-sm-3">
											<div class="form-group"><label>TAN No</label><input type="text" name="tan_no" value="{{ old('tan_no', $company->tan_no) }}" class="form-control"></div>
										</div>
										<div class="col-sm-3">
											<div class="form-group"><label>GSTIN</label><input type="text" name="gstin" value="{{ old('gstin', $company->gstin) }}" class="form-control"></div>
										</div>
									</div>

									<div class="border-bottom">
										<h4>Contact Details</h4>
									</div>
									<div class="row">
										<div class="col-sm-3">
											<div class="form-group"><label>Email</label><input type="email" name="email" value="{{ old('email', $company->email) }}" class="form-control"></div>
										</div>
										<div class="col-sm-3">
											<div class="form-group"><label>Alternate Email</label><input type="email" name="alternate_email" value="{{ old('alternate_email', $company->alternate_email) }}" class="form-control"></div>
										</div>
										<div class="col-sm-3">
											<div class="form-group"><label>Phone</label><input type="text" name="phone" value="{{ old('phone', $company->phone) }}" class="form-control"></div>
										</div>
										<div class="col-sm-3">
											<div class="form-group"><label>Alternate Phone</label><input type="text" name="alternate_phone" value="{{ old('alternate_phone', $company->alternate_phone) }}" class="form-control"></div>
										</div>
										<div class="col-sm-3">
											<div class="form-group"><label>Mobile</label><input type="text" name="mobile" value="{{ old('mobile', $company->mobile) }}" class="form-control"></div>
										</div>
										<div class="col-sm-3">
											<div class="form-group"><label>Whatsapp No</label><input type="text" name="whatsapp_no" value="{{ old('whatsapp_no', $company->whatsapp_no) }}" class="form-control"></div>
										</div>
										<div class="col-sm-3">
											<div class="form-group"><label>Website</label><input type="text" name="website" value="{{ old('website', $company->website) }}" class="form-control"></div>
										</div>
										<div class="col-sm-3">
											<div class="form-group"><label>Contact Person Name</label><input type="text" name="contact_person_name" value="{{ old('contact_person_name', $company->contact_person_name) }}" class="form-control"></div>
										</div>
										<div class="col-sm-3">
											<div class="form-group"><label>Contact Person Designation</label><input type="text" name="contact_person_designation" value="{{ old('contact_person_designation', $company->contact_person_designation) }}" class="form-control"></div>
										</div>
										<div class="col-sm-3">
											<div class="form-group"><label>Contact Person Mobile</label><input type="text" name="contact_person_mobile" value="{{ old('contact_person_mobile', $company->contact_person_mobile) }}" class="form-control"></div>
										</div>
										<div class="col-sm-3">
											<div class="form-group"><label>Contact Person Email</label><input type="email" name="contact_person_email" value="{{ old('contact_person_email', $company->contact_person_email) }}" class="form-control"></div>
										</div>
									</div>

									<div class="border-bottom">
										<h4>Registered Address</h4>
									</div>
									<div class="row">
										<div class="col-sm-6">
											<div class="form-group"><label>Address 1</label><textarea name="address_1" class="form-control" rows="2">{{ old('address_1', $company->address_1) }}</textarea></div>
										</div>
										<div class="col-sm-6">
											<div class="form-group"><label>Address 2</label><textarea name="address_2" class="form-control" rows="2">{{ old('address_2', $company->address_2) }}</textarea></div>
										</div>
										<div class="col-sm-3">
											<div class="form-group"><label>Landmark</label><input type="text" name="landmark" value="{{ old('landmark', $company->landmark) }}" class="form-control"></div>
										</div>
										<div class="col-sm-3">
											<div class="form-group"><label>State</label><select name="state_id" class="form-control">
													<option value="">Select State</option>@foreach ($states as $state)<option value="{{ $state->id }}" @selected((string) old('state_id', $company->state_id) === (string) $state->id)>{{ $state->name }}</option>@endforeach
												</select></div>
										</div>
										<div class="col-sm-3">
											<div class="form-group"><label>City Name</label><input type="text" name="city_name" value="{{ old('city_name', $company->city_name) }}" class="form-control"></div>
										</div>
										<div class="col-sm-3">
											<div class="form-group"><label>District Name</label><input type="text" name="district_name" value="{{ old('district_name', $company->district_name) }}" class="form-control"></div>
										</div>
										<div class="col-sm-3">
											<div class="form-group"><label>Pincode</label><input type="text" name="pincode" value="{{ old('pincode', $company->pincode) }}" class="form-control"></div>
										</div>
									</div>

									<div class="border-bottom">
										<h4>Billing Address</h4>
									</div>
									<div class="row">
										<div class="col-sm-6">
											<div class="form-group"><label>Billing Address 1</label><textarea name="billing_address_1" class="form-control" rows="2">{{ old('billing_address_1', $company->billing_address_1) }}</textarea></div>
										</div>
										<div class="col-sm-6">
											<div class="form-group"><label>Billing Address 2</label><textarea name="billing_address_2" class="form-control" rows="2">{{ old('billing_address_2', $company->billing_address_2) }}</textarea></div>
										</div>
										<div class="col-sm-3">
											<div class="form-group"><label>Billing State</label><select name="billing_state_id" class="form-control">
													<option value="">Select State</option>@foreach ($states as $state)<option value="{{ $state->id }}" @selected((string) old('billing_state_id', $company->billing_state_id) === (string) $state->id)>{{ $state->name }}</option>@endforeach
												</select></div>
										</div>
										<div class="col-sm-3">
											<div class="form-group"><label>Billing City Name</label><input type="text" name="billing_city_name" value="{{ old('billing_city_name', $company->billing_city_name) }}" class="form-control"></div>
										</div>
										<div class="col-sm-3">
											<div class="form-group"><label>Billing Pincode</label><input type="text" name="billing_pincode" value="{{ old('billing_pincode', $company->billing_pincode) }}" class="form-control"></div>
										</div>
									</div>

									<div class="border-bottom">
										<h4>Bank Details</h4>
									</div>
									<div class="row">
										<div class="col-sm-3">
											<div class="form-group"><label>Bank Name</label><input type="text" name="bank_name" value="{{ old('bank_name', $company->bank_name) }}" class="form-control"></div>
										</div>
										<div class="col-sm-3">
											<div class="form-group"><label>Bank Branch Name</label><input type="text" name="bank_branch_name" value="{{ old('bank_branch_name', $company->bank_branch_name) }}" class="form-control"></div>
										</div>
										<div class="col-sm-3">
											<div class="form-group"><label>Account Holder Name</label><input type="text" name="bank_account_holder_name" value="{{ old('bank_account_holder_name', $company->bank_account_holder_name) }}" class="form-control"></div>
										</div>
										<div class="col-sm-3">
											<div class="form-group"><label>Account No</label><input type="text" name="bank_account_no" value="{{ old('bank_account_no', $company->bank_account_no) }}" class="form-control"></div>
										</div>
										<div class="col-sm-3">
											<div class="form-group"><label>Account Type</label><input type="text" name="bank_account_type" value="{{ old('bank_account_type', $company->bank_account_type) }}" class="form-control"></div>
										</div>
										<div class="col-sm-3">
											<div class="form-group"><label>IFSC Code</label><input type="text" name="bank_ifsc_code" value="{{ old('bank_ifsc_code', $company->bank_ifsc_code) }}" class="form-control"></div>
										</div>
										<div class="col-sm-3">
											<div class="form-group"><label>MICR Code</label><input type="text" name="bank_micr_code" value="{{ old('bank_micr_code', $company->bank_micr_code) }}" class="form-control"></div>
										</div>
										<div class="col-sm-3">
											<div class="form-group"><label>SWIFT Code</label><input type="text" name="bank_swift_code" value="{{ old('bank_swift_code', $company->bank_swift_code) }}" class="form-control"></div>
										</div>
										<div class="col-sm-3">
											<div class="form-group"><label>UPI Id</label><input type="text" name="bank_upi_id" value="{{ old('bank_upi_id', $company->bank_upi_id) }}" class="form-control"></div>
										</div>
									</div>

									<div class="border-bottom">
										<h4>Invoice And Accounting Settings</h4>
									</div>
									<div class="row">
										<div class="col-sm-3">
											<div class="form-group"><label>Invoice Prefix</label><input type="text" name="invoice_prefix" value="{{ old('invoice_prefix', $company->invoice_prefix) }}" class="form-control"></div>
										</div>
										<div class="col-sm-3">
											<div class="form-group"><label>Quotation Prefix</label><input type="text" name="quotation_prefix" value="{{ old('quotation_prefix', $company->quotation_prefix) }}" class="form-control"></div>
										</div>
										<div class="col-sm-3">
											<div class="form-group"><label>Purchase Prefix</label><input type="text" name="purchase_prefix" value="{{ old('purchase_prefix', $company->purchase_prefix) }}" class="form-control"></div>
										</div>
										<div class="col-sm-3">
											<div class="form-group"><label>Currency Code</label><input type="text" name="currency_code" value="{{ old('currency_code', $company->currency_code) }}" class="form-control"></div>
										</div>
										<div class="col-sm-3">
											<div class="form-group"><label>Currency Symbol</label><input type="text" name="currency_symbol" value="{{ old('currency_symbol', $company->currency_symbol) }}" class="form-control"></div>
										</div>
										<div class="col-sm-3">
											<div class="form-group"><label>Timezone</label><input type="text" name="timezone" value="{{ old('timezone', $company->timezone) }}" class="form-control"></div>
										</div>
										<div class="col-sm-3">
											<div class="form-group"><label>Date Format</label><input type="text" name="date_format" value="{{ old('date_format', $company->date_format) }}" class="form-control"></div>
										</div>
										<div class="col-sm-3">
											<div class="form-group"><label>Decimal Places</label><input type="number" name="decimal_places" value="{{ old('decimal_places', $company->decimal_places) }}" class="form-control"></div>
										</div>
										<div class="col-sm-3">
											<div class="form-group"><label>Default Tax %</label><input type="number" step="0.01" name="default_tax_percentage" value="{{ old('default_tax_percentage', $company->default_tax_percentage) }}" class="form-control"></div>
										</div>
										<div class="col-sm-3">
											<div class="form-group"><label>Credit Limit</label><input type="number" step="0.01" name="credit_limit" value="{{ old('credit_limit', $company->credit_limit) }}" class="form-control"></div>
										</div>
										<div class="col-sm-3">
											<div class="form-group"><label>Default Credit Days</label><input type="number" name="default_credit_days" value="{{ old('default_credit_days', $company->default_credit_days) }}" class="form-control"></div>
										</div>
										<div class="col-sm-6">
											<div class="form-group"><label>Invoice Terms</label><textarea name="invoice_terms" class="form-control" rows="3">{{ old('invoice_terms', $company->invoice_terms) }}</textarea></div>
										</div>
										<div class="col-sm-6">
											<div class="form-group"><label>Invoice Footer</label><textarea name="invoice_footer" class="form-control" rows="3">{{ old('invoice_footer', $company->invoice_footer) }}</textarea></div>
										</div>
									</div>

									<div class="border-bottom">
										<h4>Company Files</h4>
									</div>
									<div class="row">
										<div class="col-sm-3">
											<div class="form-group"><label>Logo</label><input type="text" name="logo" value="{{ old('logo', $company->logo) }}" class="form-control"></div>
										</div>
										<div class="col-sm-3">
											<div class="form-group"><label>Favicon</label><input type="text" name="favicon" value="{{ old('favicon', $company->favicon) }}" class="form-control"></div>
										</div>
										<div class="col-sm-3">
											<div class="form-group"><label>Letterhead</label><input type="text" name="letterhead" value="{{ old('letterhead', $company->letterhead) }}" class="form-control"></div>
										</div>
										<div class="col-sm-3">
											<div class="form-group"><label>Signature Image</label><input type="text" name="signature_image" value="{{ old('signature_image', $company->signature_image) }}" class="form-control"></div>
										</div>
										<div class="col-sm-3">
											<div class="form-group"><label>Company Stamp</label><input type="text" name="company_stamp" value="{{ old('company_stamp', $company->company_stamp) }}" class="form-control"></div>
										</div>
										<div class="col-sm-3">
											<div class="form-group"><label>QR Code Image</label><input type="text" name="qr_code_image" value="{{ old('qr_code_image', $company->qr_code_image) }}" class="form-control"></div>
										</div>
									</div>

									<div class="border-bottom">
										<h4>Additional Details</h4>
									</div>
									<div class="row">
										<div class="col-sm-3">
											<div class="form-group"><label>Latitude</label><input type="text" name="latitude" value="{{ old('latitude', $company->latitude) }}" class="form-control"></div>
										</div>
										<div class="col-sm-3">
											<div class="form-group"><label>Longitude</label><input type="text" name="longitude" value="{{ old('longitude', $company->longitude) }}" class="form-control"></div>
										</div>
										<div class="col-sm-6">
											<div class="form-group"><label>Terms And Conditions</label><textarea name="terms_and_conditions" class="form-control" rows="3">{{ old('terms_and_conditions', $company->terms_and_conditions) }}</textarea></div>
										</div>
										<div class="col-sm-6">
											<div class="form-group"><label>Remarks</label><textarea name="remarks" class="form-control" rows="3">{{ old('remarks', $company->remarks) }}</textarea></div>
										</div>
										<div class="col-sm-3">
											<div class="form-group"><label>Status</label><select name="status" class="form-control">
													@include('admin.common.status-options', ['selectedStatus' => $company->status])
												</select></div>
										</div>
									</div>
									<div class="reset-button"><a href="{{ route('admin.companies.index') }}" class="btn btn-warning">Cancel</a> <button type="submit" class="btn btn-success">Save</button></div>
								</form>
							</div>
						</div>
					</div>
				</div>
			</section>
		</div>
		@include('admin.common.footer')
	</div>
	@include('admin.common.formfooterscript')
</body>

</html>
