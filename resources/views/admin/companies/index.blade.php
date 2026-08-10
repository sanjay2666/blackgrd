<!DOCTYPE html>
<html lang="en">
<head>@include('admin.common.head')</head>
<body class="hold-transition sidebar-mini">
<div class="wrapper">
    @include('admin.common.header')
    @include('admin.common.sidebar')
    <div class="content-wrapper">
        <section class="content-header"><div class="header-icon"><i class="fa fa-building"></i></div><div class="header-title"><h1>Company Profile</h1><small>Canonical company identity for this ERP installation</small></div></section>
        <section class="content">
            {!! display_message('message') !!}
            <div class="panel panel-bd lobidrag">
                <div class="panel-heading"><h4>Company Profile</h4></div>
                <div class="panel-body">
                    <p class="text-muted">This installation supports one company profile. Branches and factories are separate operational locations.</p>
                    <div class="row">
                        <div class="col-sm-4"><strong>Legal name</strong><br>{{ $company->legal_name ?: $company->name }}</div>
                        <div class="col-sm-4"><strong>Trade/display name</strong><br>{{ $company->trade_name ?: $company->name }}</div>
                        <div class="col-sm-4"><strong>GSTIN</strong><br>{{ $company->gstin ?: 'Not provided' }}</div>
                    </div>
                    <hr>
                    <a href="{{ route('admin.companies.edit') }}" class="btn btn-success"><i class="fa fa-pencil"></i> Edit Profile</a>
                </div>
            </div>
        </section>
    </div>
    @include('admin.common.footer')
</div>
@include('admin.common.formfooterscript')
</body>
</html>
