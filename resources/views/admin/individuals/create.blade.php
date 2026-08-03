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
            <section class="content-header">
                <div class="header-icon"><i class="fa fa-users"></i></div>
                <div class="header-title"><h1>Add Individual</h1><small>Individual list</small></div>
            </section>
            <section class="content">
                <div class="row">
                    <div class="col-sm-12">
                        <div class="panel panel-bd lobidrag">
                            <div class="panel-heading">
                                <div class="btn-group" id="buttonlist"><a class="btn btn-add" href="{{ route('admin.individuals.index') }}"><i class="fa fa-list"></i> Individual List</a></div>
                            </div>
                            <div class="panel-body">
                                {!! display_message('message') !!}
                                @if ($errors->any())
                                    <div class="alert alert-danger"><strong>Please fix the errors below.</strong></div>
                                @endif

                                <form method="POST" action="{{ route('admin.individuals.store') }}" id="individualForm">
                                    @csrf
                                    <div class="row">
                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <label>Individual Type <span class="required">*</span></label>
                                                <select name="type" id="individual_type" class="form-control" required>
                                                    <option value="">Select Individual</option>
                                                    @foreach ($types as $type)
                                                        <option value="{{ $type }}" @selected(old('type', 'customers') === $type)>{{ ucfirst($type) }}</option>
                                                    @endforeach
                                                </select>
                                                @error('type')<span class="text-danger small">{{ $message }}</span>@enderror
                                            </div>
                                        </div>
                                        <div class="col-sm-6" id="vendor_type_box">
                                            <div class="form-group">
                                                <label>Vendor Type <span class="required">*</span></label>
                                                <select name="vendor_type" id="vendor_type" class="form-control">
                                                    <option value="">Select Vendor Type</option>
                                                    @foreach ($vendorTypes as $vendorType)
                                                        <option value="{{ $vendorType }}" @selected(old('vendor_type') === $vendorType)>{{ ucfirst($vendorType) }}</option>
                                                    @endforeach
                                                </select>
                                                @error('vendor_type')<span class="text-danger small">{{ $message }}</span>@enderror
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <label>Name <span class="required">*</span></label>
                                                <input type="text" name="name" value="{{ old('name') }}" class="form-control" placeholder="Enter Name" required>
                                                @error('name')<span class="text-danger small">{{ $message }}</span>@enderror
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <label>Department</label>
                                                <select name="department_id" id="department_id" class="form-control">
                                                    <option value="">Select Department</option>
                                                    @foreach ($departments as $department)
                                                        <option value="{{ $department->id }}" @selected((string) old('department_id') === (string) $department->id)>{{ $department->department_name }}</option>
                                                    @endforeach
                                                </select>
                                                @error('department_id')<span class="text-danger small">{{ $message }}</span>@enderror
                                            </div>
                                        </div>
                                        <div class="col-sm-6" id="process_box">
                                            <div class="form-group">
                                                <label>Process</label>
                                                <select name="process_type_id" id="process_type_id" class="form-control">
                                                    <option value="">Select Process</option>
                                                    @foreach ($processItems as $processItem)
                                                        <option value="{{ $processItem->id }}" @selected((string) old('process_type_id') === (string) $processItem->id)>{{ $processItem->process_name }}</option>
                                                    @endforeach
                                                </select>
                                                @error('process_type_id')<span class="text-danger small">{{ $message }}</span>@enderror
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <label>Mobile <span class="required">*</span></label>
                                                <input type="number" name="phone" value="{{ old('phone') }}" class="form-control" maxlength="10" placeholder="Enter Mobile" required>
                                                @error('phone')<span class="text-danger small">{{ $message }}</span>@enderror
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <label>Email <span class="required">*</span></label>
                                                <input type="email" name="email" value="{{ old('email') }}" class="form-control" placeholder="Enter Email" required>
                                                @error('email')<span class="text-danger small">{{ $message }}</span>@enderror
                                            </div>
                                        </div>
                                        <div class="col-sm-6" id="password_box">
                                            <div class="form-group">
                                                <label>Password <span class="required">*</span></label>
                                                <input type="password" name="password" id="password" class="form-control" placeholder="Enter Password">
                                                @error('password')<span class="text-danger small">{{ $message }}</span>@enderror
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <label>Company Name</label>
                                                <input type="text" name="company_name" value="{{ old('company_name') }}" class="form-control" placeholder="Enter Company Name">
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <label>Nick Name</label>
                                                <input type="text" name="nick_name" value="{{ old('nick_name') }}" class="form-control" placeholder="Enter Nick Name">
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <label>GSTIN <span id="gstin_required_mark" class="required" style="display:none">*</span></label>
                                                <input type="text" name="gstin" id="gstin" value="{{ old('gstin') }}" class="form-control" placeholder="Enter GST Number">
                                                @error('gstin')<span class="text-danger small">{{ $message }}</span>@enderror
                                            </div>
                                        </div>
                                        <div class="col-sm-6"><div class="form-group"><label>PAN</label><input type="text" name="pan" value="{{ old('pan') }}" class="form-control" placeholder="Enter PAN"></div></div>
                                        <div class="col-sm-6"><div class="form-group"><label>TAN Number</label><input type="text" name="tanno" value="{{ old('tanno') }}" class="form-control" placeholder="Enter TAN Number"></div></div>
                                        <div class="col-sm-6" id="adhar_box"><div class="form-group"><label>Aadhaar Number</label><input type="number" name="adhar" value="{{ old('adhar') }}" class="form-control" placeholder="Enter Aadhaar Number"></div></div>
                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <label>WhatsApp <span class="required">*</span></label>
                                                <input type="number" name="whatsapp" value="{{ old('whatsapp') }}" class="form-control" maxlength="10" placeholder="Enter WhatsApp Number" required>
                                                @error('whatsapp')<span class="text-danger small">{{ $message }}</span>@enderror
                                            </div>
                                        </div>
                                        <div class="col-sm-6"><div class="form-group"><label>Verified Remark</label><input type="text" name="verified_remark" value="{{ old('verified_remark') }}" class="form-control" placeholder="Enter Verified Remark"></div></div>
                                        <div class="col-sm-6"><div class="form-group"><label>Is Verified</label><select name="is_verified" class="form-control"><option value="yes" @selected(old('is_verified', 'no') === 'yes')>Yes</option><option value="no" @selected(old('is_verified', 'no') === 'no')>No</option></select></div></div>
                                        <div class="col-sm-6"><div class="form-group"><label>Lab Test Required</label><select name="is_lab_test_required" class="form-control"><option value="No" @selected(old('is_lab_test_required', 'No') === 'No')>No</option><option value="Yes" @selected(old('is_lab_test_required') === 'Yes')>Yes</option></select></div></div>
                                        <div class="col-sm-6"><div class="form-group"><label>Status</label><select name="status" class="form-control"><option value="Active" @selected(old('status', 'Active') === 'Active')>Active</option><option value="Inactive" @selected(old('status') === 'Inactive')>Inactive</option></select></div></div>
                                    </div>

                                    <div id="address-section">
                                        <div class="border-bottom pb-3 mt-5 mb-4">
                                            <h4>Individual Address</h4>
                                            <p class="text-muted">Billing and shipping address fields are entered together.</p>
                                        </div>
                                        <div class="checkbox checkbox-success">
                                            <input type="hidden" name="same_as_billing" value="0">
                                            <input id="same_as_billing" name="same_as_billing" type="checkbox" value="1" @checked(old('same_as_billing'))>
                                            <label for="same_as_billing">Shipping address same as billing address</label>
                                        </div>
                                        <div class="row">
                                            <div class="col-sm-6">
                                                <div class="panel panel-default"><div class="panel-heading"><strong>Billing Address</strong></div><div class="panel-body">
                                                    <div class="form-group"><label>Address 1 <span class="required">*</span></label><textarea id="billing_address_1" name="billing[address_1]" rows="2" class="form-control" required>{{ old('billing.address_1') }}</textarea>@error('billing.address_1')<span class="text-danger small">{{ $message }}</span>@enderror</div>
                                                    <div class="form-group"><label>Address 2 <span class="required">*</span></label><textarea id="billing_address_2" name="billing[address_2]" rows="2" class="form-control" required>{{ old('billing.address_2') }}</textarea>@error('billing.address_2')<span class="text-danger small">{{ $message }}</span>@enderror</div>
                                                    <div class="row"><div class="col-sm-4 form-group"><label>State <span class="required">*</span></label><select id="billing_state_id" name="billing[state_id]" class="form-control" required><option value="">Select State</option>@foreach ($states as $state)<option value="{{ $state->id }}" @selected((string) old('billing.state_id') === (string) $state->id)>{{ $state->name }}</option>@endforeach</select>@error('billing.state_id')<span class="text-danger small">{{ $message }}</span>@enderror</div><div class="col-sm-4 form-group"><label>City <span class="required">*</span></label><input id="billing_city" name="billing[city]" type="text" value="{{ old('billing.city') }}" class="form-control" required>@error('billing.city')<span class="text-danger small">{{ $message }}</span>@enderror</div><div class="col-sm-4 form-group"><label>Zip Code <span class="required">*</span></label><input id="billing_zip_code" name="billing[zip_code]" type="text" value="{{ old('billing.zip_code') }}" class="form-control" required>@error('billing.zip_code')<span class="text-danger small">{{ $message }}</span>@enderror</div></div>
                                                </div></div>
                                            </div>
                                            <div class="col-sm-6" id="shipping_box">
                                                <div class="panel panel-default"><div class="panel-heading"><strong>Shipping Address</strong></div><div class="panel-body">
                                                    <div class="form-group"><label>Address 1 <span class="required">*</span></label><textarea id="shipping_address_1" name="shipping[address_1]" rows="2" class="form-control" required>{{ old('shipping.address_1') }}</textarea>@error('shipping.address_1')<span class="text-danger small">{{ $message }}</span>@enderror</div>
                                                    <div class="form-group"><label>Address 2 <span class="required">*</span></label><textarea id="shipping_address_2" name="shipping[address_2]" rows="2" class="form-control" required>{{ old('shipping.address_2') }}</textarea>@error('shipping.address_2')<span class="text-danger small">{{ $message }}</span>@enderror</div>
                                                    <div class="row"><div class="col-sm-4 form-group"><label>State <span class="required">*</span></label><select id="shipping_state_id" name="shipping[state_id]" class="form-control" required><option value="">Select State</option>@foreach ($states as $state)<option value="{{ $state->id }}" @selected((string) old('shipping.state_id') === (string) $state->id)>{{ $state->name }}</option>@endforeach</select>@error('shipping.state_id')<span class="text-danger small">{{ $message }}</span>@enderror</div><div class="col-sm-4 form-group"><label>City <span class="required">*</span></label><input id="shipping_city" name="shipping[city]" type="text" value="{{ old('shipping.city') }}" class="form-control" required>@error('shipping.city')<span class="text-danger small">{{ $message }}</span>@enderror</div><div class="col-sm-4 form-group"><label>Zip Code <span class="required">*</span></label><input id="shipping_zip_code" name="shipping[zip_code]" type="text" value="{{ old('shipping.zip_code') }}" class="form-control" required>@error('shipping.zip_code')<span class="text-danger small">{{ $message }}</span>@enderror</div></div>
                                                </div></div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="reset-button">
                                        <a href="{{ route('admin.individuals.index') }}" class="btn btn-warning">Cancel</a>
                                        <button type="submit" class="btn btn-success">Save</button>
                                    </div>
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
    <script>
        function limitLength(input, max) { if (input.value.length > max) input.value = input.value.slice(0, max); }
        function toggleIndividualFields() {
            var selectedType = $('#individual_type').val();
            var isVendor = selectedType === 'vendors';
            $('#vendor_type_box').toggle(isVendor);
            $('#vendor_type').prop('required', isVendor);
            if (!isVendor) { $('#vendor_type').val(''); }
            $('#password_box').toggle(selectedType === 'employee');
            $('#password').prop('required', selectedType === 'employee');
            $('#adhar_box').toggle(selectedType !== 'employee' && selectedType !== 'transport');
            $('#process_box').toggle(selectedType !== 'transport');
            $('#gstin').prop('required', selectedType === 'vendors' || selectedType === 'customers');
            $('#gstin_required_mark').toggle(selectedType === 'vendors' || selectedType === 'customers');
        }
        function copyBillingToShipping() {
            $('#shipping_address_1').val($('#billing_address_1').val());
            $('#shipping_address_2').val($('#billing_address_2').val());
            $('#shipping_state_id').val($('#billing_state_id').val());
            $('#shipping_city').val($('#billing_city').val());
            $('#shipping_zip_code').val($('#billing_zip_code').val());
        }

        function toggleShippingAddress() {
            var same = $('#same_as_billing').is(':checked');
            if (same) {
                copyBillingToShipping();
            }

            $('#shipping_box').show();
            $('#shipping_box').find('textarea,input')
                .prop('readonly', same)
                .prop('required', !same);
        }
        $('#individual_type').on('change', toggleIndividualFields);
        $('#same_as_billing').on('change', toggleShippingAddress);
        $('#billing_address_1,#billing_address_2,#billing_state_id,#billing_city,#billing_zip_code').on('input change', function () {
            if ($('#same_as_billing').is(':checked')) toggleShippingAddress();
        });
        $('input[name="phone"], input[name="whatsapp"]').on('input', function () { limitLength(this, 10); });
        $('input[name="adhar"]').on('input', function () { limitLength(this, 12); });
        toggleIndividualFields();
        toggleShippingAddress();
    </script>
</body>
</html>
