<!DOCTYPE html>
<html lang="en">
<head>@include('admin.common.head')</head>
<body class="hold-transition sidebar-mini">
<div class="wrapper">
    @include('admin.common.header')
    @include('admin.common.sidebar')
    <div class="content-wrapper">
        <section class="content-header"><div class="header-icon"><i class="fa fa-building"></i></div><div class="header-title"><h1>Edit Company Profile</h1><small>One canonical company per installation</small></div></section>
        <section class="content">
            <div class="panel panel-bd lobidrag"><div class="panel-heading"><h4>Company Profile</h4></div><div class="panel-body">
                {!! display_message('message') !!}
                @if ($errors->any()) <div class="alert alert-danger">{{ $errors->first() }}</div> @endif
                <form method="POST" action="{{ route('admin.companies.update') }}" enctype="multipart/form-data">
                    @csrf @method('PUT')
                    <div class="border-bottom"><h4>Company Identity</h4></div><div class="row">
                        @foreach (['company_code'=>'Company Code','name'=>'Display Name','legal_name'=>'Legal Company Name','trade_name'=>'Trade Name'] as $field => $label)
                            <div class="col-sm-3"><div class="form-group"><label>{{ $label }}@if ($field === 'name') <span class="required">*</span>@endif</label><input name="{{ $field }}" value="{{ old($field, $company->$field) }}" class="form-control" @if ($field === 'name') required @endif></div></div>
                        @endforeach
                    </div>
                    <div class="border-bottom"><h4>Contact Details</h4></div><div class="row">
                        @foreach (['email'=>'Email','alternate_email'=>'Alternate Email','phone'=>'Phone','mobile'=>'Mobile','website'=>'Website','contact_person_name'=>'Authorized Contact','contact_person_designation'=>'Contact Designation','contact_person_mobile'=>'Contact Mobile','contact_person_email'=>'Contact Email'] as $field => $label)
                            <div class="col-sm-3"><div class="form-group"><label>{{ $label }}</label><input name="{{ $field }}" value="{{ old($field, $company->$field) }}" class="form-control"></div></div>
                        @endforeach
                    </div>
                    <div class="border-bottom"><h4>Registered Address</h4></div><div class="row">
                        <div class="col-sm-6"><div class="form-group"><label>Address Line 1</label><textarea name="address_1" class="form-control" rows="2">{{ old('address_1', $company->address_1) }}</textarea></div></div>
                        <div class="col-sm-6"><div class="form-group"><label>Address Line 2</label><textarea name="address_2" class="form-control" rows="2">{{ old('address_2', $company->address_2) }}</textarea></div></div>
                        @foreach (['landmark'=>'Landmark','city_name'=>'City','district_name'=>'District','pincode'=>'PIN Code'] as $field => $label)
                            <div class="col-sm-3"><div class="form-group"><label>{{ $label }}</label><input name="{{ $field }}" value="{{ old($field, $company->$field) }}" class="form-control"></div></div>
                        @endforeach
                        <div class="col-sm-3"><div class="form-group"><label>State</label><select name="state_id" class="form-control"><option value="">Select State</option>@foreach ($states as $state)<option value="{{ $state->id }}" @selected((string) old('state_id', $company->state_id) === (string) $state->id)>{{ $state->name }}</option>@endforeach</select></div></div>
                    </div>
                    <div class="border-bottom"><h4>Tax and Legal Details</h4></div><div class="row">
                        @foreach (['registration_no'=>'Registration No.','pan_no'=>'PAN','tan_no'=>'TAN','gstin'=>'GSTIN'] as $field => $label)
                            <div class="col-sm-3"><div class="form-group"><label>{{ $label }}</label><input name="{{ $field }}" value="{{ old($field, $company->$field) }}" class="form-control"></div></div>
                        @endforeach
                    </div>
                    <div class="border-bottom"><h4>Branding</h4></div><div class="row">
                        <div class="col-sm-6"><div class="form-group"><label>Company Logo</label><input type="file" name="logo" class="form-control" accept=".jpg,.jpeg,.png,.webp"><p class="help-block">JPG, PNG or WEBP; maximum 2 MB. Existing logo is preserved when no replacement is uploaded.</p></div></div>
                        <div class="col-sm-6">@if ($company->logo)<p><strong>Current logo</strong></p><img src="{{ asset('storage/'.$company->logo) }}" alt="Company logo" style="max-height:70px;max-width:220px">@endif</div>
                    </div>
                    <div class="reset-button"><a href="{{ route('admin.companies.index') }}" class="btn btn-warning">Cancel</a> <button type="submit" class="btn btn-success">Save Profile</button></div>
                </form>
            </div></div>
        </section>
    </div>
    @include('admin.common.footer')
</div>
@include('admin.common.formfooterscript')
</body>
</html>
