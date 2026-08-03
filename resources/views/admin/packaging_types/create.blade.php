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
            <section class="content-header"><div class="header-icon"><i class="fa fa-list"></i></div><div class="header-title"><h1>Add Packaging Types</h1><small>Packaging Types</small></div></section>
            <section class="content"><div class="row"><div class="col-sm-12"><div class="panel panel-bd lobidrag">
                <div class="panel-heading"><a href="{{ route('admin.packaging-types.index') }}" class="btn btn-add"><i class="fa fa-list"></i> Packaging Types List</a></div>
                <div class="panel-body">
                    {!! display_message('message') !!}
                    <form method="POST" action="{{ route('admin.packaging-types.store') }}">
                        @csrf
                        <div class="row">
                            <div class="col-sm-4"><div class="form-group"><label>Name <span class="required">*</span></label><input type="text" name="name" value="{{ old('name') }}" class="form-control" required></div></div>
                            <div class="col-sm-4"><div class="form-group"><label>Status</label><select name="status" class="form-control"><option value="Active" @selected(old('status', 'Active') === 'Active')>Active</option><option value="Inactive" @selected(old('status', 'Active') === 'Inactive')>Inactive</option></select></div></div>
                        </div>
                        <div class="reset-button"><a href="{{ route('admin.packaging-types.index') }}" class="btn btn-warning">Cancel</a> <button type="submit" class="btn btn-success">Save</button></div>
                    </form>
                </div>
            </div></div></div></section>
        </div>
        @include('admin.common.footer')
    </div>
    @include('admin.common.formfooterscript')
</body>
</html>
