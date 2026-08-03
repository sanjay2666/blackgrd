<!DOCTYPE html>
<html lang="en">
<head>
@include('admin.common.head')
</head>
<body class="hold-transition sidebar-mini">
    <div id="preloader"><div id="status"></div></div>
    <div class="wrapper">
        @include('admin.common.header')
        @include('admin.common.sidebar')
        <div class="content-wrapper">
            <section class="content-header"><div class="header-icon"><i class="fa fa-building"></i></div><div class="header-title"><h1>Add Company</h1><small>Company details</small></div></section>
            <section class="content"><div class="row"><div class="col-sm-12"><div class="panel panel-bd lobidrag">
                <div class="panel-heading"><a href="{{ route('admin.companies.index') }}" class="btn btn-add"><i class="fa fa-list"></i> Company Details</a></div>
                <div class="panel-body">
                    {!! display_message('message') !!}
                    <form method="POST" action="{{ route('admin.companies.store') }}">
                        @csrf
                        <div class="border-bottom"><h4>Basic Company Details</h4></div>
                        <div class="row">
                            <div class="col-sm-3"><div class="form-group"><label>Company Code</label><input type="text" name="company_code" value="{{ old('company_code') }}" class="form-control"></div></div>
                            <div class="col-sm-3"><div class="form-group"><label>Name <span class="required">*</span></label><input type="text" name="name" value="{{ old('name') }}" class="form-control" required></div></div>
                            <div class="col-sm-3"><div class="form-group"><label>Legal Name</label><input type="text" name="legal_name" value="{{ old('legal_name') }}" class="form-control"></div></div>
                            <div class="col-sm-3"><div class="form-group"><label>Trade Name</label><input type="text" name="trade_name" value="{{ old('trade_name') }}" class="form-control"></div></div>
                            <div class="col-sm-12"><div class="form-group"><label>Company Description</label><textarea name="company_description" class="form-control" rows="3">{{ old('company_description') }}</textarea></div></div>
                        </div>

                        <div class="border-bottom"><h4>Registration Details</h4></div>
                        <div class="row">
                            <div class="col-sm-3"><div class="form-group"><label>Registration No</label><input type="text" name="registration_no" value="{{ old('registration_no') }}" class="form-control"></div></div>
                            <div class="col-sm-3"><div class="form-group"><label>PAN No</label><input type="text" name="pan_no" value="{{ old('pan_no') }}" class="form-control"></div></div>
                            <div class="col-sm-3"><div class="form-group"><label>TAN No</label><input type="text" name="tan_no" value="{{ old('tan_no') }}" class="form-control"></div></div>
                            <div class="col-sm-3"><div class="form-group"><label>GSTIN</label><input type="text" name="gstin" value="{{ old('gstin') }}" class="form-control"></div></div>
                        </div>

                        <div class="border-bottom"><h4>Contact Details</h4></div>
                        <div class="row">
                            <div class="col-sm-3"><div class="form-group"><label>Email</label><input type="email" name="email" value="{{ old('email') }}" class="form-control"></div></div>
                            <div class="col-sm-3"><div class="form-group"><label>Alternate Email</label><input type="email" name="alternate_email" value="{{ old('alternate_email') }}" class="form-control"></div></div>
                            <div class="col-sm-3"><div class="form-group"><label>Phone</label><input type="text" name="phone" value="{{ old('phone') }}" class="form-control"></div></div>
                            <div class="col-sm-3"><div class="form-group"><label>Alternate Phone</label><input type="text" name="alternate_phone" value="{{ old('alternate_phone') }}" class="form-control"></div></div>
                            <div class="col-sm-3"><div class="form-group"><label>Mobile</label><input type="text" name="mobile" value="{{ old('mobile') }}" class="form-control"></div></div>
                            <div class="col-sm-3"><div class="form-group"><label>Whatsapp No</label><input type="text" name="whatsapp_no" value="{{ old('whatsapp_no') }}" class="form-control"></div></div>
                            <div class="col-sm-3"><div class="form-group"><label>Website</label><input type="text" name="website" value="{{ old('website') }}" class="form-control"></div></div>
                            <div class="col-sm-3"><div class="form-group"><label>Contact Person Name</label><input type="text" name="contact_person_name" value="{{ old('contact_person_name') }}" class="form-control"></div></div>
                            <div class="col-sm-3"><div class="form-group"><label>Contact Person Designation</label><input type="text" name="contact_person_designation" value="{{ old('contact_person_designation') }}" class="form-control"></div></div>
                            <div class="col-sm-3"><div class="form-group"><label>Contact Person Mobile</label><input type="text" name="contact_person_mobile" value="{{ old('contact_person_mobile') }}" class="form-control"></div></div>
                            <div class="col-sm-3"><div class="form-group"><label>Contact Person Email</label><input type="email" name="contact_person_email" value="{{ old('contact_person_email') }}" class="form-control"></div></div>
                        </div>

                        <div class="border-bottom"><h4>Registered Address</h4></div>
                        <div class="row">
                            <div class="col-sm-6"><div class="form-group"><label>Address 1</label><textarea name="address_1" class="form-control" rows="2">{{ old('address_1') }}</textarea></div></div>
                            <div class="col-sm-6"><div class="form-group"><label>Address 2</label><textarea name="address_2" class="form-control" rows="2">{{ old('address_2') }}</textarea></div></div>
                            <div class="col-sm-3"><div class="form-group"><label>Landmark</label><input type="text" name="landmark" value="{{ old('landmark') }}" class="form-control"></div></div>
                            <div class="col-sm-3"><div class="form-group"><label>State</label><select name="state_id" class="form-control"><option value="">Select State</option>@foreach ($states as $state)<option value="{{ $state->id }}" @selected((string) old('state_id') === (string) $state->id)>{{ $state->name }}</option>@endforeach</select></div></div>
                            <div class="col-sm-3"><div class="form-group"><label>City Name</label><input type="text" name="city_name" value="{{ old('city_name') }}" class="form-control"></div></div>
                            <div class="col-sm-3"><div class="form-group"><label>District Name</label><input type="text" name="district_name" value="{{ old('district_name') }}" class="form-control"></div></div>
                            <div class="col-sm-3"><div class="form-group"><label>Pincode</label><input type="text" name="pincode" value="{{ old('pincode') }}" class="form-control"></div></div>
                        </div>

                        <div class="border-bottom"><h4>Billing Address</h4></div>
                        <div class="row">
                            <div class="col-sm-6"><div class="form-group"><label>Billing Address 1</label><textarea name="billing_address_1" class="form-control" rows="2">{{ old('billing_address_1') }}</textarea></div></div>
                            <div class="col-sm-6"><div class="form-group"><label>Billing Address 2</label><textarea name="billing_address_2" class="form-control" rows="2">{{ old('billing_address_2') }}</textarea></div></div>
                            <div class="col-sm-3"><div class="form-group"><label>Billing State</label><select name="billing_state_id" class="form-control"><option value="">Select State</option>@foreach ($states as $state)<option value="{{ $state->id }}" @selected((string) old('billing_state_id') === (string) $state->id)>{{ $state->name }}</option>@endforeach</select></div></div>
                            <div class="col-sm-3"><div class="form-group"><label>Billing City Name</label><input type="text" name="billing_city_name" value="{{ old('billing_city_name') }}" class="form-control"></div></div>
                            <div class="col-sm-3"><div class="form-group"><label>Billing Pincode</label><input type="text" name="billing_pincode" value="{{ old('billing_pincode') }}" class="form-control"></div></div>
                        </div>

                        <div class="border-bottom"><h4>Bank Details</h4></div>
                        <div class="row">
                            <div class="col-sm-3"><div class="form-group"><label>Bank Name</label><input type="text" name="bank_name" value="{{ old('bank_name') }}" class="form-control"></div></div>
                            <div class="col-sm-3"><div class="form-group"><label>Bank Branch Name</label><input type="text" name="bank_branch_name" value="{{ old('bank_branch_name') }}" class="form-control"></div></div>
                            <div class="col-sm-3"><div class="form-group"><label>Account Holder Name</label><input type="text" name="bank_account_holder_name" value="{{ old('bank_account_holder_name') }}" class="form-control"></div></div>
                            <div class="col-sm-3"><div class="form-group"><label>Account No</label><input type="text" name="bank_account_no" value="{{ old('bank_account_no') }}" class="form-control"></div></div>
                            <div class="col-sm-3"><div class="form-group"><label>Account Type</label><input type="text" name="bank_account_type" value="{{ old('bank_account_type') }}" class="form-control"></div></div>
                            <div class="col-sm-3"><div class="form-group"><label>IFSC Code</label><input type="text" name="bank_ifsc_code" value="{{ old('bank_ifsc_code') }}" class="form-control"></div></div>
                            <div class="col-sm-3"><div class="form-group"><label>MICR Code</label><input type="text" name="bank_micr_code" value="{{ old('bank_micr_code') }}" class="form-control"></div></div>
                            <div class="col-sm-3"><div class="form-group"><label>SWIFT Code</label><input type="text" name="bank_swift_code" value="{{ old('bank_swift_code') }}" class="form-control"></div></div>
                            <div class="col-sm-3"><div class="form-group"><label>UPI Id</label><input type="text" name="bank_upi_id" value="{{ old('bank_upi_id') }}" class="form-control"></div></div>
                        </div>

                        <div class="border-bottom"><h4>Invoice And Accounting Settings</h4></div>
                        <div class="row">
                            <div class="col-sm-3"><div class="form-group"><label>Invoice Prefix</label><input type="text" name="invoice_prefix" value="{{ old('invoice_prefix') }}" class="form-control"></div></div>
                            <div class="col-sm-3"><div class="form-group"><label>Quotation Prefix</label><input type="text" name="quotation_prefix" value="{{ old('quotation_prefix') }}" class="form-control"></div></div>
                            <div class="col-sm-3"><div class="form-group"><label>Purchase Prefix</label><input type="text" name="purchase_prefix" value="{{ old('purchase_prefix') }}" class="form-control"></div></div>
                            <div class="col-sm-3"><div class="form-group"><label>Currency Code</label><input type="text" name="currency_code" value="{{ old('currency_code', 'INR') }}" class="form-control"></div></div>
                            <div class="col-sm-3"><div class="form-group"><label>Currency Symbol</label><input type="text" name="currency_symbol" value="{{ old('currency_symbol', 'Rs') }}" class="form-control"></div></div>
                            <div class="col-sm-3"><div class="form-group"><label>Timezone</label><input type="text" name="timezone" value="{{ old('timezone', 'Asia/Kolkata') }}" class="form-control"></div></div>
                            <div class="col-sm-3"><div class="form-group"><label>Date Format</label><input type="text" name="date_format" value="{{ old('date_format', 'd-m-Y') }}" class="form-control"></div></div>
                            <div class="col-sm-3"><div class="form-group"><label>Decimal Places</label><input type="number" name="decimal_places" value="{{ old('decimal_places', 2) }}" class="form-control"></div></div>
                            <div class="col-sm-3"><div class="form-group"><label>Default Tax %</label><input type="number" step="0.01" name="default_tax_percentage" value="{{ old('default_tax_percentage', 0) }}" class="form-control"></div></div>
                            <div class="col-sm-3"><div class="form-group"><label>Credit Limit</label><input type="number" step="0.01" name="credit_limit" value="{{ old('credit_limit', 0) }}" class="form-control"></div></div>
                            <div class="col-sm-3"><div class="form-group"><label>Default Credit Days</label><input type="number" name="default_credit_days" value="{{ old('default_credit_days', 0) }}" class="form-control"></div></div>
                            <div class="col-sm-6"><div class="form-group"><label>Invoice Terms</label><textarea name="invoice_terms" class="form-control" rows="3">{{ old('invoice_terms') }}</textarea></div></div>
                            <div class="col-sm-6"><div class="form-group"><label>Invoice Footer</label><textarea name="invoice_footer" class="form-control" rows="3">{{ old('invoice_footer') }}</textarea></div></div>
                        </div>

                        <div class="border-bottom"><h4>Company Files</h4></div>
                        <div class="row">
                            <div class="col-sm-3"><div class="form-group"><label>Logo</label><input type="text" name="logo" value="{{ old('logo') }}" class="form-control"></div></div>
                            <div class="col-sm-3"><div class="form-group"><label>Favicon</label><input type="text" name="favicon" value="{{ old('favicon') }}" class="form-control"></div></div>
                            <div class="col-sm-3"><div class="form-group"><label>Letterhead</label><input type="text" name="letterhead" value="{{ old('letterhead') }}" class="form-control"></div></div>
                            <div class="col-sm-3"><div class="form-group"><label>Signature Image</label><input type="text" name="signature_image" value="{{ old('signature_image') }}" class="form-control"></div></div>
                            <div class="col-sm-3"><div class="form-group"><label>Company Stamp</label><input type="text" name="company_stamp" value="{{ old('company_stamp') }}" class="form-control"></div></div>
                            <div class="col-sm-3"><div class="form-group"><label>QR Code Image</label><input type="text" name="qr_code_image" value="{{ old('qr_code_image') }}" class="form-control"></div></div>
                        </div>

                        <div class="border-bottom"><h4>Additional Details</h4></div>
                        <div class="row">
                            <div class="col-sm-3"><div class="form-group"><label>Latitude</label><input type="text" name="latitude" value="{{ old('latitude') }}" class="form-control"></div></div>
                            <div class="col-sm-3"><div class="form-group"><label>Longitude</label><input type="text" name="longitude" value="{{ old('longitude') }}" class="form-control"></div></div>
                            <div class="col-sm-6"><div class="form-group"><label>Terms And Conditions</label><textarea name="terms_and_conditions" class="form-control" rows="3">{{ old('terms_and_conditions') }}</textarea></div></div>
                            <div class="col-sm-6"><div class="form-group"><label>Remarks</label><textarea name="remarks" class="form-control" rows="3">{{ old('remarks') }}</textarea></div></div>
                            <div class="col-sm-3"><div class="form-group"><label>Status</label><select name="status" class="form-control"><option value="Active" @selected(old('status', 'Active') === 'Active')>Active</option><option value="Inactive" @selected(old('status', 'Active') === 'Inactive')>Inactive</option></select></div></div>
                        </div>
                        <div class="reset-button"><a href="{{ route('admin.companies.index') }}" class="btn btn-warning">Cancel</a> <button type="submit" class="btn btn-success">Save</button></div>
                    </form>
                </div>
            </div></div></div></section>
        </div>
        @include('admin.common.footer')
    </div>
    @include('admin.common.formfooterscript')
</body>
</html>
