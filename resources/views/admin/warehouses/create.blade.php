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
            <section class="content-header"><div class="header-icon"><i class="fa fa-list"></i></div><div class="header-title"><h1>Add Warehouses</h1><small>Warehouses</small></div></section>
            <section class="content"><div class="row"><div class="col-sm-12"><div class="panel panel-bd lobidrag">
                <div class="panel-heading"><a href="{{ route('admin.warehouses.index') }}" class="btn btn-add"><i class="fa fa-list"></i> Warehouses List</a></div>
                <div class="panel-body">
                    {!! display_message('message') !!}
                    <form method="POST" action="{{ route('admin.warehouses.store') }}">
                        @csrf
                        <div class="row">
                            <div class="col-sm-4"><div class="form-group"><label>Warehouse Name <span class="required">*</span></label><input type="text" name="warehouse_name" value="{{ old('warehouse_name') }}" class="form-control" required></div></div>
                            <div class="col-sm-4"><div class="form-group"><label>Location <span class="required">*</span></label><input type="text" name="location" value="{{ old('location') }}" class="form-control" required></div></div>
                            <div class="col-sm-4"><div class="form-group"><label>Capacity <span class="required">*</span></label><input type="text" name="capacity" value="{{ old('capacity') }}" class="form-control" required></div></div>
                            <div class="col-sm-4"><div class="form-group"><label>Supervisor Id</label><input type="number" name="supervisor_id" value="{{ old('supervisor_id') }}" class="form-control" step="any"></div></div>
                            <div class="col-sm-4"><div class="form-group"><label>Contact Number <span class="required">*</span></label><input type="text" name="contact_number" value="{{ old('contact_number') }}" class="form-control" required></div></div>
                            <div class="col-sm-4"><div class="form-group"><label>&nbsp;</label><div class="checkbox"><label><input type="checkbox" name="process_type_id" value="1" @checked(old('process_type_id'))> Process Type Id</label></div></div></div>
                            <div class="col-sm-4"><div class="form-group"><label>Status</label><select name="status" class="form-control"><option value="Active" @selected(old('status', 'Active') === 'Active')>Active</option><option value="Inactive" @selected(old('status', 'Active') === 'Inactive')>Inactive</option></select></div></div>
                        </div>
                        <div class="reset-button"><a href="{{ route('admin.warehouses.index') }}" class="btn btn-warning">Cancel</a> <button type="submit" class="btn btn-success">Save</button></div>
                    </form>
                </div>
            </div></div></div></section>
        </div>
        @include('admin.common.footer')
    </div>
    @include('admin.common.formfooterscript')
</body>
</html>
