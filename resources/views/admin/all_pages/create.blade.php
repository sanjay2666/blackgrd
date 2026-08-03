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
            <section class="content-header"><div class="header-icon"><i class="fa fa-list"></i></div><div class="header-title"><h1>Add All Pages</h1><small>All Pages</small></div></section>
            <section class="content"><div class="row"><div class="col-sm-12"><div class="panel panel-bd lobidrag">
                <div class="panel-heading"><a href="{{ route('admin.all-pages.index') }}" class="btn btn-add"><i class="fa fa-list"></i> All Pages List</a></div>
                <div class="panel-body">
                    {!! display_message('message') !!}
                    <form method="POST" action="{{ route('admin.all-pages.store') }}">
                        @csrf
                        <div class="row">
                            <div class="col-sm-4"><div class="form-group"><label>Model Name</label><input type="text" name="model_name" value="{{ old('model_name') }}" class="form-control"></div></div>
                            <div class="col-sm-4"><div class="form-group"><label>Page Title <span class="required">*</span></label><input type="text" name="page_title" value="{{ old('page_title') }}" class="form-control" required></div></div>
                            <div class="col-sm-4"><div class="form-group"><label>Page Name <span class="required">*</span></label><input type="text" name="page_name" value="{{ old('page_name') }}" class="form-control" required></div></div>
                            <div class="col-sm-4"><div class="form-group"><label>Page Rank <span class="required">*</span></label><input type="number" name="page_rank" value="{{ old('page_rank') }}" class="form-control" step="any" required></div></div>
                            <div class="col-sm-4"><div class="form-group"><label>Status</label><select name="status" class="form-control"><option value="1" @selected((string) old('status', '1') === '1')>Active</option><option value="0" @selected((string) old('status', '1') === '0')>Inactive</option></select></div></div>
                        </div>
                        <div class="reset-button"><a href="{{ route('admin.all-pages.index') }}" class="btn btn-warning">Cancel</a> <button type="submit" class="btn btn-success">Save</button></div>
                    </form>
                </div>
            </div></div></div></section>
        </div>
        @include('admin.common.footer')
    </div>
    @include('admin.common.formfooterscript')
</body>
</html>
